<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminSetting;
use App\Models\DailyTransactionSummary;
use App\Services\OpenExchangeRates;
use App\Services\PricingEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PricingController extends Controller
{
    public function limits(Request $request)
    {
        $user = $request->user();
        $perTxnMin = (int) AdminSetting::getValue('transfer.min_xaf', (int) env('TRANSFER_MIN_XAF', 2000));
        $perTxnMax = (int) AdminSetting::getValue('transfer.max_xaf', (int) env('TRANSFER_MAX_XAF', 1000000));

        // Pull rolling usage if available
        $remainingDay = null; $remainingMonth = null; $usedDay = null; $usedMonth = null; $dailyCap = null; $monthlyCap = null;
        try {
            $dailyCap = (int) AdminSetting::getValue('limits.daily_cap_xaf', 0) ?: null;
            $monthlyCap = (int) AdminSetting::getValue('limits.monthly_cap_xaf', 0) ?: null;
            if ($dailyCap || $monthlyCap) {
                $today = now()->toDateString();
                $month = now()->format('Y-m');
                if (class_exists(DailyTransactionSummary::class)) {
                    $dayRow = DailyTransactionSummary::query()
                        ->where('user_id', $user->id)
                        ->where('date', $today)
                        ->first();
                    $monRow = DailyTransactionSummary::query()
                        ->where('user_id', $user->id)
                        ->where('month', $month)
                        ->first();
                    $usedDay = (int) ($dayRow->total_amount_xaf ?? 0);
                    $usedMonth = (int) ($monRow->total_amount_xaf ?? 0);
                    $remainingDay = $dailyCap ? max($dailyCap - $usedDay, 0) : null;
                    $remainingMonth = $monthlyCap ? max($monthlyCap - $usedMonth, 0) : null;
                }
            }
        } catch (\Throwable $e) {
            // Swallow gracefully; return whatever we have
        }

        return response()->json([
            'minXaf' => $perTxnMin,
            'maxXaf' => $perTxnMax,
            'dailyCap' => $dailyCap,
            'monthlyCap' => $monthlyCap,
            'usedToday' => $usedDay,
            'usedMonth' => $usedMonth,
            'remainingXafDay' => $remainingDay,
            'remainingXafMonth' => $remainingMonth,
        ]);
    }

    public function preview(Request $request, OpenExchangeRates $oxr)
    {
        $data = $request->validate([
            'amountXaf' => ['required','numeric','min:1'],
        ]);
        $amountXaf = (int) round($data['amountXaf']);

        $fx = $oxr->fetchUsdRates();
        if (!($fx['usd_to_xaf'] ?? null) || !($fx['usd_to_ngn'] ?? null)) {
            return response()->json([
                'success' => false,
                'code' => 'RATE_UNAVAILABLE',
                'message' => 'Rate unavailable—please try again later.',
                'details' => ['raw' => $fx['raw'] ?? null],
            ], 503);
        }

        $usdToXaf = (float) $fx['usd_to_xaf'];
        $usdToNgn = (float) $fx['usd_to_ngn'];
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
            $cross = $usdToNgn / $usdToXaf;
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

        return response()->json([
            'amountXaf' => $amountXaf,
            'feeTotalXaf' => $feeTotal,
            'totalPayXaf' => $totalPayXaf,
            'receiveNgnMinor' => $receiveNgnMinor,
            'adjustedRate' => $adjustedRate,
        ]);
    }
}
