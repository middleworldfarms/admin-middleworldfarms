<?php
// Test OAuth2 authentication directly
$farmosUrl = 'https://farmos.middleworldfarms.org';
$clientId = 'NyIv5ejXa5xYRLKv0BXjUi-IHn3H2qbQQ3m-h2qp_xY';
$clientSecret = 'Qw7!pZ2rT9@xL6vB1#eF4sG8uJ0mN5cD';

echo "Testing OAuth2 Client Credentials Flow...\n\n";
echo "Client ID: $clientId\n";
echo "Client Secret: " . str_repeat('*', strlen($clientSecret)) . "\n\n";

// Test OAuth2 token request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $farmosUrl . '/oauth/token');
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
$error = curl_error($ch);
curl_close($ch);

echo "OAuth2 Token Request:\n";
echo "HTTP Code: $httpCode\n";

if ($error) {
    echo "cURL Error: $error\n";
    exit(1);
}

if ($response) {
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        if (isset($data['access_token'])) {
            echo "✅ SUCCESS: OAuth2 token acquired!\n";
            echo "Token: " . substr($data['access_token'], 0, 20) . "...\n";
            echo "Token Type: " . ($data['token_type'] ?? 'bearer') . "\n";
            echo "Expires In: " . ($data['expires_in'] ?? 'unknown') . " seconds\n\n";
            
            $token = $data['access_token'];
            
            // Test land assets with OAuth2 token
            echo "Testing land assets with OAuth2 token:\n";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $farmosUrl . '/api/asset/land');
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
                    echo "✅ SUCCESS: Found " . count($data['data']) . " land assets with OAuth2!\n";
                    echo "First asset: " . ($data['data'][0]['attributes']['name'] ?? 'Unnamed') . "\n";
                } elseif (isset($data['meta']['omitted'])) {
                    echo "❌ PERMISSION DENIED: " . count($data['meta']['omitted']) . " assets hidden\n";
                    echo "OAuth2 client needs proper scopes/permissions\n";
                } else {
                    echo "⚠️  No land assets found\n";
                }
            }
            
        } else {
            echo "❌ FAILED: No access token in response\n";
            echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "❌ FAILED: Invalid JSON response\n";
        echo "Raw Response: $response\n";
    }
} else {
    echo "❌ FAILED: No response from server\n";
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "If OAuth2 fails, check the Client configuration in farmOS Simple OAuth settings\n";
?>
