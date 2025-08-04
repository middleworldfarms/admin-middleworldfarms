<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Debugging FarmOS OAuth2 Token Usage...\n\n";

try {
    // Create fresh instance of FarmOS API service
    $farmOSApi = new \App\Services\FarmOSApiService();
    
    echo "🔐 Step 1: Testing authentication...\n";
    $auth = $farmOSApi->authenticate();
    echo "Authentication result: " . ($auth ? "✅ SUCCESS" : "❌ FAILED") . "\n";
    
    // Use reflection to access private token property
    $reflection = new ReflectionClass($farmOSApi);
    $tokenProperty = $reflection->getProperty('token');
    $tokenProperty->setAccessible(true);
    $token = $tokenProperty->getValue($farmOSApi);
    
    echo "Token after authentication: " . ($token ? "✅ SET (" . substr($token, 0, 20) . "...)" : "❌ NULL") . "\n\n";
    
    if ($token) {
        echo "🌱 Step 2: Testing manual API call with token...\n";
        
        // Get the HTTP client
        $clientProperty = $reflection->getProperty('client');
        $clientProperty->setAccessible(true);
        $client = $clientProperty->getValue($farmOSApi);
        
        try {
            $response = $client->get('/api/taxonomy_term/plant_type', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/vnd.api+json',
                ]
            ]);
            
            $statusCode = $response->getStatusCode();
            echo "✅ Manual API call successful! Status: $statusCode\n";
            
            $data = json_decode($response->getBody(), true);
            if (isset($data['data'])) {
                echo "✅ Found " . count($data['data']) . " crop types\n\n";
            }
            
        } catch (\Exception $e) {
            echo "❌ Manual API call failed: " . $e->getMessage() . "\n\n";
        }
        
        echo "🧪 Step 3: Testing getCropTypes method...\n";
        try {
            $cropTypes = $farmOSApi->getCropTypes();
            echo "✅ getCropTypes succeeded!\n";
        } catch (\Exception $e) {
            echo "❌ getCropTypes failed: " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";
