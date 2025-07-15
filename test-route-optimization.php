<?php
/**
 * Test route optimization with sample data
 */

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing Route Optimization with Sample Data ===\n";

try {
    $routeService = app('App\Services\RouteOptimizationService');
    
    // Sample delivery data
    $sampleDeliveries = [
        [
            'id' => 1,
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'address' => 'High Street, Lincoln, LN1 1AA',
            'products' => [['name' => 'Vegetable Box']]
        ],
        [
            'id' => 2,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'address' => 'Market Square, Lincoln, LN2 2BB',
            'products' => [['name' => 'Fruit Box']]
        ],
        [
            'id' => 3,
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
            'address' => 'Castle Hill, Lincoln, LN3 3CC',
            'products' => [['name' => 'Mixed Box']]
        ]
    ];
    
    echo "Testing with " . count($sampleDeliveries) . " sample deliveries\n";
    
    // Test geocoding first
    echo "\nTesting geocoding...\n";
    foreach ($sampleDeliveries as $delivery) {
        $coords = $routeService->geocodeAddress($delivery['address']);
        if ($coords) {
            echo "✓ {$delivery['address']} -> {$coords['lat']}, {$coords['lng']}\n";
        } else {
            echo "✗ Failed to geocode: {$delivery['address']}\n";
        }
    }
    
    // Test route optimization
    echo "\nTesting route optimization...\n";
    $result = $routeService->optimizeRoute($sampleDeliveries);
    
    if (isset($result['error'])) {
        echo "✗ Route optimization failed: " . $result['error'] . "\n";
    } else {
        echo "✓ Route optimization successful!\n";
        echo "  Total distance: " . ($result['total_distance'] ?? 'Unknown') . "\n";
        echo "  Total duration: " . ($result['total_duration'] ?? 'Unknown') . "\n";
        echo "  Optimization source: " . ($result['optimization_source'] ?? 'Unknown') . "\n";
        echo "  Start location: " . ($result['start_location'] ?? 'Unknown') . "\n";
        
        if (isset($result['optimized_deliveries'])) {
            echo "  Optimized order:\n";
            foreach ($result['optimized_deliveries'] as $index => $delivery) {
                echo "    " . ($index + 1) . ". {$delivery['name']} - {$delivery['address']}\n";
            }
        }
        
        if (isset($result['wp_maps'])) {
            echo "  WP Go Maps integration:\n";
            echo "    Map ID: " . $result['wp_maps']['map_id'] . "\n";
            echo "    Shortcode: " . $result['wp_maps']['shortcode'] . "\n";
            if (isset($result['wp_maps']['shareable_link'])) {
                echo "    Shareable link: " . $result['wp_maps']['shareable_link'] . "\n";
            }
        }
    }
    
    echo "\n=== Route Optimization Test Complete ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
