<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\WpApiService;
use App\Http\Controllers\Admin\DeliveryController;

$wpApi = app(WpApiService::class);
$controller = new DeliveryController();

echo "<h2>Testing Updated Delivery Frequency Detection</h2>\n\n";

try {
    // Get raw subscriptions
    $rawData = $wpApi->getDeliveryScheduleData(50);
    
    echo "Total subscriptions: " . count($rawData) . "<br><br>\n\n";
    
    // Call the transformScheduleData method using reflection to access the private method
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('transformScheduleData');
    $method->setAccessible(true);
    
    // Transform the data
    $transformedData = $method->invoke($controller, $rawData);
    
    // Analyze delivery frequencies after transformation
    $deliveryCount = 0;
    $collectionCount = 0;
    $deliveryFrequencies = ['Weekly' => 0, 'Fortnightly' => 0, 'Other' => 0];
    $collectionFrequencies = ['Weekly' => 0, 'Fortnightly' => 0, 'Other' => 0];
    
    if (isset($transformedData['data'])) {
        foreach ($transformedData['data'] as $dateKey => $dateData) {
            if (isset($dateData['deliveries'])) {
                foreach ($dateData['deliveries'] as $delivery) {
                    $deliveryCount++;
                    $freq = $delivery['frequency'] ?? 'Unknown';
                    if (isset($deliveryFrequencies[$freq])) {
                        $deliveryFrequencies[$freq]++;
                    } else {
                        $deliveryFrequencies['Other']++;
                    }
                    
                    // Show the first few deliveries as examples
                    if ($deliveryCount <= 3) {
                        echo "<strong>Delivery Example #{$deliveryCount}:</strong><br>\n";
                        echo "&nbsp;&nbsp;ID: {$delivery['id']}<br>\n";
                        echo "&nbsp;&nbsp;Frequency: {$delivery['frequency']}<br>\n";
                        echo "&nbsp;&nbsp;Frequency Badge: {$delivery['frequency_badge']}<br>\n";
                        echo "&nbsp;&nbsp;Customer Week Type: {$delivery['customer_week_type']}<br>\n";
                        echo "&nbsp;&nbsp;Week Badge: {$delivery['week_badge']}<br>\n";
                        echo "&nbsp;&nbsp;Should Deliver This Week: " . ($delivery['should_deliver_this_week'] ? 'Yes' : 'No') . "<br><br>\n";
                    }
                }
            }
            
            if (isset($dateData['collections'])) {
                foreach ($dateData['collections'] as $collection) {
                    $collectionCount++;
                    $freq = $collection['frequency'] ?? 'Unknown';
                    if (isset($collectionFrequencies[$freq])) {
                        $collectionFrequencies[$freq]++;
                    } else {
                        $collectionFrequencies['Other']++;
                    }
                }
            }
        }
    }
    
    echo "<h3>Summary After Transformation</h3>\n";
    echo "<strong>Deliveries (total: {$deliveryCount}):</strong><br>\n";
    foreach ($deliveryFrequencies as $freq => $count) {
        echo "&nbsp;&nbsp;{$freq}: {$count}<br>\n";
    }
    
    echo "<br><strong>Collections (total: {$collectionCount}):</strong><br>\n";
    foreach ($collectionFrequencies as $freq => $count) {
        echo "&nbsp;&nbsp;{$freq}: {$count}<br>\n";
    }
    
    // Check deliveries by status to see if that's where the issue is
    if (isset($transformedData['deliveriesByStatus'])) {
        echo "<br><h3>Deliveries by Status</h3>\n";
        foreach ($transformedData['deliveriesByStatus'] as $status => $dates) {
            $statusCount = 0;
            foreach ($dates as $dateKey => $dateData) {
                if (isset($dateData['deliveries'])) {
                    $statusCount += count($dateData['deliveries']);
                }
            }
            echo "<strong>{$status}:</strong> {$statusCount} deliveries<br>\n";
        }
    }
    
} catch (Exception $e) {
    echo "<strong>ERROR:</strong> " . $e->getMessage() . "<br>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<br><br>TEST COMPLETE<br>";
