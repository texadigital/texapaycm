# Transactions UX and Implementation Plan

This plan proposes user-facing and backend changes to deliver the requested experience:

- After login, user can see a prominent "Send" button.
- The dashboard shows the most recent transactions with a "See all transactions" button.
- The full transactions page lists all user transactions, supports searching, shows total XAF sent overall, lets the user export as PDF, and links to a detailed transaction view.
- A transaction details page shows breakdown: amount sent in XAF, fees, effective exchange, amount received in NGN, recipient details, pay-in and payout states, and timeline.

Please review and approve before implementation.

---

## Current State (from codebase review)

- Routes in `routes/web.php` already guard transfer flow with `auth` middleware and send `/` to `transfer.bank`.
- `DashboardController@index` returns `resources/views/dashboard/index.blade.php` with a paginated table of the current user’s transfers.
- Transfer creation and processing live in `App\Http\Controllers\TransferController` and `App\Models\Transfer`.
- Views under `resources/views/transfer/` include:
  - `bank.blade.php` (recipient verification)
  - `quote.blade.php` (FX + fees)
  - `receipt.blade.php` (status/timeline)

Gap: no dedicated "all transactions" page, no search on list, no total sum display, no export-to-PDF, and the dashboard currently doesn’t show a primary "Send" CTA or a direct "See all transactions" link.

---

## Proposed UI/UX

1. Dashboard (`/dashboard`)
   - Add a top-right primary button: "Send" -> `route('transfer.bank')`.
   - Show only the last 5 transactions for the logged-in user (most recent first).
   - Below the list, add a secondary action button: "See more transactions" -> `/transactions` (new route).
   - Keep the table compact; no pagination here since we cap at 5.

2. Transactions Index (`/transactions`)
   - Auth required.
   - Page sections:
     - Header: title.
     - Search bar with placeholder: "Search by ID, bank, account, status…".
     - Results: paginated table with columns: ID, Overall Status, Amount (XAF), Bank, Account, Created, Actions.
     - Actions:
       - View details
       - Export all as PDF (respects current search filter and date range, if provided).
   - Query parameters: `q` (search), `from` and `to` (optional date range), `status` (optional filter), `page` (pagination).

   - Footer: Display "Total Sent (XAF)" across the user's entire history (not affected by current search filters), shown at the bottom of the page. See Totals Decision below for which field to sum.

3. Transaction Details (`/transactions/{transfer}`)
   - Auth required + authorization: only owner can view.
   - Show sections:
     - Top summary: Amount Sent XAF, Fee XAF, Total Pay XAF, Adjusted Rate (XAF->NGN), Receive NGN (converted from `receive_ngn_minor`).
     - Recipient: bank code/name, account number (masked), account name.
     - Pay-in: provider, ref, status, time.
     - Payout: provider, ref, status, attempted/completed times, last error (if any).
     - Timeline: state entries from `timeline` cast.
     - FX context: `usd_to_xaf`, `usd_to_ngn`, `fx_fetched_at`.

4. Export PDF
   - Button on `/transactions` to export current list as PDF (includes applied filters/search).
   - Simple tabular layout with a header, optional filter summary, totals row at top or bottom.

---

## Backend Design

1. Routes (new)

Add under `auth` middleware in `routes/web.php`:
- GET `/transactions` -> `TransactionsController@index` name: `transactions.index`
- GET `/transactions/export` -> `TransactionsController@exportPdf` name: `transactions.export`
- GET `/transactions/{transfer}` -> `TransactionsController@show` name: `transactions.show`

2. Controller (new) `App\Http\Controllers\TransactionsController`

- `index(Request $request)`
  - Filters: `q`, `from`, `to`, `status`.
  - Base query: `Transfer::where('user_id', auth()->id())` with search scope.
  - Compute `total_sent_xaf` across the same base query but without pagination (see Decision below).
  - Return view `transactions.index` with: `transfers` (pagination 15/20), `totalSentXaf`, filters.

- `show(Transfer $transfer)`
  - Authorize: abort 403 if `$transfer->user_id !== auth()->id()`.
  - Return view `transactions.show` with the model.

- `exportPdf(Request $request)`
  - Reuse same filtering logic as `index` (no pagination; may cap to reasonable limit or chunk).
  - Render `transactions.pdf` blade to HTML and use DomPDF to generate a downloadable PDF: `transactions-YYYYMMDD-HHMM.pdf`.

