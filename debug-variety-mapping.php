<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Services\FarmOSApi;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Debugging Variety to Crop Type Mapping\n";
echo "=====================================\n\n";

try {
    $farmOSApi = new FarmOSApi();
    $cropData = $farmOSApi->getAvailableCropTypes();
    
    echo "Total crop types: " . count($cropData['types']) . "\n";
    echo "Total varieties: " . count($cropData['varieties']) . "\n\n";
    
    // Find beetroot type
    $beetrootType = null;
    foreach ($cropData['types'] as $type) {
        if ($type['label'] === 'Beetroot') {
            $beetrootType = $type;
            break;
        }
    }
    
    if ($beetrootType) {
        echo "Beetroot Type Found:\n";
        echo "  ID: " . $beetrootType['id'] . "\n";
        echo "  Label: " . $beetrootType['label'] . "\n\n";
        
        // Find varieties for beetroot
        $beetrootVarieties = [];
        foreach ($cropData['varieties'] as $variety) {
            if (isset($variety['crop_type']) && $variety['crop_type'] === $beetrootType['id']) {
                $beetrootVarieties[] = $variety;
            }
        }
        
        echo "Beetroot Varieties Found: " . count($beetrootVarieties) . "\n";
        foreach ($beetrootVarieties as $variety) {
            echo "  - " . $variety['label'] . "\n";
        }
    } else {
        echo "Beetroot type not found!\n";
    }
    
    echo "\n";
    
    // Check Redbor specifically
    $redbor = null;
    foreach ($cropData['varieties'] as $variety) {
        if ($variety['label'] === 'Redbor') {
            $redbor = $variety;
            break;
        }
    }
    
    if ($redbor) {
        echo "Redbor Variety Found:\n";
        echo "  ID: " . $redbor['id'] . "\n";
        echo "  Label: " . $redbor['label'] . "\n";
        echo "  crop_type: " . ($redbor['crop_type'] ?? 'NOT SET') . "\n";
        echo "  parent_id: " . ($redbor['parent_id'] ?? 'NOT SET') . "\n";
        
        // Find what crop type this belongs to
        $parentType = null;
        foreach ($cropData['types'] as $type) {
            if ($type['id'] === ($redbor['crop_type'] ?? '')) {
                $parentType = $type;
                break;
            }
        }
        
        if ($parentType) {
            echo "  Belongs to: " . $parentType['label'] . "\n";
        } else {
            echo "  Parent crop type not found!\n";
        }
    } else {
        echo "Redbor variety not found!\n";
    }
    
    echo "\n";
    
    // Sample some varieties to see their structure
    echo "Sample varieties structure:\n";
    for ($i = 0; $i < min(5, count($cropData['varieties'])); $i++) {
        $variety = $cropData['varieties'][$i];
        echo "Variety " . ($i + 1) . ":\n";
        echo "  Label: " . $variety['label'] . "\n";
        echo "  crop_type: " . ($variety['crop_type'] ?? 'NOT SET') . "\n";
        echo "  parent_id: " . ($variety['parent_id'] ?? 'NOT SET') . "\n";
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
