<?php

// Simple test for OAuth2 land assets using curl

echo "=== Direct farmOS OAuth2 Test ===\n";

// Load .env file
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        $_ENV[$name] = $value;
    }
}

// Get environment values
$farmosUrl = $_ENV['FARMOS_URL'] ?? 'https://farmos.middleworldfarms.org';
$clientId = $_ENV['FARMOS_OAUTH_CLIENT_ID'] ?? '';
$clientSecret = $_ENV['FARMOS_OAUTH_CLIENT_SECRET'] ?? '';

echo "FarmOS URL: $farmosUrl\n";
echo "Client ID: $clientId\n";
echo "Client Secret: " . ($clientSecret ? 'SET' : 'NOT SET') . "\n\n";

if (empty($clientId) || empty($clientSecret)) {
    echo "ERROR: OAuth2 credentials not set in environment\n";
    echo "Please check .env file for FARMOS_OAUTH_CLIENT_ID and FARMOS_OAUTH_CLIENT_SECRET\n";
    exit(1);
}

// Step 1: Get OAuth2 Token
echo "=== Getting OAuth2 Token ===\n";

$tokenData = [
    'grant_type' => 'client_credentials',
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'scope' => 'farm_manager'
];

$tokenOptions = [
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($tokenData),
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ]
];

$tokenContext = stream_context_create($tokenOptions);
$tokenResult = file_get_contents($farmosUrl . '/oauth/token', false, $tokenContext);

if ($tokenResult === false) {
    echo "✗ Failed to get OAuth2 token\n";
    exit(1);
}

$tokenResponse = json_decode($tokenResult, true);

if (!isset($tokenResponse['access_token'])) {
    echo "✗ No access token in response\n";
    echo "Response: " . $tokenResult . "\n";
    exit(1);
}

$accessToken = $tokenResponse['access_token'];
echo "✓ OAuth2 token obtained successfully\n";
echo "Token expires in: " . ($tokenResponse['expires_in'] ?? 'unknown') . " seconds\n\n";

// Step 2: Test Land Assets API
echo "=== Testing Land Assets API ===\n";

$apiOptions = [
    'http' => [
        'header' => [
            "Authorization: Bearer $accessToken",
            "Accept: application/vnd.api+json",
            "Content-Type: application/vnd.api+json"
        ],
        'method' => 'GET',
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ]
];

$apiContext = stream_context_create($apiOptions);
$apiResult = file_get_contents($farmosUrl . '/api/asset/land?filter[status]=active', false, $apiContext);

if ($apiResult === false) {
    echo "✗ Failed to fetch land assets\n";
    
    // Get more info about the error
    $error = error_get_last();
    if ($error) {
        echo "Error: " . $error['message'] . "\n";
    }
    
    // Check HTTP response headers
    if (isset($http_response_header)) {
        echo "Response headers:\n";
        foreach ($http_response_header as $header) {
            echo "  $header\n";
        }
    }
    exit(1);
}

$landAssets = json_decode($apiResult, true);

if (!$landAssets) {
    echo "✗ Invalid JSON response\n";
    echo "Raw response: " . substr($apiResult, 0, 500) . "...\n";
    exit(1);
}

echo "✓ Land assets API call successful\n";

if (isset($landAssets['data'])) {
    echo "Land assets count: " . count($landAssets['data']) . "\n";
    
    if (!empty($landAssets['data'])) {
        $firstAsset = $landAssets['data'][0];
        echo "Sample asset: " . ($firstAsset['attributes']['name'] ?? 'Unknown') . "\n";
        echo "Asset ID: " . ($firstAsset['id'] ?? 'Unknown') . "\n";
        echo "Has geometry: " . (isset($firstAsset['attributes']['geometry']) ? 'Yes' : 'No') . "\n";
    }
} else {
    echo "⚠ No 'data' key in response\n";
    echo "Response keys: " . implode(', ', array_keys($landAssets)) . "\n";
}

if (isset($landAssets['meta']['omitted']) && !empty($landAssets['meta']['omitted'])) {
    echo "⚠ Some assets were omitted due to permissions\n";
    echo "Omitted asset count: " . count($landAssets['meta']['omitted']) . "\n";
    echo "Omitted asset IDs: " . implode(', ', array_keys($landAssets['meta']['omitted'])) . "\n";
} else {
    echo "✓ No permission issues detected\n";
}

echo "\n=== Test Complete ===\n";
echo "OAuth2 authentication is working properly for land assets!\n";
