<?php

namespace App\Services;

use App\Models\Transfer;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RefundService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected bool $isSandbox;
    protected ?string $caBundle = null;
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        // Get the base URL from config (defaults to sandbox)
        $this->baseUrl = rtrim(config('services.pawapay.base_url'), '/') . '/v2';
        $this->apiKey = config('services.pawapay.api_key');
        $this->isSandbox = config('services.pawapay.sandbox', true);
        // Prefer PawaPay-specific CA bundle env, fallback to SAFEHAVEN_CA_BUNDLE
        $bundle = env('PAWAPAY_CA_BUNDLE') ?: env('SAFEHAVEN_CA_BUNDLE');
        if ($bundle) {
            $resolved = $bundle;
            if (!is_file($resolved)) {
                $maybe = base_path($bundle);
                if (is_file($maybe)) { $resolved = $maybe; }
            }
            if (is_file($resolved)) { $this->caBundle = $resolved; }
        }
    }

    /**
     * Initiate a refund for a failed payout
     */
    public function refundFailedPayout(Transfer $transfer): array
    {
        try {
            // Check if refund is already attempted
            if ($transfer->refund_attempted_at) {
                Log::warning('Refund already attempted for transfer', [
                    'transfer_id' => $transfer->id,
                    'refund_attempted_at' => $transfer->refund_attempted_at
                ]);
                return [
                    'success' => false,
                    'message' => 'Refund already attempted',
                    'refund_id' => $transfer->refund_id
                ];
            }

            // Generate a unique refund ID
            $refundId = (string) \Illuminate\Support\Str::uuid();
            
            // Log the refund attempt
            Log::info('Initiating refund for failed payout', [
                'transfer_id' => $transfer->id,
                'refund_id' => $refundId,
                'amount_xaf' => $transfer->amount_xaf,
                'total_pay_xaf' => $transfer->total_pay_xaf,
                'currency' => 'XAF'
            ]);

            // Get the original deposit (pay-in) reference from the transfer
            $depositId = (string) $transfer->payin_ref; // original pay-in reference
            
            // Build the webhook URL using the base URL from config
            $webhookBase = config('services.pawapay.webhook_base_url');
            $callbackUrl = "{$webhookBase}/api/v1/webhooks/pawapay/refunds";
            
            // Prepare the refund payload per pawaPay docs
            // For full refund: send only refundId + depositId (no amount/currency)
            // Compose metadata as non-empty strings per provider requirements
            $metadata = [
                'reason' => 'Automatic refund for failed payout',
                'transfer_id' => (string) $transfer->id,
                'original_payout_ref' => (string) ($transfer->payout_ref ?? ''),
                'original_payin_ref' => (string) $depositId,
            ];
            // Payload to provider
            $payload = [
                'refundId' => (string) $refundId,
                'depositId' => (string) $depositId,
                'callbackUrl' => (string) $callbackUrl,
                'metadata' => $metadata,
            ];
            // If business rules require partial refund, include amount and currency.
            // Note: PawaPay expects amounts in UNITS (integer for XAF), formatted as string.
            $partialMinor = null; // keep null for full refund
            if ($partialMinor !== null) {
                $payload['amount'] = (string) (int) $partialMinor; // XAF units
                $payload['currency'] = 'XAF';
            }

            // Validate API key early
            if (!$this->apiKey) {
                $timeline = is_array($transfer->timeline) ? $transfer->timeline : [];
                $timeline[] = [
                    'state' => 'refund_rejected',
                    'at' => now()->toIso8601String(),
                    'refund_id' => $refundId,
                    'reason' => 'Missing PAWAPAY_API_KEY configuration',
                ];
                $transfer->update([
                    'refund_id' => $refundId,
                    'refund_status' => 'FAILED',
                    'refund_attempted_at' => now(),
                    'timeline' => $timeline,
                ]);
                return [
                    'success' => false,
                    'message' => 'Refund not attempted: missing API key',
                    'refund_id' => $refundId,
                ];
            }

            // Make the API request to initiate refund (log outgoing payload for diagnostics)
            $http = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]);
            if ($this->caBundle) {
                $http = $http->withOptions([
                    'verify' => $this->caBundle,
                    'timeout' => 20,
                    'connect_timeout' => 10,
                ]);
            }
            Log::info('Refund request payload', [
                'endpoint' => "{$this->baseUrl}/refunds",
                'payload' => $payload,
            ]);
            $response = $http->post("{$this->baseUrl}/refunds", $payload);

            $responseData = $response->json();
            Log::info('Refund response received', [
                'status' => $response->status(),
                'body' => $responseData,
            ]);

            // Update transfer core refund fields
            $transfer->refund_id = $refundId;
            $transfer->refund_status = $responseData['status'] ?? 'failed';
            $transfer->refund_attempted_at = now();
            $transfer->refund_response = $responseData;

            // Append timeline event for user-facing visibility
            $timeline = is_array($transfer->timeline) ? $transfer->timeline : [];
            if ($response->successful() && in_array(($responseData['status'] ?? ''), ['ACCEPTED','PENDING'], true)) {
                $timeline[] = [
                    'state' => 'refund_initiated',
                    'at' => now()->toIso8601String(),
                    'refund_id' => $refundId,
                    'status' => $responseData['status'] ?? null,
                ];
                Log::info('Refund initiated successfully', [
                    'transfer_id' => $transfer->id,
                    'refund_id' => $refundId,
                    'response' => $responseData
                ]);
                $transfer->timeline = $timeline;
                $transfer->save();

                // Send refund initiated notification
                $this->notificationService->dispatchUserNotification('transfer.refund.initiated', $transfer->user, [
                    'transfer' => $transfer->toArray(),
                    'refund_id' => $refundId
                ]);

                return [
                    'success' => true,
                    'message' => 'Refund initiated successfully',
                    'refund_id' => $refundId,
                    'data' => $responseData
                ];
            }

            // Handle API errors and append failure event
            $errorMessage = $responseData['message'] ?? ($responseData['failureReason']['failureMessage'] ?? 'Unknown error');
            $timeline[] = [
                'state' => 'refund_rejected',
                'at' => now()->toIso8601String(),
                'refund_id' => $refundId,
                'reason' => $errorMessage,
                'status_code' => $response->status(),
                'provider_status' => $responseData['status'] ?? null,
            ];
            $transfer->timeline = $timeline;
            $transfer->save();

            Log::error('Failed to initiate refund', [
                'transfer_id' => $transfer->id,
                'status' => $response->status(),
                'response' => $responseData
            ]);

            return [
                'success' => false,
                'message' => 'Failed to initiate refund: ' . $errorMessage,
                'refund_id' => $refundId,
                'error' => $responseData
            ];

        } catch (\Exception $e) {
            // Append error to timeline so users can see progress/status
            $timeline = is_array($transfer->timeline) ? $transfer->timeline : [];
            $timeline[] = [
                'state' => 'refund_initiation_error',
                'at' => now()->toIso8601String(),
                'error' => $e->getMessage(),
            ];
            $transfer->update(['timeline' => $timeline]);

            Log::error('Exception while processing refund', [
                'transfer_id' => $transfer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check the status of a refund
     */
    public function checkRefundStatus(string $refundId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json'
            ])->get("{$this->baseUrl}/refunds/{$refundId}");

            $responseData = $response->json();

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => $responseData['status'] ?? 'unknown',
                    'data' => $responseData
                ];
            }

            return [
                'success' => false,
                'message' => $responseData['message'] ?? 'Failed to check refund status',
                'status' => $responseData['status'] ?? 'error',
                'error' => $responseData
            ];

        } catch (\Exception $e) {
            Log::error('Failed to check refund status', [
                'refund_id' => $refundId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'status' => 'error'
            ];
        }
    }
}
