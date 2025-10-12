<?php

namespace App\Http\Middleware;

use App\Models\AdminSetting;
use App\Models\EddCase;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckAml
{
    /**
     * Handle an incoming request.
     * Blocks transactions when an open EDD case exists or when EDD triggers fire.
     *
     * Routes covered: transfer quote/create (web) and mobile transfers quote/confirm (api).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $routeName = $request->route() ? (string) $request->route()->getName() : '';
        $guardedRoutes = [
            // Web
            'transfer.quote.create',
            'transfer.confirm',
            // Mobile API
            'api.mobile.transfers.quote',
            'api.mobile.transfers.confirm',
        ];
        if (!in_array($routeName, $guardedRoutes, true)) {
            return $next($request);
        }

        try {
            $user = Auth::user();

            // 0) CFT: Block based on FATF list and terror keywords
            try {
                // FATF grey/black country block (by user country
                $fatfCsv = (string) AdminSetting::getValue('aml.cft.fatf_block_countries', '');
                if ($fatfCsv !== '') {
                    $fatf = array_filter(array_map('strtoupper', array_map('trim', explode(',', $fatfCsv))));
                    $userCountry = strtoupper((string) ($user->country ?? ''));
                    if ($userCountry !== '' && in_array($userCountry, $fatf, true)) {
                        // Email compliance
                        try { app(\App\Services\ComplianceAlertService::class)->send('CFT Block: FATF Country', ['user_id' => $user->id, 'country' => $userCountry]); } catch (\Throwable $e) {}
                        return $this->blocked($request, 'Transactions are blocked from your jurisdiction pending compliance review.', [
                            'blocked_reason' => 'fatf_country_block',
                            'country' => $userCountry,
                        ]);
                    }
                }

                // Terrorism-related keyword screening in free-text fields
                $kwCsv = (string) AdminSetting::getValue('aml.cft.terror_keywords', '');
                if ($kwCsv !== '') {
                    $keywords = array_filter(array_map('strtolower', array_map('trim', explode(',', $kwCsv))));
                    // Common fields where narration may appear
                    $text = strtolower((string) ($request->input('narration')
                        ?? $request->input('note')
                        ?? $request->input('description')
                        ?? ''));
                    foreach ($keywords as $kw) {
                        if ($kw !== '' && str_contains($text, $kw)) {
                            // Email compliance
                            try { app(\App\Services\ComplianceAlertService::class)->send('CFT Block: Keyword', ['user_id' => $user->id, 'keyword' => $kw]); } catch (\Throwable $e) {}
                            return $this->blocked($request, 'Transaction contains restricted terms and requires compliance review.', [
                                'blocked_reason' => 'cft_keyword',
                                'keyword' => $kw,
                            ]);
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('CFT pre-check failed', ['error' => $e->getMessage()]);
            }

            // 1) Block only if EDD case is marked blocking or global toggle forces block-on-open
            $openCase = EddCase::query()
                ->where('user_id', $user->id)
                ->whereIn('status', ['open','pending_docs','review'])
                ->latest('id')
                ->first();

            if ($openCase) {
                $blockOnOpen = (bool) AdminSetting::getValue('aml.edd.block_on_open', false);
                $metaBlock = (bool) data_get($openCase->metadata, 'block', false);
                if ($blockOnOpen || $metaBlock) {
                    return $this->blocked($request, 'Transaction requires Enhanced Due Diligence review.', [
                        'blocked_reason' => 'edd_pending',
                        'edd_case_id' => $openCase->id,
                    ]);
                }
            }

            // 2) Evaluate simple EDD triggers (v1): high amount + low KYC
            $amountXaf = $this->getTransactionAmount($request);
            if ($amountXaf > 0) {
                $perTxTrigger = (int) AdminSetting::getValue('aml.edd.per_tx_trigger_xaf', 500000);
                $kycLevel = (int) ($user->kyc_level ?? 0);
                $kycStatus = (string) ($user->kyc_status ?? 'unverified');

                $highAmount = $amountXaf >= $perTxTrigger;
                $lowKyc = ($kycLevel < 1) || ($kycStatus !== 'verified');

                if ($highAmount && $lowKyc) {
                    // Auto-open an EDD case if none exists recently
                    $case = EddCase::create([
                        'user_id' => $user->id,
                        'case_ref' => 'EDD-' . now()->format('YmdHis') . '-' . $user->id,
                        'risk_reason' => 'High amount with low KYC',
                        'trigger_source' => 'middleware',
                        'status' => 'open',
                        'metadata' => [
                            'amount_xaf' => $amountXaf,
                            'kyc_level' => $kycLevel,
                            'kyc_status' => $kycStatus,
                            'route' => $routeName,
                        ],
                    ]);

                    Log::warning('AML: EDD triggered and case opened', [
                        'user_id' => $user->id,
                        'edd_case_id' => $case->id,
                        'amount_xaf' => $amountXaf,
                    ]);

                    // Email compliance
                    try {
                        app(\App\Services\ComplianceAlertService::class)->send('EDD Case Opened', [
                            'edd_case_id' => $case->id,
                            'user_id' => $user->id,
                            'reason' => 'High amount with low KYC',
                            'amount_xaf' => $amountXaf,
                        ]);
                    } catch (\Throwable $e) {}

                    return $this->blocked($request, 'Transaction blocked pending Enhanced Due Diligence.', [
                        'blocked_reason' => 'edd_required',
                        'edd_case_id' => $case->id,
                        'trigger' => 'high_amount_low_kyc',
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('CheckAml middleware error', [
                'error' => $e->getMessage(),
            ]);
            // Do not block on middleware errors
        }

        return $next($request);
    }

    protected function getTransactionAmount(Request $request): int
    {
        if ($request->has('amount_xaf')) {
            return (int) $request->input('amount_xaf');
        }
        $routeName = $request->route() ? (string) $request->route()->getName() : '';
        if (in_array($routeName, ['transfer.confirm','api.mobile.transfers.confirm'], true)) {
            $quoteId = (int) ($request->input('quoteId') ?: session('transfer.quote_id'));
            if ($quoteId) {
                $quote = \App\Models\Quote::find($quoteId);
                if ($quote) {
                    return (int) $quote->amount_xaf;
                }
            }
        }
        return 0;
    }

    protected function blocked(Request $request, string $message, array $data)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(array_merge([
                'success' => false,
                'message' => $message,
            ], $data), 400);
        }

        return redirect()->back()
            ->withInput()
            ->with('error', $message)
            ->with('aml_block', $data);
    }
}
