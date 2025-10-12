<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SmileGetWebToken extends Command
{
    protected $signature = 'smile:web-token {user_id=1}';
    protected $description = 'Generate a Smile Identity Web token for testing';

    public function handle(): int
    {
        $partnerId = (string) env('SMILE_ID_PARTNER_ID', '');
        $apiKey = (string) env('SMILE_ID_API_KEY', '');
        $sidServer = (int) env('SMILE_ID_SID_SERVER', 0);
        $callback = route('kyc.smileid.callback');

        if (!class_exists('SmileIdentity\\WebApi')) {
            $this->error('SmileIdentityCore\\WebApi not installed.');
            return self::FAILURE;
        }

        $userId = (int) $this->argument('user_id');
        $jobId = 'job_' . $userId . '_' . now()->timestamp;
        $userRef = 'user_' . $userId;

        $webApi = new \SmileIdentity\WebApi($partnerId, $callback, $apiKey, $sidServer);
        $requestParams = [
            'user_id' => $userRef,
            'job_id' => $jobId,
            'product' => 'doc_verification',
            'callback_url' => $callback,
        ];

        $token = $webApi->get_web_token($requestParams);
        $this->info(json_encode(['token' => $token['token'] ?? $token, 'callback' => $callback], JSON_UNESCAPED_SLASHES));
        return self::SUCCESS;
    }
}
