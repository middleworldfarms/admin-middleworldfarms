<?php

/**
 * 3CX API Connection Test Script
 * Run this after setting up your 3CX API credentials
 */

// Your 3CX server details (update these)
$threeCXServer = 'your-server-ip-or-domain';
$threeCXPort = '5015'; // Web Client API port
$username = 'your-username';
$password = 'your-password';

echo "Testing 3CX API Connection...\n";
echo "Server: {$threeCXServer}:{$threeCXPort}\n";
echo "Username: {$username}\n";
echo "========================\n\n";

// Test 1: Basic connectivity
echo "1. Testing basic connectivity...\n";
$testUrl = "http://{$threeCXServer}:{$threeCXPort}/webclient/api/Login";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'Username' => $username,
    'Password' => $password
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    echo "❌ Connection failed - Check server address and port\n";
} else {
    echo "✅ Connection successful (HTTP {$httpCode})\n";
    echo "Response: " . substr($response, 0, 200) . "...\n";
}

echo "\n========================\n";

// Test 2: Alternative API endpoint
echo "2. Testing alternative API endpoint...\n";
$altUrl = "http://{$threeCXServer}:5000/api/activate";

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $altUrl);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_TIMEOUT, 10);

$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

if ($response2 !== false) {
    echo "✅ Alternative API endpoint available (HTTP {$httpCode2})\n";
} else {
    echo "⚠️  Alternative API endpoint not available\n";
}

echo "\n========================\n";
echo "Next Steps:\n";
echo "1. Update the server details in this script\n";
echo "2. Run: php test-3cx-api.php\n";
echo "3. If successful, add credentials to your .env file\n";
echo "========================\n";

?>
