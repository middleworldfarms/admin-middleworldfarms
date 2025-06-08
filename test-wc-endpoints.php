<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\Http;

// Test what endpoints are actually available
$consumerKey = env('WOOCOMMERCE_CONSUMER_KEY');
$consumerSecret = env('WOOCOMMERCE_CONSUMER_SECRET'); 
$baseUrl = env('WOOCOMMERCE_URL');

echo "Testing various WooCommerce endpoints...\n";

$endpoints = [
    'orders' => '/wp-json/wc/v3/orders?per_page=5',
    'subscriptions' => '/wp-json/wc/v3/subscriptions?per_page=5',
    'customers' => '/wp-json/wc/v3/customers?per_page=5'
];

foreach ($endpoints as $name => $endpoint) {
    echo "\n--- Testing $name endpoint ---\n";
    try {
        $response = Http::timeout(10)
            ->withBasicAuth($consumerKey, $consumerSecret)
            ->get($baseUrl . $endpoint);

        echo "Status: " . $response->status() . "\n";
        
        if ($response->successful()) {
            $data = $response->json();
            echo "Records found: " . count($data) . "\n";
            if (count($data) > 0) {
                echo "First record ID: " . ($data[0]['id'] ?? 'N/A') . "\n";
                if ($name === 'customers') {
                    echo "Customer email: " . ($data[0]['email'] ?? 'N/A') . "\n";
                }
            }
        } else {
            echo "Error response: " . substr($response->body(), 0, 200) . "\n";
        }
        
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
}
