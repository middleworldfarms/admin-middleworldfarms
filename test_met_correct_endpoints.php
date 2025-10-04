<?php
// Test Met Office APIs with corrected endpoint structure from documentation
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=== Testing Met Office DataHub with Correct Endpoints ===\n\n";

$siteSpecificKey = env('MET_OFFICE_SITE_SPECIFIC_KEY');
$lat = 53.2307;
$lon = -0.5406;

echo "1. Testing Site-Specific BPF API:\n";
echo "   Trying different endpoint patterns...\n\n";

$endpoints = [
    // Try without version number
    "https://api.metoffice.gov.uk/mo-site-specific-blended-probabilistic-forecast/point/daily?latitude={$lat}&longitude={$lon}",
    // Try with includeLocationName
    "https://data.hub.api.metoffice.gov.uk/mo-site-specific-blended-probabilistic-forecast/1.0.0/point/daily?latitude={$lat}&longitude={$lon}&includeLocationName=true",
    // Try hourly
    "https://data.hub.api.metoffice.gov.uk/mo-site-specific-blended-probabilistic-forecast/1.0.0/point/hourly?latitude={$lat}&longitude={$lon}",
    // Try three-hourly  
    "https://data.hub.api.metoffice.gov.uk/mo-site-specific-blended-probabilistic-forecast/1.0.0/point/three-hourly?latitude={$lat}&longitude={$lon}",
];

foreach ($endpoints as $i => $endpoint) {
    echo "   Test " . ($i + 1) . ": " . basename(parse_url($endpoint, PHP_URL_PATH)) . "\n";
    
    try {
        $response = Http::timeout(10)->withHeaders([
            'apikey' => $siteSpecificKey,
            'accept' => 'application/json'
        ])->get($endpoint);
        
        if ($response->successful()) {
            echo "     ✅ SUCCESS!\n";
            $data = $response->json();
            
            // Check if we have forecast data
            if (isset($data['features'])) {
                echo "     Features found: " . count($data['features']) . "\n";
                if (isset($data['features'][0]['properties']['timeSeries'])) {
                    $seriesCount = count($data['features'][0]['properties']['timeSeries']);
                    echo "     Time series entries: {$seriesCount}\n";
                    
                    if ($seriesCount > 0) {
                        $firstEntry = $data['features'][0]['properties']['timeSeries'][0];
                        echo "     Sample data keys: " . implode(', ', array_keys($firstEntry)) . "\n";
                    }
                }
            }
            
            echo "     THIS ENDPOINT WORKS! ✨\n";
            break;  // Stop on first success
        } else {
            echo "     ❌ Failed: " . $response->status() . "\n";
        }
    } catch (\Exception $e) {
        echo "     ❌ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n";
