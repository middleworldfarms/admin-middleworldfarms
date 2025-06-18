<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Test WpApiService method directly
try {
    $wpApi = app('App\Services\WpApiService');
    
    echo "=== Testing WpApiService::getDeliveryScheduleData ===\n";
    $result = $wpApi->getDeliveryScheduleData(20);
    
    echo "Result type: " . gettype($result) . "\n";
    echo "Result count: " . count($result) . "\n";
    
    if (is_array($result) && !empty($result)) {
        echo "First item keys: " . implode(', ', array_keys($result[0])) . "\n";
        echo "First item status: " . ($result[0]['status'] ?? 'no status') . "\n";
        echo "First item shipping_total: " . ($result[0]['shipping_total'] ?? 'no shipping') . "\n";
    } else {
        echo "No results or not an array\n";
        var_dump($result);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
