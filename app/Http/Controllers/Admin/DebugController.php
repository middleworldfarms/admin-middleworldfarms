<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WpApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DebugController extends Controller
{
    /**
     * Debug API test page
     */
    public function apiTest(Request $request, WpApiService $wpApi)
    {
        try {
            $data = [
                'wp_api_url' => config('services.wp_api.url'),
                'wc_api_url' => config('services.wc_api.url'),
                'has_wp_keys' => !empty(config('services.wp_api.key')) && !empty(config('services.wp_api.secret')),
                'has_wc_keys' => !empty(config('services.wc_api.consumer_key')) && !empty(config('services.wc_api.consumer_secret')),
                'tests' => []
            ];
            
            // Test 1: Basic WP Connection
            $wpTest = $wpApi->testConnection();
            $data['tests']['wp_connection'] = $wpTest;
            
            // Test 2: Get Subscription
            $subscriptionId = $request->get('subscription_id', '227736');
            $wcApiUrl = config('services.wc_api.url');
            $wcConsumerKey = config('services.wc_api.consumer_key');
            $wcConsumerSecret = config('services.wc_api.consumer_secret');
            
            $response = Http::withBasicAuth($wcConsumerKey, $wcConsumerSecret)
                ->get("{$wcApiUrl}/wp-json/wc/v3/subscriptions/{$subscriptionId}");
                
            $data['tests']['get_subscription'] = [
                'status' => $response->status(),
                'success' => $response->successful()
            ];
            
            if ($response->successful()) {
                $subscription = $response->json();
                $data['subscription'] = [
                    'id' => $subscription['id'],
                    'status' => $subscription['status'],
                    'customer_name' => ($subscription['billing']['first_name'] ?? '') . ' ' . ($subscription['billing']['last_name'] ?? ''),
                    'email' => $subscription['billing']['email'] ?? '',
                ];
                
                // Extract meta data
                $data['meta_data'] = [];
                if (isset($subscription['meta_data'])) {
                    foreach ($subscription['meta_data'] as $meta) {
                        $data['meta_data'][$meta['key']] = $meta['value'];
                    }
                }
                
                // Extract frequency
                if (isset($subscription['line_items'][0]['meta_data'])) {
                    foreach ($subscription['line_items'][0]['meta_data'] as $meta) {
                        if ($meta['key'] === 'frequency') {
                            $data['frequency'] = $meta['value'];
                        }
                    }
                }
            }
            
            // Test 3: Update week type (if requested)
            if ($request->has('update_week')) {
                $weekType = $request->get('week_type');
                
                Log::info("Debug API test - updating week type", [
                    'subscription_id' => $subscriptionId,
                    'week_type' => $weekType,
                ]);
                
                $updateResponse = Http::withBasicAuth($wcConsumerKey, $wcConsumerSecret)
                    ->put("{$wcApiUrl}/wp-json/wc/v3/subscriptions/{$subscriptionId}", [
                        'meta_data' => [
                            [
                                'key' => 'customer_week_type',
                                'value' => $weekType
                            ]
                        ]
                    ]);
                    
                $data['tests']['update_week'] = [
                    'status' => $updateResponse->status(),
                    'success' => $updateResponse->successful(),
                    'body' => $updateResponse->body()
                ];
                
                // Also try the alternative MWF API endpoint
                $apiKey = config('services.wp_api.key');
                $apiSecret = config('services.wp_api.secret');
                $apiUrl = config('services.wp_api.url');
                
                $mwfResponse = Http::withBasicAuth($apiKey, $apiSecret)
                    ->post("{$apiUrl}/wp-json/mwf/v1/subscriptions/{$subscriptionId}/meta", [
                        'key' => 'customer_week_type',
                        'value' => $weekType,
                        'integration_key' => config('services.wc_api.integration_key')
                    ]);
                    
                $data['tests']['update_week_mwf'] = [
                    'status' => $mwfResponse->status(),
                    'success' => $mwfResponse->successful(),
                    'body' => $mwfResponse->body()
                ];
            }
            
            return view('admin.debug.api-test', $data);
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
