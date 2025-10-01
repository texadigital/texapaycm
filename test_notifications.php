<?php

require_once 'vendor/autoload.php';

use App\Services\NotificationService;
use App\Models\User;

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing Notification System Integration...\n\n";

try {
    // Test 1: Check if NotificationService can be instantiated
    echo "1. Testing NotificationService instantiation...\n";
    $notificationService = new NotificationService();
    echo "✅ NotificationService created successfully\n\n";

    // Test 2: Check if we can find a user
    echo "2. Testing User model and database connection...\n";
    $user = User::first();
    if ($user) {
        echo "✅ Found user: {$user->email}\n";
        echo "✅ Database connection working\n\n";
    } else {
        echo "⚠️  No users found in database\n\n";
    }

    // Test 3: Test notification dispatch (if user exists)
    if ($user) {
        echo "3. Testing notification dispatch...\n";
        try {
            $notificationService->dispatchUserNotification(
                'test.integration',
                $user,
                ['message' => 'Test notification from integration test'],
                ['email', 'in_app']
            );
            echo "✅ Notification dispatched successfully\n\n";
        } catch (Exception $e) {
            echo "❌ Notification dispatch failed: " . $e->getMessage() . "\n\n";
        }
    }

    // Test 4: Check if notification was created in database
    if ($user) {
        echo "4. Testing notification storage...\n";
        $notification = $user->notifications()->where('type', 'test.integration')->first();
        if ($notification) {
            echo "✅ Notification stored in database\n";
            echo "   - Type: {$notification->type}\n";
            echo "   - Channels: " . json_encode($notification->channels) . "\n";
            echo "   - Payload: " . json_encode($notification->payload) . "\n\n";
        } else {
            echo "❌ Notification not found in database\n\n";
        }
    }

    // Test 5: Check Twilio configuration
    echo "5. Testing Twilio configuration...\n";
    $twilioConfig = config('services.twilio');
    if ($twilioConfig && !empty($twilioConfig['sid'])) {
        echo "✅ Twilio configuration found\n";
        echo "   - SID: " . (strlen($twilioConfig['sid']) > 0 ? 'Set' : 'Empty') . "\n";
        echo "   - Auth Token: " . (strlen($twilioConfig['auth_token']) > 0 ? 'Set' : 'Empty') . "\n";
        echo "   - Phone Number: " . (strlen($twilioConfig['phone_number']) > 0 ? 'Set' : 'Empty') . "\n\n";
    } else {
        echo "⚠️  Twilio configuration not found or incomplete\n\n";
    }

    echo "🎉 Integration test completed!\n";

} catch (Exception $e) {
    echo "❌ Integration test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
