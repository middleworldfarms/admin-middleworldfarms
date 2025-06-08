<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DeliveryController;

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

// User switching routes
Route::prefix('admin/users')->group(function () {
    Route::get('/test', [App\Http\Controllers\Admin\UserSwitchingController::class, 'test'])->name('admin.users.test');
    Route::get('/search', [App\Http\Controllers\Admin\UserSwitchingController::class, 'search'])->name('admin.users.search');
    Route::get('/switch/{userId}', [App\Http\Controllers\Admin\UserSwitchingController::class, 'redirect'])->name('admin.users.switch');
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
