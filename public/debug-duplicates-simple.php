<?php
echo "=== SIMPLE DUPLICATE TEST ===\n";

// Create web-accessible test
echo "<h2>Duplicate Investigation</h2>";

try {
    require_once '../vendor/autoload.php';
    $app = require_once '../bootstrap/app.php';
    
    $directDb = new \App\Services\DirectDatabaseService();
    $rawData = $directDb->getDeliveryScheduleData(50);
    
    echo "<h3>Raw Data Analysis</h3>";
    echo "<p>Deliveries: " . $rawData['deliveries']->count() . "</p>";
    echo "<p>Collections: " . $rawData['collections']->count() . "</p>";
    
    // Check for email frequency in deliveries
    $deliveryEmails = [];
    foreach ($rawData['deliveries'] as $delivery) {
        $email = strtolower(trim($delivery['customer_email']));
        if (!isset($deliveryEmails[$email])) {
            $deliveryEmails[$email] = [];
        }
        $deliveryEmails[$email][] = [
            'id' => $delivery['id'],
            'name' => $delivery['customer_name'],
            'status' => $delivery['status'],
            'date' => $delivery['date_created']
        ];
    }
    
    // Check for email frequency in collections
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
            'date' => $collection['date_created']
        ];
    }
    
    // Find duplicates within deliveries
    $duplicateDeliveries = array_filter($deliveryEmails, function($orders) {
        return count($orders) > 1;
    });
    
    // Find duplicates within collections
    $duplicateCollections = array_filter($collectionEmails, function($subscriptions) {
        return count($subscriptions) > 1;
    });
    
    // Find cross-duplicates (same email in both)
    $crossDuplicates = array_intersect_key($deliveryEmails, $collectionEmails);
    
    echo "<h3>Duplicate Analysis</h3>";
    echo "<p><strong>Emails with multiple deliveries:</strong> " . count($duplicateDeliveries) . "</p>";
    echo "<p><strong>Emails with multiple collections:</strong> " . count($duplicateCollections) . "</p>";
    echo "<p><strong>Emails in BOTH deliveries and collections:</strong> " . count($crossDuplicates) . "</p>";
    
    if (count($duplicateDeliveries) > 0) {
        echo "<h4>Multiple Deliveries (Same Email):</h4>";
        foreach (array_slice($duplicateDeliveries, 0, 3, true) as $email => $orders) {
            echo "<p><strong>$email</strong> has " . count($orders) . " deliveries:</p><ul>";
            foreach ($orders as $order) {
                echo "<li>ID: {$order['id']}, Status: {$order['status']}, Date: {$order['date']}</li>";
            }
            echo "</ul>";
        }
    }
    
    if (count($duplicateCollections) > 0) {
        echo "<h4>Multiple Collections (Same Email):</h4>";
        foreach (array_slice($duplicateCollections, 0, 3, true) as $email => $subscriptions) {
            echo "<p><strong>$email</strong> has " . count($subscriptions) . " collections:</p><ul>";
            foreach ($subscriptions as $sub) {
                echo "<li>ID: {$sub['id']}, Status: {$sub['status']}, Date: {$sub['date']}</li>";
            }
            echo "</ul>";
        }
    }
    
    if (count($crossDuplicates) > 0) {
        echo "<h4>Cross-Duplicates (Both Delivery AND Collection):</h4>";
        foreach (array_slice($crossDuplicates, 0, 3, true) as $email => $deliveries) {
            echo "<p><strong>$email</strong>:</p><ul>";
            echo "<li>Deliveries: " . count($deliveries) . "</li>";
            echo "<li>Collections: " . count($collectionEmails[$email]) . "</li>";
            echo "</ul>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
