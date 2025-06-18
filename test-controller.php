<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Admin\DeliveryController;
use App\Services\WpApiService;
use Illuminate\Http\Request;

try {
    $wpApi = new WpApiService();
    $request = new Request();
    $controller = new DeliveryController();
    
    echo "Testing controller method...\n";
    $response = $controller->index($request, $wpApi);
    
    echo "Controller method executed successfully!\n";
    echo "Response type: " . get_class($response) . "\n";
    
    if (method_exists($response, 'getData')) {
        $data = $response->getData();
        echo "Data keys: " . implode(', ', array_keys($data)) . "\n";
        
        if (isset($data['totalDeliveries'])) {
            echo "Total deliveries: " . $data['totalDeliveries'] . "\n";
        }
        
        if (isset($data['totalCollections'])) {
            echo "Total collections: " . $data['totalCollections'] . "\n";
        }
        
        if (isset($data['scheduleData'])) {
            echo "Schedule data structure:\n";
            if (isset($data['scheduleData']['success'])) {
                echo "  Success: " . ($data['scheduleData']['success'] ? 'true' : 'false') . "\n";
            }
            if (isset($data['scheduleData']['data'])) {
                echo "  Data count: " . count($data['scheduleData']['data']) . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
