# Pricing Model v2 — Transparent FX & Minimal Fees

Date: 2025-09-29

## Executive Summary
- Introduce pricing_v2: small/zero fees for small transfers and a transparent, near-market FX margin.
- Show interbank rate, explicit margin %, and effective rate in quote responses and receipts.
- Keep straight-through flow: MoMo pay-in (PawaPay) → instant Safe Haven payout (no visible wallet).
- Feature-flagged rollout. Dynamic, admin-controlled configuration. Preserve backward compatibility when disabled.

## What the Code Currently Does (Verified)
- Quote creation (`TransferController::createQuote()`):
  - Fetches USD-base FX via `OpenExchangeRates::fetchUsdRates()` → `usd_to_xaf`, `usd_to_ngn`, computes `cross = usd_to_ngn / usd_to_xaf`.
  - Applies margin using env `FX_MARGIN_BPS` → `adjusted_rate_xaf_to_ngn = cross * (1 - margin_bps/10000)`.
  - Fees from env:
    - `FEES_FIXED_XAF` and `FEES_PERCENT_BPS` → `fee_total_xaf`.
    - `FEES_CHARGE_MODE` on_top vs net-from-amount.
  - Computes `total_pay_xaf`, `receive_ngn_minor` and persists `Quote` with TTL `QUOTE_TTL_SECONDS` (default 90s).
- Pay-in (MoMo via `App\Services\PawaPay`):
  - `initiatePayIn()` uses `amount_xaf_minor` derived from `quote.total_pay_xaf`, status ACCEPTED→pending, error-mapping handled.
  - Webhook/status polling handled elsewhere; statuses normalized.
- Payout (Safe Haven via `App\Services\SafeHaven`):
  - `initiatePayout()` uses locked `Transfer.receive_ngn_minor`; idempotent, timeline updated, success/failure handled, optional refund.
- Storage and audit:
  - `quotes` and `transfers` store: `usd_to_xaf`, `usd_to_ngn`, `cross_rate_xaf_to_ngn`, `adjusted_rate_xaf_to_ngn`, fees and amounts; TTL applied at use.
- Admin/settings:
  - Pricing currently from env only (no DB-backed settings surfaced in admin for pricing fields).

Verification Notes: Found (quote math & env-driven margin/fees). Partially Found (admin dynamic config). Found (TTL enforcement). Found (payout uses locked quote values).

## Target Policy (pricing_v2)
- Fees
  - 0 fee for small transfers up to a configurable threshold (XAF).
  - Above threshold: tiered fees (flat/percent/cap) with clear disclosure.
- FX Margin
  - Transparent interbank rate and small margin in basis points.
  - Return: `interbank_rate`, `margin_percent`, `effective_rate`.
- USD-base computation
  - Continue USD→XAF & USD→NGN cross; quote TTL strictly honored.
- Admin Controls (dynamic)
  - `pricing.min_free_transfer_threshold_xaf`.
  - `pricing.fee_tiers` (array, ranges with flat/percent/cap).
  - `pricing.fx_margin_bps` (per corridor support future-proof).
  - `pricing.quote_ttl_secs` (or reuse existing TTL).
  - `pricing_v2.enabled` feature flag.
  - Optional: `pricing.cutoffs`, `pricing.holidays`.
- User-Facing Disclosure (API fields)
  - `interbank_rate`, `margin_percent`, `effective_rate`, `fee_amount`, `receive_amount`, `quote_expires_at`.
- Edge Cases
  - Rounding per currency; minor-unit precision; minimum-net receive guard; tiny amounts underflow prevention.
  - TTL expiry: quotes rejected on confirmation if expired (already enforced).
- Monitoring & Alerts
  - Emit metrics/logs for `pricing_v2_applied`, margin bps, fee amount, effective rate, corridor.
  - Alert on margin drift vs OXR, and on increased quote failure/expiry.
- Rollout Plan
  - Ship behind `pricing_v2.enabled=false` by default.
  - Stage in lower environments; enable for internal users; enable globally after monitoring.
  - Safe fallback: toggle OFF restores legacy behavior.

Verification Notes: Not Found (current admin UI for these pricing settings). Not Found (monitoring hooks). Found (TTL guard in confirm).

