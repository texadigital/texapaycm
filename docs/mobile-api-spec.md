# TexaPay Mobile JSON API Spec

Last updated: 2025-09-30
Base URL (prod-like via tunnel): https://malika-jubilant-seriously.ngrok-free.dev
Auth model: Session cookie (no tokens). Mobile client must preserve Set-Cookie and send it on subsequent requests.

## Feature Flag
- `AdminSetting 'mobile_api_enabled'` gates all `/api/mobile/*` endpoints.
- Check: `GET /api/mobile/feature` → `{ enabled: boolean }`.

## Auth
- Session-cookie auth reused from web guard (`web`).
- Login
  - POST `/api/mobile/auth/login`
  - Body (JSON or form): `{ phone: string, password: string, pin?: string }`
  - Returns: `{ success: true, user: { id, name, phone, kycStatus, kycLevel } }` and a session cookie.
- Logout
  - POST `/api/mobile/auth/logout`
  - Returns `{ success: true }`.
- Notes
  - If the user has PIN enabled, include `pin` or you’ll get `PIN_REQUIRED` (403).
  - Use `Accept: application/json` to avoid HTML redirects.
  - For PowerShell/curl on Windows, `application/x-www-form-urlencoded` is convenient.

## Banks
- GET `/api/mobile/banks` → `{ banks: Array<{ bankCode, name, aliases[], categoryId? }> }`
- GET `/api/mobile/banks/favorites` → `{ banks: Array }` (session-based recent list)
- POST `/api/mobile/banks/suggest` body `{ accountNumber }` → heuristic name-enquiry over shortlist

## KYC (Smile ID)
- `AdminSetting 'kyc_enabled'` gates KYC prompt/flow.
- POST `/api/mobile/kyc/smileid/start` → `{ enabled, provider: 'smileid', session: { ... } }`
- POST `/api/mobile/kyc/smileid/web-token` → `{ enabled, token? }`
- GET `/api/mobile/kyc/status` → `{ kyc_status: 'unverified'|'pending'|'verified'|'failed', kyc_level: 0|1, kyc_verified_at }`
- Webhook remains: `POST /api/kyc/smileid/callback` (public; signature verified).

## Transfers Flow (CM MoMo → NGN Bank)
Order: name-enquiry → quote (TTL) → confirm (initiate pay-in) → timeline polling; receipt link.

- Name Enquiry
  - POST `/api/mobile/transfers/name-enquiry`
  - Body: `{ bankCode: string, accountNumber: string }`
  - Returns: `{ success, accountName, bankName?, reference }`.

- Quote
  - POST `/api/mobile/transfers/quote`
  - Body: `{ amountXaf: number, bankCode: string, accountNumber: string, accountName?: string }`
  - Returns: `{ success, quote: { id, ref, amountXaf, feeTotalXaf, totalPayXaf, receiveNgnMinor, adjustedRate, expiresAt } }`
  - TTL: `AdminSetting 'pricing.quote_ttl_secs'` (default 90s).
  - Pricing: v2 via `PricingEngine` if `AdminSetting 'pricing_v2.enabled'` else env-based.

- Confirm (initiate pay-in)
  - POST `/api/mobile/transfers/confirm`
  - Body: `{ quoteId: number, bankCode: string, bankName?: string, accountNumber: string, accountName?: string, msisdn: string }`
  - msisdn must be Cameroon MoMo in E.164 without `+` (e.g., `2376XXXXXXXX`).
  - Returns: `{ success, transfer: { id, status, payinRef } }` (status `payin_pending`).

- Timeline
  - GET `/api/mobile/transfers/{transfer}/timeline`
  - Returns: `{ success, timeline: [...], status, payinStatus, payoutStatus }`.

- Receipt URL (signed)
  - GET `/api/mobile/transfers/{transfer}/receipt-url`
  - Returns: `{ success, url }` → open in browser to view receipt page.

## Idempotency (mobile only)
- Use header `Idempotency-Key` on `name-enquiry`, `quote`, `confirm` to get the same JSON returned for retries within 6 hours.

## Limits & KYC gating
- Middleware `check.limits` is applied on `quote`.
- If exceeding caps and KYC is enabled/required, you will get a 400 with payload containing `kyc_required`, `kyc_url`.

## Error Envelope (mobile endpoints only)
```json
{ "success": false, "code": "ERROR_CODE", "message": "Human friendly text", "details": { } }
```

Common codes:
- `FEATURE_DISABLED`, `INVALID_CREDENTIALS`, `PIN_REQUIRED`, `RATE_UNAVAILABLE`, `NAME_ENQUIRY_FAILED`, `QUOTE_EXPIRED`, `INVALID_MSISDN`, `PAYIN_FAILED`.

## Request/Response Examples (form)
### Login
```
POST /api/mobile/auth/login
Content-Type: application/x-www-form-urlencoded

phone=237650000001&password=Passw0rd!&pin=1234
```

### Name Enquiry
```
POST /api/mobile/transfers/name-enquiry
Content-Type: application/x-www-form-urlencoded

bankCode=999240&accountNumber=0119215210
```

### Quote
```
POST /api/mobile/transfers/quote
Content-Type: application/x-www-form-urlencoded

amountXaf=10000&bankCode=999240&accountNumber=0119215210
```

### Confirm
```
POST /api/mobile/transfers/confirm
Content-Type: application/x-www-form-urlencoded

quoteId=44&bankCode=999240&bankName=SAFE%20HAVEN%20SANDBOX%20BANK&accountNumber=0119215210&accountName=TEXA%20GLOBAL%20TECHNOLOGIES%20LTD&msisdn=237653456789
```

## Conventions
- Money: integer minor units where noted (e.g., `receiveNgnMinor`).
- Field names: camelCase in JSON.
- Use `Accept: application/json` to force JSON responses.

## Operational Notes
- Session over ngrok: set `SESSION_DOMAIN` to the ngrok host and `SESSION_SECURE_COOKIE=true`.
- Trusted proxies are enabled (`TRUSTED_PROXIES=*`) so X-Forwarded headers are honored.
- Rate limiter `throttle:api` is 60/min per user/IP. Write endpoints also use `throttle:20,1`.
- Webhooks: PawaPay and SafeHaven run synchronously in dev via `WEBHOOKS_SYNC=true`.
