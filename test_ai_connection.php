<?php
// Simple test script to verify AI service connection

echo "Testing AI Service Connection...\n";

$aiServiceUrl = 'http://localhost:8005/ask';

$testData = [
    'question' => 'What are the best companion plants for tomatoes?',
    'crop_type' => 'tomato',
    'context' => 'succession_planning'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $aiServiceUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
}

if ($response) {
    echo "Response: " . substr($response, 0, 500) . "\n";
    
    $json = json_decode($response, true);
    if ($json && isset($json['success'])) {
        echo "✅ AI Service is working!\n";
        echo "Answer preview: " . substr($json['answer'] ?? 'No answer', 0, 100) . "...\n";
    } else {
        echo "❌ Response format issue\n";
    }
} else {
    echo "❌ No response received\n";
}
?>
