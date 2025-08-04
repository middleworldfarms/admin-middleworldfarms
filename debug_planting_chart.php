<?php
require_once 'bootstrap/app.php';

use App\Services\FarmOSApiService;

try {
    $farmOSApi = new FarmOSApiService();
    
    echo "=== Testing Planting Chart Data ===\n";
    
    // Get geometry assets
    echo "\n1. Getting geometry assets...\n";
    $geometryAssets = $farmOSApi->getGeometryAssets();
    echo "Found " . count($geometryAssets['features'] ?? []) . " geometry assets\n";
    
    // Get crop plans
    echo "\n2. Getting crop plans...\n";
    $cropPlans = $farmOSApi->getCropPlanningData();
    echo "Found " . count($cropPlans) . " crop plans\n";
    
    // Show some sample geometry assets
    echo "\n3. Sample geometry assets:\n";
    foreach (array_slice($geometryAssets['features'] ?? [], 0, 5) as $feature) {
        $props = $feature['properties'] ?? [];
        echo "- " . ($props['name'] ?? 'Unnamed') . " (type: " . ($props['land_type'] ?? 'unknown') . ")\n";
    }
    
    // Show some sample crop plans
    echo "\n4. Sample crop plans:\n";
    foreach (array_slice($cropPlans, 0, 3) as $plan) {
        echo "- " . ($plan['crop_type'] ?? 'Unknown') . " at " . ($plan['location'] ?? 'Unknown location') . "\n";
        echo "  Seeding: " . ($plan['planned_seeding_date'] ?? 'Not set') . "\n";
        echo "  Harvest: " . ($plan['planned_harvest_start'] ?? 'Not set') . "\n";
    }
    
    echo "\n=== Test Complete ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
