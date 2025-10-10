<?php

namespace App\Services\Screening;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmileIdProvider implements Provider
{
    /**
     * Minimal Smile ID Compliance adapter.
     * Expects env:
     * - SMILE_ID_PARTNER_ID
     * - SMILE_ID_API_KEY
     * - SMILE_ID_SID_SERVER (0 sandbox, 1 live)
     *
     * NOTE: Replace the placeholder endpoint/payload with the Smile Compliance API you subscribe to.
     */
    public function screen(User $user): array
    {
        try {
            $partnerId = (string) env('SMILE_ID_PARTNER_ID', '');
            $apiKey = (string) env('SMILE_ID_API_KEY', '');
            $sidServer = (int) env('SMILE_ID_SID_SERVER', 0);

            if ($partnerId === '' || $apiKey === '') {
                Log::warning('SmileIdProvider: missing credentials, returning pass-by-default');
                return [
                    'sanctions_hit' => false,
                    'pep_match' => false,
                    'adverse_media' => false,
                    'risk_score' => 10,
                    'decision' => 'pass',
                    'matches' => [],
                ];
            }

            // Example placeholder request. Re'https://api.smileidentity.com/v1/smile_links' : 'https://testapi.smileidentity.com/v1/smile_links';place `compliance/screen` with Smile's actual endpoint.
            $base = $sidServer === 1 ? 
            $endpoint = rtrim($base, '/').'/compliance/screen';

            $payload = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->full_name ?? $user->name ?? null,
                    'email' => $user->email,
                    'phone' => $user->phone ?? null,
                    'country' => $user->country ?? 'CM',
                ],
                'context' => 'kyc_update',
            ];

            $resp = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'SmileID-Partner-ID' => $partnerId,
                'SmileID-API-Key' => $apiKey,
            ])->timeout(10)->post($endpoint, $payload);
            if (!$resp->ok()) {
                Log::warning('SmileIdProvider: non-OK response', ['status' => $resp->status(), 'body' => $resp->body()]);
                return [
                    'sanctions_hit' => false,
                    'pep_match' => false,
                    'adverse_media' => false,
                    'risk_score' => 10,
                    'decision' => 'review',
                    'matches' => ['note' => 'Provider non-OK'],
                ];
            }

            $data = $resp->json();
            // Map provider response to normalized structure (adjust to real schema)
            $sanctions = (bool) data_get($data, 'sanctions_hit', false);
            $pep = (bool) data_get($data, 'pep_match', false);
            $adverse = (bool) data_get($data, 'adverse_media', false);
            $score = (int) data_get($data, 'risk_score', $sanctions || $pep ? 80 : 10);
            $decision = (string) data_get($data, 'decision', ($sanctions || $pep || $adverse) ? 'review' : 'pass');

            return [
                'sanctions_hit' => $sanctions,
                'pep_match' => $pep,
                'adverse_media' => $adverse,
                'risk_score' => $score,
                'decision' => $decision,
                'matches' => (array) data_get($data, 'matches', []),
            ];
        } catch (\Throwable $e) {
            Log::error('SmileIdProvider error', ['error' => $e->getMessage()]);
            return [
                'sanctions_hit' => false,
                'pep_match' => false,
                'adverse_media' => false,
                'risk_score' => 0,
                'decision' => 'error',
                'matches' => [],
            ];
        }
    }
}
