<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HolisticAICropService
{
    private string $aiServiceUrl;
    private int $timeout;
    
    public function __construct()
    {
        $this->aiServiceUrl = config('services.holistic_ai.url', 'http://localhost:8005');
        $this->timeout = config('services.holistic_ai.timeout', 90);
    }
    
    /**
     * Get comprehensive crop recommendations with holistic intelligence
     */
    public function getHolisticCropRecommendations(array $params): array
    {
        try {
            // Override socket timeout for AI responses (under Nginx limit)
            ini_set('default_socket_timeout', 55);
            
            // Use the working /ask endpoint instead of the missing /api/v1/crop-recommendations
            $question = $this->buildCropRecommendationQuestion($params);
            
            $response = Http::timeout(55) // 55 seconds - under Nginx timeout
                ->connectTimeout(10) // 10 second connection timeout
                ->retry(1, 2000) // Retry once after 2 seconds if it fails
                ->withOptions([
                    'stream_context' => stream_context_create([
                        'http' => [
                            'timeout' => 55.0,
                        ]
                    ])
                ])
                ->post($this->aiServiceUrl . '/ask', [
                    'question' => $question,
                    'context' => 'succession_planning_crop_recommendations'
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Holistic AI recommendation received', [
                    'crop' => $params['crop_type'],
                    'wisdom_level' => $data['wisdom'] ?? 'basic'
                ]);
                
                // Convert the AI response to the expected format
                return $this->parseAIResponseToRecommendations($data, $params['crop_type']);
            }
            
            Log::warning('Holistic AI service unavailable, falling back to basic recommendations');
            return $this->getFallbackRecommendations($params['crop_type']);
            
        } catch (\Exception $e) {
            Log::error('Holistic AI service error: ' . $e->getMessage());
            return $this->getFallbackRecommendations($params['crop_type']);
        }
    }
    
    /**
     * Build a comprehensive question for crop recommendations
     */
    private function buildCropRecommendationQuestion(array $params): string
    {
        $cropType = $params['crop_type'];
        $plantingDate = $params['planting_date'] ?? 'current season';
        $latitude = $params['farm_latitude'] ?? 'unknown';
        $longitude = $params['farm_longitude'] ?? 'unknown';
        
        $question = "I need holistic agricultural recommendations for growing {$cropType}. ";
        $question .= "Planting date: {$plantingDate}. ";
        
        if ($latitude !== 'unknown' && $longitude !== 'unknown') {
            $question .= "Farm location: {$latitude}°N, {$longitude}°W. ";
        }
        
        if (!empty($params['previous_crops'])) {
            $previousCrops = implode(', ', $params['previous_crops']);
            $question .= "Previous crops in this area: {$previousCrops}. ";
        }
        
        $question .= "Please provide recommendations including: ";
        $question .= "1) Optimal harvest window timing, ";
        $question .= "2) Succession planting intervals, ";
        $question .= "3) Companion plants, ";
        $question .= "4) Biodynamic considerations, ";
        $question .= "5) Lunar cycle timing if relevant. ";
        $question .= "Focus on practical farming advice with holistic wisdom.";
        
        return $question;
    }
    
    /**
     * Parse AI response into expected recommendation format
     */
    private function parseAIResponseToRecommendations(array $aiResponse, string $cropType): array
    {
        $answer = $aiResponse['answer'] ?? '';
        $moonPhase = $aiResponse['moon_phase'] ?? 'unknown';
        $wisdom = $aiResponse['wisdom'] ?? 'Basic agricultural guidance';
        
        // Extract key information from the AI response using pattern matching
        $recommendations = [
            'crop_type' => $cropType,
            'wisdom_level' => $wisdom,
            'moon_phase' => $moonPhase,
            'recommendations' => [],
            'harvest_window' => $this->extractHarvestWindow($answer),
            'succession_interval' => $this->extractSuccessionInterval($answer),
            'companion_plants' => $this->extractCompanionPlants($answer),
            'biodynamic_notes' => $this->extractBiodynamicNotes($answer),
            'confidence_level' => 'Medium', // AI doesn't provide this directly
            'source' => 'Mistral 7B Holistic AI',
            'generated_at' => now()->toISOString()
        ];
        
        // Parse the full answer into structured recommendations
        $recommendations['recommendations'] = $this->parseRecommendationsFromText($answer);
        
        return $recommendations;
    }
    
    /**
     * Get companion planting suggestions with energetic analysis
     */
    public function getCompanionPlantingSuggestions(string $cropType, bool $includeEnergetic = true): array
    {
        try {
            $question = "What are the best companion plants for {$cropType}? ";
            if ($includeEnergetic) {
                $question .= "Include biodynamic and energetic considerations, ";
                $question .= "sacred geometry principles, and holistic garden design. ";
            }
            $question .= "Focus on practical companion planting that improves soil, deters pests, and enhances growth.";
            
            $response = Http::timeout($this->timeout)
                ->post($this->aiServiceUrl . '/ask', [
                    'question' => $question,
                    'context' => 'companion_planting'
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return $this->parseCompanionResponse($data, $cropType);
            }
            
            return $this->getFallbackCompanions($cropType);
            
        } catch (\Exception $e) {
            Log::error('Companion planting AI error: ' . $e->getMessage());
            return $this->getFallbackCompanions($cropType);
        }
    }
    
    /**
     * Create holistic succession plan with cosmic timing
     */
    public function createHolisticSuccessionPlan(array $params): array
    {
        try {
            $question = "Create a holistic succession planting plan for {$params['crop_type']}. ";
            $question .= "Start date: {$params['start_date']}, ";
            $question .= "Number of successions: {$params['succession_count']}, ";
            $question .= "Interval: {$params['interval_days']} days. ";
            
            if (!empty($params['available_beds'])) {
                $bedCount = count($params['available_beds']);
                $question .= "Available beds: {$bedCount}. ";
            }
            
            $question .= "Include biodynamic calendar considerations, optimal moon phases for planting, ";
            $question .= "and sacred geometry spacing if applicable. ";
            $question .= "Provide specific dates and reasoning for each succession.";
            
            $response = Http::timeout($this->timeout)
                ->post($this->aiServiceUrl . '/ask', [
                    'question' => $question,
                    'context' => 'holistic_succession_planning'
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Holistic succession plan created', [
                    'crop' => $params['crop_type'],
                    'successions' => $params['succession_count'],
                    'ai_wisdom' => $data['wisdom'] ?? 'standard'
                ]);
                
                return $this->parseSuccessionPlanResponse($data, $params);
            }
            
            return $this->getFallbackSuccessionPlan($params);
            
        } catch (\Exception $e) {
            Log::error('Holistic succession planning error: ' . $e->getMessage());
            return $this->getFallbackSuccessionPlan($params);
        }
    }
    
    /**
     * Get cosmic timing recommendations
     */
    public function getCosmicTiming(string $cropType, string $targetDate): array
    {
        try {
            $question = "What is the optimal cosmic timing for planting {$cropType} around {$targetDate}? ";
            $question .= "Include lunar calendar considerations, biodynamic planting days, ";
            $question .= "planetary influences, and sacred agricultural timing. ";
            $question .= "Provide specific dates and reasoning for the recommendations.";
            
            $response = Http::timeout($this->timeout)
                ->post($this->aiServiceUrl . '/ask', [
                    'question' => $question,
                    'context' => 'cosmic_timing'
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return $this->parseCosmicTimingResponse($data, $cropType, $targetDate);
            }
            
            return $this->getFallbackCosmicTiming($cropType, $targetDate);
            
        } catch (\Exception $e) {
            Log::error('Cosmic timing AI error: ' . $e->getMessage());
            return $this->getFallbackCosmicTiming($cropType, $targetDate);
        }
    }
    
    /**
     * Get sacred geometry layout recommendations
     */
    public function getSacredGeometryLayout(string $cropType, float $gardenSizeSqFt = 100): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->aiServiceUrl . "/api/v1/sacred-geometry/{$cropType}", [
                    'garden_size_sq_ft' => $gardenSizeSqFt
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return $this->parseSacredGeometryResponse($data, $cropType);
            }
            
            return $this->getFallbackGeometryLayout($cropType);
            
        } catch (\Exception $e) {
            Log::error('Sacred geometry AI error: ' . $e->getMessage());
            return $this->getFallbackGeometryLayout($cropType);
        }
    }
    
    /**
     * Get holistic wisdom and guidance (Symbiosis-style)
     */
    public function getHolisticWisdom(string $cropType, string $currentDate = null): array
    {
        $currentDate = $currentDate ?? now()->toDateString();
        
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->aiServiceUrl . "/api/v1/holistic-wisdom/{$cropType}", [
                    'current_date' => $currentDate
                ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return $this->getFallbackWisdom($cropType);
            
        } catch (\Exception $e) {
            Log::error('Holistic wisdom AI error: ' . $e->getMessage());
            return $this->getFallbackWisdom($cropType);
        }
    }
    
    /**
     * Sync OpenFarm data with holistic enhancements
     */
    public function syncOpenFarmData(): array
    {
        try {
            $response = Http::timeout(120) // Longer timeout for sync
                ->post($this->aiServiceUrl . '/api/v1/sync-openfarm');
            
            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('OpenFarm data sync completed', [
                    'crops_synced' => $data['synced_crops'] ?? 0,
                    'timestamp' => $data['timestamp'] ?? now()
                ]);
                
                return $data;
            }
            
            Log::error('OpenFarm sync failed: ' . $response->body());
            return ['success' => false, 'error' => 'Sync service unavailable'];
            
        } catch (\Exception $e) {
            Log::error('OpenFarm sync error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Check if holistic AI service is available
     */
    public function isServiceAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->aiServiceUrl . '/');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get enhanced succession planning recommendations for existing controller
     */
    public function enhanceSuccessionPlan(array $basicPlan, array $params): array
    {
        if (!$this->isServiceAvailable()) {
            Log::info('Holistic AI unavailable, returning basic plan');
            return $basicPlan;
        }
        
        try {
            // Get holistic enhancements for the basic plan
            $holisticPlan = $this->createHolisticSuccessionPlan($params);
            
            if ($holisticPlan['success']) {
                // Merge holistic recommendations with basic plan
                return $this->mergeHolisticWithBasicPlan($basicPlan, $holisticPlan);
            }
            
            return $basicPlan;
            
        } catch (\Exception $e) {
            Log::warning('Failed to enhance succession plan: ' . $e->getMessage());
            return $basicPlan;
        }
    }
    
    // Fallback methods for when AI service is unavailable
    
    private function getFallbackRecommendations(string $cropType): array
    {
        return [
            'success' => true,
            'crop' => $cropType,
            'analysis' => [
                'scientific_foundation' => [
                    'name' => $cropType,
                    'basic_spacing' => $this->getBasicSpacing($cropType),
                    'days_to_maturity' => $this->getBasicMaturityDays($cropType)
                ],
                'holistic_wisdom' => [
                    'timing' => ['recommendation' => 'Plant during favorable weather'],
                    'spacing' => ['traditional_spacing' => $this->getBasicSpacing($cropType) . ' inches']
                ]
            ],
            'ai_type' => 'fallback_basic',
            'note' => 'Holistic AI service unavailable, using basic recommendations'
        ];
    }
    
    private function getFallbackCompanions(string $cropType): array
    {
        $basicCompanions = [
            'lettuce' => ['radish', 'carrot'],
            'tomato' => ['basil', 'marigold'],
            'carrot' => ['onion', 'lettuce'],
            'radish' => ['lettuce', 'spinach']
        ];
        
        return [
            'success' => true,
            'crop' => $cropType,
            'traditional_companions' => $basicCompanions[$cropType] ?? [],
            'integration_approach' => 'basic_fallback'
        ];
    }
    
    private function getFallbackSuccessionPlan(array $params): array
    {
        $successions = [];
        $startDate = Carbon::parse($params['start_date']);
        
        for ($i = 0; $i < $params['succession_count']; $i++) {
            $plantingDate = $startDate->copy()->addDays($i * $params['interval_days']);
            
            $successions[] = [
                'succession_number' => $i + 1,
                'planned_date' => $plantingDate->toDateString(),
                'basic_plan' => true,
                'holistic_notes' => 'Holistic AI unavailable - using standard timing'
            ];
        }
        
        return [
            'success' => true,
            'plan' => [
                'crop_type' => $params['crop_type'],
                'successions' => $successions
            ],
            'optimization_type' => 'basic_fallback'
        ];
    }
    
    private function getFallbackCosmicTiming(string $cropType, string $targetDate): array
    {
        return [
            'success' => true,
            'crop' => $cropType,
            'target_date' => $targetDate,
            'cosmic_timing' => [
                'lunar_guidance' => ['recommendation' => 'Plant during waxing moon for leafy crops'],
                'seasonal_energy' => ['current_season' => $this->getCurrentSeason()]
            ],
            'wisdom_tradition' => 'basic_lunar_guidance'
        ];
    }
    
    private function getFallbackGeometryLayout(string $cropType): array
    {
        $basicSpacing = $this->getBasicSpacing($cropType);
        
        return [
            'success' => true,
            'crop' => $cropType,
            'sacred_geometry' => [
                'traditional_spacing' => $basicSpacing . ' inches',
                'recommended' => 'grid_pattern'
            ],
            'design_principles' => 'basic_square_grid'
        ];
    }
    
    private function getFallbackWisdom(string $cropType): array
    {
        return [
            'success' => true,
            'crop' => $cropType,
            'holistic_wisdom' => "Plant {$cropType} with care and attention to natural rhythms. " .
                                "Traditional farming wisdom suggests planting during favorable weather conditions " .
                                "and maintaining consistent care throughout the growing season.",
            'consciousness_level' => 'basic_earth_connection'
        ];
    }
    
    private function mergeHolisticWithBasicPlan(array $basicPlan, array $holisticPlan): array
    {
        // Merge holistic recommendations into basic plan structure
        $enhanced = $basicPlan;
        
        if (isset($holisticPlan['plan']['successions'])) {
            foreach ($holisticPlan['plan']['successions'] as $index => $holisticSuccession) {
                if (isset($enhanced['plantings'][$index])) {
                    $enhanced['plantings'][$index]['holistic_guidance'] = [
                        'moon_phase' => $holisticSuccession['moon_phase'] ?? '',
                        'cosmic_adjustment' => $holisticSuccession['cosmic_adjustment'] ?? false,
                        'sacred_spacing' => $holisticSuccession['sacred_spacing'] ?? [],
                        'biodynamic_guidance' => $holisticSuccession['biodynamic_guidance'] ?? [],
                        'holistic_notes' => $holisticSuccession['holistic_notes'] ?? ''
                    ];
                }
            }
        }
        
        $enhanced['ai_enhancement'] = 'holistic_integrated';
        $enhanced['cosmic_alignment'] = $holisticPlan['cosmic_alignment'] ?? 'basic';
        
        return $enhanced;
    }
    
    // Helper methods
    
    private function getBasicSpacing(string $cropType): int
    {
        $spacingGuide = [
            'lettuce' => 6,
            'carrot' => 2,
            'radish' => 1,
            'tomato' => 18,
            'pepper' => 12,
            'broccoli' => 12,
            'spinach' => 4,
            'kale' => 8
        ];
        
        return $spacingGuide[strtolower($cropType)] ?? 6;
    }
    
    private function getBasicMaturityDays(string $cropType): int
    {
        $maturityGuide = [
            'lettuce' => 45,
            'carrot' => 70,
            'radish' => 25,
            'tomato' => 80,
            'pepper' => 70,
            'broccoli' => 60,
            'spinach' => 40,
            'kale' => 50
        ];
        
        return $maturityGuide[strtolower($cropType)] ?? 60;
    }
    
    private function getCurrentSeason(): string
    {
        $month = now()->month;
        
        if (in_array($month, [12, 1, 2])) return 'winter';
        if (in_array($month, [3, 4, 5])) return 'spring';
        if (in_array($month, [6, 7, 8])) return 'summer';
        return 'autumn';
    }

    /**
     * Get current moon phase for agricultural timing
     */
    private function getCurrentMoonPhase(): string
    {
        // Simple moon phase calculation based on day of month
        // In production, this would use astronomical calculations
        $day = now()->day;
        $moonCycle = $day % 29.5; // Approximate lunar cycle
        
        if ($moonCycle <= 7.4) return 'new_moon';
        if ($moonCycle <= 14.8) return 'waxing_crescent';
        if ($moonCycle <= 22.1) return 'full_moon';
        return 'waning_crescent';
    }

    /**
     * 🌟 Get comprehensive holistic recommendations for a crop
     */
    public function getHolisticRecommendations(string $cropType, array $options = []): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->aiServiceUrl . "/holistic-recommendation/{$cropType}", [
                    'season' => $options['season'] ?? $this->getCurrentSeason(),
                    'include_sacred_geometry' => $options['include_sacred_geometry'] ?? true,
                    'include_lunar_timing' => $options['include_lunar_timing'] ?? true,
                    'include_biodynamic' => $options['include_biodynamic'] ?? true
                ]);
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return $this->getFallbackHolisticRecommendations($cropType);
            
        } catch (\Exception $e) {
            Log::error('Holistic recommendations error: ' . $e->getMessage());
            return $this->getFallbackHolisticRecommendations($cropType);
        }
    }

    /**
     * 🌀 Get sacred geometry spacing recommendations
     */
    public function getSacredGeometrySpacing(string $cropType): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->aiServiceUrl . "/sacred-spacing/{$cropType}");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return $this->getFallbackSpacing($cropType);
            
        } catch (\Exception $e) {
            Log::error('Sacred spacing error: ' . $e->getMessage());
            return $this->getFallbackSpacing($cropType);
        }
    }

    /**
     * 🌸 Get companion mandala pattern
     */
    public function getCompanionMandala(string $cropType): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->aiServiceUrl . "/companion-mandala/{$cropType}");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return $this->getFallbackCompanionMandala($cropType);
            
        } catch (\Exception $e) {
            Log::error('Companion mandala error: ' . $e->getMessage());
            return $this->getFallbackCompanionMandala($cropType);
        }
    }

    /**
     * 🌙 Get current lunar timing and guidance
     */
    public function getCurrentLunarTiming(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->aiServiceUrl . "/moon-phase");
            
            if ($response->successful()) {
                return $response->json();
            }
            
            return $this->getFallbackLunarTiming();
            
        } catch (\Exception $e) {
            Log::error('Lunar timing error: ' . $e->getMessage());
            return $this->getFallbackLunarTiming();
        }
    }

    /**
     * Fallback methods when holistic AI is unavailable
     */
    private function getFallbackHolisticRecommendations(string $cropType): array
    {
        return [
            'success' => true,
            'crop' => $cropType,
            'sacred_geometry_advice' => [
                "Plant {$cropType} in spiral patterns following the golden ratio for optimal energy flow",
                "Use hexagonal arrangements to maximize beneficial energy exchange between plants",
                "Create pentagram formations for protective companion plants around crop perimeter"
            ],
            'lunar_timing' => [
                'best_seeding_phase' => 'New Moon to First Quarter for root crops, First Quarter to Full Moon for leafy crops',
                'current_advice' => 'Align planting with lunar cycles for enhanced vitality'
            ],
            'biodynamic_preparation' => [
                'BD 500 (Horn Manure) - Apply during evening hours for root vitality',
                'BD 501 (Horn Silica) - Apply early morning for light/cosmic force reception'
            ],
            'companion_mandala' => [
                "Center: {$cropType} in golden spiral arrangement",
                "Inner Ring: Protective herbs in sacred geometry formation",
                "Outer Ring: Beneficial flowers in natural mandala pattern"
            ],
            'energetic_considerations' => [
                "Create a meditation space near your {$cropType} bed for positive intention setting",
                "Plant during the Venus hour (first hour after sunrise) for beauty and abundance"
            ],
            'wisdom_source' => 'Ancient agricultural wisdom while cosmic connections restore'
        ];
    }

    private function getFallbackSpacing(string $cropType): array
    {
        $baseSpacing = [
            'lettuce' => 6, 'carrot' => 2, 'radish' => 1, 'spinach' => 4,
            'kale' => 12, 'arugula' => 4, 'tomato' => 18
        ];
        
        $spacing = $baseSpacing[strtolower($cropType)] ?? 8;
        $goldenRatio = 1.618;
        
        return [
            'row_spacing_inches' => round($spacing * $goldenRatio, 1),
            'plant_spacing_inches' => $spacing,
            'bed_width_ratio' => $spacing * 8, // Fibonacci number
            'path_width_ratio' => $spacing * 3, // Fibonacci number
            'sacred_geometry' => 'Based on golden ratio (φ = 1.618) and Fibonacci sequence'
        ];
    }

    private function getFallbackCompanionMandala(string $cropType): array
    {
        $companions = [
            'lettuce' => [
                'Center: Lettuce in spiral pattern (7 plants in Fibonacci arrangement)',
                'Inner Ring: Radishes at cardinal directions (4 plants) - pest deterrent',
                'Middle Ring: Marigolds in pentagram formation (5 plants) - beneficial insects',
                'Outer Ring: Sage at 8 compass points - energetic protection'
            ],
            'carrot' => [
                'Center: Carrot bed in double spiral (yin-yang pattern)',
                'Companion Spiral: Chives interwoven - onion family protection',
                'Guardian Ring: Calendula in sacred 8-pointed star - soil health',
                'Outer Barrier: Dill in Fibonacci spacing - beneficial for carrot family'
            ]
        ];
        
        return [
            'crop' => $cropType,
            'mandala_pattern' => $companions[strtolower($cropType)] ?? [
                "Center: {$cropType} in golden spiral arrangement",
                "Inner Ring: Protective herbs in sacred geometry formation",
                "Outer Ring: Beneficial flowers in natural mandala pattern"
            ],
            'sacred_geometry' => 'Based on natural patterns: spirals, pentagrams, and golden ratio proportions'
        ];
    }

    private function getFallbackLunarTiming(): array
    {
        $currentDay = date('j');
        $phase = $currentDay <= 7 ? 'waxing_crescent' : 
                ($currentDay <= 14 ? 'full_moon' : 
                ($currentDay <= 21 ? 'waning_gibbous' : 'new_moon'));
        
        return [
            'current_phase' => $phase,
            'general_advice' => 'Align your farming activities with natural lunar rhythms',
            'best_activities' => [
                'Plant seeds with intention and gratitude',
                'Water plants during lunar-optimal times',
                'Harvest at peak lunar energy for maximum vitality'
            ],
            'cosmic_wisdom' => 'The moon guides the flow of water and energy in all living things'
        ];
    }

    /**
     * Get AI-optimized harvest window using Mistral 7B with contextual data analysis
     */
    public function getOptimalHarvestWindow(
        string $cropType, 
        ?string $variety = null, 
        ?string $location = null,
        array $contextualData = []
    ): array {
        try {
            // Build comprehensive prompt with available data
            $prompt = $this->buildIntelligentHarvestPrompt($cropType, $variety, $location, $contextualData);

            $response = Http::timeout(60)->post('http://localhost:8005/ask', [
                'question' => $prompt,
                'context' => "data_driven_optimization,succession_planning,harvest_intelligence"
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $aiAnswer = $data['answer'] ?? '';
                
                // Try to extract JSON from Mistral's response
                $jsonData = $this->extractJsonFromAiResponse($aiAnswer);
                
                if ($jsonData) {
                    // Validate and return structured data with AI confidence metrics
                    return [
                        'success' => true,
                        'source' => 'mistral_7b_data_driven',
                        'max_harvest_days' => $jsonData['max_harvest_days'] ?? 21,
                        'optimal_harvest_days' => $jsonData['optimal_harvest_days'] ?? 14,
                        'peak_harvest_days' => $jsonData['peak_harvest_days'] ?? 7,
                        'recommended_successions' => $jsonData['recommended_successions'] ?? 4,
                        'days_between_plantings' => $jsonData['days_between_plantings'] ?? 14,
                        'companion_crops' => $jsonData['companion_crops'] ?? [],
                        'ai_confidence' => $jsonData['confidence_level'] ?? 'medium',
                        'data_quality' => $this->assessDataQuality($contextualData),
                        'recommendations_basis' => $jsonData['analysis_basis'] ?? 'general_guidelines',
                        'raw_response' => $aiAnswer,
                        'moon_phase' => $data['moon_phase'] ?? 'unknown',
                        'contextual_factors' => $this->summarizeContextualFactors($contextualData)
                    ];
                }
            }
            
            Log::warning('Mistral AI harvest optimization failed, using fallback');
            return $this->getFallbackHarvestWindow($cropType, $variety);
            
        } catch (\Exception $e) {
            Log::error('Harvest window optimization error: ' . $e->getMessage());
            return $this->getFallbackHarvestWindow($cropType, $variety);
        }
    }

    /**
     * Build intelligent prompt incorporating all available contextual data
     */
    private function buildIntelligentHarvestPrompt(
        string $cropType, 
        ?string $variety, 
        ?string $location, 
        array $contextualData
    ): string {
        $prompt = "You are an expert agricultural data analyst. Analyze optimal harvest windows for {$cropType}";
        
        if ($variety) $prompt .= " variety: {$variety}";
        if ($location) $prompt .= " in location: {$location}";
        
        // Add historical performance data if available
        if (isset($contextualData['historical_yields'])) {
            $prompt .= "\n\nHISTORICAL PERFORMANCE DATA:\n";
            foreach ($contextualData['historical_yields'] as $year => $data) {
                $prompt .= "- {$year}: Planted {$data['plant_date']}, Harvested {$data['harvest_date']}, Yield: {$data['yield_rating']}/10\n";
            }
        }
        
        // Add weather context if available
        if (isset($contextualData['weather_forecast'])) {
            $prompt .= "\n\nWEATHER FORECAST:\n{$contextualData['weather_forecast']}\n";
        }
        
        if (isset($contextualData['current_season_performance'])) {
            $prompt .= "\n\nCURRENT SEASON CONTEXT:\n{$contextualData['current_season_performance']}\n";
        }
        
        // Add farm-specific patterns if available
        if (isset($contextualData['farm_microclimate_adjustments'])) {
            $prompt .= "\n\nFARM-SPECIFIC PATTERNS:\n{$contextualData['farm_microclimate_adjustments']}\n";
        }
        
        $prompt .= "\n\nBased on this data, always make related recommendations and provide intelligent recommendations for:
1) Maximum harvest window duration in days
2) Optimal harvest window duration for best quality with the exact dates
3) Peak harvest period (days from planting)
4) Recommended successions for continuous harvest
5) Days between plantings (adjusted for conditions)
6) Companion crop recommendations
7) Confidence level (low/medium/high) based on data quality
8) Analysis basis (historical_data/weather_adjusted/farm_specific/general_guidelines)

