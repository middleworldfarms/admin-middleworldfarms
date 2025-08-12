<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Services\FarmOSApi;
use App\Services\FarmOSAuthService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Debugging Raw farmOS Variety Data\n";
echo "=================================\n\n";

try {
    $farmOSApi = new FarmOSApi();
    
    // Get raw varieties data to see structure
    echo "Fetching raw varieties from farmOS...\n";
    $varieties = $farmOSApi->getVarieties();
    
    echo "Found " . count($varieties) . " varieties\n\n";
    
    // Look for Redbor and other varieties
    $redbor = null;
    $beetrootRelated = [];
    
    foreach ($varieties as $variety) {
        $name = $variety['attributes']['name'] ?? '';
        $relationships = $variety['relationships'] ?? [];
        $parent = $relationships['parent']['data'][0]['id'] ?? null;
        
        if (stripos($name, 'redbor') !== false) {
            $redbor = $variety;
        }
        
        // Look for anything that might be beetroot related
        if (stripos($name, 'beet') !== false || stripos($name, 'detroit') !== false || stripos($name, 'chioggia') !== false) {
            $beetrootRelated[] = $variety;
        }
    }
    
    if ($redbor) {
        echo "Redbor Raw Data:\n";
        echo "  Name: " . ($redbor['attributes']['name'] ?? 'N/A') . "\n";
        echo "  ID: " . $redbor['id'] . "\n";
        echo "  Parent ID: " . ($redbor['relationships']['parent']['data'][0]['id'] ?? 'N/A') . "\n";
        echo "  Relationships structure:\n";
        print_r($redbor['relationships'] ?? []);
        echo "\n";
    }
    
    echo "Beetroot-related varieties found:\n";
    foreach ($beetrootRelated as $variety) {
        echo "  - " . ($variety['attributes']['name'] ?? 'N/A') . " (Parent: " . ($variety['relationships']['parent']['data'][0]['id'] ?? 'N/A') . ")\n";
    }
    
    echo "\n";
    
    // Get crop types to cross-reference
    echo "Getting crop types...\n";
    $plantTypes = $farmOSApi->getPlantTypes();
    
    $beetrootType = null;
    $kaleType = null;
    
    foreach ($plantTypes as $type) {
        $name = $type['attributes']['name'] ?? '';
        if (stripos($name, 'beetroot') !== false || stripos($name, 'beet') !== false) {
            $beetrootType = $type;
            echo "Found beetroot type: " . $name . " (ID: " . $type['id'] . ")\n";
        }
        if (stripos($name, 'kale') !== false) {
            $kaleType = $type;
            echo "Found kale type: " . $name . " (ID: " . $type['id'] . ")\n";
        }
    }
    
    echo "\n";
    
    // Check if Redbor's parent matches kale type
    if ($redbor && $kaleType) {
        $redborParent = $redbor['relationships']['parent']['data'][0]['id'] ?? null;
        echo "Redbor parent: " . $redborParent . "\n";
        echo "Kale type ID: " . $kaleType['id'] . "\n";
        echo "Match: " . ($redborParent === $kaleType['id'] ? 'YES' : 'NO') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
