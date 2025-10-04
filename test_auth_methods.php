<?php
require_once __DIR__ . '/vendor/autoload.php';

// Read key from .env
$envContent = file_get_contents(__DIR__ . '/.env');
if (preg_match('/MET_OFFICE_SITE_SPECIFIC_KEY=(.+?)(?=\nMET_OFFICE|\n[A-Z_]+=|$)/s', $envContent, $matches)) {
    $apiKey = preg_replace('/\s+/', '', $matches[1]);
} else {
    die("ERROR: Key not found\n");
}

echo "=== Testing different authentication methods ===\n\n";

$url = 'https://data.hub.api.metoffice.gov.uk/mo-site-specific-blended-probabilistic-forecast/1.0.0/collections';

$client = new \GuzzleHttp\Client([
    'timeout' => 10,
    'verify' => false,
    'http_errors' => false,
]);

$authMethods = [
    'apikey in header' => ['apikey' => $apiKey],
    'x-api-key in header' => ['x-api-key' => $apiKey],
    'Authorization Bearer' => ['Authorization' => "Bearer $apiKey"],
    'Authorization apikey' => ['Authorization' => "apikey $apiKey"],
    'api_key in header' => ['api_key' => $apiKey],
];

foreach ($authMethods as $method => $headers) {
    echo "Testing: $method\n";
    
    $headers['Accept'] = 'application/json';
    
    $response = $client->get($url, ['headers' => $headers]);
    $status = $response->getStatusCode();
    
    echo "  HTTP $status";
    
    if ($status === 200) {
        echo " ✅ SUCCESS!\n";
        $body = json_decode($response->getBody(), true);
        echo "  Response keys: " . implode(', ', array_keys($body ?? [])) . "\n";
    } elseif ($status === 401) {
        echo " - Invalid Credentials\n";
    } elseif ($status === 403) {
        echo " - Forbidden\n";
    } elseif ($status === 404) {
        echo " - Not Found\n";
    } else {
        echo " - " . $response->getReasonPhrase() . "\n";
    }
}
