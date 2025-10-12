# Texa Protect PWA – Implementation Plan

## Goals

- Build sender-focused PWA screens to start and manage a Protected (escrow) payment.
- Provide a receiver landing page (no login) to request release via a signed link.
- Reuse existing backend APIs; only add missing UI and thin client code.

## What we will reuse

- Auth/session: `/api/mobile/auth/*`.
- Protected endpoints:
  - Sender: `POST /api/mobile/protected/init`, `GET /api/mobile/protected/{ref}`, `POST /api/mobile/protected/{ref}/approve`, `POST /api/mobile/protected/{ref}/dispute`.
  - Receiver (no-login): `POST /api/mobile/protected/{ref}/request-release?sig=&exp=`.
- Notifications and device registration (optional in v1).

## PWA routes to add

- `/protected/start` – Start Protected flow (enter receiver + amount). Calls `init` and navigates to details.
- `/protected/[ref]` – Details page: status, timeline, VA instructions (created), auto-release countdown (awaiting), Approve/Dispute actions, and a "Copy receiver link" (signed link).
- `/r/protected/[ref]` – Receiver landing (no login). Reads `sig`/`exp` from URL and POSTs request-release, then shows a friendly confirmation.

## UI behaviors

- Start: validated form, shows API errors inline.
- Details:
  - Status pill: `created` | `awaiting_approval` | `disputed` | `released`.
  - VA details and TTL countdown when `created`.
  - Auto-release countdown when `awaiting_approval`.
  - Approve (sender-only) and Dispute (with reason dialog).
  - Copy receiver signed link (provided by backend or constructed from returned token fields when available).
- Receiver landing: POST request-release, show success/invalid/expired states.

## Networking

- Base URL: `NEXT_PUBLIC_API_BASE_URL`.
- Use fetch with credentials (cookie/JWT) for sender routes.
- Simple thin wrappers (or inline fetch in pages initially) to minimize changes.

## Minimal milestones

1) Start + Details pages (sender) with basic fetch and skeleton loaders.
2) Receiver landing + request-release POST and success page.
3) Polish: status/timeline components, copy-to-clipboard for receiver link, error states.
4) Optional: notifications center + device registration for push.

## Acceptance

- Sender can init → see VA instructions → later see `awaiting_approval` after webhook → Approve successfully (payout success in sandbox already validated).
- Receiver can click signed link and nudge sender without login.

## Follow-ups (optional)

- Add endpoint/field to expose pre-computed receiver signed link (backend can include in details response after lock).
- Local/test-only helper endpoint to lock by ref for smoke tests.
- README additions and a few tests (webhook matcher paths, UI smoke).
