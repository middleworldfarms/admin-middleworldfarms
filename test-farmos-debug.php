<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';

// Boot the application
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = $app->make('App\Services\FarmOSApiService');

echo "=== FarmOS Harvest Data Debug ===\n";

try {
    $harvests = $service->getHarvestLogs();
    echo "Harvest data type: " . gettype($harvests) . "\n";
    echo "Harvest count: " . (is_array($harvests) ? count($harvests) : 'N/A') . "\n";
    
    if (!empty($harvests)) {
        echo "\nFirst harvest record:\n";
        echo json_encode(array_slice($harvests, 0, 1), JSON_PRETTY_PRINT) . "\n";
        
        echo "\nFirst harvest structure:\n";
        $first = $harvests[0] ?? null;
        if ($first) {
            echo "Type: " . gettype($first) . "\n";
            if (is_array($first)) {
                echo "Keys: " . implode(', ', array_keys($first)) . "\n";
            } elseif (is_object($first)) {
                echo "Properties: " . implode(', ', array_keys(get_object_vars($first))) . "\n";
            }
        }
    } else {
        echo "No harvest data found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Expected Dashboard Data Format ===\n";
echo "The dashboard expects objects with properties:\n";
echo "- crop_name (string)\n";
echo "- crop_type (string)\n";
echo "- formatted_quantity (string)\n";
echo "- harvest_date (Carbon date object)\n";
echo "- is_today (boolean)\n";
