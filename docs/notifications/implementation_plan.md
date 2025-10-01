# Notification System Implementation Plan

## Executive Summary (code-discovered only)

Based on comprehensive codebase analysis, TexaPay is a money transfer application that enables users to send XAF (Cameroon) to NGN (Nigeria) through mobile money providers (PawaPay) and bank transfers (SafeHaven). The application currently handles:

**User-visible flows** (discovered in code):
- **Authentication**: Phone-based login with optional PIN challenge (`AuthController::login:100-162`, `AuthController::verifyPinChallenge:34-67`)
- **Profile Management**: Personal info, security settings, notification preferences (`ProfileController:80-130`, `SecurityController:23-61`)
- **KYC Verification**: Smile ID integration with status tracking (`SmileIdController::callback:98-148`)
- **Money Transfers**: Quote creation, pay-in initiation, payout processing, refund handling (`TransferController:155-480`, `ProcessPawaPayDeposit:26-81`)
- **Support System**: Ticket creation and management (`SupportController:24-52`)
- **Transaction Limits**: Daily/monthly limits with warnings (`UserLimit:40-83`, `LimitCheckService`)

**State change sources** (discovered in code):
- **Controllers**: `TransferController`, `AuthController`, `ProfileController`, `SecurityController`, `SupportController`
- **Services**: `RefundService`, `LimitCheckService`, `PawaPay`, `SafeHaven`
- **Jobs**: `ProcessPawaPayDeposit`, `ProcessPawaPayRefund`
- **Webhooks**: `PawaPayWebhookController`, `PawaPayRefundWebhookController`

**Existing communication infrastructure** (discovered in code):
- **Email**: Laravel Mail system configured (`config/mail.php:1-119`), `SupportTicketSubmitted` mailable exists (`app/Mail/SupportTicketSubmitted.php:1-28`)
- **User Preferences**: `email_notifications` and `sms_notifications` boolean fields on users table (`database/migrations/2025_09_28_120100_add_notification_prefs_to_users_table.php:12-17`)
- **Mobile API**: Notification preferences API endpoints exist (`app/Http/Controllers/Api/ProfileController::notifications:27-37`)

## Event Inventory (discovered)

