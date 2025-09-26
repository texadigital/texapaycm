<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\Transfer;
use App\Services\OpenExchangeRates;
use App\Services\PawaPay;
use App\Services\SafeHaven;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Support\Str;

class TransferController extends Controller
{
    public function showBankForm(Request $request): View
    {
        $data = [
            'bank_code' => session('transfer.bank_code'),
            'bank_name' => session('transfer.bank_name'),
            'account_number' => session('transfer.account_number'),
            'account_name' => session('transfer.account_name'),
            'error' => session('transfer.error'),
        ];
        return view('transfer.bank', $data);
    }

    public function verifyBank(Request $request, SafeHaven $safeHaven): RedirectResponse
    {
        $validated = $request->validate([
            'bank_code' => ['required','string','max:32'],
            'account_number' => ['required','string','max:32'],
        ]);

        $enquiry = $safeHaven->nameEnquiry($validated['bank_code'], $validated['account_number']);
        $statusCode = $enquiry['raw']['statusCode'] ?? ($enquiry['raw']['status'] ?? null);
        $respCode = $enquiry['raw']['responseCode'] ?? ($enquiry['raw']['data']['responseCode'] ?? null);
        $ok = (bool) ($enquiry['success'] || $statusCode === 200 || $respCode === '00');
        if (!$ok) {
            $friendly = $enquiry['raw']['friendlyMessage'] ?? ($enquiry['raw']['message'] ?? null);
            $msg = $friendly ? (string) $friendly : 'We could not verify this account. Please check details or try again.';
            return back()->withInput()->with('transfer.error', $msg);
        }

        session([
            'transfer.bank_code' => $validated['bank_code'],
            'transfer.bank_name' => $enquiry['bank_name'] ?? ($validated['bank_code'] === '999240' ? 'SAFE HAVEN SANDBOX BANK' : 'Unknown Bank'),
            'transfer.account_number' => $validated['account_number'],
            'transfer.account_name' => $enquiry['account_name'] ?? 'Verified Account',
            'transfer.name_enquiry_reference' => $enquiry['reference'] ?? null,
        ]);

        return redirect()->route('transfer.quote');
    }

    public function showQuoteForm(Request $request): View
    {
        $bankCode = session('transfer.bank_code');
        $accountNumber = session('transfer.account_number');
        $accountName = session('transfer.account_name');
        $bankName = session('transfer.bank_name');

        abort_unless($bankCode && $accountNumber, 302, 'Please verify recipient first.');
        $quoteId = session('transfer.quote_id');
        $quote = $quoteId ? Quote::find($quoteId) : null;
        $remaining = null;
        if ($quote && $quote->expires_at) {
            // Compute seconds remaining until expiry. If expired, clear the quote from session.
            $remaining = max(0, now()->diffInSeconds(Carbon::parse($quote->expires_at), false));
            if ($remaining <= 0) {
                $quote->update(['status' => 'expired']);
                // Remove expired quote from session so user can create a fresh one immediately
                session()->forget('transfer.quote_id');
                $quote = null;
            }
        }

        return view('transfer.quote', compact('bankCode','accountNumber','accountName','bankName','quote','remaining'));
    }

