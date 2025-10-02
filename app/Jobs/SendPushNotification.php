<?php

namespace App\Jobs;

use App\Models\UserNotification;
use App\Models\UserDevice;
use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public UserNotification $notification
    ) {
    }

    public function handle(): void
    {
        try {
            $user = $this->notification->user;
            
            // Check if user has push notifications enabled for this notification type
            if (!$this->shouldSendPushNotification($user)) {
                Log::info('Push notification skipped - user preferences', [
                    'user_id' => $user->id,
                    'notification_id' => $this->notification->id,
                    'type' => $this->notification->type,
                ]);
                return;
            }

            // Get user's active devices
            $devices = UserDevice::getActiveDevicesForUser($user);
            
            if ($devices->isEmpty()) {
                Log::info('No active devices found for push notification', [
                    'user_id' => $user->id,
                    'notification_id' => $this->notification->id,
                ]);
                return;
            }

            // Prepare notification data
            $notificationData = $this->prepareNotificationData();
            $payload = $this->preparePayload();

            // Resolve FcmService from container (constructor DI removed for queued compatibility)
            $fcmService = app(FcmService::class);
            // Send to all active devices
            $results = $fcmService->sendToDevices($devices->toArray(), $notificationData, $payload);

            $successCount = count(array_filter($results));
            $totalCount = count($results);

            Log::info('Push notification sent', [
                'user_id' => $user->id,
                'notification_id' => $this->notification->id,
                'success_count' => $successCount,
                'total_count' => $totalCount,
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send push notification', [
                'notification_id' => $this->notification->id,
                'user_id' => $this->notification->user_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Push notification job failed permanently', [
            'notification_id' => $this->notification->id,
            'user_id' => $this->notification->user_id,
            'error' => $exception->getMessage()
        ]);
    }

    /**
     * Check if user has push notifications enabled for this notification type
     */
    private function shouldSendPushNotification($user): bool
    {
        // Check if push is enabled in the notification channels
        $channels = $this->notification->channels ?? [];
        if (!in_array('push', $channels)) {
            return false;
        }

        // Check user's notification preferences
        $preference = $user->notificationPreferences()
            ->where('notification_type', $this->notification->type)
            ->first();

        if ($preference && !$preference->push_enabled) {
            return false;
        }

        return true;
    }

    /**
     * Prepare notification data for FCM
     */
    private function prepareNotificationData(): array
    {
        $payload = $this->notification->payload ?? [];
        
        // Get title and message from payload or use defaults
        $title = $payload['title'] ?? $this->getDefaultTitle();
        $body = $payload['message'] ?? $this->getDefaultMessage();

        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type,
            'title' => $title,
            'body' => $body,
            'click_action' => $this->getClickAction(),
        ];
    }

    /**
     * Prepare additional payload data
     */
    private function preparePayload(): array
    {
        $payload = $this->notification->payload ?? [];
        
        return array_merge($payload, [
            'notification_id' => (string) $this->notification->id,
            'type' => $this->notification->type,
            'created_at' => $this->notification->created_at->toISOString(),
        ]);
    }

    /**
     * Get default title based on notification type
     */
    private function getDefaultTitle(): string
    {
        return match ($this->notification->type) {
            'auth.login.success' => 'Welcome back!',
            'auth.login.failed' => 'Login attempt failed',
            'transfer.payin.success' => 'Payment received',
            'transfer.payout.success' => 'Transfer completed',
            'transfer.payin.failed' => 'Payment failed',
            'transfer.payout.failed' => 'Transfer failed',
            'kyc.completed' => 'KYC verification completed',
            'kyc.failed' => 'KYC verification failed',
            'support.ticket.created' => 'Support ticket created',
            default => 'TexaPay Notification',
        };
    }

    /**
     * Get default message based on notification type
     */
    private function getDefaultMessage(): string
    {
        return match ($this->notification->type) {
            'auth.login.success' => 'You have successfully logged into your TexaPay account.',
            'auth.login.failed' => 'There was a failed login attempt on your account.',
            'transfer.payin.success' => 'Your payment has been received and is being processed.',
            'transfer.payout.success' => 'Your transfer has been completed successfully.',
            'transfer.payin.failed' => 'Your payment could not be processed.',
            'transfer.payout.failed' => 'Your transfer could not be completed.',
            'kyc.completed' => 'Your identity verification has been completed successfully.',
            'kyc.failed' => 'Your identity verification could not be completed.',
            'support.ticket.created' => 'Your support ticket has been created and we will respond soon.',
            default => 'You have a new notification from TexaPay.',
        };
    }

    /**
     * Get click action for the notification
     */
    private function getClickAction(): string
    {
        $payload = $this->notification->payload ?? [];
        
        return $payload['click_action'] ?? match ($this->notification->type) {
            'transfer.payin.success', 'transfer.payout.success', 'transfer.payin.failed', 'transfer.payout.failed' => 'TRANSFER_DETAILS',
            'kyc.completed', 'kyc.failed' => 'KYC_STATUS',
            'support.ticket.created' => 'SUPPORT_TICKETS',
            default => 'NOTIFICATION_DETAILS',
        };
    }
}

