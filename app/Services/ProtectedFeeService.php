<?php

namespace App\Services;

class ProtectedFeeService
{
    // Version tag for fee rules; bump if logic changes
    public const VERSION = 'v1';

    /**
     * Calculate fee for a given NGN amount in minor units (kobo).
     * Rules:
     * - 1–100,000 NGN: 3% + 100 NGN
     * - 100,001–1,000,000 NGN: 2.5% + 100 NGN, capped at 2,000 NGN
     * - 1,000,001+ NGN: flat 2,000 NGN
     */
    public function calculate(int $amountNgnMinor): array
    {
        $amountNgn = $amountNgnMinor / 100; // to naira for readability
        $feeNgn = 0.0;
        $capped = false;
        $components = [];

        if ($amountNgn <= 100000) {
            $percent = 0.03;
            $fixed = 100.0;
            $feeNgn = ($amountNgn * $percent) + $fixed;
            $components = [
                'tier' => 'T1', 'percent' => 3.0, 'fixed_ngn' => 100,
            ];
        } elseif ($amountNgn <= 1000000) {
            $percent = 0.025;
            $fixed = 100.0;
            $feeNgn = ($amountNgn * $percent) + $fixed;
            if ($feeNgn > 2000.0) {
                $feeNgn = 2000.0;
                $capped = true;
            }
            $components = [
                'tier' => 'T2', 'percent' => 2.5, 'fixed_ngn' => 100, 'cap_ngn' => 2000,
            ];
        } else {
            $feeNgn = 2000.0;
            $components = [
                'tier' => 'T3', 'flat_ngn' => 2000,
            ];
        }

        $feeMinor = (int) round($feeNgn * 100);
        return [
            'fee_ngn_minor' => $feeMinor,
            'fee_rule_version' => self::VERSION,
            'fee_components' => $components + ['capped' => $capped],
        ];
    }
}
