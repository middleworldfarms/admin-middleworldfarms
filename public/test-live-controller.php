<?php
// Test the ACTUAL controller duplicate prevention
echo "<h2>🔧 Testing Live Controller Duplicate Prevention</h2>";

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->bootstrap();
    
    echo "<p>✅ Laravel loaded</p>";
    
    // Test the actual controller
    $controller = new \App\Http\Controllers\Admin\DeliveryController();
    $directDb = new \App\Services\DirectDatabaseService();
    
    echo "<h3>📊 Raw Data Analysis</h3>";
    $rawData = $directDb->getDeliveryScheduleData(100);
    
    echo "<ul>";
    echo "<li>Raw Deliveries: " . $rawData['deliveries']->count() . "</li>";
    echo "<li>Raw Collections: " . $rawData['collections']->count() . "</li>";
    echo "<li>Raw Total: " . ($rawData['deliveries']->count() + $rawData['collections']->count()) . "</li>";
    echo "</ul>";
    
    // Use reflection to call the private transformScheduleData method
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('transformScheduleData');
    $method->setAccessible(true);
    
    echo "<h3>🔧 Controller Transformation</h3>";
    $scheduleData = $method->invoke($controller, $rawData);
    
    // Count actual items in transformed data
    $actualDeliveries = 0;
    $actualCollections = 0;
    
    foreach ($scheduleData['data'] as $dateData) {
        $actualDeliveries += count($dateData['deliveries'] ?? []);
        $actualCollections += count($dateData['collections'] ?? []);
    }
    
    echo "<ul>";
    echo "<li>Final Deliveries: " . $actualDeliveries . "</li>";
    echo "<li>Final Collections: " . $actualCollections . "</li>";
    echo "<li>Final Total: " . ($actualDeliveries + $actualCollections) . "</li>";
    echo "</ul>";
    
    $duplicatesRemoved = ($rawData['deliveries']->count() + $rawData['collections']->count()) - ($actualDeliveries + $actualCollections);
    
    echo "<h3>📈 Results</h3>";
    echo "<div style='border: 2px solid " . ($duplicatesRemoved > 0 ? '#28a745' : '#dc3545') . "; padding: 15px; background: " . ($duplicatesRemoved > 0 ? '#d4edda' : '#f8d7da') . ";'>";
    
    if ($duplicatesRemoved > 0) {
        echo "<h4>✅ SUCCESS - Duplicates Removed!</h4>";
        echo "<p><strong>Duplicates removed: $duplicatesRemoved</strong></p>";
        echo "<p>The controller is working correctly!</p>";
    } else {
        echo "<h4>❌ NO CHANGE - Something's wrong</h4>";
        echo "<p>No duplicates were removed by the controller.</p>";
    }
    
    echo "</div>";
    
    // Check message from controller
    if (isset($scheduleData['message'])) {
        echo "<p><strong>Controller Message:</strong> " . $scheduleData['message'] . "</p>";
    }
    
    echo "<h3>👤 Ben Anderson Check</h3>";
    $benInFinalDeliveries = 0;
    $benInFinalCollections = 0;
    
    foreach ($scheduleData['data'] as $dateData) {
        foreach ($dateData['deliveries'] ?? [] as $delivery) {
            if (stripos($delivery['customer_email'], 'anderson.ben0405') !== false) {
                $benInFinalDeliveries++;
            }
        }
        
        foreach ($dateData['collections'] ?? [] as $collection) {
            if (stripos($collection['customer_email'], 'anderson.ben0405') !== false) {
                $benInFinalCollections++;
            }
        }
    }
    
    echo "<ul>";
    echo "<li>Ben in final deliveries: " . $benInFinalDeliveries . "</li>";
    echo "<li>Ben in final collections: " . $benInFinalCollections . "</li>";
    echo "<li>Ben total appearances: " . ($benInFinalDeliveries + $benInFinalCollections) . "</li>";
    echo "</ul>";
    
    if ($benInFinalDeliveries > 0 && $benInFinalCollections > 0) {
        echo "<p style='color: red;'><strong>❌ Ben still appears in both sections!</strong></p>";
    } else {
        echo "<p style='color: green;'><strong>✅ Ben appears in only one section (correct)</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>❌ Error:</strong> " . $e->getMessage() . "</p>";
}
?>
