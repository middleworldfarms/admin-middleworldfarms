<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DeliveryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Services\DeliveryScheduleService;

Route::get('/', function () {
    return redirect('/admin');
});

// Admin dashboard route
Route::get('/admin', [DashboardController::class, 'index'])->name('admin.dashboard');

// Delivery management routes
Route::get('/admin/deliveries', [DeliveryController::class, 'index'])->name('admin.deliveries.index');
Route::get('/admin/api-test', [DeliveryController::class, 'apiTest'])->name('admin.api-test');

// Customer management routes
Route::prefix('admin/users')->name('admin.users.')->group(function () {
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
Route::get('/admin/reports', function () {
    return view('admin.placeholder', ['title' => 'Reports', 'description' => 'Delivery and sales reports coming soon']);
})->name('admin.reports');

Route::get('/admin/analytics', function () {
    return view('admin.placeholder', ['title' => 'Analytics', 'description' => 'Advanced analytics dashboard coming soon']);
})->name('admin.analytics');

// System routes (placeholders for future implementation)
Route::get('/admin/settings', function () {
    return view('admin.placeholder', ['title' => 'Settings', 'description' => 'System configuration coming soon']);
})->name('admin.settings');

Route::get('/admin/logs', function () {
    return view('admin.placeholder', ['title' => 'System Logs', 'description' => 'Activity logs and debugging coming soon']);
})->name('admin.logs');

// Simple test route
Route::get('/admin/test', function () {
    return 'Test route works!';
});

// Debug route for deliveries
Route::get('/admin/debug', function (DeliveryScheduleService $service) {
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
