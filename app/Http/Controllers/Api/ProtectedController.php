<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProtectedTransaction;
use App\Models\ProtectedAuditLog;
use App\Models\AdminSetting;
use App\Services\ProtectedFeeService;
use App\Services\SafeHaven;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProtectedController extends Controller
{
    protected function ensureEnabled(): void
    {
        $enabled = (bool) (AdminSetting::getValue('protected.enabled', false) ?? false);
        if (!$enabled) {
            abort(404);
        }
    }

    /**
     * Receiver requests release (nudge sender) via either:
     * - Buyer-auth (existing, self-nudge), OR
     * - Signed link (?sig=..., optional exp=unix) so receiver can click without login.
     */
    public function requestRelease(Request $request, string $ref, NotificationService $notifier)
    {
        $this->ensureEnabled();
        $sig = (string) $request->query('sig', '');
        $exp = (string) $request->query('exp', ''); // optional unix ts
        $receiverPhone = trim((string) $request->input('phone', '')) ?: null;
        $receiverName = trim((string) $request->input('name', '')) ?: null;

        // Load txn by ref only; we'll authorize below
        $txn = ProtectedTransaction::where('funding_ref', $ref)->firstOrFail();

        // Authorization: allow if buyer-auth OR valid signed link
        $authorized = false;
        if ($request->user() && $txn->buyer_user_id === $request->user()->id) {
            $authorized = true;
            $source = 'buyer_self';
        } else {
            $source = 'receiver_link';
            $secret = (string) (env('PROTECTED_SIGNING_SECRET') ?: config('app.key'));
            // Build expected signature over ref|exp (exp can be empty)
            $base = $ref . '|' . $exp;
            $expected = hash_hmac('sha256', $base, $secret);
            // TTL: default 24h if exp missing, otherwise require now < exp
            $ttlOk = true;
            if ($exp !== '') {
                $ttlOk = (time() < (int) $exp);
            }
            if ($sig !== '' && hash_equals($expected, $sig) && $ttlOk) {
                $authorized = true;
            }
        }
        if (!$authorized) {
            return response()->json(['error' => 'UNAUTHORIZED'], 403);
        }

        if ($txn->escrow_state !== ProtectedTransaction::STATE_AWAITING) {
            return response()->json(['error' => 'Not awaiting approval'], 422);
        }

        // Anti-replay: throttle to once per 5 minutes
        $timeline = $txn->audit_timeline ?? [];
        $recentNudge = false;
        $cutoff = now()->subMinutes(5);
        foreach (array_reverse($timeline) as $entry) {
            if (($entry['event'] ?? '') === 'request_release') {
                $at = isset($entry['at']) ? \Carbon\Carbon::parse($entry['at']) : null;
                if ($at && $at->greaterThan($cutoff)) { $recentNudge = true; }
                break;
            }
        }
        if ($recentNudge) {
            return response()->json(['success' => true, 'note' => 'ALREADY_SENT_RECENTLY']);
        }

        $meta = ['event' => 'request_release', 'source' => $source];
        if ($receiverPhone || $receiverName) {
            $meta['receiver'] = ['phone' => $receiverPhone, 'name' => $receiverName];
        }
        $txn->appendTimeline($meta);
        $txn->save();

        // Notify buyer (sender)
        try {
            $notifPayload = [
                'funding_ref' => $txn->funding_ref,
                'amount_ngn_minor' => $txn->amount_ngn_minor,
            ];
            if ($receiverPhone || $receiverName) {
                $notifPayload['receiver'] = ['phone' => $receiverPhone, 'name' => $receiverName];
            }
            $notifier->dispatchUserNotification('protected.approval.requested', $txn->buyer, $notifPayload);
        } catch (\Throwable $e) { \Log::warning('Protected request-release notification failed', ['id'=>$txn->id,'err'=>$e->getMessage()]); }

        return response()->json(['success' => true]);
    }

    public function init(Request $request, SafeHaven $safeHaven)
    {
        $this->ensureEnabled();
        $data = $request->validate([
            'receiver.bankCode' => 'required|string',
            'receiver.accountNumber' => 'required|string',
            'amountNgnMinor' => 'required|integer|min:100',
            'preferredFunding' => 'nullable|in:card,va',
        ]);

        $bankCode = $data['receiver']['bankCode'];
        $acct = $data['receiver']['accountNumber'];

        $enquiry = $safeHaven->nameEnquiry($bankCode, $acct);
        $accountName = $enquiry['accountName'] ?? ($enquiry['account_name'] ?? null);
        $nameEnquiryRef = $enquiry['reference'] ?? ($enquiry['raw']['data']['sessionId'] ?? null);

        $fundingRef = 'prot_' . bin2hex(random_bytes(10));
        $preferred = 'va'; // VA-only path

        $txn = ProtectedTransaction::create([
            'buyer_user_id' => $request->user()->id,
            'receiver_bank_code' => $bankCode,
            'receiver_bank_name' => $enquiry['bankName'] ?? null,
            'receiver_account_number' => $acct,
            'receiver_account_name' => $accountName,
            'name_enquiry_reference' => $nameEnquiryRef,
            'amount_ngn_minor' => (int) $data['amountNgnMinor'],
            'funding_source' => $preferred === 'card' ? 'card' : 'virtual_account',
            'funding_provider' => 'safehaven_va',
            'funding_ref' => $fundingRef,
            'funding_status' => 'pending',
            'escrow_state' => ProtectedTransaction::STATE_CREATED,
        ]);
        $txn->appendTimeline(['event' => 'created', 'preferredFunding' => $preferred, 'enquiry' => $enquiry]);
        $txn->save();

        // Create a per-transaction Virtual Account at SafeHaven
        $callbackBase = rtrim((string) (config('services.safehaven.webhook_base_url') ?? env('APP_URL', '')) , '/');
        $callbackUrl = $callbackBase . '/webhooks/safehaven/va-credits';
        $amountNgn = (int) floor(((int) $data['amountNgnMinor']) / 100);
        $vaResp = $safeHaven->createVirtualAccount([
            'validFor' => 900,
            'callbackUrl' => $callbackUrl,
            'amountControl' => 'Fixed',
            'amount' => $amountNgn,
            'externalReference' => $fundingRef,
        ]);
        if (!($vaResp['success'] ?? false)) {
            // Keep transaction but surface error
            $txn->appendTimeline(['event' => 'va_create_failed', 'resp' => $vaResp['raw'] ?? null]);
            $txn->save();
            return response()->json(['error' => 'VA_CREATE_FAILED', 'details' => $vaResp['raw'] ?? []], 502);
        }
        // Persist VA details
        $txn->va_account_number = $vaResp['account_number'] ?? null;
        $txn->va_bank_code = $vaResp['bank_code'] ?? null;
        $txn->va_reference = $vaResp['reference'] ?? $fundingRef;
        $txn->appendTimeline(['event' => 'va_created', 'va' => [
            'bank_code' => $txn->va_bank_code,
            'account_number' => $txn->va_account_number,
            'reference' => $txn->va_reference,
        ], 'provider_raw' => $vaResp['raw'] ?? null]);
        $txn->save();

        $va = [
            'bankCode' => $txn->va_bank_code,
            'accountNumber' => $txn->va_account_number,
            'reference' => $txn->va_reference,
        ];

        return response()->json([
            'ref' => $txn->funding_ref,
            'escrowState' => $txn->escrow_state,
            'funding' => [
                'va' => $va,
            ],
            'autoReleaseAt' => $txn->auto_release_at,
        ]);
    }

    public function show(Request $request, string $ref)
    {
        $this->ensureEnabled();
        $txn = ProtectedTransaction::where('funding_ref', $ref)
            ->where('buyer_user_id', $request->user()->id)
            ->firstOrFail();

        $resp = [
            'transaction' => $txn->only([
                'funding_ref','escrow_state','amount_ngn_minor','fee_ngn_minor','receiver_bank_code','receiver_bank_name','receiver_account_number','receiver_account_name','auto_release_at','payout_status'
            ]),
            'timeline' => $txn->audit_timeline ?? [],
        ];

        // Surface VA details directly
        $resp['va'] = [
            'bank_code' => $txn->va_bank_code,
            'account_number' => $txn->va_account_number,
            'reference' => $txn->va_reference,
        ];

        // When awaiting approval, include a pre-computed receiver request-release signed link for the PWA to copy
        if ($txn->escrow_state === ProtectedTransaction::STATE_AWAITING) {
            $exp = time() + 86400; // 24h
            $secret = (string) (env('PROTECTED_SIGNING_SECRET') ?: config('app.key'));
            $base = $txn->funding_ref . '|' . $exp;
            $sig = hash_hmac('sha256', $base, $secret);
            $baseUrl = rtrim((string) (config('services.safehaven.webhook_base_url') ?: config('app.url') ?: $request->getSchemeAndHttpHost()), '/');
            // Use API path (mobile app will POST this URL); PWA can display/copy it for the receiver
            $link = $baseUrl . '/api/mobile/protected/' . urlencode($txn->funding_ref) . '/request-release?sig=' . $sig . '&exp=' . $exp;
            $resp['share'] = [ 'requestReleaseLink' => $link ];
        }

        return response()->json($resp);
    }

    public function approve(Request $request, string $ref, SafeHaven $safeHaven, ProtectedFeeService $feeSvc, NotificationService $notifier)
    {
        $this->ensureEnabled();
        $txn = ProtectedTransaction::where('funding_ref', $ref)
            ->where('buyer_user_id', $request->user()->id)
            ->firstOrFail();

        if (!in_array($txn->escrow_state, [ProtectedTransaction::STATE_LOCKED, ProtectedTransaction::STATE_AWAITING])) {
            return response()->json(['error' => 'Not releasable in current state'], 422);
        }

        return DB::transaction(function () use ($txn, $safeHaven, $feeSvc) {
            // Ensure fee snapshot
            if (!$txn->fee_ngn_minor || !$txn->fee_rule_version) {
                $calc = $feeSvc->calculate($txn->amount_ngn_minor);
                $txn->fee_ngn_minor = $calc['fee_ngn_minor'];
                $txn->fee_rule_version = $calc['fee_rule_version'];
                $txn->fee_components = $calc['fee_components'];
            }

            $netMinor = (int) max(0, $txn->amount_ngn_minor - $txn->fee_ngn_minor);
            $paymentRef = $txn->payout_ref ?: ('protpay_' . bin2hex(random_bytes(10)));

            $payload = [
                'bank_code' => $txn->receiver_bank_code,
                'account_number' => $txn->receiver_account_number,
                'amount_ngn_minor' => $netMinor,
                'narration' => 'Texa Protected release',
                'reference' => $paymentRef,
                'name_enquiry_reference' => $txn->name_enquiry_reference,
                'debit_account_number' => env('SAFEHAVEN_DEBIT_ACCOUNT_NUMBER'),
            ];
            // In local/testing (or if explicitly enabled), include beneficiary_name to satisfy some sandbox tenants
            if (app()->environment('local', 'testing')) {
                $payload['beneficiary_name'] = $txn->receiver_account_name;
            }
            $resp = $safeHaven->payout($payload);

            $txn->payout_ref = $paymentRef;
            $txn->payout_status = $resp['status'] ?? 'initiated';
            $txn->payout_attempted_at = now();
            $txn->escrow_state = ProtectedTransaction::STATE_RELEASED;
            $txn->appendTimeline(['event' => 'approve_release', 'net_minor' => $netMinor, 'resp' => $resp]);
            $txn->save();

            ProtectedAuditLog::create([
                'protected_transaction_id' => $txn->id,
                'actor_type' => 'buyer',
                'actor_id' => $txn->buyer_user_id,
                'from_state' => ProtectedTransaction::STATE_AWAITING,
                'to_state' => ProtectedTransaction::STATE_RELEASED,
                'at' => now(),
                'reason' => 'buyer_approved',
                'meta' => ['payout_ref' => $paymentRef],
            ]);

            return response()->json(['success' => true, 'payout' => ['status' => $txn->payout_status, 'payoutRef' => $txn->payout_ref]]);
        });
    }

    public function dispute(Request $request, string $ref)
    {
        $this->ensureEnabled();
        $validated = $request->validate([
            'reason' => 'required|string',
            'details' => 'nullable|string',
        ]);
        $txn = ProtectedTransaction::where('funding_ref', $ref)
            ->where('buyer_user_id', $request->user()->id)
            ->firstOrFail();

        if ($txn->escrow_state !== ProtectedTransaction::STATE_AWAITING) {
            return response()->json(['error' => 'Cannot dispute in current state'], 422);
        }

        $from = $txn->escrow_state;
        $txn->escrow_state = ProtectedTransaction::STATE_DISPUTED;
        $txn->disputed_at = now();
        $txn->appendTimeline(['event' => 'disputed', 'reason' => $validated['reason'], 'details' => $validated['details'] ?? null]);
        $txn->save();

        ProtectedAuditLog::create([
            'protected_transaction_id' => $txn->id,
            'actor_type' => 'buyer',
            'actor_id' => $txn->buyer_user_id,
            'from_state' => $from,
            'to_state' => $txn->escrow_state,
            'at' => now(),
            'reason' => $validated['reason'],
            'meta' => ['details' => $validated['details'] ?? null],
        ]);

        return response()->json(['success' => true, 'state' => $txn->escrow_state]);
    }
}
