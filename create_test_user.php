<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Load Laravel environment
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Creating test user...\n";

try {
    // Check if user already exists
    $existingUser = User::where('phone', '237123456789')->first();
    if ($existingUser) {
        echo "✅ Test user already exists: {$existingUser->phone}\n";
        echo "   - Email: {$existingUser->email}\n";
        echo "   - Name: {$existingUser->name}\n";
    } else {
        // Create test user
        $user = User::create([
            'name' => 'Test User',
            'phone' => '237123456789',
            'email' => '237123456789@local',
            'password' => Hash::make('password123'),
            'pin_hash' => Hash::make('1234'),
        ]);
        
        echo "✅ Test user created successfully\n";
        echo "   - Phone: {$user->phone}\n";
        echo "   - Email: {$user->email}\n";
        echo "   - Name: {$user->name}\n";
    }
    
    echo "\nTest user ready for password reset testing!\n";
    
} catch (Exception $e) {
    echo "❌ Failed to create test user: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
