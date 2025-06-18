<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

// Read subscription ID from query parameter
$subscriptionId = $_GET['id'] ?? null;

if (!$subscriptionId) {
    echo "<h2>Please provide a subscription ID</h2>";
    echo "<p>Example: ?id=227736</p>";
    exit;
}

// Get API credentials from config
$wcApiUrl = config('services.wc_api.url');
$wcConsumerKey = config('services.wc_api.consumer_key');
$wcConsumerSecret = config('services.wc_api.consumer_secret');

// Check if the subscription exists
echo "<h2>Checking Subscription ID: {$subscriptionId}</h2>";

try {
    $response = Http::withBasicAuth($wcConsumerKey, $wcConsumerSecret)
        ->get("{$wcApiUrl}/wp-json/wc/v3/subscriptions/{$subscriptionId}");
        
    if ($response->successful()) {
        $sub = $response->json();
        
        echo "<h3>Subscription Found! ✅</h3>";
        echo "<pre>";
        echo "ID: {$sub['id']}\n";
        echo "Customer ID: {$sub['customer_id']}\n";
        echo "Status: {$sub['status']}\n";
        echo "Customer: " . ($sub['billing']['first_name'] ?? '') . " " . ($sub['billing']['last_name'] ?? '') . "\n";
        echo "</pre>";
        
        echo "<h3>Update Customer Week Type</h3>";
        echo "<form method='post'>";
        echo "<input type='hidden' name='subscription_id' value='{$subscriptionId}'>";
        echo "<select name='week_type'>";
        echo "<option value='A'>Week A (Odd weeks)</option>";
        echo "<option value='B'>Week B (Even weeks)</option>";
        echo "</select>";
        echo "<button type='submit'>Update Week</button>";
        echo "</form>";
        
    } else {
        echo "<h3>Subscription Not Found! ❌</h3>";
        echo "<pre>";
        echo "Status Code: {$response->status()}\n";
        echo "Response: {$response->body()}\n";
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<h3>Error! ❌</h3>";
    echo "<pre>";
    echo "Exception: {$e->getMessage()}\n";
    echo "</pre>";
}

// If form is submitted, try to update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subId = $_POST['subscription_id'] ?? null;
    $weekType = $_POST['week_type'] ?? null;
    
    if ($subId && in_array($weekType, ['A', 'B'])) {
        try {
            $response = Http::withBasicAuth($wcConsumerKey, $wcConsumerSecret)
                ->put("{$wcApiUrl}/wp-json/wc/v3/subscriptions/{$subId}", [
                    'meta_data' => [
                        [
                            'key' => 'customer_week_type',
                            'value' => $weekType
                        ]
                    ]
                ]);
                
            if ($response->successful()) {
                echo "<h3>Update Successful! ✅</h3>";
                echo "<pre>";
                echo "Subscription {$subId} updated with week type {$weekType}.\n";
                echo "</pre>";
            } else {
                echo "<h3>Update Failed! ❌</h3>";
                echo "<pre>";
                echo "Status Code: {$response->status()}\n";
                echo "Response: {$response->body()}\n";
                echo "</pre>";
            }
        } catch (Exception $e) {
            echo "<h3>Update Error! ❌</h3>";
            echo "<pre>";
            echo "Exception: {$e->getMessage()}\n";
            echo "</pre>";
        }
    }
}
