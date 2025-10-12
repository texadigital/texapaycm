# Admin Wiring Plan

This plan defines the Admin (Filament) Information Architecture, RBAC model, source-of-truth decisions, standardized patterns, observability, and a minimal migration strategy. All controls map to code with citations.

## Information Architecture
- **Users**
  - Screens: Users list/detail/edit; Login history; Devices.
  - Backed by: `app/Models/User.php`, `UserDevice.php`, `LoginHistory.php`.
  - Evidence: routes and relations `app/Models/User.php:L82-L134,L242-L257`.
- **Roles & Permissions**
  - Minimal: `is_admin` boolean today `app/Models/User.php:L271-L276` + `EnsureAdmin` (`app/Providers/Filament/AdminPanelProvider.php:L62-L66`).
  - Plan: Introduce per-resource Policies; role set below.
- **Transfers & Ledger**
  - Screens: Transfers table with timeline and payout/payin statuses; Export.
  - Backed by: `app/Models/Transfer.php` with scopes `L68-L106` and accessors `L151-L156`.
  - Evidence: orchestration `app/Http/Controllers/TransferController.php:L46-L104,L252-L489,L505-L729`.
- **FX & Rates**
  - Screens: Pricing settings (FX margin bps, fee tiers, free threshold, quote TTL), FX health (OXR status).
  - Backed by: `app/Services/PricingEngine.php:L41-L55`, `app/Http/Controllers/TransferController.php:L183-L218`, `app/Services/OpenExchangeRates.php:L23-L145`.
- **Fees & Commissions**
  - Screens: Fee tiers CRUD per corridor.
  - Backed by: `AdminSetting` JSON tiers today (`app/Services/PricingEngine.php:L46-L55`). Move to typed `fees` table.
- **Limits & Risk Rules**
  - Screens: User limits, KYC caps by level, utilization widgets.
  - Backed by: `app/Services/LimitCheckService.php:L21-L31,L105-L117,L324-L348`, `app/Models/UserLimit.php` (existing), widgets present `app/Filament/Widgets/NearLimitUsersWidget.php`.
- **KYC/KYB**
  - Screens: KYC Profiles, Smile ID checks, EDD cases, STRs.
  - Backed by: `app/Models/KycProfile.php`, `ScreeningCheck.php`, `ScreeningResult.php`, `EddCase.php`, `AmlStr.php` and Filament resources (`app/Filament/Resources/*Aml*`, `AmlStrResource.php:L14-L96`).
- **Providers & Routing**
  - Screens: Provider routes/weights; MSISDN prefix rules for PawaPay; SafeHaven debit account.
  - Evidence of env-driven logic: `app/Http/Controllers/TransferController.php:L296-L338`, `L593-L596`.
- **Notifications & Templates**
  - Screens: Notification templates, event toggles, channel preferences defaults.
  - Backed by: `NotificationEvent.php`, `NotificationPreference.php`, jobs `Send*Notification.php`.
- **Feature Flags**
  - Screens: Flags with rollout and metadata (e.g., `pricing_v2.enabled`, `mobile_api_enabled`).
  - Evidence: `pricing_v2.enabled` `app/Http/Controllers/TransferController.php:L182-L201`; mobile toggle route `routes/mobile-api-enable.php:L6-L29`.
- **System Health**
  - Screens: Queues depth/failed jobs (Horizon), provider health (PawaPay/SafeHaven), OXR status, webhook error rates.
  - Evidence: health routes `routes/web.php:L154-L173`, `routes/api.php:L66-L84`; Horizon present `composer.json:L18-L19` and `config/horizon.php`.

## RBAC Model (least-privilege)
- **Roles**
  - SuperAdmin: full access.
  - Compliance: AML/KYC (view/manage EDD, STR, rules), read-only Transfers.
  - Ops: Transfers, Provider routes, Limits (operational), view health.
  - Support: Users read, resend notifications, view transfers.
  - Finance: Fees, pricing approval, reconciliation.
  - ReadOnly: read-only across.
- **Permissions per action** (mapped to Policies)
  - view/create/update/delete/export/run-job/approve/reject/hold/release.
  - Implement via per-model `Policy` classes with gate names; start with FeatureFlag/Fees/ProviderRoute; extend.

## Source of Truth Decisions
- **Remain in .env**: secrets, hostnames, API keys
  - `PAWAPAY_API_KEY`, `SAFEHAVEN_*` secrets, `OXR_APP_ID`.
- **Move to DB settings (typed)**
  - Pricing: fx_margin_bps, fee tiers, free threshold, quote TTL (from `AdminSetting` → typed tables `fx_spreads`, `fees`).
  - Limits: KYC caps per level (from `AdminSetting`) and per-user limits remain in `user_limits`.
  - Provider routing: PawaPay MSISDN prefix rules and defaults; SafeHaven debit account (non-secret).
  - Feature flags: `pricing_v2.enabled`, `mobile_api_enabled`.
- **Versioning & Audit**
  - Append-only `admin_activity_logs` for admin changes; show timeline in Admin.

## Patterns to Standardize
- **Typed Settings Registry**
  - `app/Models/Settings/*` + caching; invalidate on save.
  - Continue `AdminSetting` for legacy; migrate gradually.
- **Provider Abstraction**
  - Store routes/weights, prefix rules, toggles in `provider_routes` table; read in detection path currently in `TransferController.php:L296-L338`.
- **Limits & Risk Rules as Data**
  - Tables for `limit_rules` and `risk_rules` with expressions/thresholds; initial minimal schema.
- **Audit Logs**
  - `admin_activity_logs` capturing user id, model, action, diff JSON.

## Observability & Ops
- **Queues & Horizon**: widgets to read counts/failed via Horizon API; expose retries (UI actions call `artisan horizon:...`).
- **Failed Jobs & Webhooks**: list last N failed jobs; list `webhook_events` table records.
- **Provider Health**: wrap existing health endpoints in widgets (`routes/web.php:L154-L173`, `routes/api.php:L66-L84`).
- **CSV Exports**: table exports for Transfers and DailyTransactionSummary; include checksum column.

## Migration Plan (additive, reversible)
1. Create `admin-wiring` branch.
2. Add typed settings models/migrations (feature_flags, fees, fx_spreads, limit_rules, provider_routes, notification_templates, ledger_controls, reconciliation_rules, admin_activity_logs). No prod logic changes yet.
3. Scaffold Filament Resources stubs guarded by Policies. Keep forms read-only initially; add TODOs.
4. Introduce Policies with deny-by-default except SuperAdmin.
5. Add health widgets querying existing endpoints.
6. Gradually refactor read-paths to prefer typed settings, with env/AdminSetting fallback. Behind feature flags.

## php artisan helper commands
- Register policies in `AuthServiceProvider` (TODO notes in stubs).
- Run migrations after review.

