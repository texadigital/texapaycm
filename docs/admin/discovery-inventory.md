# Admin Discovery Inventory

This document inventories the existing Laravel 12 + Filament 4 codebase with evidence-backed findings. All claims cite file paths and line ranges.

## Repository Map
- **Routes**
  - `routes/api.php:L16-L235` Mobile JSON API (JWT guarded via `auth.jwt`, throttles, idempotency, AML/limits middleware). Health endpoints for providers.
  - `routes/web.php:L8-L51` Transfer flow (bank -> quote -> confirm -> receipt) with `check.limits` and `check.aml` middlewares.
  - `routes/web.php:L114-L151` Webhooks for PawaPay deposits/payouts and Smile ID.
  - `routes/mobile-api-enable.php:L6-L29` One-off route toggling `admin_settings.mobile_api_enabled`.
- **Middleware** (`app/Http/Middleware/`)
  - `CheckUserLimits.php` limit enforcement (see discovery under Limits section).
  - `CheckAml.php` AML checks placeholder/enforcement.
  - `EnforceIdempotency.php` idempotency keys.
  - `EnsurePoliciesAccepted.php` policy acceptance gate (`routes/api.php:L105-L116,L207-L216`).
  - `ForceJson.php` forces JSON for API (`routes/api.php:L16-L21`).
  - `EnsureAdmin.php` Filament access (`app/Providers/Filament/AdminPanelProvider.php:L62-L66`).
- **Controllers** (`app/Http/Controllers/`)
  - Transfers Orchestration: `TransferController.php` end-to-end payin/payout/refund flow with provider integration and idempotency (`L46-L104`, `L252-L489`, `L505-L729`).
  - API mirrors: `Api/TransfersController.php` and `Api/PricingController.php` (see tunables below).
  - KYC: `Kyc/SmileIdController.php` endpoints used in routes (`routes/api.php:L110-L116`).
  - Profile/Security/Notifications/Support controllers referenced in routes.
  - Webhooks: `Webhooks/PawaPayWebhookController.php`, `PawaPayRefundWebhookController.php`, `PawaPayPayoutWebhookController.php` wired in `routes/web.php:L114-L141`.
- **Services**
  - Pricing: `app/Services/PricingEngine.php:L26-L77` (tiered fee, FX margin from AdminSetting).
  - Limits: `app/Services/LimitCheckService.php:L18-L87` (KYC gates via AdminSetting; caps by level `L105-L117`; stats/recording `L119-L143`, `L324-L348`, idempotent record `L354-L386`).
  - Providers:
    - PawaPay (Payin): `app/Services/PawaPay.php:L26-L46` env-config; `initiatePayIn()` `L178-L269`; `getPayInStatus()` `L276-L305`.
    - SafeHaven (Payout): `app/Services/SafeHaven.php:L24-L37` env-config; `nameEnquiry()` `L417-L464`; `payout()` `L471-L528`; `payoutStatus()` `L534-L572`.
  - FX Rates: `app/Services/OpenExchangeRates.php:L15-L17` env; `fetchUsdRates()` `L23-L145` with cache + fallback.
  - NotificationService, RefundService, PhoneNumberService referenced in `TransferController.php`.
- **Jobs/Queues** (`app/Jobs/`)
  - `ProcessPawaPayDeposit.php`, `ProcessPawaPayRefund.php`, `SendEmailNotification.php`, `SendPushNotification.php`, `SendSmsNotification.php` (queued mail/sms/push, deposit/refund processors). Grep confirms queue usage (`config/queue.php`, `config/horizon.php`).
- **Filament Admin**
  - Panel provider: `app/Providers/Filament/AdminPanelProvider.php:L30-L67` with `EnsureAdmin` and widgets.
  - Resources (non-exhaustive): `app/Filament/Resources/*` include Transfers, Settings, UserLimits, AML (AmlRule, AmlRulePack, AmlAlert, AmlStr), SupportTicket, Users, etc. Example: `app/Filament/Resources/AmlStrResource.php:L14-L96`.
  - Pages: `app/Filament/Pages/*` include `AmlSettings.php`, `KycSettings.php`.
  - Widgets: `app/Filament/Widgets/*` include `NearLimitUsersWidget.php`, `RecentCriticalUtilizationWidget.php`.
- **Models/Domain Entities** (`app/Models/`)
  - Core: `User.php`, `Transfer.php`, `Quote.php`, `DailyTransactionSummary.php`, `UserLimit.php`, `AdminSetting.php`, `WebhookEvent.php`, `PolicyAcceptance.php`.
  - AML/KYC: `AmlRule.php`, `AmlRulePack.php`, `AmlAlert.php`, `AmlAuditLog.php`, `AmlStr.php`, `KycProfile.php`, `ScreeningCheck.php`, `ScreeningResult.php`.
  - Notifications: `UserNotification.php`, `NotificationPreference.php`, `NotificationEvent.php`, `UserDevice.php`.
  - Support/Content: `SupportTicket.php`, `Faq.php`.
