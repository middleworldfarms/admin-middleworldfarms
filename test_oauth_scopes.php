<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing different OAuth2 scopes for farmOS...\n";

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;

$client = new Client(['timeout' => 10]);
$baseUrl = Config::get('farmos.url');
$clientId = Config::get('farmos.client_id');
$clientSecret = Config::get('farmos.client_secret');

// Try different scopes that might work with farmOS 2.x
$scopes = [
    'farm_manager',
    'farm_worker',
    'farm_viewer',
    'farmos_default',
    'user',
    'default',
    '', // no scope
];

foreach ($scopes as $scope) {
    try {
        echo "\nTrying scope: '" . ($scope ?: 'none') . "'...\n";
        
        $params = [
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ];
        
        if ($scope) {
            $params['scope'] = $scope;
        }
        
        $response = $client->post($baseUrl . '/oauth/token', [
            'form_params' => $params,
            'http_errors' => false
        ]);
        
        $statusCode = $response->getStatusCode();
        $body = $response->getBody()->getContents();
        
        if ($statusCode === 200) {
            $data = json_decode($body, true);
            if (isset($data['access_token'])) {
                echo "✓ SUCCESS with scope '$scope' - Token obtained!\n";
                echo "  Token type: " . ($data['token_type'] ?? 'unknown') . "\n";
                if (isset($data['scope'])) {
                    echo "  Granted scope: " . $data['scope'] . "\n";
                }
                break;
            }
        } else {
            $data = json_decode($body, true);
            echo "✗ Failed ($statusCode): " . ($data['error'] ?? 'Unknown error') . "\n";
            if (isset($data['error_description'])) {
                echo "  Description: " . $data['error_description'] . "\n";
            }
        }
        
    } catch (Exception $e) {
        echo "✗ Exception: " . $e->getMessage() . "\n";
    }
}

echo "\nDone!\n";
