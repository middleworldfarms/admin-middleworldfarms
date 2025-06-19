<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeliveryController;
use App\Http\Controllers\Auth\LoginController;
use App\Services\DeliveryScheduleService;
use Illuminate\Support\Facades\Route;

// Public routes (no authentication required)
Route::get('/', function () {
    return redirect('/admin');
});

// Authentication routes
Route::prefix('admin')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('admin.login.form');
    Route::post('/login', [LoginController::class, 'login'])->name('admin.login');
    Route::post('/logout', [LoginController::class, 'logout'])->name('admin.logout');
});

// Protected admin routes (require authentication)
Route::middleware(['admin.auth'])->prefix('admin')->group(function () {
    
    // Debug routes
    Route::get('/debug/api-test', [App\Http\Controllers\Admin\DebugController::class, 'apiTest'])->name('admin.debug.api-test');
    Route::get('/debug/fortnightly-week-test', [App\Http\Controllers\Admin\DebugController::class, 'fortnightlyWeekTest'])->name('admin.debug.fortnightly-week-test');
    Route::get('/debug/collection-day', [DeliveryController::class, 'testCollectionDays'])->name('admin.debug.collection-day');
    
    // Admin dashboard route
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Delivery management routes
    Route::get('/deliveries', [DeliveryController::class, 'index'])->name('admin.deliveries.index');
    Route::get('/api-test', [DeliveryController::class, 'apiTest'])->name('admin.api-test');
    Route::get('/diagnostic-subscriptions', [DeliveryController::class, 'diagnosticSubscriptions'])->name('admin.diagnostic-subscriptions');
    Route::get('/test-active-filter', [DeliveryController::class, 'testActiveFilter'])->name('admin.test-active-filter');
    Route::get('/debug-week-assignment', [DeliveryController::class, 'debugWeekAssignment'])->name('admin.debug-week-assignment');
    Route::get('/debug-delivery-schedule', [DeliveryController::class, 'debugDeliverySchedule'])->name('admin.debug-delivery-schedule');
    Route::get('/debug-specific-customers', [DeliveryController::class, 'debugSpecificCustomers'])->name('admin.debug-specific-customers');
    Route::get('/debug-page-display', [DeliveryController::class, 'debugPageDisplay'])->name('admin.debug-page-display');
    Route::get('/debug-customer-statuses', [DeliveryController::class, 'debugCustomerStatuses'])->name('admin.debug-customer-statuses');
    Route::get('/compare-week-logic', [DeliveryController::class, 'compareWeekLogic'])->name('admin.compare-week-logic');
    Route::get('/test-collection-days', [DeliveryController::class, 'testCollectionDays'])->name('admin.test-collection-days');
    Route::get('/debug-frequencies', [DeliveryController::class, 'debugFrequencies'])->name('admin.debug-frequencies');
    Route::post('/customers/update-week', [DeliveryController::class, 'updateCustomerWeek'])->name('admin.customers.update-week');

    // Customer management routes
    Route::prefix('users')->name('admin.users.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\UserSwitchingController::class, 'index'])->name('index');
        Route::get('/test', [App\Http\Controllers\Admin\UserSwitchingController::class, 'test'])->name('test');
        Route::get('/search', [App\Http\Controllers\Admin\UserSwitchingController::class, 'search'])->name('search');
        Route::get('/recent', [App\Http\Controllers\Admin\UserSwitchingController::class, 'getRecentUsers'])->name('recent');
        Route::post('/switch/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'switchToUser'])->name('switch');
        Route::get('/switch-redirect/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'switchAndRedirect'])->name('switch-redirect');
        Route::post('/switch-by-email', [App\Http\Controllers\Admin\UserSwitchingController::class, 'switchByEmail'])->name('switch-by-email');
        Route::get('/details/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'getUserDetails'])->name('details');
        Route::get('/redirect/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'redirect'])->name('redirect');
    });

    // Analytics and Reports routes (placeholders for future implementation)
    Route::get('/reports', function () {
        return view('admin.placeholder', ['title' => 'Reports', 'description' => 'Delivery and sales reports coming soon']);
    })->name('admin.reports');

    Route::get('/analytics', function () {
        return view('admin.placeholder', ['title' => 'Analytics', 'description' => 'Advanced analytics dashboard coming soon']);
    })->name('admin.analytics');

    // System routes (placeholders for future implementation)
    Route::get('/settings', function () {
        return view('admin.placeholder', ['title' => 'Settings', 'description' => 'System configuration coming soon']);
    })->name('admin.settings');

    Route::get('/logs', function () {
        return view('admin.placeholder', ['title' => 'System Logs', 'description' => 'Activity logs and debugging coming soon']);
    })->name('admin.logs');

    // Simple test route
    Route::get('/test', function () {
        return 'Test route works!';
    });

    // Debug route for deliveries
    Route::get('/debug', function (DeliveryScheduleService $service) {
        try {
            echo "Route accessed successfully<br>";
            echo "Service injected successfully<br>";
            
            $testConnection = $service->testConnection();
            echo "Connection test: " . json_encode($testConnection) . "<br>";
            
            return "Debug completed successfully";
        } catch (\Exception $e) {
            return "Error: " . $e->getMessage();
        }
    });
});
