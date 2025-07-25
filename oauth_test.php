<?php
require 'vendor/autoload.php';

// Get credentials from .env
$env = file_get_contents('.env');
preg_match('/FARMOS_URL=(.+)/', $env, $url_match);
preg_match('/FARMOS_USERNAME=(.+)/', $env, $username_match);
preg_match('/FARMOS_PASSWORD=(.+)/', $env, $password_match);
preg_match('/FARMOS_CLIENT_ID=(.+)/', $env, $client_id_match);
preg_match('/FARMOS_CLIENT_SECRET=(.+)/', $env, $client_secret_match);

$url = trim($url_match[1]);
$username = trim($username_match[1]);
$password = trim($password_match[1]);
$client_id = trim($client_id_match[1]);
$client_secret = trim($client_secret_match[1]);

echo "=== FarmOS OAuth Debug Test ===\n";
echo "URL: $url\n";
echo "Username: $username\n";
echo "Client ID: $client_id\n";
echo "Client Secret: " . substr($client_secret, 0, 10) . "...\n\n";

// Test OAuth token request
$client = new \GuzzleHttp\Client(['base_uri' => $url]);

try {
    echo "Testing OAuth token request...\n";
    $response = $client->post('/oauth/token', [
        'form_params' => [
            'grant_type' => 'password',
            'username' => $username,
            'password' => $password,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
        ],
        'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded'
        ],
        'timeout' => 30
    ]);
    
    $data = json_decode($response->getBody(), true);
    echo "✅ SUCCESS! Got access token\n";
    echo "Token: " . substr($data['access_token'], 0, 20) . "...\n";
    echo "Expires in: " . $data['expires_in'] . " seconds\n";
    
} catch (\GuzzleHttp\Exception\ClientException $e) {
    echo "❌ CLIENT ERROR: " . $e->getResponse()->getStatusCode() . "\n";
    echo "Response: " . $e->getResponse()->getBody() . "\n";
    
    // Parse the error response
    $error_data = json_decode($e->getResponse()->getBody(), true);
    if (isset($error_data['error'])) {
        echo "\nError Details:\n";
        echo "- Error: " . $error_data['error'] . "\n";
        echo "- Description: " . ($error_data['error_description'] ?? 'No description') . "\n";
    }
} catch (Exception $e) {
    echo "❌ GENERAL ERROR: " . $e->getMessage() . "\n";
}
?>
