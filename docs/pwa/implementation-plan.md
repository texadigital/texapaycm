# TexaPay PWA Implementation Plan

## 1) Overview

- **Stack**: Next.js (App Router) + TypeScript + Tailwind CSS + TanStack Query + next-pwa + Axios
- **Auth strategy (current)**: Stateless JWT access tokens (short TTL) + rotating refresh tokens (long TTL, HttpOnly cookie). Mobile API authenticates with `Authorization: Bearer <token>`; refresh via cookie.
- **Goals**:
  - Deliver a performant PWA for mobile and desktop that consumes the mobile JSON API under `routes/api.php`.
  - Preserve existing server-side data contracts while moving auth to JWT.
  - Add offline-first capabilities for reads and resilient writes with idempotency.
- **Assumptions**:
  - Backend: Laravel 12. Mobile API endpoints live under `/api/mobile/*` with middlewares: `throttle`, `force.json`, `idempotency`. Authenticated routes use `auth.jwt` middleware.
  - Admin web/Filament remains session-based under `routes/web.php` and `/admin`.

### UI/UX References (visual samples only)
- Source directory: `/Users/macbookpro/Downloads/TEXA (3)/`
- Purpose: Use as visual/style guides (layout, empty/error states, flows).
  Do not imply new backend features beyond discovered APIs.

- Auth screens (map to PWA `/auth/*`):
  - `Create account/Login.png`, `Login-1.png`..`Login-4.png`
  - `Create account.png`, `Create account - verification.png`
- Dashboard and states (map to `/dashboard` + list empty states):
  - `Active state.png`, `Empty State.png`, `Error state.png`, `Error state-1.png`, `Error state-2.png`
- Transfers flow visuals (map to quote/confirm/pay-in/payout pages):
  - Use as styling analogs: `Add Money.png`, `Add Money-1.png`, `Add Money-2.png`
  - Also use generic payment visuals: `Electricity*.png`, `Data*.png`, `Airtime*.png`
  - Note: These are styling cues only; backend supports bank transfer flow as in API.
- Security & limits (map to `/profile/security` and limits UI hints):
  - `Enter PIN.png`, `Account Limit.png`
- Feedback & system UI:
  - `Airtime success.png`, `Airtime confirmation.png`, `Error messagebfor profile.png`
- Misc components (style references):
  - `Chat Bot.png`, frames like `Frame 1618869***.png` for cards, lists, spacing.

We will align typography, spacing, card styles, and states with these samples
while binding interactions strictly to the discovered API routes.

References:
- `routes/api.php`
- Controllers under `app/Http/Controllers/Api/` and shared controllers (e.g., `NotificationController`, `BankController`, `Kyc/*`).
- Middlewares in `bootstrap/app.php` aliases: `force.json`, `idempotency`, `check.limits`, `redirect.admins`.

---

## 2) Route Inventory Table (Mobile API)

Legend:
- MW = middlewares in addition to the group-level middlewares.
- Group-level middlewares (all routes below): `throttle:60,1`, `force.json`, `idempotency`.
- Authenticated section additionally uses `auth.jwt` (stateless JWT middleware).

