<?php
// Test Delivery Status Grouping
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::createFromGlobals();
$kernel->bootstrap();

echo "<h2>🧪 Testing Delivery Status Subtabs</h2>";

try {
    $controller = new \App\Http\Controllers\Admin\DeliveryController();
    $directDb = new \App\Services\DirectDatabaseService();
    
    // Get raw data
    $rawData = $directDb->getDeliveryScheduleData(100);
    echo "<p>📊 Raw Deliveries: " . $rawData['deliveries']->count() . "</p>";
    
    // Use reflection to access the private method
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('transformScheduleData');
    $method->setAccessible(true);
    
    // Call the transformation method
    $transformedData = $method->invoke($controller, $rawData);
    
    echo "<h3>🚚 Delivery Status Breakdown</h3>";
    
    if (isset($transformedData['deliveriesByStatus'])) {
        foreach ($transformedData['deliveriesByStatus'] as $status => $statusData) {
            $count = 0;
            foreach ($statusData as $dateData) {
                $count += count($dateData['deliveries'] ?? []);
            }
            
            if ($count > 0) {
                $icon = match($status) {
                    'processing' => '⚡',
                    'pending' => '⏳',
                    'completed' => '✅',
                    'on-hold' => '⏸️',
                    'cancelled' => '❌',
                    'refunded' => '💰',
                    default => '📋'
                };
                
                echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px; background: #f9f9f9;'>";
                echo "<strong>$icon $status:</strong> $count deliveries<br>";
                
                // Show first few customers for this status
                $shown = 0;
                foreach ($statusData as $dateData) {
                    foreach ($dateData['deliveries'] as $delivery) {
                        if ($shown < 3) {
                            echo "→ " . $delivery['customer_name'] . " (" . $delivery['customer_email'] . ")<br>";
                            $shown++;
                        }
                    }
                }
                
                if ($count > 3) {
                    echo "→ ... and " . ($count - 3) . " more<br>";
                }
                
                echo "</div>";
            }
        }
    } else {
        echo "<p>❌ No deliveriesByStatus data found</p>";
    }
    
    // Test delivery status examples
    echo "<h3>🔍 Delivery Status Examples</h3>";
    
    $statusExamples = [];
    foreach ($rawData['deliveries'] as $delivery) {
        $status = isset($delivery['status']) ? strtolower(str_replace('wc-', '', $delivery['status'])) : 'processing';
        if (!isset($statusExamples[$status])) {
            $statusExamples[$status] = [];
        }
        if (count($statusExamples[$status]) < 2) {
            $statusExamples[$status][] = [
                'name' => $delivery['customer_name'],
                'email' => $delivery['customer_email'],
                'id' => $delivery['id'],
                'raw_status' => $delivery['status'] ?? 'N/A'
            ];
        }
    }
    
    foreach ($statusExamples as $status => $examples) {
        echo "<p><strong>$status Status Examples:</strong></p>";
        foreach ($examples as $example) {
            echo "• {$example['name']} ({$example['email']}) - ID: {$example['id']} - Raw: {$example['raw_status']}<br>";
        }
    }
    
} catch (Exception $e) {
    echo "<p><strong>❌ Error:</strong> " . $e->getMessage() . "</p>";
}
?>
