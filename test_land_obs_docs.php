<?php
require_once __DIR__ . '/vendor/autoload.php';

// Get Land Observations API key from .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['MET_OFFICE_LAND_OBS_KEY'];

echo "=== Met Office Land Observations API - Documentation Check ===\n\n";

// According to Met Office CDA docs, the Land Observations API path is:
// /observation-land/{version}/observations
// Let's try different versions and paths

$tests = [
    'v1 observations' => 'https://data.hub.api.metoffice.gov.uk/observation-land/1.0.0/observations',
    'v1.0.0 stations' => 'https://data.hub.api.metoffice.gov.uk/observation-land/1.0.0/stations',
    'no version observations' => 'https://data.hub.api.metoffice.gov.uk/observation-land/observations',
    'no version stations' => 'https://data.hub.api.metoffice.gov.uk/observation-land/stations',
    'capabilities' => 'https://data.hub.api.metoffice.gov.uk/observation-land/1.0.0/capabilities',
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
        echo "  Response: " . json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        
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