- **Migrations** (`database/migrations/`)
  - Users, jobs, tokens, devices, OTP, security, login history.
  - Transfers stack: `create_quotes_table` `2025_09_25_233435`, `create_transfers_table` `2025_09_25_233450`, safety fields `2025_09_26_083109`, refund fields `2025_09_26_100000`.
  - Settings: `create_settings_table` `2025_09_25_233839`, `create_admin_settings_table` `2025_09_28_031133`.
  - Summaries & limits: `create_daily_transaction_summaries_table` `2025_09_28_031145`, `create_user_limits_table` `2025_09_28_031116`.
  - Webhook events: `2025_09_28_010000_create_webhook_events_table.php`.
  - AML/KYC: multiple tables for `edd_cases`, `aml_alerts`, `screening_checks`, `screening_results`, `aml_rule_packs`, `aml_rules`, `aml_audit_logs`, `aml_strs` (e.g., `2025_10_10_153800_create_aml_strs_table.php`).

## AuthN/AuthZ Surfaces
- **Guards/Providers**: `config/auth.php:L16-L43` default `web` session guard; provider `users` eloquent `App\Models\User` `L62-L66`.
- **Sanctum**: Package present `composer.json:L18-L21` and `config/sanctum.php` referenced by grep; not directly used in routes (mobile uses JWT middleware `auth.jwt` `routes/api.php:L38-L39,L104-L107`).
- **Filament Access**: `User::canAccessPanel()` restricts to admins `app/Models/User.php:L271-L276`. `EnsureAdmin` middleware also used.
- **Policies**: No explicit Laravel policy classes found in discovery; admin gating via `is_admin` boolean and middleware.

## Domain Entities (selected)
- **Transfer** `app/Models/Transfer.php:L9-L45,L55-L65,L70-L90,L95-L106` fields include amounts, statuses, refs, timeline (json cast). Relations: `user()` `L50-L53`. Scopes: `needsRefund()`, `needsPayout()`, `ownedBy()`, `search()`, `dateBetween()`. Accessor `getReceiveNgnAttribute()`.
- **User** `app/Models/User.php:L24-L47,L65-L80` fields include admin, KYC, profile, notifications; soft deletes. Relations to `UserLimit`, `DailyTransactionSummary`, `UserSecuritySetting`, notifications/devices. Business methods: `getOrCreateLimits()` `L117-L134`, `canMakeTransaction()` `L139-L208`.
- **AdminSetting** `app/Models/AdminSetting.php:L13-L26` typed settings with caching; getters/setters `L31-L65`, category retrieval `L70-L86`, public settings `L91-L107`.
- **Pricing & Limits**: `PricingEngine::price()` uses `AdminSetting` keys `pricing.fx_margin_bps`, `pricing.min_free_transfer_threshold_xaf`, `pricing.fee_tiers` (`app/Services/PricingEngine.php:L41-L55`). `LimitCheckService` uses KYC feature flag `kyc_enabled` and caps for `kyc.level{0,1}.*` (`L21-L31`, `L105-L117`).

## Config Surfaces (env/config/constants)
- **Pricing legacy env** in `TransferController::createQuote()` `app/Http/Controllers/TransferController.php:L200-L214` uses `FX_MARGIN_BPS`, `FEES_FIXED_XAF`, `FEES_PERCENT_BPS`, `FEES_CHARGE_MODE`, `QUOTE_TTL_SECONDS` when `pricing_v2.enabled` is false.
- **Pricing v2 settings** via `AdminSetting` keys: `pricing_v2.enabled` (`L183-L201`), `pricing.fx_margin_bps`, `pricing.quote_ttl_secs` (`L216-L218`).
- **Provider routing**: env-driven `PAWAPAY_PROVIDER`, prefix ranges `PAWAPAY_CM_MTN_PREFIXES`, `PAWAPAY_CM_ORANGE_PREFIXES`, detection toggle `PAWAPAY_AUTODETECT_PROVIDER` (`TransferController.php:L296-L338`).
- **PawaPay**: `PAWAPAY_BASE_URL`, `PAWAPAY_API_KEY`, `PAWAPAY_API_VERSION`, optional CA bundle `PAWAPAY_CA_BUNDLE` (`app/Services/PawaPay.php:L26-L46`).
- **SafeHaven**: multiple envs for OAuth JWT and CA bundle (`app/Services/SafeHaven.php:L24-L37`, `L80-L93`).
- **OpenExchangeRates**: `OXR_BASE_URL`, `OXR_APP_ID`, `FALLBACK_XAF_TO_NGN`, `OXR_CACHE_TTL_MINUTES` (`app/Services/OpenExchangeRates.php:L15-L17,L25,L115-L121`).

