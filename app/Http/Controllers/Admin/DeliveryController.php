<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WpApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class DeliveryController extends Controller
{
    /**
     * Display the delivery schedule management page.
     */
    public function index(Request $request, WpApiService $wpApi)
    {
        try {
            // Get selected week from request, default to current week
            $selectedWeek = $request->get('week', date('W'));
            
            // Test API connection
            $apiStatus = $wpApi->testConnection();
            
            // Get raw data via API - implement getDeliveryScheduleData in WpApiService
            $rawData = $wpApi->getDeliveryScheduleData(500);
            
            // Transform data to match view expectations
            $scheduleData = $this->transformScheduleData($rawData, $selectedWeek);
            
            // Calculate actual totals from transformed data (after duplicate removal)
            $totalDeliveries = 0;
            $totalCollections = 0;
            
            foreach ($scheduleData['data'] as $dateData) {
                $totalDeliveries += count($dateData['deliveries'] ?? []);
                $totalCollections += count($dateData['collections'] ?? []);
            }
            
            // Calculate status counts for collections subtabs
            $statusCounts = [
                'active' => 0,
                'processing' => 0,  // Add processing status
                'on-hold' => 0,
                'cancelled' => 0,
                'pending' => 0,
                'completed' => 0,   // Add completed status
                'refunded' => 0,    // Add refunded status
                'other' => 0
            ];
            
            if (isset($scheduleData['collectionsByStatus'])) {
                foreach ($scheduleData['collectionsByStatus'] as $status => $statusData) {
                    foreach ($statusData as $dateData) {
                        $statusCounts[$status] += count($dateData['collections'] ?? []);
                    }
                }
            }
            
            // Calculate status counts for delivery subtabs
            $deliveryStatusCounts = [
                'active' => 0,      // Add active for deliveries (processing = active)
                'processing' => 0,
                'pending' => 0,
                'completed' => 0,
                'on-hold' => 0,
                'cancelled' => 0,
                'refunded' => 0,
                'other' => 0
            ];
            
            if (isset($scheduleData['deliveriesByStatus'])) {
                foreach ($scheduleData['deliveriesByStatus'] as $status => $statusData) {
                    foreach ($statusData as $dateData) {
                        $count = count($dateData['deliveries'] ?? []);
                        $deliveryStatusCounts[$status] += $count;
                        
                        // Add delivery counts to combined status counts for All tab
                        if ($status === 'processing') {
                            // Processing deliveries are "active"
                            $statusCounts['active'] += $count;
                            $statusCounts['processing'] += $count;
                            $deliveryStatusCounts['active'] += $count; // Also count as active for deliveries tab
                        } else {
                            // Other delivery statuses
                            if (isset($statusCounts[$status])) {
                                $statusCounts[$status] += $count;
                            } else {
                                $statusCounts['other'] += $count;
                            }
                        }
                    }
                }
            }            
            // Direct database connection status
            $directDbStatus = [
                'success' => true,
                'message' => 'Connected directly to WooCommerce database',
                'data_source' => 'mysql_direct'
            ];
            
            // Simple user switching status (no confusing API messaging)
            $userSwitchingAvailable = true;
            
            $error = null;
            
            return view('admin.deliveries.fixed', compact(
                'scheduleData', 
                'totalDeliveries', 
                'totalCollections',
                'statusCounts',
                'deliveryStatusCounts',
                'directDbStatus',
                'userSwitchingAvailable',
                'error',
                'selectedWeek' // pass to view
            ));
            
        } catch (\Exception $e) {
            $scheduleData = ['data' => []];
            $totalDeliveries = 0;
            $totalCollections = 0;
            $statusCounts = ['active' => 0, 'processing' => 0, 'on-hold' => 0, 'cancelled' => 0, 'pending' => 0, 'completed' => 0, 'refunded' => 0, 'other' => 0];
            $deliveryStatusCounts = ['active' => 0, 'processing' => 0, 'pending' => 0, 'completed' => 0, 'on-hold' => 0, 'cancelled' => 0, 'refunded' => 0, 'other' => 0];
            $directDbStatus = ['success' => false, 'message' => 'Direct database connection failed: ' . $e->getMessage()];
            $userSwitchingAvailable = false;
            $error = $e->getMessage();
            
            return view('admin.deliveries.fixed', compact(
                'scheduleData', 
                'totalDeliveries', 
                'totalCollections',
                'statusCounts',
                'deliveryStatusCounts',
                'directDbStatus',
                'userSwitchingAvailable',
                'error',
                'selectedWeek' // pass to view
            ));
        }
    }

    /**
     * Transform raw database data to match view expectations
     */
    private function transformScheduleData($rawData, $selectedWeek = null)
    {
        // Use current week if no selectedWeek provided
        if ($selectedWeek === null) {
            $selectedWeek = date('W');
        }
        $selectedWeek = (int) $selectedWeek;
        
        // Calculate the selected week type (A or B)
        $selectedWeekType = ($selectedWeek % 2 === 1) ? 'A' : 'B';
        
        // If rawData is a flat API response (list of subscriptions), split into deliveries/collections
        if (isset($rawData[0]) && is_array($rawData[0])) {
            $subscriptions = $rawData;
            $rawData = ['deliveries' => [], 'collections' => []];
            foreach ($subscriptions as $sub) {
                $shippingTotal = (float) ($sub['shipping_total'] ?? 0);
                $type = $shippingTotal > 0 ? 'deliveries' : 'collections';
                
                // Extract frequency - use WooCommerce subscription standard fields first
                $frequency = 'Weekly'; // Default
                
                // Method 1: Check WooCommerce subscription billing_period and billing_interval (standard approach)
                if (isset($sub['billing_period']) && strtolower($sub['billing_period']) === 'week') {
                    $interval = intval($sub['billing_interval'] ?? 1);
                    if ($interval === 2) {
                        $frequency = 'Fortnightly';
                    } elseif ($interval === 1) {
                        $frequency = 'Weekly';
                    }
                }
                
                // Method 2: Check line items meta_data as fallback
                if ($frequency === 'Weekly' && isset($sub['line_items'][0]['meta_data'])) {
                    foreach ($sub['line_items'][0]['meta_data'] as $meta) {
                        if ($meta['key'] === 'frequency') {
                            $frequency = $meta['value'];
                            break;
                        }
                    }
                }
                
                // Method 3: Check top-level meta_data as final fallback
                if ($frequency === 'Weekly' && isset($sub['meta_data'])) {
                    foreach ($sub['meta_data'] as $meta) {
                        if ($meta['key'] === 'frequency' || $meta['key'] === '_subscription_frequency') {
                            $frequency = $meta['value'];
                            break;
                        }
                    }
                }
                
                // Extract customer week type from meta_data if available
                $customerWeekType = 'Weekly'; // Default
                
                // First check for session-based temporary override (from failed API updates)
                // Check both subscription ID and customer ID for backwards compatibility
                if (session()->has("customer_week_type_{$sub['id']}")) {
                    $customerWeekType = session("customer_week_type_{$sub['id']}");
                } 
                elseif (session()->has("customer_week_type_{$sub['customer_id']}")) {
                    $customerWeekType = session("customer_week_type_{$sub['customer_id']}");
                } 
                // Then check meta_data from API
                elseif (isset($sub['meta_data'])) {
                    foreach ($sub['meta_data'] as $meta) {
                        if ($meta['key'] === 'customer_week_type') {
                            $customerWeekType = $meta['value'];
                            break;
                        }
                    }
                }

                // **WEEK FILTERING LOGIC** - Skip subscriptions that shouldn't appear in the selected week
                $shouldIncludeInSelectedWeek = false;
                
                if (strtolower($frequency) === 'weekly') {
                    // Weekly subscriptions appear in every week
                    $shouldIncludeInSelectedWeek = true;
                } elseif (strtolower($frequency) === 'fortnightly') {
                    // For fortnightly subscriptions, only show on their assigned week
                    if ($customerWeekType === 'Weekly') {
                        // If no specific week assigned, assign to current week type
                        $customerWeekType = $selectedWeekType;
                    }
                    
                    // Check if the customer's assigned week matches the selected week
                    $shouldIncludeInSelectedWeek = ($customerWeekType === $selectedWeekType);
                }
                
                // Skip this subscription if it shouldn't appear in the selected week
                if (!$shouldIncludeInSelectedWeek) {
                    continue;
                }
                
                // Skip this subscription if it shouldn't appear in the selected week
                if (!$shouldIncludeInSelectedWeek) {
                    continue;
                }
                
                // Calculate week logic for display
                $currentWeek = (int) date('W');
                $currentWeekType = ($currentWeek % 2 === 1) ? 'A' : 'B';
                $shouldDeliverThisWeek = true;
                $weekBadge = 'primary';

                if (strtolower($frequency) === 'fortnightly') {
                    // For fortnightly customers, check if their assigned week matches current week
                    $shouldDeliverThisWeek = ($customerWeekType === $currentWeekType);
                    
                    // Set week badge color
                    if ($customerWeekType === 'A') {
                        $weekBadge = 'success'; // Green for Week A
                    } elseif ($customerWeekType === 'B') {
                        $weekBadge = 'info'; // Blue for Week B
                    }
                } else {
                    // Weekly customers get primary badge
                    $weekBadge = 'primary';
                }
                
                // Set frequency badge
                $frequencyBadge = strtolower($frequency) === 'fortnightly' ? 'warning' : 'success';

                // Get preferred collection day from WP user meta for collection subscriptions
                $preferred_collection_day = 'Friday'; // Default to Friday
                // We'll batch load these values later to avoid slow API calls
                // For now, use the default value to prevent API timeouts

                // Store both subscription ID and customer ID - use subscription ID for API operations
                $rawData[$type][] = [
                    'id'                    => $sub['id'], // This is the subscription ID
                    'subscription_id'        => $sub['id'], // Keep a clear reference
                    'customer_id'           => $sub['customer_id'], // This is the WP user ID
                    'status'                => $sub['status'],
                    'date_created'          => $sub['date_created'],
                    'customer_email'        => $sub['billing']['email'] ?? '',
                    'name'                  => trim(($sub['billing']['first_name'] ?? '') . ' ' . ($sub['billing']['last_name'] ?? '')),
                    'address'               => array_filter([
                        $sub['shipping']['address_1'] ?? '',
                        $sub['shipping']['address_2'] ?? '',
                        $sub['shipping']['city'] ?? '',
                        $sub['shipping']['state'] ?? '',
                        $sub['shipping']['postcode'] ?? ''
                    ]),
                    'products'              => array_map(fn($item) => ['quantity' => $item['quantity'], 'name' => $item['name']], $sub['line_items'] ?? []),
                    'phone'                 => $sub['billing']['phone'] ?? '',
                    'email'                 => $sub['billing']['email'] ?? '',
                    'frequency'             => $frequency,
                    'next_payment'          => $sub['next_payment_date_gmt'] ?? '',
                    'total'                 => $sub['total'] ?? '0.00',
                    'customer_week_type'    => $customerWeekType,
                    'current_week_type'     => $currentWeekType,
                    'should_deliver_this_week' => $shouldDeliverThisWeek,
                    'week_badge'            => $weekBadge,
                    'frequency_badge'       => $frequencyBadge,
                    'preferred_collection_day' => $preferred_collection_day,
                ];
            }
        }

        $groupedData = [];
        $seenCustomers = [];
        
        // Process DELIVERIES - items from the service where type = 'delivery'
        if (isset($rawData['deliveries'])) {
            foreach ($rawData['deliveries'] as $delivery) {
                $date = \Carbon\Carbon::parse($delivery['date_created']);
                $dateKey = $date->format('Y-m-d');
                $dateFormatted = $date->format('l, F j, Y');
                $customerKey = $delivery['customer_email'] . '_' . $delivery['id'];
                
                if (isset($seenCustomers[$customerKey])) {
                    continue;
                }
                $seenCustomers[$customerKey] = true;
                
                if (!isset($groupedData[$dateKey])) {
                    $groupedData[$dateKey] = [
                        'date_formatted' => $dateFormatted,
                        'deliveries' => [],
                        'collections' => []
                    ];
                }
                $groupedData[$dateKey]['deliveries'][] = $delivery;
            }
        }
        
        // Process COLLECTIONS - items from the service where type = 'collection'
        if (isset($rawData['collections'])) {
            foreach ($rawData['collections'] as $collection) {
                $date = \Carbon\Carbon::parse($collection['date_created']);
                $dateKey = $date->format('Y-m-d');
                $dateFormatted = $date->format('l, F j, Y');
                $customerKey = $collection['customer_email'] . '_' . $collection['id'];
                
                if (isset($seenCustomers[$customerKey])) {
                    continue;
                }
                $seenCustomers[$customerKey] = true;
                
                // Ensure frequency is properly formatted
                $frequency = isset($collection['frequency']) ? ucfirst(strtolower($collection['frequency'])) : '';
                $collection['frequency'] = $frequency;

                // Add week logic for collections if not already present
                if (!isset($collection['customer_week_type'])) {
                    $currentWeek = (int) date('W');
                    $currentWeekType = ($currentWeek % 2 === 1) ? 'A' : 'B';

                    if (strtolower($frequency) === 'fortnightly') {
                        // For fortnightly collections, assign to current week type by default
                        $collection['customer_week_type'] = $currentWeekType;
                        $collection['current_week_type'] = $currentWeekType;
                        $collection['should_deliver_this_week'] = true; // Default to true for collections
                        $collection['week_badge'] = $currentWeekType === 'A' ? 'success' : 'info';
                        $collection['frequency_badge'] = 'warning';
                    } else {
                        $collection['customer_week_type'] = 'Weekly';
                        $collection['current_week_type'] = $currentWeekType;
                        $collection['should_deliver_this_week'] = true;
                        $collection['week_badge'] = 'primary';
                        $collection['frequency_badge'] = 'success';
                    }
                }
                
                // Make sure week logic is properly set for fortnightly customers
                if (strtolower($frequency) === 'fortnightly' && $collection['customer_week_type'] === 'Weekly') {
                    $currentWeek = (int) date('W');
                    $currentWeekType = ($currentWeek % 2 === 1) ? 'A' : 'B';
                    $collection['customer_week_type'] = $currentWeekType;
                    $collection['current_week_type'] = $currentWeekType;
                    $collection['should_deliver_this_week'] = true;
                    $collection['week_badge'] = $currentWeekType === 'A' ? 'success' : 'info';
                    $collection['frequency_badge'] = 'warning';
                }
                
                if (!isset($groupedData[$dateKey])) {
                    $groupedData[$dateKey] = [
                        'date_formatted' => $dateFormatted,
                        'deliveries' => [],
                        'collections' => []
                    ];
                }
                $groupedData[$dateKey]['collections'][] = $collection;
            }
        }
        
        krsort($groupedData);
        
        // Group collections by status for the subtabs
        $collectionsByStatus = [
            'active' => [],
            'on-hold' => [],
            'cancelled' => [],
            'pending' => [],
            'other' => []
        ];
        
        foreach ($groupedData as $date => $dateData) {
            foreach ($dateData['collections'] ?? [] as $collection) {
                $status = strtolower($collection['status']);
                
                if (!isset($collectionsByStatus[$status])) {
                    $status = 'other';
                }
                
                if (!isset($collectionsByStatus[$status][$date])) {
                    $collectionsByStatus[$status][$date] = [
                        'date_formatted' => $dateData['date_formatted'],
                        'collections' => []
                    ];
                }
                
                $collectionsByStatus[$status][$date]['collections'][] = $collection;
            }
        }
        
        // Group deliveries by status for the subtabs
        $deliveriesByStatus = [
            'processing' => [],
            'pending' => [],
            'completed' => [],
            'on-hold' => [],
            'cancelled' => [],
            'refunded' => [],
            'other' => []
        ];
        
        foreach ($groupedData as $date => $dateData) {
            foreach ($dateData['deliveries'] ?? [] as $delivery) {
                $status = strtolower($delivery['status']);
                $status = str_replace('wc-', '', $status);
                
                if (!isset($deliveriesByStatus[$status])) {
                    $status = 'other';
                }
                
                if (!isset($deliveriesByStatus[$status][$date])) {
                    $deliveriesByStatus[$status][$date] = [
                        'date_formatted' => $dateData['date_formatted'],
                        'deliveries' => []
                    ];
                }
                
                $deliveriesByStatus[$status][$date]['deliveries'][] = $delivery;
            }
        }
        
        // Sort each status group by date
        foreach ($collectionsByStatus as $status => $statusData) {
            krsort($collectionsByStatus[$status]);
        }
        
        foreach ($deliveriesByStatus as $status => $statusData) {
            krsort($deliveriesByStatus[$status]);
        }
        
        $totalProcessed = count($seenCustomers);
        $deliveryCount = isset($rawData['deliveries']) ? count($rawData['deliveries']) : 0;
        $collectionCount = isset($rawData['collections']) ? count($rawData['collections']) : 0;
        $duplicatesSkipped = ($deliveryCount + $collectionCount) - $totalProcessed;
        
        $data = [
            'success' => true,
            'data' => $groupedData,
            'collectionsByStatus' => $collectionsByStatus,
            'deliveriesByStatus' => $deliveriesByStatus,
            'data_source' => 'direct_database',
            'message' => "Data loaded with duplicate prevention. Processed: {$totalProcessed} unique items, Skipped: {$duplicatesSkipped} duplicates"
        ];
        
        // Add collection day preferences to the data
        // This is done as a separate step to improve performance by batching the API calls
        $data = $this->addCollectionDaysToScheduleData($data);
        
        return $data;
    }

    /**
     * API test endpoint for debugging
     */
    public function apiTest(WpApiService $wpApi)
    {
        try {
            $tests = [
                'direct_database_connection' => $wpApi->testConnection(),
                'recent_users' => $wpApi->getRecentUsers(3),
                'delivery_data' => $wpApi->getDeliveryScheduleData(5),
                'woocommerce_settings' => $wpApi->getWooCommerceSettings()
            ];
            
            return response()->json([
                'success' => true,
                'tests' => $tests,
                'message' => 'All tests using direct database connection only',
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    /**
     * Update customer week type for fortnightly deliveries
     */
    public function updateCustomerWeek(WpApiService $wpApi)
    {
        try {
            $customerId = request('customer_id');
            $weekType = request('week_type');
            
            \Log::info("Updating customer week type", [
                'customer_id' => $customerId,
                'week_type' => $weekType
            ]);
            
            if (!$customerId || !in_array($weekType, ['A', 'B'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid customer ID or week type'
                ], 400);
            }
            
            // Let's add more detailed debugging to track down the issue
            $wcApiUrl = config('services.wc_api.url');
            $wcConsumerKey = config('services.wc_api.consumer_key');
            $wcConsumerSecret = config('services.wc_api.consumer_secret');
            
            // Check if we have valid WooCommerce API credentials
            if (empty($wcConsumerKey) || empty($wcConsumerSecret)) {
                \Log::error("Missing WooCommerce API credentials");
                return response()->json([
                    'success' => false,
                    'message' => 'WooCommerce API credentials not configured',
                    'debug' => [
                        'customer_id' => $customerId,
                        'week_type' => $weekType,
                        'has_consumer_key' => !empty($wcConsumerKey),
                        'has_consumer_secret' => !empty($wcConsumerSecret)
                    ]
                ], 500);
            }
            
            // First, verify that we're using the correct endpoint structure
            $subscriptionsEndpoint = "{$wcApiUrl}/wp-json/wc/v3/subscriptions/{$customerId}";
            \Log::info("Checking if subscription exists", [
                'subscription_id' => $customerId,
                'endpoint' => $subscriptionsEndpoint
            ]);
            
            // Check if the subscription exists by doing a GET request
            $checkResponse = Http::withBasicAuth($wcConsumerKey, $wcConsumerSecret)
                ->get($subscriptionsEndpoint);
                
            if (!$checkResponse->successful()) {
                \Log::warning("Subscription not found", [
                    'subscription_id' => $customerId, 
                    'status_code' => $checkResponse->status(),
                    'response' => $checkResponse->body()
                ]);
                
                // Let's try a fallback to update using the temporary session storage
                session()->put("customer_week_type_{$customerId}", $weekType);
                
                return response()->json([
                    'success' => true,
                    'warning' => true,
                    'message' => "Subscription with ID {$customerId} not found in WooCommerce. Using temporary session storage instead.",
                    'debug' => [
                        'status_code' => $checkResponse->status(),
                        'body' => $checkResponse->body()
                    ],
                    'customer_id' => $customerId,
                    'week_type' => $weekType,
                    'method' => 'session_based'
                ]);
            }
            
            // The subscription exists, now try to update its metadata
            // We'll try several approaches in sequence until one works
            try {
                \Log::info("Updating subscription metadata", [
                    'customer_id' => $customerId,
                    'week_type' => $weekType
                ]);
                
                // Approach 1: Standard WooCommerce Subscriptions REST API
                \Log::info("Trying approach 1: WooCommerce Subscriptions REST API");
                $response = Http::withBasicAuth($wcConsumerKey, $wcConsumerSecret)
                    ->put("{$wcApiUrl}/wp-json/wc/v3/subscriptions/{$customerId}", [
                        'meta_data' => [
                            [
                                'key' => 'customer_week_type',
                                'value' => $weekType
                            ]
                        ]
                    ]);

                if ($response->successful()) {
                    \Log::info("Successfully updated via WooCommerce API");
                    return response()->json([
                        'success' => true,
                        'message' => "Customer week type updated to Week {$weekType}",
                        'customer_id' => $customerId,
                        'week_type' => $weekType,
                        'method' => 'woocommerce_api'
                    ]);
                }
                
                \Log::warning("WooCommerce API update failed", [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                
                // Approach 2: Try MWF Custom REST endpoint if available
                \Log::info("Trying approach 2: MWF Custom API");
                $apiKey = config('services.wp_api.key');
                $apiSecret = config('services.wp_api.secret');
                $apiUrl = config('services.wp_api.url');
                
                $mwfResponse = Http::withBasicAuth($apiKey, $apiSecret)
                    ->post("{$apiUrl}/wp-json/mwf/v1/subscriptions/{$customerId}/meta", [
                        'key' => 'customer_week_type',
                        'value' => $weekType,
                        'integration_key' => config('services.wc_api.integration_key')
                    ]);
                    
                if ($mwfResponse->successful()) {
                    \Log::info("Successfully updated via MWF plugin API");
                    return response()->json([
                        'success' => true,
                        'message' => "Customer week type updated to Week {$weekType}",
                        'customer_id' => $customerId,
                        'week_type' => $weekType,
                        'method' => 'mwf_plugin_api'
                    ]);
                }
                
                \Log::warning("MWF plugin API update failed", [
                    'status' => $mwfResponse->status(),
                    'response' => $mwfResponse->body()
                ]);
                
                // Approach 3: Create a temporary flag in the session and apply it on next page load
                \Log::info("Trying approach 3: Session-based temporary update");
                session()->put("customer_week_type_{$customerId}", $weekType);
                // Also save by customer ID as fallback
                session()->put("customer_week_type_" . $checkResponse->json('customer_id'), $weekType);
                
                return response()->json([
                    'success' => true,
                    'message' => "Customer week type updated to Week {$weekType} (temporary session-based update)",
                    'customer_id' => $customerId,
                    'week_type' => $weekType,
                    'method' => 'session_based',
                    'warning' => 'This change is temporary until metadata API is working'
                ]);
                
            } catch (\Exception $apiException) {
                \Log::error("API exception", [
                    'message' => $apiException->getMessage(),
                    'trace' => $apiException->getTraceAsString()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'API Exception: ' . $apiException->getMessage()
                ], 500);
            }
            
        } catch (\Exception $e) {
            \Log::error("General exception in updateCustomerWeek", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer week: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Diagnostic method to check subscription statuses and counts
     */
    public function diagnosticSubscriptions(WpApiService $wpApi)
    {
        try {
            // Get ALL subscriptions without limit to see the full picture
            $allSubscriptions = \App\Models\WooCommerceOrder::subscriptions()
                ->with(['meta'])
                ->get();
            
            // Group by status
            $statusCounts = [];
            $statusExamples = [];
            
            foreach ($allSubscriptions as $subscription) {
                $status = $subscription->post_status;
                $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
                
                if (!isset($statusExamples[$status])) {
                    $statusExamples[$status] = [
                        'id' => $subscription->ID,
                        'date' => $subscription->post_date,
                        'customer_email' => $subscription->getMeta('_billing_email'),
                        'total' => $subscription->getMeta('_order_total')
                    ];
                }
            }
            
            // Also check what the current service returns
            $serviceData = $wpApi->getDeliveryScheduleData(200); // Increase limit for testing
            
            return response()->json([
                'success' => true,
                'total_subscriptions_in_db' => $allSubscriptions->count(),
                'status_breakdown' => $statusCounts,
                'status_examples' => $statusExamples,
                'service_returned_deliveries' => count($serviceData['deliveries'] ?? []),
                'service_returned_collections' => count($serviceData['collections'] ?? []),
                'service_total' => (count($serviceData['deliveries'] ?? [])) + (count($serviceData['collections'] ?? [])),
                'likely_active_statuses' => ['wc-active', 'active', 'wc-pending', 'pending'],
                'message' => 'Diagnostic complete - check status_breakdown to see what statuses exist'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test the fixed filtering to see active subscriptions only
     */
    public function testActiveFilter(WpApiService $wpApi)
    {
        try {
            // Test the updated service
            $rawData = $wpApi->getDeliveryScheduleData(500);
            
            return response()->json([
                'success' => true,
                'active_deliveries_count' => count($rawData['deliveries'] ?? []),
                'active_collections_count' => count($rawData['collections'] ?? []),
                'total_active_subscriptions' => (count($rawData['deliveries'] ?? [])) + (count($rawData['collections'] ?? [])),
                'should_be_30_or_31' => 'Expected around 30-31 active subscriptions',
                'deliveries_sample' => array_slice($rawData['deliveries'] ?? [], 0, 3),
                'collections_sample' => array_slice($rawData['collections'] ?? [], 0, 3),
                'message' => 'Now filtering for wc-active and wc-pending only'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug week assignment to see what's happening
     */
    public function debugWeekAssignment(WpApiService $wpApi)
    {
        try {
            // Get a few subscriptions to debug
            $subscriptions = \App\Models\WooCommerceOrder::subscriptions()
                ->whereIn('post_status', ['wc-active', 'wc-pending'])
                ->with(['meta'])
                ->limit(10)
                ->get();
            
            $debugData = [];
            
            foreach ($subscriptions as $subscription) {
                $parentOrderId = $subscription->post_parent;
                $debug = [
                    'subscription_id' => $subscription->ID,
                    'subscription_date' => $subscription->post_date,
                    'subscription_week' => (int) \Carbon\Carbon::parse($subscription->post_date)->format('W'),
                    'parent_order_id' => $parentOrderId,
                ];
                
                if ($parentOrderId) {
                    $parentOrder = \App\Models\WooCommerceOrder::find($parentOrderId);
                    if ($parentOrder) {
                        $orderDate = \Carbon\Carbon::parse($parentOrder->post_date);
                        $debug['parent_order_date'] = $parentOrder->post_date;
                        $debug['parent_order_week'] = (int) $orderDate->format('W');
                        $debug['assigned_week'] = ($orderDate->format('W') % 2 === 1) ? 'A' : 'B';
                        $debug['used_parent'] = true;
                    } else {
                        $debug['parent_not_found'] = true;
                        $debug['used_parent'] = false;
                        $subscriptionDate = \Carbon\Carbon::parse($subscription->post_date);
                        $debug['assigned_week'] = ($subscriptionDate->format('W') % 2 === 1) ? 'A' : 'B';
                    }
                } else {
                    $debug['no_parent'] = true;
                    $debug['used_parent'] = false;
                    $subscriptionDate = \Carbon\Carbon::parse($subscription->post_date);
                    $debug['assigned_week'] = ($subscriptionDate->format('W') % 2 === 1) ? 'A' : 'B';
                }
                
                $debugData[] = $debug;
            }
            
            return response()->json([
                'success' => true,
                'current_iso_week' => (int) date('W'),
                'current_week_type' => ((int) date('W') % 2 === 1) ? 'A' : 'B',
                'debug_data' => $debugData,
                'message' => 'Check the assigned_week column to see the pattern'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug what the actual delivery schedule is returning
     */
    public function debugDeliverySchedule(WpApiService $wpApi)
    {
        try {
            // Get the same data that the main page uses
            $rawData = $wpApi->getDeliveryScheduleData(500);
            
            $weekACount = 0;
            $weekBCount = 0;
            $weeklyCount = 0;
            $allCustomers = [];
            
            // Analyze deliveries
            foreach ($rawData['deliveries'] ?? [] as $delivery) {
                $weekType = $delivery['customer_week_type'] ?? 'Unknown';
                $allCustomers[] = [
                    'type' => 'delivery',
                    'id' => $delivery['id'],
                    'customer' => $delivery['customer_name'] ?? 'Unknown',
                    'frequency' => $delivery['frequency'] ?? 'Unknown',
                    'week_type' => $weekType,
                    'should_deliver' => $delivery['should_deliver_this_week'] ?? 'Unknown'
                ];
                
                if ($weekType === 'A') $weekACount++;
                elseif ($weekType === 'B') $weekBCount++;
                elseif ($weekType === 'Weekly') $weeklyCount++;
            }
            
            // Analyze collections
            foreach ($rawData['collections'] ?? [] as $collection) {
                $weekType = $collection['customer_week_type'] ?? 'Unknown';
                $allCustomers[] = [
                    'type' => 'collection',
                    'id' => $collection['id'],
                    'customer' => $collection['customer_name'] ?? 'Unknown',
                    'frequency' => $collection['frequency'] ?? 'Unknown',
                    'week_type' => $weekType,
                    'should_deliver' => $collection['should_deliver_this_week'] ?? 'Unknown'
                ];
                
                if ($weekType === 'A') $weekACount++;
                elseif ($weekType === 'B') $weekBCount++;
                elseif ($weekType === 'Weekly') $weeklyCount++;
            }
            
            return response()->json([
                'success' => true,
                'current_week_type' => 'B',
                'total_customers' => count($allCustomers),
                'week_breakdown' => [
                    'Week_A' => $weekACount,
                    'Week_B' => $weekBCount, 
                    'Weekly' => $weeklyCount
                ],
                'deliveries_count' => count($rawData['deliveries'] ?? []),
                'collections_count' => count($rawData['collections'] ?? []),
                'all_customers' => $allCustomers,
                'raw_totals' => [
                    'deliveries' => $rawData['total_deliveries'] ?? 0,
                    'collections' => $rawData['total_collections'] ?? 0
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug specific customers to see week assignment in detail
     */
    public function debugSpecificCustomers(WpApiService $wpApi)
    {
        try {
            // Get ALL fortnightly subscriptions specifically
            $subscriptions = \App\Models\WooCommerceOrder::subscriptions()
                ->whereIn('post_status', ['wc-active', 'wc-pending'])
                ->with(['meta'])
                ->limit(100) // Increased to catch all
                ->get();
            
            $debugDetails = [];
            
            foreach ($subscriptions as $subscription) {
                // Get frequency from parent order (same logic as service)
                $parentOrderId = $subscription->post_parent;
                $frequency = null;
                $paymentOption = null;
                if ($parentOrderId) {
                    $parentOrder = \App\Models\WooCommerceOrder::find($parentOrderId);
                    if ($parentOrder) {
                        foreach ($parentOrder->items as $item) {
                            $freqMeta = strtolower(trim($item->getMeta('frequency', '')));
                            $payOptMeta = strtolower(trim($item->getMeta('payment-option', '')));
                            if ($freqMeta) $frequency = $freqMeta;
                            if ($payOptMeta) $paymentOption = $payOptMeta;
                        }
                    }
                }
                
                $freqValue = $frequency ?: $paymentOption ?: '';
                $isFortnightly = strpos($freqValue, 'fortnightly') !== false;
                
                if ($isFortnightly) {
                    $storedWeekType = $subscription->getMeta('customer_week_type');
                    
                    // Week assignment logic (same as service)
                    if ($storedWeekType && in_array($storedWeekType, ['A', 'B'])) {
                        $assignedWeek = $storedWeekType;
                        $source = 'stored_preference';
                    } else {
                        if ($parentOrderId) {
                            $parentOrder = \App\Models\WooCommerceOrder::find($parentOrderId);
                            if ($parentOrder) {
                                $orderDate = \Carbon\Carbon::parse($parentOrder->post_date);
                                $orderWeek = (int) $orderDate->format('W');
                                $assignedWeek = ($orderWeek % 2 === 1) ? 'A' : 'B';
                                $source = 'parent_order_date';
                            } else {
                                $subscriptionDate = \Carbon\Carbon::parse($subscription->post_date);
                                $subscriptionWeek = (int) $subscriptionDate->format('W');
                                $assignedWeek = ($subscriptionWeek % 2 === 1) ? 'A' : 'B';
                                $source = 'subscription_date_fallback';
                            }
                        } else {
                            $subscriptionDate = \Carbon\Carbon::parse($subscription->post_date);
                            $subscriptionWeek = (int) $subscriptionDate->format('W');
                            $assignedWeek = ($subscriptionWeek % 2 === 1) ? 'A' : 'B';
                            $source = 'subscription_date_no_parent';
                        }
                    }
                    
                    $debugDetails[] = [
                        'subscription_id' => $subscription->ID,
                        'customer_email' => $subscription->getMeta('_billing_email'),
                        'frequency_found' => $freqValue,
                        'is_fortnightly' => true,
                        'stored_week_type' => $storedWeekType,
                        'assigned_week' => $assignedWeek,
                        'assignment_source' => $source,
                        'parent_order_id' => $parentOrderId,
                        'parent_order_exists' => $parentOrderId ? (\App\Models\WooCommerceOrder::find($parentOrderId) ? 'yes' : 'no') : 'no_parent'
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'fortnightly_customers_found' => count($debugDetails),
                'debug_details' => $debugDetails,
                'current_week' => (int) date('W'),
                'current_week_type' => ((int) date('W') % 2 === 1) ? 'A' : 'B',
                'message' => 'Check assignment_source and assigned_week for each customer'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug what should appear on the delivery schedule page
     */
    public function debugPageDisplay(WpApiService $wpApi)
    {
        try {
            // Get the exact same data that the main delivery page uses
            $rawData = $wpApi->getDeliveryScheduleData(500);
            
            $weekACount = 0;
            $weekBCount = 0;
            $weeklyCount = 0;
            $totalFortnightly = 0;
            $allCustomers = [];
            
            // Check all customers (deliveries + collections)
            $allItems = array_merge($rawData['deliveries'] ?? [], $rawData['collections'] ?? []);
            
            foreach ($allItems as $item) {
                $frequency = $item['frequency'] ?? 'Unknown';
                $weekType = $item['customer_week_type'] ?? 'Unknown';
                
                $customer = [
                    'id' => $item['id'],
                    'email' => $item['customer_email'] ?? 'Unknown',
                    'frequency' => $frequency,
                    'week_type' => $weekType,
                    'should_deliver' => $item['should_deliver_this_week'] ?? 'Unknown',
                    'type' => $item['type'] ?? 'Unknown'
                ];
                
                if ($frequency === 'Fortnightly') {
                    $totalFortnightly++;
                    if ($weekType === 'A') $weekACount++;
                    elseif ($weekType === 'B') $weekBCount++;
                } elseif ($frequency === 'Weekly') {
                    $weeklyCount++;
                }
                
                $allCustomers[] = $customer;
            }
            
            return response()->json([
                'success' => true,
                'total_customers' => count($allCustomers),
                'frequency_breakdown' => [
                    'Weekly' => $weeklyCount,
                    'Fortnightly' => $totalFortnightly,
                    'Fortnightly_Week_A' => $weekACount,
                    'Fortnightly_Week_B' => $weekBCount
                ],
                'raw_data_counts' => [
                    'deliveries' => count($rawData['deliveries'] ?? []),
                    'collections' => count($rawData['collections'] ?? [])
                ],
                'customers_sample' => array_slice($allCustomers, 0, 10),
                'fortnightly_customers_only' => array_filter($allCustomers, function($c) { 
                    return $c['frequency'] === 'Fortnightly'; 
                }),
                'current_week_is' => 'B',
                'message' => 'This shows what should appear on the delivery schedule page'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Simple accurate count - no complex logic, just count what we see
     */
    public function simpleCount(WpApiService $wpApi)
    {
        try {
            $rawData = $wpApi->getDeliveryScheduleData(500);
            
            $fortnightlyWeekA = 0;
            $fortnightlyWeekB = 0;
            $weekly = 0;
            $fortnightlyList = [];
            
            // Count collections
            foreach ($rawData['collections'] ?? [] as $collection) {
                $freq = $collection['frequency'] ?? '';
                $week = $collection['customer_week_type'] ?? '';
                
                if ($freq === 'Fortnightly') {
                    if ($week === 'A') $fortnightlyWeekA++;
                    elseif ($week === 'B') $fortnightlyWeekB++;
                    
                    $fortnightlyList[] = [
                        'id' => $collection['id'],
                        'email' => $collection['customer_email'] ?? '',
                        'week' => $week,
                        'type' => 'collection'
                    ];
                } elseif ($freq === 'Weekly') {
                    $weekly++;
                }
            }
            
            // Count deliveries
            foreach ($rawData['deliveries'] ?? [] as $delivery) {
                $freq = $delivery['frequency'] ?? '';
                $week = $delivery['customer_week_type'] ?? '';
                
                if ($freq === 'Fortnightly') {
                    if ($week === 'A') $fortnightlyWeekA++;
                    elseif ($week === 'B') $fortnightlyWeekB++;
                    
                    $fortnightlyList[] = [
                        'id' => $delivery['id'],
                        'email' => $delivery['customer_email'] ?? '',
                        'week' => $week,
                        'type' => 'delivery'
                    ];
                } elseif ($freq === 'Weekly') {
                    $weekly++;
                }
            }
            
            return response()->json([
                'success' => true,
                'fortnightly_week_a_count' => $fortnightlyWeekA,
                'fortnightly_week_b_count' => $fortnightlyWeekB,
                'weekly_count' => $weekly,
                'total_fortnightly' => $fortnightlyWeekA + $fortnightlyWeekB,
                'fortnightly_details' => $fortnightlyList,
                'message' => 'This is a simple count of what we see'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug customer statuses to see what "Other" means
     */
    public function debugCustomerStatuses(WpApiService $wpApi)
    {
        try {
            // Get the raw data
            $rawData = $wpApi->getDeliveryScheduleData(500);
            
            $statusBreakdown = [];
            $statusExamples = [];
            $allStatuses = [];
            
            // Check all customers (deliveries + collections)
            $allItems = array_merge($rawData['deliveries'] ?? [], $rawData['collections'] ?? []);
            
            foreach ($allItems as $item) {
                $status = strtolower($item['status'] ?? 'unknown');
                $cleanStatus = str_replace('wc-', '', $status);
                
                // Count each status
                $statusBreakdown[$cleanStatus] = ($statusBreakdown[$cleanStatus] ?? 0) + 1;
                $allStatuses[] = $cleanStatus;
                
                // Keep examples of each status
                if (!isset($statusExamples[$cleanStatus])) {
                    $statusExamples[$cleanStatus] = [
                        'customer_email' => $item['customer_email'] ?? 'unknown',
                        'id' => $item['id'],
                        'original_status' => $status,
                        'type' => $item['type'] ?? 'unknown'
                    ];
                }
            }
            
            // Check which statuses are considered "known" vs "other"
            $knownStatuses = ['active', 'processing', 'on-hold', 'cancelled', 'pending', 'completed', 'refunded'];
            $otherStatuses = [];
            
            foreach ($statusBreakdown as $status => $count) {
                if (!in_array($status, $knownStatuses)) {
                    $otherStatuses[$status] = $count;
                }
            }
            
            return response()->json([
                'success' => true,
                'total_customers' => count($allItems),
                'status_breakdown' => $statusBreakdown,
                'status_examples' => $statusExamples,
                'known_statuses' => $knownStatuses,
                'other_statuses' => $otherStatuses,
                'other_count' => array_sum($otherStatuses),
                'message' => 'Check other_statuses to see what the 8 "Other" customers actually are'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Compare service logic with manual calculation
     */
    public function compareWeekLogic(WpApiService $wpApi)
    {
        try {
            // Test the exact same customers as in Tinker
            $testIds = [224665, 224677, 225027, 225424, 227408, 227529, 227571];
            $results = [];
            
            foreach ($testIds as $id) {
                $subscription = \App\Models\WooCommerceOrder::find($id);
                if (!$subscription) continue;
                
                // Manual calculation (like Tinker)
                $parentId = $subscription->post_parent;
                $manualWeek = null;
                if ($parentId) {
                    $parent = \App\Models\WooCommerceOrder::find($parentId);
                    if ($parent) {
                        $week = \Carbon\Carbon::parse($parent->post_date)->format('W');
                        $manualWeek = ($week % 2 === 1) ? 'A' : 'B';
                    }
                }
                
                // Service logic calculation (our actual code)
                $storedWeekType = $subscription->getMeta('customer_week_type');
                $serviceWeek = null;
                
                if ($storedWeekType && in_array($storedWeekType, ['A', 'B'])) {
                    $serviceWeek = $storedWeekType;
                    $source = 'stored';
                } else {
                    if ($parentId) {
                        $parentOrder = \App\Models\WooCommerceOrder::find($parentId);
                        if ($parentOrder) {
                            $orderDate = \Carbon\Carbon::parse($parentOrder->post_date);
                            $orderWeek = (int) $orderDate->format('W');
                            $serviceWeek = ($orderWeek % 2 === 1) ? 'A' : 'B';
                            $source = 'parent_date';
                        } else {
                            $subscriptionDate = \Carbon\Carbon::parse($subscription->post_date);
                            $subscriptionWeek = (int) $subscriptionDate->format('W');
                            $serviceWeek = ($subscriptionWeek % 2 === 1) ? 'A' : 'B';
                            $source = 'subscription_fallback';
                        }
                    } else {
                        $subscriptionDate = \Carbon\Carbon::parse($subscription->post_date);
                        $subscriptionWeek = (int) $subscriptionDate->format('W');
                        $serviceWeek = ($subscriptionWeek % 2 === 1) ? 'A' : 'B';
                        $source = 'subscription_no_parent';
                    }
                }
                
                $results[] = [
                    'subscription_id' => $id,
                    'manual_calculation' => $manualWeek,
                    'service_calculation' => $serviceWeek,
                    'assignment_source' => $source ?? 'unknown',
                    'match' => $manualWeek === $serviceWeek,
                    'stored_preference' => $storedWeekType
                ];
            }
            
            return response()->json([
                'success' => true,
                'comparison_results' => $results,
                'all_match' => collect($results)->every(fn($r) => $r['match']),
                'message' => 'Compare manual vs service calculations'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update transformed data with collection day preferences in batch
     * @param array $transformedData The already transformed schedule data
     * @return array The updated data with collection days
     */
    private function addCollectionDaysToScheduleData($transformedData)
    {
        // Extract all customer IDs from collections
        $customerIds = [];
        
        // Collections in main schedule
        if (isset($transformedData['data'])) {
            foreach ($transformedData['data'] as $dateKey => $dateData) {
                if (!isset($dateData['collections'])) continue;
                
                foreach ($dateData['collections'] as $collection) {
                    if (!empty($collection['customer_id'])) {
                        $customerIds[] = $collection['customer_id'];
                    }
                }
            }
        }
        
        // Collections by status
        if (isset($transformedData['collectionsByStatus'])) {
            foreach ($transformedData['collectionsByStatus'] as $status => $dates) {
                foreach ($dates as $dateKey => $dateData) {
                    if (!isset($dateData['collections'])) continue;
                    
                    foreach ($dateData['collections'] as $collection) {
                        if (!empty($collection['customer_id'])) {
                            $customerIds[] = $collection['customer_id'];
                        }
                    }
                }
            }
        }
        
        // If no customers, return unchanged data
        if (empty($customerIds)) {
            return $transformedData;
        }
        
        // Get unique customer IDs
        $customerIds = array_unique($customerIds);
        
        // Get collection days directly from the database for better performance
        $collectionDays = [];
        
        // Use direct DB query to get all collection days at once - using proper table prefix
        $results = DB::connection('wordpress')
            ->table('usermeta') // the prefix is applied automatically by Laravel
            ->whereIn('user_id', $customerIds)
            ->where('meta_key', 'preferred_collection_day')
            ->select('user_id', 'meta_value')
            ->get();
            
        // Map results to collection days array
        foreach ($results as $result) {
            $collectionDays[$result->user_id] = $result->meta_value;
        }
        
        // Now update the collection data with the preferred collection days
        // Update collections in main schedule
        if (isset($transformedData['data'])) {
            foreach ($transformedData['data'] as $dateKey => &$dateData) {
                if (!isset($dateData['collections'])) continue;
                
                foreach ($dateData['collections'] as &$collection) {
                    if (!empty($collection['customer_id']) && isset($collectionDays[$collection['customer_id']])) {
                        $collection['preferred_collection_day'] = $collectionDays[$collection['customer_id']];
                    } else {
                        $collection['preferred_collection_day'] = 'Friday'; // Default
                    }
                }
            }
        }
        
        // Update collections by status
        if (isset($transformedData['collectionsByStatus'])) {
            foreach ($transformedData['collectionsByStatus'] as $status => &$dates) {
                foreach ($dates as $dateKey => &$dateData) {
                    if (!isset($dateData['collections'])) continue;
                    
                    foreach ($dateData['collections'] as &$collection) {
                        if (!empty($collection['customer_id']) && isset($collectionDays[$collection['customer_id']])) {
                            $collection['preferred_collection_day'] = $collectionDays[$collection['customer_id']];
                        } else {
                            $collection['preferred_collection_day'] = 'Friday'; // Default
                        }
                    }
                }
            }
        }
        
        return $transformedData;
    }

    /**
     * Test the collection days functionality
     */
    public function testCollectionDays()
    {
        try {
            // Get unique customer IDs with collection day preferences
            $customerIds = DB::connection('wordpress')
                ->table('usermeta') // Let Laravel apply the prefix
                ->where('meta_key', 'preferred_collection_day')
                ->select('user_id', 'meta_value')
                ->orderBy('meta_value')
                ->limit(20)
                ->get();
                
            // Map collection days for each customer
            $collectionDays = [];
            foreach ($customerIds as $row) {
                $collectionDays[$row->user_id] = $row->meta_value;
            }
            
            // Get the distribution of collection days
            $distributionQuery = DB::connection('wordpress')
                ->table('usermeta') // Let Laravel apply the prefix
                ->where('meta_key', 'preferred_collection_day')
                ->select('meta_value', DB::raw('COUNT(*) as count'))
                ->groupBy('meta_value')
                ->orderBy('count', 'desc');
                
            $distribution = $distributionQuery->get();
            
            return response()->json([
                'success' => true,
                'sample_collection_days' => $collectionDays,
                'total_preferences_set' => count($customerIds),
                'distribution' => $distribution,
                'message' => 'Collection day preferences are now loaded efficiently via direct database access'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Debug frequencies and week types for deliveries and collections
     */
    public function debugFrequencies()
    {
        try {
            // Get data from the WpApiService
            $wpApi = app(WpApiService::class);
            $rawData = $wpApi->getDeliveryScheduleData(500); // Get all subscriptions
            
            // Process data for both deliveries and collections
            $deliveryFrequencies = [];
            $deliveryWeekTypes = [];
            $collectionFrequencies = [];
            $collectionWeekTypes = [];
            
            // Process deliveries
            if (isset($rawData['deliveries'])) {
                foreach ($rawData['deliveries'] as $delivery) {
                    // Count frequencies
                    $frequency = strtolower($delivery['frequency'] ?? 'unknown');
                    if (!isset($deliveryFrequencies[$frequency])) {
                        $deliveryFrequencies[$frequency] = 0;
                    }
                    $deliveryFrequencies[$frequency]++;
                    
                    // Count week types
                    $weekType = $delivery['customer_week_type'] ?? 'unknown';
                    if (!isset($deliveryWeekTypes[$weekType])) {
                        $deliveryWeekTypes[$weekType] = 0;
                    }
                    $deliveryWeekTypes[$weekType]++;
                }
            }
            
            // Process collections
            if (isset($rawData['collections'])) {
                foreach ($rawData['collections'] as $collection) {
                    // Count frequencies
                    $frequency = strtolower($collection['frequency'] ?? 'unknown');
                    if (!isset($collectionFrequencies[$frequency])) {
                        $collectionFrequencies[$frequency] = 0;
                    }
                    $collectionFrequencies[$frequency]++;
                    
                    // Count week types
                    $weekType = $collection['customer_week_type'] ?? 'unknown';
                    if (!isset($collectionWeekTypes[$weekType])) {
                        $collectionWeekTypes[$weekType] = 0;
                    }
                    $collectionWeekTypes[$weekType]++;
                }
            }
            
            // Gather sample data
            $deliverySamples = [];
            $collectionSamples = [];
            
            // Sample of fortnightly deliveries
            if (isset($rawData['deliveries'])) {
                foreach ($rawData['deliveries'] as $delivery) {
                    if (strtolower($delivery['frequency'] ?? '') === 'fortnightly') {
                        $deliverySamples[] = [
                            'id' => $delivery['id'],
                            'name' => $delivery['name'],
                            'frequency' => $delivery['frequency'],
                            'customer_week_type' => $delivery['customer_week_type'],
                            'line_items' => array_map(fn($item) => [
                                'name' => $item['name'],
                                'meta' => $item['meta_data'] ?? []
                            ], $delivery['products'] ?? [])
                        ];
                        
                        if (count($deliverySamples) >= 3) {
                            break;
                        }
                    }
                }
            }
            
            // Sample of fortnightly collections
            if (isset($rawData['collections'])) {
                foreach ($rawData['collections'] as $collection) {
                    if (strtolower($collection['frequency'] ?? '') === 'fortnightly') {
                        $collectionSamples[] = [
                            'id' => $collection['id'],
                            'name' => $collection['name'],
                            'frequency' => $collection['frequency'],
                            'customer_week_type' => $collection['customer_week_type'],
                            'line_items' => array_map(fn($item) => [
                                'name' => $item['name'], 
                                'meta' => $item['meta_data'] ?? []
                            ], $collection['products'] ?? [])
                        ];
                        
                        if (count($collectionSamples) >= 3) {
                            break;
                        }
                    }
                }
            }
            
            // Create a sample for raw API response
            $rawSubscriptionSample = null;
            $transformedSubscriptionSample = null;
            
            if (!empty($rawData[0]) && is_array($rawData[0])) {
                // Find a fortnightly subscription
                foreach ($rawData as $sub) {
                    $isFortnightly = false;
                    if (isset($sub['line_items'][0]['meta_data'])) {
                        foreach ($sub['line_items'][0]['meta_data'] as $meta) {
                            if ($meta['key'] === 'frequency' && strtolower($meta['value']) === 'fortnightly') {
                                $isFortnightly = true;
                                break;
                            }
                        }
                    }
                    
                    if ($isFortnightly) {
                        $rawSubscriptionSample = $sub;
                        // Convert this to what would be in our array
                        $shippingTotal = (float) ($sub['shipping_total'] ?? 0);
                        $type = $shippingTotal > 0 ? 'delivery' : 'collection';
                        $transformedSubscriptionSample = [
                            'type' => $type,
                            'id' => $sub['id'],
                            'customer_id' => $sub['customer_id'],
                            'line_items' => $sub['line_items'] ?? [],
                            'meta_data' => $sub['meta_data'] ?? [],
                            'shipping_total' => $sub['shipping_total'] ?? 0,
                        ];
                        break;
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'deliveries' => [
                    'total' => count($rawData['deliveries'] ?? []),
                    'frequencies' => $deliveryFrequencies,
                    'week_types' => $deliveryWeekTypes,
                    'samples' => $deliverySamples,
                ],
                'collections' => [
                    'total' => count($rawData['collections'] ?? []),
                    'frequencies' => $collectionFrequencies,
                    'week_types' => $collectionWeekTypes,
                    'samples' => $collectionSamples,
                ],
                'raw_subscription_sample' => $rawSubscriptionSample,
                'transformed_subscription_sample' => $transformedSubscriptionSample,
                'message' => 'Debug information for frequencies and week types'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
