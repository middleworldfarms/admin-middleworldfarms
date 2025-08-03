<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Events\EventServiceProvider;

// Bootstrap Laravel
$app = new Application(realpath(__DIR__));
$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);
$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);
$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Load environment and config
$app->bootstrapWith([
    \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
    \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
]);

echo "=== Testing OAuth2 for Land Assets ===\n";

// Get config values
$farmosUrl = $app['config']->get('farmos.url');
$clientId = $app['config']->get('farmos.client_id');
$clientSecret = $app['config']->get('farmos.client_secret');

echo "FarmOS URL: $farmosUrl\n";
echo "Client ID: $clientId\n";
echo "Client Secret: " . ($clientSecret ? 'SET' : 'NOT SET') . "\n\n";

if (!$clientId || !$clientSecret) {
    echo "ERROR: OAuth2 credentials not configured!\n";
    exit(1);
}

// Test OAuth2 token acquisition
echo "=== Testing OAuth2 Token ===\n";

$guzzle = new \GuzzleHttp\Client([
    'base_uri' => $farmosUrl,
    'timeout' => 30,
    'verify' => false
]);

try {
    $response = $guzzle->post('/oauth/token', [
        'form_params' => [
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => 'farm_manager'
        ],
        'headers' => [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ]
    ]);

    $tokenData = json_decode($response->getBody(), true);
    
    if (isset($tokenData['access_token'])) {
        echo "✓ OAuth2 token acquired successfully\n";
        $token = $tokenData['access_token'];
        echo "Token expires in: " . ($tokenData['expires_in'] ?? 'unknown') . " seconds\n\n";
    } else {
        echo "✗ Failed to get OAuth2 token\n";
        echo "Response: " . json_encode($tokenData) . "\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ OAuth2 request failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test land assets API with OAuth2
echo "=== Testing Land Assets API with OAuth2 ===\n";

try {
    $response = $guzzle->get('/api/asset/land', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ],
        'query' => [
            'filter[status]' => 'active'
        ]
    ]);

    $data = json_decode($response->getBody(), true);
    
    echo "Status Code: " . $response->getStatusCode() . "\n";
    
    if (isset($data['data'])) {
        echo "✓ Land assets retrieved successfully\n";
        echo "Asset count: " . count($data['data']) . "\n";
        
        if (!empty($data['data'])) {
            $firstAsset = $data['data'][0];
            echo "Sample asset: " . ($firstAsset['attributes']['name'] ?? 'Unknown') . "\n";
            echo "Has geometry: " . (isset($firstAsset['attributes']['geometry']) ? 'Yes' : 'No') . "\n";
        }
    } else {
        echo "✗ Unexpected response format\n";
        echo "Response keys: " . implode(', ', array_keys($data)) . "\n";
    }
    
    if (isset($data['meta']['omitted'])) {
        echo "⚠ Some assets were omitted due to permissions\n";
        echo "Omitted count: " . count($data['meta']['omitted']) . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Land assets request failed: " . $e->getMessage() . "\n";
    
    if ($e instanceof \GuzzleHttp\Exception\ClientException) {
        $response = $e->getResponse();
        echo "Status: " . $response->getStatusCode() . "\n";
        echo "Body: " . $response->getBody() . "\n";
    }
}

echo "\n=== Test Complete ===\n";
