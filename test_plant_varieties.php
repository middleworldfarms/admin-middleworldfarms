<?php

require_once 'vendor/autoload.php';

// Get the Laravel application instance
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PlantVariety;
use Illuminate\Support\Facades\DB;

echo "🔍 Testing Plant Varieties Database\n\n";

try {
    // Check if the table exists and has data
    $count = PlantVariety::count();
    echo "📊 Total plant varieties in database: $count\n\n";
    
    if ($count == 0) {
        echo "❌ Database is empty! Let's create some test data...\n\n";
        
        // Create Carrot F1 Eskimo variety
        $carrotEskimo = PlantVariety::create([
            'farmos_id' => 'test-uuid-carrot-f1-eskimo',
            'name' => 'Carrot F1 Eskimo',
            'plant_type' => 'Carrot',
            'crop_family' => 'Apiaceae',
            'harvest_start' => '2025-07-01',  // July 1
            'harvest_end' => '2025-10-15',    // October 15
            'harvest_window_days' => 106,     // Correct 106 days
            'yield_peak' => '2025-08-15',
            'harvest_notes' => 'AMAZING PERFORMER - Exceptional quality and yield, highly recommended. F1 hybrid variety with excellent cold tolerance.',
            'harvest_method' => 'continuous',
            'expected_yield_per_plant' => '0.75',
            'maturity_days' => 106,
            'season' => 'cool',
            'is_active' => true,
            'sync_status' => 'synced'
        ]);
        
        echo "✅ Created Carrot F1 Eskimo: ID {$carrotEskimo->id}\n";
        
        // Create Carrot Early Nantes 2 variety  
        $carrotNantes = PlantVariety::create([
            'farmos_id' => 'test-uuid-carrot-early-nantes-2',
            'name' => 'Carrot Early Nantes 2',
            'plant_type' => 'Carrot',
            'crop_family' => 'Apiaceae',
            'harvest_start' => '2025-06-01',  // June 1
            'harvest_end' => '2025-08-15',    // August 15
            'harvest_window_days' => 75,      // 75 days
            'yield_peak' => '2025-07-01',
            'harvest_notes' => 'FAVORITE EARLY VARIETY - Customer favorite for sweet, crunchy carrots. Early maturing variety perfect for succession planting.',
            'harvest_method' => 'once-over',
            'expected_yield_per_plant' => '0.50',
            'maturity_days' => 75,
            'season' => 'cool',
            'is_active' => true,
            'sync_status' => 'synced'
        ]);
        
        echo "✅ Created Carrot Early Nantes 2: ID {$carrotNantes->id}\n";
        
        // Add a few more carrot varieties for testing
        $carrotVarieties = [
            [
                'farmos_id' => 'test-uuid-carrot-chantenay',
                'name' => 'Carrot Chantenay Red Core',
                'harvest_window_days' => 90,
                'harvest_notes' => 'Classic variety with excellent storage qualities'
            ],
            [
                'farmos_id' => 'test-uuid-carrot-paris-market',
                'name' => 'Carrot Paris Market',
                'harvest_window_days' => 65,
                'harvest_notes' => 'Round variety perfect for containers and shallow soils'
            ]
        ];
        
        foreach ($carrotVarieties as $variety) {
            PlantVariety::create([
                'farmos_id' => $variety['farmos_id'],
                'name' => $variety['name'],
                'plant_type' => 'Carrot',
                'crop_family' => 'Apiaceae',
                'harvest_start' => '2025-06-15',
                'harvest_end' => '2025-09-15',
                'harvest_window_days' => $variety['harvest_window_days'],
                'yield_peak' => '2025-07-15',
                'harvest_notes' => $variety['harvest_notes'],
                'harvest_method' => 'continuous',
                'expected_yield_per_plant' => '0.60',
                'maturity_days' => $variety['harvest_window_days'],
                'season' => 'cool',
                'is_active' => true,
                'sync_status' => 'synced'
            ]);
            
            echo "✅ Created {$variety['name']}\n";
        }
        
        echo "\n📊 Total varieties after creation: " . PlantVariety::count() . "\n\n";
    }
    
    // Test searching for Carrot F1 Eskimo
    echo "🔍 Testing search for 'Carrot F1 Eskimo':\n";
    
    $exact = PlantVariety::where('name', 'Carrot F1 Eskimo')->first();
    echo "  Exact match: " . ($exact ? "✅ FOUND - {$exact->name} ({$exact->harvest_window_days} days)" : "❌ NOT FOUND") . "\n";
    
    $partial = PlantVariety::where('name', 'LIKE', '%Carrot F1 Eskimo%')->first();
    echo "  Partial match: " . ($partial ? "✅ FOUND - {$partial->name} ({$partial->harvest_window_days} days)" : "❌ NOT FOUND") . "\n";
    
    $caseInsensitive = PlantVariety::whereRaw('LOWER(name) = LOWER(?)', ['Carrot F1 Eskimo'])->first();
    echo "  Case insensitive: " . ($caseInsensitive ? "✅ FOUND - {$caseInsensitive->name} ({$caseInsensitive->harvest_window_days} days)" : "❌ NOT FOUND") . "\n";
    
    // Show all carrot varieties
    echo "\n🥕 All carrot varieties in database:\n";
    $carrots = PlantVariety::where('plant_type', 'LIKE', '%Carrot%')
        ->orWhere('name', 'LIKE', '%Carrot%')
        ->get(['name', 'harvest_window_days', 'harvest_notes']);
        
    foreach ($carrots as $carrot) {
        echo "  • {$carrot->name} ({$carrot->harvest_window_days} days)\n";
        echo "    Notes: " . substr($carrot->harvest_notes, 0, 80) . "...\n";
    }
    
    echo "\n✅ Database test completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
