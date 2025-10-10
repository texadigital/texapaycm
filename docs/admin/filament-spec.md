# Filament v4 Admin Spec

This spec enumerates Admin screens with Resource names, columns, filters, actions, and policy mappings. It references code evidence with file:line citations.

## Sections & Screens

### Users
- **Resource**: `App\Filament\Resources\Users\UserResource`
- **List Columns**: id, name, email, phone, kyc_level, is_admin, created_at.
- **Filters**: KYC status/level; soft-deleted.
- **Row Actions**: View, Edit, Impersonate (SuperAdmin only), View Devices, View Login History.
- **Policies**: `UserPolicy` (TODO) with roles mapping.
- **Evidence**: `app/Models/User.php:L24-L47,L65-L80` fields; relations to devices, login history.

### Transfers & Ledger
- **Resource**: `App\Filament\Resources\Transfers\TransferResource`
- **List Columns**: id, user, amount_xaf, fee_total_xaf, receive_ngn_minor, payin_status, payout_status, status, created_at.
- **Filters**: status enums, date range, user email, bank code.
- **Row Actions**: View, Retry Payout (policy-gated), Refund (policy-gated).
- **Bulk**: Export CSV.
- **Policies**: `TransferPolicy` (TODO).
- **Evidence**: `app/Models/Transfer.php:L9-L45,L55-L65,L68-L106`.

### FX & Rates (Settings)
- **Resource**: `App\Filament\Resources\Settings\FxSettingsResource` (stub)
- **Form**: `pricing.fx_margin_bps` (int), `pricing.min_free_transfer_threshold_xaf` (int), `pricing.fee_tiers` (JSON editor), `pricing.quote_ttl_secs` (int).
- **Policies**: `FxSettingsPolicy` (TODO).
- **Evidence**: `app/Services/PricingEngine.php:L41-L55`, `app/Http/Controllers/TransferController.php:L183-L218`.

### Fees & Commissions
- **Resource**: `App\Filament\Resources\Settings\FeeTierResource` (stub)
- **Form**: Tiers per corridor with min/max/flat/percent_bps/cap_xaf.
- **Evidence**: `app/Services/PricingEngine.php:L87-L107` (tier computation).

### Limits & Risk Rules
- **Resources**: `UserLimitResource` (existing), `RiskRuleResource` (stub)
- **Widgets**: `NearLimitUsersWidget`, `RecentCriticalUtilizationWidget` (existing).
- **Evidence**: `app/Services/LimitCheckService.php:L21-L31,L105-L117,L324-L348`.

### KYC/KYB & Compliance
- **Resources**: `ScreeningResource`, `AmlRuleResource`, `AmlRulePackResource`, `AmlAlertResource`, `AmlStrResource` (existing).
- **Pages**: `AmlSettings` (existing settings page).
- **Evidence**: `app/Filament/Resources/AmlStrResource.php:L14-L96` and related.

### Providers & Routing
- **Resource**: `ProviderRouteResource` (stub)
- **Form**: corridor, priority/weight, MSISDN prefix rules, default provider per corridor.
- **Evidence**: `.env-driven detection` `app/Http/Controllers/TransferController.php:L296-L338`.

### Notifications & Templates
- **Resources**: `NotificationTemplateResource` (stub), `NotificationEventResource` (existing model `NotificationEvent.php`).
- **Evidence**: jobs `SendEmailNotification.php`, `SendSmsNotification.php`, `SendPushNotification.php`.

### Feature Flags
- **Resource**: `FeatureFlagResource` (new)
- **Form**: key, enabled, description, rollout % (optional), metadata JSON.
- **Evidence**: `pricing_v2.enabled` (`app/Http/Controllers/TransferController.php:L182-L201`), `mobile_api_enabled` (`routes/mobile-api-enable.php:L6-L29`).

### System Health
- **Widgets**: Queue depth, Failed jobs (Horizon), Provider health (PawaPay, SafeHaven), OXR status, Webhook errors.
- **Evidence**: health endpoints `routes/web.php:L154-L173`, `routes/api.php:L66-L84`, Horizon config.

## Filament Resource Specs (examples)

### FeatureFlagResource
- **Class**: `App\Filament\Resources\Settings\FeatureFlagResource`
- **Model**: `App\Models\Settings\FeatureFlag`
- **Columns**: key (searchable), enabled (toggle), description, rollout_percent, updated_at.
- **Filters**: enabled yes/no.
- **Actions**: Create, Edit, Delete (policy-gated), Toggle.
- **Policies**: `FeatureFlagPolicy` with roles: SuperAdmin full; Ops update; ReadOnly view.

### TransfersTable (existing reference)
- Evidence: `app/Filament/Resources/Transfers/Tables/TransfersTable.php` (columns/actions defined there).

## Search/Sort/Pagination
- All list pages use Filament Tables with server-side search/sort/pagination enabled by default.
- Soft deletes enabled for models with `SoftDeletes`.

## KPIs & Widgets (examples)
- **Cards**: Today’s volume, Success rate, Pending payins, Failed payouts (query `Transfer` scopes).
- **Provider Health**: Poll `SafeHaven::checkAuth()` and `PawaPay::checkAuth()` via lightweight controller endpoints.

## Routes
- Filament panel under `/admin` configured in `app/Providers/Filament/AdminPanelProvider.php:L30-L47`.

## Notes
- All sensitive actions (approve/reject/hold/release, run-job) will be policy-guarded with audit logging to `admin_activity_logs`.
