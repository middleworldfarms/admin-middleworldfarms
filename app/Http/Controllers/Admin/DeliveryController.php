<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DeliveryScheduleService;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    /**
     * Display the delivery schedule management page.
     */
    public function index(DeliveryScheduleService $service)
    {
        try {
            // Get enhanced schedule data with frequency logic
            $scheduleData = $service->getEnhancedSchedule();
            $api_test = [
                'connection' => $service->testConnection(),
                'auth' => $service->testAuth()
            ];
            $error = null;
            
            return view('admin.deliveries.fixed', compact('scheduleData', 'api_test', 'error'));
        } catch (\Exception $e) {
            $scheduleData = null;
            $api_test = ['connection' => ['success' => false], 'auth' => ['success' => false]];
            $error = $e->getMessage();
            
            return view('admin.deliveries.fixed', compact('scheduleData', 'api_test', 'error'));
        }
    }

    /**
     * API test endpoint for debugging
     */
    public function apiTest(DeliveryScheduleService $service)
    {
        try {
            $tests = [
                'connection' => $service->testConnection(),
                'auth' => $service->testAuth(),
                'schedule' => $service->getSchedule()
            ];
            
            return response()->json([
                'success' => true,
                'tests' => $tests,
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