<?php

require_once 'vendor/autoload.php';

use Illuminate\Http\Request;
use App\Http\Controllers\Admin\SuccessionPlanningController;

// Simulate Laravel environment
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create the controller
$controller = new SuccessionPlanningController();

// Create a test request similar to what the frontend sends
$testData = [
    'question' => 'Please calculate optimal harvest window for this variety.

VARIETY: Carrot F1 Eskimo
CROP TYPE: carrot  
PLANNING YEAR: 2025
PLANTING DATE: 2025-05-01

CONTEXT - CARROT TIMING DATA:
- Most varieties: 70-120 days from seed to harvest
- Storage varieties: 100-130 days (planted spring/early summer)
- Baby carrots: 50-70 days from seed
- Spring plantings: March-May
- Fall harvest: September-November
- Cold hardy varieties can extend harvest into winter

VARIETY-SPECIFIC NOTES:
- Early varieties like "Early Nantes 2": May 1st - November 30th (214 days maximum)

ROOT VEGETABLES GENERAL:
- Baby harvest: 50-70 days after sowing
- Main harvest: 90-120 days after sowing  
- Extended harvest: Can stay in ground 180-250+ days with protection
- Winter storage: Many can be harvested through winter months

MAXIMUM HARVEST CALCULATION:
- Start: Earliest possible harvest date (baby stage)
- End: Latest possible harvest before quality deteriorates
- Consider storage, succession planting, winter protection

Return ONLY a JSON object with these exact keys:
{
  "maximum_start": "YYYY-MM-DD",
  "maximum_end": "YYYY-MM-DD", 
  "days_to_harvest": 60,
  "yield_peak": "YYYY-MM-DD",
  "notes": "Maximum possible harvest window for succession planning",
  "extended_window": {"max_extension_days": 45, "risk_level": "low"}
}

NO extra text. Calculate for 2025.',
    'crop_type' => 'carrot',
    'context' => [
        'crop' => 'carrot',
        'variety_name' => 'Carrot F1 Eskimo',
        'planning_year' => 2025,
        'planting_date' => '2025-05-01'
    ]
];

// Create request object
$request = Request::create('/admin/farmos/succession-planning/chat', 'POST', $testData);

try {
    echo "🧪 Testing chat endpoint with Carrot F1 Eskimo...\n\n";
    echo "Request data:\n";
    print_r($testData);
    echo "\n";
    
    // Call the chat method
    $response = $controller->chat($request);
    
    echo "Response:\n";
    echo $response->getContent();
    echo "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
