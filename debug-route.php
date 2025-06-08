<?php

use Illuminate\Support\Facades\Route;

Route::get('/debug-customer-data', function () {
    try {
        $service = new \App\Services\DeliveryScheduleService();
        $data = $service->getEnhancedSchedule();
        
        $customersSeen = [];
        $output = "<h2>Customer ID Debug Report</h2>";
        $output .= "<p>Generated: " . date('Y-m-d H:i:s') . "</p>";
        
        if (isset($data['data'])) {
            $output .= "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            $output .= "<tr style='background: #f0f0f0;'><th>Date</th><th>Type</th><th>Customer Name</th><th>Customer ID</th><th>Profile Link Test</th></tr>";
            
            foreach ($data['data'] as $date => $dateData) {
                // Check deliveries
                if (!empty($dateData['deliveries'])) {
                    foreach (array_slice($dateData['deliveries'], 0, 5) as $delivery) {
                        $customerId = $delivery['customer_id'] ?? 'MISSING';
                        $customerName = $delivery['name'] ?? 'MISSING';
                        $profileUrl = "https://middleworldfarms.org/wp-admin/user-edit.php?user_id=" . $customerId;
                        
                        $output .= "<tr>";
                        $output .= "<td>{$date}</td>";
                        $output .= "<td>🚚 Delivery</td>";
                        $output .= "<td>{$customerName}</td>";
                        $output .= "<td style='font-weight: bold; color: blue;'>{$customerId}</td>";
                        $output .= "<td><a href='{$profileUrl}' target='_blank'>Test Profile Link</a></td>";
                        $output .= "</tr>";
                        
                        $customersSeen[$customerId] = $customerName;
                    }
                }
                
                // Check collections
                if (!empty($dateData['collections'])) {
                    foreach (array_slice($dateData['collections'], 0, 5) as $collection) {
                        $customerId = $collection['customer_id'] ?? 'MISSING';
                        $customerName = $collection['customer_name'] ?? $collection['name'] ?? 'MISSING';
                        $profileUrl = "https://middleworldfarms.org/wp-admin/user-edit.php?user_id=" . $customerId;
                        
                        $output .= "<tr>";
                        $output .= "<td>{$date}</td>";
                        $output .= "<td>📦 Collection</td>";
                        $output .= "<td>{$customerName}</td>";
                        $output .= "<td style='font-weight: bold; color: green;'>{$customerId}</td>";
                        $output .= "<td><a href='{$profileUrl}' target='_blank'>Test Profile Link</a></td>";
                        $output .= "</tr>";
                        
                        $customersSeen[$customerId] = $customerName;
                    }
                }
                
                if (count($customersSeen) >= 10) break; // Limit to first 10 customers
            }
            
            $output .= "</table>";
            
            $output .= "<h3>Summary of Customer IDs Found:</h3>";
            $output .= "<ul>";
            foreach ($customersSeen as $id => $name) {
                $output .= "<li><strong>ID {$id}:</strong> {$name}</li>";
            }
            $output .= "</ul>";
            
            $output .= "<p><strong>Total unique customers:</strong> " . count($customersSeen) . "</p>";
            
        } else {
            $output .= "<p style='color: red;'>No delivery data found!</p>";
            $output .= "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
        }
        
        return $output;
        
    } catch (Exception $e) {
        return "<p style='color: red;'>Error: " . $e->getMessage() . "</p>" .
               "<pre>" . $e->getTraceAsString() . "</pre>";
    }
});
