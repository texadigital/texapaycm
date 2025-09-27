<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

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
        $fallback = env('FALLBACK_XAF_TO_NGN');
        $cacheKey = 'oxr:last_good';
        if (!$this->appId) {
            // Try cached last good first
            if (Cache::has($cacheKey)) {
                $cached = Cache::get($cacheKey);
                return $cached + ['raw' => ['note' => 'using cached last_good (no app id)']];
            }
            if ($fallback !== null && is_numeric($fallback)) {
                // synthesize USD rates from fallback XAF->NGN cross
                // choose an arbitrary USD->XAF and compute USD->NGN to match the cross
                $usdToXaf = 600.0; // nominal anchor
                $usdToNgn = $usdToXaf * (float) $fallback; // maintain cross = NGN/XAF
                return [
                    'usd_to_xaf' => $usdToXaf,
                    'usd_to_ngn' => $usdToNgn,
                    'fetched_at' => now(),
                    'raw' => ['note' => 'using FALLBACK_XAF_TO_NGN'],
                ];
            }
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
                // HTTP error: return cached last good if present
                if (Cache::has($cacheKey)) {
                    $cached = Cache::get($cacheKey);
                    return $cached + ['raw' => ['note' => 'using cached last_good after http error', 'status' => $resp->status()]];
                }
                if ($fallback !== null && is_numeric($fallback)) {
                    $usdToXaf = 600.0;
                    $usdToNgn = $usdToXaf * (float) $fallback;
                    return [
                        'usd_to_xaf' => $usdToXaf,
                        'usd_to_ngn' => $usdToNgn,
                        'fetched_at' => now(),
                        'raw' => ['note' => 'fallback_after_http_error', 'status' => $resp->status(), 'body' => $resp->json()],
                    ];
                }
                return [
                    'usd_to_xaf' => null,
                    'usd_to_ngn' => null,
                    'fetched_at' => now(),
                    'raw' => ['status' => $resp->status(), 'body' => $resp->json()],
                ];
            }

            $json = $resp->json();
            $rates = $json['rates'] ?? [];

            $usd_to_xaf = isset($rates['XAF']) ? (float) $rates['XAF'] : null;
            $usd_to_ngn = isset($rates['NGN']) ? (float) $rates['NGN'] : null;
            if (($usd_to_xaf === null || $usd_to_ngn === null)) {
                if (Cache::has($cacheKey)) {
                    $cached = Cache::get($cacheKey);
                    return $cached + ['raw' => ['note' => 'using cached last_good; symbol missing', 'base' => $json]];
                }
                if ($fallback !== null && is_numeric($fallback)) {
                $usd_to_xaf = $usd_to_xaf ?? 600.0;
                $usd_to_ngn = $usd_to_xaf * (float) $fallback;
                return [
                    'usd_to_xaf' => $usd_to_xaf,
                    'usd_to_ngn' => $usd_to_ngn,
                    'fetched_at' => now(),
                    'raw' => ['note' => 'fallback_missing_symbol', 'base' => $json],
                ];
                }
            }
            $result = [
                'usd_to_xaf' => $usd_to_xaf,
                'usd_to_ngn' => $usd_to_ngn,
                'fetched_at' => now(),
                'raw' => $json,
            ];
            // Cache last good for configurable TTL (minutes)
            $ttlMinutes = (int) (env('OXR_CACHE_TTL_MINUTES', 60));
            Cache::put($cacheKey, [
                'usd_to_xaf' => $usd_to_xaf,
                'usd_to_ngn' => $usd_to_ngn,
                'fetched_at' => now(),
            ], now()->addMinutes(max($ttlMinutes, 5)));
            return $result;
        } catch (\Throwable $e) {
            if (Cache::has($cacheKey)) {
                $cached = Cache::get($cacheKey);
                return $cached + ['raw' => ['note' => 'using cached last_good after exception', 'exception' => $e->getMessage()]];
            }
            if ($fallback !== null && is_numeric($fallback)) {
                $usdToXaf = 600.0;
                $usdToNgn = $usdToXaf * (float) $fallback;
                return [
                    'usd_to_xaf' => $usdToXaf,
                    'usd_to_ngn' => $usdToNgn,
                    'fetched_at' => now(),
                    'raw' => ['note' => 'fallback_after_exception', 'exception' => $e->getMessage()],
                ];
            }
            return [
                'usd_to_xaf' => null,
                'usd_to_ngn' => null,
                'fetched_at' => now(),
                'raw' => ['exception' => $e->getMessage()],
            ];
        }
    }
}
