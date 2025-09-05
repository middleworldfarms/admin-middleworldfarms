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
        echo "Found subscription 227878:\n";
        echo "Status: " . ($subscription['status'] ?? 'Unknown') . "\n";
        echo "Customer: " . ($subscription['billing']['first_name'] ?? '') . " " . ($subscription['billing']['last_name'] ?? '') . "\n";

        $shippingTotal = 0;
        if (isset($subscription['shipping_lines']) && is_array($subscription['shipping_lines'])) {
            foreach ($subscription['shipping_lines'] as $line) {
                $shippingTotal += floatval($line['total'] ?? 0);
            }
        }
        echo "Shipping Total: £" . number_format($shippingTotal, 2) . "\n";

        $hasShippingAddress = isset($subscription['shipping']['address_1']) && !empty(trim($subscription['shipping']['address_1']));
        echo "Has Shipping Address: " . ($hasShippingAddress ? 'Yes' : 'No') . "\n";
        if ($hasShippingAddress) {
            echo "Shipping Address: " . $subscription['shipping']['address_1'] . "\n";
        }

        echo "Shipping Classes in Line Items:\n";
        if (isset($subscription['line_items']) && is_array($subscription['line_items'])) {
            foreach ($subscription['line_items'] as $item) {
                if (isset($item['shipping_class'])) {
                    echo "  - " . $item['shipping_class'] . "\n";
                } else {
                    echo "  - No shipping class set\n";
                }
            }
        }

        echo "Shipping Methods:\n";
        if (isset($subscription['shipping_lines']) && is_array($subscription['shipping_lines'])) {
            foreach ($subscription['shipping_lines'] as $line) {
                if (isset($line['method_title'])) {
                    echo "  - " . $line['method_title'] . "\n";
                }
            }
        }

        echo "Shipping Class in Meta Data:\n";
        if (isset($subscription['meta_data']) && is_array($subscription['meta_data'])) {
            foreach ($subscription['meta_data'] as $meta) {
                if (isset($meta['key']) && strpos(strtolower($meta['key']), 'shipping') !== false) {
                    echo "  - " . $meta['key'] . ": " . $meta['value'] . "\n";
                }
            }
        }

        $customerType = 'collections';

        if (isset($subscription['shipping_lines']) && is_array($subscription['shipping_lines'])) {
            foreach ($subscription['shipping_lines'] as $shippingLine) {
                if (isset($shippingLine['method_title']) && is_string($shippingLine['method_title'])) {
                    $methodTitle = strtolower($shippingLine['method_title']);
                    if (strpos($methodTitle, 'collection') !== false) {
                        $customerType = 'collections';
                        break;
                    }
                }
            }
        }

        if (isset($subscription['line_items']) && is_array($subscription['line_items'])) {
            foreach ($subscription['line_items'] as $item) {
                if (isset($item['shipping_class']) && is_string($item['shipping_class'])) {
                    $shippingClass = strtolower($item['shipping_class']);
                    if (strpos($shippingClass, 'collection') !== false) {
                        $customerType = 'collections';
                        break;
                    }
                }
            }
        }

        $hasDeliveryAddress = false;
        if (isset($subscription['shipping']['address_1']) && !empty(trim($subscription['shipping']['address_1']))) {
            $hasDeliveryAddress = true;
        } elseif (isset($subscription['billing']['address_1']) && !empty(trim($subscription['billing']['address_1']))) {
            $hasDeliveryAddress = true;
        }

        if ($shippingTotal > 0) {
            $customerType = 'deliveries';
        } elseif ($hasDeliveryAddress) {
            $customerType = 'deliveries';
        }

        echo "\nCurrent Classification: " . $customerType . "\n";

    } else {
        echo "Subscription 227878 not found or error:\n";
        echo "Status: " . $response->status() . "\n";
        echo "Body: " . substr($response->body(), 0, 500) . "...\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
