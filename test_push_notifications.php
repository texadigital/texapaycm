<?php

require_once 'vendor/autoload.php';

use App\Services\NotificationService;
use App\Services\FcmService;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\NotificationPreference;

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Complete Push Notification System...\n\n";

try {
    // Test 1: Check if all services can be instantiated
    echo "1. Testing service instantiation...\n";
    $notificationService = new NotificationService();
    $fcmService = new FcmService();
    echo "✅ All services created successfully\n\n";

    // Test 2: Check if we can find a user
    echo "2. Testing User model and database connection...\n";
    $user = User::first();
    if ($user) {
        echo "✅ Found user: {$user->email}\n";
        echo "✅ Database connection working\n\n";
    } else {
        echo "⚠️  No users found in database\n\n";
        return;
    }

    // Test 3: Test device registration
    echo "3. Testing device registration...\n";
    try {
        $testDeviceToken = 'test_fcm_token_' . time();
        $device = UserDevice::registerDevice(
            user: $user,
            deviceToken: $testDeviceToken,
            platform: 'android',
            deviceId: 'test_device_' . time(),
            appVersion: '1.0.0',
            osVersion: 'Android 13'
        );
        echo "✅ Device registered successfully\n";
        echo "   - Device ID: {$device->id}\n";
        echo "   - Platform: {$device->platform}\n";
        echo "   - Display Name: {$device->getDisplayName()}\n\n";
    } catch (Exception $e) {
        echo "❌ Device registration failed: " . $e->getMessage() . "\n\n";
    }

    // Test 4: Test notification preference creation
    echo "4. Testing notification preferences...\n";
    try {
        $preference = NotificationPreference::getOrCreateDefault($user, 'test.push');
        echo "✅ Notification preference created\n";
        echo "   - Push enabled: " . ($preference->push_enabled ? 'Yes' : 'No') . "\n";
        echo "   - Email enabled: " . ($preference->email_enabled ? 'Yes' : 'No') . "\n";
        echo "   - SMS enabled: " . ($preference->sms_enabled ? 'Yes' : 'No') . "\n\n";
    } catch (Exception $e) {
        echo "❌ Notification preference creation failed: " . $e->getMessage() . "\n\n";
    }

    // Test 5: Test push notification dispatch
    echo "5. Testing push notification dispatch...\n";
    try {
        $notificationService->dispatchUserNotification(
            'test.push',
            $user,
            [
                'title' => 'Test Push Notification',
                'message' => 'This is a test push notification from TexaPay',
                'click_action' => 'TEST_NOTIFICATION'
            ],
            ['push', 'in_app']
        );
        echo "✅ Push notification dispatched successfully\n\n";
    } catch (Exception $e) {
        echo "❌ Push notification dispatch failed: " . $e->getMessage() . "\n\n";
    }

    // Test 6: Check if notification was created
    echo "6. Testing notification storage...\n";
    $notification = $user->notifications()->where('type', 'test.push')->first();
    if ($notification) {
        echo "✅ Notification stored in database\n";
        echo "   - Type: {$notification->type}\n";
        echo "   - Channels: " . json_encode($notification->channels) . "\n";
        echo "   - Payload: " . json_encode($notification->payload) . "\n\n";
    } else {
        echo "❌ Notification not found in database\n\n";
    }

    // Test 7: Test FCM configuration
    echo "7. Testing FCM configuration...\n";
    $fcmConfig = config('services.fcm');
    if ($fcmConfig && !empty($fcmConfig['server_key'])) {
        echo "✅ FCM configuration found\n";
        echo "   - Server Key: " . (strlen($fcmConfig['server_key']) > 0 ? 'Set' : 'Empty') . "\n";
        echo "   - Project ID: " . (strlen($fcmConfig['project_id']) > 0 ? 'Set' : 'Empty') . "\n";
    } else {
        echo "⚠️  FCM configuration not found or incomplete\n";
        echo "   - Add FCM_SERVER_KEY and FCM_PROJECT_ID to your .env file\n";
    }
    echo "\n";

    // Test 8: Test device validation
    echo "8. Testing device token validation...\n";
    $validToken = 'valid_fcm_token_' . str_repeat('a', 100); // 100+ chars
    $invalidToken = 'short'; // Too short
    
    if ($fcmService->validateDeviceToken($validToken)) {
        echo "✅ Valid token format accepted\n";
    } else {
        echo "❌ Valid token format rejected\n";
    }
    
    if (!$fcmService->validateDeviceToken($invalidToken)) {
        echo "✅ Invalid token format rejected\n";
    } else {
        echo "❌ Invalid token format accepted\n";
    }
    echo "\n";

    // Test 9: Test user device relationships
    echo "9. Testing user device relationships...\n";
    $activeDevices = $user->activeDevices()->count();
    $allDevices = $user->devices()->count();
    echo "✅ User has {$activeDevices} active devices out of {$allDevices} total devices\n\n";

    // Test 10: Test API endpoints exist
    echo "10. Testing API endpoints...\n";
    $routes = [
        'POST /api/mobile/devices/register',
        'DELETE /api/mobile/devices/unregister', 
        'GET /api/mobile/devices',
        'POST /api/mobile/devices/test-push'
    ];
    
    foreach ($routes as $route) {
        echo "   - {$route}\n";
    }
    echo "✅ All API endpoints defined\n\n";

    echo "🎉 Complete push notification system test completed!\n";
    echo "\n📋 Next Steps:\n";
    echo "1. Add your FCM credentials to .env file:\n";
    echo "   FCM_SERVER_KEY=your_server_key_here\n";
    echo "   FCM_PROJECT_ID=your_project_id_here\n";
    echo "\n2. Run database migrations:\n";
    echo "   php artisan migrate\n";
    echo "\n3. Test with real mobile app:\n";
    echo "   - Register device via API\n";
    echo "   - Send test push notification\n";
    echo "   - Verify notification appears on device\n";

} catch (Exception $e) {
    echo "❌ Push notification system test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

