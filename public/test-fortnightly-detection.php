<?php
require_once 'vendor/autoload.php';

use App\Services\DirectDatabaseService;

$service = new DirectDatabaseService();

echo "<h2>🗓️ Fortnightly Delivery Week Detection Test</h2>";

// Current week information
$currentWeek = (int) date('W');
$currentYear = date('Y');
$weekType = ($currentWeek % 2 === 1) ? 'A' : 'B';

echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
echo "<h3>📅 Current Week Information</h3>";
echo "<p><strong>Current Date:</strong> " . date('l, F j, Y') . "</p>";
echo "<p><strong>ISO Week Number:</strong> $currentWeek (Year $currentYear)</p>";
echo "<p><strong>Week Type:</strong> <span style='color: " . ($weekType === 'A' ? '#2e7d32' : '#d32f2f') . "; font-weight: bold;'>Week $weekType</span></p>";
echo "<p><small>Logic: " . ($currentWeek % 2 === 1 ? 'Odd' : 'Even') . " week numbers = Week $weekType</small></p>";
echo "</div>";

// Test fortnightly schedule
try {
    echo "<h3>🔍 Testing Fortnightly Schedule Detection</h3>";
    
    $fortnightlyData = $service->getFortnightlySchedule();
    
    echo "<div style='background: #f3e5f5; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
    echo "<h4>Fortnightly Schedule Results:</h4>";
    echo "<p><strong>Week Type:</strong> " . $fortnightlyData['week_type'] . "</p>";
    echo "<p><strong>Current ISO Week:</strong> " . $fortnightlyData['current_iso_week'] . "</p>";
    echo "<p><strong>Fortnightly Subscriptions This Week:</strong> " . $fortnightlyData['count'] . "</p>";
    
    if (isset($fortnightlyData['error'])) {
        echo "<p style='color: #d32f2f;'><strong>Error:</strong> " . $fortnightlyData['error'] . "</p>";
    }
    echo "</div>";
    
    // Test full delivery schedule with fortnightly detection
    echo "<h3>📦 Testing Full Delivery Schedule</h3>";
    
    $scheduleData = $service->getDeliveryScheduleData(20);
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
    echo "<h4>Delivery Schedule Summary:</h4>";
    echo "<p><strong>Total Deliveries:</strong> " . $scheduleData['total_deliveries'] . "</p>";
    echo "<p><strong>Total Collections:</strong> " . $scheduleData['total_collections'] . "</p>";
    echo "</div>";
    
    // Analyze subscription frequencies
    if (isset($scheduleData['collections']) && $scheduleData['collections']->count() > 0) {
        echo "<h4>📊 Subscription Frequency Analysis:</h4>";
        
        $weeklyCount = 0;
        $fortnightlyCount = 0;
        $fortnightlyActiveThisWeek = 0;
        
        echo "<table style='border-collapse: collapse; width: 100%; margin: 15px 0;'>";
        echo "<tr style='background: #f5f5f5;'>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Customer</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Frequency</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Week</th>";
        echo "<th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Active This Week</th>";
        echo "</tr>";
        
        foreach ($scheduleData['collections'] as $subscription) {
            $frequency = $subscription['frequency'] ?? 'weekly';
            $deliveryWeek = $subscription['delivery_week'] ?? 'N/A';
            $isActiveThisWeek = $subscription['is_delivery_week'] ?? true;
            
            if ($frequency === 'weekly') {
                $weeklyCount++;
            } elseif ($frequency === 'Fortnightly') {
                $fortnightlyCount++;
                if ($isActiveThisWeek) {
                    $fortnightlyActiveThisWeek++;
                }
            }
            
            $customerName = $subscription['customer']['display_name'] ?? 
                           ($subscription['customer']['first_name'] . ' ' . $subscription['customer']['last_name']) ?? 
                           'Unknown Customer';
            
            $activeStatus = $isActiveThisWeek ? 
                "<span style='color: #2e7d32; font-weight: bold;'>✓ Yes</span>" : 
                "<span style='color: #d32f2f;'>✗ No</span>";
            
            echo "<tr>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($customerName) . "</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($frequency) . "</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($deliveryWeek) . "</td>";
            echo "<td style='border: 1px solid #ddd; padding: 8px;'>$activeStatus</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<div style='background: #fff3e0; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
        echo "<h4>📈 Frequency Summary:</h4>";
        echo "<p><strong>Weekly Subscriptions:</strong> $weeklyCount</p>";
        echo "<p><strong>Fortnightly Subscriptions:</strong> $fortnightlyCount</p>";
        echo "<p><strong>Fortnightly Active This Week (Week $weekType):</strong> $fortnightlyActiveThisWeek</p>";
        echo "<p><strong>Total Active This Week:</strong> " . ($weeklyCount + $fortnightlyActiveThisWeek) . "</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; color: #c62828; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
    echo "<h4>❌ Error Testing Fortnightly Detection:</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>🔧 Next Steps:</h3>";
echo "<ul>";
echo "<li>Verify that WooCommerce subscriptions have the 'frequency' attribute set to 'weekly' or 'Fortnightly'</li>";
echo "<li>Test the Week A/B logic with actual fortnightly subscriptions</li>";
echo "<li>Update the delivery schedule view to show Week A/B information</li>";
echo "<li>Consider adding manual Week A/B override functionality</li>";
echo "</ul>";

echo "<p><small>Test run at: " . date('Y-m-d H:i:s') . "</small></p>";
?>
