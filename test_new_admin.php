<?php
require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$farmosUrl = $_ENV['FARMOS_URL'];
$username = $_ENV['FARMOS_USERNAME'];
$password = $_ENV['FARMOS_PASSWORD'];

echo "Testing farmOS API with new admin user:\n";
echo "URL: $farmosUrl\n";
echo "Username: $username\n";
echo "Password: " . str_repeat('*', strlen($password)) . "\n\n";

// Test basic connectivity
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $farmosUrl . '/api');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/vnd.api+json',
    'Content-Type: application/vnd.api+json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "API Root Response:\n";
echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
}
if ($response) {
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "Raw Response: " . substr($response, 0, 500) . "\n";
    }
}

echo "\n" . str_repeat('=', 50) . "\n";

// Test land assets specifically
echo "Testing land assets endpoint:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $farmosUrl . '/api/asset/land');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/vnd.api+json',
    'Content-Type: application/vnd.api+json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Land Assets Response:\n";
echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
}
if ($response) {
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "Number of land assets found: " . (isset($data['data']) ? count($data['data']) : 0) . "\n";
        if (isset($data['data']) && count($data['data']) > 0) {
            echo "First land asset: " . json_encode($data['data'][0], JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "Raw Response: " . substr($response, 0, 500) . "\n";
    }
}

echo "\n" . str_repeat('=', 50) . "\n";

// Test users endpoint
echo "Testing users endpoint:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $farmosUrl . '/api/user/user');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/vnd.api+json',
    'Content-Type: application/vnd.api+json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Users Response:\n";
echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
}
if ($response) {
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "Number of users found: " . (isset($data['data']) ? count($data['data']) : 0) . "\n";
        if (isset($data['data']) && count($data['data']) > 0) {
            foreach ($data['data'] as $user) {
                echo "User: " . ($user['attributes']['name'] ?? 'Unknown') . " (ID: " . $user['id'] . ")\n";
            }
        }
    } else {
        echo "Raw Response: " . substr($response, 0, 500) . "\n";
    }
}
?>
