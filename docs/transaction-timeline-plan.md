# Transaction Timeline, USSD Guidance, and Receipt Actions – Technical Plan

## Current State Mapping (from repository)
- **Models**
  - `App\Models\Transfer` stores core lifecycle fields and `timeline` JSON. Key columns: `status`, `payin_status`, `payin_ref`, `payin_at`, `payout_status`, `payout_ref`, `payout_initiated_at`, `payout_attempted_at`, `payout_completed_at`, `last_payout_error`, `refund_*`, `timeline`.
  - `App\Models\Quote` stores quote snapshot, status `active|expired|consumed|canceled`, `quote_ref`, `expires_at`.
  - `App\Models\WebhookEvent` persists webhook idempotency and payloads.
- **Migrations**
  - `2025_09_25_233450_create_transfers_table.php` creates `transfers` with `status` default `quote_created` and `timeline` JSON.
  - `2025_09_26_083109_add_payout_safety_fields_to_transfers_table.php` adds idempotency and error fields for payout.
  - `2025_09_25_233435_create_quotes_table.php` for quotes.
  - `2025_09_28_010000_create_webhook_events_table.php` for webhook events.
- **Controllers / Jobs / Services**
  - `TransferController` orchestrates the flow: bank verification → quote → confirm pay-in (PawaPay) → receipt → poll pay-in → initiate payout → poll payout. It appends to `timeline` in multiple places and redirects to the receipt.
  - `Webhooks\PawaPayWebhookController` accepts deposit webhooks; logs, stores a `WebhookEvent`, then dispatches `ProcessPawaPayDeposit` (sync in dev via `WEBHOOKS_SYNC`).
  - `Jobs\ProcessPawaPayDeposit` normalizes statuses and updates the `timeline` with events such as `payin_webhook_received`, `payin_completed`, `payin_failed`, `payin_pending`, then calls payout initiation.
  - `Jobs\ProcessPawaPayRefund` updates timeline for refund webhook.
  - `Services\PawaPay` handles HTTP to PawaPay (auth headers, CA bundle), exposes deposit creation and status, with normalization of statuses and friendly failure messages.
  - `Services\SafeHaven` handles OAuth and payout initiation/status.
- **Routes** (`routes/web.php`)
  - Authenticated user flow under `/transfer`: `bank` → `quote` → `confirm` → `receipt/{transfer}`.
  - Polling endpoints: `POST /transfer/{transfer}/payin/status` and `POST /transfer/{transfer}/payout/status`.
  - `POST /transfer/{transfer}/payout` to trigger payout.
  - Transactions index and show reuse `showReceipt`.
- **Views**
  - `resources/views/transfer/quote.blade.php`: quote page and confirmation CTA.
  - `resources/views/transfer/bank.blade.php`: bank/name-enquiry form.
  - `resources/views/transfer/receipt.blade.php`: rich receipt UI with timeline styling already present; currently rendered server-side and refreshed via redirects. No realtime client push; no USSD guidance; no shareable link or PDF download.

## Gaps vs Desired Outcomes
- No explicit, live client updates; polling exists for pay-in/payout but not wired to auto-refresh timeline without page reload.
- USSD guidance for MTN/Orange isn’t shown immediately after Pay.
- Receipt actions: “View Receipt” exists (page), but no PDF download or shareable signed link.
- Status names differ from the specified labels. Existing timeline entries are lower_snake (e.g., `payin_webhook_received`) and overall `status` values like `quote_created`, `payin_pending`, `payout_pending`, `payout_success`, `failed`.

## Proposed Changes (minimal, modular, reversible)
- **Data model**
  - Reuse `transfers.timeline` to persist all events. No new tables required.
  - Ensure events added at missing points: `quote_created`, `payin_initiated`, `payout_initiated`, `completed`, plus failure variants.
  - Keep UTC in DB; convert to Africa/Douala client-side for display.
- **Event strategy**
  - Append to `timeline` on:
    - Quote confirmation: `QUOTE_CREATED` (if not already), and `PAYIN_INITIATED` when creating deposit with PawaPay.
    - Pay-in webhook: `PAYIN_WEBHOOK_RECEIVED`, `PAYIN_COMPLETED`, `PAYIN_FAILED`, `PAYIN_PENDING` as appropriate (already partially implemented; add uppercase mapping for UI).
    - Payout start/finish: `PAYOUT_INITIATED`, `COMPLETED` or `PAYOUT_FAILED`.
    - Refunds: keep existing `refund_webhook_received` and optionally map to friendly display.
- **Realtime UI (no new stack)**
  - Add a small JS module on `receipt.blade.php` that polls a new JSON endpoint every 3s for `transfer` state: `GET /transfer/{transfer}/timeline` returning `{ id, status, payin_status, payout_status, timeline: [] }`.
  - Update the DOM timeline incrementally; stop polling once terminal (`COMPLETED` or `FAILED`). Show “still waiting” copy when pending too long.
- **USSD guidance**
  - Immediately after `confirmPayIn`, redirect to `receipt` with a session flag. On the receipt, if `status` is in a pay-in/pending phase, display the USSD panel with exact copy:
    - “Please confirm the payment on your phone.”
    - MTN MoMo: Dial `*126#` → Pending Approvals → Approve
    - Orange Money: Dial `#150#` → Pending Approvals → Approve
    - “We’ll update each step here in real time.”
