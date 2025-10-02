<?php

namespace App\Jobs;

use App\Models\UserNotification;
use App\Services\PhoneNumberService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class SendSmsNotification implements ShouldQueue
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
            // Early debug trace
            Log::info('SMS job start', [
                'notification_id' => $this->notification->id,
                'user_id' => $user?->id,
                'type' => $this->notification->type,
                'payload_keys' => array_keys((array) ($this->notification->payload ?? [])),
                'tw_sid_set' => (bool) config('services.twilio.sid'),
                'tw_from_set' => (bool) config('services.twilio.phone_number'),
            ]);
            
            // Check if user has SMS notifications enabled
            // Always allow password reset notifications regardless of global SMS toggle
            $isPasswordReset = str_starts_with($this->notification->type, 'auth.password.reset.');
            if (!$isPasswordReset && !$user->sms_notifications) {
                Log::info('SMS notifications disabled for user', [
                    'user_id' => $user->id,
                    'notification_id' => $this->notification->id
                ]);
                return;
            }

            // Check if user has a valid phone number
            if (!$user->phone) {
                Log::warning('No phone number for user', [
                    'user_id' => $user->id
                ]);
                return;
            }

            // Check if Twilio is configured
            if (!config('services.twilio.sid') || !config('services.twilio.auth_token') || !config('services.twilio.phone_number')) {
                Log::warning('Twilio not configured, skipping SMS notification', [
                    'user_id' => $user->id,
                    'notification_id' => $this->notification->id
                ]);
                return;
            }

            // Format phone number for international format (E.164) via PhoneNumberService
            $phoneNumber = PhoneNumberService::toE164((string) $user->phone);
            
            // Create SMS content from notification payload
            $smsContent = $this->createSmsContent();
            
            // Send SMS via Twilio
            Log::info('SMS dispatching', [
                'user_id' => $user->id,
                'to' => $phoneNumber,
                'type' => $this->notification->type,
                'len' => strlen($smsContent),
            ]);
            $this->sendSms($phoneNumber, $smsContent);

            Log::info('SMS notification sent successfully', [
                'user_id' => $user->id,
                'notification_id' => $this->notification->id,
                'phone' => $phoneNumber
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send SMS notification', [
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
        Log::error('SMS notification job failed permanently', [
            'notification_id' => $this->notification->id,
            'user_id' => $this->notification->user_id,
            'error' => $exception->getMessage()
        ]);
    }

    /**
     * Send SMS via Twilio
     */
    private function sendSms(string $phone, string $message): void
    {
        $client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.auth_token')
        );

        $client->messages->create($phone, [
            'from' => config('services.twilio.phone_number'),
            'body' => $message,
        ]);
    }

    // Formatting handled by PhoneNumberService::toE164()

    /**
     * Create SMS content from notification payload
     */
    private function createSmsContent(): string
    {
        $payload = $this->notification->payload ?? [];
        
        // Create a simple SMS message based on notification type
        $type = $this->notification->type;
        $user = $this->notification->user;
        
        switch ($type) {
            case 'auth.password.reset.requested':
                $code = (string)($payload['reset_code'] ?? '');
                $mins = 15;
                return $code
                    ? "TexaPay: Your password reset code is ${code}. It expires in ${mins} minutes."
                    : "TexaPay: Password reset requested. Please use the code sent to you to reset your password.";

            case 'auth.password.reset.success':
                return "TexaPay: Your password has been reset successfully. You can now sign in.";
            case 'auth.login.success':
                return "Welcome back to TexaPay! You've successfully logged in.";
                
            case 'auth.login.failed':
                return "TexaPay: Failed login attempt detected. If this wasn't you, please secure your account.";
                
            case 'transfer.payin.success':
                $amount = $payload['transfer']['amount_xaf'] ?? 'N/A';
                return "TexaPay: Your payment of {$amount} XAF has been received successfully.";
                
            case 'transfer.payout.success':
                $amount = $payload['transfer']['receive_ngn_minor'] ?? 'N/A';
                return "TexaPay: Your transfer of {$amount} NGN has been completed successfully.";
                
            case 'transfer.payin.failed':
                return "TexaPay: Your payment failed. Please try again or contact support.";
                
            case 'transfer.payout.failed':
                return "TexaPay: Your transfer failed. A refund has been initiated.";
                
            case 'kyc.completed':
                return "TexaPay: Your identity verification has been completed successfully.";
                
            case 'kyc.failed':
                return "TexaPay: Identity verification failed. Please try again.";
                
            case 'support.ticket.created':
                return "TexaPay: Your support ticket has been received. We'll get back to you soon.";
                
            default:
                return "TexaPay: You have a new notification. Please check your account.";
        }
    }
}
