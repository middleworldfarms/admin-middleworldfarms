<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\FarmOSQuickFormService;

// Test the unified Quick Form URL generation
$service = app(FarmOSQuickFormService::class);

$testSuccessionData = [
    'crop_name' => 'Tomato',
    'variety_name' => 'Cherry',
    'bed_name' => 'Bed A1',
    'quantity' => 200,
    'seeding_date' => '2024-10-15',
    'transplant_date' => '2024-11-01',
    'harvest_date' => '2024-12-15',
    'harvest_end_date' => '2024-12-30',
    'succession_number' => 1
];

echo "Testing Unified Quick Form URL Generation:\n";
echo "==========================================\n";

$urls = $service->generateAllFormUrls($testSuccessionData);
echo "Generated URLs:\n";
print_r($urls);

echo "\nUnified URL: " . $urls['unified'] . "\n";

echo "\nTest completed successfully!\n";