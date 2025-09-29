# TexaPay: Pre-Flight Liquidity Reservation (Straight-Through MoMo → Safe Haven)

Date: 2025-09-29

## Executive Summary
- **Problem**: Instant payouts can fail when the payout provider has low funds (float), daily limits, or temporary rail outages. This creates user frustration and operational risk.
- **Solution**: Introduce a short “hold” of the outgoing amount before the user sees the mobile money checkout. If the hold is placed, we can confidently send out funds immediately after the user pays. If the user does not pay or times out, we release the hold.
- **User Experience**: Users either see “Send now” (instant) or “Queued with ETA” (if we choose to accept funds and send later). Messages are clear and friendly throughout.

## What the Codebase Currently Does (plain language)
- **Today’s flow**
  - User verifies a recipient bank account (name enquiry) and then requests a quote. The quote locks the exchange rate and fees for a short time window (a countdown is shown to the user).
  - When the user confirms, a mobile money pay-in is created. Its status is updated via provider callbacks and occasional checks.
  - As soon as the mobile money pay-in is reported as successful, the system immediately triggers the payout to the Nigerian bank account.
  - If the payout fails after the pay-in succeeded, the system attempts to refund the user back to the original mobile money source.
  - The user can view a receipt and a timeline of states (quote created, pay-in pending/success, payout pending/success/failure, etc.).
- **Where decisions are made**
  - The quote has a short validity window (default around a minute and a half). If expired, the user must refresh to continue.
  - The mobile money provider notifies us of payment status via webhooks. On a success notification, the payout is started right away.
  - Payout success/failure is determined by the provider’s response and follow-up checks.
- **Existing settings and toggles**
  - Exchange-rate fetching and a quote time-to-live value are configurable.
  - Fees and margins are configurable.
  - Basic user transaction limits and warnings exist (to protect against excessive or out-of-policy usage).
  - Health-check endpoints and status checks exist for both pay-in and payout providers.
- **Mismatches vs. the desired pre-flight behavior**
  - There is no current “pre-flight reservation” of payout funds before starting mobile money checkout.
  - The system assumes payout can start immediately after pay-in success, without confirming that money was reserved ahead of time.

## Target Flow (future, simple language)
- **Before payment**: When the user asks to send money, we first try to temporarily hold the outgoing amount needed for the payout.
- **If hold succeeds**: We show the mobile money checkout. If the user pays successfully, we send the payout immediately using the held amount.
- **If user does not pay**: We release the hold when the quote expires or when the pay-in fails or times out.
- **If hold can’t be placed**: We either block the transaction with a clear message, or, if the optional mode is enabled, we accept the funds and queue the payout with a clear ETA and updates.

## What Needs to Change (described without code)
- **Before payment**
  - Add a step to check if we can afford the payout right now. This means confirming the outgoing amount is available from our payout partner or internal pool.
  - If available, place a temporary hold for the payout amount. The hold should expire right after the quote expires to avoid long locks.
  - If not available, show a clear message that instant send is unavailable. Optionally, allow “pay now, send later” and place the transaction into a queue with a realistic ETA.
- **During payment**
  - When we create the mobile money payment, we must link it to the previously created hold. This ensures that after the payment succeeds, we spend that exact hold.
  - If the user abandons or fails the payment, release the hold immediately (or at hold expiry, whichever comes first).
- **After payment success**
  - Convert and send immediately using the held amount. The outgoing transfer should consume the hold so it can’t be double-used.
  - If the payout rail is temporarily down, keep retrying automatically with backoff rules. Keep the user informed of the situation and expected timing.
- **After payment failure or timeout**
  - Release the hold and update the user status promptly.
- **Admin controls**
  - Switch: “Require pre-fund for instant sends” (on/off).
  - Numeric thresholds: minimum reserve level, per-transaction cap for instant sends, maximum total outstanding holds, and cut-off windows.
  - Alerts: notify the team when reserves are low, holds fail frequently, or queued payouts age beyond a target window.
- **Where these changes fit naturally**
  - The hold check and placement happen when the quote is confirmed (right before launching mobile money checkout).
  - The link between the payment and the hold is stored alongside the transaction and carried through callbacks.
  - The release or consumption of the hold is driven by the same events that currently update payment/payout status.

