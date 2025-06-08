<?php
// Simple test to check what's happening
echo "=== TESTING LARAVEL ENVIRONMENT ===\n";

// Check if we can load Laravel
try {
    require_once 'vendor/autoload.php';
    echo "✓ Autoload successful\n";
    
    // Check if we can create the Laravel app
    $app = require_once 'bootstrap/app.php';
    echo "✓ Laravel app created\n";
    
    // Check if we can load the service
    $service = new App\Services\DeliveryScheduleService();
    echo "✓ DeliveryScheduleService loaded\n";
    
    // Try to get data
    echo "Attempting to get delivery schedule...\n";
    $data = $service->getDeliverySchedule();
    echo "✓ Data retrieved\n";
    
    if (is_array($data)) {
        echo "Data is array with keys: " . implode(', ', array_keys($data)) . "\n";
        
        if (isset($data['data'])) {
            echo "Data array contains 'data' key with " . count($data['data']) . " entries\n";
            
            // Show first few entries
            $count = 0;
            foreach ($data['data'] as $date => $dateData) {
                if ($count >= 2) break;
                
                echo "Date: $date\n";
                echo "  Deliveries: " . count($dateData['deliveries'] ?? []) . "\n";
                echo "  Collections: " . count($dateData['collections'] ?? []) . "\n";
                
                // Show first delivery if exists
                if (!empty($dateData['deliveries'])) {
                    $first = $dateData['deliveries'][0];
                    echo "  First delivery customer: " . ($first['name'] ?? 'N/A') . " (ID: " . ($first['customer_id'] ?? 'N/A') . ")\n";
                }
                
                $count++;
            }
        }
    } else {
        echo "Data is not an array: " . gettype($data) . "\n";
        var_dump($data);
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== END TEST ===\n";
?>
