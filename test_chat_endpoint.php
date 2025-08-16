<?php

require_once __DIR__.'/vendor/autoload.php';

use Illuminate\Http\Request;
use App\Http\Controllers\Admin\SuccessionPlanningController;
use App\Services\FarmOSApi;
use App\Services\HolisticAICropService;

// Create Laravel app instance
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing chat endpoint directly...\n";

try {
    // Create controller instance
    $controller = new SuccessionPlanningController(
        new FarmOSApi(),
        new HolisticAICropService()
    );
    
    // Create request
    $request = new Request();
    $request->merge([
        'message' => 'When should I plant lettuce in spring?',
        'crop_type' => 'lettuce',
        'season' => 'spring',
        'context' => 'succession_planning'
    ]);
    
    echo "Calling chat method...\n";
    $startTime = microtime(true);
    
    $response = $controller->chat($request);
    
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime), 2);
    
    $responseData = json_decode($response->getContent(), true);
    
    echo "Response received in {$duration} seconds:\n";
    echo "Success: " . ($responseData['success'] ? 'YES' : 'NO') . "\n";
    echo "Source: " . ($responseData['source'] ?? 'N/A') . "\n";
    echo "Has Answer: " . (isset($responseData['answer']) ? 'YES' : 'NO') . "\n";
    echo "Answer Length: " . (isset($responseData['answer']) ? strlen($responseData['answer']) : 0) . " chars\n";
    
    if (isset($responseData['answer'])) {
        echo "Answer Preview: " . substr($responseData['answer'], 0, 200) . "...\n";
    }
    
    if (!$responseData['success']) {
        echo "Error: " . ($responseData['message'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ':' . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