    public function createQuote(Request $request, OpenExchangeRates $oxr): RedirectResponse
    {
        $validated = $request->validate([
            'amount_xaf' => ['required','numeric','min:1'],
        ]);

        // Fetch USD-base FX
        $fx = $oxr->fetchUsdRates();
        if (!$fx['usd_to_xaf'] || !$fx['usd_to_ngn']) {
            return back()->withInput()->with('error', 'Rate unavailable—please try again later.');
        }

        $usdToXaf = (float) $fx['usd_to_xaf'];
        $usdToNgn = (float) $fx['usd_to_ngn'];
        $cross = $usdToNgn / $usdToXaf; // XAF->NGN

        // Basic margin & fees from env for now (to be moved to Settings model)
        $marginBps = (int) (env('FX_MARGIN_BPS', 0));
        $adjustedRate = $cross * (1 - ($marginBps / 10000));

        $amountXaf = (int) round($validated['amount_xaf']);
        $fixedFee = (int) (env('FEES_FIXED_XAF', 0));
        $percentBps = (int) (env('FEES_PERCENT_BPS', 0));
        $percentFee = (int) floor($amountXaf * $percentBps / 10000);
        $feeTotal = $fixedFee + $percentFee;

        $chargeOnTop = (env('FEES_CHARGE_MODE', 'on_top') === 'on_top');
        $totalPayXaf = $chargeOnTop ? ($amountXaf + $feeTotal) : $amountXaf;
        $effectiveSendXaf = $chargeOnTop ? $amountXaf : max($amountXaf - $feeTotal, 0);

        $receiveNgnMinor = (int) round($effectiveSendXaf * $adjustedRate * 100);

        $ttl = (int) env('QUOTE_TTL_SECONDS', 90);
        $userId = auth()->id();
        if (!$userId) {
            $userId = \App\Models\User::query()->min('id');
        }
        $quote = Quote::create([
            'user_id' => $userId,
            'amount_xaf' => $amountXaf,
            'usd_to_xaf' => $usdToXaf,
            'usd_to_ngn' => $usdToNgn,
            'cross_rate_xaf_to_ngn' => $cross,
            'adjusted_rate_xaf_to_ngn' => $adjustedRate,
            'fee_total_xaf' => $feeTotal,
            'total_pay_xaf' => $totalPayXaf,
            'receive_ngn_minor' => $receiveNgnMinor,
            'status' => 'active',
            'quote_ref' => Str::uuid()->toString(),
            'expires_at' => now()->addSeconds($ttl),
            'fx_fetched_at' => $fx['fetched_at'],
        ]);

        session(['transfer.quote_id' => $quote->id]);
        return redirect()->route('transfer.quote')->with('quote_ready', true);
    }

