<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use App\Models\NotificationPreference;
use App\Models\NotificationEvent;
use App\Jobs\SendEmailNotification;
use App\Jobs\SendSmsNotification;
use App\Jobs\SendPushNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class NotificationService
{
    /**
     * Dispatch a notification to a user
     */
    public function dispatchUserNotification(
        string $type,
        ?User $user,
        array $payload = [],
        array $channels = null
    ): ?UserNotification {
        try {
            // Check if user exists
            if (!$user) {
                Log::warning('Cannot dispatch notification - user is null', [
                    'type' => $type,
                    'payload' => $payload
                ]);
                return null;
            }

            // Check if user has notification preferences enabled
            if (!$this->shouldSendNotification($user, $type)) {
                return null;
            }

            // Check for deduplication
            $eventKey = NotificationEvent::generateEventKey($type, $payload);
            if ($this->isDuplicateEvent($user, $type, $eventKey)) {
                Log::info('Duplicate notification prevented', [
                    'user_id' => $user->id,
                    'type' => $type,
                    'event_key' => $eventKey
                ]);
                return null;
            }

            // Get notification template
            $template = $this->getNotificationTemplate($type, $payload);
            if (!$template) {
                Log::warning('No template found for notification type', ['type' => $type]);
                return null;
            }

            // Determine channels to use
            $channelsToUse = $channels ?? $this->getUserChannels($user, $type);

            // Create the notification record
            $notification = UserNotification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $template['title'],
                'message' => $template['message'],
                'payload' => $payload,
                'channels' => $channelsToUse,
                'dedupe_key' => $eventKey,
            ]);

            // Record the event for deduplication
            NotificationEvent::create([
                'user_id' => $user->id,
                'event_type' => $type,
                'event_key' => $eventKey,
                'processed_at' => now(),
            ]);

            // Dispatch to channels
            $this->dispatchToChannels($notification, $channelsToUse);

            Log::info('Notification dispatched successfully', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'type' => $type,
                'channels' => $channelsToUse
            ]);

            return $notification;

        } catch (\Exception $e) {
            Log::error('Failed to dispatch notification', [
                'user_id' => $user->id,
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Check if user should receive notifications
     */
    protected function shouldSendNotification(User $user, string $type): bool
    {
        // Check global notification preferences
        if (!$user->email_notifications && !$user->sms_notifications) {
            return false;
        }

        // Check quiet hours (if implemented)
        if ($this->isQuietHours()) {
            // Only send critical notifications during quiet hours
            $criticalTypes = ['transfer.payout.failed', 'transfer.refund.completed', 'limits.exceeded'];
            if (!in_array($type, $criticalTypes)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if this is a duplicate event
     */
    protected function isDuplicateEvent(User $user, string $type, string $eventKey): bool
    {
        // Check if event was processed recently (within 5 minutes)
        $recentEvent = NotificationEvent::where('user_id', $user->id)
            ->where('event_type', $type)
            ->where('event_key', $eventKey)
            ->where('created_at', '>', now()->subMinutes(5))
            ->first();

        return $recentEvent !== null;
    }

    /**
     * Get notification template
     */
    protected function getNotificationTemplate(string $type, array $payload): ?array
    {
        $templates = [
            // Authentication
            'auth.login.success' => [
                'title' => 'Welcome back!',
                'message' => 'You have successfully logged in to your TexaPay account.',
            ],
            'auth.login.failed' => [
                'title' => 'Failed Login Attempt',
                'message' => 'We detected a failed login attempt on your account. If this wasn\'t you, please secure your account immediately.',
            ],
            'auth.login.new_device' => [
                'title' => 'New Device Login',
                'message' => 'Your account was accessed from a new device. If this wasn\'t you, please secure your account immediately.',
            ],

            // Profile
            'profile.updated' => [
                'title' => 'Profile Updated',
                'message' => 'Your profile information has been successfully updated.',
            ],
            'security.settings.updated' => [
                'title' => 'Security Settings Updated',
                'message' => 'Your security settings have been updated successfully.',
            ],

            // KYC
            'kyc.started' => [
                'title' => 'KYC Verification Started',
                'message' => 'Your identity verification process has been initiated. Please complete the required steps.',
            ],
            'kyc.completed' => [
                'title' => 'KYC Verification Completed',
                'message' => 'Congratulations! Your identity has been successfully verified.',
            ],
            'kyc.failed' => [
                'title' => 'KYC Verification Failed',
                'message' => 'Your identity verification was unsuccessful. Please try again with clear, valid documents.',
            ],

            // Transfers
            'transfer.quote.created' => [
                'title' => 'Quote Created',
                'message' => 'Your transfer quote has been created. Please confirm the payment within the time limit.',
            ],
            'transfer.quote.expired' => [
                'title' => 'Quote Expired',
                'message' => 'Your transfer quote has expired. Please create a new quote to continue.',
            ],
            'transfer.initiated' => [
                'title' => 'Transfer Initiated',
                'message' => 'Your transfer has been initiated. Please complete the payment on your mobile money app.',
            ],
            'transfer.payin.success' => [
                'title' => 'Payment Received',
                'message' => 'Your payment has been received successfully. Processing payout to recipient.',
            ],
            'transfer.payin.failed' => [
                'title' => 'Payment Failed',
                'message' => 'Your payment could not be processed. Please try again or contact support.',
            ],
            'transfer.payout.success' => [
                'title' => 'Transfer Completed',
                'message' => 'Your transfer has been completed successfully. The recipient has received the funds.',
            ],
            'transfer.payout.failed' => [
                'title' => 'Transfer Failed',
                'message' => 'Your transfer could not be completed. A refund has been initiated automatically.',
            ],
            'transfer.refund.initiated' => [
                'title' => 'Refund Initiated',
                'message' => 'A refund has been initiated for your failed transfer. You will receive your money back shortly.',
            ],
            'transfer.refund.completed' => [
                'title' => 'Refund Completed',
                'message' => 'Your refund has been processed successfully. The funds have been returned to your account.',
            ],

            // Support
            'support.ticket.created' => [
                'title' => 'Support Ticket Created',
                'message' => 'Your support ticket has been created. We will respond within 24 hours.',
            ],
            'support.ticket.replied' => [
                'title' => 'Support Reply',
                'message' => 'You have received a reply to your support ticket.',
            ],
            'support.ticket.closed' => [
                'title' => 'Support Ticket Closed',
                'message' => 'Your support ticket has been closed. If you need further assistance, please create a new ticket.',
            ],

            // Limits
            'limits.warning.daily' => [
                'title' => 'Daily Limit Warning',
                'message' => 'You are approaching your daily transaction limit. Please monitor your usage.',
            ],
            'limits.warning.monthly' => [
                'title' => 'Monthly Limit Warning',
                'message' => 'You are approaching your monthly transaction limit. Please monitor your usage.',
            ],
            'limits.exceeded' => [
                'title' => 'Transaction Limit Exceeded',
                'message' => 'You have exceeded your transaction limit. Please contact support to increase your limits.',
            ],
        ];

        return $templates[$type] ?? null;
    }

    /**
     * Get user's preferred channels for a notification type
     */
    protected function getUserChannels(User $user, string $type): array
    {
        $preference = NotificationPreference::getOrCreateDefault($user, $type);
        return $preference->getEnabledChannels();
    }

    /**
     * Dispatch notification to specific channels
     */
    protected function dispatchToChannels(UserNotification $notification, array $channels): void
    {
        foreach ($channels as $channel) {
            switch ($channel) {
                case 'email':
                    SendEmailNotification::dispatch($notification);
                    break;
                case 'sms':
                    SendSmsNotification::dispatch($notification);
                    break;
                case 'push':
                    SendPushNotification::dispatch($notification);
                    break;
                case 'in_app':
                    // In-app notifications are already stored in database
                    break;
            }
        }
    }

    /**
     * Check if it's currently quiet hours
     */
    protected function isQuietHours(): bool
    {
        // TODO: Implement quiet hours logic based on user timezone
        // For now, return false (no quiet hours)
        return false;
    }

    /**
     * Get user's unread notification count
     */
    public function getUnreadCount(User $user): int
    {
        return UserNotification::where('user_id', $user->id)
            ->unread()
            ->count();
    }

    /**
     * Get user's recent notifications
     */
    public function getRecentNotifications(User $user, int $limit = 20)
    {
        return UserNotification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(User $user): int
    {
        return UserNotification::where('user_id', $user->id)
            ->unread()
            ->update(['read_at' => now()]);
    }
}


