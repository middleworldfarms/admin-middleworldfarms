<?php
// Test the actual duplicate prevention after cache clear
require_once 'vendor/autoload.php';

use App\Http\Controllers\Admin\DeliveryController;
use App\Services\DirectDatabaseService;

echo "=== TESTING DUPLICATE PREVENTION AFTER CACHE CLEAR ===\n\n";

try {
    $directDb = new \App\Services\DirectDatabaseService();
    $controller = new \App\Http\Controllers\Admin\DeliveryController();
    
    // Get raw data
    $rawData = $directDb->getDeliveryScheduleData(50);
    
    echo "Raw data counts:\n";
    echo "- Deliveries: " . $rawData['deliveries']->count() . "\n";
    echo "- Collections: " . $rawData['collections']->count() . "\n\n";
    
    // Use reflection to access the private method
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('transformScheduleData');
    $method->setAccessible(true);
    
    // Transform the data (this applies duplicate prevention)
    $transformedData = $method->invoke($controller, $rawData);
    
    echo "Transformed data:\n";
    echo "Message: " . $transformedData['message'] . "\n\n";
    
    // Count final items
    $totalDeliveries = 0;
    $totalCollections = 0;
    $customerEmails = [];
    
    foreach ($transformedData['data'] as $date => $dateData) {
        $totalDeliveries += count($dateData['deliveries'] ?? []);
        $totalCollections += count($dateData['collections'] ?? []);
        
        foreach ($dateData['deliveries'] ?? [] as $delivery) {
            $customerEmails[] = $delivery['customer_email'];
        }
        
        foreach ($dateData['collections'] ?? [] as $collection) {
            $customerEmails[] = $collection['customer_email'];
        }
    }
    
    $uniqueEmails = array_unique($customerEmails);
    
    echo "Final counts after duplicate prevention:\n";
    echo "- Total deliveries: $totalDeliveries\n";
    echo "- Total collections: $totalCollections\n";
    echo "- Total items: " . ($totalDeliveries + $totalCollections) . "\n";
    echo "- Unique customer emails: " . count($uniqueEmails) . "\n";
    echo "- Duplicate check: " . (count($customerEmails) === count($uniqueEmails) ? "✅ No duplicates!" : "❌ Duplicates found!") . "\n\n";
    
    // Check for Ben Anderson specifically
    $benEmails = array_filter($customerEmails, function($email) {
        return stripos($email, 'anderson.ben') !== false;
    });
    
    echo "Ben Anderson check:\n";
    echo "- Occurrences: " . count($benEmails) . "\n";
    if (count($benEmails) > 0) {
        echo "- Email: " . $benEmails[array_key_first($benEmails)] . "\n";
        echo "- Status: " . (count($benEmails) === 1 ? "✅ No duplicates!" : "❌ Still duplicated!") . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
