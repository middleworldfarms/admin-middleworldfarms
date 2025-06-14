<?php
require_once __DIR__ . '/vendor/autoload.php';

// Test the customer switching functionality
echo "Testing Customer Switch Functionality\n";
echo "=====================================\n\n";

// Test 1: Check if routes are accessible
echo "1. Testing route accessibility...\n";

$routes = [
    'GET /admin/users' => 'http://localhost:8000/admin/users',
    'POST /admin/users/switch/1' => 'http://localhost:8000/admin/users/switch/1',
    'GET /admin/users/details/1' => 'http://localhost:8000/admin/users/details/1'
];

foreach ($routes as $route => $url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "  $route: HTTP $httpCode ";
    if ($httpCode == 200) {
        echo "✓ Available\n";
    } elseif ($httpCode == 302) {
        echo "↳ Redirect (likely auth required)\n";
    } else {
        echo "✗ Error\n";
    }
}

echo "\n2. Testing with sample customer IDs from database...\n";

// Get some actual customer IDs from the recent test
$sampleCustomerIds = [224665, 224677, 225027];

foreach ($sampleCustomerIds as $customerId) {
    echo "  Testing customer ID: $customerId\n";
    
    // Test the URL format that JavaScript will use
    $url = "http://localhost:8000/admin/users/switch/$customerId";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['redirect_to' => '/my-account/']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "    HTTP $httpCode: ";
    if ($httpCode == 200) {
        $data = json_decode($result, true);
        if (isset($data['success']) && $data['success']) {
            echo "✓ Success - Switch URL generated\n";
        } else {
            echo "✗ Failed - " . ($data['message'] ?? 'Unknown error') . "\n";
        }
    } elseif ($httpCode == 302) {
        echo "↳ Redirect (authentication required)\n";
    } else {
        echo "✗ Error\n";
    }
}

echo "\n3. Checking JavaScript route in delivery schedule...\n";

// Check if the delivery schedule page loads
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/admin/deliveries');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Delivery schedule page: HTTP $httpCode ";
if ($httpCode == 200) {
    echo "✓ Accessible\n";
    
    // Check if the JavaScript function exists
    if (strpos($result, 'switchToCustomer') !== false) {
        echo "  JavaScript function found: ✓\n";
    } else {
        echo "  JavaScript function found: ✗\n";
    }
    
    // Check if the correct route is used
    if (strpos($result, '/admin/users/switch/') !== false) {
        echo "  Correct route used: ✓\n";
    } else {
        echo "  Correct route used: ✗\n";
    }
} else {
    echo "✗ Not accessible\n";
}

echo "\nTest completed!\n";
