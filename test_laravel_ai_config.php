#!/usr/bin/env php
<?php

// Quick test to check if Laravel can now reach Phi-3 AI properly
echo "🧪 Testing Laravel AI Configuration Fix\n";
echo "=====================================\n\n";

// Set the environment 
putenv('APP_ENV=production');

// Load Laravel
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

// Test the actual configuration
$aiUrl = config('services.holistic_ai.url', 'NOT_SET');
echo "📝 AI Service URL: {$aiUrl}\n";

// Test if we can reach the AI service
echo "🔄 Testing AI service connection...\n";

$healthCheck = @file_get_contents('http://localhost:8005/health');
if ($healthCheck) {
    $health = json_decode($healthCheck, true);
    echo "✅ AI Service: " . ($health['status'] ?? 'unknown') . "\n";
    echo "🤖 Model: " . ($health['model'] ?? 'unknown') . "\n";
} else {
    echo "❌ AI Service: Not responding\n";
}

// Test the actual endpoint URL that Laravel will use
$testUrl = $aiUrl . '/ask';
echo "🎯 Full endpoint URL: {$testUrl}\n";

// Quick test request
echo "⚡ Testing with quick request...\n";
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode(['question' => 'Quick test']),
        'timeout' => 10
    ]
]);

$startTime = microtime(true);
$response = @file_get_contents($testUrl, false, $context);
$endTime = microtime(true);

if ($response) {
    echo "✅ SUCCESS: Got response in " . round($endTime - $startTime, 2) . " seconds\n";
    $data = json_decode($response, true);
    echo "📄 Response type: " . ($data ? 'JSON' : 'Raw text') . "\n";
} else {
    echo "❌ FAILED: No response from {$testUrl}\n";
    echo "🔍 Check if URL is correct and service is running\n";
}

echo "\n🎯 Next steps:\n";
echo "1. If success: Your Laravel should now get real Phi-3 responses\n";
echo "2. If failed: Check if Phi-3 service is running on port 8005\n";
echo "3. Try refreshing your browser page (hard refresh: Ctrl+F5)\n";

?>
