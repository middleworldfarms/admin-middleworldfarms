<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DirectDatabaseService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DeliveryController extends Controller
{
    /**
     * Display the delivery schedule management page.
     */
    public function index(DirectDatabaseService $directDb)
    {
        try {
            // Test direct database connection
            $directDbStatus = $directDb->testConnection();
            
            // Get raw data from direct database
            $rawData = $directDb->getDeliveryScheduleData(100);
            
            // Transform data to match view expectations
            $scheduleData = $this->transformScheduleData($rawData);
            
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
                'error'
            ));
            
        } catch (\Exception $e) {            $scheduleData = ['data' => []];
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
                'error'
            ));
        }
    }

    /**
     * Transform raw database data to match view expectations
     */
    private function transformScheduleData($rawData)
    {
        if (!isset($rawData['deliveries']) || !isset($rawData['collections'])) {
            return ['data' => []];
        }

        $groupedData = [];
        $seenCustomers = []; // Track customers to prevent duplicates
        $seenEmails = []; // Track emails to prevent same customer appearing in both deliveries and collections
        
        // Group deliveries by date and apply fortnightly logic
        foreach ($rawData['deliveries'] as $delivery) {
            $date = \Carbon\Carbon::parse($delivery['date_created'])->format('Y-m-d');
            $dateFormatted = \Carbon\Carbon::parse($delivery['date_created'])->format('l, F j, Y');
            
            // Create a unique key to identify this customer/order combination
            $customerKey = $delivery['customer_email'] . '_' . $delivery['id'] . '_delivery';
            $email = strtolower(trim($delivery['customer_email']));
            
            // Skip if we've already seen this exact customer/order combo
            if (isset($seenCustomers[$customerKey])) {
                continue;
            }
            
            // Skip if we've already seen this email in either deliveries or collections
            if (isset($seenEmails[$email])) {
                continue;
            }
            
            $seenCustomers[$customerKey] = true;
            $seenEmails[$email] = 'delivery';
            
            // Apply fortnightly detection logic to the delivery (though most deliveries are one-time)
            $delivery = $this->enhanceWithFortnightlyInfo($delivery, 'delivery');
            
            if (!isset($groupedData[$date])) {
                $groupedData[$date] = [
                    'date_formatted' => $dateFormatted,
                    'deliveries' => [],
                    'collections' => []
                ];
            }
            
            $groupedData[$date]['deliveries'][] = $delivery;
        }
        
        // Group collections by date and apply fortnightly logic
        foreach ($rawData['collections'] as $collection) {
            $date = \Carbon\Carbon::parse($collection['date_created'])->format('Y-m-d');
            $dateFormatted = \Carbon\Carbon::parse($collection['date_created'])->format('l, F j, Y');
            
            // Create a unique key to identify this customer/subscription combination
            $customerKey = $collection['customer_email'] . '_' . $collection['id'] . '_collection';
            $email = strtolower(trim($collection['customer_email']));
            
            // Skip if we've already seen this exact customer/subscription combo
            if (isset($seenCustomers[$customerKey])) {
                continue;
            }
            
            // Skip if we've already seen this email (prioritize deliveries over collections)
            if (isset($seenEmails[$email])) {
                continue;
            }
            
            $seenCustomers[$customerKey] = true;
            $seenEmails[$email] = 'collection';
            
            // Apply fortnightly detection logic to the collection
            $collection = $this->enhanceWithFortnightlyInfo($collection);
            
            if (!isset($groupedData[$date])) {
                $groupedData[$date] = [
                    'date_formatted' => $dateFormatted,
                    'deliveries' => [],
                    'collections' => []
                ];
            }
            
            $groupedData[$date]['collections'][] = $collection;
        }
        
        // Sort by date (newest first)
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
            foreach ($dateData['collections'] as $collection) {
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
        
        // Sort each status group by date
        foreach ($collectionsByStatus as $status => $statusData) {
            krsort($collectionsByStatus[$status]);
        }
        
        // Group deliveries by order status for the subtabs
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
            foreach ($dateData['deliveries'] as $delivery) {
                // Use order status or default status for grouping
                $status = isset($delivery['status']) ? strtolower($delivery['status']) : 'processing';
                
                // Remove 'wc-' prefix if present (WooCommerce status format)
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
        
        // Sort each delivery status group by date
        foreach ($deliveriesByStatus as $status => $statusData) {
            krsort($deliveriesByStatus[$status]);
        }
        
        $totalProcessed = count($seenEmails);
        $duplicatesSkipped = (count($rawData['deliveries']) + count($rawData['collections'])) - $totalProcessed;
        
        return [
            'success' => true,
            'data' => $groupedData,
            'collectionsByStatus' => $collectionsByStatus,
            'deliveriesByStatus' => $deliveriesByStatus,
            'data_source' => 'direct_database',
            'message' => "Data loaded with duplicate prevention. Processed: {$totalProcessed} unique customers, Skipped: {$duplicatesSkipped} duplicates"
        ];
    }

    /**
     * Enhance collection/delivery data with fortnightly information
     */
    private function enhanceWithFortnightlyInfo($item, $type = 'collection')
    {
        // Get current week information
        $currentWeek = (int) date('W');
        $currentWeekType = ($currentWeek % 2 === 1) ? 'A' : 'B';
        
        // Get frequency from the item data (stored in meta or default based on type)
        $frequency = $item['frequency'] ?? ($type === 'delivery' ? 'One-time' : 'weekly');
        
        // If frequency is empty or not set, try to detect from subscription/order data
        if (empty($frequency) || $frequency === 'weekly') {
            // Check if this might be a fortnightly subscription by looking at meta data
            if (isset($item['billing_interval']) && $item['billing_interval'] == 2) {
                $frequency = 'Fortnightly';
            } elseif (isset($item['billing_period']) && strpos(strtolower($item['billing_period']), 'fortnight') !== false) {
                $frequency = 'Fortnightly';
            } elseif ($type === 'delivery') {
                $frequency = 'One-time';
            }
        }
        
        // Enhance the item with fortnightly data
        $item['frequency'] = $frequency;
        $item['current_week_type'] = $currentWeekType;
        $item['current_iso_week'] = $currentWeek;
        
        if (strtolower($frequency) === 'fortnightly') {
            // Use subscription/order ID to determine Week A/B assignment
            $itemId = $item['id'] ?? 0;
            $customerWeekType = ($itemId % 2 === 1) ? 'A' : 'B';
            
            $item['customer_week_type'] = $customerWeekType;
            $item['should_deliver_this_week'] = ($customerWeekType === $currentWeekType);
            $item['frequency_badge'] = $item['should_deliver_this_week'] ? 'warning' : 'secondary';
            $item['week_badge'] = $customerWeekType === 'A' ? 'success' : 'info';
        } elseif (strtolower($frequency) === 'weekly') {
            // Weekly deliveries/collections
            $item['customer_week_type'] = 'Weekly';
            $item['should_deliver_this_week'] = true;
            $item['frequency_badge'] = 'primary';
            $item['week_badge'] = 'primary';
        } else {
            // One-time orders
            $item['customer_week_type'] = 'One-time';
            $item['should_deliver_this_week'] = true;
            $item['frequency_badge'] = 'warning';
            $item['week_badge'] = 'warning';
        }
        
        return $item;
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
}