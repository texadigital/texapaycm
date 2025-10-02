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
            $depositId = (string) $transfer->payin_ref;
            
            // Callbacks are configured in PawaPay dashboard; do not include callbackUrl in body per docs
            $webhookBase = config('services.pawapay.webhook_base_url');
            $callbackUrl = null; // kept for reference, not sent
            
            // Prepare the refund payload per PawaPay API reference
            // API requires amount and currency; for XAF, send integer units as string.
            $amountUnits = (int) floor((float) $transfer->amount_xaf);
            if ($amountUnits <= 0 && $transfer->amount_xaf > 0) {
                $amountUnits = (int) ceil((float) $transfer->amount_xaf);
            }
            // Compose metadata as an array of objects with arbitrary keys (per API reference examples)
            $metaList = [];
            $metaList[] = ['reason' => 'Automatic refund for failed payout'];
            $metaList[] = ['transferId' => (string) $transfer->id];
            $metaList[] = ['originalPayinRef' => (string) $depositId];
            if (!empty($transfer->payout_ref)) {
                $metaList[] = ['originalPayoutRef' => (string) $transfer->payout_ref];
            }

            // Primary payload (full refund for original amount)
            $payload = [
                'refundId' => (string) $refundId,
                'depositId' => (string) $depositId,
                'amount' => (string) $amountUnits,
                'currency' => 'XAF',
                'metadata' => $metaList,
            ];

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
                // Include X-API-KEY in case endpoint prefers it
                'X-API-KEY' => $this->apiKey,
                'X-Api-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->retry(2, 500)->withOptions([
                // Ensure non-2xx responses do NOT throw exceptions so we can inspect and fall back
                'http_errors' => false,
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
            if (!is_array($responseData)) {
                // Defensive: try to decode manually or capture raw body for logging/fallback checks
                $raw = (string) $response->body();
                $decoded = json_decode($raw, true);
                $responseData = is_array($decoded) ? $decoded : ['raw' => $raw];
            }
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

            // If provider claims missing metadata, perform two-stage fallbacks
            $missingMeta = strtoupper((string)($responseData['failureReason']['failureCode'] ?? '')) === 'MISSING_PARAMETER'
                && str_contains(strtoupper((string)($responseData['failureReason']['failureMessage'] ?? '')), 'METADATA');
            if ($response->status() === 400 && $missingMeta) {
                // Attempt 2: minimal metadata as empty object
                $alt1 = [
                    'refundId' => (string) $refundId,
                    'depositId' => (string) $depositId,
                    'amount' => (string) $amountUnits,
                    'currency' => 'XAF',
                    'metadata' => [],
                ];
                Log::warning('Refund API requested metadata; retrying with empty object', ['refund_id' => $refundId]);
                $response = $http->post("{$this->baseUrl}/refunds", $alt1);
                $responseData = $response->json();
                if (!is_array($responseData)) {
                    $raw = (string) $response->body();
                    $decoded = json_decode($raw, true);
                    $responseData = is_array($decoded) ? $decoded : ['raw' => $raw];
                }
                if ($response->successful() && in_array(($responseData['status'] ?? ''), ['ACCEPTED','PENDING'], true)) {
                    $timeline[] = [
                        'state' => 'refund_initiated',
                        'at' => now()->toIso8601String(),
                        'refund_id' => $refundId,
                        'status' => $responseData['status'] ?? null,
                    ];
                    Log::info('Refund initiated successfully (empty metadata)', [
                        'transfer_id' => $transfer->id,
                        'refund_id' => $refundId,
                        'response' => $responseData
                    ]);
                    $transfer->timeline = $timeline;
                    $transfer->save();
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

                // Attempt 3: list-of-pairs metadata
                $kv = [];
                foreach ($metaList as $obj) {
                    foreach ($obj as $k => $v) {
                        $kv[] = ['key' => (string) $k, 'value' => (string) $v];
                    }
                }
                $alt2 = [
                    'refundId' => (string) $refundId,
                    'depositId' => (string) $depositId,
                    'amount' => (string) $amountUnits,
                    'currency' => 'XAF',
                    'metadata' => $kv,
                ];
                $alt2['metaData'] = $kv;
                Log::warning('Refund API still rejected; retrying with list-of-pairs metadata', ['refund_id' => $refundId]);
                $response = $http->post("{$this->baseUrl}/refunds", $alt2);
                $responseData = $response->json();
                if ($response->successful() && in_array(($responseData['status'] ?? ''), ['ACCEPTED','PENDING'], true)) {
                    $timeline[] = [
                        'state' => 'refund_initiated',
                        'at' => now()->toIso8601String(),
                        'refund_id' => $refundId,
                        'status' => $responseData['status'] ?? null,
                    ];
                    Log::info('Refund initiated successfully (fallback payload)', [
                        'transfer_id' => $transfer->id,
                        'refund_id' => $refundId,
                        'response' => $responseData
                    ]);
                    $transfer->timeline = $timeline;
                    $transfer->save();
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