| Method | Path | Controller@Action | Middleware | Auth/Guard | Notes |
|---|---|---|---|---|---|
| GET | /api/mobile/feature | closure | - | none | Returns `{ enabled: true }` for local testing.
| POST | /api/mobile/auth/register | Api\AuthController@register | - | none | Creates user (registration kept public).
| POST | /api/mobile/auth/login | Api\TokenAuthController@login | - | jwt | Issues access token + sets HttpOnly refresh cookie; optional PIN challenge.
| POST | /api/mobile/auth/refresh | Api\TokenAuthController@refresh | - | cookie | Rotates refresh token (cookie) and returns new access token.
| POST | /api/mobile/auth/logout | Api\TokenAuthController@logout | - | cookie | Revokes refresh token and clears cookie.
| GET  | /api/mobile/auth/me | Api\TokenAuthController@me | auth.jwt | jwt | Returns current user summary.
| GET | /api/mobile/banks | BankController@list | - | none | Public banks list (cached). `?refresh=1`, `?q`.
| GET | /api/mobile/banks/favorites | BankController@favorites | - | none | From session recent banks.
| POST | /api/mobile/banks/suggest | BankController@suggest | without CSRF | none | Name enquiry shortlist; suggestions.
| GET | /api/mobile/health/pawapay | closure | - | none | PawaPay auth check.
| GET | /api/mobile/health/safehaven | closure | - | none | SafeHaven auth check.
| GET | /api/mobile/health/safehaven/banks | closure | - | none | SafeHaven bank list raw.
| GET | /api/mobile/health/oxr | closure | - | none | OpenExchangeRates fetched rates.
| GET | /api/mobile/dashboard | Api\DashboardController@summary | auth.jwt | jwt | KYC, stats, recent transfers (15s cache).
| POST | /api/mobile/kyc/smileid/start | Kyc\SmileIdController@start | auth.jwt | jwt | Starts Smile ID session.
| POST | /api/mobile/kyc/smileid/web-token | Kyc\SmileIdController@webToken | auth.jwt | jwt | Smile ID web token or fallback.
| GET | /api/mobile/kyc/status | Kyc\KycController@status | auth.jwt | jwt | KYC status/level.
| GET | /api/mobile/transfers | Api\TransfersController@index | auth.jwt | jwt | Paginated list; filters.
| GET | /api/mobile/transfers/{transfer} | Api\TransfersController@show | auth.jwt | jwt | Ownership enforced.
| POST | /api/mobile/transfers/name-enquiry | Api\TransfersController@nameEnquiry | throttle:20,1 | jwt | Requires Idempotency-Key (MW).
| POST | /api/mobile/transfers/quote | Api\TransfersController@quote | throttle:20,1, check.limits | jwt | Requires Idempotency-Key; creates Quote.
| POST | /api/mobile/transfers/confirm | Api\TransfersController@confirm | throttle:20,1, check.limits | jwt | Requires Idempotency-Key; pay-in.
| POST | /api/mobile/transfers/{transfer}/payin/status | Api\TransfersController@payinStatus | - | jwt | Poll pay-in; state transitions.
| POST | /api/mobile/transfers/{transfer}/payout | Api\TransfersController@initiatePayout | - | jwt | Server idempotency per-transfer.
| POST | /api/mobile/transfers/{transfer}/payout/status | Api\TransfersController@payoutStatus | - | jwt | Poll payout.
| GET | /api/mobile/transfers/{transfer}/timeline | Api\TransfersController@timeline | auth.jwt | jwt | Timeline/status.
| GET | /api/mobile/transfers/{transfer}/receipt-url | Api\TransfersController@receiptUrl | auth.jwt | jwt | Signed URL to receipt.
| GET | /api/mobile/transfers/{transfer}/receipt.pdf | Api\TransfersController@receiptPdf | auth.jwt | jwt | Signed URL to PDF if enabled.
| GET | /api/mobile/pricing/limits | Api\PricingController@limits | auth.jwt | jwt | Min/max, caps, remaining.
| GET | /api/mobile/pricing/rate-preview | Api\PricingController@preview | auth.jwt | jwt | Pricing preview for amountXaf.
| GET | /api/mobile/profile | Api\ProfileController@show | - | web | Basic user + KYC profile.
| GET | /api/mobile/profile/security | Api\SecurityController@show | - | web | Pin/twoFactor flags.
| POST | /api/mobile/profile/security/pin | Api\SecurityController@updatePin | without CSRF | web | Requires Idempotency-Key (MW).
| POST | /api/mobile/profile/security/password | Api\SecurityController@updatePassword | without CSRF | web | Requires Idempotency-Key.
| GET | /api/mobile/profile/notifications | Api\ProfileController@notifications | - | web | Cached notification preferences.
| PUT | /api/mobile/profile/notifications | Api\ProfileController@updateNotifications | without CSRF | web | Requires Idempotency-Key.
| GET | /api/mobile/notifications | NotificationController@index | auth.jwt | jwt | Paginated notifications.
| GET | /api/mobile/notifications/summary | NotificationController@summary | auth.jwt | jwt | Unread count + recent.
| PUT | /api/mobile/notifications/{notification}/read | NotificationController@markAsRead | - | jwt | Mark one read (ownership).
| PUT | /api/mobile/notifications/read-all | NotificationController@markAllAsRead | - | jwt | Mark all read.
| GET | /api/mobile/notifications/preferences | NotificationController@preferences | auth.jwt | jwt | Per-type prefs.
| PUT | /api/mobile/notifications/preferences | NotificationController@updatePreferences | - | jwt | Update per-type prefs.
| POST | /api/mobile/devices/register | Api\DeviceController@register | auth.jwt | jwt | Register FCM token.
| DELETE | /api/mobile/devices/unregister | Api\DeviceController@unregister | auth.jwt | jwt | Deactivate device token.
| GET | /api/mobile/devices | Api\DeviceController@devices | auth.jwt | jwt | List active devices.
| POST | /api/mobile/devices/test-push | Api\DeviceController@testPush | auth.jwt | jwt | Send test notification.
| POST | /api/mobile/auth/forgot-password | PasswordResetController@apiSendResetCode | - | none | Send reset code via SMS/push.
| POST | /api/mobile/auth/reset-password | PasswordResetController@apiResetPassword | - | none | Verify code and reset password.
| GET | /enable-mobile-api | closure | web | One-off route to enable feature flag.

