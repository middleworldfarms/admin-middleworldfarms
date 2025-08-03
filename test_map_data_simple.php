<?php

require 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\FarmOSApiService;

echo "=== Quick Map Data Test ===\n";

$service = new FarmOSApiService();
$result = $service->getGeometryAssets();

echo "Result type: " . gettype($result) . "\n";
echo "Is valid FeatureCollection: " . (isset($result['type']) && $result['type'] === 'FeatureCollection' ? 'Yes' : 'No') . "\n";
echo "Features count: " . (isset($result['features']) ? count($result['features']) : 'N/A') . "\n";
echo "Has error: " . (isset($result['error']) ? 'Yes - ' . $result['error'] : 'No') . "\n";

if (isset($result['features']) && count($result['features']) > 0) {
    $firstFeature = $result['features'][0];
    echo "\nFirst feature validation:\n";
    echo "- Has 'type': " . (isset($firstFeature['type']) ? $firstFeature['type'] : 'Missing') . "\n";
    echo "- Has 'properties': " . (isset($firstFeature['properties']) ? 'Yes' : 'No') . "\n";
    echo "- Has 'geometry': " . (isset($firstFeature['geometry']) ? 'Yes' : 'No') . "\n";
    
    if (isset($firstFeature['geometry'])) {
        echo "- Geometry type: " . ($firstFeature['geometry']['type'] ?? 'Missing') . "\n";
        echo "- Has coordinates: " . (isset($firstFeature['geometry']['coordinates']) ? 'Yes' : 'No') . "\n";
        
        if (isset($firstFeature['geometry']['coordinates'])) {
            echo "- Coordinates structure valid: " . (is_array($firstFeature['geometry']['coordinates']) ? 'Yes' : 'No') . "\n";
            if (is_array($firstFeature['geometry']['coordinates'])) {
                echo "- Coordinate rings: " . count($firstFeature['geometry']['coordinates']) . "\n";
                if (count($firstFeature['geometry']['coordinates']) > 0 && is_array($firstFeature['geometry']['coordinates'][0])) {
                    echo "- First ring points: " . count($firstFeature['geometry']['coordinates'][0]) . "\n";
                }
            }
        }
    }
}

// Test JSON encoding
echo "\nJSON encoding test:\n";
$json = json_encode($result);
if ($json === false) {
    echo "JSON encoding failed: " . json_last_error_msg() . "\n";
} else {
    echo "JSON encoding successful, length: " . strlen($json) . " chars\n";
    
    // Test JSON decoding
    $decoded = json_decode($json, true);
    if ($decoded === null) {
        echo "JSON decoding failed: " . json_last_error_msg() . "\n";
    } else {
        echo "JSON round-trip successful\n";
    }
}

echo "\n=== End Test ===\n";
