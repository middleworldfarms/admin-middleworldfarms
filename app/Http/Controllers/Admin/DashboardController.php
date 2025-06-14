<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DirectDatabaseService;

class DashboardController extends Controller
{
    protected $directDbService;

    public function __construct(DirectDatabaseService $directDbService)
    {
        $this->directDbService = $directDbService;
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

    private function getDeliveryStats()
    {
        try {
            // Get delivery data from the direct database service
            $scheduleData = $this->directDbService->getDeliveryScheduleData();
            
            $stats = [
                'active' => $scheduleData['total_deliveries'] ?? 0,
                'collections' => $scheduleData['total_collections'] ?? 0,
                'total' => ($scheduleData['total_deliveries'] ?? 0) + ($scheduleData['total_collections'] ?? 0),
                'processing' => $scheduleData['total_deliveries'] ?? 0,
                'completed' => 0,
                'on_hold' => 0
            ];
            
            return $stats;
            
        } catch (\Exception $e) {
            return [
                'active' => 0,
                'collections' => 0,
                'total' => 0,
                'processing' => 0,
                'completed' => 0,
                'on_hold' => 0
            ];
        }
    }

    private function getCustomerStats()
    {
        try {
            // Use the recent users method to get a count estimate
            $recentUsers = $this->directDbService->getRecentUsers(100); // Get more users for better stats
            
            return [
                'total' => count($recentUsers),
                'active' => count($recentUsers), // All recent users are considered active
                'new_this_week' => collect($recentUsers)->filter(function($user) {
                    return isset($user['user_registered']) && 
                           \Carbon\Carbon::parse($user['user_registered'])->isAfter(now()->subWeek());
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
            
            // Get fortnightly schedule data
            $fortnightlyData = $this->directDbService->getFortnightlySchedule($currentWeekType);
            
            // Get weekly subscriptions count
            $weeklyCount = $this->directDbService->getWeeklySubscriptionsCount();
            
            return [
                'current_week' => $currentWeekType,
                'current_iso_week' => $currentWeek,
                'weekly_count' => $weeklyCount,
                'fortnightly_count' => $fortnightlyData['count'] ?? 0,
                'active_this_week' => $fortnightlyData['count'] ?? 0,
                'next_week_type' => ($currentWeekType === 'A') ? 'B' : 'A',
                'fortnightly_subscriptions' => $fortnightlyData['subscriptions'] ?? collect()
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
                'fortnightly_subscriptions' => collect(),
                'error' => $e->getMessage()
            ];
        }
    }

    public function getSystemHealth()
    {
        try {
            // Check database connection
            $dbStatus = $this->directDbService->testConnection();
            
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
                'database' => $dbStatus,
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
}
