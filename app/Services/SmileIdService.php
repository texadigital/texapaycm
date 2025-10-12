<?php

namespace App\Services;

use App\Models\AdminSetting;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SmileIdService
{
    /**
     * Generate signature for Smile ID REST per docs:
     * base64(HMAC_SHA256(timestamp + partner_id + 'sid_request', api_key))
     */
    public function generateSignature(string $timestamp): string
    {
        $apiKey = (string) env('SMILE_ID_API_KEY', '');
        $partnerId = (string) env('SMILE_ID_PARTNER_ID', '');
        if ($apiKey === '' || $partnerId === '') {
            Log::warning('Smile ID API key or Partner ID missing when generating signature');
        }
        $message = $timestamp . $partnerId . 'sid_request';
        return base64_encode(hash_hmac('sha256', $message, $apiKey, true));
    }

    /**
     * ISO8601 UTC timestamp (e.g., 2025-09-30T12:59:00.123Z)
     */
    public function nowIso8601Utc(): string
    {
        return now('UTC')->format('Y-m-d\TH:i:s.v\Z');
    }

    /**
     * Prepare start-session payload for Web/Flutter SDK.
     * Returns token-ish values the client needs to invoke Smile ID.
     */
    public function startSession(User $user): array
    {
        $partnerId = (string) env('SMILE_ID_PARTNER_ID', '');
        $timestamp = $this->nowIso8601Utc();
        $signature = $this->generateSignature($timestamp);

        // Allow sandbox/public override to guarantee Smile can reach our webhook
        $callbackUrl = (string) (env('SMILE_ID_CALLBACK_URL') ?: route('kyc.smileid.callback'));
        $country = 'CM'; // Cameroon context

        // Provide minimal job parameters for client SDK
        $partnerParams = [
            'job_id' => 'job_' . $user->id . '_' . now()->timestamp,
            'job_type' => 6, // Document Verification + Selfie
            'user_id' => 'user_' . $user->id,
        ];

        return [
            'smile_client_id' => $partnerId,
            'timestamp' => $timestamp,
            'signature' => $signature,
            'callback_url' => $callbackUrl,
            'partner_params' => $partnerParams,
            'country' => $country,
            'source_sdk' => 'web',
            'source_sdk_version' => '1.0.0',
        ];
    }

    /**
     * Basic webhook signature verification stub; extend to match Smile ID contract.
     */
    public function verifyWebhook(array $payload, ?string $signatureHeader): bool
    {
        // Smile ID responses include 'signature' and 'timestamp' fields in payload per docs.
        $timestamp = (string) ($payload['timestamp'] ?? '');
        $provided = (string) ($payload['signature'] ?? ($signatureHeader ?? ''));
        if ($timestamp === '' || $provided === '') {
            return false;
        }
        $expected = $this->generateSignature($timestamp);
        // Constant-time comparison
        return hash_equals($expected, $provided);
    }

    /**
     * Map Smile ID result into our internal KYC status/level.
     */
    public function mapResultToStatus(array $payload): array
    {
        $resultCode = (string) ($payload['ResultCode'] ?? '');
        $actions = (array) ($payload['Actions'] ?? []);

        // Success: document verified and liveness/selfie compare passed
        $isApproved = ($resultCode === '0810')
            || (($actions['Verify_Document'] ?? '') === 'Passed' && ($actions['Liveness_Check'] ?? 'Not Applicable') !== 'Failed');

        if ($isApproved) {
            return ['kyc_status' => 'verified', 'kyc_level' => 1];
        }

        // Under Review / Pending
        if (($actions['Verify_Document'] ?? '') === 'Under Review' || ($actions['Liveness_Check'] ?? '') === 'Under Review') {
            return ['kyc_status' => 'pending', 'kyc_level' => 0];
        }

        return ['kyc_status' => 'failed', 'kyc_level' => 0];
    }
}
