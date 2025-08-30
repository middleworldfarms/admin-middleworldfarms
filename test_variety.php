<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PlantVariety;
use App\Services\FarmOSApi;
use App\Services\AI\SymbiosisAIService;
use App\Http\Controllers\Admin\SuccessionPlanningController;
use Illuminate\Http\Request;

// Create a mock request
$request = new Request();

// Create controller with dependencies
$farmOSApi = app(FarmOSApi::class);
$symbiosisAI = app(SymbiosisAIService::class);
$controller = new SuccessionPlanningController($farmOSApi, $symbiosisAI);

// Test the getVariety method
try {
    $varietyId = '3d783526-709a-4bf4-a8fe-512996bee25f'; // Brussels Sprout F1 Doric FarmOS ID
    $response = $controller->getVariety($request, $varietyId);

    if ($response->getStatusCode() === 200) {
        $data = $response->getData();
        echo "SUCCESS: Variety data retrieved\n";
        echo "Name: " . ($data->name ?? 'N/A') . "\n";
        echo "Harvest Start: " . ($data->harvest_start ?? 'N/A') . "\n";
        echo "Harvest End: " . ($data->harvest_end ?? 'N/A') . "\n";
        echo "Days to Harvest: " . ($data->days_to_harvest ?? 'N/A') . "\n";
        echo "Yield Peak: " . ($data->yield_peak ?? 'N/A') . "\n";
        echo "Notes: " . ($data->notes ?? 'N/A') . "\n";
    } else {
        echo "ERROR: HTTP " . $response->getStatusCode() . "\n";
        $data = $response->getData();
        echo "Message: " . ($data->error ?? 'Unknown error') . "\n";
    }
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
