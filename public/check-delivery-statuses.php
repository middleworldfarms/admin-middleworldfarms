<?php
// Check delivery statuses and types
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::createFromGlobals();
$kernel->bootstrap();

$directDb = new \App\Services\DirectDatabaseService();
$rawData = $directDb->getDeliveryScheduleData(100);

echo "<h2>📊 Delivery Status Analysis</h2>";

$deliveryStatuses = [];
$deliveryTypes = [];
$orderStatuses = [];

foreach ($rawData['deliveries'] as $delivery) {
    // Check delivery status
    if (isset($delivery['status'])) {
        $status = $delivery['status'];
        if (!isset($deliveryStatuses[$status])) {
            $deliveryStatuses[$status] = 0;
        }
        $deliveryStatuses[$status]++;
    }
    
    // Check delivery type
    if (isset($delivery['type'])) {
        $type = $delivery['type'];
        if (!isset($deliveryTypes[$type])) {
            $deliveryTypes[$type] = 0;
        }
        $deliveryTypes[$type]++;
    }
    
    // Check order status (WooCommerce status)
    if (isset($delivery['order_status'])) {
        $orderStatus = $delivery['order_status'];
        if (!isset($orderStatuses[$orderStatus])) {
            $orderStatuses[$orderStatus] = 0;
        }
        $orderStatuses[$orderStatus]++;
    }
}

echo "<h3>🚚 Delivery Statuses:</h3>";
if (!empty($deliveryStatuses)) {
    foreach ($deliveryStatuses as $status => $count) {
        echo "- <strong>$status:</strong> $count<br>";
    }
} else {
    echo "No delivery status field found<br>";
}

echo "<h3>📦 Delivery Types:</h3>";
if (!empty($deliveryTypes)) {
    foreach ($deliveryTypes as $type => $count) {
        echo "- <strong>$type:</strong> $count<br>";
    }
} else {
    echo "No delivery type field found<br>";
}

echo "<h3>🛒 Order Statuses:</h3>";
if (!empty($orderStatuses)) {
    foreach ($orderStatuses as $status => $count) {
        echo "- <strong>$status:</strong> $count<br>";
    }
} else {
    echo "No order status field found<br>";
}

// Show sample delivery data
echo "<h3>🔍 Sample Delivery Data:</h3>";
$sampleCount = 0;
foreach ($rawData['deliveries'] as $delivery) {
    if ($sampleCount < 3) {
        echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 5px;'>";
        echo "<strong>Customer:</strong> " . ($delivery['customer_name'] ?? 'N/A') . "<br>";
        echo "<strong>Email:</strong> " . ($delivery['customer_email'] ?? 'N/A') . "<br>";
        echo "<strong>Status:</strong> " . ($delivery['status'] ?? 'N/A') . "<br>";
        echo "<strong>Type:</strong> " . ($delivery['type'] ?? 'N/A') . "<br>";
        echo "<strong>Order Status:</strong> " . ($delivery['order_status'] ?? 'N/A') . "<br>";
        echo "<strong>Date:</strong> " . ($delivery['date_created'] ?? 'N/A') . "<br>";
        echo "</div>";
        $sampleCount++;
    }
}
?>
