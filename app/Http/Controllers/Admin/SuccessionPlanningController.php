<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FarmOSApi;
use App\Services\AI\SymbiosisAIService;
use App\Models\PlantVariety;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SuccessionPlanningController extends Controller
{
    protected $farmOSApi;
    protected $symbiosisAI;

    public function __construct(FarmOSApi $farmOSApi, SymbiosisAIService $symbiosisAI)
    {
        $this->farmOSApi = $farmOSApi;
        $this->symbiosisAI = $symbiosisAI;
    }

    /**
     * Display the succession planning interface
     */
    public function index()
    {
        // Temporarily disable AI wake-up to debug the include error
        // try {
        //     $this->wakeUpAIService();
        // } catch (\Exception $e) {
        //     // Don't let AI wake-up failures break the page load
        //     Log::debug('AI wake-up failed during page load: ' . $e->getMessage());
        // }
        
        try {
            // Get crop data from local database (much faster than FarmOS API)
            $cropData = $this->getCropDataFromLocalDatabase();
            $geometryAssets = $this->farmOSApi->getGeometryAssets();
            $availableBeds = $this->extractAvailableBeds($geometryAssets);
            
            // Use fallback data only if local database is empty
            if (empty($cropData['types'])) {
                $this->command->info('Local database empty, falling back to FarmOS API');
                $cropData = $this->farmOSApi->getAvailableCropTypes();
            }
            
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
                    
                    // Create seeding log in farmOS
                    if (!empty($planting['seeding_date'])) {
                        $seedingData = [
                            'crop_name' => $planting['crop'],
                            'timestamp' => $planting['seeding_date'],
                            'location_id' => $planting['bed_id'],
                            'notes' => "Succession #{$planting['sequence']}: " . (($planting['direct_sow'] ?? false) ? 'Direct Sow' : 'Seeding') . " - " . ($planting['notes'] ?? ''),
                            'quantity' => $planting['quantity'] ?? null,
                            'quantity_unit' => 'count'
                        ];
                        
                        $seedingLog = $this->farmOSApi->createSeedingLog($seedingData);
                        $createdLogs[] = $seedingLog;
                        $farmOSPlans[] = $seedingLog;
                    }

                    // Create transplant log in farmOS (only for non-direct-sow crops)
                    if (!empty($planting['transplant_date']) && !($planting['direct_sow'] ?? false)) {
                        $transplantingData = [
                            'crop_name' => $planting['crop'],
                            'timestamp' => $planting['transplant_date'],
                            'source_location_id' => $planting['bed_id'], // From seeding bed
                            'destination_location_id' => $planting['bed_id'], // To final location
                            'notes' => "Succession #{$planting['sequence']}: Transplant - " . ($planting['notes'] ?? ''),
                            'quantity' => $planting['quantity'] ?? null,
                            'quantity_unit' => 'count'
                        ];
                        
                        $transplantLog = $this->farmOSApi->createTransplantingLog($transplantingData);
                        $createdLogs[] = $transplantLog;
                        $farmOSPlans[] = $transplantLog;
                    }

                    // Create harvest log in farmOS
                    if (!empty($planting['harvest_date'])) {
                        $harvestData = [
                            'crop_name' => $planting['crop'],
                            'timestamp' => $planting['harvest_date'],
                            'location_id' => $planting['bed_id'],
                            'notes' => "Succession #{$planting['sequence']}: Harvest - " . ($planting['notes'] ?? ''),
                            'quantity' => $planting['expected_yield'] ?? null,
                            'quantity_unit' => 'weight'
                        ];
                        
                        $harvestLog = $this->farmOSApi->createHarvestLog($harvestData);
                        $createdLogs[] = $harvestLog;
                        $farmOSPlans[] = $harvestLog;
                    }

                } catch (\Exception $e) {
                    $errors[] = "Failed to create farmOS logs for planting #{$planting['sequence']}: " . $e->getMessage();
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
                'message' => count($createdLogs) . ' farmOS logs created successfully',
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
     * Create individual succession planting log from form data
     */
    public function createSingleLog(Request $request)
    {
        $validated = $request->validate([
            'succession_id' => 'required|string',
            'crop_name' => 'required|string',
            'variety_name' => 'nullable|string',
            'seeding' => 'required|array',
            'seeding.date' => 'required|date',
            'seeding.location' => 'required|string',
            'seeding.quantity' => 'nullable|string',
            'seeding.notes' => 'nullable|string',
            'seeding.direct_sow' => 'boolean',
            'transplant' => 'nullable|array',
            'transplant.date' => 'nullable|date',
            'transplant.location' => 'nullable|string',
            'transplant.quantity' => 'nullable|string',
            'transplant.spacing' => 'nullable|string',
            'transplant.notes' => 'nullable|string',
            'harvest' => 'required|array',
            'harvest.date' => 'required|date',
            'harvest.expected_yield' => 'nullable|string',
            'harvest.notes' => 'nullable|string',
        ]);

        try {
            $createdLogs = [];
            $errors = [];

            // Create seeding log directly
            $seedingData = [
                'crop_name' => $validated['crop_name'],
                'timestamp' => $validated['seeding']['date'],
                'location_id' => $validated['seeding']['location'],
                'notes' => "Succession #{$validated['succession_id']}: " . (($validated['seeding']['direct_sow'] ?? false) ? 'Direct Sow' : 'Seeding') . " - " . ($validated['seeding']['notes'] ?? 'AI-calculated succession seeding'),
                'quantity' => $validated['seeding']['quantity'] ?? null,
                'quantity_unit' => 'count'
            ];
            
            $seedingLog = $this->farmOSApi->createSeedingLog($seedingData);
            $createdLogs[] = $seedingLog;

            // Create transplant log directly if scheduled
            if (!empty($validated['transplant']) && !empty($validated['transplant']['date'])) {
                $transplantingData = [
                    'crop_name' => $validated['crop_name'],
                    'timestamp' => $validated['transplant']['date'],
                    'source_location_id' => $validated['seeding']['location'], // From seeding location
                    'destination_location_id' => $validated['transplant']['location'], // To transplant location
                    'notes' => "Succession #{$validated['succession_id']}: Transplant - " . ($validated['transplant']['notes'] ?? 'AI-calculated succession transplant'),
                    'quantity' => $validated['transplant']['quantity'] ?? null,
                    'quantity_unit' => 'count'
                ];
                
                $transplantLog = $this->farmOSApi->createTransplantingLog($transplantingData);
                $createdLogs[] = $transplantLog;
            }

            // Create harvest log directly
            $harvestData = [
                'crop_name' => $validated['crop_name'],
                'timestamp' => $validated['harvest']['date'],
                'location_id' => $validated['transplant']['location'] ?? $validated['seeding']['location'],
                'notes' => "Succession #{$validated['succession_id']}: Harvest - " . ($validated['harvest']['notes'] ?? 'AI-calculated succession harvest'),
                'quantity' => $validated['harvest']['expected_yield'] ?? null,
                'quantity_unit' => 'weight'
            ];
            
            $harvestLog = $this->farmOSApi->createHarvestLog($harvestData);
            $createdLogs[] = $harvestLog;

            Log::info('Single succession log created in farmOS', [
                'succession_id' => $validated['succession_id'],
                'crop' => $validated['crop_name'],
                'logs_created' => count($createdLogs)
            ]);

            return response()->json([
                'success' => true,
                'created_logs' => count($createdLogs),
                'message' => "Succession #{$validated['succession_id']} logs created successfully in farmOS"
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create single succession log in farmOS: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create farmOS logs: ' . $e->getMessage()
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

        // 🧠 Get AI-optimized harvest window analysis
        try {
            $contextualData = [
                'weather_forecast' => $this->getCurrentSeasonDescription(),
                'current_season_performance' => 'Planning for ' . $firstSeedingDate->format('F Y'),
                'succession_context' => "Planning {$successionCount} successions with {$intervalDays} day intervals",
                'crop_family' => $this->getCropFamily($cropType),
                'frost_tolerance' => $this->getFrostTolerance($cropType),
                'days_to_maturity' => $this->getDaysToMaturity($cropType),
                'seasonal_adjustments' => $this->getSeasonalAdjustments($this->getCurrentSeason()),
                'beds' => $this->getFallbackBeds() // Available bed data
            ];

            // Use enhanced HuggingFace AI service with FarmOS form field requirements
            $aiHarvestWindow = $this->getHuggingFaceHarvestWindow($cropType, $variety, $contextualData);

            if ($aiHarvestWindow['success']) {
                // Use AI recommendations for timing
                $transplantToHarvestDays = $aiHarvestWindow['peak_harvest_days'] ?? $validated['transplant_to_harvest_days'];
                $harvestDurationDays = $aiHarvestWindow['optimal_harvest_days'] ?? $validated['harvest_duration_days'];
                $recommendedInterval = $aiHarvestWindow['days_between_plantings'] ?? $intervalDays;

                // Adjust interval if AI suggests different timing
                if (abs($recommendedInterval - $intervalDays) <= 7) {
                    $intervalDays = $recommendedInterval;
                }

                $aiUsed = true;
                $aiSource = $aiHarvestWindow['source'];
                $aiConfidence = $aiHarvestWindow['ai_confidence'] ?? 'medium';
                $farmOSFormData = $aiHarvestWindow['farmOS_form_data'] ?? [];
            } else {
                // Fallback to user input
                $transplantToHarvestDays = $validated['transplant_to_harvest_days'];
                $harvestDurationDays = $validated['harvest_duration_days'];
                $aiUsed = false;
                $aiSource = 'manual_input';
                $aiConfidence = 'user_specified';
                $farmOSFormData = [];
            }
        } catch (\Exception $e) {
            Log::warning('AI harvest analysis failed, using manual input: ' . $e->getMessage());
            $transplantToHarvestDays = $validated['transplant_to_harvest_days'];
            $harvestDurationDays = $validated['harvest_duration_days'];
            $aiUsed = false;
            $aiSource = 'fallback_manual';
            $aiConfidence = 'user_specified';
            $farmOSFormData = [];
        }

        // Get crop timing from user input (preferred) or presets
        $seedingToTransplantDays = $isDirectSow ? 0 : ($validated['seeding_to_transplant_days'] ?? 0);
        
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
                'conflicts' => [],
                'ai_optimized' => $aiUsed,
                'ai_confidence' => $aiConfidence,
                // FarmOS Quick Form Data from AI
                'farmOS_form_data' => [
                    'seeding_method' => $farmOSFormData['seeding_method'] ?? ($isDirectSow ? 'direct_sow' : 'transplant'),
                    'container_type' => $farmOSFormData['container_type'] ?? (!$isDirectSow ? '72-cell' : null),
                    'soil_medium' => $farmOSFormData['soil_medium'] ?? (!$isDirectSow ? 'organic_seed_mix' : null),
                    'germination_days' => $farmOSFormData['germination_days'] ?? (!$isDirectSow ? 7 : 0),
                    'seeding_notes' => $farmOSFormData['seeding_notes'] ?? 'AI-optimized seeding for succession #' . ($i + 1),
                    'transplant_lead_weeks' => $farmOSFormData['transplant_lead_weeks'] ?? (!$isDirectSow ? 4 : 0),
                    'in_row_spacing_cm' => $farmOSFormData['in_row_spacing_cm'] ?? $this->getDefaultSpacing($cropType)['in_row'],
                    'row_spacing_cm' => $farmOSFormData['row_spacing_cm'] ?? $this->getDefaultSpacing($cropType)['row'],
                    'planting_depth_cm' => $farmOSFormData['planting_depth_cm'] ?? 1,
                    'initial_watering' => $farmOSFormData['initial_watering'] ?? 'Water thoroughly after transplanting',
                    'transplant_notes' => $farmOSFormData['transplant_notes'] ?? 'AI-optimized transplant timing',
                    'harvest_window_days' => $farmOSFormData['harvest_window_days'] ?? $harvestDurationDays,
                    'expected_yield_unit' => $farmOSFormData['expected_yield_unit'] ?? 'pounds',
                    'harvest_method' => $farmOSFormData['harvest_method'] ?? 'continuous',
                    'storage_instructions' => $farmOSFormData['storage_instructions'] ?? 'Refrigerate immediately',
                    'harvest_notes' => $farmOSFormData['harvest_notes'] ?? 'AI-optimized harvest timing'
                ]
            ];
        }

        return [
            'crop_type' => $cropType,
            'variety' => $variety,
            'total_plantings' => $successionCount,
            'interval_days' => $intervalDays,
            'direct_sow' => $isDirectSow,
            'plantings' => $plantings,
            'ai_enhanced' => $aiUsed,
            'ai_source' => $aiSource,
            'ai_confidence' => $aiConfidence,
            'ai_recommendations' => $aiHarvestWindow['contextual_factors'] ?? []
        ];
    }

    /**
     * Get crop data from local database
     */
    private function getCropDataFromLocalDatabase()
    {
        try {
            // Get all active varieties from local database
            $varieties = PlantVariety::active()
                                    ->select('id', 'farmos_id', 'name', 'plant_type', 'plant_type_id', 'description', 'crop_family', 'season', 'frost_tolerance', 'maturity_days')
                                    ->get();

            // Group varieties by plant type
            $typesMap = [];
            $varietiesByType = [];
            $allVarieties = []; // Flat array for JavaScript

            foreach ($varieties as $variety) {
                $plantType = $variety->plant_type ?? 'Unknown';
                $plantTypeId = $variety->plant_type_id ?? $variety->farmos_id;

                if (!isset($typesMap[$plantTypeId])) {
                    $typesMap[$plantTypeId] = [
                        'id' => $plantTypeId,
                        'name' => $plantType,
                        'label' => ucfirst(strtolower($plantType))
                    ];
                }

                $varietyData = [
                    'id' => $variety->farmos_id, // Use farmos_id as the main ID for compatibility
                    'name' => $variety->name,
                    'label' => $variety->name,
                    'description' => $variety->description,
                    'crop_type' => $plantTypeId,
                    'crop_id' => $plantTypeId, // Add crop_id for backward compatibility
                    'parent_id' => $plantTypeId, // Add parent_id for JavaScript filtering
                    'crop_family' => $variety->crop_family,
                    'season' => $variety->season,
                    'frost_tolerance' => $variety->frost_tolerance,
                    'maturity_days' => $variety->maturity_days,
                    'local_db_id' => $variety->id // Keep local DB ID for reference
                ];

                $varietiesByType[$plantTypeId][] = $varietyData;
                $allVarieties[] = $varietyData; // Add to flat array
            }

            $types = array_values($typesMap);

            // Sort types alphabetically
            usort($types, function($a, $b) {
                return strcmp($a['label'], $b['label']);
            });

            return [
                'types' => $types,
                'varieties' => $varietiesByType,
                'all_varieties' => $allVarieties // Flat array for JavaScript
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get crop data from local database: ' . $e->getMessage());
            return ['types' => [], 'varieties' => [], 'all_varieties' => []];
        }
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

            $response = Http::timeout(3)->post(env('AI_SERVICE_URL', 'http://localhost:8005'), [
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
                    'area' => 100 + ($block * $bed * 10) % 150 // Dynamic area calculation
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
            'fennel' => [
                'transplant_days' => 21,  // Can be transplanted or direct seeded
                'harvest_days' => 85,     // Seed to harvest (bulb fennel)
                'yield_period' => 14      // Harvest window for bulbs
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
            $holisticRec = $this->symbiosisAI->getHolisticRecommendations($cropType, [
                'season' => $season,
                'include_sacred_geometry' => true,
                'include_lunar_timing' => true,
                'include_biodynamic' => true
            ]);

            // Get sacred geometry spacing
            $spacing = $this->symbiosisAI->getSacredGeometrySpacing($cropType);

            // Get companion mandala
            $companions = $this->symbiosisAI->getCompanionMandala($cropType);

            // Get current lunar timing
            $lunarTiming = $this->symbiosisAI->getCurrentLunarTiming();

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
            $guidance = $this->symbiosisAI->getCurrentLunarTiming();
            
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

            $spacing = $this->symbiosisAI->getSacredGeometrySpacing($cropType);
            
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
            $enhanced = $this->symbiosisAI->enhanceSuccessionPlan($plan, $params);
            
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

    /**
     * Get current season description for AI context
     */
    private function getCurrentSeasonDescription(): string
    {
        $month = now()->month;
        
        if (in_array($month, [12, 1, 2])) {
            return 'Winter conditions: Cold temperatures, limited growing season, protected cultivation recommended';
        } elseif (in_array($month, [3, 4, 5])) {
            return 'Spring conditions: Cool, wet conditions with increasing daylight, risk of late frost';
        } elseif (in_array($month, [6, 7, 8])) {
            return 'Summer conditions: Warm, stable conditions, monitor for heat stress and drought';
        } else {
            return 'Autumn conditions: Cooling temperatures with shorter days, focus on cold-hardy varieties';
        }
    }

    /**
     * Get AI-optimized harvest window for succession planning
     */
    private function getHuggingFaceHarvestWindow(string $cropType, string $variety, array $contextualData): array
    {
        try {
            // Enhanced prompt with FarmOS quick form field requirements
            $farmOSPrompt = "
            Analyze {$cropType} ({$variety}) for succession planning and provide complete FarmOS quick form data.

            CONTEXT:
            - Season: {$contextualData['current_season_performance']}
            - Succession planning: {$contextualData['succession_context']}
            - Weather: {$contextualData['weather_forecast']}

            REQUIRED FarmOS QUICK FORM FIELDS - Return ALL of these:

            SEEDING DATA:
            - seeding_method: 'direct_sow' or 'transplant'
            - container_type: For transplants ('72-cell', '4-inch_pot', 'plug_tray', null for direct)
            - soil_medium: ('organic_seed_mix', 'peat_pellets', 'soil_blocks', 'direct_soil')
            - germination_days: Expected days to germination (integer)
            - seeding_notes: Specific seeding instructions and timing

            TRANSPLANT DATA (if transplant method):
            - transplant_lead_weeks: Weeks to start seeds before transplant (integer)
            - in_row_spacing_cm: Spacing between plants in row (integer)
            - row_spacing_cm: Spacing between rows (integer)
            - planting_depth_cm: How deep to plant seedlings (integer)
            - initial_watering: Initial watering instructions (string)
            - transplant_notes: Transplant timing and method notes

            HARVEST DATA:
            - harvest_window_days: Days harvest window remains viable (integer)
            - expected_yield_unit: ('pounds', 'ounces', 'count', 'bunches', 'heads')
            - harvest_method: ('single_harvest', 'cut_and_come_again', 'continuous', 'progressive')
            - storage_instructions: Post-harvest handling and storage
            - harvest_notes: Harvest timing optimization notes

            SUCCESSION OPTIMIZATION:
            - optimal_succession_interval_days: Recommended days between plantings (integer)
            - max_successions_per_season: Maximum viable successions (integer)
            - seasonal_adjustments: Any season-specific modifications needed

            Provide realistic, farm-proven recommendations based on crop characteristics and seasonal context.
            ";

            $response = Http::timeout(30)->post(env('AI_SERVICE_URL', 'http://localhost:8005') . '/ask', [
                'question' => $farmOSPrompt,
                'context' => json_encode($contextualData)
            ]);

            if ($response->successful()) {
                $aiData = $response->json();
                $answer = $aiData['answer'] ?? 'No AI response available';

                // Parse AI response for structured data
                $structuredData = $this->parseAIResponseForFarmOS($answer);

                return [
                    'success' => true,
                    'source' => 'huggingface_ai',
                    'ai_confidence' => 'high',
                    'peak_harvest_days' => $structuredData['peak_harvest_days'] ?? 60,
                    'optimal_harvest_days' => $structuredData['harvest_window_days'] ?? 21,
                    'days_between_plantings' => $structuredData['optimal_succession_interval_days'] ?? 14,
                    'contextual_factors' => [$answer],
                    'farmOS_form_data' => $structuredData
                ];
            }

            return [
                'success' => false,
                'source' => 'fallback',
                'error' => 'AI service unavailable'
            ];

        } catch (\Exception $e) {
            Log::warning('HuggingFace harvest analysis failed: ' . $e->getMessage());
            return [
                'success' => false,
                'source' => 'error_fallback',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Parse AI response to extract FarmOS quick form field data
     */
    private function parseAIResponseForFarmOS(string $aiResponse): array
    {
        $structuredData = [];

        // Extract seeding method
        if (preg_match('/seeding_method:\s*[\'"]([^\'"]+)[\'"]/', $aiResponse, $matches)) {
            $structuredData['seeding_method'] = $matches[1];
        }

        // Extract container type
        if (preg_match('/container_type:\s*[\'"]([^\'"]+)[\'"]/', $aiResponse, $matches)) {
            $structuredData['container_type'] = $matches[1];
        }

        // Extract soil medium
        if (preg_match('/soil_medium:\s*[\'"]([^\'"]+)[\'"]/', $aiResponse, $matches)) {
            $structuredData['soil_medium'] = $matches[1];
        }

        // Extract germination days
        if (preg_match('/germination_days:\s*(\d+)/', $aiResponse, $matches)) {
            $structuredData['germination_days'] = (int)$matches[1];
        }

        // Extract transplant lead weeks
        if (preg_match('/transplant_lead_weeks:\s*(\d+)/', $aiResponse, $matches)) {
            $structuredData['transplant_lead_weeks'] = (int)$matches[1];
        }

        // Extract spacing data
        if (preg_match('/in_row_spacing_cm:\s*(\d+)/', $aiResponse, $matches)) {
            $structuredData['in_row_spacing_cm'] = (int)$matches[1];
        }
        if (preg_match('/row_spacing_cm:\s*(\d+)/', $aiResponse, $matches)) {
            $structuredData['row_spacing_cm'] = (int)$matches[1];
        }

        // Extract harvest window
        if (preg_match('/harvest_window_days:\s*(\d+)/', $aiResponse, $matches)) {
            $structuredData['harvest_window_days'] = (int)$matches[1];
        }

        // Extract yield unit
        if (preg_match('/expected_yield_unit:\s*[\'"]([^\'"]+)[\'"]/', $aiResponse, $matches)) {
            $structuredData['expected_yield_unit'] = $matches[1];
        }

        // Extract harvest method
        if (preg_match('/harvest_method:\s*[\'"]([^\'"]+)[\'"]/', $aiResponse, $matches)) {
            $structuredData['harvest_method'] = $matches[1];
        }

        // Extract succession interval
        if (preg_match('/optimal_succession_interval_days:\s*(\d+)/', $aiResponse, $matches)) {
            $structuredData['optimal_succession_interval_days'] = (int)$matches[1];
        }

        // Extract notes (multi-line capture)
        if (preg_match('/seeding_notes:\s*(.+?)(?=\n\n|\n[A-Z]|$)/s', $aiResponse, $matches)) {
            $structuredData['seeding_notes'] = trim($matches[1]);
        }
        if (preg_match('/transplant_notes:\s*(.+?)(?=\n\n|\n[A-Z]|$)/s', $aiResponse, $matches)) {
            $structuredData['transplant_notes'] = trim($matches[1]);
        }
        if (preg_match('/harvest_notes:\s*(.+?)(?=\n\n|\n[A-Z]|$)/s', $aiResponse, $matches)) {
            $structuredData['harvest_notes'] = trim($matches[1]);
        }

        return $structuredData;
    }

    /**
     * Get crop family information for AI context
     */
    private function getCropFamily(string $cropType): string
    {
        $families = [
            'lettuce' => 'asteraceae',
            'carrot' => 'apiaceae',
            'radish' => 'brassicaceae',
            'spinach' => 'amaranthaceae',
            'kale' => 'brassicaceae',
            'arugula' => 'brassicaceae',
            'chard' => 'amaranthaceae',
            'beets' => 'amaranthaceae',
            'cilantro' => 'apiaceae',
            'dill' => 'apiaceae',
            'scallion' => 'amaryllidaceae',
            'mesclun' => 'mixed_greens',
            'fennel' => 'apiaceae',
            'tomato' => 'solanaceae',
            'pepper' => 'solanaceae',
            'cucumber' => 'cucurbitaceae',
            'basil' => 'lamiaceae'
        ];

        return $families[$cropType] ?? 'unknown';
    }

    /**
     * Get frost tolerance for AI context
     */
    private function getFrostTolerance(string $cropType): string
    {
        $tolerances = [
            'lettuce' => 'half-hardy',
            'carrot' => 'hardy',
            'radish' => 'half-hardy',
            'spinach' => 'hardy',
            'kale' => 'very-hardy',
            'arugula' => 'half-hardy',
            'chard' => 'hardy',
            'beets' => 'hardy',
            'cilantro' => 'tender',
            'dill' => 'half-hardy',
            'scallion' => 'hardy',
            'mesclun' => 'half-hardy',
            'fennel' => 'half-hardy',
            'tomato' => 'tender',
            'pepper' => 'tender',
            'cucumber' => 'tender',
            'basil' => 'tender'
        ];

        return $tolerances[$cropType] ?? 'half-hardy';
    }

    /**
     * Get days to maturity for AI context
     */
    private function getDaysToMaturity(string $cropType): int
    {
        $maturities = [
            'lettuce' => 65,
            'carrot' => 75,
            'radish' => 30,
            'spinach' => 50,
            'kale' => 70,
            'arugula' => 40,
            'chard' => 60,
            'beets' => 70,
            'cilantro' => 45,
            'dill' => 50,
            'scallion' => 60,
            'mesclun' => 30,
            'fennel' => 85,
            'tomato' => 80,
            'pepper' => 75,
            'cucumber' => 55,
            'basil' => 60
        ];

        return $maturities[$cropType] ?? 60;
    }

    /**
     * Get default spacing for crops
     */
    private function getDefaultSpacing(string $cropType): array
    {
        $spacing = [
            'lettuce' => ['in_row' => 25, 'row' => 30],
            'carrot' => ['in_row' => 3, 'row' => 30],
            'radish' => ['in_row' => 3, 'row' => 15],
            'spinach' => ['in_row' => 15, 'row' => 25],
            'kale' => ['in_row' => 45, 'row' => 60],
            'arugula' => ['in_row' => 10, 'row' => 20],
            'chard' => ['in_row' => 25, 'row' => 30],
            'beets' => ['in_row' => 8, 'row' => 30],
            'cilantro' => ['in_row' => 15, 'row' => 25],
            'dill' => ['in_row' => 20, 'row' => 30],
            'scallion' => ['in_row' => 5, 'row' => 20],
            'mesclun' => ['in_row' => 3, 'row' => 15],
            'fennel' => ['in_row' => 30, 'row' => 45],
            'tomato' => ['in_row' => 60, 'row' => 90],
            'pepper' => ['in_row' => 45, 'row' => 60],
            'cucumber' => ['in_row' => 45, 'row' => 60],
            'basil' => ['in_row' => 20, 'row' => 30]
        ];

        return $spacing[$cropType] ?? ['in_row' => 20, 'row' => 30];
    }

    /**
     * Get variety details for AI processing (now from local database)
     */
    public function getVariety(Request $request, $varietyId)
    {
        try {
            // First try to find by FarmOS ID (exact match)
            $variety = PlantVariety::where('farmos_id', $varietyId)->first();

            // If not found by FarmOS ID, try by local ID (only if it's numeric)
            if (!$variety && is_numeric($varietyId)) {
                $variety = PlantVariety::find($varietyId);
            }

            if (!$variety) {
                // Fallback to FarmOS API if not found locally
                $farmOSApi = app(FarmOSApi::class);
                $varietyData = $farmOSApi->getVarietyById($varietyId);

                if ($varietyData) {
                    // Store in local database for future use
                    PlantVariety::updateOrCreate(
                        ['farmos_id' => $varietyData['id']],
                        [
                            'name' => $varietyData['name'] ?? 'Unknown',
                            'description' => $varietyData['description'] ?? '',
                            'farmos_data' => $varietyData,
                            'is_active' => true,
                            'last_synced_at' => now(),
                            'sync_status' => 'synced'
                        ]
                    );
                    return response()->json($varietyData);
                }

                return response()->json(['error' => 'Variety not found'], 404);
            }

            // Enhance variety data with harvest window calculations
            $varietyData = $variety->toArray();

            // Calculate harvest windows based on variety-specific data
            if ($variety->maturity_days || $variety->harvest_days) {
                $daysToHarvest = $variety->maturity_days ?? $variety->harvest_days;
                $season = $variety->season ?? 'Spring';

                // Calculate appropriate planting date based on season and crop type
                $currentYear = date('Y');
                $plantingDate = $this->calculatePlantingDate($season, $variety->name, $currentYear);

                // For transplanted crops, add transplant time
                if ($variety->transplant_days && $variety->transplant_days > 0) {
                    $transplantDate = clone $plantingDate;
                    $transplantDate->modify("+{$variety->transplant_days} days");
                    $varietyData['transplant_date'] = $transplantDate->format('Y-m-d');
                    $harvestStart = clone $transplantDate; // Start harvest calculation from transplant date
                } else {
                    $harvestStart = clone $plantingDate; // Start harvest calculation from planting date
                }

                $harvestStart->modify("+{$daysToHarvest} days");

                $harvestEnd = clone $harvestStart;
                $harvestEnd->modify('+60 days'); // Extended harvest window for Brussels sprouts

                $varietyData['harvest_start'] = $harvestStart->format('Y-m-d');
                $varietyData['harvest_end'] = $harvestEnd->format('Y-m-d');
                $varietyData['days_to_harvest'] = $daysToHarvest;
                
                // Calculate yield peak (don't modify harvestStart)
                $yieldPeak = clone $harvestStart;
                $yieldPeak->modify('+30 days');
                $varietyData['yield_peak'] = $yieldPeak->format('Y-m-d');

                // Add transplant information
                if ($variety->transplant_days && $variety->transplant_days > 0) {
                    $transplantDate = clone $plantingDate;
                    $transplantDate->modify("+{$variety->transplant_days} days");
                    $varietyData['notes'] = 'Transplanted crop. Seed ' . $plantingDate->format('M j') . ', transplant ' . $transplantDate->format('M j') . ', harvest ' . $harvestStart->format('M j') . ' to ' . $harvestEnd->format('M j');
                }
            }

            return response()->json($varietyData);

        } catch (\Exception $e) {
            Log::error('Failed to get variety details: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load variety data'], 500);
        }
    }

    /**
     * Get AI service status for frontend
     */
    public function getAIStatus(Request $request)
    {
        try {
            // Check if Ollama is running and responding
            $ollamaResponse = Http::timeout(5)->get('http://localhost:11434/api/tags');

            if ($ollamaResponse->successful()) {
                $ollamaData = $ollamaResponse->json();
                $models = $ollamaData['models'] ?? [];

                // Check if tinyllama model is available
                $hasTinyLlama = false;
                foreach ($models as $model) {
                    if (strpos($model['name'] ?? '', 'tinyllama') !== false) {
                        $hasTinyLlama = true;
                        break;
                    }
                }

                if ($hasTinyLlama) {
                    return response()->json([
                        'status' => 'online',
                        'message' => 'AI Service Online - TinyLlama ready',
                        'models_available' => count($models),
                        'tinyllama_available' => true
                    ]);
                } else {
                    return response()->json([
                        'status' => 'model_missing',
                        'message' => 'Ollama running but TinyLlama model not found',
                        'models_available' => count($models),
                        'tinyllama_available' => false
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 'offline',
                    'message' => 'Ollama service not responding',
                    'error_code' => $ollamaResponse->status()
                ]);
            }

        } catch (\Exception $e) {
            Log::warning('AI status check failed: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'AI service check failed: ' . $e->getMessage(),
                'error' => true
            ]);
        }
    }

    /**
     * Handle AI chat requests for succession planning
     */
    public function chat(Request $request)
    {
        try {
            $message = $request->input('message');
            $context = $request->input('context', []);

            if (!$message) {
                return response()->json(['error' => 'Message is required'], 400);
            }

            // Build context-aware prompt for succession planning
            $systemPrompt = "You are a farmOS AI assistant specializing in succession planning and crop management. ";
            $systemPrompt .= "You have access to farmOS data including 3600+ plant varieties and bed specifications. ";
            $systemPrompt .= "Provide practical, farm-proven advice for succession planning, crop timing, and growing wisdom. ";
            $systemPrompt .= "Consider seasonal factors, bed spacing, and optimal harvest windows in your recommendations.";

            // Check if this is a harvest window calculation request
            $isHarvestQuery = stripos($message, 'harvest') !== false ||
                             stripos($message, 'timing') !== false ||
                             stripos($message, 'window') !== false ||
                             stripos($message, 'days to') !== false ||
                             ($context['request_type'] ?? '') === 'harvest_window_research';

            if ($isHarvestQuery && (!empty($context['variety']) || !empty($context['variety_name']))) {
                // Enhanced prompt for variety-specific harvest research
                $varietyName = $context['variety'] ?? $context['variety_name'] ?? '';
                $cropName = $context['crop'] ?? $context['crop_name'] ?? '';
                $planningYear = $context['planning_year'] ?? date('Y');

                $systemPrompt = "You are an expert agricultural researcher specializing in crop variety characteristics. ";
                $systemPrompt .= "Research and provide accurate harvest timing data for specific crop varieties. ";
                $systemPrompt .= "Use your knowledge of seed catalogs, university extension data, and grower experience. ";
                $systemPrompt .= "Consider regional variations, climate factors, and optimal growing conditions. ";
                $systemPrompt .= "\n\nIMPORTANT: For harvest window queries, provide specific data including:\n";
                $systemPrompt .= "- Days from planting/transplanting to harvest\n";
                $systemPrompt .= "- Optimal planting season and timing\n";
                $systemPrompt .= "- Expected harvest window (start and end dates)\n";
                $systemPrompt .= "- Any variety-specific considerations\n";
                $systemPrompt .= "- Temperature requirements and frost tolerance\n";
                $systemPrompt .= "\nFormat responses clearly with specific dates and timeframes.";

                // Add specific research request
                if (!empty($varietyName)) {
                    $systemPrompt .= "\n\nVARIETY RESEARCH REQUEST:\n";
                    $systemPrompt .= "Please research the specific harvest characteristics for '{$varietyName}'";
                    if (!empty($cropName)) {
                        $systemPrompt .= " ({$cropName})";
                    }
                    $systemPrompt .= ". ";
                    $systemPrompt .= "This is not a generic crop - provide variety-specific data including:\n";
                    $systemPrompt .= "- Exact days to maturity from planting for this specific variety\n";
                    $systemPrompt .= "- Best planting time for optimal harvest timing in {$planningYear}\n";
                    $systemPrompt .= "- Expected harvest window in {$planningYear}\n";
                    $systemPrompt .= "- Any unique characteristics of this specific variety\n";

                    if (stripos($varietyName, 'F1') !== false || stripos($varietyName, 'hybrid') !== false || $context['specific_variety'] ?? false) {
                        $systemPrompt .= "- Special considerations for F1 hybrids or specific cultivars\n";
                    }
                }
            }

            if (!empty($context)) {
                $systemPrompt .= "\n\nCurrent Context:\n";
                $systemPrompt .= "- Crop: " . ($context['crop'] ?? $context['crop_type'] ?? 'Not specified') . "\n";
                $systemPrompt .= "- Variety: " . ($context['variety'] ?? 'Not specified') . "\n";
                $systemPrompt .= "- Season: " . ($context['season'] ?? $context['planning_season'] ?? 'Current season') . "\n";
                $systemPrompt .= "- Planning Year: " . ($context['planning_year'] ?? date('Y')) . "\n";

                // Add variety-specific research request if we don't have local data
                if ($isHarvestQuery && !empty($context['variety'])) {
                    $varietyName = $context['variety'];
                    $systemPrompt .= "\nVARIETY RESEARCH REQUEST:\n";
                    $systemPrompt .= "Please research the specific harvest characteristics for '{$varietyName}'. ";
                    $systemPrompt .= "This is not a generic crop - provide variety-specific data including:\n";
                    $systemPrompt .= "- Exact days to maturity from planting\n";
                    $systemPrompt .= "- Best planting time for optimal harvest timing\n";
                    $systemPrompt .= "- Expected harvest window (start and end dates)\n";
                    $systemPrompt .= "- Any unique characteristics of this specific variety\n";
                    $systemPrompt .= "- Temperature requirements and frost tolerance\n";
                    $systemPrompt .= "\nFormat responses clearly with specific dates and timeframes.";
                }
            }

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message]
            ];

            $aiResponse = $this->symbiosisAI->chat($messages);

            if (isset($aiResponse['choices'][0]['message']['content'])) {
                $answer = $aiResponse['choices'][0]['message']['content'];

                return response()->json([
                    'success' => true,
                    'answer' => $answer,
                    'timestamp' => now()->toISOString(),
                    'context_used' => !empty($context)
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'No response generated from AI service'
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('AI chat failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'AI chat service temporarily unavailable',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Wake up AI service (initialize/preload models)
     */
    public function wakeUpAI(Request $request)
    {
        try {
            // Test Ollama connection and warm up the model
            $ollamaResponse = Http::timeout(10)->post('http://localhost:11434/api/generate', [
                'model' => 'tinyllama:latest',
                'prompt' => 'Hello, I am testing the AI service for farmOS succession planning.',
                'stream' => false,
                'options' => [
                    'num_predict' => 10, // Short response for warm-up
                    'temperature' => 0.1
                ]
            ]);

            if ($ollamaResponse->successful()) {
                $data = $ollamaResponse->json();
                $response = $data['response'] ?? '';

                return response()->json([
                    'success' => true,
                    'message' => 'AI service warmed up successfully',
                    'response_preview' => substr($response, 0, 50) . '...',
                    'model' => 'tinyllama:latest'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to warm up AI service',
                    'error' => 'Ollama returned status ' . $ollamaResponse->status()
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('AI wake-up failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'AI wake-up failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate appropriate planting date based on season and crop type using 8-season year
     */
    private function calculatePlantingDate(string $season, string $cropName, int $year): \DateTime
    {
        $plantingDate = new \DateTime();

        // Use 8-season year for more precise farming timing
        switch (strtolower($season)) {
            case 'winter':
            case 'early winter':
            case 'the darkness':
                // Winter crops like Brussels sprouts: sow in Springtime (March 20 - May 1)
                if (stripos($cropName, 'brussels') !== false || stripos($cropName, 'sprouts') !== false) {
                    $plantingDate->setDate($year, 5, 19); // May 19 for December-February harvest (30 days to transplant, 180 days to harvest)
                } elseif (stripos($cropName, 'kale') !== false || stripos($cropName, 'spinach') !== false) {
                    $plantingDate->setDate($year, 3, 20); // March 20 (start of Springtime)
                } else {
                    $plantingDate->setDate($year, 3, 25); // March 25 default for winter crops
                }
                break;

            case 'fall':
            case 'autumn':
            case 'late autumn':
            case 'frost':
                // Fall crops: sow in The Drying (June 22 - August 5)
                if (stripos($cropName, 'brussels') !== false || stripos($cropName, 'sprouts') !== false) {
                    $plantingDate->setDate($year, 7, 1); // July 1 (The Drying season)
                } elseif (stripos($cropName, 'kale') !== false || stripos($cropName, 'broccoli') !== false) {
                    $plantingDate->setDate($year, 6, 22); // June 22 (start of The Drying)
                } else {
                    $plantingDate->setDate($year, 7, 1); // July 1 default for fall crops
                }
                break;

            case 'spring':
            case 'early spring':
            case 'springtime':
                // Spring crops: sow in Budswell (February 1 - March 22)
                if (stripos($cropName, 'lettuce') !== false || stripos($cropName, 'radish') !== false) {
                    $plantingDate->setDate($year, 2, 15); // February 15 (Budswell season)
                } else {
                    $plantingDate->setDate($year, 3, 1); // March 1 default for spring crops
                }
                break;

            case 'summer':
            case 'early summer':
            case 'the drying':
                // Summer crops: sow in Bloom (May 1 - June 22)
                $plantingDate->setDate($year, 5, 15); // May 15 (Bloom season)
                break;

            case 'late summer':
            case 'harvest':
                // Late summer crops: sow in Springtime (March 20 - May 1)
                $plantingDate->setDate($year, 4, 15); // April 15 (Springtime season)
                break;

            case 'year-round':
            case 'all season':
                // Use current 8-season as reference
                $currentMonth = date('n');
                if ($currentMonth >= 12 || $currentMonth <= 2) { // The Darkness
                    $plantingDate->setDate($year, 3, 20); // Springtime
                } elseif ($currentMonth >= 9) { // Frost/Leaf Fall
                    $plantingDate->setDate($year, 6, 22); // The Drying
                } elseif ($currentMonth >= 6) { // The Drying/Harvest
                    $plantingDate->setDate($year, 2, 1); // Budswell
                } else { // Budswell/Springtime
                    $plantingDate->setDate($year, 5, 1); // Bloom
                }
                break;

            default:
                // Default to Springtime season
                $plantingDate->setDate($year, 4, 1);
                break;
        }

        return $plantingDate;
    }
}
