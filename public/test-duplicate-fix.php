<?php
// Test Duplicate Fix - Simulate Controller Logic
echo "<h2>🧪 Testing Duplicate Prevention Fix</h2>";

try {
    // Bootstrap Laravel
    require_once __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::createFromGlobals();
    $kernel->bootstrap();
    
    echo "<p><strong>Laravel Status:</strong> ✅ Bootstrapped</p>";
    
    // Use the actual controller method
    $controller = new \App\Http\Controllers\Admin\DeliveryController();
    $directDb = new \App\Services\DirectDatabaseService();
    
    echo "<h3>📊 Getting Raw Data</h3>";
    $rawData = $directDb->getDeliveryScheduleData(100);
    
    echo "<ul>";
    echo "<li>Raw Deliveries: " . $rawData['deliveries']->count() . "</li>";
    echo "<li>Raw Collections: " . $rawData['collections']->count() . "</li>";
    echo "</ul>";
    
    echo "<h3>🔧 Applying Controller Logic (Duplicate Prevention)</h3>";
    
    // Use reflection to access the private method
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('transformScheduleData');
    $method->setAccessible(true);
    
    // Call the actual transformation method
    $transformedData = $method->invoke($controller, $rawData);
    
    echo "<ul>";
    echo "<li>Final Deliveries: " . count($transformedData['deliveries']) . "</li>";
    echo "<li>Final Collections: " . count($transformedData['collections']) . "</li>";
    echo "</ul>";
    
    echo "<h3>🔍 Cross-Type Duplicate Check</h3>";
    
    // Check for cross-type duplicates in final data
    $deliveryEmails = [];
    $collectionEmails = [];
    $crossTypeDuplicates = [];
    
    foreach ($transformedData['deliveries'] as $delivery) {
        $email = strtolower(trim($delivery['customer_email']));
        $deliveryEmails[$email] = true;
    }
    
    foreach ($transformedData['collections'] as $collection) {
        $email = strtolower(trim($collection['customer_email']));
        $collectionEmails[$email] = true;
        
        if (isset($deliveryEmails[$email])) {
            $crossTypeDuplicates[] = $email;
        }
    }
    
    echo "<p><strong>Cross-type duplicates after transformation:</strong> " . count($crossTypeDuplicates) . "</p>";
    
    if (count($crossTypeDuplicates) > 0) {
        echo "<h4>❌ Still Have Duplicates:</h4>";
        foreach ($crossTypeDuplicates as $email) {
            echo "<div style='border: 1px solid #f00; padding: 5px; margin: 2px;'>📧 $email</div>";
        }
    } else {
        echo "<h4>✅ No Cross-Type Duplicates!</h4>";
        echo "<p>The duplicate prevention logic is working correctly.</p>";
    }
    
    echo "<h3>🎯 Ben Anderson Specific Test</h3>";
    $benInDeliveries = false;
    $benInCollections = false;
    
    foreach ($transformedData['deliveries'] as $delivery) {
        if (stripos($delivery['customer_email'], 'anderson.ben0405') !== false) {
            $benInDeliveries = true;
            break;
        }
    }
    
    foreach ($transformedData['collections'] as $collection) {
        if (stripos($collection['customer_email'], 'anderson.ben0405') !== false) {
            $benInCollections = true;
            break;
        }
    }
    
    echo "<ul>";
    echo "<li>Ben in Deliveries: " . ($benInDeliveries ? '✅ YES' : '❌ NO') . "</li>";
    echo "<li>Ben in Collections: " . ($benInCollections ? '✅ YES' : '❌ NO') . "</li>";
    echo "</ul>";
    
    if ($benInDeliveries && $benInCollections) {
        echo "<p><strong>❌ Ben is still appearing in both sections!</strong></p>";
    } else {
        echo "<p><strong>✅ Ben appears in only one section (correct behavior)</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p><strong>❌ Error:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
