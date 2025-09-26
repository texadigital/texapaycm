<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenExchangeRates
{
    protected string $baseUrl;
    protected ?string $appId;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) env('OXR_BASE_URL', 'https://openexchangerates.org/api'), '/');
        $this->appId = env('OXR_APP_ID');
    }

    /**
     * Fetch USD base rates for XAF and NGN.
     * Returns: [ 'usd_to_xaf' => float|null, 'usd_to_ngn' => float|null, 'fetched_at' => now(), 'raw' => array ]
     */
    public function fetchUsdRates(): array
    {
        if (!$this->appId) {
            return [
                'usd_to_xaf' => null,
                'usd_to_ngn' => null,
                'fetched_at' => now(),
                'raw' => ['error' => 'Missing OXR_APP_ID'],
            ];
        }

        try {
            $resp = Http::acceptJson()
                ->get($this->baseUrl . '/latest.json', [
                    'app_id' => $this->appId,
                    'base' => 'USD',
                ]);

            if (!$resp->ok()) {
                return [
                    'usd_to_xaf' => null,
                    'usd_to_ngn' => null,
                    'fetched_at' => now(),
                    'raw' => ['status' => $resp->status(), 'body' => $resp->json()],
                ];
            }

            $json = $resp->json();
            $rates = $json['rates'] ?? [];

            return [
                'usd_to_xaf' => isset($rates['XAF']) ? (float) $rates['XAF'] : null,
                'usd_to_ngn' => isset($rates['NGN']) ? (float) $rates['NGN'] : null,
                'fetched_at' => now(),
                'raw' => $json,
            ];
        } catch (\Throwable $e) {
            return [
                'usd_to_xaf' => null,
                'usd_to_ngn' => null,
                'fetched_at' => now(),
                'raw' => ['exception' => $e->getMessage()],
            ];
        }
    }
}
