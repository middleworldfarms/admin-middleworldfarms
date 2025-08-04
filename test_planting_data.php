<?php

// Initialize Laravel
require_once '/opt/sites/admin.middleworldfarms.org/vendor/autoload.php';

$app = require_once '/opt/sites/admin.middleworldfarms.org/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\FarmOSApiService;

try {
    $farmOSApi = new FarmOSApiService();
    
    echo "=== Testing Planting Chart Data ===\n";
    
    // Get geometry assets
    echo "\n1. Getting geometry assets...\n";
    $geometryAssets = $farmOSApi->getGeometryAssets();
    $featureCount = count($geometryAssets['features'] ?? []);
    echo "Found $featureCount geometry assets\n";
    
    // Get crop plans
    echo "\n2. Getting crop plans...\n";
    $cropPlans = $farmOSApi->getCropPlanningData();
    $planCount = count($cropPlans);
    echo "Found $planCount crop plans\n";
    
    // Show some sample geometry assets
    echo "\n3. Sample geometry assets:\n";
    $sampleFeatures = array_slice($geometryAssets['features'] ?? [], 0, 5);
    foreach ($sampleFeatures as $feature) {
        $props = $feature['properties'] ?? [];
        $name = $props['name'] ?? 'Unnamed';
        $landType = $props['land_type'] ?? 'unknown';
        $isBlock = $props['is_block'] ?? false;
        $isBed = $props['is_bed'] ?? false;
        
        echo "- $name (type: $landType, block: " . ($isBlock ? 'yes' : 'no') . ", bed: " . ($isBed ? 'yes' : 'no') . ")\n";
    }
    
    // Show some sample crop plans
    echo "\n4. Sample crop plans:\n";
    $samplePlans = array_slice($cropPlans, 0, 3);
    foreach ($samplePlans as $plan) {
        $crop = $plan['crop_type'] ?? 'Unknown';
        $location = $plan['location'] ?? 'Unknown location';
        $seeding = $plan['planned_seeding_date'] ?? 'Not set';
        $harvest = $plan['planned_harvest_start'] ?? 'Not set';
        
        echo "- $crop at $location\n";
        echo "  Seeding: $seeding\n";
        echo "  Harvest: $harvest\n";
    }
    
    echo "\n=== Test Complete ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
