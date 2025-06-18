<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\WpApiService;
use App\Http\Controllers\Admin\DeliveryController;

try {
    echo "Testing week logic transformation..." . PHP_EOL;

$wpApi = new WpApiService();
    $controller = new DeliveryController();
    
    echo "Testing week logic transformation...\n";
    
    // Get some raw data
    $rawData = $wpApi->getDeliveryScheduleData(3);
    echo "Got " . count($rawData) . " subscriptions from API\n";
    
    // Check if any have fortnightly frequency
    $fortnightlyCount = 0;
    foreach ($rawData as $sub) {
        if (isset($sub['line_items'][0]['meta_data'])) {
            foreach ($sub['line_items'][0]['meta_data'] as $meta) {
                if ($meta['key'] === 'frequency' && strtolower($meta['value']) === 'fortnightly') {
                    $fortnightlyCount++;
                    break;
                }
            }
        }
    }
    echo "Found {$fortnightlyCount} fortnightly subscriptions\n";
    
    // Use reflection to call the private transformScheduleData method
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('transformScheduleData');
    $method->setAccessible(true);
    
    $transformedData = $method->invoke($controller, $rawData, date('W'));
    
    echo "Transformation successful: " . ($transformedData['success'] ? 'YES' : 'NO') . "\n";
    
    // Check collections
    foreach ($transformedData['data'] as $date => $dateData) {
        if (!empty($dateData['collections'])) {
            foreach ($dateData['collections'] as $collection) {
                echo "\nCollection: " . ($collection['name'] ?? 'N/A') . "\n";
                echo "  Frequency: " . ($collection['frequency'] ?? 'N/A') . "\n";
                echo "  Customer Week Type: " . ($collection['customer_week_type'] ?? 'N/A') . "\n";
                echo "  Week Badge: " . ($collection['week_badge'] ?? 'N/A') . "\n";
                
                if (strtolower($collection['frequency'] ?? '') === 'fortnightly') {
                    echo "  *** FORTNIGHTLY CUSTOMER FOUND ***\n";
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
