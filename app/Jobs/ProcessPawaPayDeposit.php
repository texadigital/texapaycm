<?php

namespace App\Jobs;

use App\Models\Transfer;
use App\Models\WebhookEvent;
use App\Services\SafeHaven;
use App\Services\NotificationService;
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

    public function handle(SafeHaven $safeHaven, NotificationService $notificationService): void
    {
        $event = WebhookEvent::find($this->eventId);
        if (!$event) { return; }
        if ($event->processed_at) { return; }

        $payload = $this->payload;
        $depositId = $payload['depositId'] ?? $payload['id'] ?? null;
        $status = strtoupper($payload['status'] ?? '');
        $reference = $payload['reference'] ?? null;
        if (!$depositId) { return; }

        DB::transaction(function () use ($depositId, $status, $reference, $payload, $safeHaven, $event, $notificationService) {
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
                    $this->handleCompletedPayment($transfer, $timeline, $safeHaven, $notificationService);
                    break;
                case 'FAILED':
                case 'REJECTED':
                case 'EXPIRED':
                case 'CANCELLED':
                    $this->handleFailedPayment($transfer, $timeline, $payload, $notificationService);
                    break;
                case 'PENDING':
                case 'SUBMITTED':
                    $this->handlePendingPayment($transfer, $timeline, $notificationService);
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

    protected function handleCompletedPayment(Transfer $transfer, array &$timeline, SafeHaven $safeHaven, NotificationService $notificationService): void
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

        // AML: evaluate rules on pay-in success
        try {
            $evaluator = app(\App\Services\AmlRuleEvaluator::class);
            $alerts = $evaluator->evaluateTransfer($transfer->fresh(), 'payin_success');
            if (!empty($alerts)) {
                $timeline[] = [
                    'state' => 'aml_alerts_created',
                    'at' => now()->toIso8601String(),
                    'phase' => 'payin_success',
                    'alert_ids' => $alerts,
                ];
                $transfer->update(['timeline' => $timeline]);
            }
        } catch (\Throwable $e) {
            \Log::error('AML evaluation (payin_success) failed', [
                'transfer_id' => $transfer->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Send pay-in success notification
        $notificationService->dispatchUserNotification('transfer.payin.success', $transfer->user, [
            'transfer' => $transfer->toArray()
        ]);

        $this->initiatePayout($transfer, $timeline, $safeHaven, $notificationService);
    }

    protected function handleFailedPayment(Transfer $transfer, array &$timeline, array $payload, NotificationService $notificationService): void
    {
        $failureMap = [
            'PAYER_LIMIT_REACHED' => 'Payer reached provider limit. Try a lower amount or later.',
            'PAYER_NOT_FOUND' => 'Mobile wallet not found. Please check the MSISDN.',
            'PAYMENT_NOT_APPROVED' => 'Payment was not approved by the provider.',
            'INSUFFICIENT_BALANCE' => 'Insufficient wallet balance.',
            'UNSPECIFIED_FAILURE' => 'Payment failed due to an unspecified error.',
            'EXPIRED' => 'Payment request expired.',
            'CANCELLED' => 'Payment request was cancelled.',
        ];
        $failureCode = strtoupper((string) ($payload['failureCode'] ?? ''));
        $provider = $payload['payer']['accountDetails']['provider'] ?? ($payload['provider'] ?? null);
        $msisdn = $payload['payer']['accountDetails']['phoneNumber'] ?? ($payload['msisdn'] ?? null);
        $reason = $payload['reason'] ?? $payload['message'] ?? ($failureMap[$failureCode] ?? 'Payment failed');
        $timeline[] = [
            'state' => 'payin_failed',
            'at' => now()->toIso8601String(),
            'reason' => $reason,
            'failure_code' => $failureCode ?: null,
            'provider' => $provider,
            'msisdn' => $msisdn,
            'payload' => $payload,
        ];
        $transfer->update([
            'payin_status' => 'failed',
            'status' => 'failed',
            'timeline' => $timeline,
        ]);

        // Send pay-in failed notification
        $notificationService->dispatchUserNotification('transfer.payin.failed', $transfer->user, [
            'transfer' => $transfer->toArray(),
            'reason' => $reason,
            'failure_code' => $failureCode ?: null
        ]);

        Log::warning('Payment failed', [
            'transfer_id' => $transfer->id,
            'reason' => $reason,
            'failure_code' => $failureCode ?: null,
            'provider' => $provider,
            'msisdn' => $msisdn,
        ]);
    }

    protected function handlePendingPayment(Transfer $transfer, array &$timeline, NotificationService $notificationService): void
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

    protected function initiatePayout(Transfer $transfer, array &$timeline, SafeHaven $safeHaven, NotificationService $notificationService, \App\Services\RefundService $refundService = null): void
    {
        try {
            if (empty($transfer->recipient_account_number) || empty($transfer->recipient_bank_code)) {
                throw new \Exception('Missing recipient details for payout');
            }

            // Just-in-time name enquiry to ensure a fresh reference before payout
            $enquiry = $safeHaven->nameEnquiry($transfer->recipient_bank_code, $transfer->recipient_account_number);
            if (!($enquiry['success'] ?? false)) {
                $reason = $enquiry['raw']['friendlyMessage'] ?? ($enquiry['raw']['message'] ?? 'Name enquiry failed');
                $timeline[] = [
                    'state' => 'name_enquiry_failed',
                    'at' => now()->toIso8601String(),
                    'reason' => $reason,
                    'raw' => $enquiry['raw'] ?? null,
                ];
                $transfer->update([
                    'status' => 'failed',
                    'timeline' => $timeline,
                ]);
                \Log::error('JIT Name enquiry failed before payout', [
                    'transfer_id' => $transfer->id,
                    'bank_code' => $transfer->recipient_bank_code,
                    'account_number' => $transfer->recipient_account_number,
                    'response' => $enquiry['raw'] ?? null,
                ]);
                return;
            }
            // Update transfer with fresh reference and account name if provided
            $transfer->name_enquiry_reference = $enquiry['reference'] ?? $transfer->name_enquiry_reference;
            if (!empty($enquiry['account_name'])) {
                $transfer->recipient_account_name = $enquiry['account_name'];
            }
            // Sanity check: prevent paying to our own debit account
            $debitAcct = (string) env('SAFEHAVEN_DEBIT_ACCOUNT_NUMBER');
            if (!empty($debitAcct) && $transfer->recipient_account_number === $debitAcct) {
                $timeline[] = [
                    'state' => 'payout_blocked_same_debit',
                    'at' => now()->toIso8601String(),
                    'reason' => 'Beneficiary account matches debit account; payout aborted for safety.',
                ];
                $transfer->update([
                    'status' => 'failed',
                    'payout_status' => 'failed',
                    'timeline' => $timeline,
                ]);
                \Log::error('Payout aborted: beneficiary equals debit account', [
                    'transfer_id' => $transfer->id,
                    'beneficiary' => $transfer->recipient_account_number,
                    'debit' => $debitAcct,
                ]);
                return;
            }

            // Extra safety: ensure NE response accountNumber matches recipient; otherwise, abort
            $neAcct = (string) ($enquiry['raw']['data']['accountNumber'] ?? $enquiry['raw']['accountNumber'] ?? '');
            if ($neAcct && $neAcct !== $transfer->recipient_account_number) {
                $timeline[] = [
                    'state' => 'name_enquiry_mismatch',
                    'at' => now()->toIso8601String(),
                    'reason' => 'NE account mismatch vs stored recipient; payout aborted.',
                    'ne_account' => $neAcct,
                    'recipient_account' => $transfer->recipient_account_number,
                ];
                $transfer->update([
                    'status' => 'failed',
                    'payout_status' => 'failed',
                    'timeline' => $timeline,
                ]);
                \Log::error('NE account mismatch; aborting payout', [
                    'transfer_id' => $transfer->id,
                    'ne_account' => $neAcct,
                    'recipient_account' => $transfer->recipient_account_number,
                ]);
                return;
            }
            $transfer->save();
            // Build a safe, compact narration including sender name and reference
            $idempotencyKey = (string) Str::uuid();
            $sender = trim((string) ($transfer->user->name ?? $transfer->user->email ?? 'TEXA'));
            $sender = str_replace(["|", "\n", "\r"], ' ', $sender);
            if (strlen($sender) > 32) { $sender = substr($sender, 0, 32); }
            $baseNarr = 'Internal Fund Transfer|TexaPay transfer ' . $transfer->id . '|' . $idempotencyKey . '|' . $sender;
            // Cap overall narration length to ~120 chars to avoid provider truncation
            $narration = strlen($baseNarr) > 120 ? substr($baseNarr, 0, 120) : $baseNarr;

            $resp = $safeHaven->payout([
                'amount_ngn_minor' => $transfer->receive_ngn_minor,
                'bank_code' => $transfer->recipient_bank_code,
                'account_number' => $transfer->recipient_account_number,
                'account_name' => $transfer->recipient_account_name,
                'narration' => $narration,
                'reference' => $idempotencyKey,
                'name_enquiry_reference' => $transfer->name_enquiry_reference,
                'debit_account_number' => env('SAFEHAVEN_DEBIT_ACCOUNT_NUMBER'),
            ]);

            $timeline[] = [
                'state' => 'payout_initiated',
                'at' => now()->toIso8601String(),
                'reference' => $resp['ref'] ?? null,
                'narration' => $narration,
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
                    
                ];
                $transfer->update([
                    'payout_completed_at' => now(),
                    'status' => 'completed',
                    'timeline' => $timeline,
                ]);

                // AML: evaluate rules on payout success
                try {
                    $evaluator = app(\App\Services\AmlRuleEvaluator::class);
                    $alerts = $evaluator->evaluateTransfer($transfer->fresh(), 'payout_success');
                    if (!empty($alerts)) {
                        $timeline[] = [
                            'state' => 'aml_alerts_created',
                            'at' => now()->toIso8601String(),
                            'phase' => 'payout_success',
                            'alert_ids' => $alerts,
                        ];
                        $transfer->update(['timeline' => $timeline]);
                    }
                } catch (\Throwable $e) {
                    \Log::error('AML evaluation (payout_success) failed', [
                        'transfer_id' => $transfer->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                // Send payout success notification
                $notificationService->dispatchUserNotification('transfer.payout.success', $transfer->user, [
                    'transfer' => $transfer->toArray()
                ]);

                // Update transaction limits idempotently so dashboard usage stays in sync
                try {
                    $limitCheck = app(\App\Services\LimitCheckService::class);
                    $limitCheck->recordCompletedTransferOnce($transfer->fresh());
                } catch (\Throwable $e) {
                    \Log::error('Failed to record completed transfer in limits system', [
                        'transfer_id' => $transfer->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } elseif ($status === 'failed') {
                $respCode = $resp['raw']['responseCode'] ?? null;
                $httpStatus = $resp['raw']['statusCode'] ?? ($resp['raw']['status'] ?? null);
                // If provider reports failure via code/status, do not display a success-sounding message
                $fallback = 'Payout failed';
                if (!empty($respCode) && $respCode !== '00') {
                    $fallback .= ' (code ' . $respCode . ')';
                } elseif (!empty($httpStatus) && (int) $httpStatus >= 400) {
                    $fallback .= ' (http ' . $httpStatus . ')';
                }
                $reason = $resp['error']
                    ?? ($respCode !== '00' && !empty($respCode) ? $fallback : null)
                    ?? ($httpStatus && (int)$httpStatus >= 400 ? $fallback : null)
                    ?? ($resp['message'] ?? ($resp['raw']['message'] ?? $fallback));
                $timeline[] = [
                    'state' => 'payout_failed',
                    'at' => now()->toIso8601String(),
                    'reason' => $reason,
                    'response_code' => $respCode,
                    'http_status' => $httpStatus,
                ];
                $transfer->update([
                    'status' => 'failed',
                    'timeline' => $timeline,
                ]);

                // Send payout failed notification
                $notificationService->dispatchUserNotification('transfer.payout.failed', $transfer->user, [
                    'transfer' => $transfer->toArray(),
                    'reason' => $reason
                ]);

                Log::error('Payout failed', ['transfer_id' => $transfer->id, 'reason' => $reason, 'response' => $resp]);

                // If payin succeeded but payout failed, initiate refund
                if ($transfer->payin_status === 'success') {
                    $refundService = $refundService ?? app(\App\Services\RefundService::class);
                    try {
                        $result = $refundService->refundFailedPayout($transfer);
                        Log::info('Refund initiation result after payout failure', [
                            'transfer_id' => $transfer->id,
                            'result' => $result,
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('Refund initiation threw exception', [
                            'transfer_id' => $transfer->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
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
            // Do not rethrow here; webhook controller should still return 200.
            return;
        }
    }
}