Notes:
- `force.json` enforces JSON Accept and converts redirects to `401 UNAUTHENTICATED` JSON.
- `idempotency` middleware (`EnforceIdempotency`) requires `Idempotency-Key` for specific routes and caches JSON responses.
- Transfers also implement an internal idempotency wrapper for mobile API within `Api\TransfersController`.

Evidence (code references):
- Routes group and all endpoints: `routes/api.php:L15-L189`
- Enable flag route: `routes/mobile-api-enable.php:L6-L29`
- Middleware aliases: `bootstrap/app.php:L14-L22`
- Auth config (session web guard): `config/auth.php:L16-L45`
- CORS config (credentials, origins): `config/cors.php:L3-L30`
- Force JSON middleware: `app/Http/Middleware/ForceJson.php:L12-L30`
- Enforce Idempotency: `app/Http/Middleware/EnforceIdempotency.php:L12-L63`
- CheckUserLimits: `app/Http/Middleware/CheckUserLimits.php:L12-L139`
- AuthController register/login/logout: `app/Http/Controllers/Api/AuthController.php:L20-L73,L79-L157,L162-L176`
- Dashboard summary: `app/Http/Controllers/Api/DashboardController.php:L13-L60`
- Transfers index/show: `app/Http/Controllers/Api/TransfersController.php:L30-L72,L77-L92`
- Transfers nameEnquiry/quote/confirm: `.../TransfersController.php:L118-L145,L152-L235,L243-L410`
- Transfers payin/payout/timeline/receipt: `.../TransfersController.php:L443-L490,L496-L650,L416-L425,L432-L437,L692-L699,L705-L714`
- Pricing limits/preview: `app/Http/Controllers/Api/PricingController.php:L15-L58,L60-L111`
- Profile show/notifications/update: `app/Http/Controllers/Api/ProfileController.php:L11-L25,L27-L37,L39-L52`
- Security show/updatePin/updatePassword: `app/Http/Controllers/Api/SecurityController.php:L13-L21,L24-L41,L44-L56`
- Notifications index/summary/mark read/all/prefs/update: `app/Http/Controllers/NotificationController.php:L22-L52,L57-L65,L70-L85,L90-L100,L102-L153,L158-L178`
- Devices register/unregister/list/test: `app/Http/Controllers/Api/DeviceController.php:L23-L92,L97-L147,L153-L186,L191-L249`
- Password reset (API): `app/Http/Controllers/PasswordResetController.php:L328-L377,L394-L461`
- KYC controllers: `app/Http/Controllers/Kyc/SmileIdController.php:L27-L84,L85-L123`; `app/Http/Controllers/Kyc/KycController.php:L18-L25`
- Banks list/favorites/suggest: `app/Http/Controllers/BankController.php:L15-L73,L78-L82,L88-L159`

---

## 3) Data Contract Summaries

Examples inferred from controllers.

- **Auth Register** (`POST /api/mobile/auth/register`)
```json
{
  "name": "John Doe",
  "phone": "+2376...",
  "password": "secret123",
  "pin": "1234"
}
```
Response 201:
```json
{
  "success": true,
  "user": { "id": 1, "name": "John Doe", "phone": "+2376...", "kycStatus": "unverified", "kycLevel": 0 }
}
```
Errors: 403 FEATURE_DISABLED, 400 INVALID_PHONE.

- **Auth Login** (`POST /api/mobile/auth/login`)
```json
{ "phone": "+2376...", "password": "secret123", "pin": "1234" }
```
Response 200 on success with `user`; 401 INVALID_CREDENTIALS; 403 PIN_REQUIRED.

- **Dashboard** (`GET /api/mobile/dashboard`)
```json
{
  "kyc": {"status":"pending","level":1},
  "today": {"count": 2, "totalXaf": 20000},
  "month": {"count": 10, "totalXaf": 100000},
  "recentTransfers": [ {"id": 101, "status":"payin_pending", "amountXaf": 10000, "createdAt": "2025-09-30T00:00:00Z"} ]
}
```

