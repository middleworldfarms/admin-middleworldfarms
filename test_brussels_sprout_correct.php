<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\FarmOSApi;

try {
    echo "=== Testing FarmOS for Brussels Sprout F1 Doric (Correct Method) ===\n";
    
    $farmOSApi = new FarmOSApi();
    $farmOSApi->authenticate();
    
    echo "1. Getting cropData using the same method as succession planner...\n";
    $cropData = $farmOSApi->getAvailableCropTypes();
    
    echo "Crop types found: " . count($cropData['types']) . "\n";
    echo "Total varieties found: " . count($cropData['varieties']) . "\n\n";
    
    // Look for Brussels Sprouts in crop types
    echo "2. Searching for Brussels Sprouts crop type...\n";
    $brusselsCropType = null;
    foreach($cropData['types'] as $type) {
        if(stripos($type['name'], 'Brussels') !== false) {
            $brusselsCropType = $type;
            echo "- Found Brussels Sprouts crop type: " . $type['name'] . " (ID: " . $type['id'] . ")\n";
        }
    }
    
    if(!$brusselsCropType) {
        echo "No Brussels Sprouts crop type found.\n";
    }
    
    echo "\n3. Searching for Brussels Sprout varieties...\n";
    $brusselsVarieties = [];
    foreach($cropData['varieties'] as $variety) {
        if(stripos($variety['name'], 'Brussels') !== false) {
            $brusselsVarieties[] = $variety;
            echo "- Found: " . $variety['name'] . "\n";
            echo "  ID: " . $variety['id'] . "\n";
            echo "  Parent: " . ($variety['parent_id'] ?? 'None') . "\n";
            if(!empty($variety['description'])) {
                echo "  Description: " . substr($variety['description'], 0, 100) . "...\n";
            }
            echo "\n";
        }
    }
    
    echo "Brussels Sprout varieties found: " . count($brusselsVarieties) . "\n\n";
    
    echo "4. Searching specifically for 'Doric'...\n";
    $doricVarieties = [];
    foreach($cropData['varieties'] as $variety) {
        if(stripos($variety['name'], 'Doric') !== false) {
            $doricVarieties[] = $variety;
            echo "- DORIC FOUND: " . $variety['name'] . "\n";
            echo "  ID: " . $variety['id'] . "\n";
            echo "  Parent: " . ($variety['parent_id'] ?? 'None') . "\n";
            if(!empty($variety['description'])) {
                echo "  Full description: " . $variety['description'] . "\n";
            }
            echo "\n";
        }
    }
    
    if(count($doricVarieties) == 0) {
        echo "No 'Doric' varieties found.\n";
    }
    
    echo "\n5. If Brussels Sprouts crop type exists, show ALL its varieties...\n";
    if($brusselsCropType) {
        $brusselsTypeId = $brusselsCropType['id'];
        $allBrusselsVarieties = [];
        
        foreach($cropData['varieties'] as $variety) {
            if($variety['parent_id'] === $brusselsTypeId || $variety['crop_type'] === $brusselsTypeId) {
                $allBrusselsVarieties[] = $variety;
            }
        }
        
        echo "All varieties under Brussels Sprouts crop type: " . count($allBrusselsVarieties) . "\n";
        foreach($allBrusselsVarieties as $variety) {
            echo "- " . $variety['name'] . "\n";
            if(stripos($variety['name'], 'Doric') !== false) {
                echo "  *** DORIC FOUND HERE! ***\n";
            }
        }
    }
    
    echo "\n=== CONCLUSION ===\n";
    if(count($brusselsVarieties) > 0 || count($doricVarieties) > 0) {
        echo "SUCCESS: FarmOS DOES contain Brussels Sprout data!\n";
        echo "The succession planner dropdown should show these varieties.\n";
    } else {
        echo "FarmOS appears to have no Brussels Sprout variety data.\n";
        echo "But crop types might exist - check the dropdown relationship logic.\n";
    }
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
