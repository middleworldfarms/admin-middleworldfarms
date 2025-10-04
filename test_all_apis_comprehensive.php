<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== Comprehensive Met Office API Test ===\n\n";

// All API keys
$apis = [
    'Site-Specific' => $_ENV['MET_OFFICE_SITE_SPECIFIC_KEY'],
    'Land Observations' => $_ENV['MET_OFFICE_LAND_OBSERVATIONS_KEY'],
    'Atmospheric' => $_ENV['MET_OFFICE_ATMOSPHERIC_KEY'],
    'Map Images' => $_ENV['MET_OFFICE_MAP_IMAGES_KEY'],
];

$lat = 53.2307;
$lon = -0.5406;

// Try various endpoint patterns for each API
$endpointPatterns = [
    // Root/capabilities
    '/capabilities',
    '',
    '/api',
    '/v1',
    '/v0',
    
    // Data endpoints  
    '/forecasts',
    '/observations',
    '/data',
    '/current',
    '/weather',
    
    // Location-based
    "/point?latitude=$lat&longitude=$lon",
    "/forecast?lat=$lat&lon=$lon",
    "/hourly?latitude=$lat&longitude=$lon",
];

$client = new \GuzzleHttp\Client([
    'timeout' => 5,
    'verify' => false,
    'http_errors' => false, // Don't throw exceptions
]);

$successfulEndpoints = [];

foreach ($apis as $apiName => $apiKey) {
    echo "\n========================================\n";
    echo "Testing: $apiName\n";
    echo "========================================\n";
    
    // Get base URL from JWT
    $parts = explode('.', $apiKey);
    if (count($parts) === 3) {
        $payload = json_decode(base64_decode($parts[1]), true);
        $context = $payload['subscribedAPIs'][0]['context'] ?? 'unknown';
        $baseUrl = "https://data.hub.api.metoffice.gov.uk$context";
        echo "Base URL: $baseUrl\n\n";
        
        foreach ($endpointPatterns as $pattern) {
            $url = $baseUrl . $pattern;
            
            $response = $client->get($url, [
                'headers' => [
                    'apikey' => trim($apiKey),
                    'Accept' => 'application/json',
                ],
            ]);
            
            $status = $response->getStatusCode();
            
            if ($status === 200) {
                echo "  ✅ SUCCESS: $pattern (HTTP $status)\n";
                $body = json_decode($response->getBody(), true);
                if ($body && is_array($body)) {
                    echo "     Keys: " . implode(', ', array_keys($body)) . "\n";
                }
                $successfulEndpoints[] = "$apiName: $url";
            } elseif ($status < 500) {
                // Don't spam on client errors, just note them
                if ($status !== 401 && $status !== 403 && $status !== 404) {
                    echo "  ℹ️  HTTP $status: $pattern\n";
                }
            }
        }
    }
}

echo "\n\n=================================\n";
echo "SUCCESSFUL ENDPOINTS:\n";
echo "=================================\n";
if (empty($successfulEndpoints)) {
    echo "None found 😢\n";
} else {
    foreach ($successfulEndpoints as $endpoint) {
        echo "$endpoint\n";
    }
}