| Event | Trigger Source | State Fields | Payload Available | Current User Visibility | Citations |
|-------|----------------|--------------|-------------------|------------------------|-----------|
| **Authentication Events** |
| `user.registered` | `AuthController::register:69-93` | `users.name`, `users.phone`, `users.email` | User object | Dashboard redirect | `routes/web.php:51-52` |
| `user.login.success` | `AuthController::login:144-155` | `login_histories` table | User, IP, user agent | Dashboard redirect | `app/Models/LoginHistory.php:1-33` |
| `user.login.failed` | `AuthController::login:110-121` | `login_histories` table | User ID, IP, user agent | Error message | `app/Models/LoginHistory.php:1-33` |
| `user.login.pin_challenge` | `AuthController::login:125-142` | Session data | User ID, challenge expiry | PIN challenge view | `routes/web.php:56-57` |
| `user.logout` | `AuthController::logout:164-170` | Session invalidation | User object | Login redirect | `routes/web.php:58` |
| **Profile Events** |
| `profile.personal_info.updated` | `ProfileController::updatePersonalInfo:86-107` | `users.full_name`, `users.notification_email`, `users.avatar_path` | User object | Success message | `routes/web.php:70-71` |
| `profile.security.updated` | `SecurityController::updateToggles:23-42` | `user_security_settings` table | Settings object | Success message | `routes/web.php:75-76` |
| `profile.pin.updated` | `SecurityController::updatePin:44-61` | `user_security_settings.pin_hash` | Settings object | Success message | `routes/web.php:76` |
| `account.deleted` | `ProfileController::deleteAccount:48-78` | User soft delete | User object | Login redirect | `routes/web.php:80` |
| **KYC Events** |
| `kyc.started` | `SmileIdController::start:64-96` | `users.kyc_status = 'pending'`, `users.kyc_attempts++` | User, job_id | JSON response | `routes/web.php:199-200` |
| `kyc.completed` | `SmileIdController::callback:98-148` | `users.kyc_status`, `users.kyc_level`, `users.kyc_verified_at` | User, provider_ref, meta | Webhook processed | `routes/web.php:97-99` |
| `kyc.failed` | `SmileIdController::callback:98-148` | `users.kyc_status`, `users.kyc_meta` | User, failure reason | Webhook processed | `routes/web.php:97-99` |
| **Transfer Events** |
| `transfer.quote.created` | `TransferController::createQuote:155-231` | `quotes` table | Quote object | Session redirect | `routes/web.php:27-29` |
| `transfer.quote.expired` | `TransferController::showQuoteForm:125-153` | `quotes.status = 'expired'` | Quote object | UI refresh | `app/Models/Quote.php:1-33` |
| `transfer.initiated` | `TransferController::confirmPayIn:235-480` | `transfers` table | Transfer object | Receipt redirect | `routes/web.php:30-31` |
| `transfer.payin.pending` | `ProcessPawaPayDeposit::handlePendingPayment:144-156` | `transfers.payin_status = 'pending'` | Transfer, timeline | Receipt page | `app/Jobs/ProcessPawaPayDeposit.php:1-351` |
| `transfer.payin.success` | `ProcessPawaPayDeposit::handleCompletedPayment:83-104` | `transfers.payin_status = 'success'` | Transfer, timeline | Receipt page | `app/Jobs/ProcessPawaPayDeposit.php:1-351` |
| `transfer.payin.failed` | `ProcessPawaPayDeposit::handleFailedPayment:106-142` | `transfers.payin_status = 'failed'` | Transfer, timeline, reason | Receipt page | `app/Jobs/ProcessPawaPayDeposit.php:1-351` |
| `transfer.payout.initiated` | `ProcessPawaPayDeposit::initiatePayout:158-350` | `transfers.payout_status = 'processing'` | Transfer, payout_ref | Receipt page | `app/Jobs/ProcessPawaPayDeposit.php:1-351` |
| `transfer.payout.success` | `ProcessPawaPayDeposit::initiatePayout:271-281` | `transfers.status = 'completed'` | Transfer, timeline | Receipt page | `app/Jobs/ProcessPawaPayDeposit.php:1-351` |
| `transfer.payout.failed` | `ProcessPawaPayDeposit::initiatePayout:292-335` | `transfers.status = 'failed'` | Transfer, timeline, reason | Receipt page | `app/Jobs/ProcessPawaPayDeposit.php:1-351` |
| `transfer.refund.initiated` | `RefundService::refundFailedPayout:37-220` | `transfers.refund_id`, `transfers.refund_status` | Transfer, refund_id | Timeline update | `app/Services/RefundService.php:1-264` |
| `transfer.refund.completed` | `ProcessPawaPayRefund` (webhook) | `transfers.refund_status = 'SUCCESS'` | Transfer, refund_id | Timeline update | `routes/web.php:102-104` |
| **Support Events** |
| `support.ticket.created` | `SupportController::submitTicket:24-52` | `support_tickets` table | Ticket object | Email sent | `routes/web.php:86-87` |
| `support.ticket.replied` | **NOT FOUND** | **NOT FOUND** | **NOT FOUND** | **NOT FOUND** | **NOT FOUND** |
| `support.ticket.closed` | **NOT FOUND** | **NOT FOUND** | **NOT FOUND** | **NOT FOUND** | **NOT FOUND** |
| **Limit Events** |
| `limits.warning.daily` | `LimitCheckService::getLimitWarnings` | `user_limits` table | Usage, limits | Dashboard display | `app/Services/LimitCheckService.php:1-200` |
| `limits.warning.monthly` | `LimitCheckService::getLimitWarnings` | `user_limits` table | Usage, limits | Dashboard display | `app/Services/LimitCheckService.php:1-200` |
| `limits.exceeded` | `User::canMakeTransaction:138-207` | `user_limits` table | Usage, limits | Error message | `app/Models/User.php:138-207` |

## Notification Catalog (grounded in code)

### Authentication Notifications
- **`auth.login.success`**
  - **Channels**: In-app, Email
  - **When to send**: After successful login (`AuthController::login:144-155`)
  - **Payload**: `user.name`, `user.email`, `login.ip_address`, `login.created_at`
  - **CTA**: Dashboard link (`route('dashboard')`)

