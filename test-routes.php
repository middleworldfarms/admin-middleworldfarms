<?php
/**
 * Test route planning functionality
 */

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing Route Planning Functionality ===\n";

try {
    // Test RouteController instantiation
    $controller = app('App\Http\Controllers\Admin\RouteController');
    echo "✓ RouteController instantiated successfully\n";
    
    // Test services
    $routeService = app('App\Services\RouteOptimizationService');
    echo "✓ RouteOptimizationService instantiated successfully\n";
    
    $wpGoMapsService = app('App\Services\WPGoMapsService');
    echo "✓ WPGoMapsService instantiated successfully\n";
    
    $driverService = app('App\Services\DriverNotificationService');
    echo "✓ DriverNotificationService instantiated successfully\n";
    
    // Test configuration
    $googleMapsKey = config('services.google_maps.api_key');
    echo "✓ Google Maps API Key: " . ($googleMapsKey ? 'SET (' . substr($googleMapsKey, 0, 10) . '...)' : 'NOT SET') . "\n";
    
    $depotAddress = config('services.delivery.depot_address');
    echo "✓ Depot Address: " . $depotAddress . "\n";
    
    // Test DeliveryScheduleService
    $deliveryService = app('App\Services\DeliveryScheduleService');
    echo "✓ DeliveryScheduleService instantiated successfully\n";
    
    echo "\nTesting getEnhancedSchedule method...\n";
    $scheduleData = $deliveryService->getEnhancedSchedule();
    
    if (isset($scheduleData['success']) && $scheduleData['success']) {
        echo "✓ Schedule data retrieved successfully\n";
        echo "  Data source: " . ($scheduleData['data_source'] ?? 'unknown') . "\n";
        
        if (isset($scheduleData['data']) && is_array($scheduleData['data'])) {
            $totalDeliveries = 0;
            $totalCollections = 0;
            $dates = array_keys($scheduleData['data']);
            
            foreach ($scheduleData['data'] as $date => $dateData) {
                $deliveries = count($dateData['deliveries'] ?? []);
                $collections = count($dateData['collections'] ?? []);
                $totalDeliveries += $deliveries;
                $totalCollections += $collections;
            }
            
            echo "  Dates covered: " . count($dates) . "\n";
            echo "  Total deliveries: $totalDeliveries\n";
            echo "  Total collections: $totalCollections\n";
            
            // Test with today's date
            $today = date('Y-m-d');
            if (isset($scheduleData['data'][$today])) {
                $todayDeliveries = count($scheduleData['data'][$today]['deliveries'] ?? []);
                $todayCollections = count($scheduleData['data'][$today]['collections'] ?? []);
                echo "  Today ($today): $todayDeliveries deliveries, $todayCollections collections\n";
            } else {
                echo "  No data for today ($today)\n";
            }
        }
    } else {
        echo "✗ Failed to get schedule data\n";
        if (isset($scheduleData['error'])) {
            echo "  Error: " . $scheduleData['error'] . "\n";
        }
    }
    
    // Test RouteController index method
    echo "\nTesting RouteController index method...\n";
    $request = new \Illuminate\Http\Request();
    $request->merge(['date' => date('Y-m-d')]);
    
    $response = $controller->index($request);
    
    if ($response instanceof \Illuminate\View\View) {
        echo "✓ RouteController index returned a view\n";
        $viewData = $response->getData();
        echo "  View: " . $response->getName() . "\n";
        echo "  Deliveries for route planning: " . count($viewData['deliveries'] ?? []) . "\n";
        echo "  Google Maps key available: " . (empty($viewData['google_maps_key']) ? 'NO' : 'YES') . "\n";
        
        if (isset($viewData['message'])) {
            echo "  Message: " . $viewData['message'] . "\n";
        }
        if (isset($viewData['error'])) {
            echo "  Error: " . $viewData['error'] . "\n";
        }
    } else {
        echo "✗ RouteController index did not return a view\n";
    }
    
    echo "\n=== Route Planning Test Complete ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