Format as JSON with keys: max_harvest_days, optimal_harvest_days, peak_harvest_days, recommended_successions, days_between_plantings, companion_crops (array), confidence_level, analysis_basis.";

        return $prompt;
    }

    /**
     * Assess the quality of contextual data for AI analysis
     */
    private function assessDataQuality(array $contextualData): string
    {
        $score = 0;
        $maxScore = 4;
        
        if (isset($contextualData['historical_yields']) && !empty($contextualData['historical_yields'])) $score++;
        if (isset($contextualData['weather_forecast']) && !empty($contextualData['weather_forecast'])) $score++;
        if (isset($contextualData['current_season_performance']) && !empty($contextualData['current_season_performance'])) $score++;
        if (isset($contextualData['farm_microclimate_adjustments']) && !empty($contextualData['farm_microclimate_adjustments'])) $score++;
        
        $percentage = ($score / $maxScore) * 100;
        
        if ($percentage >= 75) return 'excellent';
        if ($percentage >= 50) return 'good';
        if ($percentage >= 25) return 'fair';
        return 'basic';
    }

    /**
     * Summarize contextual factors that influenced AI recommendations
     */
    private function summarizeContextualFactors(array $contextualData): array
    {
        $factors = [];
        
        if (isset($contextualData['historical_yields'])) {
            $factors[] = count($contextualData['historical_yields']) . ' years of historical yield data';
        }
        
        if (isset($contextualData['weather_forecast'])) {
            $factors[] = 'Current weather forecast analysis';
        }
        
        if (isset($contextualData['current_season_performance'])) {
            $factors[] = 'Current season performance trends';
        }
        
        if (isset($contextualData['farm_microclimate_adjustments'])) {
            $factors[] = 'Farm-specific microclimate patterns';
        }
        
        if (empty($factors)) {
            $factors[] = 'General agricultural guidelines (no historical data available)';
        }
        
        return $factors;
    }

    /**
     * 🧠 Get intelligent succession plan with historical data integration
     */
    public function getIntelligentSuccessionPlan(
        string $cropType,
        ?string $variety = null,
        ?string $location = null,
        ?string $desiredHarvestDate = null,
        array $farmHistoricalData = []
    ): array {
        try {
            // Get contextual data for AI analysis
            $contextualData = $this->gatherContextualData($cropType, $farmHistoricalData);
            
            // Get AI-optimized harvest window with all available data
            $harvestWindow = $this->getOptimalHarvestWindow($cropType, $variety, $location, $contextualData);
            
            if (!$harvestWindow['success']) {
                return $harvestWindow;
            }
            
            // Calculate intelligent succession schedule
            $successionPlan = $this->calculateIntelligentSuccessions(
                $cropType,
                $harvestWindow,
                $desiredHarvestDate,
                $contextualData
            );
            
            return [
                'success' => true,
                'source' => 'ai_driven_intelligence',
                'crop_type' => $cropType,
                'variety' => $variety,
                'location' => $location,
                'harvest_window_analysis' => $harvestWindow,
                'succession_schedule' => $successionPlan,
                'ai_confidence' => $harvestWindow['ai_confidence'] ?? 'medium',
                'data_quality' => $harvestWindow['data_quality'] ?? 'basic',
                'recommendations_basis' => $harvestWindow['recommendations_basis'] ?? 'general_guidelines',
                'contextual_factors' => $harvestWindow['contextual_factors'] ?? [],
                'generated_at' => now()->toISOString(),
                'next_data_improvement_suggestions' => $this->suggestDataImprovements($contextualData)
            ];
            
        } catch (\Exception $e) {
            Log::error('Intelligent succession planning error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Gather all available contextual data for AI analysis
     */
    private function gatherContextualData(string $cropType, array $farmHistoricalData): array
    {
        $contextualData = [];
        
        // Add historical yields if available
        if (isset($farmHistoricalData['yields'])) {
            $contextualData['historical_yields'] = $farmHistoricalData['yields'];
        }
        
        // Add weather forecast (would integrate with weather API)
        $contextualData['weather_forecast'] = $this->getWeatherContext();
        
        // Add current season performance (would come from farm logs)
        if (isset($farmHistoricalData['current_season'])) {
            $contextualData['current_season_performance'] = $farmHistoricalData['current_season'];
        }
        
        // Add farm-specific patterns (would be learned over time)
        if (isset($farmHistoricalData['microclimate_patterns'])) {
            $contextualData['farm_microclimate_adjustments'] = $farmHistoricalData['microclimate_patterns'];
        }
        
        return $contextualData;
    }

    /**
     * Calculate intelligent succession schedule based on AI analysis
     */
    private function calculateIntelligentSuccessions(
        string $cropType,
        array $harvestWindow,
        ?string $desiredHarvestDate,
        array $contextualData
    ): array {
        $daysBetween = $harvestWindow['days_between_plantings'] ?? 14;
        $recommendedSuccessions = $harvestWindow['recommended_successions'] ?? 4;
        $maturityDays = $harvestWindow['peak_harvest_days'] ?? $this->getBasicMaturityDays($cropType);
        
        $successions = [];
        $today = now();
        
        if ($desiredHarvestDate) {
            // Work backwards from desired harvest date
            $targetHarvest = Carbon::parse($desiredHarvestDate);
            $firstPlantingDate = $targetHarvest->copy()->subDays($maturityDays);
            
            // Check if first planting is in the past
            if ($firstPlantingDate->isPast()) {
                $firstPlantingDate = $today->copy()->addDays(1); // Start tomorrow
            }
        } else {
            // Start from optimal timing (today or next week)
            $firstPlantingDate = $today->copy()->addDays(3); // Give 3 days prep time
        }
        
        // Generate succession schedule
        for ($i = 0; $i < $recommendedSuccessions; $i++) {
            $plantingDate = $firstPlantingDate->copy()->addDays($i * $daysBetween);
            $harvestDate = $plantingDate->copy()->addDays($maturityDays);
            $isPastOpportunity = $plantingDate->isPast();
            
            $successions[] = [
                'succession_number' => $i + 1,
                'planting_date' => $plantingDate->toDateString(),
                'expected_harvest_date' => $harvestDate->toDateString(),
                'days_to_planting' => $today->diffInDays($plantingDate, false),
                'days_to_harvest' => $today->diffInDays($harvestDate, false),
                'is_past_opportunity' => $isPastOpportunity,
                'planting_window_status' => $isPastOpportunity ? 'missed' : 'available',
                'ai_confidence' => $harvestWindow['ai_confidence'] ?? 'medium',
                'weather_factors' => $this->getPlantingWindowWeather($plantingDate),
                'companion_suggestions' => array_slice($harvestWindow['companion_crops'] ?? [], 0, 2)
            ];
        }
        
        return $successions;
    }

    /**
     * Get weather context for AI analysis
     */
    private function getWeatherContext(): string
    {
        // In production, this would integrate with weather APIs
        // For now, return seasonal context
        $season = $this->getCurrentSeason();
        
        $seasonalContext = [
            'spring' => 'Cool, wet conditions with increasing daylight. Risk of late frost.',
            'summer' => 'Warm, stable conditions. Monitor for heat stress and drought.',
            'autumn' => 'Cooling temperatures with shorter days. Focus on cold-hardy varieties.',
            'winter' => 'Limited growing season. Protected cultivation recommended.'
        ];
        
        return $seasonalContext[$season] ?? 'Variable seasonal conditions';
    }

    /**
     * Get weather factors for specific planting window
     */
    private function getPlantingWindowWeather(Carbon $plantingDate): array
    {
        // In production, would fetch detailed forecast
        $season = $this->getCurrentSeason();
        
        return [
            'season' => $season,
            'estimated_conditions' => 'Variable ' . $season . ' conditions',
            'risk_factors' => $season === 'spring' ? ['Late frost risk'] : []
        ];
    }

    /**
     * Suggest data improvements for better AI recommendations
     */
    private function suggestDataImprovements(array $contextualData): array
    {
        $suggestions = [];
        
        if (!isset($contextualData['historical_yields']) || empty($contextualData['historical_yields'])) {
            $suggestions[] = 'Start tracking yield data by planting date to improve AI accuracy';
        }
        
        if (!isset($contextualData['current_season_performance'])) {
            $suggestions[] = 'Log current season performance to build farm-specific patterns';
        }
        
        if (!isset($contextualData['farm_microclimate_adjustments'])) {
            $suggestions[] = 'Track microclimate variations to develop personalized timing adjustments';
        }
        
        $suggestions[] = 'Connect weather station data for precise environmental analysis';
        $suggestions[] = 'Integrate soil temperature monitoring for optimal planting windows';
        
        return $suggestions;
    }

    /**
     * Extract JSON data from AI response
     */
    private function extractJsonFromAiResponse(string $response): ?array
    {
        // Look for JSON in the response
        preg_match('/\{[^{}]*\}/', $response, $matches);
        
        if (!empty($matches)) {
            $jsonString = $matches[0];
            $decoded = json_decode($jsonString, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        // Fallback: parse structured text response
        return $this->parseStructuredResponse($response);
    }

    /**
     * Parse structured text response when JSON extraction fails
     */
    private function parseStructuredResponse(string $response): ?array
    {
        $data = [];
        
        // Extract numeric values using regex patterns
        if (preg_match('/max.*harvest.*?(\d+)\s*days?/i', $response, $matches)) {
            $data['max_harvest_days'] = (int)$matches[1];
        }
        
        if (preg_match('/optimal.*harvest.*?(\d+)\s*days?/i', $response, $matches)) {
            $data['optimal_harvest_days'] = (int)$matches[1];
        }
        
        if (preg_match('/peak.*harvest.*?(\d+)\s*days?/i', $response, $matches)) {
            $data['peak_harvest_days'] = (int)$matches[1];
        }
        
        if (preg_match('/(\d+)\s*succession/i', $response, $matches)) {
            $data['recommended_successions'] = (int)$matches[1];
        }
        
        if (preg_match('/(\d+)\s*days?\s*between/i', $response, $matches)) {
            $data['days_between_plantings'] = (int)$matches[1];
        }
        
        return $data;
    }
    
    /**
     * Extract harvest window information from AI response text
     */
    private function extractHarvestWindow(string $text): array
    {
        $window = [
            'optimal_days' => null,
            'max_days' => null,
            'peak_days' => null
        ];
        
        // Look for harvest window patterns
        if (preg_match('/harvest.*?window.*?(\d+)[\s-]*(\d+)?\s*days?/i', $text, $matches)) {
            $window['optimal_days'] = (int)$matches[1];
            if (isset($matches[2]) && $matches[2]) {
                $window['max_days'] = (int)$matches[2];
            }
        }
        
        return $window;
    }
    
    /**
     * Extract succession interval from AI response text
     */
    private function extractSuccessionInterval(string $text): array
    {
        $interval = [
            'days' => 14, // default
            'recommended_successions' => 4 // default
        ];
        
        if (preg_match('/succession.*?(\d+)[\s-]*(\d+)?\s*days?/i', $text, $matches)) {
            $interval['days'] = (int)$matches[1];
        }
        
        if (preg_match('/(\d+)\s*succession/i', $text, $matches)) {
            $interval['recommended_successions'] = (int)$matches[1];
        }
        
        return $interval;
    }
    
    /**
     * Extract companion plants from AI response text
     */
    private function extractCompanionPlants(string $text): array
    {
        $companions = [];
        
        // Look for companion plant mentions
        $commonCompanions = ['lettuce', 'radish', 'carrot', 'spinach', 'basil', 'marigold', 'tomato', 'pepper', 'herb', 'onion', 'garlic'];
        
        foreach ($commonCompanions as $companion) {
            if (stripos($text, $companion) !== false) {
                $companions[] = $companion;
            }
        }
        
        return array_unique($companions);
    }
    
    /**
     * Extract biodynamic notes from AI response text
     */
    private function extractBiodynamicNotes(string $text): string
    {
        // Look for biodynamic-related content
        $biodynamicKeywords = ['lunar', 'moon', 'biodynamic', 'cosmic', 'energetic', 'sacred', 'rhythm'];
        
        $notes = [];
        foreach ($biodynamicKeywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                // Extract sentence containing the keyword
                $sentences = preg_split('/[.!?]/', $text);
                foreach ($sentences as $sentence) {
                    if (stripos($sentence, $keyword) !== false) {
                        $notes[] = trim($sentence);
                        break;
                    }
                }
            }
        }
        
        return implode('. ', array_unique($notes));
    }
    
    /**
     * Parse recommendations from full AI response text
     */
    private function parseRecommendationsFromText(string $text): array
    {
        $recommendations = [];
        
        // Split into sections and extract key points
        $sections = preg_split('/\d+\)/', $text);
        
        foreach ($sections as $section) {
            $section = trim($section);
            if (strlen($section) > 20) { // Minimum length for meaningful recommendation
                $recommendations[] = $section;
            }
        }
        
        // If no numbered sections, try to extract key sentences
        if (empty($recommendations)) {
            $sentences = preg_split('/[.!?]/', $text);
            foreach ($sentences as $sentence) {
                $sentence = trim($sentence);
                if (strlen($sentence) > 30 && 
                    (stripos($sentence, 'recommend') !== false || 
                     stripos($sentence, 'should') !== false || 
                     stripos($sentence, 'plant') !== false)) {
                    $recommendations[] = $sentence;
                }
            }
        }
        
        return array_slice($recommendations, 0, 5); // Limit to 5 key recommendations
    }
    
    /**
     * Parse companion planting AI response
     */
    private function parseCompanionResponse(array $aiResponse, string $cropType): array
    {
        $answer = $aiResponse['answer'] ?? '';
        
        return [
            'crop_type' => $cropType,
            'companions' => $this->extractCompanionPlants($answer),
            'beneficial_relationships' => $this->extractBeneficialRelationships($answer),
            'avoid_planting_with' => $this->extractAvoidPlants($answer),
            'energetic_notes' => $this->extractBiodynamicNotes($answer),
            'moon_phase' => $aiResponse['moon_phase'] ?? 'unknown',
            'wisdom' => $aiResponse['wisdom'] ?? 'Holistic companion planting guidance',
            'source' => 'Mistral 7B Holistic AI'
        ];
    }
    
    /**
     * Extract beneficial plant relationships from text
     */
    private function extractBeneficialRelationships(string $text): array
    {
        $relationships = [];
        
        // Look for benefit patterns
        if (preg_match_all('/(improves?|enhance[sd]?|benefit[sd]?|help[sd]?).*?(soil|growth|pest|disease)/i', $text, $matches)) {
            foreach ($matches[0] as $match) {
                $relationships[] = trim($match);
            }
        }
        
        return array_unique($relationships);
    }
    
    /**
     * Extract plants to avoid from text
     */
    private function extractAvoidPlants(string $text): array
    {
        $avoid = [];
        
        // Look for avoid/negative patterns
        if (preg_match_all('/(avoid|don\'t plant|not.*with|compete[sd]?).*?(\w+)/i', $text, $matches)) {
            $commonPlants = ['walnut', 'fennel', 'sunflower', 'corn', 'bean', 'peas'];
            foreach ($commonPlants as $plant) {
                if (stripos($text, $plant) !== false && stripos($text, 'avoid') !== false) {
                    $avoid[] = $plant;
                }
            }
        }
        
        return array_unique($avoid);
    }
    
    /**
     * Parse succession plan AI response
     */
    private function parseSuccessionPlanResponse(array $aiResponse, array $params): array
    {
        $answer = $aiResponse['answer'] ?? '';
        
        return [
            'crop_type' => $params['crop_type'],
            'total_successions' => $params['succession_count'],
            'interval_days' => $params['interval_days'],
            'start_date' => $params['start_date'],
            'moon_phase' => $aiResponse['moon_phase'] ?? 'unknown',
            'optimization_type' => 'holistic_ai',
            'planting_schedule' => $this->extractPlantingSchedule($answer, $params),
            'biodynamic_recommendations' => $this->extractBiodynamicNotes($answer),
            'cosmic_considerations' => $this->extractCosmicConsiderations($answer),
            'wisdom' => $aiResponse['wisdom'] ?? 'Holistic succession planning guidance',
            'source' => 'Mistral 7B Holistic AI',
            'confidence_level' => 'High'
        ];
    }
    
    /**
     * Extract planting schedule from AI response
     */
    private function extractPlantingSchedule(string $text, array $params): array
    {
        $schedule = [];
        $startDate = Carbon::parse($params['start_date']);
        
        // Generate schedule based on interval if specific dates aren't in response
        for ($i = 0; $i < $params['succession_count']; $i++) {
            $plantingDate = $startDate->copy()->addDays($i * $params['interval_days']);
            $schedule[] = [
                'succession' => $i + 1,
                'planting_date' => $plantingDate->format('Y-m-d'),
                'moon_phase_recommended' => $this->getCurrentMoonPhase(),
                'ai_notes' => "Succession {($i + 1)} - optimal spacing maintained"
            ];
        }
        
        return $schedule;
    }
    
    /**
     * Extract cosmic considerations from AI response
     */
    private function extractCosmicConsiderations(string $text): array
    {
        $considerations = [];
        
        $cosmicKeywords = ['lunar', 'moon', 'cosmic', 'planetary', 'star', 'celestial', 'rhythm'];
        
        foreach ($cosmicKeywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                $considerations[] = "Consider {$keyword} influences for optimal growth";
            }
        }
        
        return $considerations;
    }
    
    /**
     * Parse cosmic timing AI response
     */
    private function parseCosmicTimingResponse(array $aiResponse, string $cropType, string $targetDate): array
    {
        $answer = $aiResponse['answer'] ?? '';
        
        return [
            'crop_type' => $cropType,
            'target_date' => $targetDate,
            'moon_phase' => $aiResponse['moon_phase'] ?? 'unknown',
            'optimal_dates' => $this->extractOptimalDates($answer, $targetDate),
            'lunar_calendar' => $this->extractLunarRecommendations($answer),
            'biodynamic_days' => $this->extractBiodynamicDays($answer),
            'planetary_influences' => $this->extractPlanetaryInfluences($answer),
            'wisdom' => $aiResponse['wisdom'] ?? 'Cosmic timing guidance',
            'source' => 'Mistral 7B Holistic AI'
        ];
    }
    
    /**
     * Extract optimal planting dates from text
     */
    private function extractOptimalDates(string $text, string $targetDate): array
    {
        $dates = [];
        
        // Look for date patterns in the response
        if (preg_match_all('/(\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4})/', $text, $matches)) {
            foreach ($matches[1] as $dateMatch) {
                try {
                    $date = Carbon::parse($dateMatch);
                    $dates[] = [
                        'date' => $date->format('Y-m-d'),
                        'reason' => 'AI recommended optimal date'
                    ];
                } catch (\Exception $e) {
                    // Skip invalid dates
                }
            }
        }
        
        // If no specific dates found, provide general guidance around target date
        if (empty($dates)) {
            $target = Carbon::parse($targetDate);
            $dates[] = [
                'date' => $target->format('Y-m-d'),
                'reason' => 'Target date with holistic considerations'
            ];
        }
        
        return $dates;
    }
    
    /**
     * Extract lunar recommendations from text
     */
    private function extractLunarRecommendations(string $text): array
    {
        $lunar = [];
        
        $lunarPhases = ['new moon', 'waxing', 'full moon', 'waning'];
        foreach ($lunarPhases as $phase) {
            if (stripos($text, $phase) !== false) {
                $lunar[] = "Consider {$phase} for optimal growth energy";
            }
        }
        
        return $lunar;
    }
    
    /**
     * Extract biodynamic day recommendations
     */
    private function extractBiodynamicDays(string $text): array
    {
        $days = [];
        
        $biodynamicTypes = ['fruit days', 'root days', 'leaf days', 'flower days'];
        foreach ($biodynamicTypes as $type) {
            if (stripos($text, $type) !== false || stripos($text, str_replace(' days', '', $type)) !== false) {
                $days[] = $type;
            }
        }
        
        return $days;
    }
    
    /**
     * Extract planetary influences
     */
    private function extractPlanetaryInfluences(string $text): array
    {
        $influences = [];
        
        $planets = ['venus', 'mars', 'mercury', 'jupiter', 'saturn', 'moon', 'sun'];
        foreach ($planets as $planet) {
            if (stripos($text, $planet) !== false) {
                $influences[] = ucfirst($planet) . ' influence noted';
            }
        }
        
        return $influences;
    }

    /**
     * Fallback harvest window data when AI is unavailable
     */
    private function getFallbackHarvestWindow(string $cropType, ?string $variety = null): array
    {
        // Basic crop-specific harvest windows
        $fallbackData = [
            'lettuce' => [
                'max_harvest_days' => 21,
                'optimal_harvest_days' => 14,
                'peak_harvest_days' => 7,
                'recommended_successions' => 6,
                'days_between_plantings' => 14,
                'companion_crops' => ['radishes', 'carrots', 'herbs']
            ],
            'spinach' => [
                'max_harvest_days' => 28,
                'optimal_harvest_days' => 21,
                'peak_harvest_days' => 10,
                'recommended_successions' => 4,
                'days_between_plantings' => 21,
                'companion_crops' => ['lettuce', 'arugula', 'peas']
            ],
            'carrots' => [
                'max_harvest_days' => 60,
                'optimal_harvest_days' => 30,
                'peak_harvest_days' => 14,
                'recommended_successions' => 3,
                'days_between_plantings' => 30,
                'companion_crops' => ['lettuce', 'onions', 'herbs']
            ]
        ];
        
        $defaults = [
            'max_harvest_days' => 21,
            'optimal_harvest_days' => 14,
            'peak_harvest_days' => 7,
            'recommended_successions' => 4,
            'days_between_plantings' => 14,
            'companion_crops' => []
        ];
        
        $data = $fallbackData[strtolower($cropType)] ?? $defaults;
        
        return array_merge($data, [
            'success' => true,
            'source' => 'fallback_rules',
            'note' => 'AI service unavailable, using basic crop data'
        ]);
    }

    /**
     * Parse sacred geometry AI response
     */
    private function parseSacredGeometryResponse(array $aiResponse, string $cropType): array
    {
        $answer = $aiResponse['answer'] ?? '';
        
        return [
            'crop_type' => $cropType,
            'geometry_type' => 'sacred_spiral',
            'spacing_pattern' => $this->extractSpacingPattern($answer),
            'sacred_ratios' => $this->extractSacredRatios($answer),
            'orientation' => $this->extractOrientation($answer),
            'energy_flow' => $this->extractEnergyFlow($answer),
            'moon_phase' => $aiResponse['moon_phase'] ?? 'unknown',
            'wisdom' => $aiResponse['wisdom'] ?? 'Sacred geometry guidance',
            'source' => 'Mistral 7B Holistic AI'
        ];
    }

    /**
     * Extract spacing pattern from AI response
     */
    private function extractSpacingPattern(string $text): array
    {
        // Look for spacing patterns in the response
        $patterns = [];
        
        if (preg_match('/(\d+)\s*(inch|ft|cm|m)/i', $text, $matches)) {
            $patterns['primary_spacing'] = $matches[1] . ' ' . strtolower($matches[2]);
        }
        
        if (stripos($text, 'spiral') !== false) {
            $patterns['type'] = 'spiral';
        } elseif (stripos($text, 'mandala') !== false) {
            $patterns['type'] = 'mandala';
        } else {
            $patterns['type'] = 'grid';
        }
        
        return $patterns;
    }

    /**
     * Extract sacred ratios from AI response
     */
    private function extractSacredRatios(string $text): array
    {
        $ratios = [];
        
        if (stripos($text, 'golden') !== false || stripos($text, '1.618') !== false) {
            $ratios[] = 'golden_ratio';
        }
        
        if (stripos($text, 'fibonacci') !== false) {
            $ratios[] = 'fibonacci';
        }
        
        return $ratios;
    }

    /**
     * Extract orientation from AI response
     */
    private function extractOrientation(string $text): string
    {
        if (stripos($text, 'north') !== false) return 'north_facing';
        if (stripos($text, 'east') !== false) return 'east_facing';
        if (stripos($text, 'south') !== false) return 'south_facing';
        if (stripos($text, 'west') !== false) return 'west_facing';
        
        return 'solar_oriented';
    }

    /**
     * Extract energy flow from AI response
     */
    private function extractEnergyFlow(string $text): array
    {
        $flow = [];
        
        if (stripos($text, 'clockwise') !== false) {
            $flow['direction'] = 'clockwise';
        } elseif (stripos($text, 'counter') !== false) {
            $flow['direction'] = 'counterclockwise';
        } else {
            $flow['direction'] = 'natural';
        }
        
        $flow['elements'] = [];
        if (stripos($text, 'water') !== false) $flow['elements'][] = 'water';
        if (stripos($text, 'fire') !== false) $flow['elements'][] = 'fire';
        if (stripos($text, 'earth') !== false) $flow['elements'][] = 'earth';
        if (stripos($text, 'air') !== false) $flow['elements'][] = 'air';
        
        return $flow;
    }
}
