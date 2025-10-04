<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// The env var uses underscores not spaces
$apiKey = str_replace(["\n", "\r"], '', $_ENV['MET_OFFICE_SITE_SPECIFIC_KEY'] ?? '');

echo "=== Met Office Site-Specific API - Testing v0 endpoints ===\n\n";

// Try different base URL patterns based on the "BETA" status
$tests = [
    'v0 site list' => 'https://data.hub.api.metoffice.gov.uk/sitespecific/v0/site/list',
    'v0 point list' => 'https://data.hub.api.metoffice.gov.uk/sitespecific/v0/point/list',
    'v0 forecasts' => 'https://data.hub.api.metoffice.gov.uk/sitespecific/v0/forecasts',
    'v0 point daily with location' => 'https://data.hub.api.metoffice.gov.uk/sitespecific/v0/point/daily?latitude=53.2307&longitude=-0.5406',
    'v0 point hourly with location' => 'https://data.hub.api.metoffice.gov.uk/sitespecific/v0/point/hourly?latitude=53.2307&longitude=-0.5406',
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
        $body = $response->getBody()->getContents();
        
        echo "  ✅ Success: $statusCode\n";
        
        $decoded = json_decode($body, true);
        if ($decoded) {
            $preview = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if (strlen($preview) > 800) {
                echo "  Response (first 800 chars): " . substr($preview, 0, 800) . "...\n";
            } else {
                echo "  Response: " . $preview . "\n";
            }
        } else {
            echo "  Response (raw, first 500): " . substr($body, 0, 500) . "\n";
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
