<?php
// Test Land Observations API (actual current weather from UK stations)
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=== Testing Land Observations API ===\n\n";

$landObsKey = env('MET_OFFICE_LAND_OBSERVATIONS_KEY');
$lat = 53.2307;  // Lincoln
$lon = -0.5406;

echo "This API provides ACTUAL weather station data from across the UK!\n\n";

// Test different Land Observations endpoints
$endpoints = [
    // Try to list stations
    'https://data.hub.api.metoffice.gov.uk/observation-land/1/stations',
    // Try hourly observations
    'https://data.hub.api.metoffice.gov.uk/observation-land/1/observations/hourly',
    // Try with location filter
    "https://data.hub.api.metoffice.gov.uk/observation-land/1/observations/hourly?latitude={$lat}&longitude={$lon}",
];

foreach ($endpoints as $endpoint) {
    echo "Testing: " . basename($endpoint) . "\n";
    
    try {
        $response = Http::timeout(10)->withHeaders([
            'apikey' => $landObsKey,
            'accept' => 'application/json'
        ])->get($endpoint);
        
        if ($response->successful()) {
            echo "  ✅ SUCCESS!\n";
            $data = $response->json();
            echo "  Response keys: " . implode(', ', array_keys($data)) . "\n\n";
            
            // If we got stations, show nearest
            if (isset($data['stations'])) {
                echo "  Found " . count($data['stations']) . " weather stations\n";
                // Find nearest station
                $nearest = null;
                $minDist = PHP_FLOAT_MAX;
                foreach ($data['stations'] as $station) {
                    if (isset($station['latitude']) && isset($station['longitude'])) {
                        $dist = sqrt(pow($station['latitude'] - $lat, 2) + pow($station['longitude'] - $lon, 2));
                        if ($dist < $minDist) {
                            $minDist = $dist;
                            $nearest = $station;
                        }
                    }
                }
                if ($nearest) {
                    echo "  Nearest station: " . ($nearest['name'] ?? 'Unknown') . "\n";
                    echo "  Location: {$nearest['latitude']}, {$nearest['longitude']}\n";
                    echo "  Distance: ~" . round($minDist * 111, 1) . " km\n";
                }
            }
            
            // If we got observations, show them
            if (isset($data['observations'])) {
                echo "  Found " . count($data['observations']) . " observations\n";
                if (count($data['observations']) > 0) {
                    $latest = $data['observations'][0];
                    echo "  Latest observation:\n";
                    echo "    Temperature: " . ($latest['screenTemperature'] ?? 'N/A') . "°C\n";
                    echo "    Humidity: " . ($latest['screenRelativeHumidity'] ?? 'N/A') . "%\n";
                    echo "    Wind Speed: " . ($latest['windSpeed'] ?? 'N/A') . " mph\n";
                }
            }
            
            break;  // Stop on success
        } else {
            echo "  ❌ Failed: " . $response->status() . "\n";
            echo "  Error: " . substr($response->body(), 0, 150) . "\n\n";
        }
    } catch (\Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n\n";
    }
}
