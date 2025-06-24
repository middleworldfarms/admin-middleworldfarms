<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Get the Laravel application instance
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Use the DeliveryController directly
use App\Http\Controllers\Admin\DeliveryController;
use App\Services\WpApiService;

// Create instances of the services we need
$deliveryController = app(DeliveryController::class);
$wpApi = app(WpApiService::class);

// Test the collection days functionality directly
try {
    // Call the testCollectionDays method directly
    $response = $deliveryController->testCollectionDays();
    $result = $response->getData(true);
    
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
    
    // This code is not reached since we exit after sending the response above
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
