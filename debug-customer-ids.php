<?php
// Debug script to check customer IDs being returned from API
require_once 'bootstrap/app.php';

use App\Services\DeliveryScheduleService;

$app = require_once 'bootstrap/app.php';

try {
    $service = new DeliveryScheduleService();
    $data = $service->getEnhancedSchedule();
    
    echo "<h2>Customer ID Debug</h2>";
    echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
    
    if (isset($data['data'])) {
        $customersSeen = [];
        $customerCount = 0;
        
        foreach ($data['data'] as $date => $dateData) {
            if (!empty($dateData['deliveries'])) {
                echo "<h3>Deliveries for $date:</h3>";
                echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
                echo "<tr><th>Name</th><th>Customer ID</th><th>Phone</th><th>Email</th></tr>";
                
                foreach ($dateData['deliveries'] as $delivery) {
                    $customerId = $delivery['customer_id'] ?? 'N/A';
                    $customerName = $delivery['name'] ?? 'N/A';
                    $phone = $delivery['phone'] ?? 'N/A';
                    $email = $delivery['email'] ?? 'N/A';
                    
                    echo "<tr>";
                    echo "<td>{$customerName}</td>";
                    echo "<td style='font-weight: bold; color: " . ($customerId === 'N/A' ? 'red' : 'blue') . ";'>{$customerId}</td>";
                    echo "<td>{$phone}</td>";
                    echo "<td>{$email}</td>";
                    echo "</tr>";
                    
                    if ($customerId !== 'N/A') {
                        $customersSeen[$customerId] = $customerName;
                        $customerCount++;
                    }
                }
                
                echo "</table>";
                
                if ($customerCount >= 10) break; // Limit output
            }
            
            if (!empty($dateData['collections'])) {
                echo "<h3>Collections for $date:</h3>";
                echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
                echo "<tr><th>Name</th><th>Customer ID</th><th>Phone</th><th>Email</th></tr>";
                
                foreach ($dateData['collections'] as $collection) {
                    $customerId = $collection['customer_id'] ?? 'N/A';
                    $customerName = $collection['customer_name'] ?? $collection['name'] ?? 'N/A';
                    $phone = $collection['phone'] ?? 'N/A';
                    $email = $collection['email'] ?? 'N/A';
                    
                    echo "<tr>";
                    echo "<td>{$customerName}</td>";
                    echo "<td style='font-weight: bold; color: " . ($customerId === 'N/A' ? 'red' : 'blue') . ";'>{$customerId}</td>";
                    echo "<td>{$phone}</td>";
                    echo "<td>{$email}</td>";
                    echo "</tr>";
                    
                    if ($customerId !== 'N/A') {
                        $customersSeen[$customerId] = $customerName;
                        $customerCount++;
                    }
                }
                
                echo "</table>";
                
                if ($customerCount >= 10) break; // Limit output
            }
        }
        
        echo "<h3>Summary:</h3>";
        echo "<p>Total unique customers seen: " . count($customersSeen) . "</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Customer ID</th><th>Customer Name</th><th>Profile Link</th></tr>";
        
        foreach ($customersSeen as $id => $name) {
            $profileUrl = "https://middleworldfarms.org/wp-admin/user-edit.php?user_id=" . $id;
            echo "<tr>";
            echo "<td style='font-weight: bold;'>{$id}</td>";
            echo "<td>{$name}</td>";
            echo "<td><a href='{$profileUrl}' target='_blank'>Open Profile</a></td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>No delivery data found</p>";
        echo "<pre>" . print_r($data, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
