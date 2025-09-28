<?php

namespace App\Jobs;

use App\Models\Transfer;
use App\Models\WebhookEvent;
use App\Services\SafeHaven;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessPawaPayDeposit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $backoff = 15;

    public function __construct(private int $eventId, private array $payload) {}

    public function handle(SafeHaven $safeHaven): void
    {
        $event = WebhookEvent::find($this->eventId);
        if (!$event) { return; }
        if ($event->processed_at) { return; }

        $payload = $this->payload;
        $depositId = $payload['depositId'] ?? $payload['id'] ?? null;
        $status = strtoupper($payload['status'] ?? '');
        $reference = $payload['reference'] ?? null;
        if (!$depositId) { return; }

        DB::transaction(function () use ($depositId, $status, $reference, $payload, $safeHaven, $event) {
            $transfer = Transfer::where('payin_ref', $depositId)->lockForUpdate()->first();
            if (!$transfer) {
                Log::warning('Deposit webhook: transfer not found', ['depositId' => $depositId]);
                $event->update(['processed_at' => now()]);
                return;
            }

            $timeline = is_array($transfer->timeline) ? $transfer->timeline : [];
            $timeline[] = [
                'state' => 'payin_webhook_received',
                'status' => $status,
                'at' => now()->toIso8601String(),
                'reference' => $reference
            ];

            switch ($status) {
                case 'COMPLETED':
                    $this->handleCompletedPayment($transfer, $timeline, $safeHaven);
                    break;
                case 'FAILED':
                case 'REJECTED':
                case 'EXPIRED':
                    $this->handleFailedPayment($transfer, $timeline, $payload);
                    break;
                case 'PENDING':
                    $this->handlePendingPayment($transfer, $timeline);
                    break;
                default:
                    Log::warning('Deposit webhook: Unknown status', ['status' => $status, 'transfer_id' => $transfer->id]);
                    $timeline[] = [
                        'state' => 'payin_unknown_status',
                        'at' => now()->toIso8601String(),
                        'status' => $status,
                        'payload' => $payload,
                    ];
                    $transfer->update(['timeline' => $timeline]);
            }

            $event->update(['processed_at' => now()]);
        });
    }

    protected function handleCompletedPayment(Transfer $transfer, array &$timeline, SafeHaven $safeHaven): void
    {
        if (in_array($transfer->status, ['payout_pending', 'completed', 'payout_success'])) {
            Log::info('Payment already processed', ['transfer_id' => $transfer->id, 'current_status' => $transfer->status]);
            return;
        }

        $timeline[] = [
            'state' => 'payin_completed',
            'at' => now()->toIso8601String(),
            'amount' => $transfer->amount_xaf,
        ];

        $transfer->update([
            'payin_status' => 'success',
            'status' => 'payout_pending',
            'payin_at' => now(),
            'timeline' => $timeline,
        ]);

        $this->initiatePayout($transfer, $timeline, $safeHaven);
    }

    protected function handleFailedPayment(Transfer $transfer, array &$timeline, array $payload): void
    {
        $reason = $payload['reason'] ?? $payload['message'] ?? 'Payment failed';
        $timeline[] = [
            'state' => 'payin_failed',
            'at' => now()->toIso8601String(),
            'reason' => $reason,
            'payload' => $payload,
        ];
        $transfer->update([
            'payin_status' => 'failed',
            'status' => 'failed',
            'timeline' => $timeline,
        ]);
        Log::warning('Payment failed', ['transfer_id' => $transfer->id, 'reason' => $reason]);
    }

    protected function handlePendingPayment(Transfer $transfer, array &$timeline): void
    {
        $timeline[] = [
            'state' => 'payin_pending',
            'at' => now()->toIso8601String(),
            'message' => 'Waiting for payment confirmation',
        ];
        $transfer->update([
            'payin_status' => 'pending',
            'status' => 'payin_pending',
            'timeline' => $timeline,
        ]);
    }

    protected function initiatePayout(Transfer $transfer, array &$timeline, SafeHaven $safeHaven): void
    {
        try {
            if (empty($transfer->recipient_account_number) || empty($transfer->recipient_bank_code)) {
                throw new \Exception('Missing recipient details for payout');
            }
            $resp = $safeHaven->payout([
                'amount_ngn_minor' => $transfer->receive_ngn_minor,
                'bank_code' => $transfer->recipient_bank_code,
                'account_number' => $transfer->recipient_account_number,
                'account_name' => $transfer->recipient_account_name,
                'narration' => 'TexaPay transfer ' . $transfer->id,
                'reference' => (string) Str::uuid(),
                'name_enquiry_reference' => $transfer->name_enquiry_reference,
                'debit_account_number' => env('SAFEHAVEN_DEBIT_ACCOUNT_NUMBER'),
            ]);

            $timeline[] = [
                'state' => 'payout_initiated',
                'at' => now()->toIso8601String(),
                'reference' => $resp['ref'] ?? null,
                'response' => $resp,
            ];
            $status = $resp['status'] ?? 'pending';
            $transfer->update([
                'payout_ref' => $resp['ref'] ?? null,
                'payout_status' => $status,
                'status' => $status === 'success' ? 'completed' : 'payout_pending',
                'timeline' => $timeline,
            ]);

            if ($status === 'success') {
                $timeline[] = [
                    'state' => 'completed',
                    'at' => now()->toIso8601String(),
                    'message' => 'Payout completed successfully',
                ];
                $transfer->update([
                    'payout_completed_at' => now(),
                    'status' => 'completed',
                    'timeline' => $timeline,
                ]);
            } elseif ($status === 'failed') {
                $reason = $resp['message'] ?? ($resp['error'] ?? 'Payout failed');
                $timeline[] = [
                    'state' => 'payout_failed',
                    'at' => now()->toIso8601String(),
                    'reason' => $reason,
                ];
                $transfer->update([
                    'status' => 'failed',
                    'timeline' => $timeline,
                ]);
                Log::error('Payout failed', ['transfer_id' => $transfer->id, 'reason' => $reason, 'response' => $resp]);
            }
        } catch (\Exception $e) {
            Log::error('Error initiating payout', ['transfer_id' => $transfer->id, 'error' => $e->getMessage()]);
            $timeline[] = [
                'state' => 'payout_error',
                'at' => now()->toIso8601String(),
                'error' => $e->getMessage(),
            ];
            $transfer->update([
                'status' => 'failed',
                'payout_status' => 'failed',
                'timeline' => $timeline,
            ]);
            throw $e;
        }
    }
}
