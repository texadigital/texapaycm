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
use App\Services\RefundService;
use App\Services\LimitCheckService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
     * GET /api/mobile/transactions/feed
     * Returns a month-grouped transaction feed with in/out totals and labeled rows.
     * This is presentation-ready for the mobile/PWA UI and does not introduce wallet logic.
     * Query: page?, perPage? (pagination over transfers before grouping)
     */
    public function feed(Request $request)
    {
        $userId = Auth::id();
        $q = Transfer::query()->where('user_id', $userId)->orderByDesc('created_at');

        $per = min(max((int) $request->query('perPage', 50), 1), 200);
        $p = $q->paginate($per);

        $months = [];

        $humanStatus = function(string $status): string {
            return match($status) {
                'payout_success', 'payin_success' => 'Successful',
                'payin_pending', 'payout_pending', 'processing', 'pending' => 'Pending',
                'failed', 'payout_failed', 'payin_failed' => 'Failed',
                default => ucfirst(str_replace('_', ' ', $status)),
            };
        };

        foreach ($p->items() as $t) {
            $monthKey = Carbon::parse($t->created_at)->format('Y-m');
            $monthLabel = Carbon::parse($t->created_at)->format('MMM Y');
            if (!isset($months[$monthKey])) {
                $months[$monthKey] = [
                    'key' => $monthKey,
                    'label' => $monthLabel,
                    'items' => [],
                    'totals' => [
                        'inMinor' => 0,
                        'outMinor' => 0,
                        'currency' => 'NGN',
                    ],
                ];
            }

            $transferRow = [
                'id' => 't-' . $t->id,
                'transferId' => (int) $t->id,
                'kind' => 'transfer',
                'direction' => 'out',
                'label' => 'Transfer to ' . ($t->recipient_account_name ?: 'UNKNOWN'),
                'at' => Carbon::parse($t->created_at)->toIso8601String(),
                'status' => (string) $t->status,
                'statusLabel' => $humanStatus((string) $t->status),
                'currency' => 'NGN',
                'amountMinor' => (int) ($t->receive_ngn_minor ?? 0),
                'sign' => -1,
                'meta' => [
                    'bankName' => $t->recipient_bank_name,
                    'bankCode' => $t->recipient_bank_code,
                    'accountNumber' => $t->recipient_account_number,
                ],
            ];
            $months[$monthKey]['items'][] = $transferRow;
            $months[$monthKey]['totals']['outMinor'] += (int) ($t->receive_ngn_minor ?? 0);

            $fee = (int) ($t->fee_total_xaf ?? 0);
            if ($fee > 0) {
                $months[$monthKey]['items'][] = [
                    'id' => 'fee-' . $t->id,
                    'transferId' => (int) $t->id,
                    'kind' => 'fee',
                    'direction' => 'out',
                    'label' => 'Electronic Money Transfer Levy',
                    'at' => Carbon::parse($t->created_at)->toIso8601String(),
                    'status' => (string) $t->status,
                    'statusLabel' => $humanStatus((string) $t->status),
                    'currency' => 'XAF',
                    'amountMinor' => $fee * 100,
                    'sign' => -1,
                ];
            }
        }

        krsort($months);
        $monthsArr = array_values($months);

        return response()->json([
            'months' => $monthsArr,
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
        // Compute human-readable FX rate display
        $adjusted = (float) ($transfer->adjusted_rate_xaf_to_ngn ?? 0);
        // Display as 1 XAF = NGN {rate}
        $rateDisplay = $adjusted > 0
            ? sprintf("1 XAF = NGN %s", number_format($adjusted, 2))
            : null;

        // Determine a suitable session identifier for bank workflow
        $sessionId = $transfer->payout_ref
            ?: ($transfer->name_enquiry_reference ?: $transfer->payin_ref);

        // Dynamic receipt branding/footer from admin settings or env
        $receiptFooter = AdminSetting::getValue('receipt.footer_text', env('RECEIPT_FOOTER_TEXT'));
        $receiptWatermarkUrl = AdminSetting::getValue('receipt.watermark_url', env('RECEIPT_WATERMARK_URL'));

        return response()->json([
            'id' => $transfer->id,
            'status' => $transfer->status,
            'amountXaf' => (int) $transfer->amount_xaf,
            'feeTotalXaf' => (int) ($transfer->fee_total_xaf ?? 0),
            'totalPayXaf' => (int) $transfer->total_pay_xaf,
            'receiveNgnMinor' => (int) $transfer->receive_ngn_minor,
            'adjustedRate' => $transfer->adjusted_rate_xaf_to_ngn,
            'rateDisplay' => $rateDisplay,
            'recipientGetsMinor' => (int) $transfer->receive_ngn_minor,
            'recipientGetsCurrency' => 'NGN',
            'sourceCurrency' => 'XAF',
            'targetCurrency' => 'NGN',
            'bankCode' => $transfer->recipient_bank_code,
            'bankName' => $transfer->recipient_bank_name,
            'accountNumber' => $transfer->recipient_account_number,
            'accountName' => $transfer->recipient_account_name,
            'payerMsisdn' => $transfer->msisdn ?? null,
            'payerName' => optional($transfer->user)->full_name ?: optional($transfer->user)->name,
            'payinAt' => $transfer->payin_at?->toISOString(),
            'payoutInitiatedAt' => $transfer->payout_initiated_at?->toISOString(),
            'payoutAttemptedAt' => $transfer->payout_attempted_at?->toISOString(),
            'payoutCompletedAt' => $transfer->payout_completed_at?->toISOString(),
            'payinRef' => $transfer->payin_ref,
            'payoutRef' => $transfer->payout_ref,
            'nameEnquiryRef' => $transfer->name_enquiry_reference,
            'transactionNo' => (string) $transfer->id,
            'sessionId' => $sessionId,
            'lastPayoutError' => $transfer->last_payout_error,
            'createdAt' => $transfer->created_at?->toISOString(),
            // Share receipt extras
            'receiptFooterText' => $receiptFooter,
            'receiptWatermarkUrl' => $receiptWatermarkUrl,
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
            // Mask account for logs
            $acct = (string) $data['accountNumber'];
            $masked = strlen($acct) > 4 ? str_repeat('•', max(0, strlen($acct) - 4)) . substr($acct, -4) : '••••';
            \Log::info('mobile.name_enquiry', [
                'u' => (int) (\Illuminate\Support\Facades\Auth::id() ?: 0),
                'bank' => $data['bankCode'],
                'acct' => $masked,
                'ok' => $ok,
                'ref' => $res['reference'] ?? null,
                'code' => $res['raw']['responseCode'] ?? null,
            ]);
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
            // Components for UI transparency (best-effort; fields may be null under pricing v2)
            $components = [
                'fxMarginBps' => null,
                'percentBps' => null,
                'fixedFeeXaf' => null,
                'percentFeeXaf' => null,
                'levyXaf' => null,
                'totalFeeXaf' => null,
            ];
            if ($pricingV2) {
                $engine = app(PricingEngine::class);
                $calc = $engine->price($amountXaf, $usdToXaf, $usdToNgn, [
                    'charge_mode' => env('FEES_CHARGE_MODE', 'on_top'),
                ]);
                $adjustedRate = (float) $calc['effective_rate'];
                $feeTotal = (int) $calc['fee_amount_xaf'];
                $totalPayXaf = (int) $calc['total_pay_xaf'];
                $receiveNgnMinor = (int) $calc['receive_ngn_minor'];
                // Surface known knobs to UI if available in admin settings
                $components['fxMarginBps'] = (int) AdminSetting::getValue('pricing.fx_margin_bps', (int) env('FX_MARGIN_BPS', 0));
                $components['totalFeeXaf'] = $feeTotal;
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
                // Fill components for legacy path
                $components['fxMarginBps'] = $marginBps;
                $components['percentBps'] = $percentBps;
                $components['fixedFeeXaf'] = $fixedFee;
                $components['percentFeeXaf'] = $percentFee;
                $components['totalFeeXaf'] = $feeTotal;
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
                    'ttlSeconds' => max(0, (int) now()->diffInSeconds($quote->expires_at, false)),
                    'rateDisplay' => $quote->adjusted_rate_xaf_to_ngn ? sprintf("1 XAF = NGN %s", number_format($quote->adjusted_rate_xaf_to_ngn, 2)) : null,
                    'components' => $components,
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
            // Normalize phone number using PhoneNumberService
            $phone = \App\Services\PhoneNumberService::normalize($data['msisdn']);
            
            // Validate Cameroon phone number
            $validation = \App\Services\PhoneNumberService::validateCameroon($phone);
            if (!$validation['valid']) {
                return response()->json(['success' => false, 'code' => 'INVALID_MSISDN', 'message' => $validation['error']], 400);
            }
            
            $msisdn = $validation['normalized'];

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

    /**
     * POST /api/mobile/transfers/payin/status
     * Poll pay-in status on PawaPay and update timeline. If success, set payout_pending.
     */
    public function payinStatus(Request $request, Transfer $transfer, PawaPay $pawa, SafeHaven $safeHaven)
    {
        $this->authorizeOwner($transfer);
        if (!$transfer->payin_ref) {
            return response()->json(['success' => false, 'message' => 'No pay-in reference to check.'], 400);
        }

        $statusResp = $pawa->getPayInStatus($transfer->payin_ref);
        $status = $statusResp['status'] ?? 'pending';
        $timeline = is_array($transfer->timeline) ? $transfer->timeline : [];
        $timeline[] = [
            'state' => 'payin_status_check',
            'at' => now()->toIso8601String(),
            'status' => $status,
            'raw' => $statusResp['raw'] ?? null,
        ];

        if ($status === 'success') {
            $transfer->update([
                'payin_status' => 'success',
                'status' => 'payout_pending',
                'payin_at' => now(),
                'timeline' => $timeline,
            ]);
            return response()->json(['success' => true, 'status' => 'success', 'message' => 'Pay-in completed. Payout pending.']);
        } elseif ($status === 'failed') {
            $reason = $statusResp['raw']['message'] ?? 'Payment failed';
            $timeline[] = [
                'state' => 'payin_failed',
                'at' => now()->toIso8601String(),
                'reason' => $reason,
            ];
            $transfer->update([
                'payin_status' => 'failed',
                'status' => 'failed',
                'timeline' => $timeline,
            ]);
            return response()->json(['success' => false, 'status' => 'failed', 'message' => $reason], 400);
        }

        // still pending
        $transfer->update([
            'payin_status' => 'pending',
            'status' => 'payin_pending',
            'timeline' => $timeline,
        ]);
        return response()->json(['success' => true, 'status' => 'pending']);
    }

    /**
     * POST /api/mobile/transfers/payout
     * Initiate NGN payout using SafeHaven. Mirrors web initiatePayout but JSON-only.
     */
    public function initiatePayout(Request $request, Transfer $transfer, SafeHaven $safeHaven, RefundService $refundService = null)
    {
        $this->authorizeOwner($transfer);
        $refundService = $refundService ?? app(RefundService::class);

        return DB::transaction(function () use ($request, $transfer, $safeHaven, $refundService) {
            // lock the row
            $transfer = Transfer::lockForUpdate()->findOrFail($transfer->id);

            // Precondition: only allow payout once pay-in is confirmed successful
            if ($transfer->payin_status !== 'success') {
                return response()->json([
                    'status' => 'blocked',
                    'message' => 'Payout cannot start until pay-in is successful.',
                    'payin_status' => $transfer->payin_status,
                ], 409);
            }

            if ($transfer->payout_status === 'success') {
                return response()->json([
                    'status' => 'already_processed',
                    'message' => 'Payout already completed successfully',
                    'transfer_id' => $transfer->id,
                    'payout_ref' => $transfer->payout_ref
                ]);
            }

            if ($transfer->payout_status === 'processing' && $transfer->payout_attempted_at > now()->subMinutes(15)) {
                return response()->json([
                    'status' => 'in_progress',
                    'message' => 'Payout is already being processed',
                    'transfer_id' => $transfer->id,
                    'last_attempt' => $transfer->payout_attempted_at
                ]);
            }

            $idempotencyKey = $transfer->payout_idempotency_key ?? (string) Str::uuid();
            $transfer->update([
                'payout_idempotency_key' => $idempotencyKey,
                'payout_status' => 'processing',
                'payout_attempted_at' => now(),
                'last_payout_error' => null,
                'payout_initiated_at' => now()
            ]);

            try {
                $payload = [
                    'amount_ngn_minor' => $transfer->receive_ngn_minor,
                    'bank_code' => $transfer->recipient_bank_code,
                    'account_number' => $transfer->recipient_account_number,
                    'account_name' => $transfer->recipient_account_name,
                    'narration' => 'TexaPay transfer ' . $transfer->id,
                    'reference' => $idempotencyKey,
                    'name_enquiry_reference' => $transfer->name_enquiry_reference,
                    'debit_account_number' => env('SAFEHAVEN_DEBIT_ACCOUNT_NUMBER'),
                ];

                $resp = $safeHaven->payout($payload);

                $timeline = is_array($transfer->timeline) ? $transfer->timeline : [];
                $timeline[] = [
                    'state' => 'ngn_payout_initiated',
                    'at' => now()->toIso8601String(),
                    'reference' => $idempotencyKey,
                    'provider_response' => $resp['raw'] ?? null
                ];

                $payoutStatus = $resp['status'] ?? 'failed';
                $newStatus = match($payoutStatus) {
                    'success' => 'payout_success',
                    'failed' => 'failed',
                    default => 'payout_pending'
                };

                $updateData = [
                    'payout_ref' => $resp['ref'] ?? $transfer->payout_ref,
                    'payout_status' => $payoutStatus,
                    'status' => $newStatus,
                    'timeline' => $timeline,
                    'payout_attempted_at' => now()
                ];

                if ($payoutStatus === 'success') {
                    $updateData['payout_completed_at'] = now();
                    $timeline[] = [
                        'state' => 'ngn_payout_success',
                        'at' => now()->toIso8601String(),
                        'reference' => $resp['ref'] ?? null
                    ];
                    $updateData['timeline'] = $timeline;
                    app(LimitCheckService::class)->recordTransaction($transfer->user, $transfer->amount_xaf, true);
                } elseif ($payoutStatus === 'failed') {
                    $errorMsg = $resp['raw']['message'] ?? 'Unknown error';
                    $updateData['last_payout_error'] = $errorMsg;
                    $timeline[] = [
                        'state' => 'ngn_payout_failed',
                        'at' => now()->toIso8601String(),
                        'error' => $errorMsg,
                        'response' => $resp['raw'] ?? null
                    ];
                    $updateData['timeline'] = $timeline;
                    $updateData['payout_status'] = 'failed';
                    $updateData['status'] = 'failed';
                    $transfer->update($updateData);
                    if ($transfer->payin_status === 'success') {
                        // fire refund asynchronously path via RefundService
                        app()->call([$refundService, 'refundFailedPayout'], ['transfer' => $transfer->fresh()]);
                    }
                    return response()->json([
                        'status' => 'error',
                        'message' => 'NGN payout failed: ' . $errorMsg,
                        'transfer_id' => $transfer->id,
                    ], 400);
                }

                $transfer->update($updateData);
                return match($payoutStatus) {
                    'success' => response()->json([
                        'status' => 'success',
                        'message' => 'NGN payout completed successfully.',
                        'transfer_id' => $transfer->id,
                        'payout_ref' => $transfer->payout_ref
                    ]),
                    'failed' => response()->json([
                        'status' => 'error',
                        'message' => 'NGN payout failed: ' . ($resp['raw']['message'] ?? 'Unknown error'),
                        'transfer_id' => $transfer->id,
                        'error_details' => $resp['raw'] ?? null
                    ], 400),
                    default => response()->json([
                        'status' => 'pending',
                        'message' => 'NGN payout is being processed.',
                        'transfer_id' => $transfer->id,
                        'payout_ref' => $transfer->payout_ref
                    ])
                };
            } catch (\Exception $e) {
                $transfer->update([
                    'payout_status' => 'failed',
                    'last_payout_error' => $e->getMessage(),
                    'payout_attempted_at' => now(),
                    'timeline' => array_merge($transfer->timeline ?? [], [[
                        'state' => 'ngn_payout_error',
                        'at' => now()->toIso8601String(),
                        'error' => $e->getMessage(),
                    ]])
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payout processing failed: ' . $e->getMessage(),
                    'transfer_id' => $transfer->id,
                ], 500);
            }
        });
    }

    /**
     * POST /api/mobile/transfers/payout/status
     */
    public function payoutStatus(Request $request, Transfer $transfer, SafeHaven $safeHaven)
    {
        $this->authorizeOwner($transfer);
        if (!$transfer->payout_ref && !$transfer->name_enquiry_reference) {
            return response()->json(['success' => false, 'message' => 'No payout session to check.'], 400);
        }
        $sessionId = $transfer->payout_ref ?: $transfer->name_enquiry_reference;
        $resp = $safeHaven->payoutStatus($sessionId);
        $timeline = is_array($transfer->timeline) ? $transfer->timeline : [];
        $timeline[] = [
            'state' => 'ngn_payout_status_check',
            'at' => now()->toIso8601String(),
            'status' => $resp['status']
        ];
        $update = ['timeline' => $timeline];
        if ($resp['status'] === 'success') {
            $update['status'] = 'payout_success';
            $update['payout_status'] = 'success';
            $update['payout_completed_at'] = now();
            app(LimitCheckService::class)->recordCompletedTransferOnce($transfer);
        } elseif ($resp['status'] === 'failed') {
            $update['status'] = 'failed';
            $update['payout_status'] = 'failed';
        } else {
            $update['payout_status'] = 'pending';
        }
        $transfer->update($update);
        return response()->json([
            'status' => $resp['status'],
            'transfer_status' => $update['status'] ?? $transfer->status,
        ]);
    }

    /**
     * GET /api/mobile/transfers/{transfer}/receipt.pdf
     * For API, return a signed URL to the PDF route used by web.
     */
    public function receiptPdf(Request $request, Transfer $transfer)
    {
        $this->authorizeOwner($transfer);
        $enabled = filter_var(env('FEATURE_ENABLE_RECEIPT_PDF', true), FILTER_VALIDATE_BOOLEAN);
        if (!$enabled) { return response()->json(['success' => false, 'message' => 'PDF disabled'], 404); }
        $url = URL::temporarySignedRoute('transfer.receipt.pdf', now()->addMinutes(10), ['transfer' => $transfer->id]);
        return response()->json(['success' => true, 'url' => $url, 'expires_at' => now()->addMinutes(10)->toIso8601String()]);
    }

    /**
     * POST /api/mobile/transfers/{transfer}/share-url
     * Generate a signed, time-limited share URL to the public receipt.
     */
    public function shareLink(Request $request, Transfer $transfer)
    {
        $this->authorizeOwner($transfer);
        $enabled = filter_var(env('FEATURE_ENABLE_RECEIPT_SHARE', true), FILTER_VALIDATE_BOOLEAN);
        if (!$enabled) { return response()->json(['success' => false, 'message' => 'Share feature disabled'], 404); }
        $days = (int) env('RECEIPT_SHARE_TTL_DAYS', 7);
        $expires = now()->addDays(max(1, $days));
        $url = URL::temporarySignedRoute('transfer.receipt.shared', $expires, ['transfer' => $transfer->id]);
        return response()->json(['success' => true, 'url' => $url, 'expires_at' => $expires->toIso8601String()]);
    }

    protected function authorizeOwner(Transfer $transfer): void
    {
        $userId = Auth::id();
        abort_if($transfer->user_id !== $userId, 403);
    }
}