- **`auth.login.failed`**
  - **Channels**: In-app, Email (security alert)
  - **When to send**: After failed login attempt (`AuthController::login:110-121`)
  - **Payload**: `user.email`, `login.ip_address`, `login.user_agent`, `login.created_at`
  - **CTA**: Password reset link (`route('password.request')`)

- **`auth.login.new_device`**
  - **Channels**: In-app, Email, SMS
  - **When to send**: **NOT FOUND** - needs implementation
  - **Payload**: **NOT FOUND**
  - **CTA**: **NOT FOUND**

### Profile Notifications
- **`profile.updated`**
  - **Channels**: In-app, Email
  - **When to send**: After profile update (`ProfileController::updatePersonalInfo:100-106`)
  - **Payload**: `user.full_name`, `user.notification_email`, `changes` array
  - **CTA**: Profile settings link (`route('profile.personal')`)

- **`security.settings.updated`**
  - **Channels**: In-app, Email
  - **When to send**: After security settings change (`SecurityController::updateToggles:34-39`)
  - **Payload**: `settings.two_factor_enabled`, `settings.pin_enabled`, `settings.sms_login_enabled`
  - **CTA**: Security settings link (`route('profile.security')`)

### KYC Notifications
- **`kyc.started`**
  - **Channels**: In-app, Email
  - **When to send**: After KYC session start (`SmileIdController::start:86-89`)
  - **Payload**: `user.name`, `kyc.provider`, `kyc.job_id`
  - **CTA**: KYC status page (`route('kyc.status')`)

- **`kyc.completed`**
  - **Channels**: In-app, Email, SMS
  - **When to send**: After successful KYC verification (`SmileIdController::callback:135-137`)
  - **Payload**: `user.name`, `kyc.level`, `kyc.verified_at`
  - **CTA**: Dashboard link (`route('dashboard')`)

- **`kyc.failed`**
  - **Channels**: In-app, Email
  - **When to send**: After failed KYC verification (`SmileIdController::callback:122-134`)
  - **Payload**: `user.name`, `kyc.reason`, `kyc.retry_available`
  - **CTA**: KYC retry link (`route('kyc.index')`)

### Transfer Notifications
- **`transfer.quote.created`**
  - **Channels**: In-app
  - **When to send**: After quote creation (`TransferController::createQuote:229-230`)
  - **Payload**: `quote.amount_xaf`, `quote.receive_ngn_minor`, `quote.expires_at`
  - **CTA**: Quote confirmation link (`route('transfer.quote')`)

- **`transfer.quote.expired`**
  - **Channels**: In-app
  - **When to send**: When quote expires (`TransferController::showQuoteForm:139-144`)
  - **Payload**: `quote.amount_xaf`, `quote.expired_at`
  - **CTA**: New quote link (`route('transfer.quote')`)

- **`transfer.initiated`**
  - **Channels**: In-app, Email
  - **When to send**: After transfer creation (`TransferController::confirmPayIn:476-479`)
  - **Payload**: `transfer.id`, `transfer.amount_xaf`, `transfer.recipient_account_name`, `transfer.status`
  - **CTA**: Transfer receipt link (`route('transfer.receipt', $transfer)`)

- **`transfer.payin.success`**
  - **Channels**: In-app, Email, SMS
  - **When to send**: After successful pay-in (`ProcessPawaPayDeposit::handleCompletedPayment:96-101`)
  - **Payload**: `transfer.id`, `transfer.amount_xaf`, `transfer.payin_at`
  - **CTA**: Transfer receipt link (`route('transfer.receipt', $transfer)`)

- **`transfer.payin.failed`**
  - **Channels**: In-app, Email, SMS
  - **When to send**: After failed pay-in (`ProcessPawaPayDeposit::handleFailedPayment:130-134`)
  - **Payload**: `transfer.id`, `transfer.amount_xaf`, `transfer.failure_reason`
  - **CTA**: Transfer receipt link (`route('transfer.receipt', $transfer)`)

- **`transfer.payout.success`**
  - **Channels**: In-app, Email, SMS
  - **When to send**: After successful payout (`ProcessPawaPayDeposit::initiatePayout:277-281`)
  - **Payload**: `transfer.id`, `transfer.amount_xaf`, `transfer.receive_ngn_minor`, `transfer.recipient_account_name`
  - **CTA**: Transfer receipt link (`route('transfer.receipt', $transfer)`)

