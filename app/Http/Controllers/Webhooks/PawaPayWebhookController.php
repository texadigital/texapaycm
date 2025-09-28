<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Transfer;
use App\Services\SafeHaven;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PawaPayWebhookController extends Controller
{
    public function __invoke(Request $request, SafeHaven $safeHaven)
    {
        $payload = $request->json()->all();
        $signature = $request->header('X-Signature'); // TODO: verify signature if supported
        Log::info('PawaPay webhook received', ['payload' => $payload]);

        $depositId = $payload['depositId'] ?? $payload['id'] ?? null;
        if (!$depositId) {
            Log::warning('PawaPay webhook: Missing deposit ID', ['payload' => $payload]);
            return response()->json(['ok' => false, 'error' => 'Missing deposit ID'], 400);
        }

        // Idempotency: record the webhook event and short-circuit if already processed
        $event = \App\Models\WebhookEvent::firstOrCreate(
            ['provider' => 'pawapay', 'type' => 'deposits', 'event_id' => (string) $depositId],
            ['payload' => $payload, 'signature_hash' => $signature ? sha1($signature) : null]
        );
        if ($event->processed_at) {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }

        // Dispatch async job to process deposit webhook and return fast
        dispatch(new \App\Jobs\ProcessPawaPayDeposit($event->id, $payload));
        return response()->json(['ok' => true]);
    }

    protected function handleCompletedPayment($transfer, &$timeline, $safeHaven)
    {
        // Only process if not already completed
        if (in_array($transfer->status, ['payout_pending', 'completed', 'payout_success'])) {
            Log::info('Payment already processed', [
                'transfer_id' => $transfer->id,
                'current_status' => $transfer->status
            ]);
            return;
        }

        // Update transfer status
        $timeline[] = [
            'state' => 'payin_completed',
            'at' => now()->toIso8601String(),
            'amount' => $transfer->amount_xaf
        ];

        $transfer->update([
            'payin_status' => 'success',
            'status' => 'payout_pending',
            'payin_at' => now(),
            'timeline' => $timeline
        ]);

        // Initiate payout to recipient
        $this->initiatePayout($transfer, $timeline, $safeHaven);
    }

    protected function handleFailedPayment($transfer, &$timeline, $payload)
    {
        $reason = $payload['reason'] ?? $payload['message'] ?? 'Payment failed';
        
        $timeline[] = [
            'state' => 'payin_failed',
            'at' => now()->toIso8601String(),
            'reason' => $reason,
            'payload' => $payload
        ];

        $transfer->update([
            'payin_status' => 'failed',
            'status' => 'failed',
            'timeline' => $timeline
        ]);

        Log::warning('Payment failed', [
            'transfer_id' => $transfer->id,
            'reason' => $reason
        ]);
    }

    protected function handlePendingPayment($transfer, &$timeline)
    {
        $timeline[] = [
            'state' => 'payin_pending',
            'at' => now()->toIso8601String(),
            'message' => 'Waiting for payment confirmation'
        ];

        $transfer->update([
            'payin_status' => 'pending',
            'status' => 'payin_pending',
            'timeline' => $timeline
        ]);
    }

    protected function initiatePayout($transfer, &$timeline, $safeHaven)
    {
        try {
            // Ensure we have required recipient details
            if (empty($transfer->recipient_account_number) || empty($transfer->recipient_bank_code)) {
                throw new \Exception('Missing recipient details for payout');
            }

            // Initiate payout to recipient bank account
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
                'response' => $resp
            ];

            $status = $resp['status'] ?? 'pending';
            $transfer->update([
                'payout_ref' => $resp['ref'] ?? null,
                'payout_status' => $status,
                'status' => $status === 'success' ? 'completed' : 'payout_pending',
                'timeline' => $timeline
            ]);

            if ($status === 'success') {
                $timeline[] = [
                    'state' => 'completed',
                    'at' => now()->toIso8601String(),
                    'message' => 'Payout completed successfully'
                ];
                $transfer->update([
                    'payout_completed_at' => now(),
                    'status' => 'completed',
                    'timeline' => $timeline
                ]);
            } elseif ($status === 'failed') {
                $reason = $resp['message'] ?? ($resp['error'] ?? 'Payout failed');
                $timeline[] = [
                    'state' => 'payout_failed',
                    'at' => now()->toIso8601String(),
                    'reason' => $reason
                ];
                $transfer->update([
                    'status' => 'failed',
                    'timeline' => $timeline
                ]);
                
                Log::error('Payout failed', [
                    'transfer_id' => $transfer->id,
                    'reason' => $reason,
                    'response' => $resp
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error initiating payout', [
                'transfer_id' => $transfer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $timeline[] = [
                'state' => 'payout_error',
                'at' => now()->toIso8601String(),
                'error' => $e->getMessage()
            ];
            
            $transfer->update([
                'status' => 'failed',
                'payout_status' => 'failed',
                'timeline' => $timeline
            ]);
            
            throw $e; // Re-throw to trigger rollback
        }
    }
}
