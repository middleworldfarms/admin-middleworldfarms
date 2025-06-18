<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WpApiService;

class DashboardController extends Controller
{
    protected $wpApiService;

    public function __construct(WpApiService $wpApiService)
    {
        $this->wpApiService = $wpApiService;
    }

    public function index()
    {
        try {
            // Get delivery statistics
            $deliveryStats = $this->getDeliveryStats();
            
            // Get customer statistics
            $customerStats = $this->getCustomerStats();
            
            // Get fortnightly information
            $fortnightlyInfo = $this->getFortnightlyInfo();
            
            return view('admin.dashboard', compact('deliveryStats', 'customerStats', 'fortnightlyInfo'));
            
        } catch (\Exception $e) {
            // Fallback stats if database connection fails
            $deliveryStats = [
                'active' => 0,
                'collections' => 0,
                'total' => 0
            ];
            
            $customerStats = [
                'total' => 0,
                'active' => 0
            ];
            
            $fortnightlyInfo = [
                'current_week' => 'A',
                'weekly_count' => 0,
                'fortnightly_count' => 0,
                'active_this_week' => 0
            ];
            
            return view('admin.dashboard', compact('deliveryStats', 'customerStats', 'fortnightlyInfo'));
        }
    }

    public function getDeliveryStats()
    {
        try {
            // Get raw subscription data from the WP API service
            $rawData = $this->wpApiService->getDeliveryScheduleData(500);
            
            // Use the same transformation logic as DeliveryController
            $scheduleData = $this->transformScheduleData($rawData);
            
            // Calculate actual totals from transformed data (after duplicate removal)
            $totalDeliveries = 0;
            $totalCollections = 0;
            
            foreach ($scheduleData['data'] as $dateData) {
                $totalDeliveries += count($dateData['deliveries'] ?? []);
                $totalCollections += count($dateData['collections'] ?? []);
            }
            
            // Calculate status counts for deliveries
            $deliveryStatusCounts = [
                'active' => 0,
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
                        if (isset($deliveryStatusCounts[$status])) {
                            $deliveryStatusCounts[$status] += $count;
                        } else {
                            $deliveryStatusCounts['other'] += $count;
                        }
                    }
                }
            }
            
            // Calculate status counts for collections
            $collectionStatusCounts = [
                'active' => 0,
                'on-hold' => 0,
                'cancelled' => 0,
                'pending' => 0,
                'other' => 0
            ];
            
            if (isset($scheduleData['collectionsByStatus'])) {
                foreach ($scheduleData['collectionsByStatus'] as $status => $statusData) {
                    foreach ($statusData as $dateData) {
                        $count = count($dateData['collections'] ?? []);
                        if (isset($collectionStatusCounts[$status])) {
                            $collectionStatusCounts[$status] += $count;
                        } else {
                            $collectionStatusCounts['other'] += $count;
                        }
                    }
                }
            }
            
            // Calculate total active items (active deliveries + active collections + processing/other deliveries for WC compatibility)
            $totalActive = $deliveryStatusCounts['active'] + $deliveryStatusCounts['processing'] + $deliveryStatusCounts['other'] + $collectionStatusCounts['active'];
            
            $stats = [
                'active' => $totalActive,
                'collections' => $totalCollections,
                'total' => $totalDeliveries + $totalCollections,
                'processing' => $deliveryStatusCounts['processing'],
                'completed' => $deliveryStatusCounts['completed'],
                'on_hold' => $deliveryStatusCounts['on-hold'] + $collectionStatusCounts['on-hold'],
                'deliveries' => $totalDeliveries,
                'cancelled' => $deliveryStatusCounts['cancelled'] + $collectionStatusCounts['cancelled'],
                'pending' => $deliveryStatusCounts['pending'] + $collectionStatusCounts['pending']
            ];
            
