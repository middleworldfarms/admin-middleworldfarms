<?php

echo "=== Direct OAuth2 vs Service Test ===\n\n";

// Test 1: Direct OAuth2 call (we know this works)
$clientId = 'NyIv5ejXa5xYRLKv0BXjUi-IHn3H2qbQQ3m-h2qp_xY';
$clientSecret = 'Qw7!pZ2rT9@xL6vB1#eF4sG8uJ0mN5cD';

echo "1. Direct OAuth2 token request:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://farmos.middleworldfarms.org/oauth/token');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'client_credentials',
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'scope' => 'farm_manager'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $response) {
    $data = json_decode($response, true);
    if (isset($data['access_token'])) {
        $token = $data['access_token'];
        echo "   ✅ Token acquired: " . substr($token, 0, 20) . "...\n";
        
        // Test 2: Use OAuth2 token directly for land assets
        echo "\n2. Direct land assets request with OAuth2 token:\n";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://farmos.middleworldfarms.org/api/asset/land');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/vnd.api+json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['data']) && count($data['data']) > 0) {
                echo "   ✅ SUCCESS: Found " . count($data['data']) . " land assets\n";
                echo "   First asset: " . ($data['data'][0]['attributes']['name'] ?? 'Unnamed') . "\n";
            } elseif (isset($data['meta']['omitted'])) {
                echo "   ❌ PERMISSION DENIED: " . count($data['meta']['omitted']) . " assets hidden\n";
            } else {
                echo "   ⚠️  No land assets found\n";
            }
        }
    } else {
        echo "   ❌ No access token in response\n";
    }
} else {
    echo "   ❌ OAuth2 token request failed\n";
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "If direct OAuth2 works but service doesn't, the issue is in the service implementation.\n";
