<?php

require_once '/opt/sites/admin.middleworldfarms.org/vendor/autoload.php';

$app = require_once '/opt/sites/admin.middleworldfarms.org/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Services\FarmOSApiService;

echo "=== Testing farmOS OAuth2 Authentication ===\n";

try {
    $farmOSApi = new FarmOSApiService();
    
    echo "1. Testing OAuth2 token acquisition...\n";
    
    // Use reflection to access the private getAccessToken method
    $reflection = new ReflectionClass($farmOSApi);
    $getTokenMethod = $reflection->getMethod('getAccessToken');
    $getTokenMethod->setAccessible(true);
    
    $token = $getTokenMethod->invoke($farmOSApi);
    
    if ($token) {
        echo "✓ OAuth2 token acquired successfully\n";
        echo "Token type: " . gettype($token) . "\n";
        
        // Test a simple API call
        echo "\n2. Testing API call with token...\n";
        $assets = $farmOSApi->getGeometryAssets();
        
        if (isset($assets['features'])) {
            echo "✓ API call successful - found " . count($assets['features']) . " assets\n";
        } else {
            echo "✗ API call returned unexpected format\n";
            echo "Response: " . json_encode($assets) . "\n";
        }
        
    } else {
        echo "✗ Failed to acquire OAuth2 token\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
