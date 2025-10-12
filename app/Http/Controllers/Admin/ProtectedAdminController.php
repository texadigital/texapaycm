<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProtectedTransaction;
use App\Models\ProtectedAuditLog;
use App\Services\ProtectedFeeService;
use App\Services\SafeHaven;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProtectedAdminController extends Controller
{
    public function resolve(Request $request, int $id, SafeHaven $safeHaven, ProtectedFeeService $feeSvc, NotificationService $notifier)
    {
        // Require authenticated admin
        $user = $request->user();
        if (!$user || !($user->is_admin ?? false)) {
            abort(403);
        }

        $validated = $request->validate([
            'action' => 'required|in:release,refund,partial',
            'partialAmountNgnMinor' => 'nullable|integer|min:0',
            'note' => 'nullable|string',
        ]);

        $txn = ProtectedTransaction::findOrFail($id);
        if ($txn->escrow_state !== ProtectedTransaction::STATE_DISPUTED && $validated['action'] !== 'release') {
            // Allow release from awaiting_approval too, but refunds only from disputed
            return response()->json(['error' => 'Invalid state for action'], 422);
        }

        return DB::transaction(function () use ($txn, $validated, $safeHaven, $feeSvc, $notifier) {
            $from = $txn->escrow_state;
            $note = $validated['note'] ?? null;
            $action = $validated['action'];

            if ($action === 'release') {
                // Ensure fee snapshot
                if (!$txn->fee_ngn_minor || !$txn->fee_rule_version) {
                    $calc = $feeSvc->calculate($txn->amount_ngn_minor);
                    $txn->fee_ngn_minor = $calc['fee_ngn_minor'];
                    $txn->fee_rule_version = $calc['fee_rule_version'];
                    $txn->fee_components = $calc['fee_components'];
                }
                $amountMinor = $txn->amount_ngn_minor;
                $netMinor = (int) max(0, $amountMinor - $txn->fee_ngn_minor);
                $paymentRef = $txn->payout_ref ?: ('protpay_' . bin2hex(random_bytes(10)));

                $resp = $safeHaven->payout([
                    'bankCode' => $txn->receiver_bank_code,
                    'accountNumber' => $txn->receiver_account_number,
                    'accountName' => $txn->receiver_account_name,
                    'amountNgn' => $netMinor / 100.0,
                    'narration' => 'Texa Protected admin release',
                    'paymentReference' => $paymentRef,
                ]);

                $txn->payout_ref = $paymentRef;
                $txn->payout_status = $resp['status'] ?? 'initiated';
                $txn->payout_attempted_at = now();
                $txn->escrow_state = ProtectedTransaction::STATE_RELEASED;
                $txn->appendTimeline(['event' => 'admin_release', 'net_minor' => $netMinor, 'resp' => $resp, 'note' => $note]);
                $txn->save();

                ProtectedAuditLog::create([
                    'protected_transaction_id' => $txn->id,
                    'actor_type' => 'admin',
                    'actor_id' => $txn->buyer_user_id,
                    'from_state' => $from,
                    'to_state' => ProtectedTransaction::STATE_RELEASED,
                    'at' => now(),
                    'reason' => 'admin_resolve_release',
                    'meta' => ['note' => $note, 'payout_ref' => $paymentRef],
                ]);

                // Notify buyer
                try { $notifier->dispatchUserNotification('protected.approved', $txn->buyer, [ 'funding_ref'=>$txn->funding_ref, 'payout_ref'=>$txn->payout_ref ]); } catch (\Throwable $e) {}

                return response()->json(['success' => true, 'state' => $txn->escrow_state]);
            }

            if ($action === 'refund' || $action === 'partial') {
                // For MVP: mark as refunded (no provider refund integration). If partial, store note.
                $txn->escrow_state = ProtectedTransaction::STATE_REFUNDED;
                $txn->resolved_at = now();
                $txn->appendTimeline(['event' => $action === 'partial' ? 'admin_partial_refund' : 'admin_refund', 'note' => $note, 'partial_minor' => $validated['partialAmountNgnMinor'] ?? null]);
                $txn->save();

                ProtectedAuditLog::create([
                    'protected_transaction_id' => $txn->id,
                    'actor_type' => 'admin',
                    'actor_id' => null,
                    'from_state' => $from,
                    'to_state' => $txn->escrow_state,
                    'at' => now(),
                    'reason' => $action === 'partial' ? 'admin_resolve_partial' : 'admin_resolve_refund',
                    'meta' => ['note' => $note, 'partial_minor' => $validated['partialAmountNgnMinor'] ?? null],
                ]);

                // Notify buyer
                try { $notifier->dispatchUserNotification('transfer.refund.initiated', $txn->buyer, [ 'funding_ref'=>$txn->funding_ref ]); } catch (\Throwable $e) {}

                return response()->json(['success' => true, 'state' => $txn->escrow_state]);
            }

            return response()->json(['error' => 'Unsupported action'], 400);
        });
    }
}
