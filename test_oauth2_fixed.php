<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing FarmOS OAuth2 Authentication with farm_manager scope...\n\n";

try {
    // Create API service instance
    $farmOSApi = new \App\Services\FarmOSApiService();
    
    // Test authentication
    echo "🔐 Testing OAuth2 authentication...\n";
    $authResult = $farmOSApi->authenticate();
    
    if ($authResult) {
        echo "✅ Authentication successful!\n";
        
        // Test a simple API call
        echo "🌱 Testing API call (crop types)...\n";
        $cropTypes = $farmOSApi->getAvailableCropTypes();
        echo "✅ Found " . count($cropTypes) . " crop types: " . implode(', ', array_slice($cropTypes, 0, 5)) . "\n";
        
        // Test geometry assets
        echo "🗺️  Testing geometry assets...\n";
        $geometry = $farmOSApi->getGeometryAssets();
        if (isset($geometry['features'])) {
            echo "✅ Found " . count($geometry['features']) . " land assets\n";
        } else {
            echo "⚠️  No land assets or permission issue\n";
            if (isset($geometry['error'])) {
                echo "   Error: " . $geometry['error'] . "\n";
            }
        }
        
        echo "\n🎉 All tests completed successfully!\n";
        
    } else {
        echo "❌ Authentication failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