    public function confirmPayIn(Request $request, PawaPay $pawa): RedirectResponse
    {
        $quoteId = session('transfer.quote_id');
        $quote = $quoteId ? Quote::find($quoteId) : null;
        if (!$quote) {
            return back()->with('error', 'Quote not found.');
        }

        if (now()->greaterThan(Carbon::parse($quote->expires_at))) {
            $quote->update(['status' => 'expired']);
            return back()->with('error', 'Quote expired—Refresh to get a new rate.');
        }

        // Minimal MSISDN capture for demo
        $validated = $request->validate([
            'msisdn' => ['required','string','min:8','max:20'],
        ]);
        // No OTP capture here; providers will handle authorisation out-of-band if needed.

        // Normalize MSISDN to E.164 for Cameroon and auto-detect provider
        $rawMsisdn = preg_replace('/\s+/', '', $validated['msisdn']);
        $digits = preg_replace('/\D+/', '', $rawMsisdn ?? '');
        if (str_starts_with($digits, '00')) { $digits = substr($digits, 2); }
        if (str_starts_with($rawMsisdn, '+')) { /* already handled by removing non-digits */ }
        if (strlen($digits) === 9 && str_starts_with($digits, '6')) {
            // Local CM format -> add country code
            $digits = '237' . $digits;
        }
        // Basic validation of CM E.164
        if (!(strlen($digits) === 12 && str_starts_with($digits, '237'))) {
            return back()->with('error', 'Please enter a valid Cameroon MoMo number in international format (e.g., 2376XXXXXXXX).');
        }
        $msisdn = $digits;

        // Provider detection for Cameroon via .env-configurable prefixes
        $prefix3 = substr($msisdn, 3, 3); // digits after 237
        $provider = env('PAWAPAY_PROVIDER', 'MTN_MOMO_CMR');
        $mtnCode = env('PAWAPAY_PROVIDER_MTN', 'MTN_MOMO_CMR');
        $orgCode = env('PAWAPAY_PROVIDER_ORANGE', 'ORANGE_CMR');
        $autoDetect = filter_var(env('PAWAPAY_AUTODETECT_PROVIDER', true), FILTER_VALIDATE_BOOLEAN);

        if ($autoDetect) {
            $cfgMtn = trim((string) env('PAWAPAY_CM_MTN_PREFIXES', '650-659,670-689'));
            $cfgOrg = trim((string) env('PAWAPAY_CM_ORANGE_PREFIXES', '690-699'));

            $rangeToAlternation = function (string $ranges): string {
                $parts = array_filter(array_map('trim', explode(',', $ranges)));
                $alts = [];
                foreach ($parts as $p) {
                    if (preg_match('/^(\d{3})-(\d{3})$/', $p, $m)) {
                        $start = (int) $m[1];
                        $end = (int) $m[2];
                        for ($i=$start; $i<=$end; $i++) { $alts[] = sprintf('%03d', $i); }
                    } elseif (preg_match('/^\d{3}$/', $p)) {
                        $alts[] = $p;
                    }
                }
                $alts = array_values(array_unique($alts));
                return $alts ? '^(' . implode('|', $alts) . ')$' : '';
            };

            $mtnRe = $rangeToAlternation($cfgMtn);
            $orgRe = $rangeToAlternation($cfgOrg);

            if ($mtnRe && preg_match('/' . $mtnRe . '/', $prefix3)) {
                $provider = $mtnCode;
            } elseif ($orgRe && preg_match('/' . $orgRe . '/', $prefix3)) {
                $provider = $orgCode;
            } else {
                // Fallback to default heuristics if config did not match
                if (preg_match('/^(650|651|652|653|654|655|656|657|658|659|670|671|672|673|674|675|676|677|678|679|680|681|682|683|684|685|686|687|688|689)$/', $prefix3)) {
                    $provider = $mtnCode;
                } elseif (preg_match('/^(690|691|692|693|694|695|696|697|698|699)$/', $prefix3)) {
                    $provider = $orgCode;
                }
            }
        }

        // Create Transfer record in quote_created
        $userId = auth()->id();
        if (!$userId) {
            $userId = \App\Models\User::query()->min('id');
        }
        $transfer = Transfer::create([
            'user_id' => $userId,
            'quote_id' => $quote->id,
            'recipient_bank_code' => session('transfer.bank_code'),
            'recipient_bank_name' => session('transfer.bank_name'),
            'recipient_account_number' => session('transfer.account_number'),
            'recipient_account_name' => session('transfer.account_name'),
            'name_enquiry_reference' => session('transfer.name_enquiry_reference'),
            'amount_xaf' => $quote->amount_xaf,
            'fee_total_xaf' => $quote->fee_total_xaf,
            'total_pay_xaf' => $quote->total_pay_xaf,
            'receive_ngn_minor' => $quote->receive_ngn_minor,
            'adjusted_rate_xaf_to_ngn' => $quote->adjusted_rate_xaf_to_ngn,
            'usd_to_xaf' => $quote->usd_to_xaf,
            'usd_to_ngn' => $quote->usd_to_ngn,
            'fx_fetched_at' => $quote->fx_fetched_at,
            'status' => 'payin_pending',
            'timeline' => [
                ['state' => 'quote_created', 'at' => now()->toIso8601String()],
                ['state' => 'payin_pending', 'at' => now()->toIso8601String()],
            ],
        ]);

        // Initiate pawaPay
        $resp = $pawa->initiatePayIn([
            'amount_xaf_minor' => $quote->total_pay_xaf,
            'msisdn' => $msisdn,
            'currency' => 'XAF',
            'reference' => (string) Str::uuid(),
            'callback_url' => env('PAWAPAY_CALLBACK_URL', url('/webhooks/pawapay')),
            'provider' => $provider,
            'client_ref' => $quote->quote_ref ?? (string) Str::uuid(),
            'customer_message' => env('PAWAPAY_CUSTOMER_MESSAGE', 'TexaPay Payment'),
        ]);

        $transfer->update([
            'payin_ref' => $resp['ref'],
            'payin_status' => $resp['status'],
        ]);

        if ($resp['status'] === 'failed') {
            $timeline = is_array($transfer->timeline) ? $transfer->timeline : [];
            $timeline[] = ['state' => 'payin_failed', 'at' => now()->toIso8601String(), 'reason' => $resp['raw']['message'] ?? ($resp['raw']['error'] ?? 'Unknown')];
            $transfer->update(['status' => 'failed', 'timeline' => $timeline]);
            $reason = $resp['raw']['message'] ?? ($resp['raw']['error'] ?? 'MoMo payment failed.');
            \Log::warning('PawaPay deposit failed', [
                'transfer_id' => $transfer->id,
                'http_status' => $resp['raw']['http_status'] ?? null,
                'provider' => $provider,
                'msisdn' => substr($msisdn,0,5) . '…' . substr($msisdn,-2),
                'raw' => $resp['raw'] ?? null,
            ]);
            return redirect()
                ->route('transfer.receipt', $transfer)
                ->with('error', 'MoMo payment failed: ' . $reason)
                ->with('error_details', json_encode($resp['raw'] ?? [], JSON_PRETTY_PRINT));
        }

        // For now, treat non-failed as success to allow proceeding to payout in later step
        return redirect()->route('transfer.receipt', $transfer)->with('info', 'Payment pending. This is a demo step.');
    }

