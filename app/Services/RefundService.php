<?php

namespace App\Services;

use App\Models\Transfer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RefundService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected bool $isSandbox;
    protected ?string $caBundle = null;

    public function __construct()
    {
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
            $depositId = $transfer->payin_ref; // original pay-in reference
            
            // Build the webhook URL using the base URL from config
            $webhookBase = config('services.pawapay.webhook_base_url');
            $callbackUrl = "{$webhookBase}/api/v1/webhooks/pawapay/refunds";
            
            // Prepare the refund payload per pawaPay docs
            // For full refund: send only refundId + depositId (no amount/currency)
            $payload = [
                'refundId' => $refundId,
                'depositId' => $depositId,
                'callbackUrl' => $callbackUrl,
                'metadata' => [
                    'reason' => 'Automatic refund for failed payout',
                    'transfer_id' => $transfer->id,
                    'original_payout_ref' => $transfer->payout_ref,
                    'original_payin_ref' => $depositId
                ]
            ];
            // If business rules require partial refund, include amount and currency.
            // Note: PawaPay expects amounts in UNITS (integer for XAF), formatted as string.
            $partialMinor = null; // keep null for full refund
            if ($partialMinor !== null) {
                $payload['amount'] = (string) (int) $partialMinor; // XAF units
                $payload['currency'] = 'XAF';
            }

            // Make the API request to initiate refund
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
            $response = $http->post("{$this->baseUrl}/refunds", $payload);

            $responseData = $response->json();
            
            // Update transfer with refund information
            $transfer->update([
                'refund_id' => $refundId,
                'refund_status' => $responseData['status'] ?? 'failed',
                'refund_attempted_at' => now(),
                'refund_response' => $responseData
            ]);

            if ($response->successful() && in_array(($responseData['status'] ?? ''), ['ACCEPTED','PENDING'], true)) {
                Log::info('Refund initiated successfully', [
                    'transfer_id' => $transfer->id,
                    'refund_id' => $refundId,
                    'response' => $responseData
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Refund initiated successfully',
                    'refund_id' => $refundId,
                    'data' => $responseData
                ];
            }

            // Handle API errors
            $errorMessage = $responseData['message'] ?? ($responseData['failureReason']['failureMessage'] ?? 'Unknown error');
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
