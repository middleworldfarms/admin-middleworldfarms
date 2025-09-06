<?php

require_once 'vendor/autoload.php';

// Simple test to verify variety lookup
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\PlantVariety;
use Illuminate\Support\Facades\Log;

echo "Testing Carrot F1 Eskimo Lookup...\n\n";

// Test exact match
$variety = 'Carrot F1 Eskimo';
echo "Looking for exact match: '{$variety}'\n";
$plantVariety = PlantVariety::where('name', $variety)->first();

if ($plantVariety) {
    echo "✅ FOUND: " . $plantVariety->name . "\n";
    echo "   Harvest Start: " . $plantVariety->harvest_start . "\n";
    echo "   Harvest End: " . $plantVariety->harvest_end . "\n";
    echo "   Window Days: " . $plantVariety->harvest_window_days . "\n";
    
    // Test date conversion
    $year = 2026;
    echo "\nTesting date conversion for year {$year}:\n";
    
    if ($plantVariety->harvest_start) {
        try {
            $date = \Carbon\Carbon::parse($plantVariety->harvest_start);
            $planningDate = \Carbon\Carbon::create($year, $date->month, $date->day);
            echo "   Start Date: " . $planningDate->format('Y-m-d') . "\n";
        } catch (Exception $e) {
            echo "   Start Date Error: " . $e->getMessage() . "\n";
        }
    }
    
    if ($plantVariety->harvest_end) {
        try {
            $date = \Carbon\Carbon::parse($plantVariety->harvest_end);
            $planningDate = \Carbon\Carbon::create($year, $date->month, $date->day);
            echo "   End Date: " . $planningDate->format('Y-m-d') . "\n";
        } catch (Exception $e) {
            echo "   End Date Error: " . $e->getMessage() . "\n";
        }
    }
    
} else {
    echo "❌ NOT FOUND\n";
    
    // Try partial match
    echo "\nTrying partial match...\n";
    $partial = PlantVariety::where('name', 'LIKE', "%{$variety}%")->first();
    if ($partial) {
        echo "✅ PARTIAL FOUND: " . $partial->name . "\n";
    } else {
        echo "❌ NO PARTIAL MATCH\n";
    }
}

echo "\nDone.\n";
