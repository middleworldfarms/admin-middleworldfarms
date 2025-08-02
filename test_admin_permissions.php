<?php
// Simple test script for admin user permissions
$farmosUrl = 'https://farmos.middleworldfarms.org';
$username = 'admin';
$password = 'WdxWWPSTy1asdvWw6BW5';

echo "Testing farmOS admin user permissions...\n\n";

// Test land assets
echo "1. Testing land assets access:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $farmosUrl . '/api/asset/land');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/vnd.api+json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response) {
    $data = json_decode($response, true);
    if (isset($data['data']) && count($data['data']) > 0) {
        echo "   ✅ SUCCESS: Found " . count($data['data']) . " land assets\n";
        echo "   First asset: " . ($data['data'][0]['attributes']['name'] ?? 'Unnamed') . "\n";
    } elseif (isset($data['meta']['omitted'])) {
        echo "   ❌ PERMISSION DENIED: " . count($data['meta']['omitted']) . " assets hidden\n";
        echo "   Need to assign proper roles to admin user in farmOS\n";
    } else {
        echo "   ⚠️  No land assets found\n";
    }
} else {
    echo "   ❌ CONNECTION FAILED\n";
}

echo "\n2. Testing plant assets access:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $farmosUrl . '/api/asset/plant');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/vnd.api+json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
if ($response) {
    $data = json_decode($response, true);
    if (isset($data['data'])) {
        echo "   ✅ SUCCESS: Found " . count($data['data']) . " plant assets\n";
    } elseif (isset($data['meta']['omitted'])) {
        echo "   ❌ PERMISSION DENIED: " . count($data['meta']['omitted']) . " assets hidden\n";
    } else {
        echo "   ⚠️  No plant assets found\n";
    }
} else {
    echo "   ❌ CONNECTION FAILED\n";
}

echo "\n3. Testing user profile access:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $farmosUrl . '/api/user/user');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/vnd.api+json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
if ($response) {
    $data = json_decode($response, true);
    if (isset($data['data'])) {
        echo "   ✅ SUCCESS: Found " . count($data['data']) . " users\n";
        foreach ($data['data'] as $user) {
            if ($user['attributes']['name'] === 'admin') {
                echo "   Admin user found with roles: " . json_encode($user['attributes']['roles'] ?? []) . "\n";
            }
        }
    }
} else {
    echo "   ❌ CONNECTION FAILED\n";
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "If land assets show permission denied, assign Manager/Administrator role to admin user in farmOS\n";
?>
