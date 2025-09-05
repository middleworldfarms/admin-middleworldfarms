<?php

require_once 'vendor/autoload.php';

// Simple test to check field work conditions
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create service instance
$weatherService = new App\Services\WeatherService();

echo "Testing Field Work Conditions...\n\n";

try {
    $conditions = $weatherService->getFieldWorkConditions(5);
    
    echo "Field Work Conditions for Next 5 Days:\n";
    echo "=====================================\n\n";
    
    foreach ($conditions as $day => $dayConditions) {
        echo "Day $day:\n";
        echo "  Spraying: " . ($dayConditions['spraying']['suitable'] ? 'SUITABLE' : 'NOT SUITABLE') . "\n";
        echo "    Reason: " . $dayConditions['spraying']['reason'] . "\n";
        echo "  Planting: " . ($dayConditions['planting']['suitable'] ? 'SUITABLE' : 'NOT SUITABLE') . "\n";
        echo "    Reason: " . $dayConditions['planting']['reason'] . "\n";
        echo "  Harvesting: " . ($dayConditions['harvesting']['suitable'] ? 'SUITABLE' : 'NOT SUITABLE') . "\n";
        echo "    Reason: " . $dayConditions['harvesting']['reason'] . "\n";
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
