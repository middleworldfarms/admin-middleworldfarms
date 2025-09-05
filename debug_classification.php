<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$wcApiUrl = config('services.wc_api.url');
$wcConsumerKey = config('services.wc_api.consumer_key');
$wcConsumerSecret = config('services.wc_api.consumer_secret');

try {
    $response = Illuminate\Support\Facades\Http::withBasicAuth($wcConsumerKey, $wcConsumerSecret)
        ->get("$wcApiUrl/wp-json/wc/v3/subscriptions/227878");

    if ($response->successful()) {
        $subscription = $response->json();

        // Debug the classification logic step by step
        echo "=== CLASSIFICATION DEBUG ===\n";

        $customerType = 'collections'; // default
        echo "1. Default classification: " . $customerType . "\n";

        // PRIORITY 1: Check shipping method for explicit collection indicators
        if (isset($subscription['shipping_lines']) && is_array($subscription['shipping_lines'])) {
            foreach ($subscription['shipping_lines'] as $shippingLine) {
                if (isset($shippingLine['method_title']) && is_string($shippingLine['method_title'])) {
                    $methodTitle = strtolower($shippingLine['method_title']);
                    echo "2. Checking method_title: " . $methodTitle . "\n";
                    if (strpos($methodTitle, 'collection') !== false || strpos($methodTitle, 'pickup') !== false) {
                        $customerType = 'collections';
                        echo "   -> Found collection/pickup in method_title, FINAL DECISION: " . $customerType . "\n";
                        echo "\nFINAL RESULT: " . $customerType . "\n";
                        exit; // Exit early since this takes priority
                    } else {
                        echo "   -> No collection/pickup found in method_title\n";
                    }
                }
            }
        }

        // PRIORITY 2: Check shipping classes in line_items
        if (isset($subscription['line_items']) && is_array($subscription['line_items'])) {
            foreach ($subscription['line_items'] as $item) {
                if (isset($item['shipping_class']) && is_string($item['shipping_class'])) {
                    $shippingClass = strtolower($item['shipping_class']);
                    echo "3. Checking shipping_class: " . $shippingClass . "\n";
                    if (strpos($shippingClass, 'collection') !== false || strpos($shippingClass, 'pickup') !== false) {
                        $customerType = 'collections';
                        echo "   -> Found collection/pickup in shipping_class, FINAL DECISION: " . $customerType . "\n";
                        echo "\nFINAL RESULT: " . $customerType . "\n";
                        exit; // Exit early since this takes priority
                    } else {
                        echo "   -> No collection/pickup found in shipping_class\n";
                    }
                } else {
                    echo "3. No shipping_class set on line items\n";
                }
            }
        }

        // PRIORITY 4: Check shipping total
        $shippingTotal = 0;
        if (isset($subscription['shipping_lines']) && is_array($subscription['shipping_lines'])) {
            foreach ($subscription['shipping_lines'] as $line) {
                $shippingTotal += floatval($line['total'] ?? 0);
            }
        }
        echo "4. Shipping total: £" . number_format($shippingTotal, 2) . "\n";
        if ($shippingTotal > 0) {
            $customerType = 'deliveries';
            echo "   -> Shipping total > 0, FINAL DECISION: " . $customerType . "\n";
            echo "\nFINAL RESULT: " . $customerType . "\n";
            exit;
        }

        // PRIORITY 5: Check if customer has a delivery address (only matters if no collection method found above)
        $hasDeliveryAddress = false;
        if (isset($subscription['shipping']['address_1']) && !empty(trim($subscription['shipping']['address_1']))) {
            $hasDeliveryAddress = true;
            echo "5. Has shipping address: YES (" . $subscription['shipping']['address_1'] . ")\n";
        } elseif (isset($subscription['billing']['address_1']) && !empty(trim($subscription['billing']['address_1']))) {
            $hasDeliveryAddress = true;
            echo "5. Has billing address: YES (" . $subscription['billing']['address_1'] . ")\n";
        } else {
            echo "5. No delivery address found\n";
        }

        // Final classification based on address (only if no collection method found)
        if ($hasDeliveryAddress) {
            $customerType = 'deliveries';
            echo "6. Has delivery address, FINAL DECISION: " . $customerType . "\n";
        } else {
            echo "6. No shipping cost and no delivery address, FINAL DECISION: " . $customerType . "\n";
        }

        echo "\nFINAL RESULT: " . $customerType . "\n";

    } else {
        echo "Failed to get subscription\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
