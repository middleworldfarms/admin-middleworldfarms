<?php
// Quick JWT decoder to understand the token structure
$token = getenv('MET_OFFICE_SITE_SPECIFIC_KEY');
if (!$token) {
    echo "No token found\n";
    exit(1);
}

$parts = explode('.', $token);
if (count($parts) !== 3) {
    echo "Invalid JWT format\n";
    exit(1);
}

$payload = json_decode(base64_decode($parts[1]), true);
echo "=== JWT Token Payload ===\n";
echo "Subscribed APIs:\n";
foreach ($payload['subscribedAPIs'] ?? [] as $api) {
    echo "  - Name: " . ($api['name'] ?? 'unknown') . "\n";
    echo "    Context: " . ($api['context'] ?? 'unknown') . "\n";
    echo "    Version: " . ($api['version'] ?? 'unknown') . "\n";
    echo "    Tier: " . ($api['subscriptionTier'] ?? 'unknown') . "\n\n";
}
