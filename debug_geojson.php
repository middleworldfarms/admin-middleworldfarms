<?php

require 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\FarmOSApiService;

echo "=== Debug GeoJSON Structure ===\n";

$service = new FarmOSApiService();
$result = $service->getGeometryAssets();

echo "Result type: " . gettype($result) . "\n";
echo "Has features: " . (isset($result['features']) ? 'Yes' : 'No') . "\n";

if (isset($result['features'])) {
    echo "Feature count: " . count($result['features']) . "\n";
    
    if (count($result['features']) > 0) {
        echo "\nFirst feature structure:\n";
        print_r($result['features'][0]);
        
        // Check if geometry is valid
        if (isset($result['features'][0]['geometry'])) {
            $geometry = $result['features'][0]['geometry'];
            echo "\nGeometry type: " . ($geometry['type'] ?? 'Missing type') . "\n";
            echo "Has coordinates: " . (isset($geometry['coordinates']) ? 'Yes' : 'No') . "\n";
            
            if (isset($geometry['coordinates'])) {
                echo "Coordinates type: " . gettype($geometry['coordinates']) . "\n";
                if (is_array($geometry['coordinates'])) {
                    echo "Coordinates count: " . count($geometry['coordinates']) . "\n";
                }
            }
        }
    }
}

if (isset($result['error'])) {
    echo "\nError: " . $result['error'] . "\n";
}

echo "\n=== Full Result (JSON) ===\n";
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

echo "\n=== End Debug ===\n";
