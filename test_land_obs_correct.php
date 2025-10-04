<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['MET_OFFICE_LAND_OBSERVATIONS_KEY'];

echo "=== Met Office Land Observations API - Correct Endpoints ===\n\n";

// Based on JWT context: /observation-land/1
$baseUrl = 'https://data.hub.api.metoffice.gov.uk/observation-land/1';

$tests = [
    'capabilities' => "$baseUrl/capabilities",
    'stations' => "$baseUrl/stations",
    'observations' => "$baseUrl/observations",
    'hourly' => "$baseUrl/hourly",
    'hourly with location' => "$baseUrl/hourly?latitude=53.2307&longitude=-0.5406",
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
        
        // Show first few items if it's an array
        if (is_array($body)) {
            if (isset($body[0])) {
                echo "  First item: " . json_encode($body[0], JSON_PRETTY_PRINT) . "\n";
                echo "  Total items: " . count($body) . "\n";
            } else {
                echo "  Response: " . json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            }
        } else {
            echo "  Response: " . $body . "\n";
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
