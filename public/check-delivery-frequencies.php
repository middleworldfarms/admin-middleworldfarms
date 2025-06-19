<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Get the Laravel application instance
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Use the WpApiService directly
use App\Services\WpApiService;

// Create a service instance
$wpApi = app(WpApiService::class);

// Get raw subscription data
try {
    $rawData = $wpApi->getDeliveryScheduleData(50); // Limit to 50 subscriptions
    
    // Count frequencies for deliveries and collections
    $deliveryFrequencies = [];
    $collectionFrequencies = [];
    
    // Process deliveries
    if (isset($rawData['deliveries'])) {
        foreach ($rawData['deliveries'] as $delivery) {
            $frequency = $delivery['frequency'] ?? 'Unknown';
            if (!isset($deliveryFrequencies[$frequency])) {
                $deliveryFrequencies[$frequency] = 0;
            }
            $deliveryFrequencies[$frequency]++;
        }
    }
    
    // Process collections
    if (isset($rawData['collections'])) {
        foreach ($rawData['collections'] as $collection) {
            $frequency = $collection['frequency'] ?? 'Unknown';
            if (!isset($collectionFrequencies[$frequency])) {
                $collectionFrequencies[$frequency] = 0;
            }
            $collectionFrequencies[$frequency]++;
        }
    }
    
    // Show sample deliveries with different frequencies
    $deliverySamples = [];
    if (isset($rawData['deliveries'])) {
        foreach ($rawData['deliveries'] as $delivery) {
            $deliverySamples[] = [
                'id' => $delivery['id'],
                'customer_id' => $delivery['customer_id'],
                'name' => $delivery['name'],
                'frequency' => $delivery['frequency'],
                'customer_week_type' => $delivery['customer_week_type'] ?? 'Not set',
                'products' => array_map(function($product) {
                    return [
                        'name' => $product['name'],
                        'quantity' => $product['quantity']
                    ];
                }, $delivery['products'] ?? [])
            ];
            
            // Just get 3 samples
            if (count($deliverySamples) >= 3) break;
        }
    }
    
    // Find a fortnightly delivery specifically
    $fortnightlyDelivery = null;
    if (isset($rawData['deliveries'])) {
        foreach ($rawData['deliveries'] as $delivery) {
            if (strtolower($delivery['frequency'] ?? '') === 'fortnightly') {
                $fortnightlyDelivery = $delivery;
                break;
            }
        }
    }
    
    // Show raw subscriptions
    $rawSubscriptions = $rawData[0] ?? [];
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'delivery_count' => count($rawData['deliveries'] ?? []),
        'collection_count' => count($rawData['collections'] ?? []),
        'delivery_frequencies' => $deliveryFrequencies,
        'collection_frequencies' => $collectionFrequencies,
        'delivery_samples' => $deliverySamples,
        'fortnightly_delivery' => $fortnightlyDelivery
    ], JSON_PRETTY_PRINT | JSON_PARTIAL_OUTPUT_ON_ERROR);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
