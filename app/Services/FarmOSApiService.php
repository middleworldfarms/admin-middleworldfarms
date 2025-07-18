<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
        $this->baseUrl = config('farmos.url', 'https://farmos.middleworldfarms.org');
        $this->username = config('farmos.username');
        $this->password = config('farmos.password');
        
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
     * Authenticate with FarmOS and get access token
     */
    public function authenticate()
    {
        if ($this->token && $this->isTokenValid()) {
            return $this->token;
        }

        try {
            $response = $this->client->post('/oauth/token', [
                'json' => [
                    'grant_type' => 'password',
                    'username' => $this->username,
                    'password' => $this->password,
                    'client_id' => config('farmos.client_id'),
                    'client_secret' => config('farmos.client_secret'),
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            $this->token = $data['access_token'];
            
            // Cache token for its lifetime
            Cache::put('farmos_token', $this->token, $data['expires_in'] - 60);
            
            return $this->token;
        } catch (\Exception $e) {
            Log::error('FarmOS authentication failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get harvest logs from FarmOS
     */
    public function getHarvestLogs($since = null)
    {
        $this->authenticate();
        
        $url = '/api/log/harvest';
        $params = [
            'filter[status]' => 'done',
            'include' => 'asset,quantity,quantity.units',
            'sort' => '-timestamp'
        ];

        if ($since) {
            $params['filter[timestamp][value]'] = $since;
            $params['filter[timestamp][operator]'] = '>=';
        }

        try {
            $response = $this->client->get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ],
                'query' => $params
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Failed to fetch harvest logs: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get plant assets (crops) from FarmOS
     */
    public function getPlantAssets()
    {
        $this->authenticate();
        
        try {
            $response = $this->client->get('/api/asset/plant', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ],
                'query' => [
                    'include' => 'plant_type,season',
                    'filter[status]' => 'active'
                ]
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Failed to fetch plant assets: ' . $e->getMessage());
            throw $e;
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
     * Check if the current token is still valid
     */
    private function isTokenValid()
    {
        return Cache::has('farmos_token');
    }
}
