<?php
// Test Collection Status Grouping
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::createFromGlobals();
$kernel->bootstrap();

echo "<h2>🧪 Testing Collection Status Subtabs</h2>";

try {
    $controller = new \App\Http\Controllers\Admin\DeliveryController();
    $directDb = new \App\Services\DirectDatabaseService();
    
    // Get raw data
    $rawData = $directDb->getDeliveryScheduleData(100);
    echo "<p>📊 Raw Collections: " . $rawData['collections']->count() . "</p>";
    
    // Use reflection to access the private method
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('transformScheduleData');
    $method->setAccessible(true);
    
    // Call the transformation method
    $transformedData = $method->invoke($controller, $rawData);
    
    echo "<h3>📦 Collection Status Breakdown</h3>";
    
    if (isset($transformedData['collectionsByStatus'])) {
        foreach ($transformedData['collectionsByStatus'] as $status => $statusData) {
            $count = 0;
            foreach ($statusData as $dateData) {
                $count += count($dateData['collections'] ?? []);
            }
            
            if ($count > 0) {
                $icon = match($status) {
                    'active' => '✅',
                    'on-hold' => '⏸️',
                    'cancelled' => '❌',
                    'pending' => '⏳',
                    default => '📋'
                };
                
                echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px; background: #f9f9f9;'>";
                echo "<strong>$icon $status:</strong> $count collections<br>";
                
                // Show first few customers for this status
                $shown = 0;
                foreach ($statusData as $dateData) {
                    foreach ($dateData['collections'] as $collection) {
                        if ($shown < 3) {
                            echo "→ " . $collection['customer_name'] . " (" . $collection['customer_email'] . ")<br>";
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
        echo "<p>❌ No collectionsByStatus data found</p>";
    }
    
    // Test specific customers
    echo "<h3>🔍 Specific Status Examples</h3>";
    
    $statusExamples = [];
    foreach ($rawData['collections'] as $collection) {
        $status = strtolower($collection['status']);
        if (!isset($statusExamples[$status])) {
            $statusExamples[$status] = [];
        }
        if (count($statusExamples[$status]) < 2) {
            $statusExamples[$status][] = [
                'name' => $collection['customer_name'],
                'email' => $collection['customer_email'],
                'id' => $collection['id']
            ];
        }
    }
    
    foreach ($statusExamples as $status => $examples) {
        echo "<p><strong>$status Status Examples:</strong></p>";
        foreach ($examples as $example) {
            echo "• {$example['name']} ({$example['email']}) - ID: {$example['id']}<br>";
        }
    }
    
} catch (Exception $e) {
    echo "<p><strong>❌ Error:</strong> " . $e->getMessage() . "</p>";
}
?>
