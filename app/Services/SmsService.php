<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $to, string $message): void
    {
        $to = trim($to);
        // Prefer Twilio if configured
        $sid = config('services.twilio.sid') ?? env('TWILIO_SID');
        $token = config('services.twilio.token') ?? env('TWILIO_AUTH_TOKEN');
        $from = config('services.twilio.from') ?? env('TWILIO_PHONE_NUMBER');
        if ($sid && $token && class_exists(\Twilio\Rest\Client::class)) {
            try {
                $client = new \Twilio\Rest\Client($sid, $token);
                $client->messages->create($to, ['from' => $from, 'body' => $message]);
                return;
            } catch (\Throwable $e) {
                Log::warning('Twilio SMS send failed: ' . $e->getMessage(), ['to' => $to]);
            }
        }
        // Fallback: log only
        Log::info('[SMS:FALLBACK] ' . $to . ' :: ' . $message);
    }
}
