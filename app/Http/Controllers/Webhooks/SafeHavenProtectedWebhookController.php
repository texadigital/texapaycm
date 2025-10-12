<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\ProtectedTransaction;
use App\Models\ProtectedAuditLog;
use App\Models\WebhookEvent;
use App\Services\ProtectedFeeService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SafeHavenProtectedWebhookController extends Controller
{
    public function __invoke(Request $request, ProtectedFeeService $feeSvc)
    {
        // Expect SafeHaven payload per docs
        $raw = $request->getContent();
        $payload = json_decode($raw, true) ?? [];
        if (!is_array($payload)) { $payload = []; }
        $type = (string) ($payload['type'] ?? '');
        $data = (array) ($payload['data'] ?? []);
        // Prefer _id as stable event id; fallback to sessionId
        $eventId = (string) ($data['_id'] ?? ($data['sessionId'] ?? ''));
        if ($eventId === '') {
            return response('Missing eventId', 400);
        }

        // Idempotency: store or fetch existing event
        $existing = WebhookEvent::where([
            'provider' => 'safehaven',
            'type' => 'va_credit', // normalized internal type
            'event_id' => $eventId,
        ])->first();
        if ($existing) {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }

        $webhook = WebhookEvent::create([
            'provider' => 'safehaven',
            'type' => 'va_credit',
            'event_id' => $eventId,
            'signature_hash' => $request->header('X-Signature'),
            'payload' => $payload,
        ]);

        // Extract and normalize values from data block
        $accountNumber = (string) ($data['creditAccountNumber'] ?? '');
        $reference = (string) ($data['paymentReference'] ?? '');
        $nameEnquiryRef = (string) ($data['nameEnquiryReference'] ?? '');
        $destBank = (string) ($data['destinationInstitutionCode'] ?? '');
        $accountNumber = preg_replace('/\D+/', '', trim($accountNumber));
        $reference = trim($reference);
        $nameEnquiryRef = trim($nameEnquiryRef);
        $destBank = strtoupper(trim($destBank));

        // Accept both documented types
        if (!in_array($type, ['virtualAccount.transfer','transfer'], true)) {
            \Log::warning('Protected VA webhook: unsupported type', ['type' => $type]);
            return response()->json(['ok' => true, 'note' => 'unsupported_type']);
        }
        $amountNgn = (float) ($data['amount'] ?? 0);
        $amountMinor = (int) round($amountNgn * 100);

        // Prefer exact funding_ref match first (fast path)
        $txn = null;
        $matchedBy = null;
        if ($reference !== '') {
            $txn = ProtectedTransaction::where('funding_ref', $reference)->first();
            if ($txn) { $matchedBy = 'funding_ref'; }
        }
        // Fallback to name_enquiry_reference
        if (!$txn && $nameEnquiryRef !== '') {
            $txn = ProtectedTransaction::where('name_enquiry_reference', $nameEnquiryRef)->orderByDesc('id')->first();
            if ($txn) { $matchedBy = 'name_enquiry_reference'; }
        }
        // Fallback to VA account / VA reference
        if (!$txn && ($accountNumber !== '' || $reference !== '')) {
            $txn = ProtectedTransaction::query()
                ->where(function ($q) use ($accountNumber, $reference) {
                    $q->where('va_account_number', $accountNumber)
                      ->orWhere('va_reference', $reference);
                })
                ->orderByDesc('id')
                ->first();
            if ($txn) { $matchedBy = $txn->va_account_number === $accountNumber ? 'va_account_number' : 'va_reference'; }
        }
        // Fallback to receiver account + bank
        if (!$txn && $accountNumber !== '') {
            $query = ProtectedTransaction::where('receiver_account_number', $accountNumber);
            if ($destBank !== '') { $query->where('receiver_bank_code', $destBank); }
            $txn = $query->orderByDesc('id')->first();
            if ($txn) { $matchedBy = 'receiver_account+bank'; }
        }

        if (!$txn) {
            \Log::warning('Protected VA credit webhook: transaction not found', ['accountNumber' => $accountNumber, 'reference' => $reference, 'nameEnquiryRef' => $nameEnquiryRef, 'destBank' => $destBank]);
            $debug = (bool) $request->boolean('debug', false) || app()->environment('local', 'testing');
            if ($debug) {
                $byRef = \App\Models\ProtectedTransaction::where('funding_ref', $reference)->count();
                $byEnq = \App\Models\ProtectedTransaction::where('name_enquiry_reference', $nameEnquiryRef)->count();
                $byRecv = \App\Models\ProtectedTransaction::where('receiver_account_number', $accountNumber)
                    ->when($destBank !== '', function ($q) use ($destBank) { $q->where('receiver_bank_code', $destBank); })
                    ->count();
                return response()->json([
                    'ok' => false,
                    'note' => 'txn_not_found',
                    'match' => [
                        'funding_ref' => $byRef,
                        'name_enquiry_reference' => $byEnq,
                        'receiver_account+bank' => $byRecv,
                    ],
                    'parsed' => [
                        'type' => $type,
                        'nameEnquiryReference' => $nameEnquiryRef,
                        'creditAccountNumber' => $accountNumber,
                        'destinationInstitutionCode' => $destBank,
                    ],
                ]);
            }
            return response()->json(['ok' => true, 'note' => 'txn_not_found', 'matched_by' => $matchedBy]);
        }
if (in_array($txn->escrow_state, [ProtectedTransaction::STATE_LOCKED, ProtectedTransaction::STATE_AWAITING, ProtectedTransaction::STATE_RELEASED])) {
            $webhook->processed_at = now();
            $webhook->save();
            \Log::info('Protected VA credit processed', ['ref' => $txn->funding_ref, 'state' => $txn->escrow_state]);
            $resp = ['ok' => true, 'state' => $txn->escrow_state];
            if ((bool) $request->boolean('debug', false) || app()->environment('local', 'testing')) {
                $resp['matched_by'] = $matchedBy;
{{ ... }}
                $resp['debug'] = [
                    'txn_id' => $txn->id,
                    'txn_state' => $txn->escrow_state,
                    'matched_by' => $matchedBy,
                ];
            }
            return response()->json($resp);
        }

        // Amount check (if provided). If mismatch, log and in non-prod proceed to lock for sandbox convenience.
        if ($amountMinor > 0 && $txn->amount_ngn_minor > 0 && $amountMinor < $txn->amount_ngn_minor) {
            \Log::warning('Protected VA credit amount less than expected', [
                'expected' => $txn->amount_ngn_minor,
                'got' => $amountMinor,
            ]);
            if (app()->environment('production')) {
                return response()->json(['ok' => false, 'error' => 'amount_mismatch'], 422);
            }
        }

        DB::transaction(function () use ($txn, $feeSvc, $payload, $amountMinor, $webhook, $type, $data) {
            // Lock and compute fee
            $txn->locked_at = now();
            $calc = $feeSvc->calculate($txn->amount_ngn_minor);
            $txn->fee_ngn_minor = $calc['fee_ngn_minor'];
            $txn->fee_rule_version = $calc['fee_rule_version'];
            $txn->fee_components = $calc['fee_components'];
            $txn->escrow_state = ProtectedTransaction::STATE_AWAITING; // move to awaiting approval
            $txn->auto_release_at = now()->addDays(5);
            $txn->funding_status = 'success';
            $txn->appendTimeline([
                'event' => 'funding_locked',
                'amount_minor' => $amountMinor,
                'provider' => 'safehaven',
                'payload' => ['type' => $type, 'data' => $data],
            ]);
            $txn->save();

            ProtectedAuditLog::create([
                'protected_transaction_id' => $txn->id,
                'actor_type' => 'provider',
                'actor_id' => null,
                'from_state' => ProtectedTransaction::STATE_CREATED,
                'to_state' => ProtectedTransaction::STATE_AWAITING,
                'at' => now(),
                'reason' => 'va_credit',
                'meta' => ['provider' => 'safehaven', 'event_id' => $webhook->event_id],
            ]);

            $webhook->processed_at = now();
            $webhook->save();
        });

        // Notify buyer that funds are locked in escrow
        try {
            app(NotificationService::class)->dispatchUserNotification('protected.locked', $txn->buyer, [
                'funding_ref' => $txn->funding_ref,
                'amount_ngn_minor' => $txn->amount_ngn_minor,
                'auto_release_at' => optional($txn->auto_release_at)->toIso8601String(),
            ]);
        } catch (\Throwable $e) { \Log::warning('Protected locked notification failed', ['id'=>$txn->id,'err'=>$e->getMessage()]); }

        return response()->json(['ok' => true]);
    }
}
