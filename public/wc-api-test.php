<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// WooCommerce API test
$wcApiUrl = config('services.wc_api.url');
$wcConsumerKey = config('services.wc_api.consumer_key');
$wcConsumerSecret = config('services.wc_api.consumer_secret');

echo "<h2>WooCommerce API Test</h2>";
echo "<p>Testing connection to: {$wcApiUrl}</p>";

try {
    // Test authentication with a simple GET request
    $response = Http::withBasicAuth($wcConsumerKey, $wcConsumerSecret)
        ->get("{$wcApiUrl}/wp-json/wc/v3/system_status");
    
    echo "<p>Response Status: " . $response->status() . "</p>";
    
    if ($response->successful()) {
        echo "<p style='color:green'>✅ WooCommerce API authentication successful!</p>";
        
        // Get some basic info from the response
        $data = $response->json();
        echo "<p>WooCommerce Version: " . ($data['environment']['version'] ?? 'Unknown') . "</p>";
    } else {
        echo "<p style='color:red'>❌ Authentication failed!</p>";
        echo "<p>Error: " . $response->body() . "</p>";
    }
    
    // Test GET on a subscription
    echo "<h3>Testing GET on a subscription</h3>";
    $subscriptionId = request()->get('subscription_id', '227736'); // Default to ID from your earlier example
    
    echo "<p>Testing GET on subscription ID: {$subscriptionId}</p>";
    
    $response = Http::withBasicAuth($wcConsumerKey, $wcConsumerSecret)
        ->get("{$wcApiUrl}/wp-json/wc/v3/subscriptions/{$subscriptionId}");
    
    echo "<p>Response Status: " . $response->status() . "</p>";
    
    if ($response->successful()) {
        echo "<p style='color:green'>✅ Successfully retrieved subscription!</p>";
        $data = $response->json();
        echo "<p>Subscription Status: " . ($data['status'] ?? 'Unknown') . "</p>";
        echo "<p>Customer: " . ($data['billing']['first_name'] ?? '') . " " . ($data['billing']['last_name'] ?? '') . "</p>";
        
        // Look for customer_week_type in meta_data
        $weekType = null;
        if (isset($data['meta_data'])) {
            foreach ($data['meta_data'] as $meta) {
                if ($meta['key'] === 'customer_week_type') {
                    $weekType = $meta['value'];
                    break;
                }
            }
        }
        
        echo "<p>Current Week Type: " . ($weekType ?? 'Not set') . "</p>";
        
        // Check if this is a fortnightly subscription
        $frequency = null;
        if (isset($data['line_items'][0]['meta_data'])) {
            foreach ($data['line_items'][0]['meta_data'] as $meta) {
                if ($meta['key'] === 'frequency') {
                    $frequency = $meta['value'];
                    break;
                }
            }
        }
        
        echo "<p>Frequency: " . ($frequency ?? 'Unknown') . "</p>";
        
        // Show a form to update the week type
        echo "<h3>Update Week Type</h3>";
        echo "<form method='post' action=''>";
        echo "<input type='hidden' name='action' value='update'>";
        echo "<input type='hidden' name='subscription_id' value='{$subscriptionId}'>";
        echo "<select name='week_type'>";
        echo "<option value='A'" . ($weekType === 'A' ? ' selected' : '') . ">Week A (Odd weeks)</option>";
        echo "<option value='B'" . ($weekType === 'B' ? ' selected' : '') . ">Week B (Even weeks)</option>";
        echo "</select>";
        echo "&nbsp;";
        echo "<button type='submit'>Update Week Type</button>";
        echo "</form>";
    } else {
        echo "<p style='color:red'>❌ Failed to retrieve subscription!</p>";
        echo "<p>Error: " . $response->body() . "</p>";
    }
    
    // Process form submission to update week type
    if (request()->method() === 'POST' && request()->get('action') === 'update') {
        $subscriptionId = request()->get('subscription_id');
        $weekType = request()->get('week_type');
        
        echo "<h3>Updating Week Type</h3>";
        echo "<p>Subscription ID: {$subscriptionId}</p>";
        echo "<p>New Week Type: {$weekType}</p>";
        
        // Try update
        $response = Http::withBasicAuth($wcConsumerKey, $wcConsumerSecret)
            ->put("{$wcApiUrl}/wp-json/wc/v3/subscriptions/{$subscriptionId}", [
                'meta_data' => [
                    [
                        'key' => 'customer_week_type',
                        'value' => $weekType
                    ]
                ]
            ]);
        
        echo "<p>Response Status: " . $response->status() . "</p>";
        
        if ($response->successful()) {
            echo "<p style='color:green'>✅ Successfully updated week type!</p>";
            $data = $response->json();
            
            // Check if update was successful by looking at returned meta_data
            $updatedWeekType = null;
            if (isset($data['meta_data'])) {
                foreach ($data['meta_data'] as $meta) {
                    if ($meta['key'] === 'customer_week_type') {
                        $updatedWeekType = $meta['value'];
                        break;
                    }
                }
            }
            
            echo "<p>Updated Week Type: " . ($updatedWeekType ?? 'Not found in response') . "</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to update week type!</p>";
            echo "<p>Error: " . $response->body() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Exception occurred!</p>";
    echo "<pre>" . $e->getMessage() . "\n\n" . $e->getTraceAsString() . "</pre>";
}
