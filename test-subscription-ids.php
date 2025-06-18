<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\WpApiService;

echo "Testing subscription ID mapping...\n";

$wpApi = new WpApiService();

// Get some subscription data
$rawData = $wpApi->getDeliveryScheduleData(3);

echo "Got " . count($rawData) . " subscriptions from API\n\n";

// Display the subscription IDs vs customer IDs
foreach ($rawData as $index => $sub) {
    echo "Subscription #{$index}:\n";
    echo "  Subscription ID: " . ($sub['id'] ?? 'N/A') . "\n";
    echo "  Customer ID: " . ($sub['customer_id'] ?? 'N/A') . "\n";
    echo "  Name: " . ($sub['billing']['first_name'] ?? '') . " " . ($sub['billing']['last_name'] ?? '') . "\n";
    echo "  Status: " . ($sub['status'] ?? 'N/A') . "\n";
    
    // Check for customer_week_type in meta_data
    $weekType = 'Not set';
    if (isset($sub['meta_data'])) {
        foreach ($sub['meta_data'] as $meta) {
            if ($meta['key'] === 'customer_week_type') {
                $weekType = $meta['value'];
                break;
            }
        }
    }
    echo "  Week Type: " . $weekType . "\n\n";
}
