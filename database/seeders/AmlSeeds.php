<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Models\AmlRulePack;
use App\Models\AmlRule;

class AmlSeeds extends Seeder
{
    public function run(): void
    {
        try {
            // Create or update a general AML rule pack
            $pack = AmlRulePack::updateOrCreate(
                ['key' => 'core_aml'],
                [
                    'name' => 'Core AML Rules',
                    'description' => 'Base AML rules for high-amount and low-KYC scenarios',
                    'is_active' => true,
                    'tags' => ['core'],
                ]
            );

            // High amount with low KYC rule
            AmlRule::updateOrCreate(
                ['key' => 'high_amount_low_kyc'],
                [
                    'pack_id' => $pack->id,
                    'name' => 'High Amount with Low KYC',
                    'description' => 'Flags transfers above 500,000 XAF when KYC level < 1',
                    'severity' => 'high',
                    'is_active' => true,
                    'expression' => [
                        'amount_xaf_gt' => 500000,
                        'min_kyc_level' => 1,
                    ],
                    'thresholds' => [],
                ]
            );

            // High NGN amount rule (e.g., > 5,000,000 NGN)
            AmlRule::updateOrCreate(
                ['key' => 'high_amount_ngn'],
                [
                    'pack_id' => $pack->id,
                    'name' => 'High Amount NGN',
                    'description' => 'Flags transfers receiving above 5,000,000 NGN equivalent',
                    'severity' => 'high',
                    'is_active' => true,
                    'expression' => [
                        'amount_ngn_gt' => 5000000,
                    ],
                    'thresholds' => [],
                ]
            );

            // FX USD equivalent rule (e.g., > $5,000)
            AmlRule::updateOrCreate(
                ['key' => 'fx_usd_over_5k'],
                [
                    'pack_id' => $pack->id,
                    'name' => 'High FX USD Equivalent',
                    'description' => 'Flags transfers whose USD equivalent exceeds $5,000',
                    'severity' => 'high',
                    'is_active' => true,
                    'expression' => [
                        'fx_usd_gt' => 5000,
                    ],
                    'thresholds' => [],
                ]
            );

            // 24h burst activity rule (> 10 transfers)
            AmlRule::updateOrCreate(
                ['key' => 'burst_24h_activity'],
                [
                    'pack_id' => $pack->id,
                    'name' => '24h Burst Activity',
                    'description' => 'Flags users with more than 10 transfers within 24 hours',
                    'severity' => 'medium',
                    'is_active' => true,
                    'expression' => [
                        'daily_txn_count_gt' => 10,
                    ],
                    'thresholds' => [],
                ]
            );

            // Dormant account spike rule (e.g., > 90 days dormant and high amount)
            AmlRule::updateOrCreate(
                ['key' => 'dormant_spike_high_amount'],
                [
                    'pack_id' => $pack->id,
                    'name' => 'Dormant Account Spike',
                    'description' => 'Flags transfers after 90+ days inactivity with amount above 300,000 XAF',
                    'severity' => 'high',
                    'is_active' => true,
                    'expression' => [
                        'dormant_days_gt' => 90,
                        'amount_xaf_gt' => 300000,
                    ],
                    'thresholds' => [],
                ]
            );

            // ATF-focused rule pack
            $atf = AmlRulePack::updateOrCreate(
                ['key' => 'atf_typologies'],
                [
                    'name' => 'ATF Typology Rules',
                    'description' => 'Basic typology examples (round amounts, rapid repeats)',
                    'is_active' => true,
                    'tags' => ['atf'],
                ]
            );

            // Example: Round amount heuristic (placeholder; evaluated by future evaluator upgrade)
            AmlRule::updateOrCreate(
                ['key' => 'round_amount_heuristic'],
                [
                    'pack_id' => $atf->id,
                    'name' => 'Round Amount Heuristic',
                    'description' => 'Flags transfers that are round multiples above 100,000 XAF',
                    'severity' => 'medium',
                    'is_active' => true,
                    'expression' => [
                        // Placeholder keys for future evaluator enhancements
                        'round_amount_multiple' => 100000,
                        'amount_xaf_gt' => 100000,
                    ],
                    'thresholds' => [],
                ]
            );

            Log::info('AmlSeeds completed');
        } catch (\Throwable $e) {
            Log::error('AmlSeeds failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
