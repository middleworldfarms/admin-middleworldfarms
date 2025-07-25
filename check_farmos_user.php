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

echo "=== FarmOS User Permissions Check ===\n";
echo "URL: $url\n";
echo "Username: $username\n\n";

$client = new \GuzzleHttp\Client(['base_uri' => $url]);

try {
    // Get OAuth token
    echo "Getting OAuth token...\n";
    $response = $client->post('/oauth/token', [
        'form_params' => [
            'grant_type' => 'password',
            'username' => $username,
            'password' => $password,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
        ]
    ]);
    $data = json_decode($response->getBody(), true);
    $token = $data['access_token'];
    echo "✅ Got token\n\n";
    
    // Test user info endpoint
    echo "Checking user details...\n";
    $userResponse = $client->get('/api/user/user', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/vnd.api+json'
        ]
    ]);
    $userData = json_decode($userResponse->getBody(), true);
    
    if (isset($userData['data'])) {
        foreach ($userData['data'] as $user) {
            if ($user['attributes']['name'] === $username) {
                echo "User ID: " . $user['id'] . "\n";
                echo "Name: " . $user['attributes']['name'] . "\n";
                echo "Email: " . ($user['attributes']['mail'] ?? 'N/A') . "\n";
                echo "Status: " . ($user['attributes']['status'] ? 'Active' : 'Inactive') . "\n";
                
                if (isset($user['relationships']['roles']['data'])) {
                    echo "Role IDs: ";
                    $roleIds = [];
                    foreach ($user['relationships']['roles']['data'] as $role) {
                        $roleIds[] = $role['id'];
                    }
                    echo implode(', ', $roleIds) . "\n";
                    
                    // Get role details
                    echo "\nFetching role details...\n";
                    try {
                        $rolesResponse = $client->get('/api/user_role/user_role', [
                            'headers' => [
                                'Authorization' => 'Bearer ' . $token,
                                'Accept' => 'application/vnd.api+json'
                            ]
                        ]);
                        $rolesData = json_decode($rolesResponse->getBody(), true);
                        
                        if (isset($rolesData['data'])) {
                            echo "Available roles:\n";
                            foreach ($rolesData['data'] as $role) {
                                $isUserRole = in_array($role['id'], $roleIds) ? '✅' : '  ';
                                echo "$isUserRole " . $role['attributes']['label'] . " (ID: " . $role['id'] . ")\n";
                            }
                        }
                    } catch (Exception $e) {
                        echo "Could not fetch role details: " . $e->getMessage() . "\n";
                    }
                }
                break;
            }
        }
    }
    
    // Test asset permissions
    echo "\n=== Testing Asset Access ===\n";
    try {
        $assetResponse = $client->get('/api/asset/land', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/vnd.api+json'
            ]
        ]);
        $assetData = json_decode($assetResponse->getBody(), true);
        echo "✅ Can access land assets! Found " . (isset($assetData['data']) ? count($assetData['data']) : 0) . " assets\n";
        
        if (isset($assetData['data']) && count($assetData['data']) > 0) {
            $asset = $assetData['data'][0];
            echo "First asset: " . $asset['attributes']['name'] . " (ID: " . $asset['id'] . ")\n";
            if (isset($asset['attributes']['geometry'])) {
                echo "✅ Has geometry data\n";
            } else {
                echo "❌ No geometry data\n";
            }
        }
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        echo "❌ Asset access failed: " . $e->getResponse()->getStatusCode() . "\n";
        echo "Response: " . $e->getResponse()->getBody() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
