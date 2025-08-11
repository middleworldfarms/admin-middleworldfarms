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

    Route::get('/logs', function () {
        return view('admin.placeholder', ['title' => 'System Logs', 'description' => 'Activity logs and debugging coming soon']);
    })->name('admin.logs');

    // Simple test route
    Route::get('/test', function () {
        return 'Test route works!';
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
    
    // Backup management routes
    Route::prefix('backups')->name('admin.backups.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\BackupController::class, 'index'])->name('index');
        Route::post('/create', [App\Http\Controllers\Admin\BackupController::class, 'create'])->name('create');
        Route::post('/rename/{filename}', [App\Http\Controllers\Admin\BackupController::class, 'rename'])->name('rename');
        Route::delete('/delete/{filename}', [App\Http\Controllers\Admin\BackupController::class, 'delete'])->name('delete');
        Route::get('/download/{filename}', [App\Http\Controllers\Admin\BackupController::class, 'download'])->name('download');
        Route::post('/schedule', [App\Http\Controllers\Admin\BackupController::class, 'updateSchedule'])->name('schedule');
        Route::get('/status', [App\Http\Controllers\Admin\BackupController::class, 'status'])->name('status');
        Route::post('/upload', [App\Http\Controllers\Admin\BackupController::class, 'upload'])->name('upload');
        Route::get('/preview/{filename}', [App\Http\Controllers\Admin\BackupController::class, 'preview'])->name('preview');
        Route::post('/restore/{filename}', [App\Http\Controllers\Admin\BackupController::class, 'restore'])->name('restore');
    });
    
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
        Route::delete('/clear-test-data', [App\Http\Controllers\Admin\FarmOSDataController::class, 'clearTestData'])->name('clear-test-data');
        
        // Succession Planning routes - AI-powered succession planting
        Route::get('/succession-planning', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'index'])->name('succession-planning');
        Route::post('/succession-planning/generate', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'generate'])->name('succession-planning.generate');
        Route::post('/succession-planning/create-logs', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'createLogs'])->name('succession-planning.create-logs');
    });

    // Test route for AI timing
    Route::get('/test-ai-timing', function () {
        return view('test-ai-timing');
    });

    // AI API routes (outside farmOS group since they might be called differently)
    Route::prefix('api')->group(function () {
        Route::post('/ai/crop-timing', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getAICropTiming'])->name('api.ai.crop-timing');
        
        // 🌟 Holistic AI routes - Sacred geometry, lunar cycles, and biodynamic wisdom
        Route::post('/ai/holistic-recommendations', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getHolisticRecommendations'])->name('api.ai.holistic-recommendations');
        Route::get('/ai/moon-phase', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getMoonPhaseGuidance'])->name('api.ai.moon-phase');
        Route::post('/ai/sacred-spacing', [App\Http\Controllers\Admin\SuccessionPlanningController::class, 'getSacredSpacing'])->name('api.ai.sacred-spacing');
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

    // Debug route addresses for route planner
    Route::get('/debug-route-addresses', function() {
        $wpApi = app(\App\Services\WpApiService::class);
        $controller = new \App\Http\Controllers\Admin\RouteController(
            app('App\Services\RouteOptimizationService'),
            app('App\Services\DeliveryScheduleService'), 
            app('App\Services\DriverNotificationService'),
            app('App\Services\WPGoMapsService')
        );
        
        // The 4 correct delivery IDs for this week
        $correctIds = ['227748', '227726', '227673', '227581'];
        
        // Use reflection to access the private getDeliveriesByIds method
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('getDeliveriesByIds');
        $method->setAccessible(true);
        
        $result = $method->invoke($controller, $correctIds);
        
        return response()->json([
            'correct_ids' => $correctIds,
            'found_deliveries' => count($result),
            'addresses' => array_map(function($delivery) {
                return [
                    'id' => $delivery['id'],
                    'name' => $delivery['name'],
                    'address' => $delivery['address']
                ];
            }, $result)
        ]);
    })->name('debug.route-addresses');

    // Test route planner with this week's deliveries
    Route::get('/test-route-planner', function() {
        // The 4 correct delivery IDs for this week
        $correctIds = '227748,227726,227673,227581';
        
        // Redirect to route planner with the delivery IDs
        return redirect()->route('admin.routes.index', ['delivery_ids' => $correctIds]);
    })->name('test.route-planner');

    // Debug backup list
    Route::get('/debug-backup-list', function() {
        $controller = new \App\Http\Controllers\Admin\BackupController();
        
        try {
            $reflection = new ReflectionClass($controller);
            $method = $reflection->getMethod('getBackupList');
            $method->setAccessible(true);
            
            $backups = $method->invoke($controller);
            
            return response()->json([
                'success' => true,
                'backup_count' => count($backups),
                'backups' => $backups,
                'storage_path' => storage_path('app/backups'),
                'files_in_directory' => \Illuminate\Support\Facades\Storage::disk('local')->files('backups')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    })->name('debug.backup-list');

    // Subscription management endpoint
    Route::get('/manage-subscription/{email}', [DeliveryController::class, 'manageSubscription'])->name('manage.subscription');

    // Simple planting week (raw JSON, no AI)
    Route::get('/planting-week-simple', function(\App\Services\PlantingRecommendationService $svc) {
        return response()->json($svc->forWeek());
    })->name('admin.planting-week-simple');

    // farmOS sanity check (counts only)
    Route::get('/farmos-sanity', function(\App\Services\FarmOSApi $svc) {
        $harvest = $svc->getHarvestLogs();
        $plantRaw = $svc->getPlantAssets();
        $plantCount = 0;
        if (is_array($plantRaw)) {
            if (isset($plantRaw['data']) && is_array($plantRaw['data'])) { $plantCount = count($plantRaw['data']); }
            else { $plantCount = count($plantRaw); }
        }
        $land = $svc->getGeometryAssets();
        return response()->json([
            'harvest_logs_count' => is_array($harvest)? count($harvest) : 0,
            'plant_assets_count' => $plantCount,
            'land_assets_count' => is_array($land)? count($land) : 0,
            'timestamp' => now()->toDateTimeString(),
        ]);
    })->name('admin.farmos-sanity');

    // AI ingestion tasks (basic)
    Route::post('/api/ai/ingest', function(\Illuminate\Http\Request $request, \App\Services\AiIngestionService $ingest) {
        $task = $ingest->createTask($request->input('type'), $request->input('params', []), auth()->id());
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
        $land = collect($svc->getLandAssets(['status'=>'active']))->map(fn($a)=>[
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
