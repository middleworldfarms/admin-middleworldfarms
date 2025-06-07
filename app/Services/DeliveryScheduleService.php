<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeliveryScheduleService
{
    private $baseUrl;
    private $apiKey;

    public function __construct()
    {
        $this->baseUrl = env('WOOCOMMERCE_URL');
        $this->apiKey = env('MWF_API_KEY');
    }

    public function getSchedule($startDate = null, $endDate = null, $status = 'active')
    {
        try {
            $params = [];

            if ($startDate) {
                $params['start_date'] = $startDate;
            }
            if ($endDate) {
                $params['end_date'] = $endDate;
            }
            if ($status) {
                $params['status'] = $status;
            }

            $response = Http::timeout(30)
                ->withHeaders([
                    'X-MWF-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->get($this->baseUrl . '/wp-json/mwf-delivery-schedule/v1/schedule', $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to fetch delivery schedule', [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Exception in DeliveryScheduleService::getSchedule', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    public function fetchSchedule()
    {
        return $this->getSchedule();
    }

    public function testConnection()
    {
        try {
            // Test the actual working WooCommerce API endpoint that provides the data
            $consumerKey = env('WOOCOMMERCE_CONSUMER_KEY');
            $consumerSecret = env('WOOCOMMERCE_CONSUMER_SECRET');
            
            $response = Http::timeout(10)
                ->withBasicAuth($consumerKey, $consumerSecret)
                ->get($this->baseUrl . '/wp-json/wc/v3/subscriptions', [
                    'per_page' => 1,
                    'status' => 'active'
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'WooCommerce API connected'
                ];
            }

            return [
                'success' => false,
                'status' => $response->status(),
                'body' => $response->body()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function testAuth()
    {
        try {
            // Test authentication with actual API key
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-MWF-API-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->get($this->baseUrl . '/wp-json/mwf-delivery-schedule/v1/schedule', [
                    'start_date' => date('Y-m-d'),
                    'end_date' => date('Y-m-d')
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Authentication successful'
                ];
            }

            return [
                'success' => false,
                'status' => $response->status(),
                'body' => $response->body()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculate if the current week is Week A or Week B for a specific customer
     * This ensures fortnightly customers are split between Week A and Week B
     * Based on Thursday-to-Wednesday delivery weeks (Thursday = start of delivery week)
     * 
     * @param string $subscriptionId Subscription ID to determine A/B assignment
     * @param string $targetDate Date to check (defaults to current date)
     * @return string 'A' or 'B'
     */
    public function calculateWeekAB($subscriptionId, $targetDate = null)
    {
        if (!$targetDate) {
            $targetDate = date('Y-m-d');
        }

        // Get the Thursday of the current delivery week
        // If today is Thu/Fri/Sat/Sun/Mon/Tue/Wed, find the most recent Thursday
        $targetTimestamp = strtotime($targetDate);
        $dayOfWeek = date('N', $targetTimestamp); // 1=Monday, 4=Thursday, 7=Sunday
        
        // Calculate days to subtract to get to Thursday
        if ($dayOfWeek >= 4) {
            // If Thu/Fri/Sat/Sun, go back to this week's Thursday
            $daysBack = $dayOfWeek - 4;
        } else {
            // If Mon/Tue/Wed, go back to last week's Thursday
            $daysBack = $dayOfWeek + 3;
        }
        
        $deliveryWeekThursday = date('Y-m-d', strtotime($targetDate . " -$daysBack days"));
        
        // Get week number based on Thursday (delivery week start)
        $deliveryWeekNumber = (int) date('W', strtotime($deliveryWeekThursday));
        
        // Use subscription ID to determine if customer is in A or B group
        // Swap groups since payment is taken in advance
        // Even subscription IDs = Group B, Odd subscription IDs = Group A
        $subscriptionGroup = (intval($subscriptionId) % 2 === 0) ? 'B' : 'A';
        
        return $subscriptionGroup;
    }

    /**
     * Check if a delivery/collection should happen this week based on frequency
     * 
     * @param string $frequency 'weekly' or 'fortnightly' 
     * @param string $subscriptionId Subscription ID for A/B group assignment
     * @param string $targetDate Date to check
     * @return bool
     */
    public function shouldDeliverThisWeek($frequency, $subscriptionId, $targetDate = null)
    {
        if (strtolower($frequency) === 'weekly') {
            return true; // Weekly deliveries happen every week
        }

        if (strtolower($frequency) === 'fortnightly') {
            if (!$targetDate) {
                $targetDate = date('Y-m-d');
            }
            
            // Get the Thursday of the current delivery week
            $targetTimestamp = strtotime($targetDate);
            $dayOfWeek = date('N', $targetTimestamp); // 1=Monday, 4=Thursday, 7=Sunday
            
            // Calculate days to subtract to get to Thursday
            if ($dayOfWeek >= 4) {
                // If Thu/Fri/Sat/Sun, go back to this week's Thursday
                $daysBack = $dayOfWeek - 4;
            } else {
                // If Mon/Tue/Wed, go back to last week's Thursday
                $daysBack = $dayOfWeek + 3;
            }
            
            $deliveryWeekThursday = date('Y-m-d', strtotime($targetDate . " -$daysBack days"));
            
            // Get delivery week number based on Thursday
            $deliveryWeekNumber = (int) date('W', strtotime($deliveryWeekThursday));
            
            // Use subscription ID to determine if customer is in A or B group
            // Swap groups since payment is taken in advance
            // Even subscription IDs = Group B, Odd subscription IDs = Group A
            $subscriptionGroup = (intval($subscriptionId) % 2 === 0) ? 'B' : 'A';
            
            // Current delivery week determines which group delivers this week
            // Even delivery weeks = Group A delivers, Odd delivery weeks = Group B delivers
            $currentWeekGroup = ($deliveryWeekNumber % 2 === 0) ? 'A' : 'B';
            
            // Customer should deliver if they're in the current delivery week's group
            return $subscriptionGroup === $currentWeekGroup;
        }

        return true; // Default to true for unknown frequencies
    }

    /**
     * Get delivery schedule with enhanced frequency logic
     */
    public function getEnhancedSchedule($startDate = null, $endDate = null, $status = 'active')
    {
        $scheduleData = $this->getSchedule($startDate, $endDate, $status);
        
        if (!$scheduleData || !isset($scheduleData['data'])) {
            return $scheduleData;
        }

        // Enhance each delivery/collection with frequency information
        foreach ($scheduleData['data'] as $date => &$dateData) {
            // Process deliveries
            if (isset($dateData['deliveries'])) {
                foreach ($dateData['deliveries'] as &$delivery) {
                    $this->enhanceWithFrequencyInfo($delivery, $date);
                }
            }

            // Process collections
            if (isset($dateData['collections'])) {
                foreach ($dateData['collections'] as &$collection) {
                    $this->enhanceWithFrequencyInfo($collection, $date);
                }
            }
        }

        return $scheduleData;
    }

    /**
     * Enhance delivery/collection item with frequency information
     */
    private function enhanceWithFrequencyInfo(&$item, $deliveryDate)
    {
        // Use frequency from API response if available, otherwise extract from products
        $frequency = $item['frequency'] ?? $this->extractFrequencyFromProducts($item['products'] ?? []);
        
        // Get subscription ID for A/B group calculation
        $subscriptionId = $item['id'] ?? 0;
        
        // Add frequency information
        $item['frequency'] = $frequency;
        $item['week_type'] = $this->calculateWeekAB($subscriptionId, $deliveryDate);
        $item['should_deliver'] = $this->shouldDeliverThisWeek($frequency, $subscriptionId, $deliveryDate);
        
        // Add visual indicators
        $item['frequency_badge'] = strtolower($frequency) === 'weekly' ? 'primary' : 'info';
        $item['week_badge'] = $item['week_type'] === 'A' ? 'success' : 'warning';
    }

    /**
     * Extract frequency from product names or descriptions
     */
    private function extractFrequencyFromProducts($products)
    {
        foreach ($products as $product) {
            $name = strtolower($product['name'] ?? '');
            
            // Look for frequency indicators in product names
            if (strpos($name, 'fortnightly') !== false) {
                return 'fortnightly';
            }
            if (strpos($name, 'weekly') !== false) {
                return 'weekly';
            }
            // Check for annual products (special case)
            if (strpos($name, 'annual') !== false) {
                return 'annual';
            }
        }
        
        // Default to weekly if not specified
        return 'weekly';
    }
}
