# TexaPay Mobile App (React Native + Expo)

This folder will contain the standalone mobile app for iOS and Android.

## Phase 0 — Scope, IDs, Environments

- **App name**: TexaPay
- **Identifiers**:
  - iOS bundle ID: `com.texapay`
  - Android package: `com.texapay`
- **Minimum OS targets (proposed)**:
  - iOS 14+
  - Android API 24+ (Android 7.0)
- **Environments**:
  - Sandbox (local):
    - iOS Simulator: `http://localhost:<port>`
    - Android Emulator: `http://10.0.2.2:<port>`
    - Physical devices: `http://<LAN-IP>:<port>` or HTTPS tunnel
  - Production: `https://bg.texa.ng`
- **API base path**: `/api/mobile/*` (parity with PWA rewrites and caching)
- **Auth refresh strategy (RN)**: Prefer token-in-body (access/refresh tokens in secure storage) over HttpOnly cookies.

## Next Steps

1. Confirm Minimum OS targets.
2. Confirm Sandbox API URL and port (and whether base path is `/api/mobile/*`).
3. Decide on RN refresh approach (token-in-body vs cookies); default will be token-in-body.
4. Scaffold Expo (TypeScript) app and initial navigation in this `mobile/` directory.

## References

- PWA rewrites and caching: `pwa/next.config.ts`
- API client behavior: `pwa/src/lib/api.ts`
- Offline queue pattern: `pwa/src/lib/offline-queue.ts`
- OpenAPI: `openapi/mobile-openapi.yaml`
