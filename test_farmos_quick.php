<?php
require 'vendor/autoload.php';

echo "=== Quick FarmOS Test ===\n";

$url = 'https://farmos.middleworldfarms.org';
$client_id = 'NyIv5ejXa5xYRLKv0BXjUi-IHn3H2qbQQ3m-h2qp_xY';
$client_secret = 'Qw7!pZ2rT9@xL6vB1#eF4sG8uJ0mN5cD';
$username = 'martin@middleworldfarms.org';
$password = 'Mackie1974';

echo "Testing: $url\n";
echo "Username: $username\n\n";

$client = new \GuzzleHttp\Client(['base_uri' => $url]);

try {
    echo "1. Testing OAuth token...\n";
    $response = $client->post('/oauth/token', [
        'form_params' => [
            'grant_type' => 'password',
            'username' => $username,
            'password' => $password,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
        ],
        'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded'
        ]
    ]);
    
    $data = json_decode($response->getBody(), true);
    $token = $data['access_token'];
    echo "✅ Got OAuth token!\n\n";
    
    echo "2. Testing land assets access...\n";
    $assetResponse = $client->get('/api/asset/land', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/vnd.api+json'
        ]
    ]);
    
    $assetData = json_decode($assetResponse->getBody(), true);
    echo "✅ Successfully accessed land assets!\n";
    echo "Found " . (isset($assetData['data']) ? count($assetData['data']) : 0) . " land assets\n";
    
    if (isset($assetData['data']) && count($assetData['data']) > 0) {
        $firstAsset = $assetData['data'][0];
        echo "\nFirst asset details:\n";
        echo "- Name: " . ($firstAsset['attributes']['name'] ?? 'No name') . "\n";
        echo "- ID: " . $firstAsset['id'] . "\n";
        echo "- Has geometry: " . (isset($firstAsset['attributes']['geometry']) ? 'YES ✅' : 'NO ❌') . "\n";
        
        if (isset($firstAsset['attributes']['geometry'])) {
            $geometry = $firstAsset['attributes']['geometry'];
            echo "- Geometry type: " . ($geometry['type'] ?? 'Unknown') . "\n";
            echo "- Coordinates found: " . (isset($geometry['coordinates']) ? 'YES ✅' : 'NO ❌') . "\n";
        }
    }
    
} catch (\GuzzleHttp\Exception\ClientException $e) {
    echo "❌ API Error: " . $e->getResponse()->getStatusCode() . "\n";
    echo "Response: " . $e->getResponse()->getBody() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
