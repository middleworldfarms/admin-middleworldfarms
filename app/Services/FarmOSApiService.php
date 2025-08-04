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
 */
class FarmOSApiService
{
    private $client;
    private $baseUrl;
    private $username;
    private $password;
    private $token;

    public function __construct()
    {
        $this->baseUrl = Config::get('farmos.url', 'https://farmos.middleworldfarms.org');
        $this->username = Config::get('farmos.username');
        $this->password = Config::get('farmos.password');
        
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
     * Authenticate with FarmOS using OAuth2 client credentials
     * Falls back to basic auth if OAuth2 fails
     */
    public function authenticate()
    {
        // Try OAuth2 first
        $token = $this->getOAuth2Token();
        if ($token) {
            $this->token = $token;
            Log::info('FarmOS OAuth2 authentication successful');
            return true;
        }
        
        // Fallback to basic auth
        if (!$this->username || !$this->password) {
            throw new \Exception('FarmOS OAuth2 failed and no basic auth credentials available');
        }
        
        // Clear token since we're using basic auth
        $this->token = null;
        Log::info('FarmOS falling back to basic authentication');
        return true;
    }

    /**
     * Get OAuth2 token using client credentials
     */
    private function getOAuth2Token()
    {
        try {
            $clientId = Config::get('farmos.client_id');
            $clientSecret = Config::get('farmos.client_secret');
            
            if (!$clientId || !$clientSecret) {
                Log::info('OAuth2 credentials not configured, skipping OAuth2');
                return null;
            }

            // Check cache first
            $cacheKey = 'farmos_oauth2_token';
            if (Cache::has($cacheKey)) {
                $this->token = Cache::get($cacheKey);
                Log::info('Using cached OAuth2 token');
                return $this->token;
            }

            // Request new token
            $scope = Config::get('farmos.oauth_scope', 'farmos_restws_access');
            
            Log::info('Requesting OAuth2 token', [
                'client_id' => $clientId,
                'scope' => $scope,
                'url' => $this->baseUrl . '/oauth/token'
            ]);
            
            $response = $this->client->post('/oauth/token', [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'scope' => $scope
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            
            if (isset($data['access_token'])) {
                $this->token = $data['access_token'];
                $expiresIn = $data['expires_in'] ?? 3600;
                
                // Cache token for 90% of its lifetime (in seconds)
                Cache::put($cacheKey, $this->token, intval($expiresIn * 0.9));
                
                Log::info('OAuth2 token acquired successfully', ['expires_in' => $expiresIn]);
                return $this->token;
            }

            Log::warning('OAuth2 token request failed', ['response' => $data]);
            return null;

        } catch (\Exception $e) {
            Log::warning('OAuth2 authentication failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if the current token is still valid
     */
    private function isTokenValid()
    {
        return Cache::has('farmos_token');
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
            $requestOptions = ['headers' => $headers];
            
            // Use OAuth2 token if available, otherwise fall back to basic auth
            if ($this->token) {
                $headers['Authorization'] = 'Bearer ' . $this->token;
                $requestOptions['headers'] = $headers;
            } else {
                $requestOptions['auth'] = [$this->username, $this->password];
            }
            
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
                        'harvest_date' => $attributes['timestamp'] ?? date('c'),
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
     * Get plant assets (crops) from FarmOS
     */
    public function getPlantAssets()
    {
        $token = $this->authenticate();
        if (!$token) {
            return [];
        }
        
        try {
            $response = $this->client->get('/api/asset/plant', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/vnd.api+json',
                    'Content-Type' => 'application/vnd.api+json',
                ],
                'query' => [
                    'include' => 'plant_type,season',
                    'filter[status]' => 'active'
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Failed to fetch plant assets: ' . $e->getMessage());
            return [];
        }
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
                    'timestamp' => time(),
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
    public function getGeometryAssets()
    {
        try {
            $authSuccess = $this->authenticate();
            if (!$authSuccess) {
                Log::warning('FarmOS authentication failed');
                return [
                    'type' => 'FeatureCollection',
                    'features' => [],
                    'error' => 'Authentication failed - check farmOS credentials'
                ];
            }

            $headers = ['Accept' => 'application/vnd.api+json'];
            $requestOptions = ['headers' => $headers];
            
            // Use OAuth2 token if available, otherwise fall back to basic auth
            if ($this->token) {
                $headers['Authorization'] = 'Bearer ' . $this->token;
                $requestOptions['headers'] = $headers;
            } else {
                $requestOptions['auth'] = [$this->username, $this->password];
            }
            
            $requestOptions['query'] = ['filter[status]' => 'active'];

            $response = $this->client->get('/api/asset/land', $requestOptions);
            $data = json_decode($response->getBody(), true);
            
            // Check for authorization issues
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
            
            // Convert to GeoJSON format using WKT conversion
            $features = [];
            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $asset) {
                    if (isset($asset['attributes']['geometry'])) {
                        $geometry = $this->convertWktToGeoJson($asset['attributes']['geometry']);
                        
                        if ($geometry) {
                            // Get additional asset details
                            $assetId = $asset['id'];
                            $assetDetails = $this->getAssetDetails($assetId);
                            
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
                                    'asset_details' => $assetDetails,
                                    'farmos_url' => $this->generateFarmOSAssetUrl($asset),
                                    // Enhanced properties for smart selection
                                    'is_bed' => $this->isBedAsset($asset),
                                    'is_block' => $this->isBlockAsset($asset),
                                    'parent_block' => $this->getParentBlock($asset),
                                    'area_size' => $this->calculateAssetArea($asset),
                                    'asset_hierarchy' => $this->getAssetHierarchy($asset)
                                ],
                                'geometry' => $geometry
                            ];
                        }
                    }
                }
            }

            Log::info('FarmOS geometry assets loaded successfully', [
                'feature_count' => count($features),
                'auth_method' => $this->token ? 'OAuth2' : 'Basic Auth'
            ]);
            
            return [
                'type' => 'FeatureCollection',
                'features' => $features
            ];

        } catch (\Exception $e) {
            Log::error('FarmOS geometry fetch failed: ' . $e->getMessage());
            return [
                'type' => 'FeatureCollection',
                'features' => [],
                'error' => $e->getMessage()
            ];
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
                        'created_at' => $attributes['created'] ?? date('c'),
                        'updated_at' => $attributes['changed'] ?? date('c'),
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
                        'date' => $attributes['timestamp'] ?? date('c'),
                        'created_at' => $attributes['created'] ?? date('c'),
                        'updated_at' => $attributes['changed'] ?? date('c'),
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
                        'created_at' => $attributes['created'] ?? date('c'),
                        'updated_at' => $attributes['changed'] ?? date('c'),
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
     * Get available crop types from plant assets
     */
    public function getAvailableCropTypes()
    {
        try {
            $token = $this->authenticate();
            if (!$token) {
                return ['lettuce', 'tomato', 'carrot', 'spinach']; // fallback
            }

            // Get plant types from farmOS taxonomy
            $response = $this->client->get('/api/taxonomy_term/plant_type', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/vnd.api+json'
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            $cropTypes = [];

            if (isset($data['data'])) {
                foreach ($data['data'] as $term) {
                    $attributes = $term['attributes'] ?? [];
                    $cropTypes[] = $attributes['name'] ?? 'Unknown';
                }
            }

            // If no crop types from taxonomy, try to get from existing plant assets
            if (empty($cropTypes)) {
                $plants = $this->getPlantAssets();
                if (isset($plants['data'])) {
                    foreach ($plants['data'] as $plant) {
                        $cropType = $this->extractCropType($plant);
                        if ($cropType && !in_array($cropType, $cropTypes)) {
                            $cropTypes[] = $cropType;
                        }
                    }
                }
            }

            // Add some common defaults if still empty
            if (empty($cropTypes)) {
                $cropTypes = ['lettuce', 'tomato', 'carrot', 'cabbage', 'potato', 'herb', 'flower'];
            }

            sort($cropTypes);
            return array_unique($cropTypes);

        } catch (\Exception $e) {
            Log::error('FarmOS crop types fetch failed: ' . $e->getMessage());
            return ['lettuce', 'tomato', 'carrot', 'cabbage', 'potato'];
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
        // For now, generate reasonable test dates
        $baseDate = time();
        
        switch ($type) {
            case 'seeding':
                return date('Y-m-d', $baseDate - (rand(10, 30) * 86400));
            case 'transplanting':
                return date('Y-m-d', $baseDate + (rand(5, 15) * 86400));
            case 'harvest':
                return date('Y-m-d', $baseDate + (rand(30, 90) * 86400));
            default:
                return date('Y-m-d', $baseDate);
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
        return date('Y-m-d', strtotime($harvestStart . ' +14 days'));
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
            
            // Use OAuth2 token if available, otherwise fall back to basic auth
            if ($this->token) {
                $headers['Authorization'] = 'Bearer ' . $this->token;
                $requestOptions['headers'] = $headers;
            } else {
                $requestOptions['auth'] = [$this->username, $this->password];
            }
            
            // Fetch related assets (like plants in this location)
            $requestOptions['query'] = [
                'filter[location.id]' => $assetId,
                'filter[status]' => 'active'
            ];

            $response = $this->client->get('/api/asset/plant', $requestOptions);
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
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch asset details for ' . $assetId . ': ' . $e->getMessage());
            return [
                'related_assets' => [],
                'asset_count' => 0,
                'error' => $e->getMessage()
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
}
