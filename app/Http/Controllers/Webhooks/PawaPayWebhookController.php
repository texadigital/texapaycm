<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Transfer;
use App\Services\SafeHaven;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PawaPayWebhookController extends Controller
{
    public function __invoke(Request $request, SafeHaven $safeHaven)
    {
        $payload = $request->json()->all();
        Log::info('PawaPay webhook received', ['payload' => $payload]);

        $depositId = $payload['depositId'] ?? $payload['id'] ?? null;
        $status = $payload['status'] ?? null; // e.g., COMPLETED, REJECTED

        if (!$depositId) {
            return response()->json(['ok' => true]);
        }

        $transfer = Transfer::where('payin_ref', $depositId)->first();
        if (!$transfer) {
            return response()->json(['ok' => true]);
        }

        $timeline = is_array($transfer->timeline) ? $transfer->timeline : [];
        $timeline[] = ['state' => 'payin_webhook', 'at' => now()->toIso8601String(), 'status' => $status];

        if ($status === 'COMPLETED') {
            $transfer->update([
                'payin_status' => 'success',
                'status' => 'payout_pending',
                'payin_at' => now(),
                'timeline' => $timeline,
            ]);

            // Auto-trigger payout with Safe Haven
            if (empty($transfer->name_enquiry_reference)) {
                // try to recover from session or leave null
                $transfer->name_enquiry_reference = session('transfer.name_enquiry_reference');
                $transfer->save();
            }
            $resp = $safeHaven->payout([
                'amount_ngn_minor' => $transfer->receive_ngn_minor,
                'bank_code' => $transfer->recipient_bank_code,
                'account_number' => $transfer->recipient_account_number,
                'account_name' => $transfer->recipient_account_name,
                'narration' => 'TexaPay transfer ' . $transfer->id,
                'reference' => (string) Str::uuid(),
                'name_enquiry_reference' => $transfer->name_enquiry_reference,
                'debit_account_number' => env('SAFEHAVEN_DEBIT_ACCOUNT_NUMBER'),
            ]);

            $timeline[] = ['state' => 'ngn_payout_initiated', 'at' => now()->toIso8601String()];

            $transfer->update([
                'payout_ref' => $resp['ref'],
                'payout_status' => $resp['status'],
                'status' => $resp['status'] === 'success' ? 'payout_success' : ($resp['status'] === 'failed' ? 'failed' : 'payout_pending'),
                'timeline' => $timeline,
            ]);

            if ($resp['status'] === 'success') {
                $timeline[] = ['state' => 'ngn_payout_success', 'at' => now()->toIso8601String()];
                $transfer->update([
                    'timeline' => $timeline,
                    'payout_completed_at' => now(),
                ]);
            } elseif ($resp['status'] === 'failed') {
                \Log::warning('SafeHaven payout failed (webhook path)', [
                    'transfer_id' => $transfer->id,
                    'raw' => $resp['raw'] ?? null,
                ]);
                $reason = $resp['raw']['message'] ?? ($resp['raw']['error'] ?? ($resp['raw']['raw']['message'] ?? ''));
                $timeline[] = ['state' => 'ngn_payout_failed', 'at' => now()->toIso8601String(), 'reason' => $reason];
                $transfer->update(['timeline' => $timeline]);
            }
        } elseif ($status === 'REJECTED') {
            $transfer->update([
                'payin_status' => 'failed',
                'status' => 'failed',
                'timeline' => $timeline,
            ]);
        } else {
            $transfer->update([
                'payin_status' => 'pending',
                'timeline' => $timeline,
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
