<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\LimitCheckService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckUserLimits
{
    protected $limitCheckService;

    public function __construct(LimitCheckService $limitCheckService)
    {
        $this->limitCheckService = $limitCheckService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check limits for authenticated users on transaction routes
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        
        // Only apply to specific transaction routes
        $transactionRoutes = [
            'transfer.quote.create',
            'transfer.confirm',
        ];

        if (!in_array($request->route()->getName(), $transactionRoutes)) {
            return $next($request);
        }

        try {
            // Get transaction amount from request
            $amount = $this->getTransactionAmount($request);
            
            if ($amount <= 0) {
                return $next($request);
            }

            // Check if user can make this transaction
            $limitCheck = $this->limitCheckService->canUserTransact($user, $amount);

            if (!$limitCheck['can_transact']) {
                Log::warning('Transaction blocked by limits', [
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'reason' => $limitCheck['reason'],
                    'limit_type' => $limitCheck['limit_type']
                ]);

                // Return appropriate response based on request type
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $limitCheck['reason'],
                        'limit_info' => $limitCheck
                    ], 400);
                }

                return redirect()->back()
                    ->withInput()
                    ->with('error', $limitCheck['reason'])
                    ->with('limit_info', $limitCheck);
            }

            // Add limit warnings to the request for display
            $warnings = $this->limitCheckService->getLimitWarnings($user);
            if (!empty($warnings)) {
                $request->merge(['limit_warnings' => $warnings]);
            }

        } catch (\Exception $e) {
            Log::error('Error in CheckUserLimits middleware', [
                'user_id' => $user->id,
                'route' => $request->route()->getName(),
                'error' => $e->getMessage()
            ]);

            // Don't block the transaction on middleware errors
            // Just log and continue
        }

        return $next($request);
    }

    /**
     * Extract transaction amount from request
     */
    protected function getTransactionAmount(Request $request): int
    {
        // For quote creation
        if ($request->has('amount_xaf')) {
            return (int) $request->input('amount_xaf');
        }

        // For transfer confirmation, get amount from quote
        if ($request->route()->getName() === 'transfer.confirm') {
            $quoteId = session('transfer.quote_id');
            if ($quoteId) {
                $quote = \App\Models\Quote::find($quoteId);
                if ($quote) {
                    return $quote->amount_xaf;
                }
            }
        }

        return 0;
    }
}
