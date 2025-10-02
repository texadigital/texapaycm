<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class DeviceController extends Controller
{
    public function __construct(
        private FcmService $fcmService
    ) {}

    /**
     * Register a device for push notifications
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_token' => 'required|string|min:64|max:255',
            'platform' => 'required|string|in:android,ios,web',
            'device_id' => 'nullable|string|max:100',
            'app_version' => 'nullable|string|max:20',
            'os_version' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            
            // Validate device token format
            if (!$this->fcmService->validateDeviceToken($request->device_token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid device token format',
                ], 400);
            }

            // Register or update the device
            $device = UserDevice::registerDevice(
                user: $user,
                deviceToken: $request->device_token,
                platform: $request->platform,
                deviceId: $request->device_id,
                appVersion: $request->app_version,
                osVersion: $request->os_version
            );

            Log::info('Device registered successfully', [
                'user_id' => $user->id,
                'device_id' => $device->id,
                'platform' => $device->platform,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Device registered successfully',
                'device' => [
                    'id' => $device->id,
                    'platform' => $device->platform,
                    'app_version' => $device->app_version,
                    'os_version' => $device->os_version,
                    'registered_at' => $device->created_at->toISOString(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Device registration failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Device registration failed',
            ], 500);
        }
    }

    /**
     * Unregister a device
     */
    public function unregister(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            
            $device = UserDevice::where('user_id', $user->id)
                ->where('device_token', $request->device_token)
                ->first();

            if (!$device) {
                return response()->json([
                    'success' => false,
                    'message' => 'Device not found',
                ], 404);
            }

            $device->deactivate();

            Log::info('Device unregistered successfully', [
                'user_id' => $user->id,
                'device_id' => $device->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Device unregistered successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Device unregistration failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Device unregistration failed',
            ], 500);
        }
    }

    /**
     * Get user's registered devices
     */
    public function devices(): JsonResponse
    {
        try {
            $user = Auth::user();
            $devices = $user->activeDevices()->get();

            return response()->json([
                'success' => true,
                'devices' => $devices->map(function ($device) {
                    return [
                        'id' => $device->id,
                        'platform' => $device->platform,
                        'platform_icon' => $device->getPlatformIcon(),
                        'display_name' => $device->getDisplayName(),
                        'app_version' => $device->app_version,
                        'os_version' => $device->os_version,
                        'last_used_at' => $device->last_used_at?->toISOString(),
                        'registered_at' => $device->created_at->toISOString(),
                    ];
                }),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch devices', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch devices',
            ], 500);
        }
    }

    /**
     * Test push notification (for development)
     */
    public function testPush(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_token' => 'required|string',
            'title' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();
            
            $device = UserDevice::where('user_id', $user->id)
                ->where('device_token', $request->device_token)
                ->where('is_active', true)
                ->first();

            if (!$device) {
                return response()->json([
                    'success' => false,
                    'message' => 'Device not found or inactive',
                ], 404);
            }

            $notification = [
                'title' => $request->title ?? 'Test Notification',
                'body' => $request->body ?? 'This is a test push notification from TexaPay',
                'type' => 'test',
            ];

            $success = $this->fcmService->sendToDevice($device, $notification, [
                'test' => 'true',
                'timestamp' => now()->toISOString(),
            ]);

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Test notification sent' : 'Failed to send test notification',
            ]);

        } catch (\Exception $e) {
            Log::error('Test push notification failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Test notification failed',
            ], 500);
        }
    }
}
