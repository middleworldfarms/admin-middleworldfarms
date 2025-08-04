<?php

require_once '/opt/sites/admin.middleworldfarms.org/vendor/autoload.php';

$app = require_once '/opt/sites/admin.middleworldfarms.org/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Services\FarmOSApiService;
use App\Http\Controllers\Admin\FarmOSDataController;
use Illuminate\Http\Request;

echo "=== Testing Planting Chart Data Transformation ===\n";

try {
    // Create the farmOS API service
    $farmOSApi = new FarmOSApiService();
    
    echo "1. Getting farmOS data...\n";
    $geometryAssets = $farmOSApi->getGeometryAssets();
    $cropPlans = $farmOSApi->getCropPlanningData();
    
    echo "Found " . count($geometryAssets['features'] ?? []) . " geometry assets\n";
    echo "Found " . count($cropPlans) . " crop plans\n";
    
    // Create a controller instance and use reflection to access private methods
    $controller = new FarmOSDataController($farmOSApi);
    $reflection = new ReflectionClass($controller);
    
    echo "\n2. Testing data transformation...\n";
    
    // Test transformLandAssetsToChart method
    $transformMethod = $reflection->getMethod('transformLandAssetsToChart');
    $transformMethod->setAccessible(true);
    $chartData = $transformMethod->invoke($controller, $geometryAssets, $cropPlans);
    
    echo "Chart data keys: " . implode(', ', array_keys($chartData)) . "\n";
    echo "Chart data structure:\n";
    foreach ($chartData as $location => $activities) {
        echo "  $location: " . count($activities) . " activities\n";
        foreach (array_slice($activities, 0, 2) as $activity) {
            echo "    - " . ($activity['crop'] ?? 'Unknown') . " (" . ($activity['type'] ?? 'Unknown') . ") " . 
                 ($activity['start'] ?? 'No start') . " - " . ($activity['end'] ?? 'No end') . "\n";
        }
    }
    
    // Test extractLocationsFromAssets method
    $locationMethod = $reflection->getMethod('extractLocationsFromAssets');
    $locationMethod->setAccessible(true);
    $locations = $locationMethod->invoke($controller, $geometryAssets);
    
    echo "\n3. Extracted locations:\n";
    echo "Locations: " . implode(', ', $locations) . "\n";
    
    echo "\n=== Test Complete ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    if (strpos($e->getMessage(), 'Unauthorized') !== false) {
        echo "\nNote: This appears to be an OAuth2 authentication issue.\n";
        echo "The farmOS API may need token refresh or configuration update.\n";
    }
}
