<?php
require_once 'vendor/autoload.php';

use App\Services\DirectDatabaseService;
use App\Http\Controllers\Admin\DeliveryController;

echo "=== DEBUGGING DUPLICATE ISSUE STEP BY STEP ===\n\n";

try {
    $directDb = new DirectDatabaseService();
    
    echo "1. Getting raw data from DirectDatabaseService:\n";
    $rawData = $directDb->getDeliveryScheduleData(50);
    
    echo "Raw counts:\n";
    echo "- Deliveries: " . $rawData['deliveries']->count() . "\n";
    echo "- Collections: " . $rawData['collections']->count() . "\n\n";
    
    echo "2. Looking for duplicates in raw data:\n";
    
    // Check for email duplicates in deliveries
    $deliveryEmails = [];
    $duplicateDeliveries = [];
    foreach ($rawData['deliveries'] as $delivery) {
        $email = strtolower(trim($delivery['customer_email']));
        if (isset($deliveryEmails[$email])) {
            $duplicateDeliveries[] = $email;
        } else {
            $deliveryEmails[$email] = true;
        }
    }
    
    // Check for email duplicates in collections
    $collectionEmails = [];
    $duplicateCollections = [];
    foreach ($rawData['collections'] as $collection) {
        $email = strtolower(trim($collection['customer_email']));
        if (isset($collectionEmails[$email])) {
            $duplicateCollections[] = $email;
        } else {
            $collectionEmails[$email] = true;
        }
    }
    
    // Check for cross-duplicates (same email in both deliveries and collections)
    $crossDuplicates = array_intersect(array_keys($deliveryEmails), array_keys($collectionEmails));
    
    echo "Duplicate analysis:\n";
    echo "- Duplicate emails within deliveries: " . count($duplicateDeliveries) . "\n";
    echo "- Duplicate emails within collections: " . count($duplicateCollections) . "\n";
    echo "- Emails appearing in BOTH deliveries AND collections: " . count($crossDuplicates) . "\n\n";
    
    if (count($crossDuplicates) > 0) {
        echo "Cross-duplicate emails (appearing in both):\n";
        foreach (array_slice($crossDuplicates, 0, 5) as $email) {
            echo "  - $email\n";
            
            // Find the actual records
            $deliveryRecord = $rawData['deliveries']->first(function($d) use ($email) {
                return strtolower(trim($d['customer_email'])) === $email;
            });
            
            $collectionRecord = $rawData['collections']->first(function($c) use ($email) {
                return strtolower(trim($c['customer_email'])) === $email;
            });
            
            if ($deliveryRecord) {
                echo "    Delivery: ID {$deliveryRecord['id']}, Type: {$deliveryRecord['type']}, Status: {$deliveryRecord['status']}\n";
            }
            if ($collectionRecord) {
                echo "    Collection: ID {$collectionRecord['id']}, Type: {$collectionRecord['type']}, Status: {$collectionRecord['status']}\n";
            }
        }
        echo "\n";
    }
    
    echo "3. Testing the transformation logic:\n";
    
    // Use reflection to test the private method
    $controller = new DeliveryController();
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('transformScheduleData');
    $method->setAccessible(true);
    
    $transformedData = $method->invoke($controller, $rawData);
    
    echo "Transformation result message: " . $transformedData['message'] . "\n\n";
    
    // Count final results
    $finalDeliveryCount = 0;
    $finalCollectionCount = 0;
    $finalEmails = [];
    
    foreach ($transformedData['data'] as $date => $dateData) {
        foreach ($dateData['deliveries'] ?? [] as $delivery) {
            $finalDeliveryCount++;
            $finalEmails[] = strtolower(trim($delivery['customer_email']));
        }
        foreach ($dateData['collections'] ?? [] as $collection) {
            $finalCollectionCount++;
            $finalEmails[] = strtolower(trim($collection['customer_email']));
        }
    }
    
    $uniqueFinalEmails = array_unique($finalEmails);
    
    echo "Final results after transformation:\n";
    echo "- Final deliveries: $finalDeliveryCount\n";
    echo "- Final collections: $finalCollectionCount\n";
    echo "- Total final items: " . count($finalEmails) . "\n";
    echo "- Unique emails in final: " . count($uniqueFinalEmails) . "\n";
    echo "- Duplicates remaining: " . (count($finalEmails) - count($uniqueFinalEmails)) . "\n\n";
    
    if (count($finalEmails) !== count($uniqueFinalEmails)) {
        echo "❌ DUPLICATES STILL EXIST!\n";
        
        // Find which emails are still duplicated
        $emailCounts = array_count_values($finalEmails);
        $stillDuplicated = array_filter($emailCounts, function($count) { return $count > 1; });
        
        echo "Still duplicated emails:\n";
        foreach ($stillDuplicated as $email => $count) {
            echo "  - $email: appears $count times\n";
        }
    } else {
        echo "✅ NO DUPLICATES! Duplicate prevention working.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
