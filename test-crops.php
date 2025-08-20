<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $farmOSApi = new App\Services\FarmOSApi();
    $cropData = $farmOSApi->getAvailableCropTypes();
    
    echo "Crop Types Count: " . count($cropData['types'] ?? []) . PHP_EOL;
    echo "Varieties Count: " . count($cropData['varieties'] ?? []) . PHP_EOL;
    
    if (!empty($cropData['types'])) {
        echo "First 10 crop types:" . PHP_EOL;
        foreach (array_slice($cropData['types'], 0, 10) as $crop) {
            echo "- " . $crop['name'] . " (ID: " . $crop['id'] . ")" . PHP_EOL;
        }
    }
    
    if (!empty($cropData['varieties'])) {
        echo "\nFirst 10 varieties:" . PHP_EOL;
        foreach (array_slice($cropData['varieties'], 0, 10) as $variety) {
            echo "- " . $variety['name'] . " (Parent: " . ($variety['parent_id'] ?? 'N/A') . ")" . PHP_EOL;
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}
