<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\DeliveryScheduleService;

echo "=== MWF Delivery Schedule API Debug ===\n\n";

$service = new DeliveryScheduleService();

echo "1. Testing Connection...\n";
$connectionTest = $service->testConnection();
print_r($connectionTest);

echo "\n2. Testing Authentication...\n";
$authTest = $service->testAuth();
print_r($authTest);

echo "\n3. Fetching Schedule Data...\n";
$scheduleData = $service->getSchedule();

if ($scheduleData) {
    echo "Raw API Response:\n";
    echo json_encode($scheduleData, JSON_PRETTY_PRINT);
    
    echo "\n\n=== Data Structure Analysis ===\n";
    
    if (isset($scheduleData['success'])) {
        echo "Has 'success' field: " . ($scheduleData['success'] ? 'true' : 'false') . "\n";
    }
    
    if (isset($scheduleData['data'])) {
        echo "Has 'data' field: yes\n";
        echo "Data type: " . gettype($scheduleData['data']) . "\n";
        
        if (is_array($scheduleData['data'])) {
            echo "Data count: " . count($scheduleData['data']) . "\n";
            
            if (count($scheduleData['data']) > 0) {
                echo "\nFirst item structure:\n";
                $firstItem = $scheduleData['data'][0];
                echo "Available fields: " . implode(', ', array_keys($firstItem)) . "\n";
                print_r($firstItem);
            }
        }
    } else {
        echo "No 'data' field found\n";
        echo "Top-level fields: " . implode(', ', array_keys($scheduleData)) . "\n";
    }
} else {
    echo "No data returned from API\n";
}

echo "\n=== Debug Complete ===\n";
