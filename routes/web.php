<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeliveryController;
use App\Http\Controllers\Auth\LoginController;
use App\Services\DeliveryScheduleService;
use Illuminate\Support\Facades\Route;

// Public routes (no authentication required)
Route::get('/', function () {
    return redirect(config('app.url') . '/admin/login');
});

// Authentication routes
Route::prefix('admin')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('admin.login.form');
    Route::post('/login', [LoginController::class, 'login'])->name('admin.login');
    Route::post('/logout', [LoginController::class, 'logout'])->name('admin.logout');
});

// Protected admin routes (require authentication)
Route::middleware(['admin.auth'])->prefix('admin')->group(function () {
    
    // Admin dashboard route
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Weekly planting recommendations
    Route::get('/planting-recommendations', [DashboardController::class, 'plantingRecommendations'])->name('admin.planting-recommendations');

    // AI data catalog
    Route::get('/api/data-catalog', [DashboardController::class, 'dataCatalog'])->name('admin.api.data-catalog');

    // FarmOS map data endpoint
    Route::get('/farmos-map-data', [DashboardController::class, 'farmosMapData'])->name('admin.farmos-map-data');

    // Delivery management routes
    Route::get('/deliveries', [DeliveryController::class, 'index'])->name('admin.deliveries.index');
    Route::get('/diagnostic-subscriptions', [DeliveryController::class, 'diagnosticSubscriptions'])->name('admin.diagnostic-subscriptions');
    
    // DEBUG: Shipping totals analysis
    Route::get('/debug-shipping-totals', [DeliveryController::class, 'debugShippingTotals'])->name('admin.debug-shipping-totals');
    
    // DEBUG: Specific customer analysis
    Route::get('/debug-customer/{email}', [DeliveryController::class, 'debugSpecificCustomer'])->name('admin.debug-customer');
    
    // DEBUG: WooCommerce subscription structure analysis
    Route::get('/debug-subscription-structure', [DeliveryController::class, 'debugSubscriptionStructure'])->name('admin.debug-subscription-structure');
    
    // Debug endpoint for specific customer analysis
    Route::get('/debug-bethany', [DeliveryController::class, 'debugBethany'])->name('debug.bethany');
    
    Route::post('/customers/update-week', [DeliveryController::class, 'updateCustomerWeek'])->name('admin.customers.update-week');

    // Customer management routes
    Route::prefix('users')->name('admin.users.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\UserSwitchingController::class, 'index'])->name('index');
        Route::get('/search', [App\Http\Controllers\Admin\UserSwitchingController::class, 'search'])->name('search');
        Route::get('/recent', [App\Http\Controllers\Admin\UserSwitchingController::class, 'getRecentUsers'])->name('recent');
        Route::get('/test', [App\Http\Controllers\Admin\UserSwitchingController::class, 'test'])->name('test');
        Route::post('/switch/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'switchToUser'])->name('switch');
        Route::get('/switch-redirect/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'switchAndRedirect'])->name('switch-redirect');
        Route::post('/switch-by-email', [App\Http\Controllers\Admin\UserSwitchingController::class, 'switchByEmail'])->name('switch-by-email');
        Route::post('/get-subscription-url', [App\Http\Controllers\Admin\UserSwitchingController::class, 'getSubscriptionUrl'])->name('get-subscription-url');
        Route::get('/subscription-redirect/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'subscriptionRedirect'])->name('subscription-redirect');
        Route::get('/details/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'getUserDetails'])->name('details');
        Route::get('/redirect/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'redirect'])->name('redirect');
    });

    // Customer Management routes (actual customer management, not user switching)
    Route::prefix('customers')->name('admin.customers.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\CustomerManagementController::class, 'index'])->name('index');
        Route::post('/switch/{userId}', [App\Http\Controllers\Admin\CustomerManagementController::class, 'switchToUser'])->name('switch');
        Route::get('/details/{userId}', [App\Http\Controllers\Admin\CustomerManagementController::class, 'details'])->name('details');
    });

    // Analytics and Reports routes (placeholders for future implementation)
    Route::get('/reports', function () {
        return view('admin.placeholder', ['title' => 'Reports', 'description' => 'Delivery and sales reports coming soon']);
    })->name('admin.reports');

    Route::get('/analytics', function () {
        return view('admin.placeholder', ['title' => 'Analytics', 'description' => 'Advanced analytics dashboard coming soon']);
    })->name('admin.analytics');

    // System routes
    Route::get('/settings', [App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('admin.settings');
    Route::post('/settings', [App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('admin.settings.update');
    Route::get('/settings/reset', [App\Http\Controllers\Admin\SettingsController::class, 'reset'])->name('admin.settings.reset');
    Route::get('/settings/api', [App\Http\Controllers\Admin\SettingsController::class, 'api'])->name('admin.settings.api');
    
    // Server monitoring routes for IONOS I/O throttling detection
    Route::get('/settings/server-metrics', [App\Http\Controllers\Admin\SettingsController::class, 'serverMetrics'])->name('admin.settings.server-metrics');
    Route::post('/settings/test-io-speed', [App\Http\Controllers\Admin\SettingsController::class, 'testIOSpeed'])->name('admin.settings.test-io-speed');
    Route::post('/settings/test-db-performance', [App\Http\Controllers\Admin\SettingsController::class, 'testDatabasePerformance'])->name('admin.settings.test-db-performance');

    // Variety Audit routes
    Route::post('/variety-audit/{id}/approve', [App\Http\Controllers\Admin\SettingsController::class, 'approveAudit']);
    Route::post('/variety-audit/{id}/reject', [App\Http\Controllers\Admin\SettingsController::class, 'rejectAudit']);
    Route::post('/variety-audit/{id}/update-suggestion', [App\Http\Controllers\Admin\SettingsController::class, 'updateSuggestion']);
    Route::post('/variety-audit/bulk-approve', [App\Http\Controllers\Admin\SettingsController::class, 'bulkApproveAudit']);
    Route::post('/variety-audit/bulk-reject', [App\Http\Controllers\Admin\SettingsController::class, 'bulkRejectAudit']);
    Route::post('/variety-audit/approve-high-confidence', [App\Http\Controllers\Admin\SettingsController::class, 'approveHighConfidence']);
    Route::post('/variety-audit/apply-approved', [App\Http\Controllers\Admin\SettingsController::class, 'applyApprovedAudit']);
    Route::get('/variety-audit/stats', [App\Http\Controllers\Admin\SettingsController::class, 'auditStats']);
    Route::get('/variety-audit/status', [App\Http\Controllers\Admin\SettingsController::class, 'auditStatus']);
    Route::post('/variety-audit/start', [App\Http\Controllers\Admin\SettingsController::class, 'auditStart']);
    Route::post('/variety-audit/pause', [App\Http\Controllers\Admin\SettingsController::class, 'auditPause']);
    Route::post('/variety-audit/resume', [App\Http\Controllers\Admin\SettingsController::class, 'auditResume']);

    Route::get('/logs', function () {
        return view('admin.placeholder', ['title' => 'System Logs', 'description' => 'Activity logs and debugging coming soon']);
    })->name('admin.logs');

    // Chatbot settings page
    Route::get('/chatbot-settings', function () {
        // Check AI service status
        $aiStatus = ['status' => (@file_get_contents('http://localhost:8005/health') !== false) ? 'online' : 'offline'];
        $knowledgeStatus = ['status' => 'unavailable']; // Default status, update as needed
        return view('admin.chatbot-settings', compact('aiStatus', 'knowledgeStatus'));
    })->name('admin.chatbot-settings');

    // Chatbot API endpoint
    Route::post('/chatbot-api', function (\Illuminate\Http\Request $request) {
        try {
            $data = [
                'question' => $request->input('message'),
                'context' => 'general'
            ];
            
            $response = file_get_contents('http://localhost:8005/ask', false, stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json',
                    'content' => json_encode($data)
                ]
            ]));
            
            if ($response !== false) {
                $result = json_decode($response, true);
                return response()->json(['success' => true, 'response' => $result['answer'] ?? 'Response received']);
            } else {
                return response()->json(['success' => false, 'error' => 'AI service unavailable']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    })->name('admin.chatbot-api');

    // Simple test route
    Route::get('/test', function () {
        return response()->json(['message' => 'Admin system is working', 'timestamp' => now()]);
    });

    // Conversation management routes (ADMIN ONLY)
    Route::prefix('conversations')->name('admin.conversations.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\ConversationAdminController::class, 'index'])->name('index');
        Route::get('/statistics', [App\Http\Controllers\Admin\ConversationAdminController::class, 'statistics'])->name('statistics');
        Route::get('/search', [App\Http\Controllers\Admin\ConversationAdminController::class, 'search'])->name('search');
        Route::get('/export-training', [App\Http\Controllers\Admin\ConversationAdminController::class, 'exportTraining'])->name('export-training');
        Route::post('/purge-old', [App\Http\Controllers\Admin\ConversationAdminController::class, 'purgeOld'])->name('purge-old');
        Route::get('/{id}', [App\Http\Controllers\Admin\ConversationAdminController::class, 'show'])->name('show');
        Route::delete('/{id}', [App\Http\Controllers\Admin\ConversationAdminController::class, 'destroy'])->name('destroy');
    });


    // Route planning and optimization routes
    Route::prefix('routes')->name('admin.routes.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\RouteController::class, 'index'])->name('index');
        Route::post('/optimize', [App\Http\Controllers\Admin\RouteController::class, 'optimize'])->name('optimize');
        Route::post('/send-to-driver', [App\Http\Controllers\Admin\RouteController::class, 'sendToDriver'])->name('send-to-driver');
        Route::post('/send-to-driver-sms', [App\Http\Controllers\Admin\RouteController::class, 'sendToDriverSMS'])->name('send-to-driver-sms');
        Route::get('/map-data', [App\Http\Controllers\Admin\RouteController::class, 'getMapData'])->name('map-data');
        Route::post('/create-shareable-map', [App\Http\Controllers\Admin\RouteController::class, 'createShareableMap'])->name('create-shareable-map');
        Route::get('/wp-go-maps-data', [App\Http\Controllers\Admin\RouteController::class, 'getWPGoMapsData'])->name('wp-go-maps-data');
    });

    // New route planner page
    Route::get('/deliveries/route-planner', [\App\Http\Controllers\Admin\RouteController::class, 'index'])->name('admin.route-planner');
    
    
    // Print packing slips
    Route::get('/deliveries/print', [App\Http\Controllers\Admin\DeliveryController::class, 'print'])->name('admin.deliveries.print');
    
    // Print actual packing slips (multiple per sheet)
    Route::get('/deliveries/print-slips', [App\Http\Controllers\Admin\DeliveryController::class, 'printSlips'])->name('admin.deliveries.print-slips');

    // Completion tracking routes
    Route::post('/deliveries/mark-complete', [App\Http\Controllers\Admin\DeliveryController::class, 'markComplete'])->name('admin.deliveries.mark-complete');
    Route::post('/deliveries/unmark-complete', [App\Http\Controllers\Admin\DeliveryController::class, 'unmarkComplete'])->name('admin.deliveries.unmark-complete');

    // FarmOS Integration routes
    Route::prefix('farmos')->name('admin.farmos.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\FarmOSDataController::class, 'index'])->name('dashboard');
        Route::get('/planting-chart', [App\Http\Controllers\Admin\FarmOSDataController::class, 'plantingChart'])->name('planting-chart');
        Route::get('/harvests', [App\Http\Controllers\Admin\FarmOSDataController::class, 'harvests'])->name('harvests');
        Route::get('/stock', [App\Http\Controllers\Admin\FarmOSDataController::class, 'stock'])->name('stock');
        Route::post('/stock', [App\Http\Controllers\Admin\FarmOSDataController::class, 'storeStock'])->name('stock.store');
        Route::get('/crop-plans', [App\Http\Controllers\Admin\FarmOSDataController::class, 'cropPlans'])->name('crop-plans');
        Route::post('/crop-plans', [App\Http\Controllers\Admin\FarmOSDataController::class, 'storeCropPlan'])->name('crop-plans.store');
        
        // Data sync routes
        Route::post('/sync-harvests', [App\Http\Controllers\Admin\FarmOSDataController::class, 'syncHarvests'])->name('sync-harvests');
        Route::post('/sync-to-stock', [App\Http\Controllers\Admin\FarmOSDataController::class, 'syncToStock'])->name('sync-to-stock');
        Route::post('/sync-varieties', [App\Http\Controllers\Admin\FarmOSDataController::class, 'syncVarieties'])->name('sync-varieties');
        Route::delete('/clear-test-data', [App\Http\Controllers\Admin\FarmOSDataController::class, 'clearTestData'])->name('clear-test-data');
        
        // Succession Planning routes - AI-powered succession planting
        Route::get('/succession-planning', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'index'])->name('succession-planning');
        Route::post('/succession-planning/calculate', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'calculate'])->name('succession-planning.calculate');
        Route::post('/succession-planning/generate', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'generate'])->name('succession-planning.generate');
        Route::post('/succession-planning/create-logs', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'createLogs'])->name('succession-planning.create-logs');
        Route::post('/succession-planning/create-single-log', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'createSingleLog'])->name('succession-planning.create-single-log');
        Route::post('/succession-planning/harvest-window', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getOptimalHarvestWindow'])->name('succession-planning.harvest-window');
        Route::post('/succession-planning/seeding-transplant', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getSeedingTransplantData'])->name('succession-planning.seeding-transplant');
        Route::post('/succession-planning/chat', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'chat'])->name('succession-planning.chat');
        Route::post('/succession-planning/analyze-cash-crops', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'analyzeCashCrops'])->name('succession-planning.analyze-cash-crops');
        
        // API log submission for Quick Forms
        Route::post('/succession-planning/submit-log', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'submitLog'])->name('succession-planning.submit-log');
        Route::post('/succession-planning/submit-all-logs', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'submitAllLogs'])->name('succession-planning.submit-all-logs');
        
        // Variety details endpoint for AI processing
        Route::get('/succession-planning/varieties/{varietyId}', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getVariety'])->name('succession-planning.variety');
        
        // Bed occupancy data for timeline visualization
        Route::get('/succession-planning/bed-occupancy', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getBedOccupancy'])->name('succession-planning.bed-occupancy');
        
        // AI service management routes
        Route::get('/succession-planning/ai-status', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getAIStatus'])->name('succession-planning.ai-status');
        Route::post('/succession-planning/wake-ai', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'wakeUpAI'])->name('succession-planning.wake-ai');
        
        // Image proxy for FarmOS variety images
        Route::get('/variety-image/{fileId}', [App\Http\Controllers\Admin\FarmOSDataController::class, 'proxyVarietyImage'])->name('variety-image');
        
        // Quick Form routes - serve the unified quick form template
        Route::get('/quick/seeding', function () {
            return view('admin.farmos.quick-forms.quick-planting');
        })->name('quick.seeding');
        Route::get('/quick/transplant', function () {
            return view('admin.farmos.quick-forms.quick-planting');
        })->name('quick.transplant');
        Route::get('/quick/harvest', function () {
            return view('admin.farmos.quick-forms.quick-planting');
        })->name('quick.harvest');

        // Proxy routes for FarmOS Quick Forms with pre-filling
        Route::get('/proxy/quick/seeding', [App\Http\Controllers\Admin\FarmOSProxyController::class, 'proxySeedingForm'])->name('proxy.quick.seeding');
        Route::get('/proxy/quick/transplant', [App\Http\Controllers\Admin\FarmOSProxyController::class, 'proxyTransplantForm'])->name('proxy.quick.transplant');
        Route::get('/proxy/quick/harvest', [App\Http\Controllers\Admin\FarmOSProxyController::class, 'proxyHarvestForm'])->name('proxy.quick.harvest');
    });

    // Test route for AI timing
    Route::get('/test-ai-timing', function () {
        return view('test-ai-timing');
    });

    // Weather Integration routes
    Route::prefix('weather')->name('admin.weather.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\WeatherController::class, 'index'])->name('dashboard');
        Route::get('/current', [App\Http\Controllers\Admin\WeatherController::class, 'getCurrentWeather'])->name('current');
        Route::get('/forecast', [App\Http\Controllers\Admin\WeatherController::class, 'getForecast'])->name('forecast');
        Route::get('/frost-risk', [App\Http\Controllers\Admin\WeatherController::class, 'getFrostRisk'])->name('frost-risk');
        Route::post('/planting-analysis', [App\Http\Controllers\Admin\WeatherController::class, 'analyzePlantingWindow'])->name('planting-analysis');
        Route::get('/growing-degree-days', [App\Http\Controllers\Admin\WeatherController::class, 'getGrowingDegreeDays'])->name('growing-degree-days');
        Route::get('/historical', [App\Http\Controllers\Admin\WeatherController::class, 'getHistoricalWeather'])->name('historical');
        Route::get('/alerts', [App\Http\Controllers\Admin\WeatherController::class, 'getWeatherAlerts'])->name('alerts');
        Route::get('/field-work', [App\Http\Controllers\Admin\WeatherController::class, 'getFieldWorkRecommendations'])->name('field-work');
    });

    // AI API routes (outside farmOS group since they might be called differently)
    Route::prefix('api')->group(function () {
        Route::post('/ai/crop-timing', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getAICropTiming'])->name('api.ai.crop-timing');
        
        // 🌟 Holistic AI routes - Sacred geometry, lunar cycles, and biodynamic wisdom
        Route::post('/ai/holistic-recommendations', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getHolisticRecommendations'])->name('api.ai.holistic-recommendations');
        Route::get('/ai/moon-phase', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getMoonPhaseGuidance'])->name('api.ai.moon-phase');
        Route::post('/ai/sacred-spacing', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getSacredSpacing'])->name('api.ai.sacred-spacing');
    });

    // Stripe Payment Integration Routes
    Route::prefix('stripe')->name('admin.stripe.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\StripeController::class, 'index'])->name('dashboard');
        Route::get('/payments', [App\Http\Controllers\Admin\StripeController::class, 'getPayments'])->name('payments');
        Route::get('/statistics', [App\Http\Controllers\Admin\StripeController::class, 'getStatistics'])->name('statistics');
        Route::get('/subscriptions', [App\Http\Controllers\Admin\StripeController::class, 'getSubscriptions'])->name('subscriptions');
        Route::get('/customers/search', [App\Http\Controllers\Admin\StripeController::class, 'searchCustomers'])->name('customers.search');
    });

    // AI Gateway test route (internal)
    Route::get('/api/ai/gateway', function(\Illuminate\Http\Request $request, \App\Services\AiGatewayService $gw) {
        $service = $request->query('service','farmos');
        $method = $request->query('method','getPlantAssets');
        $params = $request->query('params', []);
        if (is_string($params)) { // allow JSON in query
            $decoded = json_decode($params, true);
            if (json_last_error() === JSON_ERROR_NONE) $params = $decoded; else $params = [];
        }
        return response()->json($gw->call($service, $method, $params));
    })->name('admin.ai.gateway-test');

    // Debug endpoint for delivery/collection classification verification
    Route::get('/debug-classification', [DeliveryController::class, 'debugClassification'])->name('debug.classification');

    // Debug endpoint for Pauline Moore's duplicate order analysis
    Route::get('/debug-pauline', [DeliveryController::class, 'debugPauline'])->name('debug.pauline');

    // Debug route addresses for route planner (simplified)
    Route::get('/debug-route-addresses', function() {
        return response()->json([
            'message' => 'Route address debugging endpoint - contact dev team for specific delivery analysis',
            'timestamp' => now()->toDateTimeString()
        ]);
    })->name('debug.route-addresses');

    // Test route planner with this week's deliveries
    Route::get('/test-route-planner', function() {
        // The 4 correct delivery IDs for this week
        $correctIds = '227748,227726,227673,227581';
        
        // Redirect to route planner with the delivery IDs
        return redirect()->route('admin.routes.index', ['delivery_ids' => $correctIds]);
    })->name('test.route-planner');

    // Subscription management endpoint
    Route::get('/manage-subscription/{email}', [DeliveryController::class, 'manageSubscription'])->name('manage.subscription');

    // Simple planting week (raw JSON, no AI)
    Route::get('/planting-week-simple', function(\App\Services\PlantingRecommendationService $svc) {
        return response()->json($svc->forWeek());
    })->name('admin.planting-week-simple');

    // farmOS sanity check (counts only)
    Route::get('/farmos-sanity', function(\App\Services\FarmOSApi $svc) {
        $harvest = $svc->getHarvestLogs();
        $plantTypes = $svc->getPlantTypes();
        $plantCount = 0;
        if (is_array($plantTypes)) {
            if (isset($plantTypes['data']) && is_array($plantTypes['data'])) { $plantCount = count($plantTypes['data']); }
            else { $plantCount = count($plantTypes); }
        }
        $land = $svc->getGeometryAssets();
        return response()->json([
            'harvest_logs_count' => is_array($harvest)? count($harvest) : 0,
            'plant_types_count' => $plantCount,
            'land_assets_count' => is_array($land)? count($land) : 0,
            'timestamp' => now()->toDateTimeString(),
        ]);
    })->name('admin.farmos-sanity');

    // AI ingestion tasks (basic)
    Route::post('/api/ai/ingest', function(\Illuminate\Http\Request $request, \App\Services\AiIngestionService $ingest) {
        $userId = 1; // Default admin user ID for API calls
        $task = $ingest->createTask($request->input('type'), $request->input('params', []), $userId);
        return response()->json(['task_id' => $task->id, 'status' => $task->status]);
    })->name('admin.ai.ingest.create');

    Route::post('/api/ai/ingest/run-pending', function(\App\Services\AiIngestionService $ingest) {
        $count = $ingest->runPending();
        return ['ran' => $count];
    })->name('admin.ai.ingest.run');

    Route::get('/api/ai/ingest/tasks', function() {
        return \App\Models\AiIngestionTask::orderByDesc('id')->limit(50)->get();
    })->name('admin.ai.ingest.list');

    // farmOS UUID helper for creating plant assets
    Route::get('/farmos/uuid-helper', function(\App\Services\FarmOSApi $svc) {
        $plantTypes = collect($svc->getPlantTypes())->map(fn($t)=>[
            'id' => $t['id'] ?? null,
            'name' => $t['attributes']['name'] ?? null,
        ])->filter(fn($r)=>$r['id'] && $r['name'])->values();
        $varieties = collect($svc->getVarieties())->map(fn($t)=>[
            'id' => $t['id'] ?? null,
            'name' => $t['attributes']['name'] ?? null,
        ])->filter(fn($r)=>$r['id'] && $r['name'])->values();
        $land = collect($svc->getGeometryAssets(['status'=>'active']))->map(fn($a)=>[
            'id' => $a['id'] ?? null,
            'name' => $a['attributes']['name'] ?? null,
        ])->filter(fn($r)=>$r['id'] && $r['name'])->values();
        return response()->json([
            'plant_types' => $plantTypes,
            'varieties' => $varieties,
            'land_assets' => $land,
        ]);
    })->name('admin.farmos.uuid-helper');
});