- **`transfer.payout.failed`**
  - **Channels**: In-app, Email, SMS
  - **When to send**: After failed payout (`ProcessPawaPayDeposit::initiatePayout:313-316`)
  - **Payload**: `transfer.id`, `transfer.amount_xaf`, `transfer.failure_reason`, `transfer.refund_initiated`
  - **CTA**: Transfer receipt link (`route('transfer.receipt', $transfer)`)

- **`transfer.refund.initiated`**
  - **Channels**: In-app, Email, SMS
  - **When to send**: After refund initiation (`RefundService::refundFailedPayout:144-170`)
  - **Payload**: `transfer.id`, `transfer.refund_id`, `transfer.amount_xaf`
  - **CTA**: Transfer receipt link (`route('transfer.receipt', $transfer)`)

- **`transfer.refund.completed`**
  - **Channels**: In-app, Email, SMS
  - **When to send**: After refund completion (webhook processing)
  - **Payload**: `transfer.id`, `transfer.refund_id`, `transfer.amount_xaf`, `transfer.refund_completed_at`
  - **CTA**: Transfer receipt link (`route('transfer.receipt', $transfer)`)

### Support Notifications
- **`support.ticket.created`**
  - **Channels**: In-app, Email
  - **When to send**: After ticket creation (`SupportController::submitTicket:32-37`)
  - **Payload**: `ticket.id`, `ticket.subject`, `ticket.priority`, `ticket.created_at`
  - **CTA**: Ticket details link (`route('support.tickets')`)

- **`support.ticket.replied`**
  - **Channels**: In-app, Email
  - **When to send**: **NOT FOUND** - needs implementation
  - **Payload**: **NOT FOUND**
  - **CTA**: **NOT FOUND**

### Limit Notifications
- **`limits.warning.daily`**
  - **Channels**: In-app, Email
  - **When to send**: When daily limit utilization > 80% (`LimitCheckService::getLimitWarnings`)
  - **Payload**: `usage.amount`, `limits.daily_limit_xaf`, `usage.percentage`
  - **CTA**: Limits page link (`route('profile.limits')`)

- **`limits.warning.monthly`**
  - **Channels**: In-app, Email
  - **When to send**: When monthly limit utilization > 80% (`LimitCheckService::getLimitWarnings`)
  - **Payload**: `usage.amount`, `limits.monthly_limit_xaf`, `usage.percentage`
  - **CTA**: Limits page link (`route('profile.limits')`)

- **`limits.exceeded`**
  - **Channels**: In-app, Email, SMS
  - **When to send**: When limit exceeded (`User::canMakeTransaction:155-175`)
  - **Payload**: `limit_type`, `current_usage`, `limit_amount`, `remaining`
  - **CTA**: Limits page link (`route('profile.limits')`)

## Hook Points Map (precise insertion plan)

### Synchronous Hooks (Controllers/Services)

1. **`AuthController::login:155`** - After successful login
   - **Available variables**: `$user`, `$request`
   - **Hook**: `dispatchUserNotification('auth.login.success', $user, ['ip_address' => $request->ip()])`

2. **`AuthController::login:121`** - After failed login
   - **Available variables**: `$user`, `$request`
   - **Hook**: `dispatchUserNotification('auth.login.failed', $user, ['ip_address' => $request->ip()])`

3. **`ProfileController::updatePersonalInfo:100-106`** - After profile update
   - **Available variables**: `$user`, `$validated`
   - **Hook**: `dispatchUserNotification('profile.updated', $user, ['changes' => $validated])`

4. **`SecurityController::updateToggles:34-39`** - After security settings update
   - **Available variables**: `$user`, `$settings`
   - **Hook**: `dispatchUserNotification('security.settings.updated', $user, ['settings' => $settings->toArray()])`

5. **`SmileIdController::start:86-89`** - After KYC session start
   - **Available variables**: `$user`, `$session`
   - **Hook**: `dispatchUserNotification('kyc.started', $user, ['job_id' => $session['partner_params']['job_id']])`

6. **`SmileIdController::callback:135-137`** - After KYC completion
   - **Available variables**: `$user`, `$mapped`
   - **Hook**: `dispatchUserNotification('kyc.completed', $user, ['level' => $mapped['kyc_level']])`