## Operational Knobs (tunable parameters)
- **FX & Pricing**
  - `pricing.fx_margin_bps` (AdminSetting) and fallback `FX_MARGIN_BPS` (`PricingEngine.php:L41-L45`, `TransferController.php:L200-L206`).
  - `pricing.fee_tiers` (AdminSetting JSON tiers) and `pricing.min_free_transfer_threshold_xaf` (`PricingEngine.php:L46-L55`).
  - `FEES_FIXED_XAF`, `FEES_PERCENT_BPS`, `FEES_CHARGE_MODE` (env) (`TransferController.php:L205-L213`).
  - Quote TTL `pricing.quote_ttl_secs` or `QUOTE_TTL_SECONDS` (`TransferController.php:L216-L218`).
- **Limits & KYC**
  - Feature flag `kyc_enabled` (AdminSetting) (`LimitCheckService.php:L21-L25`).
  - Caps by level `kyc.level{0,1}.per_tx_cap_xaf`, `.daily_cap_xaf`, `.monthly_cap_xaf` (AdminSetting) (`LimitCheckService.php:L105-L117`).
- **Provider Routing**
  - `PAWAPAY_PROVIDER[_MTN/_ORANGE]`, `PAWAPAY_AUTODETECT_PROVIDER`, `PAWAPAY_CM_*_PREFIXES` (`TransferController.php:L296-L338`).
  - SafeHaven debit account `SAFEHAVEN_DEBIT_ACCOUNT_NUMBER` used in payout payload (`TransferController.php:L593-L596`).
- **Notifications**
  - Throttles for OTP and signup in routes (`routes/api.php:L48-L57,L95-L102`).
- **Queues/Horizon**
  - Horizon configured; queueable jobs present (see Jobs section).

## Background Processing
- **Jobs**: `app/Jobs/*` for deposits, refunds, and notifications (email/sms/push). Grep shows queue config `config/queue.php` and Horizon `config/horizon.php`.
- **Schedules**: `app/Console/Kernel.php` contains artisan schedules (not opened here; to be exposed in Admin widgets).
- **Idempotency**: `EnforceIdempotency` middleware and transfer creation idempotency checks (`TransferController.php:L265-L278,L340-L399`).

## API Surfaces (method/path/controller)
- See `routes/api.php:L16-L235` for mobile endpoints. Key paths:
  - Auth JWT: `/mobile/auth/login|refresh|logout|me` -> `Api/TokenAuthController`.
  - Signup/OTP: `/mobile/auth/signup/*` -> `Auth/SignupController` with throttles.
  - Pricing: `/mobile/pricing/limits` and `/mobile/pricing/rate-preview` -> `Api/PricingController`.
  - Transfers: list/show/feed/name-enquiry/quote/confirm/payin-status/payout/payout-status -> `Api/TransfersController` with `check.limits`,`check.aml`.
  - KYC endpoints: Smile ID start/token/status/edd.
  - Notifications and Devices management endpoints.
- Web routes mirror transfer flow and add webhooks (`routes/web.php:L114-L151, L175-L179`).

## Gaps / Admin Manageability
- **Env-driven pricing and provider routing**: Many tunables still in `.env` instead of DB-backed settings (FX margin env path, fee envs, provider codes/ranges) (`TransferController.php:L200-L214,L296-L338`).
- **Policies/RBAC**: No granular Laravel Policies present; administration gated by `is_admin` and middleware.
- **Schedules/Queues visibility**: No Admin pages to monitor queues, failed jobs, scheduled tasks.
- **Provider routing weights/failover**: No DB model to configure multiple providers/weights.
- **Feature Flags**: Only `AdminSetting` generic table; no typed feature flags registry.
- **Audit for Admin changes**: AML-specific audit exists (`AmlAuditLog`), but no general admin activity audit timeline.

## Suggested Admin-Controlled Surfaces (mapped to code)
- **Pricing & FX**: Manage `pricing.fx_margin_bps`, `pricing.fee_tiers`, `pricing.min_free_transfer_threshold_xaf`, `pricing.quote_ttl_secs` (PricingEngine, TransferController cites above).
- **Limits & KYC**: Manage `kyc_enabled`, and KYC caps `kyc.level*.per_tx/daily/monthly` (LimitCheckService cites above).
- **Provider Routing**: Move provider prefix ranges and selection to DB (replace `.env` driven detection in `TransferController.php:L296-L338`).
- **Feature Flags**: Add typed flags for `pricing_v2.enabled`, `mobile_api_enabled` (currently toggled via one-off route; move to Admin model).
- **Notifications**: Templates and event enable/disable, retries.
- **Ops**: Queue depth, failed jobs, webhook error rates, provider health (wrap existing health endpoints `routes/web.php:L154-L173` and `routes/api.php:L66-L84`).

---

This file will be expanded iteratively with more line-level citations as we wire Admin surfaces and add typed settings models.
