<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$jwt = $_ENV['MET_OFFICE_LAND_OBSERVATIONS_KEY'];

// Decode JWT (it's base64 encoded in 3 parts: header.payload.signature)
$parts = explode('.', $jwt);

if (count($parts) === 3) {
    $header = json_decode(base64_decode($parts[0]), true);
    $payload = json_decode(base64_decode($parts[1]), true);
    
    echo "=== Land Observations JWT Details ===\n\n";
    echo "Header:\n";
    print_r($header);
    echo "\nPayload:\n";
    print_r($payload);
    
    echo "\n=== Key Information ===\n";
    if (isset($payload['subscribedAPIs'])) {
        foreach ($payload['subscribedAPIs'] as $api) {
            echo "API Name: " . $api['name'] . "\n";
            echo "Context: " . $api['context'] . "\n";
            echo "Version: " . $api['version'] . "\n";
            echo "Publisher: " . $api['publisher'] . "\n";
            echo "Subscription Tier: " . $api['subscriptionTier'] . "\n";
        }
    }
}
