Title: Smile ID KYC Integration Plan (TexaPay – 2-Level Model)
Date: 2025-09-29

Executive Summary
- Why KYC: Comply with AML/CFT, curb fraud, unlock higher limits and corridors for trusted users while keeping Level 0 risk low.
- Smile ID flow (Biometric KYC): Collect ID image/number + selfie (+ optional liveness). Submit a job to Smile ID. Receive near-instant status updates via callbacks: pending, verified, failed.
- Outcomes mapping:
  - verified → upgrade to Level 1 and unlock higher caps & corridors
  - failed → allow retry/help flow; keep Level 0 caps
  - pending → keep Level 0 caps; notify when completed

What the Code Currently Does (Verified)
- Auth/Registration
  - `app/Http/Controllers/AuthController.php` handles phone+password login and registration; optional PIN challenge via `User.securitySettings`.
  - `app/Models/User.php` contains fields for basic profile and relationships: `userLimit`, `dailyTransactionSummaries`, `securitySettings`.
- Transaction Initiation
  - Flow: `routes/web.php` → `transfer` routes → `TransferController`.
  - Quote creation at `TransferController::createQuote()`; rate/pricing via `OpenExchangeRates` and optional pricing v2.
  - Transfer creation and pay-in with PawaPay in `confirmPayIn()`. Payout via SafeHaven in `initiatePayout()`.
- Limits Enforcement
  - Middleware `app/Http/Middleware/CheckUserLimits.php` applied to `transfer.quote.create` route.
  - Delegates to `app/Services/LimitCheckService.php` which reads per-user limits (`User->getOrCreateLimits()`, `User->canMakeTransaction()`), usage from `DailyTransactionSummary`.
  - Data models: `app/Models/UserLimit.php` and `app/Models/DailyTransactionSummary.php` with helpers to compute daily/monthly usage, counts, and to record successful transactions.
- Admin Settings Pattern
  - `app/Models/AdminSetting.php` provides DB-backed settings with caching and type casting.
  - Filament resources exist for editing settings and limits, e.g., `app/Filament/Resources/AdminSettings/*` and `app/Filament/Resources/UserLimits/*`.
- KYC Artifacts (Early Stub)
  - `database/migrations/2025_09_25_233423_create_kyc_profiles_table.php` creates `kyc_profiles` with minimal columns (`status` defaults to verified). Placeholder `app/Models/KycProfile.php` is empty.
  - No Smile ID integration, tokens, or webhooks are present.

Verification Notes
- Found
  - `CheckUserLimits` middleware gating quote creation.
  - Per-user limits (`UserLimit`) + usage tracking (`DailyTransactionSummary`).
  - Admin DB settings framework (`AdminSetting` + Filament resources).
  - Clean transfer lifecycle and webhook-based state updates (PawaPay, SafeHaven) to mirror for Smile ID.
- Partially Found
  - `KycProfile` schema exists but is not wired to users’ transactional gates, nor Smile ID.
  - Admin limits exist per-user, but not per KYC level globally.
- Not Found
  - No `kyc_level`, `kyc_status`, `kyc_provider_ref`, `kyc_verified_at`, `kyc_meta`, `kyc_attempts` on `users`.
  - No Smile ID client/server integration: token/signature generation, SDK bootstrap, callback/webhook handler.
  - No admin-configurable KYC caps per level; no feature flag.
- Next Steps (one-liners)
  - Add KYC fields to `users` and wire to `User` casts.
  - Create Smile ID service + controller for tokens and callbacks.
  - Insert KYC gate into `CheckUserLimits` based on `kyc_level` and admin caps with feature flag.
  - Add Admin settings forms for KYC flag and level caps.
  - Persist verification outcomes and map to user upgrades/downgrades.

Target User Flow (2 levels only)
- New users start at Level 0 (Unverified) with small caps (per-tx/daily/monthly) in XAF.
- If a requested quote/transfer exceeds Level 0 caps and KYC is enabled, block and prompt KYC.
- KYC collection via Smile ID Web/Flutter SDK (ID + selfie + liveness optional) using a server-issued token.
- After submission:
  - verified → auto-upgrade user to Level 1; show success; allow retry of the transaction.
  - failed → show reason and allow retry/help.
  - pending → show “Verifying…” status; notify on completion.

System Changes Needed
- Data Model (User)
  - Add: `kyc_level` (int; 0/1), `kyc_status` (enum: unverified|pending|verified|failed), `kyc_provider_ref` (string), `kyc_verified_at` (datetime), `kyc_meta` (json), `kyc_attempts` (int).
  - Minimal use of `KycProfile` for audit if needed; primary KYC flags live on `users` for gate performance.
- Smile ID Integration (Server)
  - Service to generate Smile ID signatures/tokens for client SDK.
  - Endpoint to start KYC session: returns token/ref, job params, and callback URL.
  - Callback/Webhook endpoint to receive result payloads, verify signature, upsert `kyc_provider_ref`, update `kyc_status`, `kyc_level`, `kyc_verified_at`, store `kyc_meta` (subset of Actions, ResultCode/Text, ID type/number, country = CM).
  - Error/result mapping table to unify Smile ID statuses → TexaPay statuses.