## User Experience (what the customer sees)
- **Success path**
  - “Send now” with a countdown. After paying, the receipt updates to show the payout started and completed.
- **Not enough money available**
  - Option A: Clear block message: “Instant send not available right now. Please try later.”
  - Option B (optional): “Pay now, send later” message with a queue ETA and automatic updates.
- **Bank downtime path**
  - Friendly message: “Your payment is received. We’re waiting for the bank network. We’ll send automatically and keep you updated.”
- **Receipts and status timeline**
  - The timeline shows: quote created, hold placed, pay-in pending/success, payout initiated using held funds, payout success (or queued/retry messages), and any refunds if needed.

## Risks & Edge Cases
- **Simultaneous sends**: Two users (or two sends by the same user) might compete for the last available funds. Use a first-come, first-served hold and show a clear message if the second cannot be held.
- **Quote timer expires during payment**: If the timer runs out while the user is authorizing the mobile money payment, we still honor the hold if the payment finishes within a short grace window; otherwise, we release it.
- **Provider daily limits or cut-offs**: When the payout side hits daily/session limits or off-hours windows, we either block upfront or switch to the queued mode with clear ETA.
- **Invalid beneficiary details**: If the beneficiary name enquiry or validation fails at the last moment, release the hold and return the user to fix details.
- **Mobile money success but payout outage**: If payout cannot proceed right away, we keep retrying automatically and keep the user calm with clear, time-based updates. If it turns permanent, we initiate a refund policy where appropriate.

## Monitoring & Alerts (non-technical)
- **What to watch**
  - Available payout money vs. minimum reserve threshold.
  - Total money in temporary holds.
  - Hold placement failures or timeouts.
  - Payout retry counts and age of queued payouts.
- **Thresholds to alert**
  - “NGN pool low” when reserves drop below a configured minimum.
  - “Holds failing” when a percentage of hold attempts fail within a short window.
  - “Queue aging” when queued payouts exceed a target time (e.g., 30 minutes, 2 hours, 1 day).

## Rollout Plan
- **Staged rollout**
  - Internal testing with toggles and logging.
  - Limited user cohort with close monitoring.
  - Full rollout after stability.
- **Safe fallback**
  - A switch disables the pre-fund requirement, returning to today’s straight-through mode.
- **Post-launch data review**
  - Reduction in payout failures after pay-in success.
  - Lower refund rates and faster completion times.
  - Improved user satisfaction from clearer messages and fewer surprises.

## Acceptance Criteria (clear, testable, no code)
- **Instant sends start only with a successful hold**: We do not start mobile money checkout for instant sends unless we can place a temporary hold, or we have explicitly chosen the queued mode.
- **On pay-in success, payout proceeds or queues with updates**: If the user pays, either the payout is sent immediately using the hold or it is queued automatically with clear status and retry behavior.
- **On pay-in failure or timeout, holds are released**: Holds are released promptly and the user is informed.
- **Admin control of policy**: Admin can switch “require pre-fund” on/off and set minimum thresholds and cut-offs.
- **Alerts when low or unstable**: Alerts fire when reserves are low or hold attempts fail too often.

## Open Questions & Assumptions
- **Open questions**
  - Do we have a definitive, machine-readable balance or limit endpoint from the payout provider to verify available funds in real time?
  - What grace window should apply if the user completes payment just as the quote timer expires?
  - How many automatic payout retries and over what intervals should we attempt before switching to refund or manual intervention?
- **Assumptions**
  - The existing refund path remains the fallback when payout becomes permanently unavailable after pay-in success.
  - The quote timer is the right duration for the hold; both should expire together unless a short grace is configured.
  - The system can maintain a lightweight internal record for each hold and safely consume or release it.

## Glossary (friendly definitions)
- **Temporary hold**: A short reservation of the outgoing amount on our side so that, if the user pays, we can send out funds immediately.
- **Queue (send later) mode**: An optional mode that accepts the user’s payment even when instant payout isn’t available, then sends later automatically with clear ETAs and updates.
- **Quote timer**: The short countdown period during which the exchange rate, fees, and receive amount are locked for the user.
- **Provider float / rail**: The amount of money available with the payout partner and the capacity of the underlying bank transfer network at any given time.
