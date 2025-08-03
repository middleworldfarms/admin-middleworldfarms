<?php

require 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\FarmOSApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

echo "=== Debug OAuth2 Token Acquisition ===\n";

// Check config values
echo "Config farmos.client_id: " . Config::get('farmos.client_id') . "\n";
echo "Config farmos.client_secret: [" . strlen(Config::get('farmos.client_secret')) . " chars] " . Config::get('farmos.client_secret') . "\n";
echo "Env FARMOS_OAUTH_CLIENT_SECRET: [" . strlen(env('FARMOS_OAUTH_CLIENT_SECRET')) . " chars] " . env('FARMOS_OAUTH_CLIENT_SECRET') . "\n";

// Create service instance
$service = new FarmOSApiService();

// Try to get geometry assets (this should trigger OAuth2)
echo "\nTesting OAuth2 via getGeometryAssets()...\n";

try {
    $result = $service->getGeometryAssets();
    
    echo "Success! Result type: " . gettype($result) . "\n";
    if (is_array($result)) {
        echo "Auth method: " . ($result['auth_method'] ?? 'Unknown') . "\n";
        echo "Has error: " . (isset($result['error']) ? 'Yes - ' . $result['error'] : 'No') . "\n";
        echo "Features count: " . (isset($result['features']) ? count($result['features']) : 'N/A') . "\n";
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

echo "\n=== End Debug ===\n";
