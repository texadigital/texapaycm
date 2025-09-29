<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\Transfer;
use App\Services\OpenExchangeRates;
use App\Services\PawaPay;
use App\Services\SafeHaven;
use App\Services\RefundService;
use App\Services\LimitCheckService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

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

    /**
     * Poll pay-in (deposit) status from PawaPay and update the transfer accordingly.
     * If completed, automatically initiate payout to the recipient.
     */
    public function payinStatus(Request $request, Transfer $transfer, \App\Services\PawaPay $pawa, SafeHaven $safeHaven)
    {
        // Must have a payin_ref (depositId)
        if (!$transfer->payin_ref) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'No pay-in reference to check.'], 400);
            }
            return back()->with('error', 'No pay-in reference to check.');
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
            // Mark payin completed and proceed to payout
            $transfer->update([
                'payin_status' => 'success',
                'status' => 'payout_pending',
                'payin_at' => now(),
                'timeline' => $timeline,
            ]);

            // Reuse payout initiation flow
            return $this->initiatePayout($request, $transfer, $safeHaven);
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

            return redirect()->route('transfer.receipt', $transfer)->with('error', $reason);
        }

        // Still pending
        $transfer->update([
            'payin_status' => 'pending',
            'status' => 'payin_pending',
            'timeline' => $timeline,
        ]);
        return redirect()->route('transfer.receipt', $transfer)->with('info', 'Payment still pending.');
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

        // Get user's limit warnings
        $limitCheckService = app(LimitCheckService::class);
        $limitWarnings = $limitCheckService->getLimitWarnings(auth()->user());
        $limitStatus = $limitCheckService->getUserLimitStatus(auth()->user());

        return view('transfer.quote', compact('bankCode','accountNumber','accountName','bankName','quote','remaining','limitWarnings','limitStatus'));
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
        
        // Enhanced validation of CM E.164
        if (!(strlen($digits) === 12 && str_starts_with($digits, '237'))) {
            return back()->with('error', 'Please enter a valid Cameroon MoMo number in international format (e.g., 2376XXXXXXXX).');
        }
        
        // Validate provider-specific format
        $prefix3 = substr($digits, 3, 3);
        $isValidMtn = preg_match('/^(65[0-9]|6[7-8][0-9])/', $prefix3);
        $isValidOrange = preg_match('/^69[0-9]/', $prefix3);
        
        if (!($isValidMtn || $isValidOrange)) {
            return back()->with('error', 'Invalid mobile money number. Please enter a valid MTN or Orange mobile money number.');
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

        // Start a database transaction to ensure data consistency
        $transfer = DB::transaction(function () use ($quote, $msisdn, $provider) {
            // Check if a transfer with this quote already exists
            $existingTransfer = Transfer::where('quote_id', $quote->id)->first();
            if ($existingTransfer) {
                return $existingTransfer;
            }

            // Get the authenticated user or fallback to system user
            $userId = auth()->id() ?? \App\Models\User::query()->min('id');
            
            // Create the transfer record
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
                'msisdn' => $msisdn,
                'provider' => $provider,
                'timeline' => [
                    ['state' => 'quote_created', 'at' => now()->toIso8601String()],
                    ['state' => 'payin_pending', 'at' => now()->toIso8601String(), 'msisdn' => $msisdn, 'provider' => $provider],
                ],
            ]);

            // Log the transfer creation
            \Log::info('Transfer created', [
                'transfer_id' => $transfer->id,
                'msisdn' => substr($msisdn, 0, 5) . '...' . substr($msisdn, -2),
                'provider' => $provider,
                'amount_xaf' => $quote->amount_xaf,
            ]);

            return $transfer;
        });

        // Check if this transfer already existed
        if (Transfer::where('quote_id', $quote->id)->where('id', '!=', $transfer->id)->exists()) {
            return redirect()
                ->route('transfer.receipt', $transfer)
                ->with('info', 'A transaction with this quote already exists.');
        }

        // Note: Transaction will be recorded in limits system only when it succeeds
        // This happens via webhook callbacks when payment status changes to success

        // Generate a unique reference for this transaction
        $reference = (string) Str::uuid();
        
        // Initiate payment with PawaPay
        $resp = $pawa->initiatePayIn([
            'amount_xaf_minor' => (int)($quote->total_pay_xaf * 100), // Convert to minor units
            'msisdn' => $msisdn,
            'currency' => 'XAF',
            'reference' => $reference,
            'callback_url' => route('webhooks.pawapay'),
            'provider' => $provider,
            'client_ref' => $quote->quote_ref ?? (string) Str::uuid(),
            'customer_message' => env('PAWAPAY_CUSTOMER_MESSAGE', 'TexaPay Payment'),
        ]);

        // PawaPay v2 returns status: ACCEPTED -> we map to 'pending'. Only treat explicit 'failed' as failure
        if (($resp['status'] ?? 'failed') === 'failed') {
            // Log the failure
            $errorMessage = $resp['message'] ?? 'Failed to initiate payment';
            $timeline = is_array($transfer->timeline) ? $transfer->timeline : [];
            $timeline[] = [
                'state' => 'payin_failed',
                'at' => now()->toIso8601String(),
                'reason' => $errorMessage,
                'raw' => $resp
            ];
            
            $transfer->update([
                'status' => 'failed',
                'payin_status' => 'failed',
                'timeline' => $timeline
            ]);
            
            \Log::error('Failed to initiate PawaPay payment', [
                'transfer_id' => $transfer->id,
                'msisdn' => substr($msisdn, 0, 5) . '...' . substr($msisdn, -2),
                'provider' => $provider,
                'response' => $resp
            ]);
            
            return redirect()
                ->route('transfer.receipt', $transfer)
                ->with('error', 'Failed to initiate payment: ' . $errorMessage);
        }
        
        // Update transfer with payment reference and pending status
        $timeline = is_array($transfer->timeline) ? $transfer->timeline : [];
        $timeline[] = [
            'state' => 'payin_initiated',
            'at' => now()->toIso8601String(),
            'reference' => $reference,
            'provider' => $provider
        ];
        
        $transfer->update([
            'payin_ref' => $reference,
            'payin_status' => 'pending',
            'status' => 'payin_pending',
            'timeline' => $timeline
        ]);
        
        // Log the successful payment initiation
        \Log::info('Payment initiated successfully', [
            'transfer_id' => $transfer->id,
            'reference' => $reference,
            'provider' => $provider
        ]);
        
        // Redirect to receipt page with pending status
        return redirect()
            ->route('transfer.receipt', $transfer)
            ->with('info', 'Payment initiated. Please complete the payment on your mobile money app.');
    }

    public function showReceipt(Request $request, Transfer $transfer): View
    {
        return view('transfer.receipt', compact('transfer'));
    }

    /**
     * Handle the payout initiation with idempotency and transaction safety
     * 
     * This method ensures that payouts are processed in an idempotent manner by:
     * 1. Using database transactions with row-level locking
     * 2. Generating and tracking idempotency keys
     * 3. Properly handling concurrent requests
     * 4. Providing detailed error handling and logging
     */
    public function initiatePayout(Request $request, Transfer $transfer, SafeHaven $safeHaven, RefundService $refundService = null)
    {
        // Start a database transaction with a lock on the transfer record
        return DB::transaction(function () use ($request, $transfer, $safeHaven, $refundService) {
            $refundService = $refundService ?? app(RefundService::class);
            // Reload the transfer with a lock to prevent concurrent modifications
            $transfer = Transfer::lockForUpdate()->findOrFail($transfer->id);
            
            // Check if payout was already successful
            if ($transfer->payout_status === 'success') {
                Log::info('Payout already completed', [
                    'transfer_id' => $transfer->id,
                    'payout_ref' => $transfer->payout_ref,
                    'payout_status' => $transfer->payout_status,
                    'status' => $transfer->status
                ]);
                
                return $this->jsonOrRedirect($request, [
                    'status' => 'already_processed',
                    'message' => 'Payout already completed successfully',
                    'transfer_id' => $transfer->id,
                    'payout_ref' => $transfer->payout_ref
                ]);
            }

            // Check if payout is already in progress (within the last 15 minutes)
            if ($transfer->payout_status === 'processing' && 
                $transfer->payout_attempted_at > now()->subMinutes(15)) {
                Log::info('Payout already in progress', [
                    'transfer_id' => $transfer->id,
                    'last_attempt' => $transfer->payout_attempted_at,
                    'payout_ref' => $transfer->payout_ref
                ]);
                
                return $this->jsonOrRedirect($request, [
                    'status' => 'in_progress',
                    'message' => 'Payout is already being processed',
                    'transfer_id' => $transfer->id,
                    'last_attempt' => $transfer->payout_attempted_at
                ]);
            }

            // Generate idempotency key if not exists
            $idempotencyKey = $transfer->payout_idempotency_key ?? (string) Str::uuid();
            
            // Update transfer with processing status
            $transfer->update([
                'payout_idempotency_key' => $idempotencyKey,
                'payout_status' => 'processing',
                'payout_attempted_at' => now(),
                'last_payout_error' => null,
                'payout_initiated_at' => now()
            ]);

            try {
                // Verify the transfer hasn't been processed by another request
                if ($transfer->payout_status === 'success') {
                    Log::warning('Race condition detected: payout already completed in another process', [
                        'transfer_id' => $transfer->id,
                        'idempotency_key' => $idempotencyKey
                    ]);
                    
                    return $this->jsonOrRedirect($request, [
                        'status' => 'already_processed',
                        'message' => 'Payout already completed in another process',
                        'transfer_id' => $transfer->id,
                        'payout_ref' => $transfer->payout_ref
                    ]);
                }

                // Prepare the payout payload
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

                // Log the payout attempt
                Log::info('Initiating payout', [
                    'transfer_id' => $transfer->id,
                    'idempotency_key' => $idempotencyKey,
                    'amount_ngn_minor' => $payload['amount_ngn_minor'],
                    'bank_code' => $payload['bank_code'],
                    'account_number' => substr($payload['account_number'], 0, 3) . '...' . substr($payload['account_number'], -3)
                ]);

                // Initiate the payout
                $resp = $safeHaven->payout($payload);
                
                // Log the response
                Log::debug('Payout response', [
                    'transfer_id' => $transfer->id,
                    'status' => $resp['status'],
                    'reference' => $resp['ref'] ?? null,
                    'raw_response' => $resp['raw'] ?? null
                ]);
                
                // Prepare timeline update
                $timeline = is_array($transfer->timeline) ? $transfer->timeline : [];
                $timeline[] = [
                    'state' => 'ngn_payout_initiated',
                    'at' => now()->toIso8601String(),
                    'reference' => $idempotencyKey,
                    'provider_response' => $resp['raw'] ?? null
                ];

                // Determine the new status based on the response
                $payoutStatus = $resp['status'] ?? 'failed';
                $newStatus = match($payoutStatus) {
                    'success' => 'payout_success',
                    'failed' => 'failed',
                    default => 'payout_pending'
                };

                // Prepare update data
                $updateData = [
                    'payout_ref' => $resp['ref'] ?? $transfer->payout_ref,
                    'payout_status' => $payoutStatus,
                    'status' => $newStatus,
                    'timeline' => $timeline,
                    'payout_attempted_at' => now()
                ];

                // If successful, mark as completed
                if ($payoutStatus === 'success') {
                    $updateData['payout_completed_at'] = now();
                    $timeline[] = [
                        'state' => 'ngn_payout_success',
                        'at' => now()->toIso8601String(),
                        'reference' => $resp['ref'] ?? null
                    ];
                    $updateData['timeline'] = $timeline;
                    
                    // Record successful transaction in limits system (both payin and payout succeeded)
                    $limitCheckService = app(LimitCheckService::class);
                    $limitCheckService->recordTransaction($transfer->user, $transfer->amount_xaf, true);
                    
                    Log::info('Payout completed successfully and recorded in limits system', [
                        'transfer_id' => $transfer->id,
                        'user_id' => $transfer->user_id,
                        'amount' => $transfer->amount_xaf,
                        'idempotency_key' => $idempotencyKey,
                        'payout_ref' => $updateData['payout_ref']
                    ]);
                } else if ($payoutStatus === 'failed') {
                    $errorMsg = $resp['raw']['message'] ?? 'Unknown error';
                    $updateData['last_payout_error'] = $errorMsg;
                    
                    // Add detailed error to timeline
                    $timeline[] = [
                        'state' => 'ngn_payout_failed',
                        'at' => now()->toIso8601String(),
                        'error' => $errorMsg,
                        'response' => $resp['raw'] ?? null
                    ];
                    $updateData['timeline'] = $timeline;
                    
                    Log::error('Payout failed', [
                        'transfer_id' => $transfer->id,
                        'idempotency_key' => $idempotencyKey,
                        'error' => $errorMsg,
                        'response' => $resp['raw'] ?? null
                    ]);
                    
                    // If payin was successful but payout failed, initiate refund
                    if ($transfer->payin_status === 'success') {
                        $this->initiateRefund($transfer, $refundService, [
                            'error' => $errorMsg,
                            'response' => $resp['raw'] ?? null
                        ]);
                    }
                    
                    // Throw exception to trigger rollback
                    throw new \Exception("Payout failed: " . $errorMsg);
                }

                // Update the transfer
                $transfer->update($updateData);

                // Return appropriate response
                return match($payoutStatus) {
                    'success' => $this->jsonOrRedirect($request, [
                        'status' => 'success',
                        'message' => 'NGN payout completed successfully.',
                        'transfer_id' => $transfer->id,
                        'payout_ref' => $transfer->payout_ref
                    ]),
                    'failed' => $this->jsonOrRedirect($request, [
                        'status' => 'error',
                        'message' => 'NGN payout failed: ' . ($resp['raw']['message'] ?? 'Unknown error'),
                        'transfer_id' => $transfer->id,
                        'error_details' => $resp['raw'] ?? null
                    ], 400),
                    default => $this->jsonOrRedirect($request, [
                        'status' => 'pending',
                        'message' => 'NGN payout is being processed.',
                        'transfer_id' => $transfer->id,
                        'payout_ref' => $transfer->payout_ref
                    ])
                };

            } catch (\Exception $e) {
                // Log the full exception for debugging
                Log::error("Payout processing failed for transfer {$transfer->id}", [
                    'idempotency_key' => $idempotencyKey ?? null,
                    'exception' => [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ],
                    'transfer_status' => $transfer->status,
                    'payout_status' => $transfer->payout_status,
                    'payout_ref' => $transfer->payout_ref
                ]);

                // Update transfer with error status
                $transfer->update([
                    'payout_status' => 'failed',
                    'last_payout_error' => $e->getMessage(),
                    'payout_attempted_at' => now(),
                    'timeline' => array_merge($transfer->timeline ?? [], [[
                        'state' => 'ngn_payout_error',
                        'at' => now()->toIso8601String(),
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]])
                ]);

                // Re-throw to trigger transaction rollback
                throw $e;
            }
        });
    }

    /**
     * Helper method to return JSON or redirect response based on request type
     */
    /**
     * Initiate a refund for a failed payout
     */
    private function initiateRefund(Transfer $transfer, RefundService $refundService, array $errorDetails = [])
    {
        try {
            // Only attempt refund if the transfer is eligible
            if (!$transfer->isEligibleForRefund()) {
                Log::warning('Transfer not eligible for refund', [
                    'transfer_id' => $transfer->id,
                    'payin_status' => $transfer->payin_status,
                    'payout_status' => $transfer->payout_status,
                    'refund_status' => $transfer->refund_status
                ]);
                return null;
            }

            Log::info('Initiating refund for failed payout', [
                'transfer_id' => $transfer->id,
                'amount' => $transfer->amount_xaf,
                'currency' => 'XAF',
                'error_details' => $errorDetails
            ]);

            // Call the refund service
            $refundResult = $refundService->refundFailedPayout($transfer);
            
            // Log the result
            if ($refundResult['success']) {
                Log::info('Refund initiated successfully', [
                    'transfer_id' => $transfer->id,
                    'refund_id' => $refundResult['refund_id']
                ]);
            } else {
                Log::error('Failed to initiate refund', [
                    'transfer_id' => $transfer->id,
                    'error' => $refundResult['message'] ?? 'Unknown error'
                ]);
            }
            
            return $refundResult;
            
        } catch (\Exception $e) {
            Log::error('Exception while initiating refund', [
                'transfer_id' => $transfer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }
    
    private function jsonOrRedirect(Request $request, array $data, int $status = 200)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($data, $status);
        }

        $method = $status >= 400 ? 'error' : 'info';
        $transferParam = $request->route('transfer');
        return redirect()
            ->route('transfer.receipt', $transferParam)
            ->with($method, $data['message'] ?? null);
    }

    public function payoutStatus(Request $request, Transfer $transfer, SafeHaven $safeHaven)
    {
        if (!$transfer->payout_ref && !$transfer->name_enquiry_reference) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'No payout session to check.'], 400);
            }
            return back()->with('error', 'No payout session to check.');
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
            $message = 'Payout completed successfully';
            
            // Record successful transaction in limits system idempotently
            $limitCheckService = app(LimitCheckService::class);
            $limitCheckService->recordCompletedTransferOnce($transfer);
            
            Log::info('Complete transaction recorded in limits system (idempotent)', [
                'transfer_id' => $transfer->id,
                'user_id' => $transfer->user_id,
                'amount' => $transfer->amount_xaf,
                'payin_status' => $transfer->payin_status,
                'payout_status' => 'success'
            ]);
        } elseif ($resp['status'] === 'failed') {
            $update['status'] = 'failed';
            $update['payout_status'] = 'failed';
            $message = 'Payout failed';
        } else {
            $update['payout_status'] = 'pending';
            $message = 'Payout is still pending';
        }
        
        $transfer->update($update);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => $resp['status'],
                'message' => $message,
                'transfer_status' => $update['status'] ?? $transfer->status,
                'redirect' => $resp['status'] !== 'pending' ? route('transfer.receipt', $transfer) : null,
            ]);
        }
        
        return redirect()
            ->route('transfer.receipt', $transfer)
            ->with('info', 'Payout status: ' . $resp['status']);
    }

    /**
     * Live timeline JSON endpoint for polling.
     */
    public function timeline(Request $request, Transfer $transfer)
    {
        // Owner guard (route already under auth + redirect.admins). Double-check ownership.
        if (auth()->check() && !((bool) (auth()->user()->is_admin ?? false))) {
            abort_if($transfer->user_id !== auth()->id(), 403);
        }
        $data = [
            'id' => $transfer->id,
            'status' => $transfer->status,
            'payin_status' => $transfer->payin_status,
            'payout_status' => $transfer->payout_status,
            'timeline' => $transfer->timeline ?? [],
            'updated_at' => optional($transfer->updated_at)->toIso8601String(),
        ];
        return response()->json($data);
    }

    /**
     * Download single receipt as PDF (fallback to HTML if DomPDF not installed).
     */
    public function receiptPdf(Request $request, Transfer $transfer)
    {
        $enabled = filter_var(env('FEATURE_ENABLE_RECEIPT_PDF', true), FILTER_VALIDATE_BOOLEAN);
        abort_unless($enabled, 404);

        $data = ['transfer' => $transfer];
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('transfer.receipt_pdf', $data);
            $filename = 'receipt-' . $transfer->id . '-' . now()->format('Ymd-Hi') . '.pdf';
            return $pdf->download($filename);
        }
        // Fallback: return printable HTML
        return response()->view('transfer.receipt_pdf', $data);
    }

    /**
     * Generate a temporary signed share link for this receipt.
     */
    public function shareLink(Request $request, Transfer $transfer)
    {
        $enabled = filter_var(env('FEATURE_ENABLE_RECEIPT_SHARE', true), FILTER_VALIDATE_BOOLEAN);
        abort_unless($enabled, 404);

        // Owner guard
        abort_if($transfer->user_id !== auth()->id(), 403);

        $days = (int) env('RECEIPT_SHARE_TTL_DAYS', 7);
        $expires = now()->addDays(max(1, $days));
        $url = URL::temporarySignedRoute('transfer.receipt.shared', $expires, ['transfer' => $transfer->id]);
        return response()->json(['url' => $url, 'expires_at' => $expires->toIso8601String()]);
    }

    /**
     * Public, signed receipt view with masked data and no account actions.
     */
    public function showSharedReceipt(Request $request, Transfer $transfer): View
    {
        return view('transfer.receipt', [
            'transfer' => $transfer,
            'shared' => true,
        ]);
    }
}
