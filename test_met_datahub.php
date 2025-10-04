<?php
// Test actual Met Office DataHub API endpoints
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=== Testing Met Office DataHub APIs ===\n\n";

$keys = [
    'Site-Specific (BPF)' => env('MET_OFFICE_SITE_SPECIFIC_KEY'),
    'Land Observations' => env('MET_OFFICE_LAND_OBSERVATIONS_KEY'),
    'Atmospheric' => env('MET_OFFICE_ATMOSPHERIC_KEY'),
    'Map Images' => env('MET_OFFICE_MAP_IMAGES_KEY'),
];

// Test endpoints based on subscription info
$endpoints = [
    'Site-Specific (BPF)' => [
        'https://data.hub.api.metoffice.gov.uk/mo-site-specific-blended-probabilistic-forecast/1.0.0/point/daily?latitude=53.2307&longitude=-0.5406',
        'https://data.hub.api.metoffice.gov.uk/mo-site-specific-blended-probabilistic-forecast/1.0.0/point/hourly?latitude=53.2307&longitude=-0.5406',
    ],
    'Land Observations' => [
        'https://data.hub.api.metoffice.gov.uk/observation-land/1/stations',
    ],
    'Atmospheric' => [
        'https://data.hub.api.metoffice.gov.uk/atmospheric-models/1.0.0/orders',
    ],
    'Map Images' => [
        'https://data.hub.api.metoffice.gov.uk/map-images/1.0.0/capabilities',
    ],
];

foreach ($keys as $name => $key) {
    echo "Testing {$name}:\n";
    echo "  Key: " . substr($key, 0, 20) . "...\n";
    
    if (!isset($endpoints[$name])) {
        echo "  ⚠️  No test endpoints defined\n\n";
        continue;
    }
    
    foreach ($endpoints[$name] as $endpoint) {
        echo "  Testing: " . basename($endpoint) . "\n";
        
        try {
            $response = Http::timeout(10)->withHeaders([
                'apikey' => $key,
                'accept' => 'application/json'
            ])->get($endpoint);
            
            if ($response->successful()) {
                echo "    ✅ SUCCESS! Status: " . $response->status() . "\n";
                $data = $response->json();
                echo "    Response keys: " . implode(', ', array_keys($data)) . "\n";
            } else {
                echo "    ❌ FAILED! Status: " . $response->status() . "\n";
                echo "    Error: " . substr($response->body(), 0, 150) . "\n";
            }
        } catch (\Exception $e) {
            echo "    ❌ Exception: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
}
