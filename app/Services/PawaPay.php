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

    public function __construct()
    {
        $this->baseUrl = rtrim((string) env('PAWAPAY_BASE_URL', ''), '/');
        $this->apiKey = env('PAWAPAY_API_KEY');
        $this->apiVersion = trim((string) env('PAWAPAY_API_VERSION', 'v2'));
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
        return Http::acceptJson()->withHeaders($headers);
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
            $amountDecimal = number_format(($payload['amount_xaf_minor'] ?? 0) / 1, 0, '.', '');

            // Docs show deposits payload
            $body = [
                'depositId' => $depositId,
                'amount' => (string) $amountDecimal,
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
            $statusRaw = $json['status'] ?? null; // ACCEPTED/REJECTED
            $http = $resp->status();
            $ok = $resp->successful();
            $status = 'failed';
            if (($ok && in_array($statusRaw, ['ACCEPTED', 'DUPLICATE_IGNORED'], true)) || in_array($http, [200, 201, 202], true)) {
                $status = 'pending';
            }
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
            $statusRaw = $json['status'] ?? null; // COMPLETED/REJECTED/PENDING
            $status = 'pending';
            if ($statusRaw === 'COMPLETED') {
                $status = 'success';
            } elseif ($statusRaw === 'REJECTED') {
                $status = 'failed';
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
