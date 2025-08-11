<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FarmOSApiService;
use App\Services\HolisticAICropService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SuccessionPlanningController extends Controller
{
    protected $farmOSApi;
    protected $holisticAI;

    public function __construct(FarmOSApiService $farmOSApi, HolisticAICropService $holisticAI)
    {
        $this->farmOSApi = $farmOSApi;
        $this->holisticAI = $holisticAI;
    }

    /**
     * Display the succession planning interface
     */
    public function index()
    {
        try {
            // Get available crop types and varieties from farmOS
            $cropData = $this->farmOSApi->getAvailableCropTypes();
            $geometryAssets = $this->farmOSApi->getGeometryAssets();
            $availableBeds = $this->extractAvailableBeds($geometryAssets);
            
            // Crop timing presets for common market garden crops
            $cropPresets = $this->getCropTimingPresets();
            
            return view('admin.farmos.succession-planning', compact(
                'cropData',
                'availableBeds', 
                'cropPresets'
            ));
            
        } catch (\Exception $e) {
            Log::error('Failed to load succession planning: ' . $e->getMessage());
            
            // Fallback data for demo
            $cropData = [
                'types' => [
                    ['id' => 'lettuce', 'name' => 'lettuce', 'label' => 'Lettuce'],
                    ['id' => 'carrot', 'name' => 'carrot', 'label' => 'Carrot'],
                    ['id' => 'radish', 'name' => 'radish', 'label' => 'Radish'],
                    ['id' => 'spinach', 'name' => 'spinach', 'label' => 'Spinach'],
                    ['id' => 'kale', 'name' => 'kale', 'label' => 'Kale'],
                    ['id' => 'arugula', 'name' => 'arugula', 'label' => 'Arugula'],
                    ['id' => 'beets', 'name' => 'beets', 'label' => 'Beets']
                ],
                'varieties' => []
            ];
            $availableBeds = $this->getFallbackBeds();
            $cropPresets = $this->getCropTimingPresets();
            
            return view('admin.farmos.succession-planning', compact(
                'cropData',
                'availableBeds',
                'cropPresets'
            ));
        }
    }

    /**
     * Generate succession plan based on user input
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'crop_type' => 'required|string',
            'variety' => 'nullable|string',
            'succession_count' => 'required|integer|min:1|max:50',
            'interval_days' => 'required|integer|min:1|max:365',
            'first_seeding_date' => 'required|date',
            'seeding_to_transplant_days' => 'nullable|integer|min:0|max:180',
            'transplant_to_harvest_days' => 'required|integer|min:1|max:365',
            'harvest_duration_days' => 'required|integer|min:1|max:90',
            'beds_per_planting' => 'required|integer|min:1|max:10',
            'auto_assign_beds' => 'boolean',
            'selected_beds' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
            'direct_sow' => 'boolean'
        ]);

        try {
            // Get existing farmOS crop plans to check for conflicts
            $existingPlans = $this->farmOSApi->getCropPlanningData();
            $cropPresets = $this->getCropTimingPresets();
            
            // Generate succession plan with AI assistance
            $successionPlan = $this->generateSuccessionPlan($validated, $existingPlans, $cropPresets);
            
            // Check for bed conflicts and find alternatives
            $planWithBeds = $this->assignBedsWithConflictResolution($successionPlan);
            
            // Use AI to optimize the plan
            $optimizedPlan = $this->optimizePlanWithAI($planWithBeds, $validated);
            
            return response()->json([
                'success' => true,
                'plan' => $optimizedPlan,
                'message' => 'Succession plan generated successfully with AI optimization',
                'conflicts_resolved' => $optimizedPlan['conflicts_resolved'] ?? 0
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to generate succession plan: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate succession plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create farmOS logs for the succession plan via API
     * IMPORTANT: This admin is a CLIENT ONLY - all data goes to farmOS via API
     * Our planting chart will then read this data back from farmOS
     */
    public function createLogs(Request $request)
    {
        $validated = $request->validate([
            'plan' => 'required|array',
            'confirm' => 'required|boolean'
        ]);

        if (!$validated['confirm']) {
            return response()->json([
                'success' => false,
                'message' => 'Plan creation not confirmed'
            ], 400);
        }

        try {
            $createdLogs = [];
            $errors = [];
            $farmOSPlans = []; // Track what we send to farmOS

            foreach ($validated['plan']['plantings'] as $planting) {
                try {
                    // CRITICAL: All data goes to farmOS via API calls
                    // We store NOTHING locally - farmOS is the master database
                    
                    // Create seeding plan in farmOS
                    if (!empty($planting['seeding_date'])) {
                        $seedingType = ($planting['direct_sow'] ?? false) ? 'direct_seed' : 'seed';
                        $seedingNote = ($planting['direct_sow'] ?? false) ? 'Direct Sow' : 'Seeding';
                        
                        $seedingPlan = $this->farmOSApi->createCropPlan([
                            'type' => $seedingType,
                            'crop' => [
                                'name' => $planting['crop'],
                                'variety' => $planting['variety'] ?? ''
                            ],
                            'location' => $planting['bed_id'],
                            'timestamp' => $planting['seeding_date'],
                            'notes' => "Succession #{$planting['sequence']}: {$seedingNote} - " . ($planting['notes'] ?? ''),
                            'status' => 'pending'
                        ]);
                        $createdLogs[] = $seedingPlan;
                        $farmOSPlans[] = $seedingPlan;
                    }

                    // Create transplant plan in farmOS (only for non-direct-sow crops)
                    if (!empty($planting['transplant_date']) && !($planting['direct_sow'] ?? false)) {
                        $transplantPlan = $this->farmOSApi->createCropPlan([
                            'type' => 'transplant',
                            'crop' => [
                                'name' => $planting['crop'],
                                'variety' => $planting['variety'] ?? ''
                            ],
                            'location' => $planting['bed_id'],
                            'timestamp' => $planting['transplant_date'],
                            'notes' => "Succession #{$planting['sequence']}: Transplant - " . ($planting['notes'] ?? ''),
                            'status' => 'pending'
                        ]);
                        $createdLogs[] = $transplantPlan;
                        $farmOSPlans[] = $transplantPlan;
                    }

                    // Create harvest plan in farmOS
                    if (!empty($planting['harvest_date'])) {
                        $harvestPlan = $this->farmOSApi->createCropPlan([
                            'type' => 'harvest',
                            'crop' => [
                                'name' => $planting['crop'],
                                'variety' => $planting['variety'] ?? ''
                            ],
                            'location' => $planting['bed_id'],
                            'timestamp' => $planting['harvest_date'],
                            'notes' => "Succession #{$planting['sequence']}: Harvest - " . ($planting['notes'] ?? ''),
                            'status' => 'pending'
                        ]);
                        $createdLogs[] = $harvestPlan;
                        $farmOSPlans[] = $harvestPlan;
                    }

                } catch (\Exception $e) {
                    $errors[] = "Failed to create farmOS plans for planting #{$planting['sequence']}: " . $e->getMessage();
                    Log::error("farmOS API error during succession planning: " . $e->getMessage());
                }
            }

            // Log what was sent to farmOS for debugging
            Log::info('Succession planning: Created ' . count($farmOSPlans) . ' plans in farmOS', [
                'plans_created' => count($farmOSPlans),
                'crop_type' => $validated['plan']['crop_type'] ?? 'unknown',
                'total_plantings' => $validated['plan']['total_plantings'] ?? 0
            ]);

            return response()->json([
                'success' => true,
                'created_logs' => count($createdLogs),
                'errors' => $errors,
                'message' => count($createdLogs) . ' farmOS crop plans created successfully',
                'note' => 'Data now exists in farmOS - planting chart will show these when refreshed'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create succession plans in farmOS: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create farmOS plans: ' . $e->getMessage(),
                'note' => 'No data was stored locally - all operations are against farmOS API'
            ], 500);
        }
    }

    /**
     * Generate basic succession plan with timing calculations
     */
    private function generateSuccessionPlan($validated, $existingPlans, $cropPresets)
    {
        $cropType = $validated['crop_type'];
        $variety = $validated['variety'] ?? 'Standard';
        $successionCount = $validated['succession_count'];
        $intervalDays = $validated['interval_days'];
        $firstSeedingDate = Carbon::parse($validated['first_seeding_date']);
        $notes = $validated['notes'] ?? '';
        $isDirectSow = $validated['direct_sow'] ?? false;

        // Get crop timing from user input (preferred) or presets
        $seedingToTransplantDays = $isDirectSow ? 0 : ($validated['seeding_to_transplant_days'] ?? 0);
        $transplantToHarvestDays = $validated['transplant_to_harvest_days'];
        $harvestDurationDays = $validated['harvest_duration_days'];
        
        $plantings = [];
        
        for ($i = 0; $i < $successionCount; $i++) {
            $seedingDate = $firstSeedingDate->copy()->addDays($i * $intervalDays);
            
            // For direct sow crops, transplant date is null
            $transplantDate = $isDirectSow ? null : 
                ($seedingToTransplantDays > 0 ? $seedingDate->copy()->addDays($seedingToTransplantDays) : null);
                
            // Calculate harvest date based on planting method
            if ($isDirectSow) {
                // Direct sow: harvest date calculated from seeding date
                $harvestDate = $seedingDate->copy()->addDays($transplantToHarvestDays);
            } else {
                // Transplant: harvest date calculated from transplant date
                $harvestDate = $transplantDate ? 
                    $transplantDate->copy()->addDays($transplantToHarvestDays) :
                    $seedingDate->copy()->addDays($transplantToHarvestDays);
            }
            
            $plantings[] = [
                'sequence' => $i + 1,
                'crop' => $cropType,
                'variety' => $variety,
                'seeding_date' => $seedingDate->format('Y-m-d'),
                'transplant_date' => $transplantDate ? $transplantDate->format('Y-m-d') : null,
                'harvest_date' => $harvestDate->format('Y-m-d'),
                'harvest_duration_days' => $harvestDurationDays,
                'direct_sow' => $isDirectSow,
                'notes' => $notes,
                'bed_id' => null, // To be assigned later
                'conflicts' => []
            ];
        }

        return [
            'crop_type' => $cropType,
            'variety' => $variety,
            'total_plantings' => $successionCount,
            'interval_days' => $intervalDays,
            'direct_sow' => $isDirectSow,
            'plantings' => $plantings
        ];
    }

    /**
     * Assign beds while checking for conflicts with existing farmOS data
     */
    private function assignBedsWithConflictResolution($plan)
    {
        // Get all available beds from farmOS
        $geometryAssets = $this->farmOSApi->getGeometryAssets();
        $availableBeds = $this->extractAvailableBeds($geometryAssets);
        
        // Get existing crop plans to check for conflicts
        $existingPlans = $this->farmOSApi->getCropPlanningData();
        
        $conflictsResolved = 0;
        
        foreach ($plan['plantings'] as &$planting) {
            $assigned = false;
            $attemptCount = 0;
            
            // Try to find an available bed
            while (!$assigned && $attemptCount < count($availableBeds)) {
                $candidateBed = $availableBeds[$attemptCount % count($availableBeds)];
                
                // Check for conflicts with existing plans
                $conflicts = $this->checkBedConflicts(
                    $candidateBed, 
                    $planting['seeding_date'], 
                    $planting['harvest_date'], 
                    $existingPlans
                );
                
                if (empty($conflicts)) {
                    $planting['bed_id'] = $candidateBed['id'];
                    $planting['bed_name'] = $candidateBed['name'];
                    $assigned = true;
                } else {
                    $planting['conflicts'] = $conflicts;
                    $conflictsResolved++;
                }
                
                $attemptCount++;
            }
            
            if (!$assigned) {
                $planting['bed_id'] = null;
                $planting['bed_name'] = 'No available bed found';
                $planting['conflicts'][] = 'All beds are occupied during this period';
            }
        }
        
        $plan['conflicts_resolved'] = $conflictsResolved;
        return $plan;
    }

    /**
     * Use AI to optimize the succession plan
     */
    private function optimizePlanWithAI($plan, $originalRequest)
    {
        try {
            $aiContext = [
                'crop_type' => $plan['crop_type'],
                'variety' => $plan['variety'],
                'total_plantings' => $plan['total_plantings'],
                'interval_days' => $plan['interval_days'],
                'conflicts_resolved' => $plan['conflicts_resolved'] ?? 0,
                'current_date' => now()->format('Y-m-d'),
                'farm_beds' => count($this->getFallbackBeds()),
                'plantings_overview' => array_map(function($p) {
                    return [
                        'sequence' => $p['sequence'],
                        'seeding_date' => $p['seeding_date'],
                        'harvest_date' => $p['harvest_date'],
                        'bed_assigned' => !empty($p['bed_id']),
                        'conflicts' => count($p['conflicts'] ?? [])
                    ];
                }, $plan['plantings'])
            ];

            $aiQuestion = "Analyze this succession planting plan for {$plan['crop_type']} and suggest optimizations. "
                        . "Consider: timing intervals, bed rotation, seasonal factors, and any conflicts found. "
                        . "Provide specific recommendations for improving yield and reducing conflicts.";

            $response = Http::timeout(10)->post(env('AI_SERVICE_URL', 'http://localhost:8001/ask'), [
                'question' => $aiQuestion,
                'context' => json_encode($aiContext)
            ]);

            if ($response->successful()) {
                $aiData = $response->json();
                $plan['ai_recommendations'] = $aiData['answer'] ?? 'No recommendations available';
                $plan['ai_analysis_date'] = now()->format('Y-m-d H:i:s');
            } else {
                $plan['ai_recommendations'] = 'AI analysis unavailable';
            }

        } catch (\Exception $e) {
            Log::warning('AI optimization failed: ' . $e->getMessage());
            $plan['ai_recommendations'] = 'AI analysis temporarily unavailable';
        }

        return $plan;
    }

    /**
     * Check for bed conflicts with existing farmOS plans
     */
    private function checkBedConflicts($bed, $startDate, $endDate, $existingPlans)
    {
        $conflicts = [];
        $startCarbon = Carbon::parse($startDate);
        $endCarbon = Carbon::parse($endDate);

        foreach ($existingPlans as $plan) {
            if (isset($plan['location']) && $plan['location'] === $bed['name']) {
                $planStart = Carbon::parse($plan['start_date'] ?? $plan['timestamp']);
                $planEnd = Carbon::parse($plan['end_date'] ?? $plan['harvest_date'] ?? $planStart->copy()->addDays(90));

                // Check for date overlap
                if ($startCarbon->lte($planEnd) && $endCarbon->gte($planStart)) {
                    $conflicts[] = [
                        'type' => 'date_overlap',
                        'existing_crop' => $plan['crop'] ?? 'Unknown crop',
                        'existing_start' => $planStart->format('Y-m-d'),
                        'existing_end' => $planEnd->format('Y-m-d')
                    ];
                }
            }
        }

        return $conflicts;
    }

    /**
     * Extract available beds from farmOS geometry assets
     */
    private function extractAvailableBeds($geometryAssets)
    {
        $beds = [];
        
        if (isset($geometryAssets['features'])) {
            foreach ($geometryAssets['features'] as $feature) {
                $properties = $feature['attributes'] ?? [];
                $name = $properties['name'] ?? 'Unknown';
                
                // Filter for bed-type assets
                if (stripos($name, 'bed') !== false || preg_match('/\d+\/\d+/', $name)) {
                    $beds[] = [
                        'id' => $feature['id'] ?? uniqid(),
                        'name' => $name,
                        'type' => $properties['type'] ?? 'bed',
                        'area' => $properties['area'] ?? 0
                    ];
                }
            }
        }

        // Fallback if no beds found
        if (empty($beds)) {
            return $this->getFallbackBeds();
        }

        return $beds;
    }

    /**
     * Get fallback bed data for demo/testing
     */
    private function getFallbackBeds()
    {
        $beds = [];
        for ($block = 1; $block <= 10; $block++) {
            for ($bed = 1; $bed <= 16; $bed++) {
                $beds[] = [
                    'id' => "bed_{$block}_{$bed}",
                    'name' => "{$block}/{$bed}",
                    'type' => 'bed',
                    'area' => rand(50, 200) // sq ft
                ];
            }
        }
        return $beds;
    }

    /**
     * Get crop timing presets for common market garden crops
     */
    private function getCropTimingPresets()
    {
        return [
            'lettuce' => [
                'transplant_days' => 21,  // Seed to transplant
                'harvest_days' => 65,     // Seed to harvest
                'yield_period' => 14      // Harvest window
            ],
            'carrot' => [
                'transplant_days' => 0,   // Direct seed
                'harvest_days' => 75,
                'yield_period' => 21
            ],
            'radish' => [
                'transplant_days' => 0,
                'harvest_days' => 30,
                'yield_period' => 10
            ],
            'spinach' => [
                'transplant_days' => 14,
                'harvest_days' => 50,
                'yield_period' => 21
            ],
            'kale' => [
                'transplant_days' => 28,
                'harvest_days' => 70,
                'yield_period' => 60
            ],
            'arugula' => [
                'transplant_days' => 0,
                'harvest_days' => 40,
                'yield_period' => 14
            ],
            'chard' => [
                'transplant_days' => 21,
                'harvest_days' => 60,
                'yield_period' => 90
            ],
            'beets' => [
                'transplant_days' => 0,
                'harvest_days' => 70,
                'yield_period' => 21
            ],
            'cilantro' => [
                'transplant_days' => 0,   // Direct seed
                'harvest_days' => 45,
                'yield_period' => 21
            ],
            'dill' => [
                'transplant_days' => 0,   // Direct seed
                'harvest_days' => 50,
                'yield_period' => 14
            ],
            'scallion' => [
                'transplant_days' => 14,
                'harvest_days' => 60,
                'yield_period' => 30
            ],
            'mesclun' => [
                'transplant_days' => 0,   // Cut-and-come-again mix
                'harvest_days' => 30,
                'yield_period' => 21
            ],
            'default' => [
                'transplant_days' => 21,
                'harvest_days' => 70,
                'yield_period' => 21
            ]
        ];
    }

    /**
     * Get AI-powered crop timing recommendations
     */
    public function getAICropTiming(Request $request): JsonResponse
    {
        // Debug logging
        Log::info('AI crop timing request received', [
            'crop_type' => $request->input('crop_type'),
            'season' => $request->input('season'),
            'is_direct_sow' => $request->input('is_direct_sow')
        ]);
        
        try {
            $cropType = $request->input('crop_type');
            $season = $request->input('season', $this->getCurrentSeason());
            $isDirectSow = $request->input('is_direct_sow', false);
            
            if (!$cropType) {
                Log::warning('AI crop timing: Missing crop type');
                return new JsonResponse(['error' => 'Crop type is required'], 400);
            }

            // Get base timing from presets
            $presets = $this->getCropTimingPresets();
            $baseTiming = $presets[$cropType] ?? $presets['default'];
            
            // Apply seasonal adjustments
            $seasonalAdjustments = $this->getSeasonalAdjustments($season);
            
            // Calculate AI-enhanced timing
            $timing = $this->calculateAITiming($baseTiming, $seasonalAdjustments, $isDirectSow, $cropType, $season);
            
            Log::info('AI crop timing success', [
                'crop_type' => $cropType,
                'season' => $season,
                'timing' => $timing
            ]);
            
            return new JsonResponse([
                'success' => true,
                'timing' => $timing,
                'recommendations' => $this->getAIRecommendations($cropType, $season, $isDirectSow)
            ]);
            
        } catch (\Exception $e) {
            Log::error('AI crop timing failed: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Failed to get timing recommendations'], 500);
        }
    }

    /**
     * Calculate AI-enhanced timing based on crop, season, and growing method
     */
    private function calculateAITiming(array $baseTiming, array $seasonalAdjustments, bool $isDirectSow, string $cropType, string $season): array
    {
        $timing = [
            'days_to_transplant' => $baseTiming['transplant_days'],
            'days_to_harvest' => $baseTiming['harvest_days'],
            'harvest_window' => $baseTiming['yield_period']
        ];
        
        // Apply direct sow adjustments
        if ($isDirectSow) {
            $timing['days_to_transplant'] = 0;
            // Direct sow crops often take slightly longer to harvest
            $timing['days_to_harvest'] += $this->getDirectSowAdjustment($cropType);
        }
        
        // Apply seasonal adjustments
        $timing['days_to_transplant'] = max(0, $timing['days_to_transplant'] + $seasonalAdjustments['transplant_adjustment']);
        $timing['days_to_harvest'] = max(1, $timing['days_to_harvest'] + $seasonalAdjustments['harvest_adjustment']);
        $timing['harvest_window'] = max(7, $timing['harvest_window'] + $seasonalAdjustments['window_adjustment']);
        
        // Apply crop-specific seasonal logic
        $timing = $this->applyCropSpecificSeasonalLogic($timing, $cropType, $season);
        
        return $timing;
    }

    /**
     * Get seasonal adjustments for timing calculations
     */
    private function getSeasonalAdjustments(string $season): array
    {
        // Convert to lowercase for consistent matching
        $season = strtolower($season);
        
        $adjustments = [
            'spring' => [
                'transplant_adjustment' => 0,    // Standard timing
                'harvest_adjustment' => 0,
                'window_adjustment' => 0
            ],
            'summer' => [
                'transplant_adjustment' => -3,   // Faster growth in heat
                'harvest_adjustment' => -7,     // Quicker maturation
                'window_adjustment' => -3       // Shorter harvest window (bolting risk)
            ],
            'fall' => [
                'transplant_adjustment' => 2,    // Slightly slower establishment
                'harvest_adjustment' => 5,      // Slower growth in cooling weather
                'window_adjustment' => 7        // Longer harvest window (less bolting)
            ],
            'winter' => [
                'transplant_adjustment' => 7,    // Much slower establishment
                'harvest_adjustment' => 14,     // Significantly slower growth
                'window_adjustment' => 14       // Extended harvest window
            ]
        ];
        
        return $adjustments[$season] ?? $adjustments['spring'];
    }

    /**
     * Get direct sow timing adjustments for specific crops
     */
    private function getDirectSowAdjustment(string $cropType): int
    {
        $adjustments = [
            'lettuce' => 7,      // Direct sow lettuce takes longer to establish
            'carrot' => 0,       // Carrots are typically direct sown
            'radish' => 0,       // Radishes are typically direct sown
            'spinach' => 5,      // Direct sow spinach slightly slower
            'arugula' => 3,      // Minimal difference for arugula
            'kale' => 10,        // Kale benefits from transplant head start
            'chard' => 7,        // Chard establishes better as transplant
            'beet' => 0,         // Beets are typically direct sown
            'turnip' => 0,       // Turnips are typically direct sown
            'cucumber' => 14,    // Cucumbers much better as transplants
            'tomato' => 21,      // Tomatoes require transplants in most climates
            'pepper' => 21,      // Peppers require transplants
            'basil' => 10,       // Basil better as transplant
            'cilantro' => 0,     // Cilantro often direct sown
            'dill' => 0,         // Dill typically direct sown
            'scallion' => 5,     // Scallions can be either
            'mesclun' => 0       // Mix is typically direct sown
        ];
        
        return $adjustments[$cropType] ?? 7; // Default 7-day adjustment for unknown crops
    }

    /**
     * Apply crop-specific seasonal logic
     */
    private function applyCropSpecificSeasonalLogic(array $timing, string $cropType, string $season): array
    {
        // Cool season crops (lettuce, spinach, kale, etc.)
        $coolSeasonCrops = ['lettuce', 'spinach', 'arugula', 'kale', 'chard', 'radish', 'turnip', 'cilantro', 'dill', 'mesclun'];
        
        // Warm season crops (tomato, pepper, cucumber, basil)
        $warmSeasonCrops = ['tomato', 'pepper', 'cucumber', 'basil'];
        
        if (in_array($cropType, $coolSeasonCrops)) {
            if ($season === 'summer') {
                // Cool season crops bolt quickly in summer heat
                $timing['harvest_window'] = max(7, $timing['harvest_window'] - 7);
                $timing['days_to_harvest'] -= 3; // Harvest earlier before bolting
            } elseif ($season === 'fall' || $season === 'winter') {
                // Cool season crops thrive in cool weather
                $timing['harvest_window'] += 7; // Extended harvest window
            }
        }
        
        if (in_array($cropType, $warmSeasonCrops)) {
            if ($season === 'winter') {
                // Warm season crops may not be viable in winter
                $timing['days_to_harvest'] += 21; // Much slower growth
                $timing['harvest_window'] -= 7;   // Shorter viable period
            } elseif ($season === 'summer') {
                // Warm season crops thrive in heat
                $timing['days_to_harvest'] -= 7;  // Faster maturation
                $timing['harvest_window'] += 14;  // Longer productive period
            }
        }
        
        return $timing;
    }

    /**
     * Get AI recommendations for the crop and conditions
     */
    private function getAIRecommendations(string $cropType, string $season, bool $isDirectSow): array
    {
        $recommendations = [];
        
        // Convert season to lowercase for consistent matching
        $season = strtolower($season);
        
        // Seasonal recommendations
        $seasonalTips = [
            'spring' => 'Ideal growing conditions. Watch for late frost risks.',
            'summer' => 'Hot weather. Provide shade for cool-season crops and ensure adequate water.',
            'fall' => 'Cool growing season. Excellent for greens and root vegetables.',
            'winter' => 'Slow growth period. Consider row covers or greenhouse protection.'
        ];
        
        $recommendations[] = $seasonalTips[$season] ?? $seasonalTips['spring'];
        
        // Direct sow vs transplant recommendations
        if ($isDirectSow) {
            $recommendations[] = 'Direct seeding: Ensure consistent soil moisture for germination.';
            
            // Crop-specific direct sow tips
            switch ($cropType) {
                case 'lettuce':
                    $recommendations[] = 'Lettuce: Thin seedlings to 6-8" spacing. Successive plant every 2 weeks.';
                    break;
                case 'carrot':
                    $recommendations[] = 'Carrots: Do not transplant. Thin to 2" spacing when 2" tall.';
                    break;
                case 'radish':
                    $recommendations[] = 'Radishes: Quick crop. Can interplant with slower vegetables.';
                    break;
            }
        } else {
            $recommendations[] = 'Transplanting: Start seeds ' . (14 + ($season === 'winter' ? 7 : 0)) . ' days before transplant date.';
        }
        
        // Crop-specific recommendations
        switch ($cropType) {
            case 'lettuce':
                $recommendations[] = 'Lettuce: Harvest outer leaves for continuous production.';
                break;
            case 'tomato':
                $recommendations[] = 'Tomatoes: Stake or cage for support. Prune suckers for better fruit development.';
                break;
            case 'cucumber':
                $recommendations[] = 'Cucumbers: Provide trellis support. Harvest regularly to encourage production.';
                break;
            case 'basil':
                $recommendations[] = 'Basil: Pinch flowers to encourage leaf growth. Harvest regularly.';
                break;
        }
        
        return $recommendations;
    }

    /**
     * Get current season based on date
     */
    private function getCurrentSeason(): string
    {
        $month = (int) date('n');
        
        if ($month >= 3 && $month <= 5) {
            return 'spring';
        } elseif ($month >= 6 && $month <= 8) {
            return 'summer';
        } elseif ($month >= 9 && $month <= 11) {
            return 'fall';
        } else {
            return 'winter';
        }
    }

    /**
     * 🌟 Get holistic AI crop recommendations with sacred geometry and lunar wisdom
     */
    public function getHolisticRecommendations(Request $request): JsonResponse
    {
        try {
            $cropType = $request->input('crop_type');
            $season = $request->input('season', $this->getCurrentSeason());
            
            if (!$cropType) {
                return response()->json(['error' => 'Crop type is required'], 400);
            }

            Log::info('🌙 Getting holistic recommendations', [
                'crop' => $cropType,
                'season' => $season,
                'moon_phase' => 'checking'
            ]);

            // Get comprehensive holistic recommendations
            $holisticRec = $this->holisticAI->getHolisticRecommendations($cropType, [
                'season' => $season,
                'include_sacred_geometry' => true,
                'include_lunar_timing' => true,
                'include_biodynamic' => true
            ]);

            // Get sacred geometry spacing
            $spacing = $this->holisticAI->getSacredGeometrySpacing($cropType);

            // Get companion mandala
            $companions = $this->holisticAI->getCompanionMandala($cropType);

            // Get current lunar timing
            $lunarTiming = $this->holisticAI->getCurrentLunarTiming();

            $response = [
                'success' => true,
                'crop' => $cropType,
                'season' => $season,
                'holistic_wisdom' => $holisticRec,
                'sacred_spacing' => $spacing,
                'companion_mandala' => $companions,
                'lunar_timing' => $lunarTiming,
                'integration_notes' => [
                    '🌙 Plant during optimal lunar phase for maximum vitality',
                    '🌀 Use golden ratio spacing (1:1.618) for harmonious energy flow', 
                    '🌸 Create companion mandalas for living ecosystem balance',
                    '⭐ Honor cosmic timing for enhanced growth and flavor'
                ]
            ];

            Log::info('✨ Holistic recommendations generated successfully', [
                'crop' => $cropType,
                'has_sacred_geometry' => !empty($spacing),
                'has_companions' => !empty($companions),
                'lunar_phase' => $lunarTiming['current_phase'] ?? 'unknown'
            ]);

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('🚨 Holistic recommendations failed: ' . $e->getMessage());
            
            // Fallback to basic recommendations with mystical inspiration
            return response()->json([
                'success' => true,
                'crop' => $request->input('crop_type'),
                'holistic_wisdom' => $this->getFallbackHolisticWisdom($request->input('crop_type')),
                'message' => '🌱 Using ancient wisdom while cosmic connections restore...'
            ]);
        }
    }

    /**
     * 🌙 Get current moon phase and optimal planting timing
     */
    public function getMoonPhaseGuidance(Request $request): JsonResponse
    {
        try {
            $guidance = $this->holisticAI->getCurrentLunarTiming();
            
            return response()->json([
                'success' => true,
                'lunar_guidance' => $guidance,
                'cosmic_wisdom' => [
                    'New Moon' => 'Perfect for planting seeds - earth energy is receptive to new beginnings',
                    'Waxing Crescent' => 'Excellent for transplanting - growth energy is building',
                    'First Quarter' => 'Time for balanced maintenance and strengthening plant support',
                    'Waxing Gibbous' => 'Monitor and adjust - plants absorbing maximum cosmic energy',
                    'Full Moon' => 'Optimal harvest time - maximum life force and flavor concentration',
                    'Waning Gibbous' => 'Processing and preservation - cosmic energy moving inward',
                    'Last Quarter' => 'Pruning and removing - releasing what no longer serves',
                    'Waning Crescent' => 'Rest and soil restoration - preparing for next lunar cycle'
                ]
            ]);

        } catch (\Exception $e) {
            Log::warning('Moon phase guidance unavailable: ' . $e->getMessage());
            
            return response()->json([
                'success' => true,
                'lunar_guidance' => $this->getBasicLunarWisdom(),
                'message' => 'Using traditional lunar wisdom while cosmic connections restore...'
            ]);
        }
    }

    /**
     * 🌀 Get sacred geometry spacing recommendations
     */
    public function getSacredSpacing(Request $request): JsonResponse
    {
        try {
            $cropType = $request->input('crop_type');
            
            if (!$cropType) {
                return response()->json(['error' => 'Crop type is required'], 400);
            }

            $spacing = $this->holisticAI->getSacredGeometrySpacing($cropType);
            
            return response()->json([
                'success' => true,
                'crop' => $cropType,
                'sacred_spacing' => $spacing,
                'geometry_wisdom' => [
                    'Golden Ratio (φ = 1.618)' => 'Nature\'s perfect proportion found in sunflowers, nautilus shells, and galaxy spirals',
                    'Fibonacci Sequence' => 'Sacred numbers: 1, 1, 2, 3, 5, 8, 13, 21, 34... for optimal plant arrangements',
                    'Hexagonal Patterns' => 'Six-sided formations maximize energy exchange and space efficiency',
                    'Spiral Arrangements' => 'Follow natural vortex patterns for enhanced vitality flow'
                ]
            ]);

        } catch (\Exception $e) {
            Log::warning('Sacred spacing unavailable: ' . $e->getMessage());
            
            return response()->json([
                'success' => true,
                'sacred_spacing' => $this->getBasicSacredSpacing($request->input('crop_type')),
                'message' => 'Using geometric principles while holistic service restores...'
            ]);
        }
    }

    /**
     * 🌸 Enhance existing succession plan with holistic AI wisdom
     */
    private function enhanceWithHolisticWisdom(array $plan, array $params): array
    {
        try {
            // Add holistic enhancements to the basic plan
            $enhanced = $this->holisticAI->enhanceSuccessionPlan($plan, $params);
            
            if ($enhanced['success'] ?? false) {
                Log::info('✨ Plan enhanced with holistic wisdom', [
                    'crop' => $params['crop_type'],
                    'enhancements' => count($enhanced['holistic_enhancements'] ?? [])
                ]);
                
                return $enhanced;
            }
            
            return $plan;
            
        } catch (\Exception $e) {
            Log::warning('Holistic enhancement failed, using base plan: ' . $e->getMessage());
            return $plan;
        }
    }

    /**
     * Fallback holistic wisdom when AI service is unavailable
     */
    private function getFallbackHolisticWisdom(string $cropType): array
    {
        return [
            'ancient_wisdom' => "🌱 {$cropType} carries the wisdom of countless seasons. Plant with intention and gratitude.",
            'elemental_guidance' => $this->getElementalGuidance($cropType),
            'seasonal_harmony' => 'Align your planting with natural rhythms - early morning for peace, evening for reflection.',
            'companion_spirits' => $this->getBasicCompanions($cropType),
            'sacred_reminder' => 'Every seed contains infinite potential. Honor the mystery of growth.'
        ];
    }

    private function getElementalGuidance(string $cropType): string
    {
        $elements = [
            'lettuce' => 'Water element - flows with lunar cycles, thrives with gentle moisture',
            'carrot' => 'Earth element - deep roots connect to underground wisdom',
            'radish' => 'Fire element - quick transformation, cleansing energy',
            'spinach' => 'Water element - cooling energy, lunar-responsive growth',
            'kale' => 'Earth element - sturdy constitution, grounding energy',
            'arugula' => 'Fire element - spicy life force, awakening energy'
        ];
        
        return $elements[$cropType] ?? 'Mixed elements - balanced approach honors all aspects of nature';
    }

    private function getBasicCompanions(string $cropType): array
    {
        $companions = [
            'lettuce' => ['Radish (pest protection)', 'Marigold (beneficial insects)', 'Chives (growth enhancement)'],
            'carrot' => ['Chives (flavor improvement)', 'Rosemary (pest deterrent)', 'Sage (energetic protection)'],
            'radish' => ['Lettuce (space sharing)', 'Spinach (soil improvement)', 'Calendula (healing energy)'],
            'spinach' => ['Strawberry (ground cover)', 'Thyme (aromatic support)', 'Borage (mineral uptake)'],
            'kale' => ['Nasturtium (pest control)', 'Dill (beneficial insects)', 'Chamomile (soil health)'],
            'arugula' => ['Basil (flavor synergy)', 'Oregano (protection)', 'Parsley (companion support)']
        ];
        
        return $companions[$cropType] ?? ['Marigold (universal companion)', 'Basil (harmony)', 'Chamomile (gentle healing)'];
    }

    private function getBasicLunarWisdom(): array
    {
        $currentDay = date('j');
        $phase = $currentDay <= 7 ? 'waxing' : ($currentDay <= 14 ? 'full' : ($currentDay <= 21 ? 'waning' : 'new'));
        
        return [
            'current_phase' => $phase,
            'guidance' => "Current lunar energy supports {$phase} moon activities",
            'planting_advice' => 'Plant seeds during new moon, transplant during waxing, harvest during full moon',
            'cosmic_reminder' => 'Moon cycles guide the flow of water and energy in all living things'
        ];
    }

    private function getBasicSacredSpacing(string $cropType): array
    {
        $baseSpacing = [
            'lettuce' => 6, 'carrot' => 2, 'radish' => 1,
            'spinach' => 4, 'kale' => 12, 'arugula' => 4
        ];
        
        $spacing = $baseSpacing[$cropType] ?? 6;
        $goldenRatio = 1.618;
        
        return [
            'plant_spacing_inches' => $spacing,
            'row_spacing_inches' => round($spacing * $goldenRatio, 1),
            'bed_width_ratio' => $spacing * 8, // Fibonacci number
            'path_width_ratio' => $spacing * 3, // Fibonacci number
            'sacred_note' => 'Spacing based on golden ratio (φ = 1.618) and Fibonacci sequence'
        ];
    }
}
