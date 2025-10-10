<?php

namespace App\Services;

use App\Models\AdminSetting;
use App\Models\Settings\FxSpread;
use App\Models\Settings\Fee;
use App\Models\Settings\FeatureFlag;

class PricingEngine
{
    public const CORRIDOR_XAF_NGN = 'XAF_NGN';

    public function __construct()
    {
    }

    /**
     * Compute transparent pricing for XAF -> NGN given USD-base market rates.
     * Inputs are currency units (not minor) except NGN output minor.
     * Returns array with keys:
     * - interbank_rate (float)
     * - margin_percent (float)
     * - effective_rate (float)
     * - fee_amount_xaf (int)
     * - total_pay_xaf (int)
     * - receive_ngn_minor (int)
     */
    public function price(
        int $amountXaf,
        float $usdToXaf,
        float $usdToNgn,
        ?array $opts = []
    ): array {
        $opts = $opts ?? [];
        $chargeMode = $opts['charge_mode'] ?? (string) env('FEES_CHARGE_MODE', 'on_top'); // on_top | net

        // Interbank cross rate XAF->NGN
        $interbank = $usdToXaf > 0 ? ($usdToNgn / $usdToXaf) : 0.0;
        if ($interbank <= 0) {
            throw new \InvalidArgumentException('Invalid market rates');
        }

        // FX margin bps (basis points)
        // If feature flag is enabled, prefer DB-backed spread; else fallback to AdminSetting/env
        $marginBps = (int) AdminSetting::getValue('pricing.fx_margin_bps', (int) env('FX_MARGIN_BPS', 0));
        if (FeatureFlag::isEnabled('pricing.db_settings.enabled')) {
            $spread = FxSpread::query()
                ->where('corridor', self::CORRIDOR_XAF_NGN)
                ->where('active', true)
                ->orderByDesc('updated_at')
                ->first();
            if ($spread) {
                $marginBps = (int) $spread->margin_bps;
            }
        }
        $marginPct = max(0.0, $marginBps / 100.0); // convert bps to percent
        $effective = $interbank * (1 - ($marginBps / 10000));

        // Free threshold and tiers
        $threshold = (int) AdminSetting::getValue('pricing.min_free_transfer_threshold_xaf', 0);
        $tiers = [];
        if (FeatureFlag::isEnabled('pricing.db_settings.enabled')) {
            // Build tiers from Fee table for corridor
            $feeRows = Fee::query()
                ->where('corridor', self::CORRIDOR_XAF_NGN)
                ->where('active', true)
                ->orderBy('min_xaf')
                ->get();
            foreach ($feeRows as $f) {
                $tiers[] = [
                    'min' => (int) $f->min_xaf,
                    'max' => (int) ($f->max_xaf ?: PHP_INT_MAX),
                    'flat_xaf' => (int) $f->flat_xaf,
                    'percent_bps' => (int) $f->percent_bps,
                    'cap_xaf' => (int) $f->cap_xaf,
                ];
            }
        } else {
            $tiers = AdminSetting::getValue('pricing.fee_tiers', []);
            if (!is_array($tiers)) { $tiers = []; }
        }

        $fee = 0;
        if ($amountXaf > $threshold) {
            // Select first matching tier
            $fee = $this->computeTieredFee($amountXaf, $tiers);
        }

        // Apply on-top vs net mode
        $chargeOnTop = ($chargeMode === 'on_top');
        $totalPayXaf = $chargeOnTop ? ($amountXaf + $fee) : $amountXaf;
        $effectiveSendXaf = $chargeOnTop ? $amountXaf : max($amountXaf - $fee, 0);

        // Compute receive in NGN minor (kobo)
        $receiveMinor = (int) round($effectiveSendXaf * $effective * 100);
        // Tiny amount guard: if user sends >0 XAF and receiveMinor is 0 due to rounding, bump to 1 kobo
        if ($effectiveSendXaf > 0 && $receiveMinor === 0) {
            $receiveMinor = 1;
        }

        return [
            'interbank_rate' => $interbank,
            'margin_percent' => $marginPct,
            'effective_rate' => $effective,
            'fee_amount_xaf' => $fee,
            'total_pay_xaf' => $totalPayXaf,
            'receive_ngn_minor' => $receiveMinor,
        ];
    }

    /**
     * Compute fee using tier definitions.
     * Tier format example:
     * [
     *   {"min":50001,"max":200000,"flat_xaf":500,"percent_bps":50,"cap_xaf":1500},
     *   ...
     * ]
     */
    protected function computeTieredFee(int $amountXaf, array $tiers): int
    {
        foreach ($tiers as $t) {
            $min = (int) ($t['min'] ?? 0);
            $max = (int) ($t['max'] ?? PHP_INT_MAX);
            if ($amountXaf < $min || $amountXaf > $max) {
                continue;
            }
            $flat = (int) ($t['flat_xaf'] ?? 0);
            $bps = (int) ($t['percent_bps'] ?? 0);
            $cap = isset($t['cap_xaf']) ? (int) $t['cap_xaf'] : null;
            $percent = (int) floor($amountXaf * $bps / 10000);
            $fee = $flat + $percent;
            if ($cap !== null) {
                $fee = min($fee, $cap);
            }
            return max(0, $fee);
        }
        // default: no tier matched → 0 fee
        return 0;
    }
}
