<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Providers
            ['group' => 'providers', 'key' => 'pawapay.base_url', 'type' => 'string', 'label' => 'pawaPay Base URL', 'description' => null, 'value' => env('PAWAPAY_BASE_URL')],
            ['group' => 'providers', 'key' => 'pawapay.api_key', 'type' => 'string', 'label' => 'pawaPay API Key', 'description' => null, 'value' => env('PAWAPAY_API_KEY')],
            ['group' => 'providers', 'key' => 'pawapay.webhook_secret', 'type' => 'string', 'label' => 'pawaPay Webhook Secret', 'description' => null, 'value' => env('PAWAPAY_WEBHOOK_SECRET')],
            ['group' => 'providers', 'key' => 'safehaven.base_url', 'type' => 'string', 'label' => 'Safe Haven Base URL', 'description' => null, 'value' => env('SAFEHAVEN_BASE_URL')],
            ['group' => 'providers', 'key' => 'safehaven.api_key', 'type' => 'string', 'label' => 'Safe Haven API Key', 'description' => null, 'value' => env('SAFEHAVEN_API_KEY')],
            ['group' => 'providers', 'key' => 'oxr.base_url', 'type' => 'string', 'label' => 'Open Exchange Rates Base URL', 'description' => null, 'value' => env('OXR_BASE_URL')],
            ['group' => 'providers', 'key' => 'oxr.app_id', 'type' => 'string', 'label' => 'Open Exchange Rates App ID', 'description' => null, 'value' => env('OXR_APP_ID')],

            // FX & margin
            ['group' => 'fx', 'key' => 'fx.margin_bps', 'type' => 'integer', 'label' => 'FX Margin (bps)', 'description' => 'Basis points added to cross-rate (e.g., 50 = 0.50%).', 'value' => '0'],
            ['group' => 'fx', 'key' => 'fx.decimals_display_rate', 'type' => 'integer', 'label' => 'FX Rate Display Decimals', 'description' => 'Decimal places for FX display.', 'value' => '6'],

            // Fees
            ['group' => 'fees', 'key' => 'fees.fixed_xaf', 'type' => 'integer', 'label' => 'Fixed Fee (XAF minor)', 'description' => 'Fixed fee in XAF minor units.', 'value' => '0'],
            ['group' => 'fees', 'key' => 'fees.percent_bps', 'type' => 'integer', 'label' => 'Percent Fee (bps)', 'description' => 'Percentage fee in basis points of XAF amount.', 'value' => '0'],
            ['group' => 'fees', 'key' => 'fees.min_xaf', 'type' => 'integer', 'label' => 'Min Fee (XAF minor)', 'description' => null, 'value' => '0'],
            ['group' => 'fees', 'key' => 'fees.max_xaf', 'type' => 'integer', 'label' => 'Max Fee (XAF minor)', 'description' => null, 'value' => null],
            ['group' => 'fees', 'key' => 'fees.charge_mode', 'type' => 'string', 'label' => 'Fee Charge Mode', 'description' => 'on_top or deduct_from_send', 'value' => 'on_top'],

            // Limits
            ['group' => 'limits', 'key' => 'transfer.min_xaf', 'type' => 'integer', 'label' => 'Min per Transfer (XAF minor)', 'description' => null, 'value' => env('TRANSFER_MIN_XAF')],
            ['group' => 'limits', 'key' => 'transfer.max_xaf', 'type' => 'integer', 'label' => 'Max per Transfer (XAF minor)', 'description' => null, 'value' => env('TRANSFER_MAX_XAF')],
            ['group' => 'limits', 'key' => 'limits.daily_max_xaf', 'type' => 'integer', 'label' => 'Daily Max (XAF minor)', 'description' => null, 'value' => env('DAILY_MAX_XAF')],
            ['group' => 'limits', 'key' => 'limits.rolling_window_hours', 'type' => 'integer', 'label' => 'Rolling Window (hours)', 'description' => null, 'value' => env('ROLLING_WINDOW_HOURS', 24)],

            // Quote TTL
            ['group' => 'limits', 'key' => 'quote.ttl_seconds', 'type' => 'integer', 'label' => 'Quote TTL (seconds)', 'description' => 'Default quote lock time.', 'value' => env('QUOTE_TTL_SECONDS', 90)],

            // Toggles
            ['group' => 'toggles', 'key' => 'feature.payin_enabled', 'type' => 'boolean', 'label' => 'Enable Pay-in', 'description' => null, 'value' => env('FEATURE_PAYIN_ENABLED', true) ? 'true' : 'false'],
            ['group' => 'toggles', 'key' => 'feature.payout_enabled', 'type' => 'boolean', 'label' => 'Enable Payout', 'description' => null, 'value' => env('FEATURE_PAYOUT_ENABLED', true) ? 'true' : 'false'],
            ['group' => 'toggles', 'key' => 'maintenance.mode', 'type' => 'boolean', 'label' => 'Maintenance Mode', 'description' => null, 'value' => env('MAINTENANCE_MODE', false) ? 'true' : 'false'],

            // Banks & countries (JSON arrays)
            ['group' => 'providers', 'key' => 'safehaven.supported_banks', 'type' => 'json', 'label' => 'Supported Banks (JSON)', 'description' => 'List of supported bank codes/names.', 'value' => '[]'],
            ['group' => 'providers', 'key' => 'safehaven.blocked_banks', 'type' => 'json', 'label' => 'Blocked Banks (JSON)', 'description' => 'List of blocked bank codes/names.', 'value' => '[]'],

            // Copy / labels
            ['group' => 'copy', 'key' => 'copy.pending_message', 'type' => 'string', 'label' => 'Pending Message', 'description' => null, 'value' => 'We are processing your transfer.'],
            ['group' => 'copy', 'key' => 'copy.failed_message', 'type' => 'string', 'label' => 'Failed Message', 'description' => null, 'value' => 'Your transfer could not be completed. Please try again.'],
        ];

        foreach ($settings as $s) {
            Setting::updateOrCreate(
                ['key' => $s['key']],
                [
                    'group' => $s['group'],
                    'type' => $s['type'],
                    'label' => $s['label'] ?? null,
                    'description' => $s['description'] ?? null,
                    'value' => isset($s['value']) ? (string) $s['value'] : null,
                    'enabled' => true,
                ]
            );
        }
    }
}
