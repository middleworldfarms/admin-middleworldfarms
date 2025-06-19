<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WpApiService
{
    protected $apiUrl;
    protected $apiKey;
    protected $apiSecret;
    protected $wcApiUrl;
    protected $wcConsumerKey;
    protected $wcConsumerSecret;
    protected $integrationKey;

    public function __construct()
    {
        $this->apiUrl = config('services.wp_api.url');
        $this->apiKey = config('services.wp_api.key');
        $this->apiSecret = config('services.wp_api.secret');
        // WooCommerce API and Integration key
        $this->wcApiUrl        = config('services.wc_api.url');
        $this->wcConsumerKey   = config('services.wc_api.consumer_key');
        $this->wcConsumerSecret= config('services.wc_api.consumer_secret');
        $this->integrationKey  = config('services.wc_api.integration_key');
    }

    /**
     * Test API connection
     */
    public function testConnection()
    {
        try {
            // Test WooCommerce API connection first
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->get("{$this->apiUrl}/wp-json/wc/v3/system_status");
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true, 
                    'woocommerce' => true,
                    'version' => $data['environment']['version'] ?? 'unknown'
                ];
            }
            
            // Fallback to basic wp-json test
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->get("{$this->apiUrl}/wp-json");
                
            return [
                'success' => $response->successful(),
                'woocommerce' => false,
                'status_code' => $response->status()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false, 
                'message' => $e->getMessage(),
                'woocommerce' => false
            ];
        }
    }

    /**
     * Search users via MWF integration plugin
     */
    public function searchUsers($query, $limit = 20)
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->get("{$this->apiUrl}/wp-json/mwf/v1/users/search", ['query' => $query, 'limit' => $limit]);
            return collect($response->json());
        } catch (Exception $e) {
            Log::error('User search failed: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get user details by ID via API
     */
    public function getUserById($userId)
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->get("{$this->apiUrl}/wp-json/mwf/v1/users/{$userId}");
            return $response->json();
        } catch (Exception $e) {
            Log::error('Get user failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get recent users via API
     */
    public function getRecentUsers($limit = 10, $role = null)
    {
        try {
            $params = ['limit' => $limit];
            if ($role) {
                $params['role'] = $role;
            }

            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->get("{$this->apiUrl}/wp-json/mwf/v1/users/recent", $params);
            return collect($response->json());
        } catch (Exception $e) {
            Log::error('Get recent users failed: ' . $e->getMessage());
            return collect();
        }
    }

    /**
     * Get user funds balance via API
     */
    public function getUserFunds($email)
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->get("{$this->apiUrl}/wp-json/mwf/v1/funds", ['email' => $email]);
            $data = $response->json();
            return $data['balance'] ?? 0;
        } catch (Exception $e) {
            Log::error('Get user funds failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Generate user switch URL via API
     */
    public function generateUserSwitchUrl($userId, $redirectTo = '/my-account/')
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->post("{$this->apiUrl}/wp-json/mwf/v1/users/switch", [
                    'id' => $userId,
                    'redirect_to' => $redirectTo
                ]);

            $data = $response->json();
            return $data['switch_url'] ?? null;
        } catch (Exception $e) {
            Log::error('Generate switch URL failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Add funds to user account via Self-Serve Shop endpoint
     */
    public function addUserFunds($email, $amount)
    {
        try {
            $response = Http::post("{$this->wcApiUrl}/wp-json/mwf/v1/funds/add", [
                'email'           => $email,
                'amount'          => $amount,
                'integration_key' => $this->integrationKey,
            ]);
            return $response->json();
        } catch (Exception $e) {
            Log::error('Add user funds failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create a new WooCommerce order via API
     */
    public function createOrder(array $orderData)
    {
        try {
            $response = Http::withBasicAuth($this->wcConsumerKey, $this->wcConsumerSecret)
                ->post("{$this->wcApiUrl}/wp-json/wc/v3/orders", $orderData);
            return $response->json();
        } catch (Exception $e) {
            Log::error('Create order failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get subscription payment status via MWF plugin
     */
    public function getSubscriptionPaymentStatus($subscriptionId)
    {
        try {
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->get("{$this->apiUrl}/wp-json/mwf/v1/subscription-payment-status", ['subscription_id' => $subscriptionId]);
            return $response->json();
        } catch (Exception $e) {
            Log::error('Get subscription payment status failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Process a subscription payment via MWF plugin
     */
    public function processSubscriptionPayment($subscriptionId, array $params)
    {
        try {
            $payload = array_merge($params, ['subscription_id' => $subscriptionId]);
            $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
                ->post("{$this->apiUrl}/wp-json/mwf/v1/subscription-payment/process", $payload);
            return $response->json();
        } catch (Exception $e) {
            Log::error('Process subscription payment failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get delivery schedule data via WooCommerce subscriptions API
     */
    public function getDeliveryScheduleData($limit = 100)
    {
        try {
            // WooCommerce API has a maximum per_page limit of 100
            $perPage = min($limit, 100);
            
            // Fetch active subscriptions via WooCommerce REST
            $response = Http::withBasicAuth($this->wcConsumerKey, $this->wcConsumerSecret)
                ->get("{$this->wcApiUrl}/wp-json/wc/v3/subscriptions", [
                    'per_page' => $perPage,
                    'orderby'  => 'date',
                    'order'    => 'desc',
                ]);
             
            $data = $response->json();
            
            // Return as array if successful, otherwise empty array
            if ($response->successful() && is_array($data)) {
                return $data;
            }
            
            return [];
        } catch (Exception $e) {
            Log::error('Get delivery schedule failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user meta information from WordPress
     * 
     * @param int $userId The WordPress user ID
     * @param string $metaKey Optional specific meta key to retrieve
     * @return array|string|null The meta value(s)
     */
    public function getUserMeta($userId, $metaKey = null)
    {
        try {
            $params = ['user_id' => $userId];
            
            // Add specific meta key if provided
            if ($metaKey) {
                $params['meta_key'] = $metaKey;
            }
            
            $response = Http::withHeaders(['X-WC-API-Key' => $this->integrationKey])
                ->get("{$this->apiUrl}/wp-json/mwf/v1/user-meta", $params);
                
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['success']) && $data['success']) {
                    if ($metaKey) {
                        return $data['meta_value'] ?? null;
                    }
                    return $data['meta'] ?? [];
                }
            }
            
            // Fallback to WP REST API for user meta
            $response = Http::withBasicAuth($this->wcConsumerKey, $this->wcConsumerSecret)
                ->get("{$this->apiUrl}/wp-json/wp/v2/users/{$userId}");
                
            if ($response->successful()) {
                $userData = $response->json();
                
                // For specific meta keys like preferred_collection_day
                if ($metaKey === 'preferred_collection_day') {
                    return $userData['meta']['preferred_collection_day'][0] ?? 'Friday'; // Default to Friday
                }
                
                // Return all meta if available
                return $userData['meta'] ?? [];
            }
            
            return null;
        } catch (Exception $e) {
            Log::error('Get user meta failed: ' . $e->getMessage());
            return null;
        }
    }
}
