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

    public function __construct()
    {
        // Get the base URL from config (defaults to sandbox)
        $this->baseUrl = rtrim(config('services.pawapay.base_url'), '/') . '/v2';
        $this->apiKey = config('services.pawapay.api_key');
        $this->isSandbox = config('services.pawapay.sandbox', true);
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
                'amount' => $transfer->amount,
                'currency' => $transfer->currency
            ]);

            // Get the deposit ID from the transfer
            $depositId = $transfer->payout_id; // This should be the original payin reference
            
            // Build the webhook URL using the base URL from config
            $webhookBase = config('services.pawapay.webhook_base_url');
            $callbackUrl = "{$webhookBase}/api/v1/webhooks/pawapay/refunds";
            
            // Prepare the refund payload
            $payload = [
                'refundId' => $refundId,
                'depositId' => $depositId,
                'amount' => number_format($transfer->amount_xaf, 2, '.', ''),
                'currency' => 'XAF',
                'callbackUrl' => $callbackUrl,
                'metadata' => [
                    'reason' => 'Automatic refund for failed payout',
                    'transfer_id' => $transfer->id,
                    'original_payout_id' => $transfer->payout_id,
                    'original_payin_id' => $depositId
                ]
            ];

            // Make the API request to initiate refund
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post("{$this->baseUrl}/refunds", $payload);

            $responseData = $response->json();
            
            // Update transfer with refund information
            $transfer->update([
                'refund_id' => $refundId,
                'refund_status' => $responseData['status'] ?? 'failed',
                'refund_attempted_at' => now(),
                'refund_response' => $responseData
            ]);

            if ($response->successful() && ($responseData['status'] ?? '') === 'ACCEPTED') {
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
            $errorMessage = $responseData['message'] ?? 'Unknown error';
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