## Acceptance Criteria (Testable, Non-Technical)
- Transfers below free threshold have fee = 0; only FX margin applies.
- Quotes include and lock breakdown fields until TTL.
- Receipts/timeline reflect the exact numbers used.
- Toggling pricing_v2 OFF restores current/legacy pricing without code changes.
- No regressions in MoMo→payout straight-through flow.

## PHASE 2 — Implement (Feature-Flagged, Config-First)

Constraints
- No secrets exposed. No new wallet. All behind `pricing_v2.enabled`.
- DB-backed settings → config → env → defaults.

Settings (DB-backed, surfaced in admin)
- `pricing_v2.enabled` (bool, default false).
- `pricing.min_free_transfer_threshold_xaf` (default e.g., 50_000 XAF).
- `pricing.fee_tiers` (array of tiers: min/max XAF, flat, percent_bps, cap_xaf).
- `pricing.fx_margin_bps` (basis points; future: per corridor key like `XAF_NGN`).
- `pricing.quote_ttl_secs` (reuse existing QUOTE_TTL_SECONDS when not set).
- Optional: `pricing.cutoffs`, `pricing.holidays` (structure only).

Pricing Engine
- New centralized service `PricingEngine`:
  - Inputs: amount_xaf, corridor (XAF→NGN), market rates (USD-base), settings.
  - Outputs: `interbank_rate`, `margin_percent`, `effective_rate`, `fee_amount_xaf`, `receive_ngn_minor`.
  - Rules:
    - If amount ≤ min_free_threshold → fee_amount_xaf = 0.
    - Else select appropriate tier and compute fee with cap; support on_top/net modes as today.
    - Margin: apply bps to interbank_rate (buy side) → effective_rate.
    - Rounding: XAF in units, NGN in kobo; guard tiny amounts to avoid zero payouts when amount>0.

Quote API Update
- When `pricing_v2.enabled=true`, `POST /transfer/quote` response must include:
  - `interbank_rate`, `margin_percent`, `effective_rate`, `fee_amount`, `receive_amount`, `quote_expires_at`.
- Preserve existing fields to maintain backward compatibility.

Pay-in → Payout Path
- Lock computed `effective_rate` and `fee_total_xaf` into `Quote` → copied to `Transfer` upon initiation.
- Enforce TTL at `confirmPayIn()` (already done); require refresh if expired.

Receipts & Timeline
- Ensure receipt JSON and timeline objects include pricing breakdown for audit.

Admin
- Expose settings via existing admin/settings mechanism (maker-checker if present), with validations:
  - margin_bps: 0–300 bps; thresholds ≥ 0; tiers consistent and non-overlapping.

Tests
- Unit tests for `PricingEngine`:
  - below-threshold → fee=0; correct margin/rounding.
  - tiered fee selection and caps at boundaries.
  - underflow guards for tiny amounts.
- Integration tests for quote → pay-in webhook → payout:
  - `receive_ngn_minor` equals computed breakdown; TTL honored.

Metrics/Logs
- Log/emit: `pricing_v2_applied`, `fx_margin_bps`, `fee_amount_xaf`, `effective_rate`, `corridor`.
- Guardrail alert when margin drift vs OXR exceeds tolerance or quote failures spike.

Migration & Safety
- Add migrations or seed defaults for new settings table/keys.
- All wrapped in `pricing_v2` flag; OFF mode retains current env-based behavior.

Admin Playbook (Appendix)
- How to change thresholds/margins safely, test in staging, and rollback by toggling flag or reverting settings to defaults.

## PHASE 3 — Output & PR Hygiene
- Branch: `feature/pricing-v2-transparent-fx`.
- PR includes:
  - Summary of policy with screenshots of `/docs/pricing-model-v2.md` rendered.
  - Test results.
  - Enabling instructions in admin settings.
- Commit messages: `pricing-v2: ...` prefix.

## Verification Notes Summary
- Quote math & env margin/fees: Found.
- Admin dynamic pricing config: Not Found.
- TTL enforcement: Found (rejects expired quotes on confirm).
- Payout uses locked quote values: Found.
- Monitoring hooks: Not Found.