3. Model Additions in `App\Models\Transfer`

- Scopes:
  - `scopeOwnedBy($query, $userId)` -> `where('user_id', $userId)`.
  - `scopeSearch($query, $term)` -> match by id, bank name/code, account number/name, status, payin/payout status, reference fields; sanitize term.
  - `scopeDateBetween($query, $from, $to)` to filter by `created_at`.

- Accessors/Helpers:
  - `getReceiveNgnAttribute()` -> `receive_ngn_minor / 100`.
  - `maskAccount($n)` helper for views (or implement in blade).

4. PDF Generation

- Add dependency: `barryvdh/laravel-dompdf`.
- Register provider if needed for Laravel 12 (package auto-discovery typically handles this).
- Create blade `resources/views/transactions/pdf.blade.php` for print-friendly layout.

5. Authorization & Security

- Ensure every action under `/transactions` checks `auth()` and ownership.
- Route-model binding for `Transfer` and a manual owner check in `show`.
- Do not expose sensitive data (mask account number).

6. Performance & DX

- Add DB indexes if not already present on `transfers.user_id`, `transfers.created_at`, `transfers.status`.
- Use `select([...])` to limit columns for list and export.
- Consider chunked export if records are large; for now assume within PDF limits.

---

## View Details

1. `resources/views/dashboard/index.blade.php`

- Header toolbar:
  - Primary button: "Send" -> `route('transfer.bank')`.
- Below, render a list/table of the last 5 transactions (descending by `created_at`).
- Under the list, include a button/link: "See more transactions" -> `route('transactions.index')`.

2. `resources/views/transactions/index.blade.php` (new)

- Search form (`GET`) with `q`, `from`, `to`, `status`.
- Table of results with pagination controls.
- Button to export: links to `route('transactions.export', request()->query())`.
- At the bottom/footer of the page (below pagination), show `Total Sent (XAF) across all-time: {{ number_format($totalSentAllTime) }}`.

3. `resources/views/transactions/show.blade.php` (new)

- Structured sections as described above with badges for statuses similar to dashboard styles.

4. `resources/views/transactions/pdf.blade.php` (new)

- Compact table, headers, optional filter summary, and a totals row.

---

## Search Semantics

- `q` should match any of:
  - `id`
  - `recipient_bank_name`, `recipient_bank_code`
  - `recipient_account_number`, `recipient_account_name`
  - `status`, `payin_status`, `payout_status`
  - `payin_ref`, `payout_ref`
- Case-insensitive; partial matches on strings.

---

## Totals Decision

- Option A (simple): Sum `amount_xaf` to display "Total Sent (XAF)". This represents what user intended to send before fees (if `FEES_CHARGE_MODE=on_top`).
- Option B (paid): Sum `total_pay_xaf` to represent what the user actually paid (includes fees when charge-on-top).

Please pick:
- A: Show total of `amount_xaf` (intent to send)
- B: Show total of `total_pay_xaf` (actual paid)

Default if not specified: A (sum of `amount_xaf`).

---

## Edge Cases

- No transactions: show informative empty state with a "Send" button.
- Large result sets for PDF: display a notice if rows exceed a threshold (e.g., 2,000); suggest refining filters.
- Soft-deleted users won’t access the area; handle gracefully if session is invalidated.

---

## Step-by-Step Implementation (post-approval)

1. Routes
   - Add `/transactions`, `/transactions/{transfer}`, `/transactions/export` under `auth`.

2. Controller
   - Create `TransactionsController` with `index`, `show`, `exportPdf`.

3. Model
   - Add `scopeOwnedBy`, `scopeSearch`, `scopeDateBetween`, and helpers.

4. Views
   - Build `transactions/index.blade.php`, `transactions/show.blade.php`, and `transactions/pdf.blade.php`.
   - Update `dashboard/index.blade.php` to add "Send" and "See all transactions" actions.

5. PDF
   - Require `barryvdh/laravel-dompdf` and implement `exportPdf`.

6. QA
   - Test auth/authorization, search combinations, pagination, totals, and PDF download.

---

## Open Questions for You

1. Which total should we display on the Transactions page?
   - A: Sum of `amount_xaf` (intent to send), or
   - B: Sum of `total_pay_xaf` (actual paid).
2. Any additional filters you’d like (e.g., bank, payout status only, date range default)?
3. For transaction details, do you also want to show raw provider responses (sanitized) or keep it clean?
4. PDF header branding and formatting preferences?
