<?php

// Quick test of the new central FarmOS auth system
require_once __DIR__ . '/vendor/autoload.php';

use App\Services\FarmOSAuthService;

try {
    echo "🔍 Testing Central FarmOS Auth System...\n\n";
    
    // Test 1: Check if service can be instantiated
    echo "1. Creating FarmOSAuthService instance...\n";
    $authService = FarmOSAuthService::getInstance();
    echo "   ✅ Service created successfully\n\n";
    
    // Test 2: Check authentication
    echo "2. Testing authentication...\n";
    $isAuthenticated = $authService->authenticate();
    
    if ($isAuthenticated) {
        echo "   ✅ Authentication successful\n";
        
        // Test 3: Get auth headers
        echo "3. Getting authentication headers...\n";
        $headers = $authService->getAuthHeaders();
        echo "   ✅ Headers retrieved: " . json_encode(array_keys($headers)) . "\n";
        
        // Test 4: Check auth status
        echo "4. Checking authentication status...\n";
        $status = $authService->getAuthStatus();
        echo "   ✅ Status: " . json_encode($status) . "\n";
        
    } else {
        echo "   ❌ Authentication failed\n";
        $status = $authService->getAuthStatus();
        echo "   Error details: " . json_encode($status) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error testing auth system: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n🏁 Test completed.\n";
