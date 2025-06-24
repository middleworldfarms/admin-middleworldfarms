<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\WpApiService;

$wpApi = app(WpApiService::class);

echo "<h2>Analyzing Delivery Subscription Structure</h2>\n\n";

try {
    $subscriptions = $wpApi->getDeliveryScheduleData(50);
    
    $totalSubscriptions = count($subscriptions);
    echo "Total subscriptions: {$totalSubscriptions}<br><br>\n\n";
    
    $deliveries = [];
    $collections = [];
    
    foreach ($subscriptions as $sub) {
        $shippingTotal = (float) ($sub['shipping_total'] ?? 0);
        if ($shippingTotal > 0) {
            $deliveries[] = $sub;
        } else {
            $collections[] = $sub;
        }
    }
    
    echo "Total deliveries: " . count($deliveries) . "<br>\n";
    echo "Total collections: " . count($collections) . "<br><br>\n\n";
    
    // Check frequency structure in delivery subscriptions
    echo "<h3>Delivery Subscriptions Frequency Structure</h3>\n";
    $frequencyCount = [
        'fortnightly' => 0,
        'weekly' => 0,
        'unknown' => 0,
        'other' => 0
    ];
    
    $exampleFortnightlyDelivery = null;
    
    foreach ($deliveries as $i => $delivery) {
        $id = $delivery['id'];
        $frequency = 'unknown';
        $metaSource = 'none';
        
        // Check line_items -> meta_data
        if (isset($delivery['line_items'][0]['meta_data'])) {
            foreach ($delivery['line_items'][0]['meta_data'] as $meta) {
                if ($meta['key'] === 'frequency') {
                    $frequency = strtolower($meta['value']);
                    $metaSource = 'line_items[0].meta_data';
                    break;
                }
            }
        }
        
        // Check top-level meta_data if not found yet
        if ($frequency === 'unknown' && isset($delivery['meta_data'])) {
            foreach ($delivery['meta_data'] as $meta) {
                if ($meta['key'] === 'frequency' || $meta['key'] === '_subscription_frequency') {
                    $frequency = strtolower($meta['value']);
                    $metaSource = 'meta_data';
                    break;
                }
            }
        }
        
        // Check billing_period property - this is the standard WooCommerce method
        if ($frequency === 'unknown' && isset($delivery['billing_period'])) {
            if (strtolower($delivery['billing_period']) === 'week') {
                $interval = intval($delivery['billing_interval'] ?? 1);
                if ($interval === 2) {
                    $frequency = 'fortnightly';
                    $metaSource = 'billing_period/interval';
                } elseif ($interval === 1) {
                    $frequency = 'weekly';
                    $metaSource = 'billing_period/interval';
                }
            }
        }
        
        // Update counts
        if (isset($frequencyCount[$frequency])) {
            $frequencyCount[$frequency]++;
        } else {
            $frequencyCount['other']++;
        }
        
        // If we found a fortnightly delivery, save it as an example
        if ($frequency === 'fortnightly' && !$exampleFortnightlyDelivery) {
            $exampleFortnightlyDelivery = $delivery;
        }
        
        // Print the first 5 deliveries as examples
        if ($i < 5) {
            echo "<strong>Delivery #{$i} (ID: {$id}):</strong><br>\n";
            echo "&nbsp;&nbsp;- Frequency: {$frequency} (found in {$metaSource})<br>\n";
            echo "&nbsp;&nbsp;- Customer: {$delivery['billing']['first_name']} {$delivery['billing']['last_name']}<br>\n";
            echo "&nbsp;&nbsp;- Status: {$delivery['status']}<br>\n";
            echo "&nbsp;&nbsp;- Has line_items[0].meta_data: " . (isset($delivery['line_items'][0]['meta_data']) ? 'Yes' : 'No') . "<br>\n";
            echo "&nbsp;&nbsp;- Has meta_data: " . (isset($delivery['meta_data']) ? 'Yes' : 'No') . "<br>\n";
            echo "&nbsp;&nbsp;- billing_period: " . ($delivery['billing_period'] ?? 'Not set') . "<br>\n";
            echo "&nbsp;&nbsp;- billing_interval: " . ($delivery['billing_interval'] ?? 'Not set') . "<br><br>\n";
        }
    }
    
    echo "<h3>Delivery Frequency Summary</h3>\n";
    echo "Fortnightly deliveries: {$frequencyCount['fortnightly']}<br>\n";
    echo "Weekly deliveries: {$frequencyCount['weekly']}<br>\n";
    echo "Unknown frequency: {$frequencyCount['unknown']}<br>\n";
    echo "Other frequency: {$frequencyCount['other']}<br><br>\n";
    
    // If we found a fortnightly delivery, print its structure
    if ($exampleFortnightlyDelivery) {
        echo "<h3>Example Fortnightly Delivery Full Structure</h3>\n";
        echo "Subscription ID: {$exampleFortnightlyDelivery['id']}<br>\n";
        echo "billing_period: " . ($exampleFortnightlyDelivery['billing_period'] ?? 'Not set') . "<br>\n";
        echo "billing_interval: " . ($exampleFortnightlyDelivery['billing_interval'] ?? 'Not set') . "<br>\n";
        echo "<pre>";
        print_r($exampleFortnightlyDelivery);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<strong>ERROR:</strong> " . $e->getMessage() . "<br>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}
echo "<br><br>DEBUG COMPLETE<br>";
