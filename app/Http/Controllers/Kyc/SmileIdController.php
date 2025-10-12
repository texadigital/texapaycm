<?php

namespace App\Http\Controllers\Kyc;

use App\Http\Controllers\Controller;
use App\Models\AdminSetting;
use App\Models\User;
use App\Services\SmileIdService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SmileIdController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

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
        if (class_exists('SmileIdentity\\WebApi')) {
            $partnerId = (string) env('SMILE_ID_PARTNER_ID', '');
            $apiKey = (string) env('SMILE_ID_API_KEY', '');
            $sidServer = (int) env('SMILE_ID_SID_SERVER', 0); // 0 sandbox, 1 live per docs
            $callback = route('kyc.smileid.callback');

            $jobId = 'job_' . $user->id . '_' . now()->timestamp;
            $userRef = 'user_' . $user->id;

            $webApi = new \SmileIdentity\WebApi($partnerId, $callback, $apiKey, $sidServer);

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

            // Send KYC started notification
            $this->notificationService->dispatchUserNotification('kyc.started', $user, [
                'provider' => 'smileid',
                'job_id' => $jobId
            ]);

            return response()->json(['enabled' => true, 'token' => $token['token'] ?? $token]);
        }

        // Fallback: return our existing session payload
        $session = $svc->startSession($user);
        $user->increment('kyc_attempts');
        $user->update(['kyc_status' => 'pending']);
        
        // Send KYC started notification
        $this->notificationService->dispatchUserNotification('kyc.started', $user, [
            'provider' => 'smileid',
            'job_id' => $session['partner_params']['job_id'] ?? null
        ]);
        
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

        // Send KYC started notification
        $this->notificationService->dispatchUserNotification('kyc.started', $user, [
            'provider' => 'smileid',
            'job_id' => $session['partner_params']['job_id'] ?? null
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
        // Smile ID REST now prefers header-based auth per docs
        $timestamp = (string) ($request->header('SmileID-Request-Timestamp') ?? ($payload['timestamp'] ?? ''));
        $providedSig = (string) ($request->header('SmileID-Request-Signature') ?? ($payload['signature'] ?? ''));
        $expectedSig = $timestamp !== '' ? $svc->generateSignature($timestamp) : '';
        if ($timestamp === '' || $providedSig === '' || !hash_equals($expectedSig, $providedSig)) {
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

        // AML Audit: record KYC callback payload (sanitized summary)
        try {
            \App\Models\AmlAuditLog::create([
                'actor_type' => 'system',
                'actor_id' => null,
                'action' => 'kyc.callback',
                'subject_type' => 'user',
                'subject_id' => $user->id,
                'payload' => [
                    'provider' => 'smileid',
                    'ref' => $providerRef,
                    'mapped' => $mapped,
                    'result_code' => $payload['ResultCode'] ?? null,
                ],
            ]);
        } catch (\Throwable $e) { /* ignore audit failures */ }

        // AML: Trigger screening on KYC status update
        try {
            $screening = app(\App\Services\ScreeningService::class);
            $result = $screening->runUserScreening($user, 'kyc_update');

            // AML Audit: record screening run
            try {
                \App\Models\AmlAuditLog::create([
                    'actor_type' => 'system',
                    'actor_id' => null,
                    'action' => 'screening.run',
                    'subject_type' => 'user',
                    'subject_id' => $user->id,
                    'payload' => $result,
                ]);
            } catch (\Throwable $e) { /* ignore audit failures */ }

            $decision = (string) ($result['decision'] ?? 'pass');
            $sanctions = (bool) ($result['sanctions_hit'] ?? false);
            $pep = (bool) ($result['pep_match'] ?? false);
            $adverse = (bool) ($result['adverse_media'] ?? false);

            if ($sanctions || $pep || $adverse || in_array($decision, ['review','fail'], true)) {
                // Open or reuse an EDD case for this user
                $open = \App\Models\EddCase::query()
                    ->where('user_id', $user->id)
                    ->whereIn('status', ['open','pending_docs','review'])
                    ->first();
                if (!$open) {
                    // Determine required docs and flags per risk
                    $requiredDocs = [
                        'source_of_funds',
                        'bank_statement',
                        'id_copy',
                        'address_proof',
                    ];
                    $metadata = $result;
                    $metadata['required_docs'] = $requiredDocs;
                    $metadata['requires_mlro_approval'] = true;
                    $metadata['senior_mgmt_required'] = $pep === true; // senior mgmt sign-off for PEPs
                    // Determine whether this EDD case should block transactions
                    $blockOnOpen = (bool) AdminSetting::getValue('aml.edd.block_on_open', false);
                    $blockReasonsCsv = (string) AdminSetting::getValue('aml.edd.block_reasons', 'sanctions,pep');
                    $blockReasons = array_filter(array_map('trim', explode(',', strtolower($blockReasonsCsv))));
                    $reasonKey = $sanctions ? 'sanctions' : ($pep ? 'pep' : ($adverse ? 'adverse' : 'review'));
                    $shouldBlock = $blockOnOpen || in_array($reasonKey, $blockReasons, true);
                    $metadata['block'] = $shouldBlock;

                    $open = \App\Models\EddCase::create([
                        'user_id' => $user->id,
                        'case_ref' => 'EDD-' . now()->format('YmdHis') . '-' . $user->id,
                        'risk_reason' => $sanctions ? 'Sanctions hit' : ($pep ? 'PEP match' : ($adverse ? 'Adverse media' : 'Screening review')),
                        'trigger_source' => 'screening',
                        'status' => 'pending_docs',
                        'sla_due_at' => now()->addDays(2),
                        'metadata' => $metadata,
                    ]);
                    // Email compliance about EDD created via screening
                    try {
                        app(\App\Services\ComplianceAlertService::class)->send('EDD Case Opened (Screening)', [
                            'edd_case_id' => $open->id,
                            'user_id' => $user->id,
                            'decision' => $decision,
                            'sanctions' => $sanctions,
                            'pep' => $pep,
                            'adverse' => $adverse,
                        ]);
                    } catch (\Throwable $e) { /* ignore */ }
                    // AML Audit: record EDD case opened
                    try {
                        \App\Models\AmlAuditLog::create([
                            'actor_type' => 'system',
                            'actor_id' => null,
                            'action' => 'edd.opened',
                            'subject_type' => 'edd_case',
                            'subject_id' => $open->id,
                            'payload' => [
                                'user_id' => $user->id,
                                'reason' => $open->risk_reason,
                            ],
                        ]);
                    } catch (\Throwable $e) { /* ignore audit failures */ }
                }
                \Log::warning('AML: EDD case required after screening', [
                    'user_id' => $user->id,
                    'edd_case_id' => $open->id,
                    'decision' => $decision,
                    'sanctions' => $sanctions,
                    'pep' => $pep,
                    'adverse' => $adverse,
                ]);
            }
        } catch (\Throwable $e) {
            \Log::error('AML screening after KYC failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Send KYC completion notification
        if ($mapped['kyc_status'] === 'verified') {
            $this->notificationService->dispatchUserNotification('kyc.completed', $user, [
                'level' => $mapped['kyc_level'],
                'provider_ref' => $providerRef
            ]);
        } else {
            $this->notificationService->dispatchUserNotification('kyc.failed', $user, [
                'reason' => $payload['ResultText'] ?? 'Verification failed',
                'provider_ref' => $providerRef
            ]);
        }

        Log::info('kyc_webhook_processed', [
            'user_id' => $user->id,
            'kyc_status' => $user->kyc_status,
            'kyc_level' => $user->kyc_level,
        ]);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Return current user's active EDD requirements (mobile onboarding assistance).
     */
    public function edd(Request $request)
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        $case = \App\Models\EddCase::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['open','pending_docs','review'])
            ->latest('id')
            ->first();
        if (!$case) {
            return response()->json(['active' => false]);
        }
        return response()->json([
            'active' => true,
            'status' => $case->status,
            'case_ref' => $case->case_ref,
            'required_docs' => $case->metadata['required_docs'] ?? [],
            'requires_mlro_approval' => $case->metadata['requires_mlro_approval'] ?? false,
            'senior_mgmt_required' => $case->metadata['senior_mgmt_required'] ?? false,
            'sla_due_at' => optional($case->sla_due_at)->toIso8601String(),
        ]);
    }

    /**
     * Health check for Smile ID configuration and signature verification.
     * - Does NOT make any external requests.
     * - Returns env/config presence and verifies a sample signature.
     * - If headers are provided (SmileID-Request-Timestamp/Signature), validates them too.
     */
    public function health(Request $request, SmileIdService $svc)
    {
        $partnerId = (string) env('SMILE_ID_PARTNER_ID', '');
        $apiKeySet = !empty(env('SMILE_ID_API_KEY'));
        $sidServer = (int) env('SMILE_ID_SID_SERVER', 0);
        $callback = route('kyc.smileid.callback');

        // Generate a sample timestamp/signature and verify round-trip
        $ts = $svc->nowIso8601Utc();
        $sig = $svc->generateSignature($ts);
        $roundTrip = hash_equals($svc->generateSignature($ts), $sig);

        // If caller supplied headers or payload, validate them too
        $hdrTs = (string) ($request->header('SmileID-Request-Timestamp') ?? ($request->input('timestamp') ?? ''));
        $hdrSig = (string) ($request->header('SmileID-Request-Signature') ?? ($request->input('signature') ?? ''));
        $hdrValid = false;
        if ($hdrTs !== '' && $hdrSig !== '') {
            $hdrValid = hash_equals($svc->generateSignature($hdrTs), $hdrSig);
        }

        return response()->json([
            'config' => [
                'partner_id_set' => $partnerId !== '',
                'sid_server' => $sidServer,
                'api_key_set' => $apiKeySet,
                'callback' => $callback,
            ],
            'signature' => [
                'sample_timestamp' => $ts,
                'sample_signature' => $sig,
                'round_trip_ok' => $roundTrip,
                'header_supplied' => $hdrTs !== '' && $hdrSig !== '',
                'header_valid' => $hdrValid,
            ],
            'status' => ($partnerId !== '' && $apiKeySet && $roundTrip) ? 'ok' : 'check_config',
        ]);
    }
}
