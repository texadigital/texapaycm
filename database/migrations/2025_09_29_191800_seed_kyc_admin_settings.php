<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Seed default KYC settings using the AdminSetting model if available
        try {
            \App\Models\AdminSetting::setValue('kyc_enabled', false, 'boolean', 'Enable Smile ID KYC gating', 'kyc');

            // Level 0 caps (XAF) — Cameroon defaults
            \App\Models\AdminSetting::setValue('kyc.level0.per_tx_cap_xaf', 50000, 'integer', 'Level 0 per-transaction cap (XAF)', 'kyc');
            \App\Models\AdminSetting::setValue('kyc.level0.daily_cap_xaf', 150000, 'integer', 'Level 0 daily cap (XAF)', 'kyc');
            \App\Models\AdminSetting::setValue('kyc.level0.monthly_cap_xaf', 1000000, 'integer', 'Level 0 monthly cap (XAF)', 'kyc');

            // Level 1 caps (XAF)
            \App\Models\AdminSetting::setValue('kyc.level1.per_tx_cap_xaf', 2000000, 'integer', 'Level 1 per-transaction cap (XAF)', 'kyc');
            \App\Models\AdminSetting::setValue('kyc.level1.daily_cap_xaf', 5000000, 'integer', 'Level 1 daily cap (XAF)', 'kyc');
            \App\Models\AdminSetting::setValue('kyc.level1.monthly_cap_xaf', 50000000, 'integer', 'Level 1 monthly cap (XAF)', 'kyc');
        } catch (\Throwable $e) {
            // Allow migration to continue even if model not ready in some environments
            \Log::error('Failed seeding KYC AdminSettings', ['error' => $e->getMessage()]);
        }
    }

    public function down(): void
    {
        try {
            $keys = [
                'kyc_enabled',
                'kyc.level0.per_tx_cap_xaf','kyc.level0.daily_cap_xaf','kyc.level0.monthly_cap_xaf',
                'kyc.level1.per_tx_cap_xaf','kyc.level1.daily_cap_xaf','kyc.level1.monthly_cap_xaf',
            ];
            foreach ($keys as $k) {
                $s = \App\Models\AdminSetting::where('setting_key', $k)->first();
                if ($s) { $s->delete(); }
            }
        } catch (\Throwable $e) {
            \Log::warning('Failed to rollback KYC AdminSettings', ['error' => $e->getMessage()]);
        }
    }
};