            return $stats;
            
        } catch (\Exception $e) {
            return [
                'active' => 0,
                'collections' => 0,
                'total' => 0,
                'processing' => 0,
                'completed' => 0,
                'on_hold' => 0,
                'deliveries' => 0,
                'cancelled' => 0,
                'pending' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    private function getCustomerStats()
    {
        try {
            // Use the recent users method to get a count estimate
            $recentUsers = $this->wpApiService->getRecentUsers(100); // Get more users for better stats
            
            return [
                'total' => count($recentUsers),
                'active' => count($recentUsers), // All recent users are considered active
                'new_this_week' => collect($recentUsers)->filter(function($user) {
                    return isset($user['date_created']) && 
                           \Carbon\Carbon::parse($user['date_created'])->isAfter(now()->subWeek());
                })->count(),
                'orders_this_month' => 0 // Can be enhanced later
            ];
            
        } catch (\Exception $e) {
            return [
                'total' => 0, 
                'active' => 0,
                'new_this_week' => 0,
                'orders_this_month' => 0
            ];
        }
    }

    private function getFortnightlyInfo()
    {
        try {
            // Get current week information
            $currentWeek = (int) date('W');
            $currentWeekType = ($currentWeek % 2 === 1) ? 'A' : 'B';
            
            // Get subscription data and transform it properly
            $rawData = $this->wpApiService->getDeliveryScheduleData(500);
            $scheduleData = $this->transformScheduleData($rawData);
            
            // Count actual subscription types from the transformed data
            $weeklyCount = 0;
            $fortnightlyCount = 0;
            $activeThisWeek = 0;
            
            // Count from both deliveries and collections
            foreach ($scheduleData['data'] as $dateData) {
                foreach ($dateData['deliveries'] ?? [] as $delivery) {
                    // For now, assume deliveries are weekly (can be enhanced with actual frequency data)
                    $weeklyCount++;
                }
                
                foreach ($dateData['collections'] ?? [] as $collection) {
                    $frequency = strtolower($collection['frequency'] ?? 'weekly');
                    if (str_contains($frequency, 'fortnightly') || str_contains($frequency, 'biweekly')) {
                        $fortnightlyCount++;
                        // Simple estimation: assume half of fortnightly are active this week
                        if (rand(0, 1)) {
                            $activeThisWeek++;
                        }
                    } else {
                        $weeklyCount++;
                    }
                }
            }
            
            return [
                'current_week' => $currentWeekType,
                'current_iso_week' => $currentWeek,
                'weekly_count' => $weeklyCount,
                'fortnightly_count' => $fortnightlyCount,
                'active_this_week' => $activeThisWeek,
                'next_week_type' => ($currentWeekType === 'A') ? 'B' : 'A',
                'total_subscriptions' => $weeklyCount + $fortnightlyCount
            ];
            
        } catch (\Exception $e) {
            // Fallback data if fortnightly detection fails
            $currentWeek = (int) date('W');
            $currentWeekType = ($currentWeek % 2 === 1) ? 'A' : 'B';
            
            return [
                'current_week' => $currentWeekType,
                'current_iso_week' => $currentWeek,
                'weekly_count' => 0,
                'fortnightly_count' => 0,
                'active_this_week' => 0,
                'next_week_type' => ($currentWeekType === 'A') ? 'B' : 'A',
                'total_subscriptions' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getSystemHealth()
    {
        try {
            // Check API connection
            $apiStatus = $this->wpApiService->testConnection();
            
            // Check Laravel components
            $laravel = [
                'version' => app()->version(),
                'environment' => app()->environment(),
                'debug' => config('app.debug'),
                'timezone' => config('app.timezone')
            ];
            
            // Check disk space (basic)
            $diskSpace = disk_free_space('/') / (1024 * 1024 * 1024); // GB
            
            return [
                'api' => $apiStatus,
                'laravel' => $laravel,
                'disk_space_gb' => round($diskSpace, 2),
                'php_version' => PHP_VERSION,
                'memory_usage' => round(memory_get_usage(true) / (1024 * 1024), 2) . ' MB'
            ];
            
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'status' => 'error'
            ];
        }
    }

    private function transformScheduleData($rawData, $selectedWeek = null)
    {
        // If rawData is a flat API response (list of subscriptions), split into deliveries/collections
        if (isset($rawData[0]) && is_array($rawData[0])) {
            $subscriptions = $rawData;
            $rawData = ['deliveries' => [], 'collections' => []];
            foreach ($subscriptions as $sub) {
                $shippingTotal = (float) ($sub['shipping_total'] ?? 0);
                $type = $shippingTotal > 0 ? 'deliveries' : 'collections';
                
                // Extract frequency from line items meta_data
                $frequency = 'Weekly'; // Default
                if (isset($sub['line_items'][0]['meta_data'])) {
                    foreach ($sub['line_items'][0]['meta_data'] as $meta) {
                        if ($meta['key'] === 'frequency') {
                            $frequency = $meta['value'];
                            break;
                        }
                    }
                }
                
                $rawData[$type][] = [
                    'id'             => $sub['customer_id'],
                    'customer_id'    => $sub['customer_id'],
                    'status'         => $sub['status'],
                    'date_created'   => $sub['date_created'],
                    'customer_email' => $sub['billing']['email'] ?? '',
                    'name'           => trim(($sub['billing']['first_name'] ?? '') . ' ' . ($sub['billing']['last_name'] ?? '')),
                    'address'        => array_filter([
                        $sub['shipping']['address_1'] ?? '',
                        $sub['shipping']['address_2'] ?? '',
                        $sub['shipping']['city'] ?? '',
                        $sub['shipping']['state'] ?? '',
                        $sub['shipping']['postcode'] ?? ''
                    ]),
                    'products'       => array_map(fn($item) => ['quantity' => $item['quantity'], 'name' => $item['name']], $sub['line_items'] ?? []),
                    'phone'          => $sub['billing']['phone'] ?? '',
                    'email'          => $sub['billing']['email'] ?? '',
                    'frequency'      => $frequency,
                    'next_payment'   => $sub['next_payment_date_gmt'] ?? '',
                    'total'          => $sub['total'] ?? '0.00',
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
            'data_source' => 'wp_api',
            'message' => "Data loaded with duplicate prevention. Processed: {$totalProcessed} unique items, Skipped: {$duplicatesSkipped} duplicates"
        ];
    }
}
