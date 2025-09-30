<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PawaPay
{
    protected string $baseUrl;
    protected ?string $apiKey;
    protected ?array $lastAuthError = null;
    protected string $apiVersion;
    protected ?string $caBundle = null;
    /** @var array<string,string> */
    protected array $failureMessages = [
        'PAYER_LIMIT_REACHED' => 'Payer reached provider limit. Try a lower amount or later.',
        'PAYER_NOT_FOUND' => 'Mobile wallet not found. Please check the MSISDN.',
        'PAYMENT_NOT_APPROVED' => 'Payment was not approved by the provider.',
        'INSUFFICIENT_BALANCE' => 'Insufficient wallet balance.',
        'UNSPECIFIED_FAILURE' => 'Payment failed due to an unspecified error.',
        'EXPIRED' => 'Payment request expired.',
        'CANCELLED' => 'Payment request was cancelled.',
    ];

    public function __construct()
    {
        $this->baseUrl = rtrim((string) env('PAWAPAY_BASE_URL', ''), '/');
        $this->apiKey = env('PAWAPAY_API_KEY');
        $this->apiVersion = trim((string) env('PAWAPAY_API_VERSION', 'v2'));
        // Prefer PawaPay-specific CA bundle, fallback to SAFEHAVEN_CA_BUNDLE
        $bundle = env('PAWAPAY_CA_BUNDLE') ?: env('SAFEHAVEN_CA_BUNDLE');
        if ($bundle) {
            // Resolve relative paths like "storage/certs/cacert.pem"
            $resolved = $bundle;
            if (!is_file($resolved)) {
                $maybe = base_path($bundle);
                if (is_file($maybe)) {
                    $resolved = $maybe;
                }
            }
            if (is_file($resolved)) {
                $this->caBundle = $resolved;
            }
        }
    }

    /**
     * Normalize PawaPay statuses to internal statuses.
     */
    protected function normalizeStatus(string $statusRaw, int $http = 0, bool $ok = false): string
    {
        $statusRaw = strtoupper(trim($statusRaw));
        if (in_array($statusRaw, ['COMPLETED'], true)) { return 'success'; }
        if (in_array($statusRaw, ['FAILED','REJECTED','CANCELLED','EXPIRED'], true)) { return 'failed'; }
        if (in_array($statusRaw, ['ACCEPTED','SUBMITTED','PENDING','DUPLICATE_IGNORED'], true)) { return 'pending'; }
        // Fallback from HTTP
        if ($ok || in_array($http, [200,201,202], true)) { return 'pending'; }
        return 'failed';
    }

    /**
     * Map failureCode to friendly message.
     */
    protected function failureMessage(string $code): string
    {
        $code = strtoupper(trim($code));
        return $this->failureMessages[$code] ?? ('Payment failed: ' . ($code ?: 'UNKNOWN'));
    }

    protected function client(string $mode = 'both')
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];
        if (!empty($this->apiKey)) {
            if ($mode === 'auth') {
                $headers['Authorization'] = 'Bearer ' . $this->apiKey;
            } elseif ($mode === 'xkey') {
                $headers['X-API-KEY'] = $this->apiKey;
                $headers['X-Api-Key'] = $this->apiKey;
            } else { // both
                $headers['Authorization'] = 'Bearer ' . $this->apiKey;
                $headers['X-API-KEY'] = $this->apiKey;
                $headers['X-Api-Key'] = $this->apiKey;
            }
        }
        $http = Http::acceptJson()->withHeaders($headers);
        // If we have a CA bundle, instruct Guzzle to use it for TLS verification
        if ($this->caBundle) {
            $http = $http->withOptions([
                'verify' => $this->caBundle,
                'timeout' => 20,
                'connect_timeout' => 10,
            ]);
        }
        return $http;
    }

    protected function path(string $suffix): string
    {
        $prefix = '/' . trim($this->apiVersion ?: 'v2', '/');
        $suffix = '/' . ltrim($suffix, '/');
        return $this->baseUrl . $prefix . $suffix;
    }

    /**
     * Health check: verifies API key by probing a protected endpoint.
     * We call a non-existent deposit to force an authenticated response.
     */
    public function checkAuth(): array
    {
        if (!$this->baseUrl || !$this->apiKey) {
            return [
                'ok' => false,
                'base_url' => $this->baseUrl,
                'api_key_present' => (bool) $this->apiKey,
                'status' => 'missing_config',
            ];
        }
        try {
            $probeId = 'health-' . Str::uuid();
            $url = $this->baseUrl . '/deposits/' . $probeId;

            // Try Authorization only
            $respAuth = $this->client('auth')->get($url);
            if ($respAuth->status() !== 401 && $respAuth->status() !== 403) {
                return [
                    'ok' => true,
                    'status' => 'authorized',
                    'mode' => 'Authorization',
                    'http_status' => $respAuth->status(),
                ];
            }

            // Try X-API-KEY only
            $respX = $this->client('xkey')->get($url);
            if ($respX->status() !== 401 && $respX->status() !== 403) {
                return [
                    'ok' => true,
                    'status' => 'authorized',
                    'mode' => 'X-API-KEY',
                    'http_status' => $respX->status(),
                ];
            }

            // Try both headers
            $respBoth = $this->client('both')->get($url);
            if ($respBoth->status() !== 401 && $respBoth->status() !== 403) {
                return [
                    'ok' => true,
                    'status' => 'authorized',
                    'mode' => 'both',
                    'http_status' => $respBoth->status(),
                ];
            }

            return [
                'ok' => false,
                'status' => 'unauthorized',
                'http_status' => $respBoth->status(),
                'body' => $respBoth->json() ?? $respBoth->body(),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 'exception',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Initiate a MoMo deposit (pay-in).
     * Input: [amount_xaf_minor, msisdn, currency='XAF', reference, callback_url, provider]
     * Return: structured array with 'ref' (depositId), 'status' (pending/success/failed/canceled), 'raw'
     */
    public function initiatePayIn(array $payload): array
    {
        if (!$this->baseUrl || !$this->apiKey) {
            return [
                'ref' => null,
                'status' => 'failed',
                'raw' => ['error' => 'Missing PAWAPAY_BASE_URL or PAWAPAY_API_KEY'],
            ];
        }

        try {
            $depositId = $payload['reference'] ?? (string) Str::uuid();
            $provider = $payload['provider'] ?? null; // e.g., MTN_MOMO_CMR
            $msisdn = $payload['msisdn'] ?? null;
            // Our app computes XAF amount in minor units (×100). PawaPay expects currency units for XAF.
            // Convert minor->units by dividing by 100 and sending an integer string.
            $minor = (int) ($payload['amount_xaf_minor'] ?? 0);
            $amountUnits = (int) floor($minor / 100);
            if ($amountUnits <= 0 && $minor > 0) {
                // Guard for very small non-zero values due to rounding; ensure at least 1 unit if minor>0
                $amountUnits = (int) ceil($minor / 100);
            }
            $amountString = (string) $amountUnits;

            // Docs show deposits payload
            $body = [
                'depositId' => $depositId,
                'amount' => $amountString,
                'currency' => $payload['currency'] ?? 'XAF',
                'payer' => [
                    'type' => 'MMO',
                    'accountDetails' => [
                        'phoneNumber' => $msisdn,
                        'provider' => $provider,
                    ],
                ],
            ];
            if (!empty($payload['client_ref'])) {
                $body['clientReferenceId'] = (string) $payload['client_ref'];
            }
            if (!empty($payload['customer_message'])) {
                $msg = trim((string) $payload['customer_message']);
                if (strlen($msg) < 4) { $msg = str_pad($msg, 4, ' '); }
                if (strlen($msg) > 22) { $msg = substr($msg, 0, 22); }
                $body['customerMessage'] = $msg;
            }
            // Note: v2 API does NOT accept per-request callbackUrl; callbacks are configured in Dashboard.

            $resp = $this->client()->post($this->path('/deposits'), $body);

            $json = $resp->json();
            $statusRaw = strtoupper((string)($json['status'] ?? '')); // ACCEPTED/REJECTED/SUBMITTED
            $http = $resp->status();
            $ok = $resp->successful();
            $status = $this->normalizeStatus($statusRaw, $http, $ok);
            $raw = $json ?? [];
            if (!$json) {
                $raw = ['body' => $resp->body()];
            }
            $raw['http_status'] = $http;
            if (!isset($raw['message']) && isset($raw['errorMessage'])) {
                $raw['message'] = $raw['errorMessage'];
            }
            if (!isset($raw['message']) && isset($raw['errors']) && is_array($raw['errors'])) {
                $raw['message'] = implode('; ', array_map(function($e){ return is_array($e) ? ($e['message'] ?? json_encode($e)) : (string)$e; }, $raw['errors']));
            }
            if (isset($raw['failureCode']) && !isset($raw['message'])) {
                $raw['message'] = $this->failureMessage((string) $raw['failureCode']);
            }
            return [
                'ref' => $json['depositId'] ?? $depositId,
                'status' => $status,
                'raw' => $raw,
            ];
        } catch (\Throwable $e) {
            return [
                'ref' => null,
                'status' => 'failed',
                'raw' => ['exception' => $e->getMessage()],
            ];
        }
    }

    /**
     * Get pay-in status by depositId.
     * Return: ['status' => 'pending|success|failed|canceled', 'raw' => array]
     */
    public function getPayInStatus(string $reference): array
    {
        if (!$this->baseUrl || !$this->apiKey) {
            return [
                'status' => 'failed',
                'raw' => ['error' => 'Missing PAWAPAY_BASE_URL or PAWAPAY_API_KEY'],
            ];
        }

        try {
            // Docs mention checking deposit status
            $resp = $this->client()->get($this->path('/deposits/' . urlencode($reference)));
            $json = $resp->json();
            $statusRaw = strtoupper((string)($json['status'] ?? ''));
            $status = $this->normalizeStatus($statusRaw, $resp->status(), $resp->successful());
            if (isset($json['failureCode']) && !isset($json['message'])) {
                $json['message'] = $this->failureMessage((string) $json['failureCode']);
            }
            return [
                'status' => $status,
                'raw' => $json,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'raw' => ['exception' => $e->getMessage()],
            ];
        }
    }

    /**
     * Predict provider for MSISDN (stub to be implemented based on docs endpoint).
     */
    public function predictProvider(string $msisdn): array
    {
        if (!$this->baseUrl || !$this->apiKey) {
            return ['ok' => false, 'raw' => ['error' => 'Missing PAWAPAY_BASE_URL or PAWAPAY_API_KEY']];
        }
        try {
            $resp = $this->client()->post($this->path('/toolkit/predict-provider'), [
                'phoneNumber' => $msisdn,
            ]);
            return ['ok' => $resp->successful(), 'raw' => $resp->json() ?? $resp->body()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'raw' => ['exception' => $e->getMessage()]];
        }
    }

    /**
     * Fetch active configuration (providers, currencies, limits) for this merchant.
     */
    public function activeConfiguration(): array
    {
        if (!$this->baseUrl || !$this->apiKey) {
            return ['ok' => false, 'raw' => ['error' => 'Missing PAWAPAY_BASE_URL or PAWAPAY_API_KEY']];
        }
        try {
            $resp = $this->client()->get($this->path('/toolkit/active-configuration'));
            return ['ok' => $resp->successful(), 'raw' => $resp->json() ?? $resp->body()];
        } catch (\Throwable $e) {
            return ['ok' => false, 'raw' => ['exception' => $e->getMessage()]];
        }
    }
}
