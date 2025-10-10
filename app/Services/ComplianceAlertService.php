<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\AdminSetting;
use App\Mail\ComplianceAlertMail;

class ComplianceAlertService
{
    public function send(string $subject, array $data = []): void
    {
        try {
            $to = (string) AdminSetting::getValue('compliance.alert_email', '')
                ?: (string) AdminSetting::getValue('support_email', '')
                ?: (string) env('MAIL_FROM_ADDRESS', 'no-reply@localhost');

            Mail::to($to)->send(new ComplianceAlertMail($subject, $data));
        } catch (\Throwable $e) {
            Log::error('Compliance alert email failed', ['error' => $e->getMessage(), 'subject' => $subject]);
        }
    }
}
