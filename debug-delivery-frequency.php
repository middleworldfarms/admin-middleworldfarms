<?php
// Debug script to check specifically delivery subscriptions frequency data

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\WpApiService;

// Get the WpApiService
$wpApi = app(WpApiService::class);

echo "=== Analyzing Delivery Subscription Structure ===\n\n";

try {
    // Fetch subscriptions data
    $subscriptions = $wpApi->getDeliveryScheduleData(100);
    
    // Count the subscriptions
    $totalSubscriptions = count($subscriptions);
    echo "Total subscriptions: {$totalSubscriptions}\n\n";
    
    // Separate deliveries and collections
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
    
    echo "Total deliveries: " . count($deliveries) . "\n";
    echo "Total collections: " . count($collections) . "\n\n";
    
    // Check frequency structure in delivery subscriptions
    echo "=== Delivery Subscriptions Frequency Structure ===\n";
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
        
        // Check billing_period property
        if ($frequency === 'unknown' && isset($delivery['billing_period'])) {
            // Check if weekly or bi-weekly
            if (strtolower($delivery['billing_period']) === 'week') {
                // Check billing interval (1 = weekly, 2 = fortnightly/bi-weekly)
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
        
        // If we found a fortnightly delivery, save it as an example
        if ($frequency === 'fortnightly' && !$exampleFortnightlyDelivery) {
            $exampleFortnightlyDelivery = $delivery;
        }
        
        // Update counts
        if (isset($frequencyCount[$frequency])) {
            $frequencyCount[$frequency]++;
        } else {
            $frequencyCount['other']++;
        }
        
        // Print the first 2 deliveries as examples
        if ($i < 2) {
            echo "\nDelivery #{$i} (ID: {$id}):\n";
            echo "  - Frequency: {$frequency} (found in {$metaSource})\n";
            echo "  - Customer: {$delivery['billing']['first_name']} {$delivery['billing']['last_name']}\n";
            echo "  - Status: {$delivery['status']}\n";
            
            // Show the keys where we might find frequency info
            echo "  - Has line_items[0].meta_data: " . (isset($delivery['line_items'][0]['meta_data']) ? 'Yes' : 'No') . "\n";
            echo "  - Has meta_data: " . (isset($delivery['meta_data']) ? 'Yes' : 'No') . "\n";
            echo "  - Has billing_period: " . (isset($delivery['billing_period']) ? 'Yes ('.$delivery['billing_period'].')' : 'No') . "\n";
            echo "  - Has billing_interval: " . (isset($delivery['billing_interval']) ? 'Yes ('.$delivery['billing_interval'].')' : 'No') . "\n";
        }
    }
    
    echo "\n=== Delivery Frequency Summary ===\n";
    echo "Fortnightly deliveries: {$frequencyCount['fortnightly']}\n";
    echo "Weekly deliveries: {$frequencyCount['weekly']}\n";
    echo "Unknown frequency: {$frequencyCount['unknown']}\n";
    echo "Other frequency: {$frequencyCount['other']}\n";
    
    // If we found a fortnightly delivery, print its full structure
    if ($exampleFortnightlyDelivery) {
        echo "\n=== Example Fortnightly Delivery Full Structure ===\n";
        echo "Subscription ID: {$exampleFortnightlyDelivery['id']}\n";
        
        // Extract the frequency-related keys specifically
        echo "billing_period: " . ($exampleFortnightlyDelivery['billing_period'] ?? 'Not set') . "\n";
        echo "billing_interval: " . ($exampleFortnightlyDelivery['billing_interval'] ?? 'Not set') . "\n";
        
        // Check meta_data for any frequency-related keys
        if (isset($exampleFortnightlyDelivery['meta_data'])) {
            echo "\nMeta Data (top level):\n";
            foreach ($exampleFortnightlyDelivery['meta_data'] as $meta) {
                if (strpos(strtolower($meta['key']), 'frequency') !== false || 
                    strpos(strtolower($meta['key']), 'week') !== false ||
                    strpos(strtolower($meta['key']), 'period') !== false ||
                    strpos(strtolower($meta['key']), 'interval') !== false) {
                    echo "  {$meta['key']} = {$meta['value']}\n";
                }
            }
        }
        
        // Check line_items[0].meta_data
        if (isset($exampleFortnightlyDelivery['line_items'][0]['meta_data'])) {
            echo "\nLine Items Meta Data:\n";
            foreach ($exampleFortnightlyDelivery['line_items'][0]['meta_data'] as $meta) {
                if (strpos(strtolower($meta['key']), 'frequency') !== false || 
                    strpos(strtolower($meta['key']), 'week') !== false ||
                    strpos(strtolower($meta['key']), 'period') !== false ||
                    strpos(strtolower($meta['key']), 'interval') !== false) {
                    echo "  {$meta['key']} = {$meta['value']}\n";
                }
            }
        }
    } else {
        echo "\nNo fortnightly delivery found in the sample.\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