- Transaction Gate
  - In `CheckUserLimits`/`LimitCheckService`, before evaluating per-user `UserLimit`, select dynamic caps based on `kyc_level` and admin configuration when `kyc_enabled=true`.
  - If user over Level 0 cap and `kyc_enabled=true` → respond with “KYC required” error and a deep-link/action hint to the KYC screen.
- Admin
  - DB-backed settings for KYC: feature flag and level caps.
  - Filament pages to view users’ KYC state, manual override (promote/demote), and editor for caps.
  - Optional corridor allowlists by level.

Dynamic Limits (Admin-controlled)
- Keys (stored via `AdminSetting`):
  - `kyc_enabled` (boolean; default false)
  - `kyc.level0.per_tx_cap_xaf`, `kyc.level0.daily_cap_xaf`, `kyc.level0.monthly_cap_xaf`
  - `kyc.level1.per_tx_cap_xaf`, `kyc.level1.daily_cap_xaf`, `kyc.level1.monthly_cap_xaf`
  - (Optional) `kyc.level0.allowed_corridors`, `kyc.level1.allowed_corridors` (json arrays)
- Selection logic:
  - If `kyc_enabled=false` → legacy behavior unaffected (use current per-user `UserLimit`).
  - If `kyc_enabled=true` → compute effective caps from admin KYC caps by level; `UserLimit` remains for per-user overrides or additional enforcement.

UX Copy & States
- Under Level 0 cap: “You’re under your current limit. You can proceed.”
- KYC required: “To send this amount, please verify your identity (1–2 minutes).”
- Verifying…: “We’re verifying your identity. This may take a moment.”
- Verified: “You’re verified. Higher limits unlocked.”
- Retry: “We couldn’t verify your identity. Please try again or contact support.”

Monitoring & Alerts
- Log events: `kyc_prompted`, `kyc_started`, `kyc_verified`, `kyc_failed`, `kyc_pending`, `kyc_webhook_error`.
- Metrics: failed verifications, users hitting caps, average time-to-verify, webhook error rate, drop-offs.
- Alerts: webhook signature failures, repeated failed attempts per user, long-pending verifications.

Rollout Plan & Feature Flag
- Default `kyc_enabled=false`.
- Stage 1: Enable in sandbox/internal test accounts only.
- Stage 2: Enable for a percentage of users or corridor (CM→NG) with lower Level 0 caps.
- Rollback: Set `kyc_enabled=false` → system instantly reverts to legacy gating.

Acceptance Criteria (testable)
- Every transaction checks `kyc_level` vs caps when `kyc_enabled=true`.
- Smile ID “success” upgrades user to Level 1; failed leaves Level 0; pending leaves Level 0.
- Admin can edit caps and toggle enforcement via settings; overrides user status from Filament.
- With flag OFF, behavior matches current production flows and limits.

Proposed Insertion Points (Code)
- KYC Data
  - Migration to add KYC columns to `users`.
  - Extend `app/Models/User.php` casts/fillable accordingly.
- KYC Gate
  - `app/Http/Middleware/CheckUserLimits.php`: short-circuit with KYC checks and dynamic caps selection (read from `AdminSetting`).
  - `app/Services/LimitCheckService.php`: expose helper to compute effective caps by level.
- Smile ID Integration
  - New service `SmileIdService` (server-side signature/token); controller `SmileIdController` for start-session and webhook.
  - `routes/web.php`: `POST /api/kyc/smileid/start` (auth), `POST /api/kyc/smileid/callback` (public, CSRF exempt; signature verified).
- Admin
  - Filament: Settings section for KYC caps and flag; User detail page section to view/override KYC fields.

Cameroon Context
- Currency is XAF; existing flows already normalize Cameroon MSISDN formats in `TransferController::confirmPayIn()`.
- Smile ID job should set country “CM” and support relevant ID types (National ID, Passport, etc.).

Implementation Checklist (Phase 3)
- Data & Settings
  - Add `users` fields: `kyc_level`, `kyc_status`, `kyc_provider_ref`, `kyc_verified_at`, `kyc_meta` (JSON), `kyc_attempts`.
  - Seed defaults: `kyc_enabled=false`; reasonable Level 0 caps for Cameroon.
- Smile ID Integration
  - Signature/token generator.
  - Start-session endpoint.
  - Callback/webhook → update user status and metadata.
- Transaction Gate
  - Before quote/transfer finalize, compare with level caps; if > Level 0 and `kyc_enabled=true`, block with “KYC required” and deep-link to KYC.
- Admin Dashboard
  - Caps editor and flag; KYC status view and override.
- Notifications
  - Email/SMS/push for prompts, success/failure, pending updates.
- Tests
  - Unit: cap selection by level; status transitions; webhook mapping.
  - Integration: Level 0 → attempt high amount → KYC → Level 1 → transaction succeeds.
  - Flag OFF = legacy behavior unchanged.
- Metrics/Logs
  - Emit structured logs and counters for KYC lifecycle and webhook errors.

Commits
- docs: add Smile ID KYC integration plan (2-level model)
- feat(kyc): add Smile ID 2-level KYC data model & settings
- feat(kyc): integrate Smile ID SDK + callbacks
- feat(kyc): enforce admin caps by KYC level
- chore(admin): KYC caps & status management
- test(kyc): unit & integration coverage