- **Banks**
- List (`GET /api/mobile/banks`): `{ "banks": [{"bankCode": "000014", "name": "Access Bank", "aliases": []}] }`
- Favorites (`GET /api/mobile/banks/favorites`): `{ "banks": [...] }`
- Suggest (`POST /api/mobile/banks/suggest`): `{ "resolved": true|false, "bank": {...}, "accountName": "...", "suggestions": [...] }`

- **Transfers**
- Name Enquiry (`POST /api/mobile/transfers/name-enquiry`):
```json
{ "bankCode": "000014", "accountNumber": "0123456789" }
```
Success:
```json
{ "success": true, "accountName": "DOE JOHN", "bankName": "Access Bank", "reference": "abc-123" }
```
Error 400 NAME_ENQUIRY_FAILED with provider raw details.

- Quote (`POST /api/mobile/transfers/quote`):
```json
{ "amountXaf": 10000, "bankCode": "000014", "accountNumber": "0123456789" }
```
Response:
```json
{
  "success": true,
  "quote": {
    "id": 1,
    "ref": "q-uuid",
    "amountXaf": 10000,
    "feeTotalXaf": 200,
    "totalPayXaf": 10200,
    "receiveNgnMinor": 1234567,
    "adjustedRate": 1.2345,
    "expiresAt": "2025-10-02T20:00:00Z"
  }
}
```
Errors: 503 RATE_UNAVAILABLE.

- Confirm (`POST /api/mobile/transfers/confirm`):
```json
{ "quoteId": 1, "bankCode": "000014", "accountNumber": "0123456789", "msisdn": "+2376..." }
```
Response success:
```json
{ "success": true, "transfer": { "id": 123, "status": "payin_pending", "payinRef": "ref-uuid" } }
```
Errors: 400 QUOTE_EXPIRED, 400 INVALID_MSISDN, 400 PAYIN_FAILED (+raw), 403 FEATURE_DISABLED.

- Pay-in Status (`POST /api/mobile/transfers/{id}/payin/status`): `{ "success": true, "status": "pending|success" }` or 400 with message.
- Payout (`POST /api/mobile/transfers/{id}/payout`): `status: success|pending|error`, message, payout_ref, errors for failed.
- Payout Status (`POST /api/mobile/transfers/{id}/payout/status`): `{ "status": "success|pending|failed", "transfer_status": "..." }`.
- Timeline (`GET /api/mobile/transfers/{id}/timeline`): `{ "success": true, "timeline": [...], "status": "...", "payinStatus": "...", "payoutStatus": "..." }`.
- Receipt URL (`GET /api/mobile/transfers/{id}/receipt-url`): `{ "success": true, "url": "https://...", "expires_at": "..." }`.
- Receipt PDF (`GET /api/mobile/transfers/{id}/receipt.pdf`): same shape; 404 if feature disabled.

- **Pricing**
- Limits (`GET /api/mobile/pricing/limits`): `{ minXaf, maxXaf, dailyCap, monthlyCap, usedToday, usedMonth, remainingXafDay, remainingXafMonth }`.
- Preview (`GET /api/mobile/pricing/rate-preview?amountXaf=...`): `{ amountXaf, feeTotalXaf, totalPayXaf, receiveNgnMinor, adjustedRate }`.

- **Profile**
- Show (`GET /api/mobile/profile`): user fields + `kyc` subobject.
- Security (`GET /api/mobile/profile/security`): `{ pinEnabled, twoFactorEnabled, lastSecurityUpdate }`.
- Update PIN (`POST /api/mobile/profile/security/pin`): `{ currentPin?, newPin }` → `{ success: true }` or 400 INVALID_PIN.
- Update Password (`POST /api/mobile/profile/security/password`): `{ currentPassword, newPassword }` → `{ success: true }` or 400 INVALID_PASSWORD.
- Notification prefs (`GET /api/mobile/profile/notifications`): returns cache object; `PUT` merges and returns `{ success: true, preferences }`.

- **Notifications**
- Index (`GET /api/mobile/notifications`):
```json
{
  "notifications": [ ... ],
  "pagination": {"current_page":1, "last_page":3, "per_page":20, "total":60},
  "unread_count": 5
}
```
- Summary: `{ unread_count, recent_notifications }`
- Mark one: `{ success: true, message: "Notification marked as read" }`
- Mark all: `{ success: true, message, count }`
- Preferences: `{ preferences: {"type": {...}}, global_settings: {...} }`

