<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\Http;

// Test WooCommerce subscriptions API directly
$consumerKey = env('WOOCOMMERCE_CONSUMER_KEY');
$consumerSecret = env('WOOCOMMERCE_CONSUMER_SECRET'); 
$baseUrl = env('WOOCOMMERCE_URL');

echo "Testing WooCommerce API...\n";
echo "Base URL: $baseUrl\n";
echo "Consumer Key: " . substr($consumerKey, 0, 10) . "...\n";

try {
    $response = Http::timeout(30)
        ->withBasicAuth($consumerKey, $consumerSecret)
        ->get($baseUrl . '/wp-json/wc/v3/subscriptions', [
            'per_page' => 5,
            'status' => 'active'
        ]);

    echo "Response status: " . $response->status() . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        echo "Subscriptions found: " . count($data) . "\n";
        
        if (count($data) > 0) {
            echo "First subscription data:\n";
            echo json_encode($data[0], JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "No active subscriptions found\n";
        }
    } else {
        echo "API Error: " . $response->status() . "\n";
        echo "Response body: " . $response->body() . "\n";
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
