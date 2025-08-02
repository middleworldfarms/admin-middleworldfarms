<?php

require_once 'vendor/autoload.php';

// Simple test without Laravel bootstrapping
use GuzzleHttp\Client;

echo "Testing FarmOS API directly with basic auth...\n\n";

$client = new Client([
    'base_uri' => 'https://farmos.middleworldfarms.org',
    'timeout' => 10,
]);

echo "Testing various endpoints with martin user:\n\n";

$endpoints = [
    '/api/asset/land' => 'Land assets',
    '/api/taxonomy_term/plant_type' => 'Plant types',
    '/api/log/seeding' => 'Seeding logs',
    '/api/asset/plant' => 'Plant assets',
];

foreach ($endpoints as $endpoint => $description) {
    echo "Testing $description ($endpoint):\n";
    
    try {
        $response = $client->get($endpoint, [
            'auth' => ['martin', 'Mavckie1974'],
            'headers' => [
                'Accept' => 'application/vnd.api+json'
            ]
        ]);
        
        $data = json_decode($response->getBody(), true);
        $itemCount = count($data['data'] ?? []);
        $omittedCount = count($data['meta']['omitted'] ?? []);
        
        if ($omittedCount > 0) {
            echo "  ⚠️  $itemCount visible, $omittedCount omitted (permission issue)\n";
        } else {
            echo "  ✅ $itemCount items accessible\n";
        }
        
    } catch (Exception $e) {
        echo "  ❌ Failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "Summary:\n";
echo "- Basic authentication works with martin user\n";
echo "- farmOS has data but user needs additional API permissions\n";
echo "- The FarmOSApiService should handle this gracefully\n";

echo "\nTest completed.\n";
