<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Http\Client\Factory as HttpClient;

$http = new HttpClient();

$apiKey = 'Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h';
$baseUrl = 'https://middleworldfarms.org/wp-json/mwf/v1/';

echo "Testing MWF API User Switching...\n\n";

// Test 1: User search
echo "1. Testing user search:\n";
try {
    $response = $http->withHeaders([
        'X-WC-API-Key' => $apiKey,
        'Content-Type' => 'application/json'
    ])->get($baseUrl . 'users/search', ['search' => 'amanda']);
    
    echo "Status: " . $response->status() . "\n";
    echo "Response: " . $response->body() . "\n\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// Test 2: User switching for user ID 123 (amanda)
echo "2. Testing user switching for ID 123:\n";
try {
    $response = $http->withHeaders([
        'X-WC-API-Key' => $apiKey,
        'Content-Type' => 'application/json'
    ])->post($baseUrl . 'users/switch', [
        'user_id' => 123
    ]);
    
    echo "Status: " . $response->status() . "\n";
    echo "Response: " . $response->body() . "\n\n";
    
    $data = $response->json();
    if (isset($data['switch_url'])) {
        echo "Switch URL generated: " . $data['switch_url'] . "\n\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Try another user ID
echo "3. Testing user switching for ID 456:\n";
try {
    $response = $http->withHeaders([
        'X-WC-API-Key' => $apiKey,
        'Content-Type' => 'application/json'
    ])->post($baseUrl . 'users/switch', [
        'user_id' => 456
    ]);
    
    echo "Status: " . $response->status() . "\n";
    echo "Response: " . $response->body() . "\n\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

echo "Test completed!\n";
