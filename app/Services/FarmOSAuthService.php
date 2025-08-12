<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;
use Exception;

class FarmOSAuthService
{
    private static $instance = null;
    private $client;
    private $baseUrl;
    
    private function __construct()
    {
        $this->baseUrl = Config::get('farmos.url', 'https://farmos.middleworldfarms.org');
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
        ]);
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getAccessToken()
    {
        // Check cache first
        $token = Cache::get('farmos_access_token');
        if ($token) {
            return $token;
        }
        
        // Get new token
        return $this->refreshAccessToken();
    }
    
    public function refreshAccessToken()
    {
        try {
            $response = $this->client->post('/oauth/token', [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => Config::get('farmos.client_id'),
                    'client_secret' => Config::get('farmos.client_secret'),
                    'scope' => Config::get('farmos.oauth_scope', 'farmos_restws_access'),
                ]
            ]);
            
            $data = json_decode($response->getBody(), true);
            $token = $data['access_token'];
            
            // Cache for 50 minutes (tokens expire in 1 hour)
            Cache::put('farmos_access_token', $token, now()->addMinutes(50));
            
            return $token;
        } catch (Exception $e) {
            throw new Exception('Failed to get FarmOS access token: ' . $e->getMessage());
        }
    }
    
    public function isAuthenticated()
    {
        try {
            $token = $this->getAccessToken();
            return !empty($token);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function authenticate()
    {
        try {
            $token = $this->getAccessToken();
            return !empty($token);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getAuthHeaders()
    {
        try {
            $token = $this->getAccessToken();
            return [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ];
        } catch (Exception $e) {
            return [
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ];
        }
    }
}