    public function showReceipt(Request $request, Transfer $transfer): View
    {
        return view('transfer.receipt', compact('transfer'));
    }

    public function initiatePayout(Request $request, Transfer $transfer, SafeHaven $safeHaven): RedirectResponse
    {
        // In a real flow, this would be triggered automatically on pay-in success or via webhook.
        if (in_array($transfer->status, ['payout_success', 'failed'])) {
            return back()->with('info', 'Transfer already completed.');
        }

        $payload = [
            'amount_ngn_minor' => $transfer->receive_ngn_minor,
            'bank_code' => $transfer->recipient_bank_code,
            'account_number' => $transfer->recipient_account_number,
            'account_name' => $transfer->recipient_account_name,
            'narration' => 'TexaPay transfer ' . $transfer->id,
            'reference' => (string) Str::uuid(),
            'name_enquiry_reference' => session('transfer.name_enquiry_reference'),
            'debit_account_number' => env('SAFEHAVEN_DEBIT_ACCOUNT_NUMBER'),
        ];

        $resp = $safeHaven->payout($payload);

        $timeline = is_array($transfer->timeline) ? $transfer->timeline : [];
        $timeline[] = ['state' => 'ngn_payout_initiated', 'at' => now()->toIso8601String()];

        $transfer->update([
            'payout_ref' => $resp['ref'],
            'payout_status' => $resp['status'],
            'payout_initiated_at' => now(),
            'status' => $resp['status'] === 'success' ? 'payout_success' : ($resp['status'] === 'failed' ? 'failed' : 'payout_pending'),
            'timeline' => $timeline,
        ]);

        if ($resp['status'] === 'success') {
            $transfer->update([
                'payout_completed_at' => now(),
            ]);
            $timeline[] = ['state' => 'ngn_payout_success', 'at' => now()->toIso8601String()];
            $transfer->update(['timeline' => $timeline]);
            return redirect()->route('transfer.receipt', $transfer)->with('info', 'NGN payout completed.');
        }

        if ($resp['status'] === 'failed') {
            return redirect()->route('transfer.receipt', $transfer)->with('error', 'NGN payout failed.');
        }

        return redirect()->route('transfer.receipt', $transfer)->with('info', 'NGN payout pending.');
    }

    public function payoutStatus(Request $request, Transfer $transfer, SafeHaven $safeHaven): RedirectResponse
    {
        if (!$transfer->payout_ref && !$transfer->name_enquiry_reference) {
            return back()->with('error', 'No payout session to check.');
        }
        $sessionId = $transfer->payout_ref ?: $transfer->name_enquiry_reference;
        $resp = $safeHaven->payoutStatus($sessionId);
        $timeline = is_array($transfer->timeline) ? $transfer->timeline : [];
        $timeline[] = ['state' => 'ngn_payout_status_check', 'at' => now()->toIso8601String(), 'status' => $resp['status']];

        $update = [
            'timeline' => $timeline,
        ];
        if ($resp['status'] === 'success') {
            $update['status'] = 'payout_success';
            $update['payout_status'] = 'success';
            $update['payout_completed_at'] = now();
        } elseif ($resp['status'] === 'failed') {
            $update['status'] = 'failed';
            $update['payout_status'] = 'failed';
        } else {
            $update['payout_status'] = 'pending';
        }
        $transfer->update($update);
        return redirect()->route('transfer.receipt', $transfer)->with('info', 'Payout status: ' . $resp['status']);
    }
}
