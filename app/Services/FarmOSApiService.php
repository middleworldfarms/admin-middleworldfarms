<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

/**
 * FarmOS API Service
 * Integrates with FarmOS to sync harvest logs and update stock levels
 * Now uses centralized FarmOSAuthService for authentication
 */
class FarmOSApiService
{
    private $client;
    private $baseUrl;
    private $authService;
    private $token;
    private $username;
    private $password;

    public function __construct()
    {
        $this->baseUrl = Config::get('farmos.url', 'https://farmos.middleworldfarms.org');
        $this->authService = FarmOSAuthService::getInstance();
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ]
        ]);
    }

    /**
     * Authenticate using centralized auth service
     */
    public function authenticate()
    {
        return $this->authService->authenticate();
    }

    /**
     * Get auth headers using centralized auth service
     */
    private function getAuthHeaders()
    {
        return $this->authService->getAuthHeaders();
    }

    /**
     * Check if the current token is still valid
     */
    private function isTokenValid()
    {
        return $this->authService->authenticate();
    }

    /**
     * Get harvest logs from FarmOS
     */
    public function getHarvestLogs($since = null)
    {
        try {
            $authSuccess = $this->authenticate();
            if (!$authSuccess) {
                Log::warning('FarmOS authentication failed for harvest logs');
                return [];
            }
            
            $headers = ['Accept' => 'application/vnd.api+json'];
            
            // Use centralized auth service
            $authHeaders = $this->getAuthHeaders();
            $headers = array_merge($headers, $authHeaders);
            $requestOptions = ['headers' => $headers];
            
            $params = [
                'filter[status]' => 'done',
                'sort' => '-timestamp'
            ];

            if ($since) {
                $params['filter[timestamp][value]'] = $since;
                $params['filter[timestamp][operator]'] = '>=';
            }
            
            $requestOptions['query'] = $params;

            $response = $this->client->get('/api/log/harvest', $requestOptions);
            $data = json_decode($response->getBody(), true);
            
            // Extract and format harvest data from JSON:API response
            $harvestLogs = [];
            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $log) {
                    $attributes = $log['attributes'] ?? [];
                    
                    $harvestLogs[] = [
                        'id' => $log['id'] ?? '',
                        'crop_name' => $attributes['name'] ?? 'Unknown Crop',
                        'crop_type' => $this->extractCropTypeFromLog($log),
                        'formatted_quantity' => $this->formatQuantityFromLog($log),
                        'harvest_date' => $attributes['timestamp'] ?? \date('c'),
                        'synced_to_stock' => false, // farmOS logs don't have this concept
                        'notes' => $attributes['notes']['value'] ?? '',
                        'status' => $attributes['status'] ?? 'done'
                    ];
                }
            }
            
            Log::info('FarmOS harvest logs loaded', [
                'count' => count($harvestLogs),
                'auth_method' => $this->token ? 'OAuth2' : 'Basic Auth'
            ]);
            
            return $harvestLogs;

        } catch (\Exception $e) {
            Log::error('Failed to fetch harvest logs: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Format quantity information from harvest log
     */
    private function formatQuantityFromLog($log)
    {
        if (isset($log['relationships']['quantity']['data'])) {
            $quantities = $log['relationships']['quantity']['data'];
            if (!empty($quantities)) {
                // For now, return a simple format - could be enhanced with actual quantity data
                return count($quantities) . ' item(s)';
            }
        }
        return 'N/A';
    }

    /**
     * Get plant assets (crops) from FarmOS (paginated)
     */
    public function getPlantAssets(array $filters = [])
    {
        $params = $this->buildFilterParams($filters, ['status','type']);
        $params['include'] = 'plant_type,season';
        $raw = $this->jsonApiPaginatedFetch('/api/asset/plant', $params);
        return ['data' => $raw];
    }

    /**
     * Get crop/variety taxonomy terms
     */
    public function getCropTypes()
    {
        $this->authenticate();
        
        try {
            $response = $this->client->get('/api/taxonomy_term/plant_type', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Failed to fetch crop types: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create inventory adjustment log in FarmOS
     */
    public function createInventoryAdjustment($assetId, $quantity, $measure, $units, $adjustmentType = 'increment')
    {
        $this->authenticate();
        
        $data = [
            'data' => [
                'type' => 'log--activity',
                'attributes' => [
                    'name' => 'Stock adjustment from harvest',
                    'timestamp' => \time(),
                    'status' => 'done',
                    'notes' => [
                        'value' => 'Automatically created from harvest log integration',
                        'format' => 'default'
                    ]
                ],
                'relationships' => [
                    'asset' => [
                        'data' => [
                            ['type' => 'asset--plant', 'id' => $assetId]
                        ]
                    ],
                    'quantity' => [
                        'data' => [
                            [
                                'type' => 'quantity--standard',
                                'attributes' => [
                                    'measure' => $measure,
                                    'value' => [
                                        'decimal' => $quantity
                                    ],
                                    'inventory_adjustment' => $adjustmentType
                                ],
                                'relationships' => [
                                    'units' => [
                                        'data' => ['type' => 'taxonomy_term--unit', 'id' => $units]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        try {
            $response = $this->client->post('/api/log/activity', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ],
                'json' => $data
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Failed to create inventory adjustment: ' . $e->getMessage());
            throw $e;
        }
    }
    /**
     * Get geometry assets (land/fields) for mapping
     */
    public function getGeometryAssets($options = [])
    {
        // Added $options to allow ['refresh'=>true] forcing cache bypass
        try {
            $cacheKey = 'farmos.geometry.assets.v1';
            $forceRefresh = $options['refresh'] ?? request()->boolean('refresh', false);
            if (!$forceRefresh) {
                $cached = \Cache::get($cacheKey);
                if ($cached) {
                    Log::info('FarmOS geometry assets cache hit', ['feature_count' => count($cached['features'])]);
                    return $cached;
                }
            } else {
                Log::info('FarmOS geometry assets forced refresh requested');
            }

            if (!$this->authenticate()) {
                Log::warning('FarmOS authentication failed');
                return [
                    'type' => 'FeatureCollection',
                    'features' => [],
                    'error' => 'Authentication failed - check farmOS credentials'
                ];
            }

            $result = $this->fetchGeometryAssetsInternal();
            // If unauthorized, clear token + retry once
            if (isset($result['__http_status']) && $result['__http_status'] === 401) {
                Log::warning('FarmOS geometry first attempt 401 - purging token & retrying');
                \Cache::forget('farmos_oauth2_token');
                \Cache::forget('farmos_token');
                $this->token = null;
                if ($this->authenticate()) {
                    $result = $this->fetchGeometryAssetsInternal();
                }
            }
            unset($result['__http_status']);
            if (!isset($result['error'])) {
                \Cache::put($cacheKey, $result, now()->addMinutes(10));
            }
            return $result;
        } catch (\Throwable $e) {
            Log::error('Failed to load geometry assets: '.$e->getMessage());
            return [
                'type' => 'FeatureCollection',
                'features' => [],
                'error' => 'Exception: '.$e->getMessage()
            ];
        }
    }

    private function fetchGeometryAssetsInternal()
    {
        try {
            $headers = ['Accept' => 'application/vnd.api+json'];
            $requestOptions = ['headers' => $headers, 'http_errors' => false];
            if ($this->token) {
                $headers['Authorization'] = 'Bearer ' . $this->token;
                $requestOptions['headers'] = $headers;
            } else {
                $requestOptions['auth'] = [$this->username, $this->password];
            }
            $requestOptions['query'] = ['filter[status]' => 'active'];
            $response = $this->client->get('/api/asset/land', $requestOptions);
            $status = $response->getStatusCode();
            if ($status === 401 || $status === 403) {
                return ['type' => 'FeatureCollection', 'features' => [], '__http_status' => $status, 'error' => 'Unauthorized'];
            }
            $data = json_decode($response->getBody(), true);
            if (isset($data['meta']['omitted'])) {
                Log::warning('FarmOS API access denied - insufficient permissions for land assets', [
                    'available_assets' => count($data['meta']['omitted'] ?? []),
                    'user' => $this->username,
                    'auth_method' => $this->token ? 'OAuth2' : 'Basic'
                ]);
                return [
                    'type' => 'FeatureCollection',
                    'features' => [],
                    'error' => 'Access denied - farmOS user needs permission to view land assets',
                    'available_assets' => count($data['meta']['omitted'] ?? []),
                    'asset_ids' => array_keys($data['meta']['omitted'] ?? []),
                    'auth_issue' => true,
                    'auth_method' => $this->token ? 'OAuth2' : 'Basic Auth',
                    'help_url' => 'https://www.drupal.org/docs/8/modules/json-api/filtering#filters-access-control'
                ];
            }
            $features = [];
            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $asset) {
                    if (isset($asset['attributes']['geometry'])) {
                        $geometry = $this->convertWktToGeoJson($asset['attributes']['geometry']);
                        if ($geometry) {
                            $areaSize = $this->calculateGeometryAreaSqM($geometry);
                            $features[] = [
                                'type' => 'Feature',
                                'properties' => [
                                    'name' => $asset['attributes']['name'] ?? 'Unnamed Area',
                                    'id' => $asset['id'],
                                    'status' => $asset['attributes']['status'] ?? 'unknown',
                                    'land_type' => $asset['attributes']['land_type'] ?? 'field',
                                    'notes' => $asset['attributes']['notes']['value'] ?? '',
                                    'created' => $asset['attributes']['created'] ?? null,
                                    'changed' => $asset['attributes']['changed'] ?? null,
                                    'farmos_url' => $this->generateFarmOSAssetUrl($asset),
                                    'is_bed' => ($asset['attributes']['land_type'] ?? '') === 'bed',
                                    'is_block' => ($asset['attributes']['land_type'] ?? '') === 'field',
                                    'parent_block' => $this->getParentBlock($asset),
                                    'area_size_sqm' => $areaSize,
                                    'lazy_details' => true
                                ],
                                'geometry' => $geometry
                            ];
                        }
                    }
                }
            }
            return ['type' => 'FeatureCollection', 'features' => $features];
        } catch (\Exception $e) {
            return ['type' => 'FeatureCollection', 'features' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Get crop planning data (plant assets with planned dates)
     */
    public function getCropPlanningData()
    {
        try {
            $token = $this->authenticate();
            if (!$token) {
                return [];
            }

            // First try to get crop plans directly from farmOS
            $cropPlans = $this->getCropPlansFromAPI($token);
            
            // If no crop plans, build timeline from logs
            if (empty($cropPlans)) {
                $cropPlans = $this->buildTimelineFromLogs($token);
            }
            
            // If still no data, get plant assets
            if (empty($cropPlans)) {
                $cropPlans = $this->getPlantAssetsAsPlans($token);
            }

            return $cropPlans;

        } catch (\Exception $e) {
            Log::error('FarmOS crop planning data fetch failed: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get crop plans using farmOS native JSON:API
     */
    private function getCropPlansFromAPI($token)
    {
        try {
            $response = $this->client->get('/api/plan/crop', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/vnd.api+json'
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            $cropPlans = [];

            if (isset($data['data'])) {
                foreach ($data['data'] as $plan) {
                    $attributes = $plan['attributes'] ?? [];
                    $cropPlans[] = [
                        'id' => $plan['id'] ?? '',
                        'name' => $attributes['name'] ?? '',
                        'crop_type' => $attributes['crop'] ?? 'Unknown',
                        'location' => $this->extractLocationFromPlan($plan),
                        'status' => $attributes['status'] ?? 'active',
                        'created_at' => $attributes['created'] ?? \date('c'),
                        'updated_at' => $attributes['changed'] ?? \date('c'),
                    ];
                }
            }

            return $cropPlans;
            
        } catch (\Exception $e) {
            Log::error('Failed to get crop plans from API: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Build timeline from seeding and harvest logs
     */
    private function buildTimelineFromLogs($token)
    {
        $timeline = [];
        
        try {
            // Get seeding logs
            $seedingResponse = $this->client->get('/api/log/seeding', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/vnd.api+json'
                ]
            ]);
            
            $seedingData = json_decode($seedingResponse->getBody(), true);
            
            if (isset($seedingData['data'])) {
                foreach ($seedingData['data'] as $log) {
                    $attributes = $log['attributes'] ?? [];
                    $timeline[] = [
                        'id' => $log['id'] ?? '',
                        'name' => $attributes['name'] ?? 'Seeding Activity',
                        'crop_type' => $this->extractCropTypeFromLog($log),
                        'location' => $this->extractLocationFromLog($log),
                        'status' => 'active',
                        'type' => 'seeding',
                        'date' => $attributes['timestamp'] ?? \date('c'),
                        'created_at' => $attributes['created'] ?? \date('c'),
                        'updated_at' => $attributes['changed'] ?? \date('c'),
                    ];
                }
            }
            
            return $timeline;
            
        } catch (\Exception $e) {
            Log::error('Failed to build timeline from logs: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get plant assets as crop plans (fallback)
     */
    private function getPlantAssetsAsPlans($token)
    {
        try {
            $response = $this->client->get('/api/asset/plant', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/vnd.api+json'
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            $cropPlans = [];

            if (isset($data['data'])) {
                foreach ($data['data'] as $plant) {
                    $attributes = $plant['attributes'] ?? [];
                    
                    $cropPlans[] = [
                        'farmos_asset_id' => $plant['id'],
                        'crop_type' => $this->extractCropType($plant),
                        'variety' => $attributes['name'] ?? '',
                        'status' => $attributes['status'] ?? 'active',
                        'location' => $this->extractLocationFromAsset($plant),
                        'planned_seeding_date' => $this->extractPlannedDate($plant, 'seeding'),
                        'planned_transplant_date' => $this->extractPlannedDate($plant, 'transplanting'),
                        'planned_harvest_start' => $this->extractPlannedDate($plant, 'harvest'),
                        'planned_harvest_end' => $this->calculateHarvestEnd($this->extractPlannedDate($plant, 'harvest')),
                        'created_at' => $attributes['created'] ?? \date('c'),
                        'updated_at' => $attributes['changed'] ?? \date('c'),
                    ];
                }
            }

            return $cropPlans;
            
        } catch (\Exception $e) {
            Log::error('Failed to get plant assets: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get available crop types and varieties from farmOS taxonomy
     */
    public function getAvailableCropTypes()
    {
        try {
            $this->authenticate();
            
            $cropData = [
                'types' => [],
                'varieties' => []
            ];

            // Get plant types from farmOS taxonomy
            $response = $this->client->get('/api/taxonomy_term/plant_type', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Accept' => 'application/vnd.api+json'
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data['data'])) {
                foreach ($data['data'] as $term) {
                    $attributes = $term['attributes'] ?? [];
                    $name = $attributes['name'] ?? 'Unknown';
                    
                    if ($name !== 'Unknown') {
                        $cropData['types'][] = [
                            'id' => $term['id'] ?? '',
                            'name' => $name,
                            'label' => ucfirst(strtolower($name))
                        ];
                    }
                }
            }

            // Get crop varieties using the fixed pagination method
            try {
                $varieties = $this->getVarieties();
                
                foreach ($varieties as $variety) {
                    $attributes = $variety['attributes'] ?? [];
                    $name = $attributes['name'] ?? '';
                    $description = $attributes['description']['value'] ?? '';
                    $parent = $variety['relationships']['parent']['data'][0]['id'] ?? null;
                    
                    if ($name) {
                        $cropData['varieties'][] = [
                            'id' => $variety['id'] ?? '',
                            'name' => $name,
                            'label' => $name,
                            'description' => $description,
                            'parent_id' => $parent
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Could not fetch crop varieties: ' . $e->getMessage());
            }

            // Add some common defaults if no types found
            if (empty($cropData['types'])) {
                $defaultTypes = ['lettuce', 'tomato', 'carrot', 'cabbage', 'potato', 'spinach', 'kale', 'radish', 'beets', 'arugula'];
                foreach ($defaultTypes as $type) {
                    $cropData['types'][] = [
                        'id' => $type,
                        'name' => $type,
                        'label' => ucfirst($type)
                    ];
                }
            }

            // Sort types alphabetically
            usort($cropData['types'], function($a, $b) {
                return strcmp($a['label'], $b['label']);
            });
            
            usort($cropData['varieties'], function($a, $b) {
                return strcmp($a['label'], $b['label']);
            });

            return $cropData;

        } catch (\Exception $e) {
            Log::error('Failed to fetch crop types from farmOS: ' . $e->getMessage());
            
            // Fallback data
            return [
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
        }
    }

    /**
     * Get available locations from land assets
     */
    public function getAvailableLocations()
    {
        try {
            $token = $this->authenticate();
            if (!$token) {
                return ['Block 1', 'Block 2', 'Block 3', 'Block 4', 'Block 5'];
            }

            $response = $this->client->get('/api/asset/land', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            $locations = [];

            if (isset($data['data'])) {
                foreach ($data['data'] as $land) {
                    $name = $land['attributes']['name'] ?? null;
                    if ($name && !in_array($name, $locations)) {
                        $locations[] = $name;
                    }
                }
            }

            // Add some defaults if empty
            if (empty($locations)) {
                $locations = ['Block 1', 'Block 2', 'Block 3', 'Block 4', 'Block 5'];
            }

            sort($locations);
            return $locations;

        } catch (\Exception $e) {
            Log::error('FarmOS locations fetch failed: ' . $e->getMessage());
            return ['Block 1', 'Block 2', 'Block 3', 'Block 4', 'Block 5'];
        }
    }

    /**
     * Extract crop type from plant asset
     */
    private function extractCropType($plant)
    {
        $attributes = $plant['attributes'] ?? [];
        $name = $attributes['name'] ?? '';
        
        // Try to extract crop type from the name
        $name = strtolower($name);
        
        // Common crop type patterns
        $cropTypes = [
            'lettuce' => ['lettuce', 'salad'],
            'tomato' => ['tomato', 'cherry tomato'],
            'carrot' => ['carrot'],
            'cabbage' => ['cabbage', 'brassica'],
            'potato' => ['potato'],
            'herb' => ['basil', 'parsley', 'cilantro', 'herb'],
            'flower' => ['flower', 'bloom']
        ];

        foreach ($cropTypes as $type => $patterns) {
            foreach ($patterns as $pattern) {
                if (strpos($name, $pattern) !== false) {
                    return $type;
                }
            }
        }

        // Default fallback
        return 'vegetable';
    }

    /**
     * Extract location from plant asset
     */
    private function extractLocationFromAsset($plant)
    {
        $relationships = $plant['relationships'] ?? [];
        
        // Try to get location from relationships
        if (isset($relationships['location']['data']) && !empty($relationships['location']['data'])) {
            $location = $relationships['location']['data'][0];
            return $location['attributes']['name'] ?? 'Unknown';
        }

        // Fallback to extracting from name or generating
        $name = $plant['attributes']['name'] ?? '';
        if (preg_match('/block\s*(\d+)/i', $name, $matches)) {
            return 'Block ' . $matches[1];
        }

        return 'Block 1'; // Default fallback
    }

    /**
     * Extract planned date from plant asset logs
     */
    private function extractPlannedDate($plant, $type)
    {
        // This would need to look at log relationships
        // For now, generate reasonable test dates using Carbon
        $baseDate = Carbon::now();
        
        switch ($type) {
            case 'seeding':
                // Use fixed values instead of random to avoid IDE issues
                return $baseDate->subDays(20)->format('Y-m-d');
            case 'transplanting':
                return $baseDate->addDays(10)->format('Y-m-d');
            case 'harvest':
                return $baseDate->addDays(60)->format('Y-m-d');
            default:
                return $baseDate->format('Y-m-d');
        }
    }

    /**
     * Calculate harvest end date
     */
    private function calculateHarvestEnd($harvestStart)
    {
        if (!$harvestStart) {
            return null;
        }

        // Default 2-week harvest window
        return \date('Y-m-d', strtotime($harvestStart . ' +14 days'));
    }

    /**
     * Extract location from plan using farmOS relationships
     */
    private function extractLocationFromPlan($plan)
    {
        if (isset($plan['relationships']['location']['data'])) {
            $locationData = $plan['relationships']['location']['data'];
            if (is_array($locationData) && !empty($locationData)) {
                return $locationData[0]['id'] ?? 'Unknown Location';
            }
        }
        return 'Unknown Location';
    }
    
    /**
     * Extract crop type from log using farmOS relationships
     */
    private function extractCropTypeFromLog($log)
    {
        if (isset($log['relationships']['asset']['data'])) {
            $assetData = $log['relationships']['asset']['data'];
            if (is_array($assetData) && !empty($assetData)) {
                return $assetData[0]['type'] ?? 'Unknown Crop';
            }
        }
        return 'Unknown Crop';
    }
    
    /**
     * Extract location from log using farmOS relationships
     */
    private function extractLocationFromLog($log)
    {
        if (isset($log['relationships']['location']['data'])) {
            $locationData = $log['relationships']['location']['data'];
            if (is_array($locationData) && !empty($locationData)) {
                return $locationData[0]['id'] ?? 'Unknown Location';
            }
        }
        return 'Unknown Location';
    }

    /**
     * Convert WKT geometry to GeoJSON format
     */
    private function convertWktToGeoJson($geometryData)
    {
        if (!isset($geometryData['value']) || !isset($geometryData['geo_type'])) {
            return null;
        }
        
        $wkt = $geometryData['value'];
        $geoType = $geometryData['geo_type'];
        
        try {
            // Handle different geometry types
            switch (strtoupper($geoType)) {
                case 'POLYGON':
                    return $this->parsePolygonWkt($wkt);
                case 'POINT':
                    return $this->parsePointWkt($wkt);
                case 'LINESTRING':
                    return $this->parseLineStringWkt($wkt);
                case 'GEOMETRYCOLLECTION':
                    return $this->parseGeometryCollectionWkt($wkt);
                default:
                    Log::warning('Unsupported geometry type: ' . $geoType);
                    return null;
            }
        } catch (\Exception $e) {
            Log::error('Failed to convert WKT to GeoJSON: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Parse POLYGON WKT to GeoJSON
     */
    private function parsePolygonWkt($wkt)
    {
        // Remove POLYGON wrapper and parentheses
        $wkt = trim($wkt);
        if (preg_match('/^POLYGON\s*\(\((.*)\)\)$/i', $wkt, $matches)) {
            $coordinateString = $matches[1];
            $coordinates = $this->parseCoordinateString($coordinateString);
            
            return [
                'type' => 'Polygon',
                'coordinates' => [$coordinates] // Polygon coordinates are nested in array
            ];
        }
        return null;
    }
    
    /**
     * Parse POINT WKT to GeoJSON
     */
    private function parsePointWkt($wkt)
    {
        if (preg_match('/^POINT\s*\(([^)]+)\)$/i', $wkt, $matches)) {
            $coordPairs = explode(' ', trim($matches[1]));
            if (count($coordPairs) >= 2) {
                return [
                    'type' => 'Point',
                    'coordinates' => [(float)$coordPairs[0], (float)$coordPairs[1]]
                ];
            }
        }
        return null;
    }
    
    /**
     * Parse LINESTRING WKT to GeoJSON
     */
    private function parseLineStringWkt($wkt)
    {
        if (preg_match('/^LINESTRING\s*\(([^)]+)\)$/i', $wkt, $matches)) {
            $coordinateString = $matches[1];
            $coordinates = $this->parseCoordinateString($coordinateString);
            
            return [
                'type' => 'LineString',
                'coordinates' => $coordinates
            ];
        }
        return null;
    }
    
    /**
     * Parse GEOMETRYCOLLECTION WKT to GeoJSON (simplified - return first polygon)
     */
    private function parseGeometryCollectionWkt($wkt)
    {
        // For simplicity, extract the first polygon from the collection
        if (preg_match('/POLYGON\s*\(\(([^)]+(?:\)[^)]*)*)\)\)/i', $wkt, $matches)) {
            $coordinateString = $matches[1];
            $coordinates = $this->parseCoordinateString($coordinateString);
            
            return [
                'type' => 'Polygon',
                'coordinates' => [$coordinates]
            ];
        }
        return null;
    }
    
    /**
     * Parse coordinate string into array of [lon, lat] pairs
     */
    private function parseCoordinateString($coordinateString)
    {
        $coordinates = [];
        
        // Split by comma to get coordinate pairs
        $pairs = explode(',', $coordinateString);
        
        foreach ($pairs as $pair) {
            $coords = preg_split('/\s+/', trim($pair));
            if (count($coords) >= 2) {
                // GeoJSON uses [longitude, latitude] order
                $coordinates[] = [(float)$coords[0], (float)$coords[1]];
            }
        }
        
        return $coordinates;
    }
    
    /**
     * Get detailed information about a specific asset
     */
    private function getAssetDetails($assetId)
    {
        try {
            $headers = ['Accept' => 'application/vnd.api+json'];
            $requestOptions = ['headers' => $headers];
            if ($this->token) {
                $headers['Authorization'] = 'Bearer ' . $this->token;
                $requestOptions['headers'] = $headers;
            } else {
                $requestOptions['auth'] = [$this->username, $this->password];
            }
            $requestOptions['query'] = [
                'filter[location.id]' => $assetId,
                'filter[status]' => 'active'
            ];
            $response = $this->client->get('/api/asset/plant', $requestOptions);
            if ($response->getStatusCode() >= 500) {
                return [
                    'related_assets' => [],
                    'asset_count' => 0
                ];
            }
            $data = json_decode($response->getBody(), true);
            $relatedAssets = [];
            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $asset) {
                    $attributes = $asset['attributes'] ?? [];
                    $relatedAssets[] = [
                        'id' => $asset['id'],
                        'name' => $attributes['name'] ?? 'Unnamed Asset',
                        'type' => $asset['type'] ?? 'unknown',
                        'status' => $attributes['status'] ?? 'active',
                        'created' => $attributes['created'] ?? null
                    ];
                }
            }
            return [
                'related_assets' => $relatedAssets,
                'asset_count' => count($relatedAssets)
            ];
        } catch (\GuzzleHttp\Exception\ServerException $se) {
            return [
                'related_assets' => [],
                'asset_count' => 0
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch asset details for ' . $assetId . ': ' . $e->getMessage());
            return [
                'related_assets' => [],
                'asset_count' => 0
            ];
        }
    }

    /**
     * Generate proper FarmOS asset URL
     * FarmOS web interface uses numeric IDs in URLs, not UUIDs
     * Based on FarmOS canonical URL pattern: /asset/{drupal_internal__id}
     */
    private function generateFarmOSAssetUrl($asset)
    {
        $assetId = $asset['id'];
        $attributes = $asset['attributes'] ?? [];
        
        // FarmOS uses drupal_internal__id for web URLs (not the UUID)
        // This follows the canonical link pattern defined in core/asset/src/Entity/Asset.php
        if (isset($attributes['drupal_internal__id'])) {
            $numericId = $attributes['drupal_internal__id'];
            return $this->baseUrl . '/asset/' . $numericId;
        }
        
        // Log warning if numeric ID is not available
        Log::warning('FarmOS asset missing drupal_internal__id, cannot generate valid URL', [
            'asset_id' => $assetId, 
            'available_attributes' => array_keys($attributes),
            'name' => $attributes['name'] ?? 'Unknown'
        ]);
        
        // Return null instead of invalid URL
        // FarmOS URLs require the numeric ID to work properly
        return null;
    }

    /**
     * Check if an asset is a bed (smaller plot within a block)
     */
    private function isBedAsset($asset)
    {
        $name = strtolower($asset['attributes']['name'] ?? '');
        
        // Check for bed patterns in name
        return preg_match('/bed\s*\d+|plot\s*\d+|section\s*[a-z]|row\s*\d+/i', $name) ||
               strpos($name, 'bed') !== false ||
               strpos($name, 'plot') !== false;
    }

    /**
     * Check if an asset is a block (larger area containing beds)
     */
    private function isBlockAsset($asset)
    {
        $name = strtolower($asset['attributes']['name'] ?? '');
        
        // Check for block patterns in name
        return preg_match('/block\s*\d+|field\s*\d+|area\s*[a-z]/i', $name) ||
               strpos($name, 'block') !== false ||
               strpos($name, 'field') !== false;
    }

    /**
     * Get parent block for a bed asset
     */
    private function getParentBlock($asset)
    {
        $relationships = $asset['relationships'] ?? [];
        
        // Try to get parent from relationships
        if (isset($relationships['parent']['data']) && !empty($relationships['parent']['data'])) {
            $parent = $relationships['parent']['data'][0];
            return $parent['id'] ?? null;
        }

        // Try to infer from name patterns
        $name = $asset['attributes']['name'] ?? '';
        if (preg_match('/block\s*(\d+).*bed\s*\d+/i', $name, $matches)) {
            return 'block_' . $matches[1];
        }

        return null;
    }

    /**
     * Calculate approximate area of an asset
     */
    private function calculateAssetArea($asset)
    {
        if (!isset($asset['attributes']['geometry'])) {
            return 0;
        }

        $geometry = $asset['attributes']['geometry'];
        if (!isset($geometry['value']) || $geometry['geo_type'] !== 'POLYGON') {
            return 0;
        }

        // Simple area calculation based on coordinate bounds
        $wkt = $geometry['value'];
        if (preg_match('/POLYGON\s*\(\((.*)\)\)/i', $wkt, $matches)) {
            $coordinateString = $matches[1];
            $coordinates = $this->parseCoordinateString($coordinateString);
            
            if (count($coordinates) < 3) return 0;
            
            // Calculate area using shoelace formula
            $area = 0;
            for ($i = 0; $i < count($coordinates) - 1; $i++) {
                $area += ($coordinates[$i][0] * $coordinates[$i + 1][1] - $coordinates[$i + 1][0] * $coordinates[$i][1]);
            }
            return abs($area) / 2;
        }

        return 0;
    }

    /**
     * Calculate geometry area in square meters (approximate)
     */
    private function calculateGeometryAreaSqM($geometry)
    {
        if (!$geometry || !isset($geometry['type']) || !isset($geometry['coordinates'])) {
            return 0;
        }
        if ($geometry['type'] !== 'Polygon') {
            return 0;
        }
        $coords = $geometry['coordinates'][0];
        if (count($coords) < 4) {
            return 0;
        }
        // Approximate area using equirectangular projection around polygon centroid
        $latSum = 0; $lonSum = 0; $n = count($coords)-1; // last point == first point
        for ($i=0; $i<$n; $i++) { $lonSum += $coords[$i][0]; $latSum += $coords[$i][1]; }
        $centroidLatRad = deg2rad($latSum / $n);
        $earthRadius = 6378137; // meters
        $area = 0;
        for ($i=0; $i<$n; $i++) {
            $x1 = deg2rad($coords[$i][0]) * $earthRadius * cos($centroidLatRad);
            $y1 = deg2rad($coords[$i][1]) * $earthRadius;
            $x2 = deg2rad($coords[($i+1)%$n][0]) * $earthRadius * cos($centroidLatRad);
            $y2 = deg2rad($coords[($i+1)%$n][1]) * $earthRadius;
            $area += ($x1 * $y2 - $x2 * $y1);
        }
        return abs($area) / 2.0;
    }

    /**
     * Get asset hierarchy information
     */
    private function getAssetHierarchy($asset)
    {
        $name = $asset['attributes']['name'] ?? '';
        $isBed = $this->isBedAsset($asset);
        $isBlock = $this->isBlockAsset($asset);
        $area = $this->calculateAssetArea($asset);
        
        return [
            'is_bed' => $isBed,
            'is_block' => $isBlock,
            'level' => $isBed ? 'bed' : ($isBlock ? 'block' : 'field'),
            'area' => $area,
            'parent' => $this->getParentBlock($asset),
            'name_pattern' => $this->getNamePattern($name)
        ];
    }

    /**
     * Extract name pattern for hierarchy detection
     */
    private function getNamePattern($name)
    {
        $name = strtolower($name);
        
        if (preg_match('/block\s*(\d+)\s*bed\s*(\d+)/i', $name, $matches)) {
            return 'block_bed';
        } elseif (preg_match('/block\s*(\d+)/i', $name)) {
            return 'block';
        } elseif (preg_match('/bed\s*(\d+)/i', $name)) {
            return 'bed';
        } elseif (preg_match('/field\s*(\d+)/i', $name)) {
            return 'field';
        }
        
        return 'unknown';
    }

    /**
     * Create crop plan in farmOS for succession planning
     * CRITICAL: This sends data to farmOS - farmOS is the master database
     */
    public function createCropPlan($planData)
    {
        $this->authenticate();
        
        // Prepare farmOS plan structure
        $data = [
            'data' => [
                'type' => 'plan--crop',
                'attributes' => [
                    'name' => $planData['crop']['name'] . ' - ' . $planData['type'] . ' Plan',
                    'notes' => [
                        'value' => $planData['notes'] ?? '',
                        'format' => 'default'
                    ],
                    'status' => $planData['status'] ?? 'pending'
                ],
                'relationships' => [
                    'crop' => [
                        'data' => [
                            'type' => 'taxonomy_term--crop_family',
                            'attributes' => [
                                'name' => $planData['crop']['name']
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Add location if provided
        if (!empty($planData['location'])) {
            $data['data']['relationships']['location'] = [
                'data' => [
                    'type' => 'asset--land',
                    'id' => $planData['location']
                ]
            ];
        }

        // Add timing if provided
        if (!empty($planData['timestamp'])) {
            $data['data']['attributes']['timestamp'] = strtotime($planData['timestamp']);
        }

        try {
            $response = $this->client->post('/api/plan/crop', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ],
                'json' => $data
            ]);

            $result = json_decode($response->getBody(), true);
            Log::info('Created farmOS crop plan', [
                'crop' => $planData['crop']['name'],
                'type' => $planData['type'],
                'location' => $planData['location'] ?? 'none',
                'plan_id' => $result['data']['id'] ?? 'unknown'
            ]);

            return $result;
            
        } catch (\Exception $e) {
            Log::error('Failed to create farmOS crop plan: ' . $e->getMessage(), [
                'crop' => $planData['crop']['name'] ?? 'unknown',
                'type' => $planData['type'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generic JSON:API GET with pagination + 401 retry
     */
    private function jsonApiPaginatedFetch($path, $params = [], $maxPages = 20, $pageSize = 50)
    {
        $results = [];
        $page = 0;
        $retried = false;
        
        do {
            $query = array_merge($params, [
                'page[limit]' => $pageSize,
                'page[offset]' => $page * $pageSize
            ]);
            $resp = $this->jsonApiRequest($path, $query);
            
            if ($resp['status'] === 401 && !$retried) {
                // purge token and retry once total
                \Cache::forget('farmos_oauth2_token');
                $this->token = null;
                $this->authenticate();
                $retried = true;
                continue; // redo same page
            }
            
            if ($resp['status'] !== 200) {
                Log::warning('FarmOS API pagination failed', [
                    'path' => $path,
                    'page' => $page,
                    'status' => $resp['status']
                ]);
                break;
            }
            
            $dataChunk = $resp['body']['data'] ?? [];
            $results = array_merge($results, $dataChunk);
            
            // Check if there's a next page link
            $hasNextPage = isset($resp['body']['links']['next']);
            
            // Stop if no next page or if we got no data
            if (!$hasNextPage || count($dataChunk) === 0) {
                break;
            }
            
            $page++;
            $retried = false; // reset retry flag for next page
        } while ($page < $maxPages);
        
        if ($page >= $maxPages) {
            Log::warning('FarmOS API pagination hit max pages limit', [
                'path' => $path,
                'maxPages' => $maxPages,
                'totalFetched' => count($results)
            ]);
        }
        
        return $results;
    }

    private function jsonApiRequest($path, $query = [])
    {
        $this->authenticate();
        $headers = ['Accept' => 'application/vnd.api+json'];
        
        // Use centralized auth service
        $authHeaders = $this->getAuthHeaders();
        $headers = array_merge($headers, $authHeaders);
        $options = ['headers' => $headers, 'http_errors' => false];
        
        if (!empty($query)) $options['query'] = $query;
        $response = $this->client->get($path, $options);
        $status = $response->getStatusCode();
        $body = json_decode($response->getBody(), true);
        return ['status' => $status, 'body' => $body];
    }

    /** Asset fetchers **/
    public function getLandAssets($filters = [])
    {
        $params = $this->buildFilterParams($filters, ['status']);
        return $this->jsonApiPaginatedFetch('/api/asset/land', $params);
    }

    /** Log fetchers **/
    public function getObservationLogs($filters = [])
    {
        $params = $this->buildLogFilterParams($filters);
        return $this->jsonApiPaginatedFetch('/api/log/observation', $params);
    }

    public function getActivityLogs($filters = [])
    {
        $params = $this->buildLogFilterParams($filters);
        return $this->jsonApiPaginatedFetch('/api/log/activity', $params);
    }

    public function getInputLogs($filters = [])
    {
        $params = $this->buildLogFilterParams($filters);
        return $this->jsonApiPaginatedFetch('/api/log/input', $params);
    }

    public function getSeedingLogs($filters = [])
    {
        $params = $this->buildLogFilterParams($filters);
        return $this->jsonApiPaginatedFetch('/api/log/seeding', $params);
    }

    public function getTransplantingLogs($filters = [])
    {
        $params = $this->buildLogFilterParams($filters);
        return $this->jsonApiPaginatedFetch('/api/log/transplanting', $params);
    }

    /** Taxonomy **/
    public function getPlantTypes()
    {
        return $this->jsonApiPaginatedFetch('/api/taxonomy_term/plant_type');
    }

    public function getVarieties()
    {
        return $this->jsonApiPaginatedFetch('/api/taxonomy_term/plant_variety');
    }

    public function getCropFamilies()
    {
        return $this->jsonApiPaginatedFetch('/api/taxonomy_term/crop_family');
    }

    public function getLocations()
    {
        return $this->jsonApiPaginatedFetch('/api/taxonomy_term/location');
    }

    private function buildFilterParams($filters, $allow)
    {
        $params = [];
        foreach ($allow as $f) {
            if (isset($filters[$f])) {
                $params['filter['.$f.']'] = $filters[$f];
            }
        }
        return $params;
    }

    private function buildLogFilterParams($filters)
    {
        return $this->buildFilterParams($filters, ['status','type','asset']);
    }

    /** AI convenience aggregate **/
    public function getFullDataSnapshot()
    {
        return [
            'land_assets' => $this->getLandAssets(['status' => 'active']),
            'plant_assets' => $this->getPlantAssets(['status' => 'active']),
            'logs' => [
                'harvest' => $this->getHarvestLogs(),
                'observation' => $this->getObservationLogs(),
                'activity' => $this->getActivityLogs(),
                'input' => $this->getInputLogs(),
                'seeding' => $this->getSeedingLogs(),
                'transplanting' => $this->getTransplantingLogs(),
            ],
            'taxonomy' => [
                'plant_types' => $this->getPlantTypes(),
                'varieties' => $this->getVarieties(),
                'crop_families' => $this->getCropFamilies(),
                'locations' => $this->getLocations(),
            ]
        ];
    }
    
    /**
     * Create a new plant asset (trusted_public_write)
     * Required fields: name, plant_type_term_id (taxonomy_term--plant_type UUID), variety_term_id (taxonomy_term--variety UUID optional), location_asset_id (asset--land UUID optional), status (default active)
     * Optional: notes
     */
    public function createPlantAsset(array $data)
    {
        $this->authenticate();
        foreach (['name','plant_type_term_id'] as $required) {
            if (empty($data[$required])) {
                return ['error' => 'missing_field', 'field' => $required];
            }
        }
        $asset = [
            'data' => [
                'type' => 'asset--plant',
                'attributes' => [
                    'name' => $data['name'],
                    'status' => $data['status'] ?? 'active',
                ],
                'relationships' => [
                    'plant_type' => [
                        'data' => [
                            'type' => 'taxonomy_term--plant_type',
                            'id' => $data['plant_type_term_id']
                        ]
                    ]
                ]
            ]
        ];
        if (!empty($data['variety_term_id'])) {
            $asset['data']['relationships']['variety'] = [
                'data' => [
                    'type' => 'taxonomy_term--variety',
                    'id' => $data['variety_term_id']
                ]
            ];
        }
        if (!empty($data['location_asset_id'])) {
            $asset['data']['relationships']['location'] = [
                'data' => [
                    'type' => 'asset--land',
                    'id' => $data['location_asset_id']
                ]
            ];
        }
        if (!empty($data['notes'])) {
            $asset['data']['attributes']['notes'] = [
                'value' => $data['notes'],
                'format' => 'default'
            ];
        }
        try {
            $response = $this->client->post('/api/asset/plant', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Accept' => 'application/vnd.api+json',
                    'Content-Type' => 'application/vnd.api+json'
                ],
                'json' => $asset
            ]);
            $status = $response->getStatusCode();
            $body = json_decode($response->getBody(), true);
            if ($status >= 200 && $status < 300) {
                \Log::info('Created plant asset', ['name' => $data['name'], 'id' => $body['data']['id'] ?? null]);
            } else {
                \Log::warning('Plant asset creation non-2xx', ['status' => $status, 'body' => $body]);
            }
            return $body;
        } catch (\Throwable $e) {
            \Log::error('Failed to create plant asset: '.$e->getMessage(), ['name' => $data['name'] ?? 'unknown']);
            return ['error' => 'exception', 'message' => $e->getMessage()];
        }
    }

    /**
     * Create a variety (plant type) in farmOS
     */
    public function createVariety($data)
    {
        try {
            if (!$this->authenticate()) {
                throw new \Exception('Authentication failed');
            }

            // Create taxonomy term for plant type
            $variety = [
                'type' => 'taxonomy_term--plant_type',
                'attributes' => [
                    'name' => $data['name'],
                    'description' => $data['description'] ?? "Plant Type: {$data['plant_type']}",
                    'status' => true
                ]
            ];

            $response = $this->client->post('/api/taxonomy_term/plant_type', [
                'headers' => array_merge($this->getAuthHeaders(), [
                    'Accept' => 'application/vnd.api+json',
                    'Content-Type' => 'application/vnd.api+json'
                ]),
                'json' => ['data' => $variety]
            ]);

            $status = $response->getStatusCode();
            $body = json_decode($response->getBody(), true);
            
            if ($status >= 200 && $status < 300) {
                Log::info('Created variety', ['name' => $data['name'], 'id' => $body['data']['id'] ?? null]);
                return $body['data'] ?? true;
            } else {
                Log::warning('Variety creation non-2xx', ['status' => $status, 'body' => $body]);
                return false;
            }
        } catch (\Throwable $e) {
            Log::error('Failed to create variety: ' . $e->getMessage(), ['name' => $data['name'] ?? 'unknown']);
            return false;
        }
    }
}
