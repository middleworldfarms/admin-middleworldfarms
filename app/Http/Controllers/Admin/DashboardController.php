<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WpApiService;
use Illuminate\Support\Facades\Log;

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

    private function getDeliveryStats()
    {
        try {
            // Get delivery data from the WP API service
            $scheduleData = $this->wpApiService->getDeliveryScheduleData();
            
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
            
            // Get subscription data to estimate fortnightly info
            $scheduleData = $this->wpApiService->getDeliveryScheduleData();
            $totalSubscriptions = count($scheduleData['subscriptions'] ?? []);
            
            // Estimate weekly vs fortnightly split (this is a simplified calculation)
            $weeklyCount = intval($totalSubscriptions * 0.6); // Estimate 60% are weekly
            $fortnightlyCount = $totalSubscriptions - $weeklyCount;
            
            return [
                'current_week' => $currentWeekType,
                'current_iso_week' => $currentWeek,
                'weekly_count' => $weeklyCount,
                'fortnightly_count' => $fortnightlyCount,
                'active_this_week' => $fortnightlyCount, // Simplified estimate
                'next_week_type' => ($currentWeekType === 'A') ? 'B' : 'A',
                'fortnightly_subscriptions' => collect($scheduleData['subscriptions'] ?? [])
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

    /**
     * Get FarmOS map data for the dashboard with enhanced diagnostics
     */
    public function farmosMapData()
    {
        $started = microtime(true);
        $envClient = (bool) env('FARMOS_OAUTH_CLIENT_ID');
        $envSecret = (bool) env('FARMOS_OAUTH_CLIENT_SECRET');
        try {
            $farmosService = app(\App\Services\FarmOSApi::class);
            $geometryData = $farmosService->getGeometryAssets();
            $featureCount = is_array($geometryData) && isset($geometryData['features']) ? count($geometryData['features']) : 0;
            Log::info('FarmOS map data success', [
                'features' => $featureCount,
                'oauth_client_present' => $envClient,
                'oauth_secret_present' => $envSecret,
                'duration_ms' => round((microtime(true) - $started) * 1000, 1)
            ]);
            if ($featureCount === 0) {
                Log::warning('FarmOS map returned zero features');
            }
            return response()->json($geometryData ?: [
                'type' => 'FeatureCollection',
                'features' => []
            ]);
        } catch (\Throwable $e) {
            Log::error('FarmOS map data error', [
                'message' => $e->getMessage(),
                'oauth_client_present' => $envClient,
                'oauth_secret_present' => $envSecret,
                'trace_top' => collect(explode("\n", $e->getTraceAsString()))->take(5)->all(),
                'duration_ms' => round((microtime(true) - $started) * 1000, 1)
            ]);
            return response()->json([
                'type' => 'FeatureCollection',
                'features' => [],
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function plantingRecommendations()
    {
        try {
            $service = app(\App\Services\PlantingRecommendationService::class);
            return response()->json($service->forWeek());
        } catch (\Throwable $e) {
            Log::error('Planting recommendations error', ['msg' => $e->getMessage()]);
            return response()->json(['error' => 'Failed generating recommendations','detail'=>$e->getMessage()], 500);
        }
    }

    public function dataCatalog()
    {
        $svc = app(\App\Services\AiDataAccessService::class);
        return response()->json($svc->catalog());
    }
}
