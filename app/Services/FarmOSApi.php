<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;
use App\Services\FarmOSAuthService;

/**
 * FarmOS API Service (New Version)
 * Integrates with FarmOS using centralized authentication patterns
 */
class FarmOSApi
{
    private $client;
    private $baseUrl;
    private $token;

    public function __construct()
    {
        $this->baseUrl = Config::get('farmos.url', 'https://farmos.middleworldfarms.org');
        
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
     * Authenticate with FarmOS using centralized auth service
     */
    public function authenticate()
    {
        try {
            $authService = FarmOSAuthService::getInstance();
            $token = $authService->getAccessToken();
            if ($token) {
                $this->token = $token;
                Log::info('FarmOS OAuth2 authentication successful (centralized)');
                return true;
            }
            
            throw new \Exception('Failed to get OAuth2 token from auth service');
        } catch (\Exception $e) {
            Log::error('FarmOS authentication failed: ' . $e->getMessage());
            throw new \Exception('FarmOS authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Get authentication headers using centralized auth service
     */
    public function getAuthHeaders()
    {
        try {
            $authService = FarmOSAuthService::getInstance();
            return $authService->getAuthHeaders();
        } catch (\Exception $e) {
            Log::warning('Failed to get auth headers: ' . $e->getMessage());
            return ['Accept' => 'application/vnd.api+json'];
        }
    }

    /**
     * Check if authenticated using centralized auth service
     */
    public function isAuthenticated()
    {
        try {
            $authService = FarmOSAuthService::getInstance();
            return $authService->isAuthenticated();
        } catch (\Exception $e) {
            return false;
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

            // Get plant types from farmOS taxonomy using centralized auth
            $headers = $this->getAuthHeaders();
            $response = $this->client->get('/api/taxonomy_term/plant_type', [
                'headers' => $headers
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

            // Get crop varieties using pagination
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
                            'parent_id' => $parent,
                            'crop_type' => $parent  // Add crop_type field for frontend compatibility
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Could not fetch crop varieties: ' . $e->getMessage());
            }

            // Add fallback if no types found
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

            // Sort alphabetically
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
     * Get varieties with proper pagination
     */
    public function getVarieties()
    {
        return $this->jsonApiPaginatedFetch('/api/taxonomy_term/plant_variety');
    }

    /**
     * Get plant types with proper pagination
     */
    public function getPlantTypes()
    {
        return $this->jsonApiPaginatedFetch('/api/taxonomy_term/plant_type');
    }

    /**
     * Generic JSON:API GET with pagination
     */
    private function jsonApiPaginatedFetch($path, $params = [], $maxPages = 200, $pageSize = 50)
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
                // Clear auth cache and retry once
                Cache::forget('farmos_access_token');
                $this->authenticate();
                $retried = true;
                continue;
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
            $retried = false;
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

    /**
     * Make JSON:API request with centralized auth
     */
    private function jsonApiRequest($path, $query = [])
    {
        $this->authenticate();
        $headers = $this->getAuthHeaders();
        $options = ['headers' => $headers, 'http_errors' => false];
        
        if (!empty($query)) {
            $options['query'] = $query;
        }
        
        $response = $this->client->get($path, $options);
        $status = $response->getStatusCode();
        $body = json_decode($response->getBody(), true);
        
        return ['status' => $status, 'body' => $body];
    }

    /**
     * Get geometry assets (land/fields) for mapping
     */
    public function getGeometryAssets($options = [])
    {
        try {
            $cacheKey = 'farmos.geometry.assets.v1';
            $forceRefresh = $options['refresh'] ?? false;
            
            if (!$forceRefresh) {
                $cached = Cache::get($cacheKey);
                if ($cached) {
                    Log::info('FarmOS geometry assets cache hit', ['feature_count' => count($cached['features'])]);
                    return $cached;
                }
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
            
            if (!isset($result['error'])) {
                Cache::put($cacheKey, $result, now()->addMinutes(10));
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Failed to load geometry assets: ' . $e->getMessage());
            return [
                'type' => 'FeatureCollection',
                'features' => [],
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Internal geometry assets fetch
     */
    private function fetchGeometryAssetsInternal()
    {
        try {
            $headers = $this->getAuthHeaders();
            $requestOptions = ['headers' => $headers, 'http_errors' => false];
            $requestOptions['query'] = ['filter[status]' => 'active'];
            
            $response = $this->client->get('/api/asset/land', $requestOptions);
            $status = $response->getStatusCode();
            
            if ($status === 401 || $status === 403) {
                return [
                    'type' => 'FeatureCollection', 
                    'features' => [], 
                    'error' => 'Unauthorized'
                ];
            }
            
            $data = json_decode($response->getBody(), true);
            $features = [];
            
            if (isset($data['data']) && is_array($data['data'])) {
                foreach ($data['data'] as $asset) {
                    if (isset($asset['attributes']['geometry'])) {
                        $geometry = $this->convertWktToGeoJson($asset['attributes']['geometry']);
                        if ($geometry) {
                            $features[] = [
                                'type' => 'Feature',
                                'properties' => [
                                    'name' => $asset['attributes']['name'] ?? 'Unnamed Area',
                                    'id' => $asset['id'],
                                    'status' => $asset['attributes']['status'] ?? 'unknown',
                                    'land_type' => $asset['attributes']['land_type'] ?? 'field',
                                ],
                                'geometry' => $geometry
                            ];
                        }
                    }
                }
            }
            
            return ['type' => 'FeatureCollection', 'features' => $features];
            
        } catch (\Exception $e) {
            return [
                'type' => 'FeatureCollection', 
                'features' => [], 
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Simple WKT to GeoJSON conversion
     */
    private function convertWktToGeoJson($geometryData)
    {
        if (!isset($geometryData['value']) || !isset($geometryData['geo_type'])) {
            return null;
        }
        
        $wkt = $geometryData['value'];
        $geoType = $geometryData['geo_type'];
        
        if (strtoupper($geoType) === 'POLYGON') {
            return $this->parsePolygonWkt($wkt);
        }
        
        return null;
    }

    /**
     * Parse POLYGON WKT to GeoJSON
     */
    private function parsePolygonWkt($wkt)
    {
        $wkt = trim($wkt);
        if (preg_match('/^POLYGON\s*\(\((.*)\)\)$/i', $wkt, $matches)) {
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
        $pairs = explode(',', $coordinateString);
        
        foreach ($pairs as $pair) {
            $coords = preg_split('/\s+/', trim($pair));
            if (count($coords) >= 2) {
                $coordinates[] = [(float)$coords[0], (float)$coords[1]];
            }
        }
        
        return $coordinates;
    }

    /**
     * Get crop planning data
     */
    public function getCropPlanningData()
    {
        try {
            if (!$this->authenticate()) {
                return [];
            }

            // Simple implementation - get plant assets
            $headers = $this->getAuthHeaders();
            $response = $this->client->get('/api/asset/plant', [
                'headers' => $headers
            ]);

            $data = json_decode($response->getBody(), true);
            $cropPlans = [];

            if (isset($data['data'])) {
                foreach ($data['data'] as $plant) {
                    $attributes = $plant['attributes'] ?? [];
                    
                    $cropPlans[] = [
                        'farmos_asset_id' => $plant['id'],
                        'crop_type' => 'vegetable',
                        'variety' => $attributes['name'] ?? '',
                        'status' => $attributes['status'] ?? 'active',
                        'created_at' => $attributes['created'] ?? date('c'),
                        'updated_at' => $attributes['changed'] ?? date('c'),
                    ];
                }
            }

            return $cropPlans;
            
        } catch (\Exception $e) {
            Log::error('FarmOS crop planning data fetch failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create crop plan in farmOS
     */
    public function createCropPlan($planData)
    {
        $this->authenticate();
        
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
                ]
            ]
        ];

        try {
            $headers = $this->getAuthHeaders();
            $response = $this->client->post('/api/plan/crop', [
                'headers' => $headers,
                'json' => $data
            ]);

            $result = json_decode($response->getBody(), true);
            Log::info('Created farmOS crop plan', [
                'crop' => $planData['crop']['name'],
                'type' => $planData['type'],
                'plan_id' => $result['data']['id'] ?? 'unknown'
            ]);

            return $result;
            
        } catch (\Exception $e) {
            Log::error('Failed to create farmOS crop plan: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get harvest logs from farmOS
     */
    public function getHarvestLogs($since = null)
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();
            
            $query = ['filter[status]' => 'done'];
            if ($since) {
                $query['filter[timestamp][value]'] = $since;
                $query['filter[timestamp][operator]'] = '>=';
            }
            
            $response = $this->client->get('/api/log/harvest', [
                'headers' => $headers,
                'query' => $query
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['data'] ?? [];
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch harvest logs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get available locations from farmOS
     */
    public function getAvailableLocations()
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();
            
            $response = $this->client->get('/api/asset/land', [
                'headers' => $headers,
                'query' => [
                    'filter[status]' => 'active',
                    'sort' => 'name'
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            $locations = [];
            
            foreach ($data['data'] ?? [] as $location) {
                $locations[] = [
                    'id' => $location['id'],
                    'name' => $location['attributes']['name'] ?? 'Unknown',
                    'type' => 'land'
                ];
            }
            
            return $locations;
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch available locations: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a seeding log in farmOS
     */
    public function createSeedingLog($seedingData)
    {
        $this->authenticate();

        $data = [
            'data' => [
                'type' => 'log--seeding',
                'attributes' => [
                    'name' => 'Seeding: ' . ($seedingData['crop_name'] ?? 'Unknown Crop'),
                    'timestamp' => $seedingData['timestamp'] ?? now()->toISOString(),
                    'status' => 'done',
                    'notes' => [
                        'value' => $seedingData['notes'] ?? '',
                        'format' => 'default'
                    ]
                ],
                'relationships' => []
            ]
        ];

        // Add location relationship if provided
        if (isset($seedingData['location_id'])) {
            $data['data']['relationships']['location'] = [
                'data' => [
                    'type' => 'asset--land',
                    'id' => $seedingData['location_id']
                ]
            ];
        }

        // Add crop/planting relationship if provided
        if (isset($seedingData['planting_id'])) {
            $data['data']['relationships']['planting'] = [
                'data' => [
                    'type' => 'asset--planting',
                    'id' => $seedingData['planting_id']
                ]
            ];
        }

        // Add quantity data
        if (isset($seedingData['quantity'])) {
            $data['data']['attributes']['quantity'] = [
                'measure' => $seedingData['quantity_unit'] ?? 'count',
                'value' => $seedingData['quantity']
            ];
        }

        try {
            $headers = $this->getAuthHeaders();
            $response = $this->client->post('/api/log/seeding', [
                'headers' => $headers,
                'json' => $data
            ]);

            $result = json_decode($response->getBody(), true);
            Log::info('Created farmOS seeding log', [
                'crop' => $seedingData['crop_name'] ?? 'Unknown',
                'location' => $seedingData['location_id'] ?? 'Unknown',
                'log_id' => $result['data']['id'] ?? 'unknown'
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Failed to create farmOS seeding log: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a transplanting log in farmOS
     */
    public function createTransplantingLog($transplantingData)
    {
        $this->authenticate();

        $data = [
            'data' => [
                'type' => 'log--transplanting',
                'attributes' => [
                    'name' => 'Transplanting: ' . ($transplantingData['crop_name'] ?? 'Unknown Crop'),
                    'timestamp' => $transplantingData['timestamp'] ?? now()->toISOString(),
                    'status' => 'done',
                    'notes' => [
                        'value' => $transplantingData['notes'] ?? '',
                        'format' => 'default'
                    ]
                ],
                'relationships' => []
            ]
        ];

        // Add source location relationship
        if (isset($transplantingData['source_location_id'])) {
            $data['data']['relationships']['location'] = [
                'data' => [
                    'type' => 'asset--land',
                    'id' => $transplantingData['source_location_id']
                ]
            ];
        }

        // Add destination location relationship
        if (isset($transplantingData['destination_location_id'])) {
            $data['data']['relationships']['location_to'] = [
                'data' => [
                    'type' => 'asset--land',
                    'id' => $transplantingData['destination_location_id']
                ]
            ];
        }

        // Add planting relationship
        if (isset($transplantingData['planting_id'])) {
            $data['data']['relationships']['planting'] = [
                'data' => [
                    'type' => 'asset--planting',
                    'id' => $transplantingData['planting_id']
                ]
            ];
        }

        // Add quantity data
        if (isset($transplantingData['quantity'])) {
            $data['data']['attributes']['quantity'] = [
                'measure' => $transplantingData['quantity_unit'] ?? 'count',
                'value' => $transplantingData['quantity']
            ];
        }

        try {
            $headers = $this->getAuthHeaders();
            $response = $this->client->post('/api/log/transplanting', [
                'headers' => $headers,
                'json' => $data
            ]);

            $result = json_decode($response->getBody(), true);
            Log::info('Created farmOS transplanting log', [
                'crop' => $transplantingData['crop_name'] ?? 'Unknown',
                'from' => $transplantingData['source_location_id'] ?? 'Unknown',
                'to' => $transplantingData['destination_location_id'] ?? 'Unknown',
                'log_id' => $result['data']['id'] ?? 'unknown'
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Failed to create farmOS transplanting log: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a harvest log in farmOS
     */
    public function createHarvestLog($harvestData)
    {
        $this->authenticate();

        $data = [
            'data' => [
                'type' => 'log--harvest',
                'attributes' => [
                    'name' => 'Harvest: ' . ($harvestData['crop_name'] ?? 'Unknown Crop'),
                    'timestamp' => $harvestData['timestamp'] ?? now()->toISOString(),
                    'status' => 'done',
                    'notes' => [
                        'value' => $harvestData['notes'] ?? '',
                        'format' => 'default'
                    ]
                ],
                'relationships' => []
            ]
        ];

        // Add location relationship
        if (isset($harvestData['location_id'])) {
            $data['data']['relationships']['location'] = [
                'data' => [
                    'type' => 'asset--land',
                    'id' => $harvestData['location_id']
                ]
            ];
        }

        // Add planting relationship
        if (isset($harvestData['planting_id'])) {
            $data['data']['relationships']['planting'] = [
                'data' => [
                    'type' => 'asset--planting',
                    'id' => $harvestData['planting_id']
                ]
            ];
        }

        // Add quantity data
        if (isset($harvestData['quantity'])) {
            $data['data']['attributes']['quantity'] = [
                'measure' => $harvestData['quantity_unit'] ?? 'weight',
                'value' => $harvestData['quantity']
            ];
        }

        // Add quality data if provided
        if (isset($harvestData['quality'])) {
            $data['data']['attributes']['quality'] = $harvestData['quality'];
        }

        try {
            $headers = $this->getAuthHeaders();
            $response = $this->client->post('/api/log/harvest', [
                'headers' => $headers,
                'json' => $data
            ]);

            $result = json_decode($response->getBody(), true);
            Log::info('Created farmOS harvest log', [
                'crop' => $harvestData['crop_name'] ?? 'Unknown',
                'location' => $harvestData['location_id'] ?? 'Unknown',
                'quantity' => $harvestData['quantity'] ?? 'Unknown',
                'log_id' => $result['data']['id'] ?? 'unknown'
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Failed to create farmOS harvest log: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a specific variety by ID from farmOS
     */
    public function getVarietyById($varietyId)
    {
        try {
            $this->authenticate();
            
            $headers = $this->getAuthHeaders();
            $response = $this->client->get("/api/taxonomy_term/plant_variety/{$varietyId}", [
                'headers' => $headers
            ]);

            $data = json_decode($response->getBody(), true);
            
            if (isset($data['data'])) {
                $attributes = $data['data']['attributes'] ?? [];
                $relationships = $data['data']['relationships'] ?? [];
                
                return [
                    'id' => $data['data']['id'],
                    'name' => $attributes['name'] ?? 'Unknown',
                    'description' => $attributes['description']['value'] ?? '',
                    'harvest_start' => $attributes['harvest_start'] ?? null,
                    'harvest_end' => $attributes['harvest_end'] ?? null,
                    'days_to_maturity' => $attributes['maturity_days'] ?? null,
                    'parent_id' => $relationships['parent']['data'][0]['id'] ?? null,
                    'crop_family' => $relationships['crop_family']['data']['attributes']['name'] ?? null,
                    'frost_tolerance' => $attributes['frost_tolerance'] ?? null,
                    'spacing_in_row' => $attributes['spacing_in_row'] ?? null,
                    'spacing_between_rows' => $attributes['spacing_between_rows'] ?? null
                ];
            }
            
            return null;

        } catch (\Exception $e) {
            Log::error('Failed to get variety by ID from farmOS: ' . $e->getMessage());
            return null;
        }
    }
}
