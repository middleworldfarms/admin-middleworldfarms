<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test the actual data structure being passed to the view
try {
    echo "=== Testing Delivery Controller Data Structure ===\n";
    
    $wpApi = app('App\Services\WpApiService');
    $rawData = $wpApi->getDeliveryScheduleData(100);
    
    echo "Raw data count: " . count($rawData) . "\n";
    
    if (!empty($rawData)) {
        echo "\nSample raw subscription data:\n";
        $sample = $rawData[0];
        echo "Customer ID: " . ($sample['customer_id'] ?? 'N/A') . "\n";
        echo "Billing Name: " . ($sample['billing']['first_name'] ?? '') . ' ' . ($sample['billing']['last_name'] ?? '') . "\n";
        echo "Billing Email: " . ($sample['billing']['email'] ?? 'N/A') . "\n";
        echo "Shipping Total: " . ($sample['shipping_total'] ?? 'N/A') . "\n";
        echo "Total: " . ($sample['total'] ?? 'N/A') . "\n";
        echo "Next Payment: " . ($sample['next_payment_date_gmt'] ?? 'N/A') . "\n";
        echo "Line Items Count: " . count($sample['line_items'] ?? []) . "\n";
        if (!empty($sample['line_items'])) {
            echo "First Product: " . ($sample['line_items'][0]['name'] ?? 'N/A') . "\n";
        }
    }
    
    // Test the transformation
    echo "\n=== Testing Transformation ===\n";
    $controller = new App\Http\Controllers\Admin\DeliveryController();
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('transformScheduleData');
    $method->setAccessible(true);
    $transformed = $method->invoke($controller, $rawData);
    
    echo "Transformation success: " . ($transformed['success'] ? 'true' : 'false') . "\n";
    echo "Total dates: " . count($transformed['data']) . "\n";
    
    if (!empty($transformed['data'])) {
        $firstDate = array_key_first($transformed['data']);
        $firstDateData = $transformed['data'][$firstDate];
        
        echo "\nFirst date: $firstDate\n";
        echo "Deliveries count: " . count($firstDateData['deliveries'] ?? []) . "\n";
        echo "Collections count: " . count($firstDateData['collections'] ?? []) . "\n";
        
        if (!empty($firstDateData['collections'])) {
            echo "\nSample collection data structure:\n";
            $sampleCollection = $firstDateData['collections'][0];
            foreach ($sampleCollection as $key => $value) {
                if (is_array($value)) {
                    echo "  $key: [array with " . count($value) . " items]\n";
                } else {
                    echo "  $key: " . (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) . "\n";
                }
            }
        }
        
        if (!empty($firstDateData['deliveries'])) {
            echo "\nSample delivery data structure:\n";
            $sampleDelivery = $firstDateData['deliveries'][0];
            foreach ($sampleDelivery as $key => $value) {
                if (is_array($value)) {
                    echo "  $key: [array with " . count($value) . " items]\n";
                } else {
                    echo "  $key: " . (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) . "\n";
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
