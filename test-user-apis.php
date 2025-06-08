<?php
// Quick API test to determine which user switching endpoint works

// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', trim($line), 2);
            $_ENV[$key] = trim($value, '"\'');
        }
    }
}

$apis = [
    'MWF Custom API' => [
        'url' => 'https://middleworldfarms.org/wp-json/mwf/v1/users',
        'headers' => ['X-WC-API-Key: ' . ($_ENV['MWF_API_KEY'] ?? 'Ffsh8yhsuZEGySvLrP0DihCDDwhPwk4h')]
    ],
    'WordPress Users API' => [
        'url' => 'https://middleworldfarms.org/wp-json/wp/v2/users',
        'headers' => []
    ],
    'WooCommerce Customers' => [
        'url' => 'https://middleworldfarms.org/wp-json/wc/v3/customers?per_page=1',
        'auth' => [$_ENV['WOOCOMMERCE_CONSUMER_KEY'] ?? '', $_ENV['WOOCOMMERCE_CONSUMER_SECRET'] ?? '']
    ]
];

echo "=== API ENDPOINT TESTING ===\n\n";

foreach ($apis as $name => $config) {
    echo "Testing: $name\n";
    echo "URL: {$config['url']}\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $config['headers'] ?? []);
    
    if (isset($config['auth'])) {
        curl_setopt($ch, CURLOPT_USERPWD, implode(':', $config['auth']));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Status: $httpCode\n";
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        echo "✅ SUCCESS - Found " . (is_array($data) ? count($data) : 'data') . " records\n";
    } else {
        echo "❌ FAILED\n";
    }
    echo "---\n";
}
