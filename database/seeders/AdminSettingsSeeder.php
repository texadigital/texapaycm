<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AdminSetting;

class AdminSettingsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $settings = [
            // User Limits Settings
            [
                'setting_key' => 'default_daily_limit',
                'setting_value' => '500000',
                'setting_type' => 'integer',
                'description' => 'Default daily transaction limit in XAF for new users',
                'category' => 'user_limits',
                'sort_order' => 1,
                'is_public' => true,
            ],
            [
                'setting_key' => 'default_monthly_limit',
                'setting_value' => '5000000',
                'setting_type' => 'integer',
                'description' => 'Default monthly transaction limit in XAF for new users',
                'category' => 'user_limits',
                'sort_order' => 2,
                'is_public' => true,
            ],
            [
                'setting_key' => 'default_daily_count',
                'setting_value' => '10',
                'setting_type' => 'integer',
                'description' => 'Default daily transaction count limit for new users',
                'category' => 'user_limits',
                'sort_order' => 3,
                'is_public' => true,
            ],
            [
                'setting_key' => 'default_monthly_count',
                'setting_value' => '100',
                'setting_type' => 'integer',
                'description' => 'Default monthly transaction count limit for new users',
                'category' => 'user_limits',
                'sort_order' => 4,
                'is_public' => true,
            ],
            
            // System Settings
            [
                'setting_key' => 'system_maintenance_mode',
                'setting_value' => '0',
                'setting_type' => 'boolean',
                'description' => 'Enable maintenance mode to disable new transactions',
                'category' => 'system',
                'sort_order' => 1,
                'is_public' => true,
            ],
            [
                'setting_key' => 'max_transaction_amount',
                'setting_value' => '10000000',
                'setting_type' => 'integer',
                'description' => 'Maximum allowed transaction amount in XAF',
                'category' => 'system',
                'sort_order' => 2,
                'is_public' => true,
            ],
            [
                'setting_key' => 'min_transaction_amount',
                'setting_value' => '1000',
                'setting_type' => 'integer',
                'description' => 'Minimum allowed transaction amount in XAF',
                'category' => 'system',
                'sort_order' => 3,
                'is_public' => true,
            ],
            
            // Security Settings
            [
                'setting_key' => 'max_login_attempts',
                'setting_value' => '5',
                'setting_type' => 'integer',
                'description' => 'Maximum login attempts before account lockout',
                'category' => 'security',
                'sort_order' => 1,
                'is_public' => false,
            ],
            [
                'setting_key' => 'lockout_duration_minutes',
                'setting_value' => '30',
                'setting_type' => 'integer',
                'description' => 'Account lockout duration in minutes',
                'category' => 'security',
                'sort_order' => 2,
                'is_public' => false,
            ],
            [
                'setting_key' => 'session_timeout_minutes',
                'setting_value' => '120',
                'setting_type' => 'integer',
                'description' => 'User session timeout in minutes',
                'category' => 'security',
                'sort_order' => 3,
                'is_public' => false,
            ],
            
            // Notification Settings
            [
                'setting_key' => 'email_notifications_enabled',
                'setting_value' => '1',
                'setting_type' => 'boolean',
                'description' => 'Enable email notifications for users',
                'category' => 'notifications',
                'sort_order' => 1,
                'is_public' => true,
            ],
            [
                'setting_key' => 'sms_notifications_enabled',
                'setting_value' => '1',
                'setting_type' => 'boolean',
                'description' => 'Enable SMS notifications for users',
                'category' => 'notifications',
                'sort_order' => 2,
                'is_public' => true,
            ],
            [
                'setting_key' => 'limit_warning_threshold',
                'setting_value' => '80',
                'setting_type' => 'integer',
                'description' => 'Percentage threshold for limit warnings (80 = 80%)',
                'category' => 'notifications',
                'sort_order' => 3,
                'is_public' => false,
            ],
            
            // Support Settings
            [
                'setting_key' => 'support_email',
                'setting_value' => 'support@texa.ng',
                'setting_type' => 'string',
                'description' => 'Support email address',
                'category' => 'support',
                'sort_order' => 1,
                'is_public' => true,
            ],
            [
                'setting_key' => 'support_phone',
                'setting_value' => '+237123456789',
                'setting_type' => 'string',
                'description' => 'Support phone number',
                'category' => 'support',
                'sort_order' => 2,
                'is_public' => true,
            ],
            [
                'setting_key' => 'live_chat_enabled',
                'setting_value' => '1',
                'setting_type' => 'boolean',
                'description' => 'Enable live chat support',
                'category' => 'support',
                'sort_order' => 3,
                'is_public' => true,
            ],
            [
                'setting_key' => 'tawk_to_widget_id',
                'setting_value' => '',
                'setting_type' => 'string',
                'description' => 'Tawk.to widget ID for live chat',
                'category' => 'support',
                'sort_order' => 4,
                'is_public' => true,
            ],

            // Pricing v2 Settings (feature-flagged)
            [
                'setting_key' => 'pricing_v2.enabled',
                'setting_value' => '1',
                'setting_type' => 'boolean',
                'description' => 'Enable Pricing Model v2 (transparent FX + minimal fees)',
                'category' => 'pricing',
                'sort_order' => 1,
                'is_public' => false,
            ],
            [
                'setting_key' => 'pricing.min_free_transfer_threshold_xaf',
                'setting_value' => '10000000',
                'setting_type' => 'integer',
                'description' => 'Transfers up to and including this XAF amount pay zero fee',
                'category' => 'pricing',
                'sort_order' => 2,
                'is_public' => true,
            ],
            [
                'setting_key' => 'pricing.fee_tiers',
                'setting_value' => '[]',
                'setting_type' => 'json',
                'description' => 'Tiered fees for amounts above free threshold',
                'category' => 'pricing',
                'sort_order' => 3,
                'is_public' => true,
            ],
            [
                'setting_key' => 'pricing.fx_margin_bps',
                'setting_value' => '450',
                'setting_type' => 'integer',
                'description' => 'FX margin in basis points applied to interbank rate (XAF→NGN)',
                'category' => 'pricing',
                'sort_order' => 4,
                'is_public' => true,
            ],
            [
                'setting_key' => 'pricing.quote_ttl_secs',
                'setting_value' => '90',
                'setting_type' => 'integer',
                'description' => 'Quote time-to-live in seconds for pricing v2 (fallback to QUOTE_TTL_SECONDS when not set)',
                'category' => 'pricing',
                'sort_order' => 5,
                'is_public' => true,
            ],
            
            // Mobile API Settings
            [
                'setting_key' => 'mobile_api_enabled',
                'setting_value' => '1',
                'setting_type' => 'boolean',
                'description' => 'Enable mobile API endpoints for mobile applications',
                'category' => 'api',
                'sort_order' => 1,
                'is_public' => false,
            ],
            [
                'setting_key' => 'kyc_enabled',
                'setting_value' => '1',
                'setting_type' => 'boolean',
                'description' => 'Enable KYC verification flow for users',
                'category' => 'api',
                'sort_order' => 2,
                'is_public' => true,
            ],

            // Company Information
            [
                'setting_key' => 'company_name',
                'setting_value' => 'TexaPay',
                'setting_type' => 'string',
                'description' => 'Company name',
                'category' => 'company',
                'sort_order' => 1,
                'is_public' => true,
            ],
            [
                'setting_key' => 'company_address',
                'setting_value' => 'Douala, Cameroon',
                'setting_type' => 'string',
                'description' => 'Company address',
                'category' => 'company',
                'sort_order' => 2,
                'is_public' => true,
            ],
            [
                'setting_key' => 'app_version',
                'setting_value' => '1.0.0',
                'setting_type' => 'string',
                'description' => 'Application version',
                'category' => 'company',
                'sort_order' => 3,
                'is_public' => true,
            ],
        ];

        foreach ($settings as $setting) {
            AdminSetting::updateOrCreate(
                ['setting_key' => $setting['setting_key']],
                $setting
            );
        }

        $this->command->info('Admin settings seeded successfully.');
    }
}