- **Devices**
- Register (`POST /api/mobile/devices/register`): `{ device_token, platform, device_id?, app_version?, os_version? }` → `{ success, device:{...} }` or 422/400/500.
- Unregister (`DELETE /api/mobile/devices/unregister`): `{ device_token }` → `{ success }` or 404.
- List (`GET /api/mobile/devices`): `{ success, devices: [...] }`.
- Test Push (`POST /api/mobile/devices/test-push`): `{ success, message }`.

- **Password Reset**
- Forgot (`POST /api/mobile/auth/forgot-password`): `{ phone }` → `{ success, message, expires_at }` or 404.
- Reset (`POST /api/mobile/auth/reset-password`): `{ phone, code, password }` → `{ success, message }`.

Common errors:
- `401 UNAUTHENTICATED` from `force.json` for redirects.
- `403 FEATURE_DISABLED` for mobile api flag.
- `400 IDEMPOTENCY_KEY_REQUIRED` on missing header for protected routes.
- `400` validation errors with Laravel messages or structured `code` fields where added.

---

## 4) Phased Build Plan (Milestones, Deliverables)

- **Phase 0: Backend readiness (JWT)**
  - [x] Implement JWT access tokens + rotating refresh tokens (`TokenAuthController`, `RequireAccessToken`, `JwtService`).
  - [x] Protect mobile API with `auth.jwt`; keep Filament admin session-based.
  - [x] Confirm `idempotency` middleware coverage meets PWA write needs.
  - [x] Add DB table `refresh_tokens` (hashed) and indexes for dashboard speed.

- **Phase 1: Auth & Shell**
  - [x] App shell, routing, layout, theme (Tailwind).
  - [x] JWT login via `POST /api/mobile/auth/login`; store access token in memory/sessionStorage (not localStorage).
  - [x] Auto-refresh on 401 via `POST /api/mobile/auth/refresh` (HttpOnly cookie) and retry once.
  - [x] Global 401 guard + logout UI; protected pages wrapped with `RequireAuth`.
  - [x] User profile fetch (`GET /api/mobile/profile`) and KYC status badge.

- **Phase 2: Banks & Quote Flow**
  - [ ] Banks list, search, favorites.
  - [ ] Name enquiry form (requires Idempotency-Key per request).
  - [ ] Quote creation with live preview and TTL countdown; render fees, rate, receive amount.
  - [ ] Limit warnings surfaced from `check.limits` errors.

- **Phase 3: Transfers Pay-in & Timeline**
  - [ ] Confirm pay-in (msisdn detection UI), initiate pay-in.
  - [ ] Poll pay-in status; transition UI to payout step when success.
  - [ ] Timeline view; receipt URL generation, PDF link.

- **Phase 4: Payout & Status**
  - [ ] Initiate payout; show pending/success/failure states.
  - [ ] Poll payout status; handle refund-initiated errors.

- **Phase 5: Notifications & Devices**
  - [ ] Notifications list, summary badge, mark read/all, preferences.
  - [ ] Device registration for push (web platform) with FCM; test push.

- **Phase 6: KYC**
  - [ ] KYC start + Smile ID web token flow in webview/modal.
  - [ ] KYC status endpoint; show progression and completion.

- **Phase 7: Support**
  - [ ] Help topics (static), contact form, tickets list/details, reply thread.

- **Phase 8: Polishing**
  - [ ] Error boundary, empty states, skeletons.
  - [ ] SEO/PWA metadata, icons, splash.
  - [ ] Analytics, logging, Sentry.

Deliverables per phase: pages, components, and route adapters linking each API endpoint to UI actions.

Mapping API → PWA Pages/Components (high level):
- Auth pages: Register/Login/Logout → `/auth/*` using `AuthController` endpoints.
- Dashboard → `/dashboard` using `Api/DashboardController@summary`.
- Banks & Transfers → `/transfer/*` using `BankController` + `TransfersController` endpoints.
- Pricing → inline TanStack Query hooks for rate preview; Limits in Profile/Transfer forms.
- Profile & Security → `/profile`, `/profile/security` using `ProfileController` and `SecurityController`.
- Notifications → `/notifications` using `NotificationController`.
- Devices → part of settings → `/settings/devices` using `Api/DeviceController`.
- KYC → `/kyc` using `KycController` and `SmileIdController`.
- Support → `/support/*` using `Api/SupportController`.

