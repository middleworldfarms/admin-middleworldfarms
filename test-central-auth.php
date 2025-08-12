<?php

// Test the FarmOS Auth System - Laravel Integration Test
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\FarmOSAuthService;

echo "🔍 Testing Central FarmOS Auth System Integration...\n\n";

try {
    // Test 1: Service instantiation
    echo "1. Creating FarmOSAuthService instance...\n";
    $authService = FarmOSAuthService::getInstance();
    echo "   ✅ Service created successfully\n\n";
    
    // Test 2: Check configuration
    echo "2. Checking configuration...\n";
    $config = [
        'FARMOS_URL' => env('FARMOS_URL'),
        'FARMOS_USERNAME' => env('FARMOS_USERNAME') ? '***set***' : 'not set',
        'FARMOS_CLIENT_ID' => env('FARMOS_CLIENT_ID') ? '***set***' : 'not set',
    ];
    
    foreach ($config as $key => $value) {
        echo "   $key: $value\n";
    }
    echo "\n";
    
    // Test 3: Authentication
    echo "3. Testing authentication...\n";
    $isAuthenticated = $authService->authenticate();
    
    if ($isAuthenticated) {
        echo "   ✅ Authentication successful\n";
        
        // Test 4: Get auth headers
        echo "4. Getting authentication headers...\n";
        $headers = $authService->getAuthHeaders();
        $headerKeys = array_keys($headers);
        echo "   ✅ Headers available: " . implode(', ', $headerKeys) . "\n";
        
        // Test 5: Get auth status
        echo "5. Checking detailed auth status...\n";
        $status = $authService->getAuthStatus();
        echo "   ✅ Auth method: " . ($status['method'] ?? 'unknown') . "\n";
        echo "   ✅ Token cached: " . ($status['token_cached'] ? 'yes' : 'no') . "\n";
        echo "   ✅ Config valid: " . ($status['config_valid'] ? 'yes' : 'no') . "\n";
        
        // Test 6: Connection test
        echo "6. Testing farmOS connection...\n";
        $connected = $authService->testConnection();
        echo "   " . ($connected ? "✅" : "❌") . " Connection test: " . ($connected ? "success" : "failed") . "\n";
        
    } else {
        echo "   ❌ Authentication failed\n";
        $status = $authService->getAuthStatus();
        echo "   Error details: " . json_encode($status, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n🏁 Test completed.\n";
