<?php

require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use App\Services\OpenExchangeRates;

echo "=== Exchange Rates Debug Test ===\n\n";

// Check environment variables
echo "Environment Configuration:\n";
echo "- OXR_BASE_URL: " . env('OXR_BASE_URL', 'not set') . "\n";
echo "- OXR_APP_ID: " . (env('OXR_APP_ID') ? 'set (' . substr(env('OXR_APP_ID'), 0, 8) . '...)' : 'not set') . "\n";
echo "- FALLBACK_XAF_TO_NGN: " . env('FALLBACK_XAF_TO_NGN', 'not set') . "\n";
echo "- OXR_CACHE_TTL_MINUTES: " . env('OXR_CACHE_TTL_MINUTES', 'not set') . "\n\n";

try {
    // Initialize the service
    $oxr = new OpenExchangeRates();
    
    echo "Testing OpenExchangeRates service...\n";
    
    // Fetch rates
    $rates = $oxr->fetchUsdRates();
    
    echo "Rates Response:\n";
    echo "- USD to XAF: " . ($rates['usd_to_xaf'] ?? 'null') . "\n";
    echo "- USD to NGN: " . ($rates['usd_to_ngn'] ?? 'null') . "\n";
    echo "- Fetched at: " . ($rates['fetched_at'] ?? 'null') . "\n";
    
    if (isset($rates['raw'])) {
        echo "- Raw response note: " . ($rates['raw']['note'] ?? 'none') . "\n";
        if (isset($rates['raw']['error'])) {
            echo "- Error: " . $rates['raw']['error'] . "\n";
        }
        if (isset($rates['raw']['exception'])) {
            echo "- Exception: " . $rates['raw']['exception'] . "\n";
        }
    }
    
    // Calculate cross rate
    if ($rates['usd_to_xaf'] && $rates['usd_to_ngn']) {
        $crossRate = $rates['usd_to_ngn'] / $rates['usd_to_xaf'];
        echo "- Cross rate (XAF to NGN): " . number_format($crossRate, 4) . "\n";
        
        // Test quote calculation
        $amountXaf = 10000; // 10,000 XAF
        $amountNgn = $amountXaf * $crossRate;
        echo "\nSample Quote:\n";
        echo "- Send: " . number_format($amountXaf) . " XAF\n";
        echo "- Receive: " . number_format($amountNgn, 2) . " NGN\n";
        
        echo "\n✅ Exchange rates are working correctly!\n";
    } else {
        echo "\n❌ Exchange rates are not available\n";
        echo "Raw response:\n";
        print_r($rates);
    }
    
} catch (Exception $e) {
    echo "\n❌ Exception occurred:\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== Test Complete ===\n";
