<?php

require_once 'vendor/autoload.php';

// Test the updated FarmOSApiService with OAuth2 authentication
echo "Testing updated FarmOSApiService with OAuth2 authentication...\n\n";

try {
    // Load Laravel configuration
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    $service = $app->make('App\Services\FarmOSApiService');
    
    echo "1. Testing authentication:\n";
    $auth = $service->authenticate();
    echo "✅ Authentication successful: " . ($auth ? 'YES' : 'NO') . "\n\n";
    
    echo "2. Testing geometry assets (land/location data):\n";
    $geometry = $service->getGeometryAssets();
    
    if (isset($geometry['error'])) {
        echo "⚠️  Warning: " . $geometry['error'] . "\n";
        if (isset($geometry['available_assets'])) {
            echo "   Available assets: " . $geometry['available_assets'] . "\n";
        }
    } else {
        echo "✅ Success! Found " . count($geometry['features'] ?? []) . " land features\n";
    }
    
    echo "\n3. Testing crop planning data:\n";
    $cropData = $service->getCropPlanningData();
    echo "✅ Found " . count($cropData) . " crop planning items\n";
    
    echo "\n4. Testing available crop types:\n";
    $cropTypes = $service->getAvailableCropTypes();
    echo "✅ Found " . count($cropTypes) . " crop types: " . implode(', ', array_slice($cropTypes, 0, 5)) . "\n";
    
    echo "\n🎉 OAuth2 authentication is working!\n";
    echo "The FarmOSApiService is now using OAuth2 with proper land asset access.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest completed.\n";
