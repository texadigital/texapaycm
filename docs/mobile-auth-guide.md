# Mobile Auth Guide (Session Cookie)

This app uses Laravel's `web` session guard for both web and mobile. No token infrastructure is added. Mobile clients must preserve the session cookie after login and send it with subsequent requests.

## Login Flow
- Endpoint: `POST /api/mobile/auth/login`
- Body formats supported: `application/json` or `application/x-www-form-urlencoded`.
- Required fields: `phone`, `password`. If user has PIN enabled, include `pin`.
- Response: `{ success: true, user: {...} }` with `Set-Cookie: laravel-session=...`.
- Store that cookie and send it in the `Cookie` header on all subsequent requests.

Example (curl on Windows):
```
curl.exe -i -X POST "https://<base>/api/mobile/auth/login" ^
  -H "Content-Type: application/x-www-form-urlencoded" ^
  -H "Accept: application/json" ^
  --cookie-jar cookie.txt --cookie cookie.txt ^
  --data "phone=237650000001&password=Passw0rd!&pin=1234"
```

## Session Domain over ngrok
- In `.env`, set:
  - `APP_URL=https://<ngrok-host>`
  - `SESSION_DOMAIN=<ngrok-host>`
  - `SESSION_SECURE_COOKIE=true`
  - `TRUSTED_PROXIES=*`
- Then run `php artisan optimize:clear` and restart `php artisan serve`.

## JSON-only Behavior
- Always send `Accept: application/json` to avoid HTML redirects.

## Logout
- Endpoint: `POST /api/mobile/auth/logout`
- Clears and invalidates the session.

## Error Handling
- Mobile endpoints return a consistent JSON envelope on error:
```
{ "success": false, "code": "ERROR_CODE", "message": "Human friendly text", "details": { } }
```

## Security Notes
- Keep session cookie encrypted/HTTP-only (Laravel default). Do not log cookies.
- On public networks, use HTTPS only.
- Rate limits: `throttle:api` globally (60/min), and 20/min on write endpoints.