7. **`TransferController::createQuote:229-230`** - After quote creation
   - **Available variables**: `$quote`, `$userId`
   - **Hook**: `dispatchUserNotification('transfer.quote.created', User::find($userId), ['quote' => $quote->toArray()])`

8. **`TransferController::confirmPayIn:476-479`** - After transfer initiation
   - **Available variables**: `$transfer`, `$request`
   - **Hook**: `dispatchUserNotification('transfer.initiated', $transfer->user, ['transfer' => $transfer->toArray()])`

9. **`SupportController::submitTicket:32-37`** - After ticket creation
   - **Available variables**: `$ticket`, `$request`
   - **Hook**: `dispatchUserNotification('support.ticket.created', $ticket->user, ['ticket' => $ticket->toArray()])`

### Asynchronous Hooks (Jobs/Webhooks)

1. **`ProcessPawaPayDeposit::handleCompletedPayment:96-101`** - After pay-in success
   - **Available variables**: `$transfer`, `$timeline`
   - **Hook**: `dispatchUserNotification('transfer.payin.success', $transfer->user, ['transfer' => $transfer->toArray()])`

2. **`ProcessPawaPayDeposit::handleFailedPayment:130-134`** - After pay-in failure
   - **Available variables**: `$transfer`, `$timeline`, `$reason`
   - **Hook**: `dispatchUserNotification('transfer.payin.failed', $transfer->user, ['transfer' => $transfer->toArray(), 'reason' => $reason])`

3. **`ProcessPawaPayDeposit::initiatePayout:277-281`** - After payout success
   - **Available variables**: `$transfer`, `$timeline`
   - **Hook**: `dispatchUserNotification('transfer.payout.success', $transfer->user, ['transfer' => $transfer->toArray()])`

4. **`ProcessPawaPayDeposit::initiatePayout:313-316`** - After payout failure
   - **Available variables**: `$transfer`, `$timeline`, `$reason`
   - **Hook**: `dispatchUserNotification('transfer.payout.failed', $transfer->user, ['transfer' => $transfer->toArray(), 'reason' => $reason])`

5. **`RefundService::refundFailedPayout:144-170`** - After refund initiation
   - **Available variables**: `$transfer`, `$refundId`
   - **Hook**: `dispatchUserNotification('transfer.refund.initiated', $transfer->user, ['transfer' => $transfer->toArray(), 'refund_id' => $refundId])`

### Idempotency Guards

**NOT FOUND** - No existing idempotency guards for notifications. Need to implement:
- Database-based deduplication using `notification_events` table
- Time-based deduplication (e.g., same event within 5 minutes)
- User preference checks before sending

## Data & Storage (minimal deltas only if missing)

### Existing Tables (discovered)
- `users` table has `email_notifications` and `sms_notifications` boolean fields (`database/migrations/2025_09_28_120100_add_notification_prefs_to_users_table.php:12-17`)
- `support_tickets` table exists for support notifications (`database/migrations/2025_09_28_122500_create_support_tickets_table.php:1-31`)

### Missing Tables (need to create)

