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

            // Determine channels to use (explicit override or user preferences)
            $channelsToUse = $channels ?? $this->getUserChannels($user, $type);

            // Throttle per user+type to prevent floods (configurable)
            $throttleSecs = (int) config('notifications.throttle_seconds', 60);
            if ($throttleSecs > 0) {
                $throttleKey = sprintf('notify:%d:%s', $user->id, $type);
                if (!Cache::add($throttleKey, 1, now()->addSeconds($throttleSecs))) {
                    Log::info('Notification throttled', [
                        'user_id' => $user->id,
                        'type' => $type,
                        'secs' => $throttleSecs,
                    ]);
                    return null;
                }
            }

            // Check if user has notification preferences enabled (per-channel and quiet-hours aware)
            if (!$this->shouldSendNotification($user, $type, $channelsToUse)) {
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
    protected function shouldSendNotification(User $user, string $type, array $channels): bool
    {
        // Always allow password reset notifications regardless of global toggles
        if (str_starts_with($type, 'auth.password.reset.')) {
            return true;
        }

        // If email channel requested but user disabled email, drop email for this notification
        if (in_array('email', $channels) && !$user->email_notifications) {
            $channels = array_values(array_diff($channels, ['email']));
        }
        // If sms channel requested but user disabled sms, drop sms
        if (in_array('sms', $channels) && !$user->sms_notifications) {
            $channels = array_values(array_diff($channels, ['sms']));
        }
        // If no channels remain (e.g., only push/in_app not requested), do not send
        if (empty($channels)) {
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
        $templates = (array) config('notifications.templates', []);
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
        $queue = env('NOTIFICATION_QUEUE_NAME', 'notifications');
        foreach ($channels as $channel) {
            switch ($channel) {
                case 'email':
                    SendEmailNotification::dispatch($notification)->onQueue($queue);
                    break;
                case 'sms':
                    SendSmsNotification::dispatch($notification)->onQueue($queue);
                    break;
                case 'push':
                    SendPushNotification::dispatch($notification)->onQueue($queue);
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
        $cfg = (array) config('notifications.quiet_hours', []);
        if (empty($cfg['enabled'])) { return false; }
        $start = $cfg['start'] ?? '22:00';
        $end = $cfg['end'] ?? '06:00';
        $now = now();
        $startT = now()->setTimeFromTimeString($start);
        $endT = now()->setTimeFromTimeString($end);
        if ($startT->lte($endT)) {
            return $now->between($startT, $endT);
        }
        // Overnight window (e.g., 22:00 -> 06:00 next day)
        return $now->gte($startT) || $now->lte($endT);
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


