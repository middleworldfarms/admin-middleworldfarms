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

// Call the debugFrequencies method directly
try {
    $result = $deliveryController->debugFrequencies();
    
    // Convert the response to an array
    $data = json_decode($result->getContent(), true);
    
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
