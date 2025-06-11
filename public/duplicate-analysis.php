<?php
// Duplicate Debug - Proper Laravel Bootstrap
echo "<h2>🔍 Duplicate Investigation (Fixed)</h2>";

try {
    // Correct Laravel bootstrap for web
    require_once __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    
    // Boot the application properly for web context
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::createFromGlobals();
    $kernel->bootstrap();
    
    // Test that Laravel is properly loaded
    $appName = config('app.name', 'Laravel');
    
    echo "<p><strong>Laravel Status:</strong> ✅ Bootstrapped</p>";
    echo "<p><strong>App Name:</strong> " . $appName . "</p>";
    echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
    
    $directDb = new \App\Services\DirectDatabaseService();
    
    echo "<h3>📊 Connection Test</h3>";
    $connection = $directDb->testConnection();
    echo "<p>Database Connection: " . ($connection['success'] ? '✅ SUCCESS' : '❌ FAILED') . "</p>";
    
    if (!$connection['success']) {
        throw new Exception('Database connection failed: ' . $connection['message']);
    }
    
    echo "<h3>📋 Raw Data Collection</h3>";
    $rawData = $directDb->getDeliveryScheduleData(100);
    
    echo "<ul>";
    echo "<li>Total Deliveries Retrieved: " . $rawData['deliveries']->count() . "</li>";
    echo "<li>Total Collections Retrieved: " . $rawData['collections']->count() . "</li>";
    echo "</ul>";
    
    // Analyze duplicates in deliveries
    echo "<h3>🚚 Delivery Analysis</h3>";
    $deliveryEmails = [];
    $deliveryDuplicates = [];
    
    foreach ($rawData['deliveries'] as $delivery) {
        $email = strtolower(trim($delivery['customer_email']));
        if (!isset($deliveryEmails[$email])) {
            $deliveryEmails[$email] = [];
        }
        $deliveryEmails[$email][] = [
            'id' => $delivery['id'],
            'name' => $delivery['customer_name'],
            'status' => $delivery['status'],
            'type' => $delivery['type'] ?? 'order'
        ];
    }
    
    $multipleDeliveries = array_filter($deliveryEmails, function($items) {
        return count($items) > 1;
    });
    
    echo "<p>Emails with multiple deliveries: <strong>" . count($multipleDeliveries) . "</strong></p>";
    
    if (count($multipleDeliveries) > 0) {
        echo "<h4>🔄 Multiple Deliveries (Same Customer):</h4>";
        foreach (array_slice($multipleDeliveries, 0, 5, true) as $email => $items) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px;'>";
            echo "<strong>📧 $email</strong> - " . count($items) . " deliveries<br>";
            foreach ($items as $item) {
                echo "→ ID: {$item['id']}, Name: {$item['name']}, Status: {$item['status']}, Type: {$item['type']}<br>";
            }
            echo "</div>";
        }
    }
    
    // Analyze duplicates in collections
    echo "<h3>📦 Collection Analysis</h3>";
    $collectionEmails = [];
    
    foreach ($rawData['collections'] as $collection) {
        $email = strtolower(trim($collection['customer_email']));
        if (!isset($collectionEmails[$email])) {
            $collectionEmails[$email] = [];
        }
        $collectionEmails[$email][] = [
            'id' => $collection['id'],
            'name' => $collection['customer_name'],
            'status' => $collection['status'],
            'type' => $collection['type'] ?? 'subscription'
        ];
    }
    
    $multipleCollections = array_filter($collectionEmails, function($items) {
        return count($items) > 1;
    });
    
    echo "<p>Emails with multiple collections: <strong>" . count($multipleCollections) . "</strong></p>";
    
    if (count($multipleCollections) > 0) {
        echo "<h4>🔄 Multiple Collections (Same Customer):</h4>";
        foreach (array_slice($multipleCollections, 0, 5, true) as $email => $items) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px;'>";
            echo "<strong>📧 $email</strong> - " . count($items) . " collections<br>";
            foreach ($items as $item) {
                echo "→ ID: {$item['id']}, Name: {$item['name']}, Status: {$item['status']}, Type: {$item['type']}<br>";
            }
            echo "</div>";
        }
    }
    
    // Cross-check (same email in both deliveries and collections)
    echo "<h3>🔄 Cross-Type Duplicates</h3>";
    $crossDuplicates = array_intersect_key($deliveryEmails, $collectionEmails);
    echo "<p>Customers in BOTH deliveries and collections: <strong>" . count($crossDuplicates) . "</strong></p>";
    
    if (count($crossDuplicates) > 0) {
        echo "<h4>⚠️ Cross-Type Duplicates (Delivery AND Collection):</h4>";
        foreach (array_slice($crossDuplicates, 0, 5, true) as $email => $deliveries) {
            echo "<div style='border: 1px solid #f00; padding: 10px; margin: 5px; background: #ffe6e6;'>";
            echo "<strong>📧 $email</strong><br>";
            echo "📤 Deliveries: " . count($deliveries) . "<br>";
            echo "📥 Collections: " . count($collectionEmails[$email]) . "<br>";
            echo "<em>This customer appears in both lists!</em>";
            echo "</div>";
        }
    }
    
    // Check Ben Anderson specifically
    $benEmails = ['anderson.ben0405@gmail.com'];
    foreach ($benEmails as $benEmail) {
        $benEmail = strtolower(trim($benEmail));
        echo "<h3>🔍 Ben Anderson Specific Check</h3>";
        echo "<p>Email: $benEmail</p>";
        
        $benInDeliveries = isset($deliveryEmails[$benEmail]) ? count($deliveryEmails[$benEmail]) : 0;
        $benInCollections = isset($collectionEmails[$benEmail]) ? count($collectionEmails[$benEmail]) : 0;
        
        echo "<ul>";
        echo "<li>In Deliveries: $benInDeliveries</li>";
        echo "<li>In Collections: $benInCollections</li>";
        echo "<li>Total Appearances: " . ($benInDeliveries + $benInCollections) . "</li>";
        echo "</ul>";
        
        if ($benInDeliveries > 0) {
            echo "<p><strong>Ben's Deliveries:</strong></p>";
            foreach ($deliveryEmails[$benEmail] as $delivery) {
                echo "→ ID: {$delivery['id']}, Status: {$delivery['status']}<br>";
            }
        }
        
        if ($benInCollections > 0) {
            echo "<p><strong>Ben's Collections:</strong></p>";
            foreach ($collectionEmails[$benEmail] as $collection) {
                echo "→ ID: {$collection['id']}, Status: {$collection['status']}<br>";
            }
        }
    }
    
    echo "<h3>🎯 Summary</h3>";
    echo "<ul>";
    echo "<li>Total unique delivery emails: " . count($deliveryEmails) . "</li>";
    echo "<li>Total unique collection emails: " . count($collectionEmails) . "</li>";
    echo "<li>Customers with multiple deliveries: " . count($multipleDeliveries) . "</li>";
    echo "<li>Customers with multiple collections: " . count($multipleCollections) . "</li>";
    echo "<li>Customers in both categories: " . count($crossDuplicates) . "</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<div style='color: red; border: 1px solid red; padding: 10px;'>";
    echo "<h3>❌ Error Occurred</h3>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
    echo "</div>";
}