- **Receipt actions**
  - Add a new route `GET /transfer/{transfer}/receipt/pdf` to generate a single-receipt PDF (DomPDF if available; HTML fallback).
  - Add a signed share route `GET /s/receipt/{transfer}` using Laravel signed routes. Generate temporary signed links (e.g., 7 days) for sharing. The shared view hides sensitive PII and is read-only.
  - Add “Copy link” (signed URL) and OS share button if supported.
- **Feature flags**
  - Use existing admin settings system to gate features:
    - `show_transaction_timeline` (default true)
    - `enable_receipt_pdf` (default true)
    - `enable_receipt_share` (default true)
  - Read via existing `AdminSetting`/`Setting` resources if present; otherwise `.env` fallbacks.

## State Mapping for Timeline (UI labels)
- Map persisted states to display order and labels (upper = canonical, left = existing):
  - `quote_created` → QUOTE_CREATED → “Quote Created”
  - `payin_pending`, `payin_status_check` → PAYIN_PENDING → “Payin Pending”
  - on deposit creation (`confirmPayIn`) → PAYIN_INITIATED → “Payin Initiated”
  - `payin_webhook_received` → PAYIN_WEBHOOK_RECEIVED → “Payin Webhook Received”
  - `payin_completed` or `payin_status=success` → PAYIN_COMPLETED → “Payin Completed”
  - payout start (`initiatePayout`) → PAYOUT_INITIATED → “Payout Initiated”
  - payout finished success (`payout_status=success`) → COMPLETED → “Completed”
- Error/edge states (friendly copy):
  - `payin_failed` → PAYIN_FAILED
  - `payout_failed` or `last_payout_error` → PAYOUT_FAILED
  - `expired` → EXPIRED
  - `cancelled` → CANCELLED
  - background queued states (if any async delay) → QUEUED_WITH_ETA

## API/Controller Adjustments
- `TransferController::confirmPayIn()`
  - After deposit creation, push timeline event `{ state: 'payin_initiated', at: now() }` and set `status='payin_pending'`.
- `TransferController::initiatePayout()`
  - On entry, append `{ state: 'payout_initiated', at: now() }` and set `status='payout_pending'`.
  - On success, append `{ state: 'completed', at: now() }`, set `status='payout_success'` and `payout_status='success'`.
  - On failure, append `{ state: 'payout_failed', at: now(), reason }` and set `status='failed'`.
- `TransferController::payinStatus()`
  - Already appends `payin_status_check`; keep as-is for polling fallback.
- New lightweight endpoint: `GET /transfer/{transfer}/timeline`
  - Returns JSON of the transfer (subset): id, status, payin_status, payout_status, timeline, updated_at.
  - Authenticated and owner-guarded.
- Receipt routes
  - `GET /transfer/{transfer}/receipt/pdf` → PDF download.
  - `GET /s/receipt/{transfer}` → signed route to view receipt without auth; hides sensitive fields; expires.

## UI Placement and Behavior
- `resources/views/transfer/receipt.blade.php`
  - Add a USSD instruction panel at top if `status` in [quote_created, payin_pending] or while waiting for webhook completion.
  - Add a fixed “Timeline” section that renders from `timeline` using the mapping above; timestamps rendered in Africa/Douala (via JS `Intl.DateTimeFormat('fr-CM', { timeZone: 'Africa/Douala', ... })`).
  - Add JS to poll `/transfer/{id}/timeline` every 3 seconds until terminal.
  - Add actions row: View (current), Download PDF, Share (copy link / navigator.share if available). Hide actions via flags if disabled.

## Error/Timeout Handling
- If pending > N minutes, show helper text: “Still waiting… Please confirm on your phone or retry/cancel.” Provide a retry status check button that posts to existing `payin.status`/`payout.status` endpoints.
- Friendly messages for known PawaPay failure codes via `PawaPay::failureMessages`.
- Ensure idempotency via existing `WebhookEvent` and `payout_idempotency_key`.

## Security Considerations
- Use Laravel signed routes for `/s/receipt/{transfer}` with a short expiration (default 7 days) and minimal exposed data.
- Avoid exposing PII (mask account number, no user email/phone on shared view).
- Keep all times in UTC in storage; format in client with Africa/Douala.

## Minimal Schema/Data Examples
- `timeline[]` item shape (stored): `{ state: 'payin_webhook_received', at: '2025-09-29T15:07:00Z', meta?: { ... } }`
- `GET /transfer/{id}/timeline` response: `{ id, status, payin_status, payout_status, timeline: [ ... ], updated_at }`

## Settings
- `.env` (optional fallbacks)
  - `FEATURE_SHOW_TRANSACTION_TIMELINE=true`
  - `FEATURE_ENABLE_RECEIPT_PDF=true`
  - `FEATURE_ENABLE_RECEIPT_SHARE=true`
- Admin Settings (if present) keys:
  - `show_transaction_timeline`
  - `enable_receipt_pdf`
  - `enable_receipt_share`

## Rollout & QA Checklist
- Enable features in a staging environment.
- Simulate full happy path: quote → pay-in (pending → webhook completed) → payout → completed. Observe live timeline steps.
- Simulate pay-in failure and payout failure; verify friendly messages and terminal states.
- Refresh during pending and after completion; confirm timeline reconstructs from stored events.
- Verify signed share link opens read-only receipt and expires as configured.
- Verify PDF download works (DomPDF installed) or HTML fallback is returned gracefully.
- Confirm no regressions in existing flows; routes remain backward-compatible.

## Implementation Notes
- No new libraries are introduced. Polling is implemented with plain JS on the receipt page.
- All additions are guarded by feature flags and degrade gracefully if disabled.
- Naming adheres to existing conventions in `TransferController`, `PawaPay`, and `SafeHaven` services.
