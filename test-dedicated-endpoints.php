<?php
/**
 * Test script for dedicated customer action endpoints
 * This script tests the new Profile, Subscriptions, and Orders endpoints
 */

// WordPress REST API configuration
$wordpress_api_key = 'Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h';
$wordpress_api_base = 'https://middleworldfarms.org/wp-json/mwf/v1';

// Test user ID (Emma Garner)
$test_user_id = 22;

/**
 * Make API request to WordPress
 */
function makeRequest($endpoint, $data, $api_key, $api_base) {
    $url = $api_base . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-WC-API-Key: ' . $api_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error, 'http_code' => $http_code];
    }
    
    return [
        'response' => json_decode($response, true),
        'http_code' => $http_code,
        'raw_response' => $response
    ];
}

echo "<h1>🧪 Testing Dedicated Customer Action Endpoints</h1>";
echo "<p>Testing with User ID: <strong>{$test_user_id}</strong> (Emma Garner)</p>";

// Test 1: Customer Profile Endpoint
echo "<h2>1. 👤 Customer Profile Endpoint</h2>";
$profile_result = makeRequest('/customer/profile', ['user_id' => $test_user_id], $wordpress_api_key, $wordpress_api_base);
echo "<pre>";
echo "HTTP Code: " . $profile_result['http_code'] . "\n";
if (isset($profile_result['error'])) {
    echo "❌ Error: " . $profile_result['error'] . "\n";
} else {
    echo "✅ Response: " . json_encode($profile_result['response'], JSON_PRETTY_PRINT) . "\n";
    if (isset($profile_result['response']['preview_url'])) {
        echo "🔗 Preview URL: " . $profile_result['response']['preview_url'] . "\n";
    }
}
echo "</pre>";

// Test 2: Customer Subscriptions Endpoint
echo "<h2>2. 📦 Customer Subscriptions Endpoint</h2>";
$subscriptions_result = makeRequest('/customer/subscriptions', ['user_id' => $test_user_id], $wordpress_api_key, $wordpress_api_base);
echo "<pre>";
echo "HTTP Code: " . $subscriptions_result['http_code'] . "\n";
if (isset($subscriptions_result['error'])) {
    echo "❌ Error: " . $subscriptions_result['error'] . "\n";
} else {
    echo "✅ Response: " . json_encode($subscriptions_result['response'], JSON_PRETTY_PRINT) . "\n";
    if (isset($subscriptions_result['response']['preview_url'])) {
        echo "🔗 Preview URL: " . $subscriptions_result['response']['preview_url'] . "\n";
    }
}
echo "</pre>";

// Test 3: Customer Orders Endpoint
echo "<h2>3. 🛒 Customer Orders Endpoint</h2>";
$orders_result = makeRequest('/customer/orders', ['user_id' => $test_user_id], $wordpress_api_key, $wordpress_api_base);
echo "<pre>";
echo "HTTP Code: " . $orders_result['http_code'] . "\n";
if (isset($orders_result['error'])) {
    echo "❌ Error: " . $orders_result['error'] . "\n";
} else {
    echo "✅ Response: " . json_encode($orders_result['response'], JSON_PRETTY_PRINT) . "\n";
    if (isset($orders_result['response']['preview_url'])) {
        echo "🔗 Preview URL: " . $orders_result['response']['preview_url'] . "\n";
    }
}
echo "</pre>";

echo "<h2>📊 Summary</h2>";
echo "<ul>";
echo "<li>Profile API: " . ($profile_result['http_code'] == 200 ? "✅ Success" : "❌ Failed") . "</li>";
echo "<li>Subscriptions API: " . ($subscriptions_result['http_code'] == 200 ? "✅ Success" : "❌ Failed") . "</li>";
echo "<li>Orders API: " . ($orders_result['http_code'] == 200 ? "✅ Success" : "❌ Failed") . "</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='/admin/deliveries'>← Back to Deliveries</a></p>";
?>
