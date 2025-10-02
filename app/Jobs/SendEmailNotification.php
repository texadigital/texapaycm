<?php

namespace App\Jobs;

use App\Models\UserNotification;
use App\Mail\UserNotificationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendEmailNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public UserNotification $notification)
    {
    }

    public function handle(): void
    {
        try {
            $user = $this->notification->user;
            
            // Check if user has email notifications enabled
            if (!$user->email_notifications) {
                Log::info('Email notifications disabled for user', [
                    'user_id' => $user->id,
                    'notification_id' => $this->notification->id
                ]);
                return;
            }

            // Prefer notification_email, fallback to email
            $toEmail = $user->notification_email ?: $user->email;

            // Check if user has a valid destination email address
            if (!$toEmail || $toEmail === $user->phone . '@local') {
                Log::warning('No valid email address for user', [
                    'user_id' => $user->id,
                    'email' => $toEmail
                ]);
                return;
            }

            // Send the email
            Mail::to($toEmail)->send(new UserNotificationMail($this->notification));

            Log::info('Email notification sent successfully', [
                'user_id' => $user->id,
                'notification_id' => $this->notification->id,
                'email' => $toEmail
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send email notification', [
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
        Log::error('Email notification job failed permanently', [
            'notification_id' => $this->notification->id,
            'user_id' => $this->notification->user_id,
            'error' => $exception->getMessage()
        ]);
    }
}
