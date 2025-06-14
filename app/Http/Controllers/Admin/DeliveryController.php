<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DirectDatabaseService;
use Illuminate\Support\Facades\DB;

class DeliveryController extends Controller
{
    /**
     * Display the delivery schedule management page.
     */
    public function index(DirectDatabaseService $directDb)
    {
        try {
            // Get selected week from request, default to current week
            $selectedWeek = request('week', date('W'));
            
            // Test direct database connection
            $directDbStatus = $directDb->testConnection();
            
            // Get raw data from direct database - increased limit for scaling
            $rawData = $directDb->getDeliveryScheduleData(500);
            
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
        if (!isset($rawData['deliveries']) && !isset($rawData['collections'])) {
            return ['data' => []];
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
        
        return [
            'success' => true,
            'data' => $groupedData,
            'collectionsByStatus' => $collectionsByStatus,
            'deliveriesByStatus' => $deliveriesByStatus,
            'data_source' => 'direct_database',
            'message' => "Data loaded with duplicate prevention. Processed: {$totalProcessed} unique items, Skipped: {$duplicatesSkipped} duplicates"
        ];
    }

    /**
     * API test endpoint for debugging
     */
    public function apiTest(DirectDatabaseService $directDb)
    {
        try {
            $tests = [
                'direct_database_connection' => $directDb->testConnection(),
                'recent_users' => $directDb->getRecentUsers(3),
                'delivery_data' => $directDb->getDeliveryScheduleData(5),
                'woocommerce_settings' => $directDb->getWooCommerceSettings()
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
    public function updateCustomerWeek(DirectDatabaseService $directDb)
    {
        try {
            $customerId = request('customer_id');
            $weekType = request('week_type');
            
            if (!$customerId || !in_array($weekType, ['A', 'B'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid customer ID or week type'
                ], 400);
            }
            
            // Update the subscription meta for customer week type
            $subscription = \App\Models\WooCommerceOrder::find($customerId);
            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription not found'
                ], 404);
            }
            
            // Update or create the customer_week_type meta
            $metaUpdated = DB::connection('wordpress_direct')
                ->table('wp_postmeta')
                ->updateOrInsert(
                    [
                        'post_id' => $customerId,
                        'meta_key' => 'customer_week_type'
                    ],
                    [
                        'meta_value' => $weekType
                    ]
                );
            
            return response()->json([
                'success' => true,
                'message' => "Customer week type updated to Week {$weekType}",
                'customer_id' => $customerId,
                'week_type' => $weekType
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer week: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Diagnostic method to check subscription statuses and counts
     */
    public function diagnosticSubscriptions(DirectDatabaseService $directDb)
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
            $serviceData = $directDb->getDeliveryScheduleData(200); // Increase limit for testing
            
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
    public function testActiveFilter(DirectDatabaseService $directDb)
    {
        try {
            // Test the updated service
            $rawData = $directDb->getDeliveryScheduleData(500);
            
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
    public function debugWeekAssignment(DirectDatabaseService $directDb)
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
    public function debugDeliverySchedule(DirectDatabaseService $directDb)
    {
        try {
            // Get the same data that the main page uses
            $rawData = $directDb->getDeliveryScheduleData(500);
            
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
    public function debugSpecificCustomers(DirectDatabaseService $directDb)
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
    public function debugPageDisplay(DirectDatabaseService $directDb)
    {
        try {
            // Get the exact same data that the main delivery page uses
            $rawData = $directDb->getDeliveryScheduleData(500);
            
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
    public function simpleCount(DirectDatabaseService $directDb)
    {
        try {
            $rawData = $directDb->getDeliveryScheduleData(500);
            
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
    public function debugCustomerStatuses(DirectDatabaseService $directDb)
    {
        try {
            // Get the raw data
            $rawData = $directDb->getDeliveryScheduleData(500);
            
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
    public function compareWeekLogic(DirectDatabaseService $directDb)
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
}
