<?php
// Test script to check WP User Meta API endpoint
require_once '../vendor/autoload.php';

// Get API credentials from .env file
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$wpApiUrl = $_ENV['WP_API_URL'] ?? 'https://middleworldfarms.org';
$integrationKey = $_ENV['WP_INTEGRATION_KEY'] ?? '';
$wcConsumerKey = $_ENV['WC_CONSUMER_KEY'] ?? '';
$wcConsumerSecret = $_ENV['WC_CONSUMER_SECRET'] ?? '';

// Helper function to make API requests with error handling
function makeRequest($url, $params = [], $headers = []) {
    $client = new GuzzleHttp\Client(['timeout' => 10]);
    
    try {
        echo "Making request to: $url\n";
        echo "Params: " . json_encode($params) . "\n";
        echo "Headers: " . json_encode($headers) . "\n";

        $response = $client->request('GET', $url, [
            'query' => $params,
            'headers' => $headers
        ]);
        
        return [
            'success' => true,
            'status_code' => $response->getStatusCode(),
            'body' => json_decode($response->getBody(), true)
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

echo "WP User Meta API Test\n";

// Test 1: Check if the user-meta endpoint exists using the integration key
echo "\nTest 1: Check Custom User Meta Endpoint\n";
$result1 = makeRequest($wpApiUrl . '/wp-json/mwf/v1/user-meta', 
    ['user_id' => '1', 'meta_key' => 'preferred_collection_day'],
    ['X-WC-API-Key' => $integrationKey]
);

print_r($result1);

// Test 2: Check WP REST API for user with meta
echo "\nTest 2: Check WP REST API\n";
$result2 = makeRequest($wpApiUrl . '/wp-json/wp/v2/users/1', 
    [], 
    [
        'Authorization' => 'Basic ' . base64_encode($wcConsumerKey . ':' . $wcConsumerSecret)
    ]
);

print_r($result2);

// Test 3: Check for a specific customer who is known to have a collection subscription
echo "\nTest 3: Check Known Collection User\n";
// Use a user ID that you know has collection subscriptions
$knownCollectionUserId = 100; // Replace with actual user ID
$result3 = makeRequest($wpApiUrl . '/wp-json/mwf/v1/user-meta', 
    ['user_id' => $knownCollectionUserId, 'meta_key' => 'preferred_collection_day'],
    ['X-WC-API-Key' => $integrationKey]
);

print_r($result3);
