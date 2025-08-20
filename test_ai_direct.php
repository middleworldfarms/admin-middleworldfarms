<?php

// Direct test of AI service from Laravel
require_once 'vendor/autoload.php';

use App\Services\HolisticAICropService;

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Initialize Laravel
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

echo "=== Direct AI Service Test ===\n";
echo "Testing Phi-3 AI from Laravel environment...\n\n";

try {
    $aiService = new HolisticAICropService();
    
    $testQuery = "What's the optimal harvest window for Brussels Sprouts variety F1 Doric in temperate climate?";
    
    echo "Query: $testQuery\n\n";
    echo "Calling AI service...\n";
    
    $startTime = microtime(true);
    $result = $aiService->getOptimalHarvestWindow('Brussels Sprouts', 'Brussels Sprout F1 Doric', 'test location', [
        'climate_zone' => 'temperate',
        'current_date' => date('Y-m-d')
    ]);
    $endTime = microtime(true);
    
    $duration = round(($endTime - $startTime), 2);
    
    echo "\n=== Results (took {$duration}s) ===\n";
    print_r($result);
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