---

## 5) Offline/Caching Strategies (next-pwa + TanStack Query)

- **Static assets & shell**: `StaleWhileRevalidate` for `/_next/*`, static routes.
- **Banks list (`GET /api/mobile/banks`)**: Cache-first with 24h revalidation. Seed from server cache; support `?refresh=1` for manual refresh.
- **Rate preview (`GET /api/mobile/pricing/rate-preview`)**: Network-first; short client cache (30–60s) to avoid stale quotes.
- **Pricing limits**: Cache 5–10 minutes; invalidate on auth changes.
- **Dashboard summary**: Network-first; server micro-cache 15s; background revalidation.
- **Transfers index/show/timeline**: Network-first with optimistic timeline updates after actions; cache recent pages for offline viewing.
- **Receipt URL/PDF**: Do not cache responses; open signed URLs directly.
- **Notifications**: Cache-first for list and summary with revalidate-on-focus; mark-as-read mutations invalidate queries.
- **Devices**: Network-only.
- **Support tickets**: Cache list/detail; queue offline replies with Idempotency-Key when back online.

Idempotency in PWA:
- Generate a UUID v4 per write action and send `Idempotency-Key` header to satisfy `EnforceIdempotency` and enable client-side retry safety.
- Persist pending mutations in IndexedDB for offline retries.

---

## 6) Security Posture

- **Auth mode**: Stateless JWT access tokens (short TTL) signed HS256 + rotating refresh tokens (stored hashed in DB). Access token in memory/sessionStorage; refresh in HttpOnly, Secure, SameSite=Strict cookie.
- **CORS**: Prefer same-origin via reverse proxy. If cross-origin, allow credentials and restrict origins.
- **Cookies**: Host-only domain; Path `/`; HttpOnly; Secure; SameSite=Strict.
- **Idempotency**: Required by middleware for specific write routes. Client always sends `Idempotency-Key` and retries safely.
- **Rate limits**: Group `throttle:60,1` plus specific `throttle:20,1` on transfers.
- **Sensitive data**: Never store tokens in localStorage; PIN/password masked; refresh token never stored client-side.
- **Authorization checks**: Transfer and Ticket routes enforce ownership server-side.

---

## 7) Risks & Mitigations

- **Session vs Sanctum drift**: Current API uses web sessions; adding Sanctum may require guard alignment. Mitigation: support both during transition; ensure middleware stack compatible.
- **CSRF on browser**: Some POSTs are CSRF-exempt. Mitigation: prefer Sanctum-protected routes for PWA; gradually remove CSRF exemptions for browser clients.
- **CORS & cookies**: Misconfiguration will break auth. Mitigation: test with staging domain, confirm `Set-Cookie` attributes, enable proxy trust.
- **Idempotency adherence**: Missing Idempotency-Key yields 400. Mitigation: universal mutation wrapper attaches keys; retry-safe queue.
- **Rate-limit UX**: 429 or 400 from throttles. Mitigation: backoff UI, inline countdowns on rate-limited endpoints.
- **Quote TTL**: Expired quotes cause confirm failure. Mitigation: countdown timers; auto-refresh quote on expiry.
- **FX availability**: 503 `RATE_UNAVAILABLE`. Mitigation: graceful UI messaging; retry with backoff.
- **Push tokens (web)**: FCM token format and browser permissions. Mitigation: capability detection, fallbacks to in-app notifications.

---

## 8) Open Questions

- HS256 vs RS256 for JWT? Current: HS256. Any need for key rotation/public verification (RS256)?
- PWA and API origins/domains (for CORS/cookie domain) in staging/production?
- Keep `/api/mobile/*` as public contract, or add `/api/pwa/*` aliases?
- Any additional feature flags required (e.g., toggle PDF/Share features for PWA separately)?
- Expected polling intervals and SLA for pay-in/payout status? Consider push/SSE for real-time updates.
- Multi-language support at launch?
- Analytics and crash reporting providers (GA4, Sentry)?

---

## 9) Production Run Notes

- Build and run Next.js in production for demos:
  - `npm --prefix pwa run build`
  - `npm --prefix pwa run start` (defaults to port 3000)
  - `php artisan serve --host=127.0.0.1 --port=8000`
  - `npm run proxy` (fronts both on http://localhost:3005)
- Point ngrok at `http://localhost:3005` for a single public origin.
- This removes dev/HMR overhead and is noticeably faster over tunnels.
