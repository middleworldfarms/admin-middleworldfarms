<?php

// Simple test endpoint for farmOS map data
require_once 'vendor/autoload.php';

try {
    // Load Laravel
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    // Test the FarmOS service
    $farmosService = app(\App\Services\FarmOSApiService::class);
    $geometryData = $farmosService->getGeometryAssets();
    
    header('Content-Type: application/json');
    echo json_encode($geometryData, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
