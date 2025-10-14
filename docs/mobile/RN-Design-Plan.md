# TexaPay Mobile (React Native + Expo) — Phased Design Plan

## Phase 0 — Scope, IDs, Environments (1 day)
- **App name**: TexaPay
- **Identifiers**: iOS `com.texapay`, Android `com.texapay`
- **Environments**:
  - Sandbox: localhost (decide exact URL/port; iOS: `http://localhost:<port>`, Android: `http://10.0.2.2:<port>`, devices: `http://<LAN-IP>:<port>`)
  - Production: `https://bg.texa.ng`
- **Parity references**:
  - PWA rewrites and caching: `pwa/next.config.ts`
  - API client behavior: `pwa/src/lib/api.ts`
  - Offline queue pattern: `pwa/src/lib/offline-queue.ts`
  - OpenAPI: `openapi/mobile-openapi.yaml`

## Phase 1 — IA, Navigation Map, Low‑Fi Wireframes (2–3 days)
- **Information Architecture**
  - Auth, Onboarding/KYC, Dashboard, Transfers, Banks, Notifications, Profile, Support, Settings, Global states
- **Navigation**
  - Root: Auth Stack (unauthenticated) → App Tabs (authenticated)
  - Tabs: Dashboard, Transfers, Notifications, Profile
  - Modals: Quote → Recipient/Bank → Confirm → Verify → Success → Receipt
- **Low‑fidelity wireframes**
  - All screens and flows listed in Phase 2

## Phase 2 — Design System and UI Kit (2–3 days)
- **UI kit**: NativeWind (Tailwind) + `lucide-react-native`
- **Tokens**: colors (light/dark), typography scale, spacing (4px grid), radii
- **Components**: Button, Input (text/phone/amount/OTP), Card, ListItem, Badge, Banner (offline), Loader, Toast
- **Accessibility**: base roles/labels, touch targets, contrast

## Phase 3 — Static Screens (no API) (4–6 days)
- **Auth**
  - Login, Register, Forgot Password, Reset Password, 2FA/OTP Verify
- **Onboarding/KYC**
  - KYC Intro, Personal Info, Document Capture (placeholder), Selfie/Verify (placeholder), KYC Success
- **Dashboard**
  - Home (balances, quick actions, recent activity)
- **Transfers**
  - Quote, Recipient/Bank, Confirm, Verify, Success, Receipt, Timeline, Transfers List
- **Banks**
  - Bank Picker/List, Search, Details
- **Notifications**
  - Feed, Preferences
- **Profile**
  - Overview, Personal Info, Security, Limits
- **Support**
  - Support Home, Help Articles, Ticket List, Ticket Detail
- **Settings**
  - Devices, Policies, About
- **Global**
  - Offline Screen, Error/Fallback, Loading Skeletons, Empty States

## Phase 4 — Mocks, Sample Data, State Demonstrations (2–3 days)
- **Mock services**: factories for banks, dashboard, transfers, notifications
- **States**: loading, error, empty for all list/detail screens
- **Offline banner**: show/hide using connectivity listener

## Phase 5 — Backend Integration (typed client) (3–5 days)
- **OpenAPI client**: generate from `openapi/mobile-openapi.yaml` (typescript-axios)
- **Axios client**: baseURL per environment/platform; headers; timeouts; interceptors
- **Auth storage**: access/refresh tokens in secure storage (Expo Secure Store)
- **Refresh strategy**: prefer token-in-body for RN (cookie-less). If cookie-based is required, integrate RN cookie jar.

## Phase 6 — Notifications and Offline Reliability (2–4 days)
- **Push**: Expo Notifications; device token registration endpoint in backend; topic/user routing
- **Offline queue**: port `pwa/src/lib/offline-queue.ts` logic to RN using AsyncStorage; exponential backoff; category priority (transfers > profile > notifications > general)
- **Background retries**: basic app-foreground retries; consider background tasks later

## Phase 7 — QA, Accessibility, and Release Prep (3–5 days)
- **QA**: screen checklist walkthrough, regression on navigation and states
- **A11y**: VoiceOver/TalkBack pass, dynamic type sanity
- **Performance**: initial render, list virtualization
- **Branding**: icons, splash, theming polish
- **EAS setup**: `eas.json`, build profiles, signing, store metadata

---

## Screen Checklist (MVP)
- **Auth**: Login, Register, Forgot, Reset, 2FA/OTP
- **KYC**: Intro, Personal Info, Document Capture, Selfie/Verify, Success
- **Dashboard**: Home
- **Transfers**: Quote, Recipient/Bank, Confirm, Verify, Success, Receipt, Timeline, Transfers List
- **Banks**: Picker/List, Search, Details
- **Notifications**: Feed, Preferences
- **Profile**: Overview, Personal Info, Security, Limits
- **Support**: Home, Help Articles, Ticket List, Ticket Detail
- **Settings**: Devices, Policies, About
- **Global**: Offline, Error, Loading, Empty

## Technical Notes
- **Design system**: NativeWind (Tailwind), component library built in-house for control
- **Navigation**: `@react-navigation/*` (Auth Stack, Tabs, Modals)
- **State**: React Query for server cache; lightweight state (Zustand/Context) if needed
- **Forms**: `react-hook-form` + `zod`
- **Storage**: Expo Secure Store (tokens), AsyncStorage (non-sensitive UI state)
- **Env**: platform-aware sandbox URLs; production `https://bg.texa.ng`

## Open Questions
- Confirm sandbox URL/port and base path (e.g., `/api/mobile/*`)
- Confirm RN refresh approach (token-in-body vs cookies)
- Provide brand colors, logo, icon, splash (or use placeholders initially)
