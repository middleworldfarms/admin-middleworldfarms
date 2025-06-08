<?php

// Direct test of the customer action endpoints
// URL: https://admin.middleworldfarms.org/debug-customer-actions.php

$test_user_id = 22; // Emma Garner
$csrf_token = 'debug_test'; // We'll bypass CSRF for testing

echo "<h1>🔍 Debug Customer Action Endpoints</h1>";
echo "<p>Testing User ID: <strong>{$test_user_id}</strong></p>";

// Test the Laravel endpoints directly
$endpoints = [
    'profile' => '/admin/customer/profile',
    'subscriptions' => '/admin/customer/subscriptions', 
    'orders' => '/admin/customer/orders'
];

foreach ($endpoints as $action => $endpoint) {
    echo "<h2>Testing {$action} endpoint: {$endpoint}</h2>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://admin.middleworldfarms.org' . $endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['user_id' => $test_user_id]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-CSRF-TOKEN: ' . $csrf_token
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "<pre>";
    echo "HTTP Code: {$http_code}\n";
    if ($error) {
        echo "❌ cURL Error: {$error}\n";
    } else {
        echo "Response: {$response}\n";
    }
    echo "</pre><hr>";
}

echo "<p><a href='/admin/deliveries'>← Back to Deliveries</a></p>";
?>
