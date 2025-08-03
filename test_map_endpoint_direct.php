<?php

require 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Http\Request;

echo "=== Test Map Endpoint Directly ===\n";

try {
    // Create controller instance
    $controller = new DashboardController();
    
    // Create a mock request
    $request = Request::create('/admin/farmos-map-data', 'GET');
    
    // Call the map endpoint
    $response = $controller->farmosMapData($request);
    
    echo "Response type: " . gettype($response) . "\n";
    
    if (method_exists($response, 'getContent')) {
        $content = $response->getContent();
        echo "Response content length: " . strlen($content) . " chars\n";
        
        $data = json_decode($content, true);
        if ($data) {
            echo "JSON valid: Yes\n";
            echo "Type: " . ($data['type'] ?? 'Missing') . "\n";
            echo "Features count: " . (isset($data['features']) ? count($data['features']) : 'Missing') . "\n";
            
            if (isset($data['features']) && count($data['features']) > 0) {
                echo "\nFirst feature:\n";
                echo "- Name: " . ($data['features'][0]['properties']['name'] ?? 'Missing') . "\n";
                echo "- Geometry type: " . ($data['features'][0]['geometry']['type'] ?? 'Missing') . "\n";
                echo "- Has coordinates: " . (isset($data['features'][0]['geometry']['coordinates']) ? 'Yes' : 'No') . "\n";
            }
            
            if (isset($data['error'])) {
                echo "Error: " . $data['error'] . "\n";
            }
        } else {
            echo "JSON valid: No\n";
            echo "Raw content preview: " . substr($content, 0, 200) . "...\n";
        }
    } else {
        echo "Response object: " . print_r($response, true) . "\n";
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== End Test ===\n";
