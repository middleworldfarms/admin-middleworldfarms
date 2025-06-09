<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DeliveryController;
use App\Services\DeliveryScheduleService;

Route::get('/', function () {
    return redirect('/admin');
});

// Admin dashboard route
Route::get('/admin', function () {
    return view('admin.dashboard');
});

// Delivery management routes
Route::get('/admin/deliveries', [DeliveryController::class, 'index'])->name('admin.deliveries.index');
Route::get('/admin/api-test', [DeliveryController::class, 'apiTest'])->name('admin.api-test');

// User switching routes - Updated for direct database access
Route::prefix('admin/users')->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\UserSwitchingController::class, 'index'])->name('admin.users.index');
    Route::get('/test', [App\Http\Controllers\Admin\UserSwitchingController::class, 'test'])->name('admin.users.test');
    Route::get('/search', [App\Http\Controllers\Admin\UserSwitchingController::class, 'search'])->name('admin.users.search');
    Route::get('/recent', [App\Http\Controllers\Admin\UserSwitchingController::class, 'getRecentUsers'])->name('admin.users.recent');
    Route::post('/switch/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'switchToUser'])->name('admin.users.switch');
    Route::get('/switch-redirect/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'switchAndRedirect'])->name('admin.users.switch-redirect');
    Route::post('/switch-by-email', [App\Http\Controllers\Admin\UserSwitchingController::class, 'switchByEmail'])->name('admin.users.switch-by-email');
    Route::get('/details/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'getUserDetails'])->name('admin.users.details');
    Route::get('/redirect/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'redirect'])->name('admin.users.redirect');
});

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
