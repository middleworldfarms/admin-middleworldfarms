<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Debug the transformation logic
try {
    $wpApi = app('App\Services\WpApiService');
    $dashboard = new App\Http\Controllers\Admin\DashboardController($wpApi);
    
    $rawData = $wpApi->getDeliveryScheduleData(20);
    echo "Raw data count: " . count($rawData) . "\n";
    
    $reflection = new ReflectionClass($dashboard);
    $method = $reflection->getMethod('transformScheduleData');
    $method->setAccessible(true);
    $transformedData = $method->invoke($dashboard, $rawData);
    
    echo "=== Data Analysis ===\n";
    echo "Success: " . ($transformedData['success'] ? 'true' : 'false') . "\n";
    echo "Message: " . $transformedData['message'] . "\n";
    
    $totalDeliveries = 0;
    $totalCollections = 0;
    
    foreach ($transformedData['data'] as $date => $dateData) {
        $deliveriesCount = count($dateData['deliveries'] ?? []);
        $collectionsCount = count($dateData['collections'] ?? []);
        
        if ($deliveriesCount > 0 || $collectionsCount > 0) {
            echo "Date $date: $deliveriesCount deliveries, $collectionsCount collections\n";
        }
        
        $totalDeliveries += $deliveriesCount;
        $totalCollections += $collectionsCount;
    }
    
    echo "Grand totals: $totalDeliveries deliveries, $totalCollections collections\n";
    
    // Check status counts
    echo "\n=== Status Counts ===\n";
    foreach ($transformedData['deliveriesByStatus'] as $status => $statusData) {
        $count = 0;
        foreach ($statusData as $dateData) {
            $count += count($dateData['deliveries'] ?? []);
        }
        if ($count > 0) {
            echo "Deliveries with status '$status': $count\n";
        }
    }
    
    foreach ($transformedData['collectionsByStatus'] as $status => $statusData) {
        $count = 0;
        foreach ($statusData as $dateData) {
            $count += count($dateData['collections'] ?? []);
        }
        if ($count > 0) {
            echo "Collections with status '$status': $count\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
