<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing FarmOS API Service with new OAuth2 scope...\n\n";

try {
    // Create fresh instance of FarmOS API service
    $farmOSApi = new \App\Services\FarmOSApiService();
    
    echo "🔐 Testing authentication...\n";
    $auth = $farmOSApi->authenticate();
    
    if ($auth) {
        echo "✅ Authentication successful!\n\n";
        
        echo "🌱 Testing crop types fetch...\n";
        $cropTypes = $farmOSApi->getCropTypes();
        echo "✅ Found " . count($cropTypes) . " crop types\n\n";
        
        echo "🗺️  Testing land assets...\n";
        $assets = $farmOSApi->getLandAssets();
        echo "✅ Found " . count($assets) . " land assets\n\n";
        
        echo "🎉 All tests completed successfully!\n";
        
    } else {
        echo "❌ Authentication failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\nDone!\n";
