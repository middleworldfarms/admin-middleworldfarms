<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['MET_OFFICE_ATMOSPHERIC_KEY'];

echo "=== Met Office Atmospheric Models API - Weather Data ===\n\n";

// Based on JWT context: /atmospheric-models/1.0.0
$baseUrl = 'https://data.hub.api.metoffice.gov.uk/atmospheric-models/1.0.0';

// Middleworld Farms location
$lat = 53.2307;
$lon = -0.5406;

$tests = [
    'capabilities' => "$baseUrl/capabilities",
    'orders' => "$baseUrl/orders",
    'forecasts' => "$baseUrl/forecasts",
    'forecasts with location' => "$baseUrl/forecasts?latitude=$lat&longitude=$lon",
    'models' => "$baseUrl/models",
    'parameters' => "$baseUrl/parameters",
];

$client = new \GuzzleHttp\Client([
    'timeout' => 10,
    'verify' => false,
]);

foreach ($tests as $name => $url) {
    echo "Testing: $name\n";
    echo "  URL: $url\n";
    
    try {
        $response = $client->get($url, [
            'headers' => [
                'apikey' => $apiKey,
                'Accept' => 'application/json',
            ],
        ]);
        
        $statusCode = $response->getStatusCode();
        $body = json_decode($response->getBody(), true);
        
        echo "  ✅ Success: $statusCode\n";
        
        if (is_array($body)) {
            if (isset($body['orders'])) {
                echo "  Orders: " . json_encode($body['orders'], JSON_PRETTY_PRINT) . "\n";
            } elseif (isset($body[0])) {
                echo "  First item: " . json_encode($body[0], JSON_PRETTY_PRINT) . "\n";
                echo "  Total items: " . count($body) . "\n";
            } else {
                $preview = json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                if (strlen($preview) > 500) {
                    echo "  Response (first 500 chars): " . substr($preview, 0, 500) . "...\n";
                } else {
                    echo "  Response: " . $preview . "\n";
                }
            }
        }
        
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        $statusCode = $e->getResponse()->getStatusCode();
        $body = json_decode($e->getResponse()->getBody(), true);
        
        echo "  ❌ Failed: $statusCode\n";
        echo "  Error: " . json_encode($body) . "\n";
    } catch (\Exception $e) {
        echo "  ❌ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}
