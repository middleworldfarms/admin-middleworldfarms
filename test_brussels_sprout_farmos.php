<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\FarmOSApi;

try {
    echo "=== Testing FarmOS for Brussels Sprout F1 Doric ===\n";
    
    $farmOSApi = new FarmOSApi();
    $farmOSApi->authenticate();
    
    echo "1. Getting all varieties...\n";
    $varieties = $farmOSApi->getVarieties();
    echo "Total varieties: " . count($varieties) . "\n\n";
    
    echo "2. Searching for Brussels Sprout varieties...\n";
    $brusselsVarieties = [];
    foreach($varieties as $variety) {
        if(isset($variety['name']) && stripos($variety['name'], 'Brussels') !== false) {
            $brusselsVarieties[] = $variety;
            echo "- Found: " . $variety['name'] . "\n";
            if(isset($variety['description'])) {
                echo "  Description: " . substr($variety['description'], 0, 100) . "...\n";
            }
        }
    }
    echo "Brussels varieties found: " . count($brusselsVarieties) . "\n\n";
    
    echo "3. Searching specifically for 'Doric'...\n";
    $doricFound = false;
    foreach($varieties as $variety) {
        if(isset($variety['name']) && stripos($variety['name'], 'Doric') !== false) {
            echo "- DORIC FOUND: " . $variety['name'] . "\n";
            if(isset($variety['description'])) {
                echo "  Full description: " . $variety['description'] . "\n";
            }
            $doricFound = true;
        }
    }
    
    if(!$doricFound) {
        echo "No 'Doric' varieties found in farmOS.\n";
    }
    
    echo "\n4. Checking harvest logs for Brussels Sprout activity...\n";
    $harvestLogs = $farmOSApi->getHarvestLogs();
    echo "Total harvest logs: " . count($harvestLogs) . "\n";
    
    $brusselsLogs = [];
    foreach($harvestLogs as $log) {
        if(isset($log['notes']) && stripos($log['notes'], 'Brussels') !== false) {
            $brusselsLogs[] = $log;
        }
        if(isset($log['name']) && stripos($log['name'], 'Brussels') !== false) {
            $brusselsLogs[] = $log;
        }
    }
    
    echo "Brussels Sprout harvest logs: " . count($brusselsLogs) . "\n";
    foreach($brusselsLogs as $i => $log) {
        echo "Log " . ($i + 1) . ": " . ($log['name'] ?? 'Unnamed') . "\n";
        echo "  Date: " . ($log['timestamp'] ?? 'No date') . "\n";
        echo "  Notes: " . substr(($log['notes'] ?? 'No notes'), 0, 100) . "...\n";
    }
    
    echo "\n=== CONCLUSION ===\n";
    if(count($brusselsVarieties) == 0 && count($brusselsLogs) == 0) {
        echo "FarmOS appears to have NO Brussels Sprout data.\n";
        echo "Our AI fallback system would use seed company data (UK suppliers like Thompson & Morgan).\n";
    } else {
        echo "FarmOS has some Brussels Sprout data that our fallback system can use.\n";
    }
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
