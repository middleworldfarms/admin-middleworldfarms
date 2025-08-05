<?php

// Simple test of AI crop timing logic without full Laravel request
require_once 'vendor/autoload.php';

use App\Http\Controllers\Admin\SuccessionPlanningController;
use App\Services\FarmOSApiService;

// Mock the FarmOSApiService
$mockFarmOSApi = new class {
    public function getAvailableCropTypes() {
        return ['types' => [], 'varieties' => []];
    }
    
    public function getGeometryAssets() {
        return ['data' => []];
    }
};

// Create controller instance
$controller = new SuccessionPlanningController($mockFarmOSApi);

// Test the timing calculation methods using reflection
$reflection = new ReflectionClass($controller);

// Test getCurrentSeason
$getCurrentSeason = $reflection->getMethod('getCurrentSeason');
$getCurrentSeason->setAccessible(true);
$currentSeason = $getCurrentSeason->invoke($controller);

echo "Current season: " . $currentSeason . "\n";

// Test getCropTimingPresets
$getCropTimingPresets = $reflection->getMethod('getCropTimingPresets');
$getCropTimingPresets->setAccessible(true);
$presets = $getCropTimingPresets->invoke($controller);

echo "Lettuce preset: " . json_encode($presets['lettuce']) . "\n";
echo "Tomato preset: " . json_encode($presets['tomato']) . "\n";

// Test seasonal adjustments
$getSeasonalAdjustments = $reflection->getMethod('getSeasonalAdjustments');
$getSeasonalAdjustments->setAccessible(true);

$springAdjustments = $getSeasonalAdjustments->invoke($controller, 'spring');
$summerAdjustments = $getSeasonalAdjustments->invoke($controller, 'summer');

echo "Spring adjustments: " . json_encode($springAdjustments) . "\n";
echo "Summer adjustments: " . json_encode($summerAdjustments) . "\n";

// Test timing calculation
$calculateAITiming = $reflection->getMethod('calculateAITiming');
$calculateAITiming->setAccessible(true);

$lettuceSpringTiming = $calculateAITiming->invoke(
    $controller, 
    $presets['lettuce'], 
    $springAdjustments, 
    false, 
    'lettuce', 
    'spring'
);

$tomatoSummerTiming = $calculateAITiming->invoke(
    $controller, 
    $presets['tomato'], 
    $summerAdjustments, 
    false, 
    'tomato', 
    'summer'
);

echo "Lettuce spring timing: " . json_encode($lettuceSpringTiming) . "\n";
echo "Tomato summer timing: " . json_encode($tomatoSummerTiming) . "\n";

// Test recommendations
$getAIRecommendations = $reflection->getMethod('getAIRecommendations');
$getAIRecommendations->setAccessible(true);

$lettuceRecommendations = $getAIRecommendations->invoke($controller, 'lettuce', 'spring', false);
$tomatoRecommendations = $getAIRecommendations->invoke($controller, 'tomato', 'summer', false);

echo "Lettuce recommendations: " . json_encode($lettuceRecommendations) . "\n";
echo "Tomato recommendations: " . json_encode($tomatoRecommendations) . "\n";
