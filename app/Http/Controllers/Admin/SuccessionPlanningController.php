<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FarmOSApi;
use App\Services\AI\SymbiosisAIService;
use App\Services\FarmOSQuickFormService;
use App\Services\VectorSearchService;
use App\Models\PlantVariety;
use App\Models\CompanionPlantingKnowledge;
use App\Models\CropRotationKnowledge;
use App\Models\UKPlantingCalendar;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SuccessionPlanningController extends Controller
{
    protected $farmOSApi;
    protected $symbiosisAI;
    protected $quickFormService;
    protected $vectorSearch;

    public function __construct(
        FarmOSApi $farmOSApi, 
        SymbiosisAIService $symbiosisAI, 
        FarmOSQuickFormService $quickFormService,
        VectorSearchService $vectorSearch
    )
    {
        $this->farmOSApi = $farmOSApi;
        $this->symbiosisAI = $symbiosisAI;
        $this->quickFormService = $quickFormService;
        $this->vectorSearch = $vectorSearch;
    }

    /**
     * Display the succession planning interface
     */
    public function index()
    {
        // Note: AI service wake-up removed - service warms up on first use
        try {
            // Get crop types and varieties from local database (synced from FarmOS)
            // This is MUCH faster than calling the API on every page load
            $cropData = $this->getCropDataFromLocalDB();
            
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
                    ['id' => 'lettuce', 'name' => 'Lettuce', 'label' => 'Lettuce'],
                    ['id' => 'carrot', 'name' => 'Carrot', 'label' => 'Carrot'],
                    ['id' => 'radish', 'name' => 'Radish', 'label' => 'Radish'],
                    ['id' => 'spinach', 'name' => 'Spinach', 'label' => 'Spinach'],
                    ['id' => 'kale', 'name' => 'Kale', 'label' => 'Kale'],
                    ['id' => 'arugula', 'name' => 'Arugula', 'label' => 'Arugula'],
                    ['id' => 'beets', 'name' => 'Beetroot', 'label' => 'Beetroot']
                ],
                'varieties' => [
                    // Carrot varieties
                    ['id' => 'carrot_nantes', 'name' => 'Nantes', 'parent_id' => 'carrot', 'crop_type' => 'carrot'],
                    ['id' => 'carrot_early_nantes_2', 'name' => 'Early Nantes 2', 'parent_id' => 'carrot', 'crop_type' => 'carrot'],
                    ['id' => 'carrot_chantenay', 'name' => 'Chantenay', 'parent_id' => 'carrot', 'crop_type' => 'carrot'],
                    ['id' => 'carrot_imperator', 'name' => 'Imperator', 'parent_id' => 'carrot', 'crop_type' => 'carrot'],
                    ['id' => 'carrot_danvers', 'name' => 'Danvers', 'parent_id' => 'carrot', 'crop_type' => 'carrot'],
                    
                    // Lettuce varieties
                    ['id' => 'lettuce_buttercrunch', 'name' => 'Buttercrunch', 'parent_id' => 'lettuce', 'crop_type' => 'lettuce'],
                    ['id' => 'lettuce_romaine', 'name' => 'Romaine', 'parent_id' => 'lettuce', 'crop_type' => 'lettuce'],
                    ['id' => 'lettuce_red_leaf', 'name' => 'Red Leaf', 'parent_id' => 'lettuce', 'crop_type' => 'lettuce'],
                    ['id' => 'lettuce_green_leaf', 'name' => 'Green Leaf', 'parent_id' => 'lettuce', 'crop_type' => 'lettuce'],
                    
                    // Beetroot varieties
                    ['id' => 'beets_red', 'name' => 'Red Beet', 'parent_id' => 'beets', 'crop_type' => 'beets'],
                    ['id' => 'beets_golden', 'name' => 'Golden Beet', 'parent_id' => 'beets', 'crop_type' => 'beets'],
                    ['id' => 'beets_chioggia', 'name' => 'Chioggia', 'parent_id' => 'beets', 'crop_type' => 'beets'],
                    
                    // Spinach varieties
                    ['id' => 'spinach_tyee', 'name' => 'Tyee', 'parent_id' => 'spinach', 'crop_type' => 'spinach'],
                    ['id' => 'spinach_bloomsdale', 'name' => 'Bloomsdale', 'parent_id' => 'spinach', 'crop_type' => 'spinach'],
                    
                    // Kale varieties
                    ['id' => 'kale_curly', 'name' => 'Curly Kale', 'parent_id' => 'kale', 'crop_type' => 'kale'],
                    ['id' => 'kale_lacinato', 'name' => 'Lacinato', 'parent_id' => 'kale', 'crop_type' => 'kale'],
                    
                    // Radish varieties
                    ['id' => 'radish_cherry_belle', 'name' => 'Cherry Belle', 'parent_id' => 'radish', 'crop_type' => 'radish'],
                    ['id' => 'radish_icicle', 'name' => 'Icicle', 'parent_id' => 'radish', 'crop_type' => 'radish'],
                    
                    // Arugula varieties
                    ['id' => 'arugula_standard', 'name' => 'Standard Arugula', 'parent_id' => 'arugula', 'crop_type' => 'arugula'],
                    ['id' => 'arugula_wild', 'name' => 'Wild Arugula', 'parent_id' => 'arugula', 'crop_type' => 'arugula']
                ]
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
     * Calculate succession plan for frontend display with Quick Form URLs
     */
    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'crop_id' => 'required|string',
            'variety_id' => 'nullable|string',
            'harvest_start' => 'required|date',
            'harvest_end' => 'required|date',
            'bed_ids' => 'nullable|array',
            'succession_count' => 'nullable|integer|min:1|max:20',
            'use_ai' => 'boolean'
        ]);

        try {
            // Get crop and variety information
            $cropData = $this->farmOSApi->getAvailableCropTypes();
            $cropInfo = collect($cropData['types'])->firstWhere('id', $validated['crop_id']);
            $varietyInfo = null;
            if (isset($validated['variety_id']) && $validated['variety_id']) {
                $varietyInfo = collect($cropData['varieties'])->firstWhere('id', $validated['variety_id']);
            }

            // Generate basic succession plan
            $successions = $this->generateBasicSuccessions($validated);

            // Add Quick Form URLs to each succession
            foreach ($successions as $index => &$succession) {
                $succession['succession_number'] = $index + 1;
                $succession['crop_name'] = $cropInfo['name'] ?? $validated['crop_id'];
                $succession['variety_name'] = $varietyInfo['name'] ?? ($varietyInfo['title'] ?? 'Generic');

                // Generate Quick Form URLs
                $succession['quick_form_urls'] = $this->quickFormService->generateAllFormUrls($succession);
            }

            $plan = [
                'crop' => $cropInfo,
                'variety' => $varietyInfo,
                'harvest_start' => $validated['harvest_start'],
                'harvest_end' => $validated['harvest_end'],
                'plantings' => $successions,
                'total_successions' => count($successions)
            ];

            return response()->json([
                'success' => true,
                'succession_plan' => $plan,
                'message' => 'Succession plan calculated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to calculate succession plan: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate succession plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate basic successions for display
     */
    protected function generateBasicSuccessions(array $data): array
    {
        $successions = [];
        $harvestStart = Carbon::parse($data['harvest_start']);
        $harvestEnd = Carbon::parse($data['harvest_end']);

        // Calculate harvest duration
        $harvestDuration = $harvestStart->diffInDays($harvestEnd);

        // Use provided succession count or calculate based on duration and typical interval
        $successionCount = $data['succession_count'] ?? max(1, ceil($harvestDuration / 14)); // Default to 2-week intervals
        $successionCount = min($successionCount, 20); // Cap at 20 successions max

        // Calculate base growth period (45 days from seeding to harvest)
        $baseGrowthDays = 45;
        $baseTransplantToHarvestDays = 21;

        // Determine sowing strategy based on harvest window timing
        $harvestStartMonth = $harvestStart->month;
        $isSpringHarvest = $harvestStartMonth >= 3 && $harvestStartMonth <= 6; // Mar-Jun
        $isAutumnHarvest = $harvestStartMonth >= 8 && $harvestStartMonth <= 11; // Aug-Nov

        // Generate successions with seasonal adjustments
        for ($i = 0; $i < $successionCount; $i++) {
            // Calculate sowing spacing based on season
            if ($isSpringHarvest) {
                // Spring: wider spacing (2 weeks), faster growth
                $sowingSpacingDays = 14;
                $growthMultiplier = 1.0 - ($i * 0.1); // Each succession slightly faster (10% reduction)
            } elseif ($isAutumnHarvest) {
                // Autumn: closer spacing (1 week), slower growth
                $sowingSpacingDays = 7;
                $growthMultiplier = 1.0 + ($i * 0.15); // Each succession takes longer (15% increase)
            } else {
                // Summer/winter: standard spacing
                $sowingSpacingDays = 10;
                $growthMultiplier = 1.0 + ($i * 0.05); // Slight increase for later sowings
            }

            // Calculate sowing offset from first harvest
            $sowingOffsetDays = $i * $sowingSpacingDays;

            // Apply growth period adjustment
            $adjustedGrowthDays = round($baseGrowthDays * $growthMultiplier);
            $adjustedTransplantToHarvestDays = round($baseTransplantToHarvestDays * $growthMultiplier);

            // Calculate harvest date for this succession
            $successionHarvestStart = $harvestStart->copy()->addDays($i * 14); // Keep harvests 2 weeks apart
            $successionHarvestEnd = $harvestEnd->copy()->addDays($i * 14);

            // Calculate seeding date based on adjusted growth period
            $seedingDate = $successionHarvestStart->copy()->subDays($adjustedGrowthDays);
            $transplantDate = $successionHarvestStart->copy()->subDays($adjustedTransplantToHarvestDays);

            $successions[] = [
                'succession_id' => $i + 1,
                'seeding_date' => $seedingDate->format('Y-m-d'),
                'transplant_date' => $transplantDate->format('Y-m-d'),
                'harvest_date' => $successionHarvestStart->format('Y-m-d'),
                'harvest_end_date' => $successionHarvestEnd->format('Y-m-d'),
                'bed_name' => $data['bed_ids'][$i] ?? 'Bed ' . ($i + 1),
                'quantity' => 100, // Default quantity
                'growth_days' => $adjustedGrowthDays,
                'season_adjusted' => true,
                'notes' => "Succession " . ($i + 1) . " - Seasonally adjusted (" . $adjustedGrowthDays . " day growth)"
            ];
        }

        return $successions;
    }

    /**
     * Generate succession plan based on user input
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
                'succession_context' => "Planning {$successionCount} successions with {$intervalDays} day intervals"
            ];

            // Use new AI service instead of old HolisticAICropService
            $harvestWindow = $this->getAIHarvestWindow(
                $cropType,
                $variety,
                $contextualData
            );

            if ($harvestWindow['success']) {
                // Use AI recommendations for timing
                $transplantToHarvestDays = $harvestWindow['peak_harvest_days'] ?? $validated['transplant_to_harvest_days'];
                $harvestDurationDays = $harvestWindow['optimal_harvest_days'] ?? $validated['harvest_duration_days'];
                $recommendedInterval = $harvestWindow['days_between_plantings'] ?? $intervalDays;
                
                // Adjust interval if AI suggests different timing
                if (abs($recommendedInterval - $intervalDays) <= 7) {
                    $intervalDays = $recommendedInterval;
                }
                
                $aiUsed = true;
                $aiSource = $harvestWindow['source'];
                $aiConfidence = $harvestWindow['ai_confidence'] ?? 'medium';
            } else {
                // Use crop-specific fallback instead of just user input
                $fallbackWindow = $this->getFallbackHarvestWindow($cropType, $variety);
                
                $transplantToHarvestDays = $fallbackWindow['peak_harvest_days'] ?? $validated['transplant_to_harvest_days'];
                $harvestDurationDays = $fallbackWindow['maximum_harvest_days'] ?? $validated['harvest_duration_days'];
                $recommendedInterval = $fallbackWindow['days_between_plantings'] ?? $intervalDays;
                
                // Adjust interval if fallback suggests different timing
                if (abs($recommendedInterval - $intervalDays) <= 7) {
                    $intervalDays = $recommendedInterval;
                }
                
                $aiUsed = false;
                $aiSource = $fallbackWindow['source'];
                $aiConfidence = $fallbackWindow['ai_confidence'];
                
                Log::info('Using crop-specific fallback for harvest window', [
                    'crop' => $cropType,
                    'variety' => $variety,
                    'fallback_harvest_days' => $harvestDurationDays,
                    'fallback_successions' => $fallbackWindow['recommended_successions'],
                    'fallback_interval' => $recommendedInterval
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('AI harvest analysis failed, using manual input: ' . $e->getMessage());
            $transplantToHarvestDays = $validated['transplant_to_harvest_days'];
            $harvestDurationDays = $validated['harvest_duration_days'];
            $aiUsed = false;
            $aiSource = 'fallback_manual';
            $aiConfidence = 'user_specified';
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
                'ai_confidence' => $aiConfidence
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

            // Use SymbiosisAI service
            $messages = [
                [
                    'role' => 'system',
                    'content' => 'You are an expert agricultural AI assistant specializing in succession planting and crop planning. Analyze the provided plan data and give specific, actionable recommendations.'
                ],
                [
                    'role' => 'user',
                    'content' => $aiQuestion . "\n\nPlan Context: " . json_encode($aiContext)
                ]
            ];

            $aiResponse = $this->symbiosisAI->chat($messages);

            if (isset($aiResponse['choices'][0]['message']['content'])) {
                $plan['ai_recommendations'] = $aiResponse['choices'][0]['message']['content'];
                $plan['ai_analysis_date'] = now()->format('Y-m-d H:i:s');
            } else {
                $plan['ai_recommendations'] = 'AI analysis unavailable';
            }

        } catch (\Exception $e) {
            Log::warning('SymbiosisAI optimization failed: ' . $e->getMessage());
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
                $properties = $feature['properties'] ?? [];
                $name = $properties['name'] ?? 'Unknown';

                // Filter for bed-type assets with X/Y numbering pattern (e.g., "1/1", "2/3")
                if (preg_match('/^\d+\/\d+$/', $name)) {
                    $beds[] = [
                        'id' => $properties['id'] ?? uniqid(),
                        'name' => $name,
                        'type' => $properties['land_type'] ?? 'bed',
                        'status' => $properties['status'] ?? 'active'
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
     * Get crop data from local database (synced from FarmOS)
     * This is much faster than calling the API on every page load
     */
    private function getCropDataFromLocalDB()
    {
        // Get all distinct plant types (crop types)
        $plantTypes = PlantVariety::select('plant_type', 'plant_type_id')
            ->where('is_active', true)
            ->whereNotNull('plant_type')
            ->distinct()
            ->orderBy('plant_type')
            ->get();
        
        $types = [];
        foreach ($plantTypes as $type) {
            $types[] = [
                'id' => $type->plant_type_id ?? strtolower(str_replace(' ', '_', $type->plant_type)),
                'name' => $type->plant_type,
                'label' => $type->plant_type
            ];
        }
        
        // Get all varieties with their parent plant type
        $plantVarieties = PlantVariety::select('id', 'name', 'farmos_id', 'plant_type', 'plant_type_id')
            ->where('is_active', true)
            ->orderBy('plant_type')
            ->orderBy('name')
            ->get();
        
        $varieties = [];
        foreach ($plantVarieties as $variety) {
            $parentId = $variety->plant_type_id ?? strtolower(str_replace(' ', '_', $variety->plant_type));
            $varieties[] = [
                'id' => $variety->farmos_id,
                'name' => $variety->name,
                'parent_id' => $parentId,
                'crop_type' => $variety->plant_type
            ];
        }
        
        return [
            'types' => $types,
            'varieties' => $varieties
        ];
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
                'harvest_days' => 75,     // First harvest (baby carrots)
                'yield_period' => 120,    // Maximum harvest window (baby to mature)
                'maximum_harvest_days' => 180, // Absolute maximum with storage
                'harvest_notes' => 'Can harvest from 50 days (baby) to 180+ days (mature with storage)'
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
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to get timing recommendations'
            ], 500);
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
                'window_adjustment' => 7        // Longer harvest window
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
            'arugula' => ['Basil (flavor synergy)', 'Oregano (protection)', 'Parsley (companion support)'],
            // Brassica family companions
            'cauliflower' => ['Nasturtium (aphid trap crop)', 'Lettuce (space between rows)', 'Spinach (quick intercrop)', 'Dill (beneficial insects)'],
            'cabbage' => ['Nasturtium (pest control)', 'Quick salads between rows', 'Dill (attracts predators)', 'Thyme (cabbage white deterrent)'],
            'broccoli' => ['Lettuce (fast intercrop)', 'Nasturtium (aphid protection)', 'Spinach (cool season match)', 'Chamomile (health)'],
            'brussels sprouts' => ['Quick salads early season', 'Nasturtium (pest trap)', 'Dill (beneficial insects)', 'Thyme (aromatic protection)'],
            'calabrese' => ['Lettuce intercrop', 'Nasturtium (pest control)', 'Radish (fast harvest)', 'Basil (companion)']
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
    public function getOptimalHarvestWindow(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'crop_type' => 'required|string',
            'variety' => 'nullable|string',
            'location' => 'nullable|string'
        ]);

        try {
            // Gather contextual data for better AI analysis
            $contextualData = [
                'weather_forecast' => 'Current seasonal conditions for ' . ($validated['location'] ?? 'farm location'),
                'current_season_performance' => 'Planning analysis for succession timing'
            ];

            // Use new HuggingFace AI service instead of old HolisticAICropService
            $harvestWindow = $this->getAIHarvestWindow(
                $validated['crop_type'],
                $validated['variety'],
                $contextualData
            );

            if ($harvestWindow['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $harvestWindow,
                    'ai_confidence' => $harvestWindow['ai_confidence'],
                    'data_quality' => 'symbiosis_ai',
                    'recommendations_basis' => 'llama_3_1_analysis',
                    'contextual_factors' => [$harvestWindow['raw_answer'] ?? 'AI analysis complete'],
                    'timestamp' => now()->toISOString()
                ]);
            } else {
                // Use crop-specific fallback instead of throwing exception
                $fallbackWindow = $this->getFallbackHarvestWindow($validated['crop_type'], $validated['variety'], [
                    'current_date' => now()->format('Y-m-d'),
                    'season' => $season
                ]);
                
                Log::info('Using crop-specific fallback for harvest window API', [
                    'crop' => $validated['crop_type'],
                    'variety' => $validated['variety'],
                    'fallback_harvest_days' => $fallbackWindow['maximum_harvest_days']
                ]);
                
                return response()->json([
                    'success' => true,
                    'data' => $fallbackWindow,
                    'ai_confidence' => $fallbackWindow['ai_confidence'],
                    'data_quality' => 'fallback_system',
                    'recommendations_basis' => 'crop_specific_fallback',
                    'contextual_factors' => [$fallbackWindow['notes'] ?? 'Fallback analysis complete'],
                    'timestamp' => now()->toISOString()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Harvest window optimization failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to get harvest window optimization',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle chat messages to Mistral AI
     */
    public function chat(Request $request): JsonResponse
    {
        try {
            Log::info("🔥 CHAT METHOD CALLED - Start of processing", [
                'has_question' => $request->has('question'),
                'question_value' => $request->input('question'),
                'all_input' => $request->all()
            ]);
            
            $validated = $request->validate([
                'question' => 'required|string|max:5000', // Increased limit for detailed harvest window prompts
                'crop_type' => 'nullable|string',
                'season' => 'nullable|string',
                'context' => 'nullable' // Allow any type for context
            ]);

            Log::info("🔥 CHAT METHOD - Validation passed", ['data' => $validated]);

            // Check if this is a harvest window calculation request
            $lowerQuestion = strtolower($validated['question']);
            $hasHarvestWindow = str_contains($lowerQuestion, 'harvest window');
            $hasMaxStart = str_contains($lowerQuestion, 'maximum_start');
            $hasJsonObject = str_contains($lowerQuestion, 'json object');
            
            Log::info("🔍 Chat detection check", [
                'question_preview' => substr($validated['question'], 0, 100) . '...',
                'has_harvest_window' => $hasHarvestWindow,
                'has_maximum_start' => $hasMaxStart, 
                'has_json_object' => $hasJsonObject
            ]);
            
            if ($hasHarvestWindow || ($hasMaxStart && $hasJsonObject)) {
                // Extract crop_type and variety from context if not provided
                $context = $validated['context'];
                if (is_array($context) || is_object($context)) {
                    if (!$request->has('crop_type') && (isset($context['crop']) || isset($context['crop_type']))) {
                        $request->merge(['crop_type' => $context['crop'] ?? $context['crop_type']]);
                    }
                    if (!$request->has('variety') && (isset($context['variety']) || isset($context['variety_name']))) {
                        $request->merge(['variety' => $context['variety'] ?? $context['variety_name']]);
                    }
                    if (!$request->has('season') && isset($context['season'])) {
                        $request->merge(['season' => $context['season']]);
                    }
                }
                return $this->calculateHarvestWindow($request);
            }

            // Check if this is a harvest window request and enhance the prompt with crop data
            if (str_contains(strtolower($validated['question']), 'harvest window')) {
                // Get crop timing presets
                $cropPresets = $this->getCropTimingPresets();
                $cropType = $validated['crop_type'] ?? 'lettuce';
                $baseTiming = $cropPresets[$cropType] ?? $cropPresets['default'];
                
                // Enhance the question with specific crop data
                $question = $validated['question'] . "\n\nCrop-specific data for {$cropType}:\n" . 
                    "- Transplant to harvest: {$baseTiming['transplant_to_harvest']} days\n" . 
                    "- Direct seed to harvest: {$baseTiming['direct_seed_to_harvest']} days\n" . 
                    "- Yield period: {$baseTiming['yield_period']} days\n" . 
                    "- Succession interval: {$baseTiming['succession_interval']} days\n" . 
                    "- Notes: {$baseTiming['notes']}\n\n" . 
                    "Please provide a specific harvest window calculation based on this crop data. Return the response as a JSON object with fields: maximum_start, maximum_end, days_to_harvest, yield_peak, extended_window (with max_extension_days and risk_level), notes.";
            }

            // Build contextual question for AI
            $question = $validated['question'];
            
            // Get context from request
            $context = $validated['context'] ?? null;
            $hasPlan = false;
            $planDetails = '';
            
            // Check if we have succession plan context
            if ($context && is_array($context) && isset($context['has_plan']) && $context['has_plan']) {
                $hasPlan = true;
                $plan = $context['plan'];
                
                // Build detailed plan context for AI
                $planDetails = "\n\nCurrent Succession Plan Context:\n";
                $planDetails .= "- Crop: {$plan['crop_name']} ({$plan['variety_name']})\n";
                $planDetails .= "- Total Successions: {$plan['total_successions']}\n";
                $planDetails .= "- Harvest Window: {$plan['harvest_window_start']} to {$plan['harvest_window_end']}\n";
                $planDetails .= "- Bed Dimensions: {$plan['bed_length']}m × {$plan['bed_width']}cm\n";
                $planDetails .= "- Spacing: {$plan['in_row_spacing']}cm in-row, {$plan['between_row_spacing']}cm between rows\n";
                $planDetails .= "- Method: {$plan['planting_method']}\n\n";
                $planDetails .= "Planting Schedule:\n";
                
                foreach ($plan['plantings'] as $planting) {
                    $planDetails .= "  Succession {$planting['succession_number']}:\n";
                    $planDetails .= "    - Seeding: {$planting['seeding_date']}\n";
                    if (!empty($planting['transplant_date'])) {
                        $planDetails .= "    - Transplant: {$planting['transplant_date']}\n";
                    }
                    $planDetails .= "    - Harvest: {$planting['harvest_date']}\n";
                    if (!empty($planting['quantity'])) {
                        $planDetails .= "    - Quantity: {$planting['quantity']} plants\n";
                    }
                    if (!empty($planting['bed_name']) && $planting['bed_name'] !== 'Unassigned') {
                        $planDetails .= "    - Location: {$planting['bed_name']}\n";
                    }
                }
                
                $question .= $planDetails;
            }
            
            // Add context if provided
            if (!empty($validated['crop_type'])) {
                $question .= " (Context: working with {$validated['crop_type']} crop)";
            }
            
            if (!empty($validated['season'])) {
                $question .= " (Season: {$validated['season']})";
            }

            // Prepare messages for SymbiosisAI service
            $systemPrompt = 'You are Symbiosis AI, a practical farm planning assistant. Be direct and specific - no generic advice.';
            
            if ($hasPlan) {
                $bedWidth = $plan['bed_width'] ?? 75;
                $currentBetweenRow = $plan['between_row_spacing'] ?? 45;
                $currentInRow = $plan['in_row_spacing'] ?? 30;
                $bedLength = $plan['bed_length'] ?? 11;
                
                // Calculate current rows
                // Formula: How many rows can fit with given spacing?
                // For 75cm bed, 30cm spacing: positions at 0, 30, 60 = 3 rows
                // For 75cm bed, 45cm spacing: positions at 0, 45 = 2 rows (90 doesn't fit)
                $currentRows = floor($bedWidth / $currentBetweenRow) + 1;
                
                $plantsPerRow = floor(($bedLength * 100) / $currentInRow);
                $currentTotal = $currentRows * $plantsPerRow;
                
                $systemPrompt .= " Analyze the SPECIFIC succession plan provided. Current setup: {$bedWidth}cm bed with {$currentBetweenRow}cm between-row spacing = {$currentRows} rows × {$plantsPerRow} plants/row = {$currentTotal} total plants per bed.";
                $systemPrompt .= ' When suggesting spacing changes: (1) State CURRENT setup clearly using the numbers I provided, (2) Calculate NEW plant count with suggested spacing, (3) Show % increase/benefit, (4) Mention which "Row Density Preset" button to use if applicable. Comment on: spacing appropriateness, succession timing/gaps, harvest window coverage. Suggest useful COMPANION PLANTS or INTERCROPS. Keep under 150 words.';
                
                // Get crop info for knowledge lookups
                $cropType = $validated['crop_type'] ?? null;
                $cropFamily = null;
                $season = $validated['season'] ?? null;
                
                if (isset($plan['crop_name'])) {
                    $variety = PlantVariety::where('name', 'LIKE', "%{$plan['crop_name']}%")->first();
                    if ($variety) {
                        $cropFamily = $variety->crop_family;
                    }
                }
                
                // Use vector search to find relevant knowledge
                if ($cropType) {
                    // Build semantic query based on context
                    $searchQuery = "companion plants and intercropping for {$cropType}";
                    if ($cropFamily) {
                        $searchQuery .= " {$cropFamily} family";
                    }
                    
                    // Search for relevant knowledge using vector similarity
                    $relevantKnowledge = $this->vectorSearch->semanticSearch($searchQuery, 5);
                    
                    if (!empty($relevantKnowledge)) {
                        $knowledgeContext = $this->vectorSearch->formatResultsForAI($relevantKnowledge);
                        $systemPrompt .= "\n\n" . $knowledgeContext;
                    }
                    
                    // Also search for rotation advice if available
                    $rotationQuery = "what to plant after {$cropType} crop rotation succession";
                    $rotationKnowledge = $this->vectorSearch->searchKnowledgeType('rotation', $rotationQuery, 3);
                    
                    if (!empty($rotationKnowledge)) {
                        $rotationContext = "\n\n=== CROP ROTATION ADVICE ===\n";
                        foreach ($rotationKnowledge as $r) {
                            $rotationContext .= "After {$r['previous_crop']}: {$r['following_crop']} ({$r['relationship']})\n";
                            if (!empty($r['benefits'])) {
                                $rotationContext .= "  Benefits: {$r['benefits']}\n";
                            }
                        }
                        $systemPrompt .= $rotationContext;
                    }
                }
                
                // Add spacing context for brassicas
                if (isset($plan['between_row_spacing']) && $plan['between_row_spacing'] <= 30) {
                    $systemPrompt .= ' IMPORTANT: Tight spacing (30cm between rows) works for SMALL brassicas (summer cauliflower, baby cabbage, spring greens) but RECOMMEND STAGGERED PLANTING: offset plants in adjacent rows so they sit in gaps, increasing effective spacing. This improves airflow and allows larger heads. Works well for most calabrese/broccoli. Not suitable for large winter cauliflowers or cabbages - those need 45cm+.';
                }
            } else {
                $systemPrompt .= ' Help plan succession planting with practical advice about crop timing, spacing, and harvest windows.';
            }
            
            $messages = [
                [
                    'role' => 'system',
                    'content' => $systemPrompt
                ],
                [
                    'role' => 'user',
                    'content' => $question
                ]
            ];

            // Log the full prompt being sent to AI
            Log::info('🤖 AI Prompt', [
                'system' => $systemPrompt,
                'user_question' => $question
            ]);

            // Call SymbiosisAI service
            $aiResponse = $this->symbiosisAI->chat($messages);

            if (isset($aiResponse['choices'][0]['message']['content'])) {
                $answer = $aiResponse['choices'][0]['message']['content'];
                
                Log::info('SymbiosisAI chat response received', [
                    'user_message' => $validated['question'],
                    'model' => 'phi3:mini'
                ]);

                return response()->json([
                    'success' => true,
                    'answer' => $answer,
                    'wisdom' => 'Agricultural expertise from StableLM2 1.6B AI',
                    'moon_phase' => 'unknown',
                    'context' => $validated['context'] ?? null,
                    'source' => 'StableLM2 1.6B via Ollama',
                    'timestamp' => now()->toISOString()
                ]);
            } else {
                throw new \Exception('Invalid AI response format');
            }

        } catch (\Exception $e) {
            Log::error('SymbiosisAI chat error: ' . $e->getMessage());
            
            // Return fallback wisdom instead of error
            $fallbackWisdom = $this->getFallbackChatWisdom($validated['question'] ?? 'farming question');
            
            return response()->json([
                'success' => true,
                'answer' => $fallbackWisdom,
                'wisdom' => 'Fallback wisdom - AI service temporarily unavailable',
                'moon_phase' => 'unknown',
                'source' => 'Fallback System',
                'timestamp' => now()->toISOString()
            ]);
        }
    }

    /**
     * Get fallback wisdom when AI is unavailable
     */
    private function getFallbackChatWisdom(string $question): string
    {
        $fallbackResponses = [
            'farming' => 'Focus on soil health, proper spacing, and regular observation. Consider companion planting and seasonal timing for optimal results.',
            'harvest' => 'Monitor crops daily during harvest season. Harvest in cool morning hours when possible, and handle produce gently to maintain quality.',
            'planting' => 'Ensure soil temperature and conditions are appropriate for your crop. Follow proper spacing guidelines and consider succession planting for continuous harvest.',
            'weather' => 'Keep track of local weather patterns and plan accordingly. Protect sensitive crops from frost and extreme conditions.',
            'pest' => 'Regular inspection and integrated pest management work best. Encourage beneficial insects and maintain healthy soil to naturally resist pests.',
            'default' => 'Successful farming combines careful planning, regular observation, and adapting to local conditions. Keep detailed records to improve over time.'
        ];

        $question = strtolower($question);
        
        foreach ($fallbackResponses as $keyword => $response) {
            if (strpos($question, $keyword) !== false) {
                return $response;
            }
        }
        
        return $fallbackResponses['default'];
    }

    /**
     * Calculate optimal harvest window using AI and admin database
     */
    public function calculateHarvestWindow(Request $request): JsonResponse
    {
        Log::info("🔍 calculateHarvestWindow called with data: " . json_encode($request->all()));
        
        try {
            $validated = $request->validate([
                'crop_type' => 'nullable|string',
                'variety' => 'nullable|string',
                'variety_meta' => 'nullable|array',
                'season' => 'nullable|string',
                'planning_year' => 'nullable|integer',
                'current_date' => 'nullable|date',
                'context' => 'nullable|array'
            ]);

            $cropType = $validated['crop_type'] ?? $request->input('crop_type') ?? 'lettuce';
            $variety = $validated['variety'] ?? $request->input('variety') ?? '';
            $season = $validated['season'] ?? $request->input('season') ?? 'current';
            $planningYear = $validated['planning_year'] ?? $request->input('planning_year') ?? date('Y');
            
            Log::info("🗓️ Planning year determination", [
                'validated_planning_year' => $validated['planning_year'] ?? null,
                'request_planning_year' => $request->input('planning_year'),
                'fallback_current_year' => date('Y'),
                'final_planning_year' => $planningYear
            ]);

            // First check admin database for variety-specific harvest window data
            $adminVarietyData = null;
            if ($variety) {
                Log::info("🔍 Looking for variety in admin database", [
                    'variety_name' => $variety,
                    'crop_type' => $cropType,
                    'total_varieties_in_db' => PlantVariety::count()
                ]);
                
                // Try exact match first
                $plantVariety = PlantVariety::where('name', $variety)->first();
                Log::info("🔍 Exact match result", [
                    'variety_searched' => $variety,
                    'found' => $plantVariety ? 'YES' : 'NO',
                    'found_name' => $plantVariety ? $plantVariety->name : null
                ]);
                
                // If no exact match, try partial match but only on variety name
                if (!$plantVariety) {
                    $plantVariety = PlantVariety::where('name', 'LIKE', "%{$variety}%")->first();
                    Log::info("🔍 Partial match result", [
                        'variety_searched' => $variety,
                        'pattern' => "%{$variety}%",
                        'found' => $plantVariety ? 'YES' : 'NO',
                        'found_name' => $plantVariety ? $plantVariety->name : null
                    ]);
                }
                
                // Try a few more search strategies
                if (!$plantVariety) {
                    // Try case-insensitive search
                    $plantVariety = PlantVariety::whereRaw('LOWER(name) = LOWER(?)', [$variety])->first();
                    Log::info("🔍 Case-insensitive match result", [
                        'variety_searched' => $variety,
                        'found' => $plantVariety ? 'YES' : 'NO',
                        'found_name' => $plantVariety ? $plantVariety->name : null
                    ]);
                }
                
                if (!$plantVariety) {
                    // Show some sample varieties for debugging
                    $sampleVarieties = PlantVariety::select('name', 'plant_type')
                        ->where('plant_type', 'LIKE', "%carrot%")
                        ->orWhere('name', 'LIKE', "%carrot%")
                        ->limit(10)
                        ->get()
                        ->pluck('name');
                    
                    Log::info("🔍 Sample carrot varieties in database", [
                        'sample_varieties' => $sampleVarieties->toArray()
                    ]);
                }
                
                if ($plantVariety) {
                    $adminVarietyData = [
                        'harvest_start' => $plantVariety->harvest_start ? $plantVariety->harvest_start->format('Y-m-d') : null,
                        'harvest_end' => $plantVariety->harvest_end ? $plantVariety->harvest_end->format('Y-m-d') : null,
                        'harvest_window_days' => $plantVariety->harvest_window_days,
                        'yield_peak' => $plantVariety->yield_peak ? $plantVariety->yield_peak->format('Y-m-d') : $plantVariety->harvest_start,
                        'maturity_days' => $plantVariety->maturity_days,
                        'harvest_days' => $plantVariety->harvest_days,
                        'harvest_notes' => $plantVariety->harvest_notes,
                        'harvest_method' => $plantVariety->harvest_method,
                        'expected_yield_per_plant' => $plantVariety->expected_yield_per_plant,
                        'source' => 'Admin Database'
                    ];

                    Log::info("✅ Found variety data in admin database for '{$variety}': " . $plantVariety->name . " - Window: " . $plantVariety->harvest_window_days . " days", [
                        'harvest_start_value' => $adminVarietyData['harvest_start'],
                        'harvest_end_value' => $adminVarietyData['harvest_end'],
                        'has_harvest_start' => !empty($adminVarietyData['harvest_start']),
                        'has_harvest_end' => !empty($adminVarietyData['harvest_end'])
                    ]);
                } else {
                    Log::info("❌ No variety data found in admin database for '{$variety}'");
                }
            }

            // If we have admin database data, use it directly
            if ($adminVarietyData && $adminVarietyData['harvest_start'] && $adminVarietyData['harvest_end']) {
                Log::info("🎯 Using admin database calculation path", [
                    'variety' => $variety,
                    'planning_year' => $planningYear
                ]);
                
                // Convert day numbers to actual dates for the planning year
                Log::info("🔍 Converting admin database dates", [
                    'original_harvest_start' => $adminVarietyData['harvest_start'],
                    'original_harvest_end' => $adminVarietyData['harvest_end'],
                    'planning_year' => $planningYear
                ]);
                
                $harvestStart = $this->dayNumberToDate($adminVarietyData['harvest_start'], $planningYear);
                $harvestEnd = $this->dayNumberToDate($adminVarietyData['harvest_end'], $planningYear);
                $yieldPeak = $adminVarietyData['yield_peak'] ? 
                    $this->dayNumberToDate($adminVarietyData['yield_peak'], $planningYear) : $harvestStart;

                Log::info("🔍 Converted admin database dates", [
                    'converted_harvest_start' => $harvestStart,
                    'converted_harvest_end' => $harvestEnd,
                    'converted_yield_peak' => $yieldPeak
                ]);

                return response()->json([
                    'success' => true,
                    'maximum_start' => $harvestStart,
                    'maximum_end' => $harvestEnd,
                    'yield_peak' => $yieldPeak,
                    'optimal_window_days' => $adminVarietyData['harvest_window_days'] ?? 90,
                    'peak_harvest_days' => $adminVarietyData['maturity_days'] ?? $adminVarietyData['harvest_days'] ?? 75,
                    'confidence' => 'high',
                    'source' => 'Admin Database - Comprehensive Variety Data',
                    'recommendations' => [
                        "Based on comprehensive variety database for {$variety}",
                        $adminVarietyData['harvest_notes'] ?? "Maximum harvest window for optimal yield"
                    ],
                    'timestamp' => now()->toISOString()
                ]);
            } else {
                Log::info("❌ Admin database calculation skipped", [
                    'has_admin_data' => !empty($adminVarietyData),
                    'has_harvest_start' => !empty($adminVarietyData['harvest_start'] ?? null),
                    'has_harvest_end' => !empty($adminVarietyData['harvest_end'] ?? null),
                    'admin_data_keys' => array_keys($adminVarietyData ?? [])
                ]);
            }

            // Fallback to AI calculation if no admin database data
            $currentDate = $validated['current_date'] ?? $request->input('current_date') ?? now()->format('Y-m-d');
            
            // Get crop timing presets
            $cropPresets = $this->getCropTimingPresets();
            $baseTiming = $cropPresets[$cropType] ?? $cropPresets['default'];

            // Build contextual data for AI
            $contextualData = [
                'crop_type' => $cropType,
                'variety' => $variety,
                'season' => $season,
                'planning_year' => $planningYear,
                'current_date' => $currentDate,
                'base_timing' => $baseTiming,
                'location_factors' => $validated['context'] ?? []
            ];

            // Try AI-powered harvest window calculation
            $aiHarvestWindow = $this->getAIHarvestWindow($cropType, $variety, $contextualData);

            if ($aiHarvestWindow['success']) {
                return response()->json([
                    'success' => true,
                    'maximum_start' => $aiHarvestWindow['maximum_start'] ?? null,
                    'optimal_window_days' => $aiHarvestWindow['optimal_harvest_days'] ?? $baseTiming['yield_period'],
                    'peak_harvest_days' => $aiHarvestWindow['peak_harvest_days'] ?? $baseTiming['transplant_to_harvest'],
                    'confidence' => $aiHarvestWindow['ai_confidence'] ?? 'medium',
                    'source' => $aiHarvestWindow['source'] ?? 'AI Analysis',
                    'recommendations' => $aiHarvestWindow['contextual_factors'] ?? [],
                    'timestamp' => now()->toISOString()
                ]);
            }

            // Fallback to preset-based calculation
            $fallbackWindow = $this->getFallbackHarvestWindow($cropType, $variety, $contextualData);

            return response()->json([
                'success' => true,
                'maximum_start' => $currentDate,
                'optimal_window_days' => $fallbackWindow['maximum_harvest_days'] ?? $baseTiming['yield_period'],
                'peak_harvest_days' => $fallbackWindow['peak_harvest_days'] ?? $baseTiming['transplant_to_harvest'],
                'confidence' => 'low',
                'source' => 'Fallback Presets',
                'recommendations' => ['Based on standard crop timing presets'],
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Harvest window calculation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to calculate harvest window: ' . $e->getMessage(),
                'maximum_start' => $request->input('current_date', now()->format('Y-m-d')),
                'optimal_window_days' => 14, // Default fallback
                'peak_harvest_days' => 60,  // Default fallback
                'confidence' => 'none',
                'source' => 'Error Fallback',
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    /**
     * Convert day number (1-365) or date string to actual date for given year
     */
    private function dayNumberToDate($dayOrDate, int $year): string
    {
        try {
            Log::info("🔧 dayNumberToDate debug", [
                'input' => $dayOrDate,
                'input_type' => gettype($dayOrDate),
                'target_year' => $year
            ]);
            
            // Handle Carbon objects
            if ($dayOrDate instanceof \Carbon\Carbon) {
                $planningDate = Carbon::create($year, $dayOrDate->month, $dayOrDate->day);
                Log::info("🔧 Converted Carbon object", [
                    'original_carbon' => $dayOrDate->format('Y-m-d'),
                    'planning_date' => $planningDate->format('Y-m-d')
                ]);
                return $planningDate->format('Y-m-d');
            }
            
            // If it's already a date string, extract month and day and use the planning year
            if (is_string($dayOrDate) && (strpos($dayOrDate, '-') !== false || strpos($dayOrDate, '/') !== false)) {
                $date = Carbon::parse($dayOrDate);
                Log::info("🔧 Parsed original date", [
                    'original' => $dayOrDate,
                    'parsed_month' => $date->month,
                    'parsed_day' => $date->day,
                    'parsed_year' => $date->year
                ]);
                
                $planningDate = Carbon::create($year, $date->month, $date->day);
                Log::info("🔧 Created planning date", [
                    'planning_date' => $planningDate->format('Y-m-d'),
                    'used_year' => $year,
                    'used_month' => $date->month,
                    'used_day' => $date->day
                ]);
                
                return $planningDate->format('Y-m-d');
            }
            
            // If it's a day number (1-365)
            if (is_numeric($dayOrDate)) {
                $dayNumber = (int)$dayOrDate;
                $date = Carbon::create($year, 1, 1)->addDays($dayNumber - 1);
                Log::info("🔧 Converted day number", [
                    'day_number' => $dayNumber,
                    'result_date' => $date->format('Y-m-d')
                ]);
                return $date->format('Y-m-d');
            }
            
            // Fallback
            Log::warning("🔧 Using fallback date", ['input' => $dayOrDate]);
            return Carbon::create($year, 6, 1)->format('Y-m-d');
            
        } catch (\Exception $e) {
            Log::warning("Failed to convert day/date {$dayOrDate} to date for year {$year}: " . $e->getMessage());
            // Fallback to June 1st
            return Carbon::create($year, 6, 1)->format('Y-m-d');
        }
    }

    /**
     * Get crop-specific harvest window data
     */
    private function getCropHarvestWindow(string $crop, ?string $variety, string $season, int $year): array
    {
        $cropLower = strtolower($crop);

        // Default harvest windows by crop type
        $cropData = [
            'brussels sprouts' => [
                'standard_start' => "{$year}-09-15",
                'standard_end' => "{$year}-12-15",
                'extended_days' => 45,
                'days_to_harvest' => 90,
                'risk_level' => 'low'
            ],
            'fennel' => [
                'standard_start' => "{$year}-08-15",
                'standard_end' => "{$year}-11-15",
                'extended_days' => 30,
                'days_to_harvest' => 75,
                'risk_level' => 'moderate'
            ],
            'carrots' => [
                'standard_start' => "{$year}-07-15",
                'standard_end' => "{$year}-12-15",
                'extended_days' => 60,
                'days_to_harvest' => 70,
                'risk_level' => 'low'
            ],
            'beets' => [
                'standard_start' => "{$year}-07-01",
                'standard_end' => "{$year}-12-31",
                'extended_days' => 90,
                'days_to_harvest' => 55,
                'risk_level' => 'low'
            ],
            'lettuce' => [
                'standard_start' => "{$year}-06-15",
                'standard_end' => "{$year}-10-15",
                'extended_days' => 45,
                'days_to_harvest' => 45,
                'risk_level' => 'moderate'
            ],
            'kale' => [
                'standard_start' => "{$year}-08-01",
                'standard_end' => "{$year}-12-15",
                'extended_days' => 60,
                'days_to_harvest' => 50,
                'risk_level' => 'low'
            ],
            'spinach' => [
                'standard_start' => "{$year}-06-01",
                'standard_end' => "{$year}-10-31",
                'extended_days' => 30,
                'days_to_harvest' => 40,
                'risk_level' => 'moderate'
            ],
            'radish' => [
                'standard_start' => "{$year}-05-15",
                'standard_end' => "{$year}-10-15",
                'extended_days' => 15,
                'days_to_harvest' => 25,
                'risk_level' => 'high'
            ]
        ];

        $data = $cropData[$cropLower] ?? [
            'standard_start' => "{$year}-08-01",
            'standard_end' => "{$year}-11-30",
            'extended_days' => 30,
            'days_to_harvest' => 60,
            'risk_level' => 'moderate'
        ];

        return [
            'maximum_start' => $data['standard_start'],
            'maximum_end' => date('Y-m-d', strtotime($data['standard_start'] . ' + ' . $data['extended_days'] . ' days')),
            'days_to_harvest' => $data['days_to_harvest'],
            'yield_peak' => date('Y-m-d', strtotime($data['standard_start'] . ' + 30 days')),
            'notes' => "Harvest window for {$crop}" . ($variety ? " ({$variety})" : "") . " in {$season} {$year}",
            'extended_window' => [
                'max_extension_days' => (int)($data['extended_days'] * 0.2), // 20% extension
                'risk_level' => $data['risk_level'] ?? 'moderate'
            ],
            'ai_confidence' => 'fallback_system',
            'source' => 'Crop-Specific Fallback System'
        ];
    }

    /**
     * Extract harvest duration from AI response
     */
    private function extractHarvestDuration(string $aiResponse): int
    {
        // Look for numbers in the response that could be days
        preg_match_all('/(\d+)\s*(?:days?|weeks?|months?)/i', $aiResponse, $matches);
        
        if (!empty($matches[1])) {
            $numbers = array_map('intval', $matches[1]);
            
            // Convert weeks/months to days if mentioned
            $convertedNumbers = [];
            foreach ($numbers as $index => $number) {
                $unit = strtolower($matches[2][$index] ?? 'days');
                
                if (strpos($unit, 'week') !== false) {
                    $convertedNumbers[] = $number * 7;
                } elseif (strpos($unit, 'month') !== false) {
                    $convertedNumbers[] = $number * 30; // Approximate
                } else {
                    $convertedNumbers[] = $number;
                }
            }
            
            // Return the maximum duration found
            return max($convertedNumbers);
        }
        
        // Fallback: look for any numbers that might be days
        preg_match_all('/(\d{2,3})/', $aiResponse, $matches);
        if (!empty($matches[1])) {
                       $numbers = array_map('intval', $matches[1]);
            // Filter numbers that look like reasonable harvest durations (30-300 days)
            $validNumbers = array_filter($numbers, function($num) {
                return $num >= 30 && $num <= 300;
            });
            if (!empty($validNumbers)) {
                return max($validNumbers);
            }
        }
        
        // Ultimate fallback
        return 90; // 90 days default
    }

    /**
     * Get AI-powered harvest window analysis using SymbiosisAI
     */
    private function getAIHarvestWindow(string $cropType, string $variety, array $contextualData): array
    {
        try {
            // Get crop timing presets for context
            $cropPresets = $this->getCropTimingPresets();
            $baseTiming = $cropPresets[$cropType] ?? $cropPresets['default'];

            // Build a detailed prompt for the AI
            $prompt = "Calculate the optimal harvest window for {$cropType}";
            if (!empty($variety)) {
                $prompt .= " variety {$variety}";
            }
            $prompt .= ".\n\nCrop details:\n";
            $prompt .= "- Transplant to harvest: {$baseTiming['transplant_to_harvest']} days\n";
            $prompt .= "- Direct seed to harvest: {$baseTiming['direct_seed_to_harvest']} days\n";
            $prompt .= "- Yield period: {$baseTiming['yield_period']} days\n";
            $prompt .= "- Succession interval: {$baseTiming['succession_interval']} days\n";
            $prompt .= "- Notes: {$baseTiming['notes']}\n\n" . 
                "Current date: " . ($contextualData['current_date'] ?? now()->format('Y-m-d')) . "\n" . 
                "Season: " . ($contextualData['season'] ?? 'current') . "\n\n" . 
                "Provide a JSON response with: maximum_start, maximum_end, days_to_harvest, yield_peak, extended_window (with max_extension_days and risk_level), notes.\n" . 
                "Be specific to this crop and variety. Do not use generic dates.";

            // Prepare messages for SymbiosisAI
            $messages = [
                [
                    'role' => 'system',
                    'content' => 'You are an expert agricultural AI assistant. Provide specific, accurate harvest window calculations based on crop data. Return only valid JSON.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ];

            // Call SymbiosisAI
            $aiResponse = $this->symbiosisAI->chat($messages);

            if (isset($aiResponse['choices'][0]['message']['content'])) {
                $aiText = $aiResponse['choices'][0]['message']['content'];
                
                // Try to parse JSON from AI response
                $parsed = json_decode($aiText, true);
                if ($parsed && isset($parsed['maximum_start'])) {
                    return [
                        'success' => true,
                        'maximum_start' => $parsed['maximum_start'],
                        'maximum_end' => $parsed['maximum_end'] ?? null,
                        'optimal_harvest_days' => $parsed['days_to_harvest'] ?? $baseTiming['yield_period'],
                        'peak_harvest_days' => $parsed['yield_peak'] ?? $baseTiming['transplant_to_harvest'],
                        'maximum_harvest_days' => $parsed['extended_window']['max_extension_days'] ?? ($baseTiming['yield_period'] + 30),
                        'ai_confidence' => 'high',
                        'source' => 'AI Analysis',
                        'contextual_factors' => $parsed['notes'] ?? [],
                        'seasonal_adjustments' => []
                    ];
                }
            }

            // Fallback to preset-based if AI fails
            $optimalWindow = $this->calculateOptimalHarvestWindow($cropType, $variety, $contextualData);

            return [
                'success' => true,
                'maximum_start' => $contextualData['current_date'] ?? now()->format('Y-m-d'),
                'optimal_harvest_days' => $optimalWindow['optimal_days'],
                'peak_harvest_days' => $optimalWindow['peak_days'],
                'maximum_harvest_days' => $optimalWindow['maximum_days'],
                'ai_confidence' => 'low',
                'source' => 'Fallback Presets',
                'contextual_factors' => $optimalWindow['recommendations'],
                'seasonal_adjustments' => $optimalWindow['seasonal_factors']
            ];

        } catch (\Exception $e) {
            Log::warning('AI harvest window calculation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Fallback harvest window calculation when AI services are unavailable
     */
    private function getFallbackHarvestWindow(string $cropType, string $variety, array $contextualData): array
    {
        try {
            $cropPresets = $this->getCropTimingPresets();
            $baseTiming = $cropPresets[$cropType] ?? $cropPresets['default'];

            // Simple calculation based on crop type
            $optimalDays = $baseTiming['optimal_days'] ?? 60;
            $peakDays = $baseTiming['peak_days'] ?? 75;
            $maximumDays = $baseTiming['maximum_days'] ?? 90;

            return [
                'success' => true,
                'maximum_start' => $contextualData['current_date'] ?? now()->format('Y-m-d'),
                'optimal_harvest_days' => $optimalDays,
                'peak_harvest_days' => $peakDays,
                'maximum_harvest_days' => $maximumDays,
                'ai_confidence' => 'low',
                'source' => 'Fallback Calculation',
                'contextual_factors' => ['Using default crop timing presets'],
                'seasonal_adjustments' => []
            ];

        } catch (\Exception $e) {
            Log::error('Fallback harvest window calculation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate optimal harvest window based on crop characteristics and environmental factors
     */
    private function calculateOptimalHarvestWindow(string $cropType, string $variety, array $contextualData): array
    {
        try {
            $cropPresets = $this->getCropTimingPresets();
            $baseTiming = $cropPresets[$cropType] ?? $cropPresets['default'];

            // Base calculations
            $optimalDays = $baseTiming['optimal_days'] ?? 60;
            $peakDays = $baseTiming['peak_days'] ?? 75;
            $maximumDays = $baseTiming['maximum_days'] ?? 90;

            // Adjust for variety-specific characteristics
            if (isset($contextualData['variety_characteristics'])) {
                $characteristics = $contextualData['variety_characteristics'];

                // Adjust for maturity rate
                if (isset($characteristics['maturity_rate'])) {
                    $maturityAdjustment = $characteristics['maturity_rate'] === 'early' ? -7 :
                                        ($characteristics['maturity_rate'] === 'late' ? 7 : 0);
                    $optimalDays += $maturityAdjustment;
                    $peakDays += $maturityAdjustment;
                    $maximumDays += $maturityAdjustment;
                }

                // Adjust for yield potential
                if (isset($characteristics['yield_potential'])) {
                    $yieldAdjustment = $characteristics['yield_potential'] === 'high' ? 3 :
                                     ($characteristics['yield_potential'] === 'low' ? -3 : 0);
                    $optimalDays += $yieldAdjustment;
                }
            }

            // Seasonal adjustments
            $seasonalFactors = [];
            $currentMonth = now()->month;

            if ($currentMonth >= 3 && $currentMonth <= 5) { // Spring
                $seasonalFactors[] = 'Spring planting - optimal conditions';
            } elseif ($currentMonth >= 6 && $currentMonth <= 8) { // Summer
                $seasonalFactors[] = 'Summer growth - monitor heat stress';
                $optimalDays -= 2; // Slightly faster maturation in heat
            } elseif ($currentMonth >= 9 && $currentMonth <= 11) { // Fall
                $seasonalFactors[] = 'Fall harvest - watch for early frost';
                $optimalDays += 3; // Slightly slower maturation in cooler weather
            } else { // Winter
                $seasonalFactors[] = 'Winter conditions - greenhouse recommended';
                $optimalDays += 5; // Slower growth in winter
            }

            // Recommendations based on calculations
            $recommendations = [];
            if ($optimalDays < 45) {
                $recommendations[] = 'Fast-maturing variety - monitor closely for early harvest';
            } elseif ($optimalDays > 90) {
                $recommendations[] = 'Slow-maturing variety - ensure adequate growing season';
            }

            if (isset($contextualData['soil_type'])) {
                $recommendations[] = "Soil type ({$contextualData['soil_type']}) may affect timing";
            }

            return [
                'optimal_days' => max(30, $optimalDays),
                'peak_days' => max(45, $peakDays),
                'maximum_days' => max(60, $maximumDays),
                'recommendations' => $recommendations,
                'seasonal_factors' => $seasonalFactors
            ];

        } catch (\Exception $e) {
            Log::error('Optimal harvest window calculation failed: ' . $e->getMessage());
            return [
                'optimal_days' => 60,
                'peak_days' => 75,
                'maximum_days' => 90,
                'recommendations' => ['Using default timing due to calculation error'],
                'seasonal_factors' => []
            ];
        }
    }

    /**
     * Get variety details by ID
     */
    public function getVariety(Request $request, string $varietyId): JsonResponse
    {
        try {
            // CRITICAL FIX: Frontend variety selection has mismatched IDs between FarmOS and admin DB
            // The problem: Frontend passes numeric ID like "86" but admin DB has different record with ID 86
            
            Log::info('⚡ getVariety called', ['searched_id' => $varietyId]);
            
            // Strategy 1: Try farmos_id (UUID format) - most reliable
            $plantVariety = PlantVariety::where('farmos_id', $varietyId)->first();
            
            if (!$plantVariety) {
                // Strategy 2: Try name match (in case someone passes a variety name)
                $plantVariety = PlantVariety::where('name', $varietyId)->first();
            }
            
            // Strategy 3: SKIP admin database ID lookup to avoid wrong matches!
            // The numeric IDs from frontend don't reliably match admin database IDs
            
            if ($plantVariety) {
                $varietyData = [
                    'id' => $plantVariety->id,
                    'farmos_id' => $plantVariety->farmos_id,
                    'name' => $plantVariety->name,
                    'description' => $plantVariety->description, // Moles Seeds catalog description
                    'crop_family' => $plantVariety->crop_family,
                    'plant_type' => $plantVariety->plant_type,
                    'maturity_days' => $plantVariety->maturity_days,
                    'harvest_days' => $plantVariety->harvest_days,
                    'harvest_start' => $plantVariety->harvest_start,
                    'harvest_end' => $plantVariety->harvest_end,
                    'yield_peak' => $plantVariety->yield_peak,
                    'harvest_window_days' => $plantVariety->harvest_window_days,
                    'harvest_notes' => $plantVariety->harvest_notes,
                    'harvest_method' => $plantVariety->harvest_method,
                    'expected_yield_per_plant' => $plantVariety->expected_yield_per_plant,
                    'image_url' => $plantVariety->image_url,
                    // Spacing data for quantity calculations
                    'in_row_spacing_cm' => $plantVariety->in_row_spacing_cm,
                    'between_row_spacing_cm' => $plantVariety->between_row_spacing_cm,
                    'planting_method' => $plantVariety->planting_method,
                    'source' => 'Admin Database'
                ];

                Log::info('✅ getVariety found in admin database', [
                    'searched_id' => $varietyId,
                    'found_variety' => $plantVariety->name,
                    'strategy_used' => $plantVariety->farmos_id === $varietyId ? 'farmos_id' : 'name',
                    'admin_db_id' => $plantVariety->id,
                    'farmos_id' => $plantVariety->farmos_id
                ]);

                return response()->json([
                    'success' => true,
                    'variety' => $varietyData,
                    'source' => 'Admin Database (Comprehensive)'
                ]);
            }

            // Fallback: Try to get variety from FarmOS API
            Log::info('⚠️ Admin database lookup failed, trying FarmOS API', ['searched_id' => $varietyId]);
            
            $variety = $this->farmOSApi->getVarietyById($varietyId);

            if ($variety) {
                Log::info('⚠️ getVariety fell back to FarmOS API', [
                    'searched_id' => $varietyId,
                    'found_variety' => $variety['name'] ?? 'Unknown'
                ]);
                
                return response()->json([
                    'success' => true,
                    'variety' => $variety,
                    'source' => 'FarmOS API (fallback - consider running sync)'
                ]);
            }

            // Variety not found anywhere
            return response()->json([
                'success' => false,
                'error' => 'Variety not found in local database or FarmOS',
                'variety_id' => $varietyId,
                'suggestion' => 'Run plant variety sync to update local database'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to get variety details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve variety details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get AI service status
     */
    public function getAIStatus(): JsonResponse
    {
        try {
            // Check if AI service is available
            $aiAvailable = $this->symbiosisAI->isAvailable();

            return response()->json([
                'success' => true,
                'ai_available' => $aiAvailable,
                'service' => 'Phi-3 Mini via Ollama',
                'status' => $aiAvailable ? 'online' : 'offline',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => true,
                'ai_available' => false,
                'service' => 'Phi-3 Mini via Ollama',
                'status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ]);
        }
    }

    /**
     * Wake up AI service
     */
    public function wakeUpAI(Request $request): JsonResponse
    {
        try {
            $this->wakeUpAIService();

            return response()->json([
                'success' => true,
                'message' => 'AI service wake-up initiated',
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to wake up AI service: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get seeding and transplant data for AI processing
     */
    public function getSeedingTransplantData(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'crop_type' => 'required|string',
                'variety' => 'nullable|string',
                'season' => 'nullable|string'
            ]);

            $cropType = $validated['crop_type'];
            $variety = $validated['variety'] ?? '';
            $season = $validated['season'] ?? 'current';

            // Get crop timing presets
            $cropPresets = $this->getCropTimingPresets();
            $baseTiming = $cropPresets[$cropType] ?? $cropPresets['default'];

            // Apply seasonal adjustments
            $seasonalAdjustments = $this->getSeasonalAdjustments($season);
            
            $seedingData = [
                'crop_type' => $cropType,
                'variety' => $variety,
                'season' => $season,
                'seeding_to_transplant_days' => max(0, $baseTiming['transplant_days'] + $seasonalAdjustments['transplant_adjustment']),
                'transplant_to_harvest_days' => max(1, $baseTiming['harvest_days'] + $seasonalAdjustments['harvest_adjustment']),
                'optimal_planting_window' => $baseTiming['yield_period'],
                'recommended_spacing' => $this->getRecommendedSpacing($cropType),
                'soil_temperature_requirements' => $this->getSoilTemperatureRequirements($cropType),
                'light_requirements' => $this->getLightRequirements($cropType),
                'source' => 'AI Analysis (Preset-based)',
                'confidence' => 'medium'
            ];

            return response()->json([
                'success' => true,
                'data' => $seedingData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get seeding/transplant data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve seeding/transplant data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit a log entry via API
     */
    public function submitLog(Request $request)
    {
        try {
            // Handle both formats: nested data array (API) and direct form fields (quick forms)
            if ($request->has('data')) {
                // API format with nested data
                $validated = $request->validate([
                    'log_type' => 'required|in:seeding,transplant,harvest,quick',
                    'data' => 'required|array',
                    'data.crop_name' => 'required|string',
                    'data.variety_name' => 'required|string',
                    'data.bed_name' => 'required|string',
                    'data.quantity' => 'required|integer',
                    'data.succession_number' => 'required|integer',
                ]);

                $logType = $validated['log_type'];
                $data = $validated['data'];
            } else {
                // Quick form format with direct fields
                $validated = $request->validate([
                    'log_type' => 'required|in:seeding,transplant,harvest,quick',
                    'crop' => 'required|string',
                    'variety' => 'nullable|string',
                    'location' => 'required|string',
                    'quantity' => 'required|numeric',
                    'seeding_date' => 'required_if:log_type,quick|nullable|date',
                    'transplant_date' => 'nullable|date',
                    'harvest_date' => 'nullable|date',
                    'harvest_end_date' => 'nullable|date',
                    'seeding_notes' => 'nullable|string',
                    'transplant_notes' => 'nullable|string',
                    'harvest_notes' => 'nullable|string',
                    'succession_number' => 'nullable|integer',
                    // New format: logs array for dynamic quick forms
                    'logs' => 'nullable|array',
                    'logs.seeding' => 'nullable|array',
                    'logs.transplanting' => 'nullable|array',
                    'logs.harvest' => 'nullable|array',
                    'season' => 'nullable|string',
                    'crops' => 'nullable|array',
                    'log_types' => 'nullable|array',
                ]);

                $logType = $validated['log_type'];

                // Handle new dynamic quick form format
                if ($logType === 'quick' && isset($validated['logs'])) {
                    // New format with logs array
                    $data = [
                        'crop_name' => $validated['crops'][0] ?? 'Unknown Crop',
                        'variety_name' => 'Generic', // Could be enhanced to handle varieties
                        'bed_name' => $validated['logs']['seeding']['location'] ?? $validated['logs']['transplanting']['location'] ?? $validated['logs']['harvest']['location'] ?? 'Unknown Location',
                        'quantity' => $validated['logs']['seeding']['quantity']['value'] ?? $validated['logs']['transplanting']['quantity']['value'] ?? $validated['logs']['harvest']['quantity']['value'] ?? 100,
                        'succession_number' => $validated['succession_number'] ?? 1,
                        'season' => $validated['season'] ?? date('Y') . ' Succession',
                        'logs' => $validated['logs'],
                        'log_types' => $validated['log_types'] ?? [],
                    ];
                } else {
                    // Legacy format for backward compatibility
                    $data = [
                        'crop_name' => $validated['crop'],
                        'variety_name' => $validated['variety'] ?? 'Generic',
                        'bed_name' => $validated['location'],
                        'quantity' => (int)$validated['quantity'],
                        'succession_number' => $validated['succession_number'] ?? 1,
                    ];

                    // Add quick form specific fields
                    if ($logType === 'quick') {
                        $data['seeding_date'] = $validated['seeding_date'];
                        $data['transplant_date'] = $validated['transplant_date'] ?? null;
                        $data['harvest_date'] = $validated['harvest_date'] ?? null;
                        $data['harvest_end_date'] = $validated['harvest_end_date'] ?? null;
                        $data['seeding_notes'] = $validated['seeding_notes'] ?? null;
                        $data['transplant_notes'] = $validated['transplant_notes'] ?? null;
                        $data['harvest_notes'] = $validated['harvest_notes'] ?? null;
                    } else {
                        // For single log types, use the generic date field
                        $data['seeding_date'] = $validated['date'];
                    }
                }
            }

            // Prepare log data based on type
            $logData = [
                'crop_name' => $data['crop_name'],
                'variety_name' => $data['variety_name'],
                'quantity' => $data['quantity'],
                'notes' => "AI-calculated {$logType} for succession #" . $data['succession_number'] . " at " . $data['bed_name'],
            ];

            // Add date based on log type
            switch ($logType) {
                case 'seeding':
                    $logData['timestamp'] = $data['seeding_date'] ?? now()->toDateString();
                    $result = $this->farmOSApi->createSeedingLog($logData);
                    break;
                case 'transplant':
                    $logData['timestamp'] = $data['transplant_date'] ?? now()->toDateString();
                    $result = $this->farmOSApi->createCropPlan($logData);
                    break;
                case 'harvest':
                    $logData['timestamp'] = $data['harvest_date'] ?? now()->toDateString();
                    $result = $this->farmOSApi->createHarvestLog($logData);
                    break;
                case 'quick':
                    // Handle both old format and new dynamic format
                    $results = [];

                    if (isset($data['logs'])) {
                        // New dynamic format with logs array
                        $locationId = null;
                        $plantingId = null;

                        // Create planting asset first if seeding is included
                        if (isset($data['logs']['seeding'])) {
                            $locationId = $this->findLocationIdByName($data['logs']['seeding']['location']);
                            $plantingId = $this->createPlantingAsset($data, $locationId);
                        }

                        // Create seeding log
                        if (isset($data['logs']['seeding'])) {
                            $seedingData = [
                                'crop_name' => $data['crop_name'],
                                'variety_name' => $data['variety_name'],
                                'quantity' => $data['logs']['seeding']['quantity']['value'] ?? 100,
                                'timestamp' => $data['logs']['seeding']['date'],
                                'notes' => $data['logs']['seeding']['notes'] ?? "AI-calculated seeding for succession #" . $data['succession_number'],
                                'location_id' => $locationId,
                                'planting_id' => $plantingId,
                                'quantity_unit' => $data['logs']['seeding']['quantity']['units'] ?? 'seeds',
                                'status' => isset($data['logs']['seeding']['done']) ? 'done' : 'pending'
                            ];
                            $results['seeding'] = $this->farmOSApi->createSeedingLog($seedingData);
                        }

                        // Create transplant log
                        if (isset($data['logs']['transplanting'])) {
                            $transplantLocationId = $this->findLocationIdByName($data['logs']['transplanting']['location']);
                            $transplantData = [
                                'crop_name' => $data['crop_name'],
                                'variety_name' => $data['variety_name'],
                                'quantity' => $data['logs']['transplanting']['quantity']['value'] ?? 100,
                                'timestamp' => $data['logs']['transplanting']['date'],
                                'notes' => $data['logs']['transplanting']['notes'] ?? "AI-calculated transplant for succession #" . $data['succession_number'],
                                'source_location_id' => $locationId, // From seeding location
                                'destination_location_id' => $transplantLocationId, // To transplant location
                                'planting_id' => $plantingId,
                                'quantity_unit' => $data['logs']['transplanting']['quantity']['units'] ?? 'plants',
                                'status' => isset($data['logs']['transplanting']['done']) ? 'done' : 'pending',
                                'is_movement' => true
                            ];
                            $results['transplant'] = $this->farmOSApi->createTransplantingLog($transplantData);
                        }

                        // Create harvest log
                        if (isset($data['logs']['harvest'])) {
                            $harvestLocationId = $this->findLocationIdByName($data['logs']['harvest']['location'] ?? $data['bed_name']);
                            $harvestData = [
                                'crop_name' => $data['crop_name'],
                                'variety_name' => $data['variety_name'],
                                'quantity' => $data['logs']['harvest']['quantity']['value'] ?? 100,
                                'timestamp' => $data['logs']['harvest']['date'],
                                'notes' => $data['logs']['harvest']['notes'] ?? "AI-calculated harvest for succession #" . $data['succession_number'],
                                'location_id' => $harvestLocationId,
                                'planting_id' => $plantingId,
                                'quantity_unit' => $data['logs']['harvest']['quantity']['units'] ?? 'grams',
                                'status' => isset($data['logs']['harvest']['done']) ? 'done' : 'pending'
                            ];
                            $results['harvest'] = $this->farmOSApi->createHarvestLog($harvestData);
                        }
                    } else {
                        // Legacy format for backward compatibility
                        // Look up location ID from bed name
                        $locationId = $this->findLocationIdByName($data['bed_name']);

                        // Create planting asset first (required for seeding logs)
                        $plantingId = $this->createPlantingAsset($data, $locationId);

                        // 1. Create seeding log
                        if (!empty($data['seeding_date'])) {
                            $seedingData = [
                                'crop_name' => $data['crop_name'],
                                'variety_name' => $data['variety_name'],
                                'quantity' => $data['quantity'],
                                'timestamp' => $data['seeding_date'],
                                'notes' => $data['seeding_notes'] ?? "AI-calculated seeding for succession #" . $data['succession_number'],
                                'location_id' => $locationId,
                                'planting_id' => $plantingId,
                                'quantity_unit' => 'count',
                                'status' => 'done'
                            ];
                            $results['seeding'] = $this->farmOSApi->createSeedingLog($seedingData);
                        }

                        // 2. Create transplant log (if transplant date provided)
                        if (!empty($data['transplant_date'])) {
                            $transplantData = [
                                'crop_name' => $data['crop_name'],
                                'variety_name' => $data['variety_name'],
                                'quantity' => $data['quantity'],
                                'timestamp' => $data['transplant_date'],
                                'notes' => $data['transplant_notes'] ?? "AI-calculated transplant for succession #" . $data['succession_number'],
                                'source_location_id' => $locationId,
                                'destination_location_id' => $locationId,
                                'planting_id' => $plantingId,
                                'quantity_unit' => 'count',
                                'status' => 'done',
                                'is_movement' => true
                            ];
                            $results['transplant'] = $this->farmOSApi->createTransplantingLog($transplantData);
                        }

                        // 3. Create harvest log (if harvest date provided)
                        if (!empty($data['harvest_date'])) {
                            $harvestData = [
                                'crop_name' => $data['crop_name'],
                                'variety_name' => $data['variety_name'],
                                'quantity' => $data['quantity'],
                                'timestamp' => $data['harvest_date'],
                                'notes' => $data['harvest_notes'] ?? "AI-calculated harvest for succession #" . $data['succession_number'],
                                'location_id' => $locationId,
                                'planting_id' => $plantingId,
                                'quantity_unit' => 'weight',
                                'status' => 'done'
                            ];
                            if (!empty($data['harvest_end_date'])) {
                                $harvestData['end_date'] = $data['harvest_end_date'];
                            }
                            $results['harvest'] = $this->farmOSApi->createHarvestLog($harvestData);
                        }
                    }

                    $result = $results;
                    break;
            }

            Log::info("{$logType} log submitted via API", [
                'succession' => $data['succession_number'],
                'result' => $result
            ]);

            return response()->json([
                'success' => true,
                'message' => $logType === 'quick' ? 'Quick planting logs created successfully' : ucfirst($logType) . ' log created successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Log submission failed: ' . $e->getMessage(), [
                'log_type' => $request->input('log_type'),
                'data' => $request->input('data')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create log: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Find location ID by name
     */
    protected function findLocationIdByName(string $bedName): ?string
    {
        try {
            $locations = $this->farmOSApi->getAvailableLocations();

            // Look for exact match first
            foreach ($locations as $location) {
                if (strtolower($location['name']) === strtolower($bedName)) {
                    return $location['id'];
                }
            }

            // Look for partial match
            foreach ($locations as $location) {
                if (stripos($location['name'], $bedName) !== false) {
                    return $location['id'];
                }
            }

            Log::warning("Could not find location ID for bed name: {$bedName}");
            return null;

        } catch (\Exception $e) {
            Log::error("Failed to find location ID for bed '{$bedName}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a planting asset
     */
    protected function createPlantingAsset(array $data, ?string $locationId): ?string
    {
        try {
            $plantingData = [
                'crop_name' => $data['crop_name'],
                'variety_name' => $data['variety_name'] ?? $data['crop_name'],
                'location_id' => $locationId,
                'quantity' => $data['quantity'] ?? 100,
                'notes' => "Succession #" . ($data['succession_number'] ?? 1) . " planting asset"
            ];

            $result = $this->farmOSApi->createPlantingAsset($plantingData);

            if ($result && isset($result['id'])) {
                return $result['id'];
            }

            Log::warning("Failed to create planting asset, result: " . json_encode($result));
            return null;

        } catch (\Exception $e) {
            Log::error("Failed to create planting asset: " . $e->getMessage(), $data);
            return null;
        }
    }

    /**
     * Submit all quick forms at once
     */
    public function submitAllLogs(Request $request)
    {
        try {
            $validated = $request->validate([
                'plantings' => 'required|array',
                'plantings.*.succession_index' => 'required|integer',
                'plantings.*.season' => 'nullable|string',
                'plantings.*.crop_variety' => 'nullable|string',
                'plantings.*.logs' => 'required|array',
                'plantings.*.logs.seeding' => 'nullable|array',
                'plantings.*.logs.transplanting' => 'nullable|array',
                'plantings.*.logs.harvest' => 'nullable|array',
            ]);

            $results = [];
            $errors = [];

            foreach ($validated['plantings'] as $planting) {
                $successionIndex = $planting['succession_index'];
                $season = $planting['season'] ?? '';
                $cropVariety = $planting['crop_variety'] ?? '';
                $logs = $planting['logs'];

                $plantingResults = [
                    'succession_index' => $successionIndex,
                    'logs' => []
                ];

                try {
                    // Get the succession data from session or generate it
                    $successionData = session('succession_plan_data', []);
                    if (empty($successionData) || !isset($successionData[$successionIndex])) {
                        throw new \Exception("No succession data found for index {$successionIndex}");
                    }

                    $succession = $successionData[$successionIndex];
                    $locationId = null;
                    $plantingId = null;

                    // Parse crop variety - could be "Crop Variety" or just "Crop"
                    $cropParts = explode(' ', $cropVariety, 2);
                    $cropName = $cropParts[0] ?? $succession['crop_name'] ?? 'Unknown Crop';
                    $varietyName = count($cropParts) > 1 ? $cropParts[1] : ($succession['variety_name'] ?? $cropName);

                    // Create planting asset first if seeding is included
                    if (isset($logs['seeding'])) {
                        $locationId = $this->findLocationIdByName($logs['seeding']['location']);
                        $plantingAssetData = [
                            'crop_name' => $cropName,
                            'variety_name' => $varietyName,
                            'bed_name' => $logs['seeding']['location'],
                            'quantity' => $logs['seeding']['quantity']['value'] ?? 100,
                            'succession_number' => $successionIndex + 1,
                        ];
                        $plantingId = $this->createPlantingAsset($plantingAssetData, $locationId);
                    }

                    // Create seeding log
                    if (isset($logs['seeding'])) {
                        $seedingData = [
                            'crop_name' => $cropName,
                            'variety_name' => $varietyName,
                            'quantity' => $logs['seeding']['quantity']['value'] ?? 100,
                            'timestamp' => $logs['seeding']['date'],
                            'notes' => $logs['seeding']['notes'] ?? "AI-calculated seeding for succession #" . ($successionIndex + 1),
                            'location_id' => $locationId,
                            'planting_id' => $plantingId,
                            'quantity_unit' => $logs['seeding']['quantity']['units'] ?? 'seeds',
                            'status' => isset($logs['seeding']['done']) ? 'done' : 'pending'
                        ];
                        $plantingResults['logs']['seeding'] = $this->farmOSApi->createSeedingLog($seedingData);
                    }

                    // Create transplant log
                    if (isset($logs['transplanting'])) {
                        $transplantLocationId = $this->findLocationIdByName($logs['transplanting']['location']);
                        $transplantData = [
                            'crop_name' => $cropName,
                            'variety_name' => $varietyName,
                            'quantity' => $logs['transplanting']['quantity']['value'] ?? 100,
                            'timestamp' => $logs['transplanting']['date'],
                            'notes' => $logs['transplanting']['notes'] ?? "AI-calculated transplant for succession #" . ($successionIndex + 1),
                            'source_location_id' => $locationId, // From seeding location
                            'destination_location_id' => $transplantLocationId, // To transplant location
                            'planting_id' => $plantingId,
                            'quantity_unit' => $logs['transplanting']['quantity']['units'] ?? 'plants',
                            'status' => isset($logs['transplanting']['done']) ? 'done' : 'pending',
                            'is_movement' => true
                        ];
                        $plantingResults['logs']['transplant'] = $this->farmOSApi->createTransplantingLog($transplantData);
                    }

                    // Create harvest log
                    if (isset($logs['harvest'])) {
                        $harvestLocationId = $this->findLocationIdByName($logs['harvest']['location'] ?? $logs['seeding']['location']);
                        $harvestData = [
                            'crop_name' => $cropName,
                            'variety_name' => $varietyName,
                            'quantity' => $logs['harvest']['quantity']['value'] ?? 100,
                            'timestamp' => $logs['harvest']['date'],
                            'notes' => $logs['harvest']['notes'] ?? "AI-calculated harvest for succession #" . ($successionIndex + 1),
                            'location_id' => $harvestLocationId,
                            'planting_id' => $plantingId,
                            'quantity_unit' => $logs['harvest']['quantity']['units'] ?? 'grams',
                            'status' => isset($logs['harvest']['done']) ? 'done' : 'pending'
                        ];
                        $plantingResults['logs']['harvest'] = $this->farmOSApi->createHarvestLog($harvestData);
                    }

                    $results[] = $plantingResults;

                } catch (\Exception $e) {
                    $errors[] = [
                        'succession_index' => $successionIndex,
                        'error' => $e->getMessage()
                    ];
                    Log::error("Failed to create logs for succession {$successionIndex}: " . $e->getMessage());
                }
            }

            $successCount = count($results);
            $errorCount = count($errors);

            if ($errorCount > 0) {
                $message = "Created {$successCount} planting record(s) successfully";
                if ($errorCount > 0) {
                    $message .= ", {$errorCount} failed";
                }
            } else {
                $message = "All {$successCount} planting record(s) created successfully";
            }

            return response()->json([
                'success' => $successCount > 0,
                'message' => $message,
                'data' => [
                    'results' => $results,
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk log submission failed: ' . $e->getMessage(), [
                'plantings' => $request->input('plantings')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create planting records: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bed occupancy data from FarmOS for timeline visualization
     */
    public function getBedOccupancy(Request $request): JsonResponse
    {
        try {
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');

            \Log::info('Bed occupancy request', ['start_date' => $startDate, 'end_date' => $endDate]);

            if (!$startDate || !$endDate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Start date and end date are required'
                ], 400);
            }

            // Get bed occupancy data from FarmOS
            $bedData = $this->farmOSApi->getBedOccupancy($startDate, $endDate);

            \Log::info('Bed occupancy data retrieved', ['beds' => count($bedData['beds'] ?? []), 'plantings' => count($bedData['plantings'] ?? [])]);

            return response()->json([
                'success' => true,
                'data' => $bedData
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to get bed occupancy data: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to load bed occupancy data from FarmOS',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Wake up the AI service (internal method)
     */
    protected function wakeUpAIService(): void
    {
        try {
            // Check if AI service is available (this will warm it up)
            $this->symbiosisAI->isAvailable();
            Log::info('AI service wake-up check completed');
        } catch (\Exception $e) {
            Log::warning('AI service wake-up failed: ' . $e->getMessage());
            // Don't throw - this is just a wake-up attempt
        }
    }
}
