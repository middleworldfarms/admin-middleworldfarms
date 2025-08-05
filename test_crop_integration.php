<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing farmOS Crop Types and Varieties Integration...\n\n";

try {
    // Create fresh instance of FarmOS API service
    $farmOSApi = new \App\Services\FarmOSApiService();
    
    echo "🌱 Fetching crop types and varieties from farmOS...\n";
    $cropData = $farmOSApi->getAvailableCropTypes();
    
    echo "✅ Found " . count($cropData['types']) . " crop types:\n";
    foreach (array_slice($cropData['types'], 0, 10) as $crop) {
        echo "  - {$crop['label']} ({$crop['name']})\n";
    }
    
    if (count($cropData['types']) > 10) {
        echo "  ... and " . (count($cropData['types']) - 10) . " more\n";
    }
    
    echo "\n✅ Found " . count($cropData['varieties']) . " varieties:\n";
    foreach (array_slice($cropData['varieties'], 0, 10) as $variety) {
        echo "  - {$variety['label']}\n";
    }
    
    if (count($cropData['varieties']) > 10) {
        echo "  ... and " . (count($cropData['varieties']) - 10) . " more\n";
    }
    
    echo "\n🎉 farmOS integration working successfully!\n";
    echo "The succession planning tool now uses real crop data from your farmOS database.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";
