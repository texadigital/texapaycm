<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminSetting;
use App\Models\Quote;
use App\Models\Transfer;
use App\Services\OpenExchangeRates;
use App\Services\PricingEngine;
use App\Services\PawaPay;
use App\Services\SafeHaven;
use App\Services\LimitCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TransfersController extends Controller
{
    /**
     * GET /api/mobile/transfers
     * Query: status?, from?, to?, page?, perPage?
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $q = Transfer::query()->where('user_id', $userId)
            ->orderByDesc('id');

        if ($s = $request->query('status')) {
            $q->where('status', $s);
        }
        if ($from = $request->query('from')) {
            $q->where('created_at', '>=', Carbon::parse($from));
        }
        if ($to = $request->query('to')) {
            $q->where('created_at', '<=', Carbon::parse($to));
        }

        $per = min(max((int) $request->query('perPage', 15), 1), 100);
        $p = $q->paginate($per);

        $data = array_map(function ($t) {
            return [
                'id' => $t->id,
                'status' => $t->status,
                'amountXaf' => (int) $t->amount_xaf,
                'totalPayXaf' => (int) $t->total_pay_xaf,
                'receiveNgnMinor' => (int) $t->receive_ngn_minor,
                'bankCode' => $t->recipient_bank_code,
                'accountNumber' => $t->recipient_account_number,
                'accountName' => $t->recipient_account_name,
                'createdAt' => $t->created_at?->toISOString(),
            ];
        }, $p->items());

        return response()->json([
            'data' => $data,
            'meta' => [
                'page' => $p->currentPage(),
                'perPage' => $p->perPage(),
                'total' => $p->total(),
                'lastPage' => $p->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/mobile/transfers/{transfer}
     */
    public function show(Request $request, Transfer $transfer)
    {
        $this->authorizeOwner($transfer);
        return response()->json([
            'id' => $transfer->id,
            'status' => $transfer->status,
            'amountXaf' => (int) $transfer->amount_xaf,
            'totalPayXaf' => (int) $transfer->total_pay_xaf,
            'receiveNgnMinor' => (int) $transfer->receive_ngn_minor,
            'bankCode' => $transfer->recipient_bank_code,
            'bankName' => $transfer->recipient_bank_name,
            'accountNumber' => $transfer->recipient_account_number,
            'accountName' => $transfer->recipient_account_name,
            'createdAt' => $transfer->created_at?->toISOString(),
        ]);
    }
    /**
     * Simple idempotency wrapper using cache for mobile endpoints only.
     */
    protected function idempotent(Request $request, \Closure $fn)
    {
        $key = $request->header('Idempotency-Key');
        if (!$key) { return $fn(); }
        $userId = (int) Auth::id();
        $cacheKey = 'idem:mobile:' . $userId . ':' . $request->route()->getName() . ':' . sha1((string) $key);
        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }
        $resp = $fn();
        // Only cache JSON responses with success or deterministic error
        if ($resp instanceof \Illuminate\Http\JsonResponse) {
            $data = $resp->getData(true);
            Cache::put($cacheKey, $data, now()->addHours(6));
        }
        return $resp;
    }

    /**
     * POST /api/mobile/transfers/name-enquiry
     * Body: { bankCode: string, accountNumber: string }
     */
    public function nameEnquiry(Request $request, SafeHaven $safeHaven)
    {
        if (!(bool) AdminSetting::getValue('mobile_api_enabled', false)) {
            return response()->json(['success' => false, 'code' => 'FEATURE_DISABLED', 'message' => 'Mobile API is disabled'], 403);
        }
        $data = $request->validate([
            'bankCode' => ['required','string','max:32'],
            'accountNumber' => ['required','string','max:32'],
        ]);
        return $this->idempotent($request, function () use ($safeHaven, $data) {
            $res = $safeHaven->nameEnquiry($data['bankCode'], $data['accountNumber']);
            $ok = (bool) ($res['success'] ?? false);
            if (!$ok) {
                return response()->json([
                    'success' => false,
                    'code' => 'NAME_ENQUIRY_FAILED',
                    'message' => $res['raw']['friendlyMessage'] ?? ($res['raw']['message'] ?? 'Unable to verify account'),
                    'details' => ['raw' => $res['raw'] ?? null],
                ], 400);
            }
            return response()->json([
                'success' => true,
                'accountName' => $res['account_name'] ?? null,
                'bankName' => $res['bank_name'] ?? null,
                'reference' => $res['reference'] ?? null,
            ]);
        });
    }

    /**
     * POST /api/mobile/transfers/quote
     * Body: { amountXaf: number, bankCode: string, accountNumber: string, accountName?: string }
     * Mirrors pricing in TransferController::createQuote.
     */
    public function quote(Request $request, OpenExchangeRates $oxr)
    {
        if (!(bool) AdminSetting::getValue('mobile_api_enabled', false)) {
            return response()->json(['success' => false, 'code' => 'FEATURE_DISABLED', 'message' => 'Mobile API is disabled'], 403);
        }
        $data = $request->validate([
            'amountXaf' => ['required','numeric','min:1'],
            'bankCode' => ['required','string','max:32'],
            'accountNumber' => ['required','string','max:32'],
            'accountName' => ['nullable','string','max:190'],
        ]);
        // Ensure CheckUserLimits middleware sees amount_xaf
        $request->merge(['amount_xaf' => (int) round($data['amountXaf'])]);

        return $this->idempotent($request, function () use ($oxr, $data) {
            $fx = $oxr->fetchUsdRates();
            if (!$fx['usd_to_xaf'] || !$fx['usd_to_ngn']) {
                return response()->json([
                    'success' => false,
                    'code' => 'RATE_UNAVAILABLE',
                    'message' => 'Rate unavailable—please try again later.',
                    'details' => ['raw' => $fx['raw'] ?? null],
                ], 503);
            }
            $usdToXaf = (float) $fx['usd_to_xaf'];
            $usdToNgn = (float) $fx['usd_to_ngn'];
            $cross = $usdToNgn / $usdToXaf;
            $amountXaf = (int) round($data['amountXaf']);

            $pricingV2 = (bool) AdminSetting::getValue('pricing_v2.enabled', false);
            if ($pricingV2) {
                $engine = app(PricingEngine::class);
                $calc = $engine->price($amountXaf, $usdToXaf, $usdToNgn, [
                    'charge_mode' => env('FEES_CHARGE_MODE', 'on_top'),
                ]);
                $adjustedRate = (float) $calc['effective_rate'];
                $feeTotal = (int) $calc['fee_amount_xaf'];
                $totalPayXaf = (int) $calc['total_pay_xaf'];
                $receiveNgnMinor = (int) $calc['receive_ngn_minor'];
            } else {
                $marginBps = (int) (env('FX_MARGIN_BPS', 0));
                $adjustedRate = $cross * (1 - ($marginBps / 10000));
                $fixedFee = (int) (env('FEES_FIXED_XAF', 0));
                $percentBps = (int) (env('FEES_PERCENT_BPS', 0));
                $percentFee = (int) floor($amountXaf * $percentBps / 10000);
                $feeTotal = $fixedFee + $percentFee;
                $chargeOnTop = (env('FEES_CHARGE_MODE', 'on_top') === 'on_top');
                $totalPayXaf = $chargeOnTop ? ($amountXaf + $feeTotal) : $amountXaf;
                $effectiveSendXaf = $chargeOnTop ? $amountXaf : max($amountXaf - $feeTotal, 0);
                $receiveNgnMinor = (int) round($effectiveSendXaf * $adjustedRate * 100);
            }

            $ttl = (int) AdminSetting::getValue('pricing.quote_ttl_secs', (int) env('QUOTE_TTL_SECONDS', 90));
            $userId = Auth::id() ?? (int) (\App\Models\User::query()->min('id'));
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

            return response()->json([
                'success' => true,
                'quote' => [
                    'id' => $quote->id,
                    'ref' => $quote->quote_ref,
                    'amountXaf' => $quote->amount_xaf,
                    'feeTotalXaf' => $quote->fee_total_xaf,
                    'totalPayXaf' => $quote->total_pay_xaf,
                    'receiveNgnMinor' => $quote->receive_ngn_minor,
                    'adjustedRate' => $quote->adjusted_rate_xaf_to_ngn,
                    'expiresAt' => $quote->expires_at?->toISOString(),
                ],
            ]);
        });
    }

    /**
     * POST /api/mobile/transfers/confirm
     * Body: { quoteId: number, bankCode: string, bankName?: string, accountNumber: string, accountName?: string, msisdn: string }
     * Mirrors TransferController::confirmPayIn without web session.
     */
    public function confirm(Request $request, PawaPay $pawa, SafeHaven $safeHaven)
    {
        if (!(bool) AdminSetting::getValue('mobile_api_enabled', false)) {
            return response()->json(['success' => false, 'code' => 'FEATURE_DISABLED', 'message' => 'Mobile API is disabled'], 403);
        }
        $data = $request->validate([
            'quoteId' => ['required','integer','exists:quotes,id'],
            'bankCode' => ['required','string','max:32'],
            'accountNumber' => ['required','string','max:32'],
            'msisdn' => ['required','string','min:8','max:20'],
        ]);

        return $this->idempotent($request, function () use ($data, $pawa) {
            $quote = Quote::find($data['quoteId']);
            if (!$quote) {
                return response()->json(['success' => false, 'code' => 'QUOTE_NOT_FOUND', 'message' => 'Quote not found'], 404);
            }
            if (now()->greaterThan(Carbon::parse($quote->expires_at))) {
                $quote->update(['status' => 'expired']);
                return response()->json(['success' => false, 'code' => 'QUOTE_EXPIRED', 'message' => 'Quote expired—refresh to get a new rate.'], 400);
            }
            // Normalize MSISDN as in web flow
            $rawMsisdn = preg_replace('/\s+/', '', $data['msisdn']);
            $digits = preg_replace('/\D+/', '', $rawMsisdn ?? '');
            if (str_starts_with($digits, '00')) { $digits = substr($digits, 2); }
            if (strlen($digits) === 9 && str_starts_with($digits, '6')) { $digits = '237' . $digits; }
            if (!(strlen($digits) === 12 && str_starts_with($digits, '237'))) {
                return response()->json(['success' => false, 'code' => 'INVALID_MSISDN', 'message' => 'Please enter a valid Cameroon MoMo number (e.g., 2376XXXXXXXX).'], 400);
            }
            $msisdn = $digits;

            // Provider detection (same heuristic)
            $prefix3 = substr($msisdn, 3, 3);
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
                            for ($i=(int)$m[1]; $i<=(int)$m[2]; $i++) { $alts[] = sprintf('%03d', $i); }
                        } elseif (preg_match('/^\d{3}$/', $p)) { $alts[] = $p; }
                    }
                    $alts = array_values(array_unique($alts));
                    return $alts ? '^(' . implode('|', $alts) . ')$' : '';
                };
                $mtnRe = $rangeToAlternation($cfgMtn);
                $orgRe = $rangeToAlternation($cfgOrg);
                if ($mtnRe && preg_match('/' . $mtnRe . '/', $prefix3)) { $provider = $mtnCode; }
                elseif ($orgRe && preg_match('/' . $orgRe . '/', $prefix3)) { $provider = $orgCode; }
                else {
                    if (preg_match('/^(650|651|652|653|654|655|656|657|658|659|670|671|672|673|674|675|676|677|678|679|680|681|682|683|684|685|686|687|688|689)$/', $prefix3)) {
                        $provider = $mtnCode;
                    } elseif (preg_match('/^(690|691|692|693|694|695|696|697|698|699)$/', $prefix3)) {
                        $provider = $orgCode;
                    }
                }
            }

            // Resolve recipient bank name from bankCode (ignore client-provided bankName)
            $resolvedBankName = null;
            $banks = Cache::get('banks:safehaven:list', []);
            if (is_array($banks)) {
                foreach ($banks as $b) {
                    if (($b['bankCode'] ?? null) === $data['bankCode']) {
                        $resolvedBankName = $b['name'] ?? null;
                        break;
                    }
                }
            }
            if (!$resolvedBankName && $data['bankCode'] === '999240') {
                $resolvedBankName = 'SAFE HAVEN SANDBOX BANK';
            }

            // Attempt server-side name enquiry to derive account name and reference; fallback to UNKNOWN/null
            $resolvedAccountName = null;
            $resolvedNameRef = null;
            try {
                $ne = $safeHaven->nameEnquiry($data['bankCode'], $data['accountNumber']);
                $okNe = (bool) ($ne['success'] ?? false) || (($ne['statusCode'] ?? null) === 200);
                if ($okNe) {
                    $resolvedAccountName = $ne['account_name'] ?? ($ne['data']['accountName'] ?? null);
                    $resolvedNameRef = $ne['reference'] ?? ($ne['data']['sessionId'] ?? null);
                }
            } catch (\Throwable $e) {
                // ignore and fallback below
            }

            // Create or reuse transfer for quote
            $transfer = \Illuminate\Support\Facades\DB::transaction(function () use ($quote, $msisdn, $provider, $data, $resolvedBankName, $resolvedAccountName, $resolvedNameRef) {
                $existing = Transfer::where('quote_id', $quote->id)->first();
                if ($existing) { return $existing; }
                $userId = Auth::id() ?? (int) (\App\Models\User::query()->min('id'));
                return Transfer::create([
                    'user_id' => $userId,
                    'quote_id' => $quote->id,
                    'recipient_bank_code' => $data['bankCode'],
                    'recipient_bank_name' => $resolvedBankName ?: 'UNKNOWN',
                    'recipient_account_number' => $data['accountNumber'],
                    'recipient_account_name' => ($resolvedAccountName && trim($resolvedAccountName) !== '') ? $resolvedAccountName : 'UNKNOWN',
                    // Persist name enquiry ref when available to avoid JIT lookup at payout time
                    'name_enquiry_reference' => $resolvedNameRef,
                    'amount_xaf' => $quote->amount_xaf,
                    'fee_total_xaf' => $quote->fee_total_xaf,
                    'total_pay_xaf' => $quote->total_pay_xaf,
                    'receive_ngn_minor' => $quote->receive_ngn_minor,
                    'adjusted_rate_xaf_to_ngn' => $quote->adjusted_rate_xaf_to_ngn,
                    'usd_to_xaf' => $quote->usd_to_xaf,
                    'usd_to_ngn' => $quote->usd_to_ngn,
                    'fx_fetched_at' => $quote->fx_fetched_at,
                    'status' => 'payin_pending',
                    'msisdn' => $msisdn,
                    'provider' => $provider,
                    'timeline' => [
                        ['state' => 'quote_created', 'at' => now()->toIso8601String()],
                        ['state' => 'payin_pending', 'at' => now()->toIso8601String(), 'msisdn' => $msisdn, 'provider' => $provider],
                    ],
                ]);
            });

            $reference = (string) Str::uuid();
            $resp = $pawa->initiatePayIn([
                'amount_xaf_minor' => (int)($quote->total_pay_xaf * 100),
                'msisdn' => $msisdn,
                'currency' => 'XAF',
                'reference' => $reference,
                'provider' => $provider,
                'client_ref' => $quote->quote_ref ?? (string) Str::uuid(),
                'customer_message' => env('PAWAPAY_CUSTOMER_MESSAGE', 'TexaPay Payment'),
            ]);

            if (($resp['status'] ?? 'failed') === 'failed') {
                $errorMessage = $resp['raw']['message'] ?? 'Failed to initiate payment';
                $timeline = is_array($transfer->timeline) ? $transfer->timeline : [];
                $timeline[] = ['state' => 'payin_failed', 'at' => now()->toIso8601String(), 'reason' => $errorMessage, 'raw' => $resp];
                $transfer->update(['status' => 'failed', 'payin_status' => 'failed', 'timeline' => $timeline]);
                return response()->json([
                    'success' => false,
                    'code' => 'PAYIN_FAILED',
                    'message' => 'Failed to initiate payment: ' . $errorMessage,
                    'details' => ['raw' => $resp],
                ], 400);
            }

            $timeline = is_array($transfer->timeline) ? $transfer->timeline : [];
            $timeline[] = ['state' => 'payin_initiated', 'at' => now()->toIso8601String(), 'reference' => $reference, 'provider' => $provider];
            $transfer->update([
                'payin_ref' => $reference,
                'payin_status' => 'pending',
                'status' => 'payin_pending',
                'timeline' => $timeline,
            ]);

            return response()->json([
                'success' => true,
                'transfer' => [
                    'id' => $transfer->id,
                    'status' => $transfer->status,
                    'payinRef' => $transfer->payin_ref,
                ],
            ]);
        });
    }

    /**
     * GET /api/mobile/transfers/{transfer}/timeline
     */
    public function timeline(Request $request, Transfer $transfer)
    {
        $this->authorizeOwner($transfer);
        return response()->json([
            'success' => true,
            'timeline' => $transfer->timeline ?? [],
            'status' => $transfer->status,
            'payinStatus' => $transfer->payin_status,
            'payoutStatus' => $transfer->payout_status,
        ]);
    }

    /**
     * GET /api/mobile/transfers/{transfer}/receipt-url
     * Returns a temporary signed URL to the existing public receipt route.
     */
    public function receiptUrl(Request $request, Transfer $transfer)
    {
        $this->authorizeOwner($transfer);
        $url = URL::temporarySignedRoute('transfer.receipt.shared', now()->addMinutes(30), ['transfer' => $transfer->id]);
        return response()->json(['success' => true, 'url' => $url]);
    }

    protected function authorizeOwner(Transfer $transfer): void
    {
        $userId = Auth::id();
        abort_if($transfer->user_id !== $userId, 403);
    }
}
