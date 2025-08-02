<?php

require_once 'vendor/autoload.php';

echo "=== Debugging OAuth2 Token Usage ===\n\n";

try {
    // Load Laravel configuration
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    $service = $app->make('App\Services\FarmOSApiService');
    
    // Use reflection to check private token property
    $reflection = new ReflectionClass($service);
    $tokenProperty = $reflection->getProperty('token');
    $tokenProperty->setAccessible(true);
    
    echo "1. Before authentication:\n";
    echo "   Token: " . ($tokenProperty->getValue($service) ?: 'null') . "\n\n";
    
    echo "2. Testing authentication:\n";
    $auth = $service->authenticate();
    echo "   Auth result: " . ($auth ? 'YES' : 'NO') . "\n";
    
    echo "3. After authentication:\n";
    $token = $tokenProperty->getValue($service);
    echo "   Token: " . ($token ? substr($token, 0, 20) . '...' : 'null') . "\n";
    echo "   Token length: " . strlen($token ?: '') . "\n\n";
    
    // Test OAuth2 method directly
    $oauth2Method = $reflection->getMethod('getOAuth2Token');
    $oauth2Method->setAccessible(true);
    
    echo "4. Testing OAuth2 method directly:\n";
    $oauthToken = $oauth2Method->invoke($service);
    echo "   OAuth2 Token: " . ($oauthToken ? substr($oauthToken, 0, 20) . '...' : 'null') . "\n";
    echo "   OAuth2 Token length: " . strlen($oauthToken ?: '') . "\n\n";
    
    // Check if token is being cached
    echo "5. Checking cache:\n";
    $cacheKey = 'farmos_oauth2_token';
    $cachedToken = \Illuminate\Support\Facades\Cache::get($cacheKey);
    echo "   Cached token: " . ($cachedToken ? substr($cachedToken, 0, 20) . '...' : 'null') . "\n\n";
    
    echo "6. Testing land assets with current service state:\n";
    $geometry = $service->getGeometryAssets();
    
    if (isset($geometry['error'])) {
        echo "   ❌ Error: " . $geometry['error'] . "\n";
        if (isset($geometry['auth_method'])) {
            echo "   Auth method used: " . $geometry['auth_method'] . "\n";
        }
    } else {
        echo "   ✅ Success! Found " . count($geometry['features'] ?? []) . " land features\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nDebug completed.\n";
