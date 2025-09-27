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
        Log::info('PawaPay webhook received', ['payload' => $payload]);

        $depositId = $payload['depositId'] ?? $payload['id'] ?? null;
        $status = strtoupper($payload['status'] ?? '');
        $reference = $payload['reference'] ?? null;

        if (!$depositId) {
            Log::warning('PawaPay webhook: Missing deposit ID', ['payload' => $payload]);
            return response()->json(['ok' => false, 'error' => 'Missing deposit ID'], 400);
        }

        // Find the transfer with a lock to prevent race conditions
        $transfer = Transfer::where('payin_ref', $depositId)->lockForUpdate()->first();
        
        if (!$transfer) {
            Log::warning('PawaPay webhook: Transfer not found', ['depositId' => $depositId]);
            return response()->json(['ok' => false, 'error' => 'Transfer not found'], 404);
        }

        // Log the webhook receipt
        $timeline = is_array($transfer->timeline) ? $transfer->timeline : [];
        $timeline[] = [
            'state' => 'payin_webhook_received',
            'status' => $status,
            'at' => now()->toIso8601String(),
            'reference' => $reference
        ];

        try {
            DB::beginTransaction();

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
                    Log::warning('PawaPay webhook: Unknown status', [
                        'status' => $status,
                        'transfer_id' => $transfer->id,
                        'reference' => $reference
                    ]);
                    $timeline[] = [
                        'state' => 'payin_unknown_status',
                        'at' => now()->toIso8601String(),
                        'status' => $status,
                        'payload' => $payload
                    ];
                    $transfer->update(['timeline' => $timeline]);
            }

            DB::commit();
            return response()->json(['ok' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing PawaPay webhook', [
                'transfer_id' => $transfer->id ?? null,
                'deposit_id' => $depositId,
                'status' => $status,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'ok' => false,
                'error' => 'Error processing webhook: ' . $e->getMessage()
            ], 500);
        }
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
            'payin_status' => 'completed',
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
