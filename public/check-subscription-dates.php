<?php
// Debug script to check date fields in subscription data

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\WpApiService;

// Get the WpApiService
$wpApi = app(WpApiService::class);

echo "=== Checking Date Fields in Subscription Data ===\n\n";

try {
    // Fetch subscriptions data
    $subscriptions = $wpApi->getDeliveryScheduleData(5);
    
    foreach ($subscriptions as $i => $sub) {
        echo "Subscription #{$i} (ID: {$sub['id']}):\n";
        
        // Show all date-related fields
        $dateFields = [
            'date_created',
            'date_created_gmt', 
            'date_modified',
            'date_modified_gmt',
            'next_payment_date',
            'next_payment_date_gmt',
            'last_payment_date',
            'last_payment_date_gmt',
            'start_date',
            'start_date_gmt',
            'trial_end_date',
            'trial_end_date_gmt',
            'end_date',
            'end_date_gmt'
        ];
        
        foreach ($dateFields as $field) {
            if (isset($sub[$field]) && !empty($sub[$field])) {
                echo "  {$field}: {$sub[$field]}\n";
            }
        }
        
        // Show frequency info
        $frequency = 'Unknown';
        if (isset($sub['billing_period']) && strtolower($sub['billing_period']) === 'week') {
            $interval = intval($sub['billing_interval'] ?? 1);
            $frequency = $interval === 2 ? 'Fortnightly' : 'Weekly';
        }
        echo "  frequency: {$frequency}\n";
        echo "  billing_period: " . ($sub['billing_period'] ?? 'Not set') . "\n";
        echo "  billing_interval: " . ($sub['billing_interval'] ?? 'Not set') . "\n";
        echo "  status: {$sub['status']}\n";
        echo "\n";
        
        if ($i >= 2) break; // Show only first 3 for brevity
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
