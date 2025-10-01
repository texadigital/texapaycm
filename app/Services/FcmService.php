<?php

namespace App\Services;

use App\Models\UserDevice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class FcmService
{
    private string $serverKey;
    private string $projectId;
    private string $fcmUrl;

    public function __construct()
    {
        $this->serverKey = config('services.fcm.server_key');
        $this->projectId = config('services.fcm.project_id');
        $this->fcmUrl = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
    }

    /**
     * Send push notification to a single device
     */
    public function sendToDevice(UserDevice $device, array $notification, array $data = []): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('FCM not configured, skipping push notification');
            return false;
        }

        $payload = $this->buildPayload($device, $notification, $data);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                Log::info('FCM notification sent successfully', [
                    'device_id' => $device->id,
                    'user_id' => $device->user_id,
                    'platform' => $device->platform,
                ]);
                return true;
            } else {
                Log::error('FCM notification failed', [
                    'device_id' => $device->id,
                    'user_id' => $device->user_id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('FCM notification exception', [
                'device_id' => $device->id,
                'user_id' => $device->user_id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send push notification to multiple devices
     */
    public function sendToDevices(array $devices, array $notification, array $data = []): array
    {
        $results = [];
        
        foreach ($devices as $device) {
            $results[$device->id] = $this->sendToDevice($device, $notification, $data);
        }

        return $results;
    }

    /**
     * Send push notification to all active devices of a user
     */
    public function sendToUser(int $userId, array $notification, array $data = []): array
    {
        $devices = UserDevice::getActiveDevicesForUser(User::find($userId));
        return $this->sendToDevices($devices->toArray(), $notification, $data);
    }

    /**
     * Build FCM payload
     */
    private function buildPayload(UserDevice $device, array $notification, array $data): array
    {
        $message = [
            'message' => [
                'token' => $device->device_token,
                'notification' => [
                    'title' => $notification['title'] ?? 'TexaPay',
                    'body' => $notification['body'] ?? $notification['message'] ?? '',
                ],
                'data' => array_merge($data, [
                    'notification_id' => (string) ($notification['id'] ?? ''),
                    'type' => $notification['type'] ?? '',
                    'click_action' => $notification['click_action'] ?? 'FLUTTER_NOTIFICATION_CLICK',
                ]),
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'icon' => 'ic_notification',
                        'color' => '#1E40AF',
                        'sound' => 'default',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'alert' => [
                                'title' => $notification['title'] ?? 'TexaPay',
                                'body' => $notification['body'] ?? $notification['message'] ?? '',
                            ],
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                ],
            ],
        ];

        return $message;
    }

    /**
     * Get FCM access token using service account
     */
    private function getAccessToken(): string
    {
        // For now, we'll use the server key directly
        // In production, you should use OAuth 2.0 with service account
        return $this->serverKey;
    }

    /**
     * Check if FCM is properly configured
     */
    private function isConfigured(): bool
    {
        return !empty($this->serverKey) && !empty($this->projectId);
    }

    /**
     * Validate device token format
     */
    public function validateDeviceToken(string $token): bool
    {
        return UserDevice::isValidDeviceToken($token);
    }

    /**
     * Test FCM connection
     */
    public function testConnection(): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Content-Type' => 'application/json',
            ])->get("https://fcm.googleapis.com/v1/projects/{$this->projectId}");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('FCM connection test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
