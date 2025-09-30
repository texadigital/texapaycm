<?php

namespace App\Http\Controllers\Kyc;

use App\Http\Controllers\Controller;
use App\Models\AdminSetting;
use App\Models\User;
use App\Services\SmileIdService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SmileIdController extends Controller
{
    /**
     * Return a Web Token for the Smile ID Web Integration when the official library is available.
     * Falls back to our existing start-session payload if the library is not installed.
     */
    public function webToken(Request $request, SmileIdService $svc)
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        $enabled = (bool) AdminSetting::getValue('kyc_enabled', false);
        if (!$enabled) {
            return response()->json(['enabled' => false, 'message' => 'KYC is currently disabled']);
        }

        // Prefer official library if present
        if (class_exists('SmileIdentityCore\\WebApi')) {
            $partnerId = (string) env('SMILE_ID_PARTNER_ID', '');
            $apiKey = (string) env('SMILE_ID_API_KEY', '');
            $sidServer = (int) env('SMILE_ID_SID_SERVER', 0); // 0 sandbox, 1 live per docs
            $callback = route('kyc.smileid.callback');

            $jobId = 'job_' . $user->id . '_' . now()->timestamp;
            $userRef = 'user_' . $user->id;

            $webApi = new \SmileIdentityCore\WebApi($partnerId, $callback, $apiKey, $sidServer);

            // Use document verification per requirement (ID upload, Cameroon)
            $requestParams = [
                'user_id' => $userRef,
                'job_id' => $jobId,
                'product' => 'doc_verification',
                'callback_url' => $callback,
            ];

            $token = $webApi->get_web_token($requestParams);

            // Mark as pending
            $user->increment('kyc_attempts');
            $user->update(['kyc_status' => 'pending']);

            return response()->json(['enabled' => true, 'token' => $token['token'] ?? $token]);
        }

        // Fallback: return our existing session payload
        $session = $svc->startSession($user);
        $user->increment('kyc_attempts');
        $user->update(['kyc_status' => 'pending']);
        return response()->json(['enabled' => true, 'provider' => 'smileid', 'session' => $session]);
    }
    public function start(Request $request, SmileIdService $svc)
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        // Feature flag gate: if disabled, return legacy no-op
        $enabled = (bool) AdminSetting::getValue('kyc_enabled', false);
        if (!$enabled) {
            return response()->json([
                'enabled' => false,
                'message' => 'KYC is currently disabled',
            ]);
        }

        $session = $svc->startSession($user);
        Log::info('kyc_started', [
            'user_id' => $user->id,
            'provider' => 'smileid',
            'job_id' => $session['partner_params']['job_id'] ?? null,
        ]);

        // Mark status pending on start to reflect UI
        $user->increment('kyc_attempts');
        $user->update([
            'kyc_status' => 'pending',
        ]);

        return response()->json([
            'enabled' => true,
            'provider' => 'smileid',
            'session' => $session,
        ]);
    }

    public function callback(Request $request, SmileIdService $svc)
    {
        $payload = $request->all();
        $signature = $request->header('X-Smile-Signature');

        if (!$svc->verifyWebhook($payload, $signature)) {
            Log::warning('kyc_webhook_error', [
                'reason' => 'invalid_signature',
            ]);
            return response()->json(['error' => 'invalid signature'], 400);
        }

        $partnerParams = (array) ($payload['PartnerParams'] ?? []);
        $userRef = (string) ($partnerParams['user_id'] ?? '');
        $userId = (int) (preg_replace('/^user_/', '', $userRef));
        $user = User::find($userId);
        if (!$user) {
            Log::error('kyc_webhook_error', ['reason' => 'user_not_found', 'user_ref' => $userRef]);
            return response()->json(['status' => 'ignored']);
        }

        $mapped = $svc->mapResultToStatus($payload);
        $providerRef = (string) ($payload['SmileJobID'] ?? ($payload['ref_id'] ?? ''));

        $updates = [
            'kyc_status' => $mapped['kyc_status'],
            'kyc_level' => $mapped['kyc_level'],
            'kyc_provider_ref' => $providerRef,
            'kyc_meta' => [
                'ResultCode' => $payload['ResultCode'] ?? null,
                'ResultText' => $payload['ResultText'] ?? null,
                'Country' => $payload['Country'] ?? null,
                'IDType' => $payload['IDType'] ?? null,
                'IDNumber' => $payload['IDNumber'] ?? null,
                'Actions' => $payload['Actions'] ?? null,
            ],
        ];
        if ($mapped['kyc_status'] === 'verified') {
            $updates['kyc_verified_at'] = now();
        }

        $user->update($updates);

        Log::info('kyc_webhook_processed', [
            'user_id' => $user->id,
            'kyc_status' => $user->kyc_status,
            'kyc_level' => $user->kyc_level,
        ]);

        return response()->json(['status' => 'ok']);
    }
}
