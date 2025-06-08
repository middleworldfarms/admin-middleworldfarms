<?php
require_once 'vendor/autoload.php';

use App\Services\DeliveryScheduleService;

$service = new DeliveryScheduleService();
$data = $service->getEnhancedSchedule(); // Correct method name

echo "=== TESTING CUSTOMER DATA ===\n";

if (isset($data['data'])) {
    foreach ($data['data'] as $date => $dateData) {
        if (!empty($dateData['deliveries'])) {
            echo "Date: $date\n";
            foreach (array_slice($dateData['deliveries'], 0, 3) as $i => $delivery) {
                echo "  Delivery #" . ($i + 1) . ":\n";
                echo "    Customer ID: " . ($delivery['customer_id'] ?? 'N/A') . "\n";
                echo "    Customer Name: " . ($delivery['name'] ?? 'N/A') . "\n";
                echo "    Phone: " . ($delivery['phone'] ?? 'N/A') . "\n";
                echo "    Email: " . ($delivery['email'] ?? 'N/A') . "\n";
                echo "    ---\n";
            }
            break; // Only check first date with deliveries
        }
        
        if (!empty($dateData['collections'])) {
            echo "Collections for $date:\n";
            foreach (array_slice($dateData['collections'], 0, 3) as $i => $collection) {
                echo "  Collection #" . ($i + 1) . ":\n";
                echo "    Customer ID: " . ($collection['customer_id'] ?? 'N/A') . "\n";
                echo "    Customer Name: " . ($collection['customer_name'] ?? $collection['name'] ?? 'N/A') . "\n";
                echo "    Phone: " . ($collection['phone'] ?? 'N/A') . "\n";
                echo "    Email: " . ($collection['email'] ?? 'N/A') . "\n";
                echo "    ---\n";
            }
            break; // Only check first date with collections
        }
    }
} else {
    echo "No delivery data found\n";
    echo "Raw data structure:\n";
    print_r(array_keys($data));
}

echo "\n=== END TEST ===\n";
?>
