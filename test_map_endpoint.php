<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel app
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing FarmOS Map Data Endpoint ===\n";

try {
    // Test the FarmOSApiService directly
    $farmosService = $app->make(\App\Services\FarmOSApiService::class);
    echo "FarmOS Service instantiated successfully\n";
    
    // Test the getGeometryAssets method
    echo "Calling getGeometryAssets()...\n";
    $geometryData = $farmosService->getGeometryAssets();
    
    echo "Geometry data received:\n";
    echo "Type: " . gettype($geometryData) . "\n";
    
    if (is_array($geometryData)) {
        echo "Array keys: " . implode(', ', array_keys($geometryData)) . "\n";
        if (isset($geometryData['type'])) {
            echo "GeoJSON type: " . $geometryData['type'] . "\n";
        }
        if (isset($geometryData['features'])) {
            echo "Features count: " . count($geometryData['features']) . "\n";
        }
        if (isset($geometryData['error'])) {
            echo "Error: " . $geometryData['error'] . "\n";
        }
    }
    
    // Show JSON output (truncated)
    $json = json_encode($geometryData, JSON_PRETTY_PRINT);
    $truncated = strlen($json) > 1000 ? substr($json, 0, 1000) . '...[TRUNCATED]' : $json;
    echo "JSON output:\n" . $truncated . "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
