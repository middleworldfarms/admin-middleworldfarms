<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $wpApi = app('App\Services\WpApiService');
    $dashboard = new App\Http\Controllers\Admin\DashboardController($wpApi);
    
    // Test getDeliveryStats step by step
    echo "=== Testing getDeliveryStats method ===\n";
    
    // Get raw data
    $rawData = $wpApi->getDeliveryScheduleData(500);
    echo "Raw data count: " . count($rawData) . "\n";
    
    // Transform data
    $reflection = new ReflectionClass($dashboard);
    $method = $reflection->getMethod('transformScheduleData');
    $method->setAccessible(true);
    $scheduleData = $method->invoke($dashboard, $rawData);
    
    echo "Transformation success: " . ($scheduleData['success'] ? 'true' : 'false') . "\n";
    echo "Message: " . $scheduleData['message'] . "\n";
    
    // Count totals manually
    $totalDeliveries = 0;
    $totalCollections = 0;
    
    foreach ($scheduleData['data'] as $dateData) {
        $totalDeliveries += count($dateData['deliveries'] ?? []);
        $totalCollections += count($dateData['collections'] ?? []);
    }
    
    echo "Manual count - Deliveries: $totalDeliveries, Collections: $totalCollections\n";
    
    // Check status counts
    echo "\nDeliveries by status:\n";
    if (isset($scheduleData['deliveriesByStatus'])) {
        foreach ($scheduleData['deliveriesByStatus'] as $status => $statusData) {
            $count = 0;
            foreach ($statusData as $dateData) {
                $count += count($dateData['deliveries'] ?? []);
            }
            if ($count > 0) {
                echo "  $status: $count\n";
            }
        }
    }
    
    echo "\nCollections by status:\n";
    if (isset($scheduleData['collectionsByStatus'])) {
        foreach ($scheduleData['collectionsByStatus'] as $status => $statusData) {
            $count = 0;
            foreach ($statusData as $dateData) {
                $count += count($dateData['collections'] ?? []);
            }
            if ($count > 0) {
                echo "  $status: $count\n";
            }
        }
    }
    
    // Now test the actual method
    echo "\n=== Actual getDeliveryStats output ===\n";
    $stats = $dashboard->getDeliveryStats();
    foreach ($stats as $key => $value) {
        echo "$key: $value\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
