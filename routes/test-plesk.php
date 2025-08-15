<?php

use App\Services\PleskBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test-plesk-service', function (Request $request) {
    try {
        $service = app(PleskBackupService::class);
        
        $backups = $service->listBackups();
        $status = $service->getBackupStatus();
        
        return response()->json([
            'success' => true,
            'total_backups' => count($backups),
            'total_size_mb' => round(($status['total_size'] ?? 0) / 1024 / 1024, 2),
            'health' => $status['backup_health'] ?? 'unknown',
            'by_type' => $status['by_type'] ?? [],
            'sample_backups' => array_slice($backups, 0, 5)
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});
