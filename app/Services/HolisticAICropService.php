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
        $this->aiServiceUrl = config('services.holistic_ai.url', 'http://localhost:8000');
        $this->timeout = config('services.holistic_ai.timeout', 30);
    }
    
    /**
     * Get comprehensive crop recommendations with holistic intelligence
     */
    public function getHolisticCropRecommendations(array $params): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post($this->aiServiceUrl . '/api/v1/crop-recommendations', [
                    'crop_type' => $params['crop_type'],
                    'planting_date' => $params['planting_date'],
                    'farm_latitude' => $params['farm_latitude'] ?? 0,
                    'farm_longitude' => $params['farm_longitude'] ?? 0,
                    'previous_crops' => $params['previous_crops'] ?? [],
                    'include_holistic' => $params['include_holistic'] ?? true,
                    'include_sacred_geometry' => $params['include_sacred_geometry'] ?? true,
                    'include_biodynamic' => $params['include_biodynamic'] ?? true
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Holistic AI recommendation received', [
                    'crop' => $params['crop_type'],
                    'wisdom_level' => $data['wisdom_level'] ?? 'basic'
                ]);
                
                return $data;
            }
            
            Log::warning('Holistic AI service unavailable, falling back to basic recommendations');
            return $this->getFallbackRecommendations($params['crop_type']);
            
        } catch (\Exception $e) {
            Log::error('Holistic AI service error: ' . $e->getMessage());
            return $this->getFallbackRecommendations($params['crop_type']);
        }
    }
    
    /**
     * Get companion planting suggestions with energetic analysis
     */
    public function getCompanionPlantingSuggestions(string $cropType, bool $includeEnergetic = true): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->aiServiceUrl . "/api/v1/companions/{$cropType}", [
                    'include_energetic' => $includeEnergetic
                ]);
            
            if ($response->successful()) {
                return $response->json();
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
            $response = Http::timeout($this->timeout)
                ->post($this->aiServiceUrl . '/api/v1/succession-planning/holistic', [
                    'crop_type' => $params['crop_type'],
                    'start_date' => $params['start_date'],
                    'succession_count' => $params['succession_count'],
                    'interval_days' => $params['interval_days'],
                    'farm_latitude' => $params['farm_latitude'] ?? 0,
                    'farm_longitude' => $params['farm_longitude'] ?? 0,
                    'available_beds' => $params['available_beds'] ?? [],
                    'holistic_optimization' => $params['holistic_optimization'] ?? true
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Holistic succession plan created', [
                    'crop' => $params['crop_type'],
                    'successions' => $params['succession_count'],
                    'optimization_type' => $data['optimization_type'] ?? 'standard'
                ]);
                
                return $data;
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
            $response = Http::timeout($this->timeout)
                ->get($this->aiServiceUrl . "/api/v1/cosmic-timing/{$cropType}", [
                    'target_date' => $targetDate
                ]);
            
            if ($response->successful()) {
                return $response->json();
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
                return $response->json();
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
}
