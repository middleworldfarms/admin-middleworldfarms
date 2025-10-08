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
     * Note: FarmOS uses 'plant_type' vocabulary (not plant_variety) for all 2,959+ varieties
     */
    public function getVarieties()
    {
        return $this->jsonApiPaginatedFetch('/api/taxonomy_term/plant_type');
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
                'query' => ['filter[status]' => 'active']
            ]);

            $data = json_decode($response->getBody(), true);
            $locations = [];
            
            if (isset($data['data'])) {
                foreach ($data['data'] as $asset) {
                    $locations[] = [
                        'id' => $asset['id'],
                        'name' => $asset['attributes']['name'] ?? 'Unnamed Location',
                        'label' => $asset['attributes']['name'] ?? 'Unnamed Location'
                    ];
                }
            }
            
            return $locations;
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch available locations: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get file from FarmOS by file ID
     */
    public function getFileById(string $fileId)
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();
            
            // First, get the file entity to get the actual file URL
            $response = $this->client->get("/api/file/file/{$fileId}", [
                'headers' => $headers
            ]);

            $data = json_decode($response->getBody(), true);
            
            if (!isset($data['data'])) {
                Log::warning('File entity not found', ['file_id' => $fileId]);
                return null;
            }

            $fileData = $data['data'];
            $fileUrl = $fileData['attributes']['uri']['url'] ?? null;
            
            if (!$fileUrl) {
                Log::warning('No file URL in response', ['file_id' => $fileId, 'data' => $fileData]);
                return null;
            }

            // Download the actual image file
            $imageResponse = $this->client->get($fileUrl, [
                'headers' => $headers
            ]);

            return [
                'content' => $imageResponse->getBody()->getContents(),
                'mime_type' => $fileData['attributes']['filemime'] ?? 'image/jpeg',
                'filename' => $fileData['attributes']['filename'] ?? 'variety-image.jpg'
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch file from FarmOS: ' . $e->getMessage(), [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get bed occupancy data for timeline visualization
     * Returns beds and plantings within the specified date range
     */
    public function getBedOccupancy($startDate, $endDate)
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();

            // Fetch all beds (land assets) using pagination
            $bedsData = $this->jsonApiPaginatedFetch('/api/asset/land', ['filter[status]' => 'active']);
            $beds = [];

            foreach ($bedsData as $bed) {
                $bedName = $bed['attributes']['name'] ?? 'Unnamed Bed';

                // Skip beds that are just block names without specific bed numbers
                if (preg_match('/^block\s+\d+$/i', $bedName)) {
                    continue;
                }

                // Try to extract block information from bed name
                $block = 'Block Unknown';
                if (preg_match('/block\s*(\d+)/i', $bedName, $matches)) {
                    $block = 'Block ' . $matches[1];
                } elseif (preg_match('/(\d+)\s*\/\s*\d+/', $bedName, $matches)) {
                    $block = 'Block ' . $matches[1];
                } elseif (preg_match('/^(\d+)/', $bedName, $matches)) {
                    $block = 'Block ' . $matches[1];
                }

                $beds[] = [
                    'id' => $bed['id'],
                    'name' => $bedName,
                    'block' => $block,
                    'status' => $bed['attributes']['status'] ?? 'active',
                    'land_type' => $bed['attributes']['land_type'] ?? 'bed',
                    'geometry' => $bed['attributes']['geometry'] ?? null,
                    'archived' => $bed['attributes']['status'] === 'archived'
                ];
            }

            // Fetch plantings (activities) within date range using pagination
            $plantingsQuery = [
                'filter[status]' => 'active',
                'filter[timestamp][value]' => $startDate,
                'filter[timestamp][operator]' => '>=',
                'include' => 'asset,plant_type'
            ];

            $plantingsData = $this->jsonApiPaginatedFetch('/api/log/activity', $plantingsQuery);
            $plantings = [];

            foreach ($plantingsData as $planting) {
                $attributes = $planting['attributes'] ?? [];

                // Extract bed relationships
                $bedIds = [];
                if (isset($planting['relationships']['asset']['data'])) {
                    $assets = $planting['relationships']['asset']['data'];
                    if (is_array($assets)) {
                        foreach ($assets as $asset) {
                            if (isset($asset['type']) && $asset['type'] === 'asset--land') {
                                $bedIds[] = $asset['id'];
                            }
                        }
                    }
                }

                $plantings[] = [
                    'id' => $planting['id'],
                    'name' => $attributes['name'] ?? 'Unnamed Planting',
                    'status' => $attributes['status'] ?? 'active',
                    'timestamp' => $attributes['timestamp'] ?? null,
                    'start_date' => $attributes['timestamp'] ?? null,
                    'end_date' => $attributes['end_date'] ?? null,
                    'bed_ids' => $bedIds,
                    'crop_type' => $attributes['plant_type'] ?? null,
                    'quantity' => $attributes['quantity'] ?? null,
                    'notes' => $attributes['notes'] ?? null
                ];
            }

            Log::info('Fetched bed occupancy data', [
                'beds_count' => count($beds),
                'plantings_count' => count($plantings),
                'date_range' => [$startDate, $endDate]
            ]);

            return [
                'beds' => $beds,
                'plantings' => $plantings
            ];

        } catch (\Exception $e) {
            Log::error('Failed to fetch bed occupancy data: ' . $e->getMessage(), [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'error' => $e->getMessage()
            ]);

            // Return empty data structure on error
            return [
                'beds' => [],
                'plantings' => []
            ];
        }
    }

    /**
     * Create a planting asset in FarmOS
     * Required for seeding logs - creates the asset that represents the planted crop
     */
    public function createPlantingAsset($data, $locationId = null)
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();

            // Generate planting name
            $plantingName = $data['crop_name'];
            if (isset($data['variety_name']) && $data['variety_name'] !== 'Generic') {
                $plantingName .= ' - ' . $data['variety_name'];
            }
            if (isset($data['succession_number'])) {
                $plantingName .= ' (Succession #' . $data['succession_number'] . ')';
            }

            // Prepare JSON:API payload
            $payload = [
                'data' => [
                    'type' => 'asset--plant',
                    'attributes' => [
                        'name' => $plantingName,
                        'status' => 'active',
                        'notes' => [
                            'value' => $data['notes'] ?? 'Created via AI succession planning',
                            'format' => 'default'
                        ]
                    ]
                ]
            ];

            // Add plant type if available
            if (isset($data['crop_name'])) {
                // Would need to look up taxonomy term ID - for now just add to notes
                $payload['data']['attributes']['notes']['value'] .= "\nCrop: " . $data['crop_name'];
            }

            Log::info('Creating planting asset in FarmOS', [
                'name' => $plantingName,
                'payload' => $payload
            ]);

            $response = $this->client->post('/api/asset/plant', [
                'headers' => $headers,
                'json' => $payload,
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = json_decode($response->getBody(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                $assetId = $responseData['data']['id'] ?? null;
                Log::info('Successfully created planting asset', [
                    'asset_id' => $assetId,
                    'name' => $plantingName
                ]);
                return $assetId;
            } else {
                Log::error('Failed to create planting asset', [
                    'status' => $statusCode,
                    'response' => $responseData
                ]);
                throw new \Exception('Failed to create planting asset: HTTP ' . $statusCode);
            }

        } catch (\Exception $e) {
            Log::error('Exception creating planting asset: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a seeding log in FarmOS
     * Represents the act of seeding/planting seeds
     */
    public function createSeedingLog($logData)
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();

            // Generate log name
            $logName = 'Seeding: ' . $logData['crop_name'];
            if (isset($logData['variety_name']) && $logData['variety_name'] !== 'Generic') {
                $logName .= ' - ' . $logData['variety_name'];
            }

            // Prepare JSON:API payload
            $payload = [
                'data' => [
                    'type' => 'log--seeding',
                    'attributes' => [
                        'name' => $logName,
                        'timestamp' => strtotime($logData['timestamp']),
                        'status' => $logData['status'] ?? 'done',
                        'notes' => [
                            'value' => $logData['notes'] ?? '',
                            'format' => 'default'
                        ]
                    ],
                    'relationships' => []
                ]
            ];

            // Add quantity
            if (isset($logData['quantity'])) {
                $payload['data']['attributes']['quantity'] = [[
                    'measure' => 'count',
                    'value' => $logData['quantity'],
                    'unit' => $logData['quantity_unit'] ?? 'seeds',
                    'label' => ucfirst($logData['quantity_unit'] ?? 'seeds')
                ]];
            }

            // Add asset reference (planting)
            if (isset($logData['planting_id'])) {
                $payload['data']['relationships']['asset'] = [
                    'data' => [[
                        'type' => 'asset--plant',
                        'id' => $logData['planting_id']
                    ]]
                ];
            }

            // Add location reference
            if (isset($logData['location_id'])) {
                $payload['data']['relationships']['location'] = [
                    'data' => [[
                        'type' => 'asset--land',
                        'id' => $logData['location_id']
                    ]]
                ];
            }

            Log::info('Creating seeding log in FarmOS', [
                'name' => $logName,
                'timestamp' => $logData['timestamp']
            ]);

            $response = $this->client->post('/api/log/seeding', [
                'headers' => $headers,
                'json' => $payload,
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = json_decode($response->getBody(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                $logId = $responseData['data']['id'] ?? null;
                Log::info('Successfully created seeding log', [
                    'log_id' => $logId,
                    'name' => $logName
                ]);
                return [
                    'success' => true,
                    'log_id' => $logId,
                    'message' => 'Seeding log created successfully'
                ];
            } else {
                Log::error('Failed to create seeding log', [
                    'status' => $statusCode,
                    'response' => $responseData
                ]);
                return [
                    'success' => false,
                    'error' => 'HTTP ' . $statusCode . ': ' . ($responseData['errors'][0]['detail'] ?? 'Unknown error')
                ];
            }

        } catch (\Exception $e) {
            Log::error('Exception creating seeding log: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create a transplanting log in FarmOS
     * Represents moving plants from one location to another
     */
    public function createTransplantingLog($logData)
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();

            // Generate log name
            $logName = 'Transplanting: ' . $logData['crop_name'];
            if (isset($logData['variety_name']) && $logData['variety_name'] !== 'Generic') {
                $logName .= ' - ' . $logData['variety_name'];
            }

            // Prepare JSON:API payload
            $payload = [
                'data' => [
                    'type' => 'log--transplanting',
                    'attributes' => [
                        'name' => $logName,
                        'timestamp' => strtotime($logData['timestamp']),
                        'status' => $logData['status'] ?? 'done',
                        'is_movement' => $logData['is_movement'] ?? true,
                        'notes' => [
                            'value' => $logData['notes'] ?? '',
                            'format' => 'default'
                        ]
                    ],
                    'relationships' => []
                ]
            ];

            // Add quantity
            if (isset($logData['quantity'])) {
                $payload['data']['attributes']['quantity'] = [[
                    'measure' => 'count',
                    'value' => $logData['quantity'],
                    'unit' => $logData['quantity_unit'] ?? 'plants',
                    'label' => ucfirst($logData['quantity_unit'] ?? 'plants')
                ]];
            }

            // Add asset reference (planting)
            if (isset($logData['planting_id'])) {
                $payload['data']['relationships']['asset'] = [
                    'data' => [[
                        'type' => 'asset--plant',
                        'id' => $logData['planting_id']
                    ]]
                ];
            }

            // Add destination location reference (where plants are moved TO)
            if (isset($logData['destination_location_id'])) {
                $payload['data']['relationships']['location'] = [
                    'data' => [[
                        'type' => 'asset--land',
                        'id' => $logData['destination_location_id']
                    ]]
                ];
            }

            Log::info('Creating transplanting log in FarmOS', [
                'name' => $logName,
                'timestamp' => $logData['timestamp'],
                'destination' => $logData['destination_location_id'] ?? null
            ]);

            $response = $this->client->post('/api/log/transplanting', [
                'headers' => $headers,
                'json' => $payload,
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = json_decode($response->getBody(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                $logId = $responseData['data']['id'] ?? null;
                Log::info('Successfully created transplanting log', [
                    'log_id' => $logId,
                    'name' => $logName
                ]);
                return [
                    'success' => true,
                    'log_id' => $logId,
                    'message' => 'Transplanting log created successfully'
                ];
            } else {
                Log::error('Failed to create transplanting log', [
                    'status' => $statusCode,
                    'response' => $responseData
                ]);
                return [
                    'success' => false,
                    'error' => 'HTTP ' . $statusCode . ': ' . ($responseData['errors'][0]['detail'] ?? 'Unknown error')
                ];
            }

        } catch (\Exception $e) {
            Log::error('Exception creating transplanting log: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create a harvest log in FarmOS
     * Represents harvesting produce from plantings
     */
    public function createHarvestLog($logData)
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();

            // Generate log name
            $logName = 'Harvest: ' . $logData['crop_name'];
            if (isset($logData['variety_name']) && $logData['variety_name'] !== 'Generic') {
                $logName .= ' - ' . $logData['variety_name'];
            }

            // Prepare JSON:API payload
            $payload = [
                'data' => [
                    'type' => 'log--harvest',
                    'attributes' => [
                        'name' => $logName,
                        'timestamp' => strtotime($logData['timestamp']),
                        'status' => $logData['status'] ?? 'done',
                        'notes' => [
                            'value' => $logData['notes'] ?? '',
                            'format' => 'default'
                        ]
                    ],
                    'relationships' => []
                ]
            ];

            // Add quantity (harvest uses 'weight' measure typically)
            if (isset($logData['quantity'])) {
                $payload['data']['attributes']['quantity'] = [[
                    'measure' => 'weight',
                    'value' => $logData['quantity'],
                    'unit' => $logData['quantity_unit'] ?? 'kg',
                    'label' => ucfirst($logData['quantity_unit'] ?? 'kg')
                ]];
            }

            // Add asset reference (planting being harvested)
            if (isset($logData['planting_id'])) {
                $payload['data']['relationships']['asset'] = [
                    'data' => [[
                        'type' => 'asset--plant',
                        'id' => $logData['planting_id']
                    ]]
                ];
            }

            // Add location reference
            if (isset($logData['location_id'])) {
                $payload['data']['relationships']['location'] = [
                    'data' => [[
                        'type' => 'asset--land',
                        'id' => $logData['location_id']
                    ]]
                ];
            }

            Log::info('Creating harvest log in FarmOS', [
                'name' => $logName,
                'timestamp' => $logData['timestamp']
            ]);

            $response = $this->client->post('/api/log/harvest', [
                'headers' => $headers,
                'json' => $payload,
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = json_decode($response->getBody(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                $logId = $responseData['data']['id'] ?? null;
                Log::info('Successfully created harvest log', [
                    'log_id' => $logId,
                    'name' => $logName
                ]);
                return [
                    'success' => true,
                    'log_id' => $logId,
                    'message' => 'Harvest log created successfully'
                ];
            } else {
                Log::error('Failed to create harvest log', [
                    'status' => $statusCode,
                    'response' => $responseData
                ]);
                return [
                    'success' => false,
                    'error' => 'HTTP ' . $statusCode . ': ' . ($responseData['errors'][0]['detail'] ?? 'Unknown error')
                ];
            }

        } catch (\Exception $e) {
            Log::error('Exception creating harvest log: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Update a plant type taxonomy term in FarmOS
     * Used for pushing local changes back to FarmOS (DEV MODE)
     */
    public function updatePlantTypeTerm(string $termId, array $updateData)
    {
        try {
            $this->authenticate();
            $headers = $this->getAuthHeaders();

            // Build JSON:API PATCH payload
            $payload = [
                'data' => [
                    'type' => 'taxonomy_term--plant_type',
                    'id' => $termId
                ]
            ];

            // Add attributes if provided
            if (isset($updateData['attributes']) && !empty($updateData['attributes'])) {
                $payload['data']['attributes'] = $updateData['attributes'];
            }

            // Add relationships if provided
            if (isset($updateData['relationships']) && !empty($updateData['relationships'])) {
                $payload['data']['relationships'] = $updateData['relationships'];
            }

            Log::info('Updating FarmOS plant type term', [
                'term_id' => $termId,
                'attributes' => $updateData['attributes'] ?? []
            ]);

            $response = $this->client->patch("/api/taxonomy_term/plant_type/{$termId}", [
                'headers' => $headers,
                'json' => $payload,
                'http_errors' => false
            ]);

            $statusCode = $response->getStatusCode();
            $responseData = json_decode($response->getBody(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                Log::info('Successfully updated FarmOS plant type term', [
                    'term_id' => $termId,
                    'status' => $statusCode
                ]);
                return [
                    'success' => true,
                    'status' => $statusCode,
                    'data' => $responseData
                ];
            } else {
                Log::error('Failed to update FarmOS plant type term', [
                    'term_id' => $termId,
                    'status' => $statusCode,
                    'response' => $responseData
                ]);
                return [
                    'success' => false,
                    'status' => $statusCode,
                    'error' => 'HTTP ' . $statusCode . ': ' . ($responseData['errors'][0]['detail'] ?? 'Unknown error'),
                    'body' => $responseData
                ];
            }

        } catch (\Exception $e) {
            Log::error('Exception updating FarmOS plant type term: ' . $e->getMessage(), [
                'term_id' => $termId
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