```sql
-- User notifications table
CREATE TABLE user_notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    payload JSON,
    channels JSON NOT NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    dedupe_key VARCHAR(255) NULL,
    INDEX idx_user_unread (user_id, read_at),
    INDEX idx_user_type (user_id, type),
    INDEX idx_dedupe (dedupe_key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notification preferences table (granular per type/channel)
CREATE TABLE notification_preferences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    email_enabled BOOLEAN DEFAULT TRUE,
    sms_enabled BOOLEAN DEFAULT FALSE,
    push_enabled BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY unique_user_type (user_id, notification_type),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notification events table (for deduplication)
CREATE TABLE notification_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    event_key VARCHAR(255) NOT NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    UNIQUE KEY unique_event (user_id, event_type, event_key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## API/UI Surfacing (present state)

### Existing Endpoints (discovered)
- **Mobile API**: `/mobile/profile/notifications` (GET/PUT) - `app/Http/Controllers/Api/ProfileController::notifications:27-52`
- **Web UI**: `/profile/notifications` (GET/POST) - `app/Http/Controllers/ProfileController::notifications:109-130`
- **Notification preferences**: Stored in `users.email_notifications` and `users.sms_notifications` fields

### Missing Endpoints (need to create)
- **Notification feed**: `GET /api/notifications` - List user notifications
- **Mark as read**: `PUT /api/notifications/{id}/read` - Mark notification as read
- **Mark all as read**: `PUT /api/notifications/read-all` - Mark all notifications as read
- **Notification settings**: `GET /api/notifications/settings` - Get granular notification preferences

### Existing Views (discovered)
- **Notification preferences**: `resources/views/profile/notifications.blade.php:1-37` - Basic email/SMS toggles

### Missing Views (need to create)
- **Notification feed**: `resources/views/notifications/index.blade.php` - List all notifications
- **Notification settings**: `resources/views/notifications/settings.blade.php` - Granular preferences

## Delivery & Policy Rules

### De-duplication Strategy
**NOT FOUND** - Need to implement:
- **Time-based**: Same event type within 5 minutes = dedupe
- **Content-based**: Same payload hash = dedupe
- **User-based**: Per-user, per-event-type deduplication

### Quiet Hours
**NOT FOUND** - Need to implement:
- User timezone detection
- Configurable quiet hours (e.g., 10 PM - 8 AM)
- Emergency notifications bypass quiet hours

### Failure Handling
**NOT FOUND** - Need to implement:
- Email fallback for SMS failures
- Retry mechanism with exponential backoff
- Dead letter queue for failed notifications

### Localization
**NOT FOUND** - Need to implement:
- Multi-language support for notification templates
- Currency formatting (XAF/NGN)
- Timezone handling (Africa/Douala)

## Gap List & Minimal Changes

### 1. **NOT FOUND**: New device login detection
- **Insertion point**: `AuthController::login:155` - after successful login
- **Minimal change**: Compare `$request->userAgent()` with stored device fingerprints

### 2. **NOT FOUND**: Support ticket reply notifications
- **Insertion point**: New method in `SupportController` - after admin reply
- **Minimal change**: Add `reply` method with notification dispatch

### 3. **NOT FOUND**: Support ticket closure notifications
- **Insertion point**: New method in `SupportController` - after ticket closure
- **Minimal change**: Add `close` method with notification dispatch

### 4. **NOT FOUND**: Limit warning notifications
- **Insertion point**: `LimitCheckService::getLimitWarnings` - when warnings generated
- **Minimal change**: Add notification dispatch for warnings > 80% utilization

### 5. **NOT FOUND**: Quote expiration notifications
- **Insertion point**: `TransferController::showQuoteForm:139-144` - when quote expires
- **Minimal change**: Add notification dispatch before quote status update

### 6. **NOT FOUND**: Refund completion notifications
- **Insertion point**: `ProcessPawaPayRefund` job - after refund webhook processing
- **Minimal change**: Add notification dispatch after status update

## READY FOR APPROVAL

### Checklist of Implementation Tasks

**Database Changes:**
- [ ] Create `user_notifications` table migration
- [ ] Create `notification_preferences` table migration  
- [ ] Create `notification_events` table migration

**Core Infrastructure:**
- [ ] Create `NotificationService` class
- [ ] Create `UserNotification` model
- [ ] Create `NotificationPreference` model
- [ ] Create notification job classes for async processing

**API Endpoints:**
- [ ] Add notification feed endpoints to mobile API
- [ ] Add notification management endpoints to web routes
- [ ] Update existing profile controllers to use new notification system

**UI Components:**
- [ ] Create notification feed view
- [ ] Create granular notification settings view
- [ ] Add notification badge to navigation
- [ ] Update existing notification preferences to use new system

**Email Templates:**
- [ ] Create email templates for each notification type
- [ ] Create SMS templates for each notification type
- [ ] Create in-app notification templates

**Hook Integration:**
- [ ] Add notification hooks to all identified trigger points
- [ ] Implement idempotency guards
- [ ] Add user preference checks
- [ ] Add quiet hours logic

**Testing:**
- [ ] Unit tests for notification service
- [ ] Integration tests for notification flow
- [ ] End-to-end tests for notification delivery

---

**NEXT STEP:** Awaiting approval to implement. I will proceed with code changes only after you confirm.
