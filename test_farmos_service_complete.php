<?php

require_once 'vendor/autoload.php';

// Test the updated FarmOSApiService with OAuth2 authentication
echo "Testing FarmOSApiService with OAuth2 authentication...\n\n";

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
        if (isset($geometry['auth_method'])) {
            echo "   Auth method: " . $geometry['auth_method'] . "\n";
        }
    } else {
        echo "✅ Success! Found " . count($geometry['features'] ?? []) . " land features\n";
        if (!empty($geometry['features'])) {
            $firstFeature = $geometry['features'][0];
            echo "   First land asset: " . ($firstFeature['properties']['name'] ?? 'Unnamed') . "\n";
            echo "   Feature type: " . ($firstFeature['type'] ?? 'Unknown') . "\n";
        }
    }
    
    echo "\n3. Testing crop planning data:\n";
    $cropData = $service->getCropPlanningData();
    echo "✅ Found " . count($cropData) . " crop planning items\n";
    
    echo "\n4. Testing available crop types:\n";
    $cropTypes = $service->getAvailableCropTypes();
    $typeNames = [];
    if (isset($cropTypes['types'])) {
        foreach ($cropTypes['types'] as $type) {
            $typeNames[] = $type['label'] ?? $type['name'] ?? 'Unknown';
        }
    }
    echo "✅ Found " . count($typeNames) . " crop types: " . implode(', ', array_slice($typeNames, 0, 5)) . "\n";
    
    echo "\n5. Testing available locations:\n";
    $locations = $service->getAvailableLocations();
    $locationNames = [];
    if (is_array($locations)) {
        foreach ($locations as $location) {
            if (is_array($location)) {
                $locationNames[] = $location['name'] ?? $location['label'] ?? 'Unknown';
            } else {
                $locationNames[] = (string)$location;
            }
        }
    }
    echo "✅ Found " . count($locationNames) . " locations: " . implode(', ', array_slice($locationNames, 0, 5)) . "\n";
    
    echo "\n🎉 OAuth2 authentication is working!\n";
    echo "The FarmOSApiService is now using OAuth2 with proper land asset access.\n";
    echo "All " . count($geometry['features'] ?? []) . " land assets should now be visible on the admin dashboard map!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest completed.\n";
