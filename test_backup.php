<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Http\Controllers\Admin\BackupController;
use Illuminate\Http\Request;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $controller = new BackupController();
    $request = Request::create('/admin/backups', 'POST', ['custom_name' => 'test_fixed']);
    
    echo "Creating test backup with fixed system...\n";
    $response = $controller->create($request);
    $data = $response->getData();
    
    if ($data->success) {
        echo "✓ Backup created successfully: " . $data->backup_file . "\n";
        
        // Check file size
        $backupPath = storage_path('app/backups/' . $data->backup_file);
        if (file_exists($backupPath)) {
            $size = filesize($backupPath);
            $sizeMB = round($size / 1024 / 1024, 2);
            echo "✓ Backup size: " . number_format($size) . " bytes ($sizeMB MB)\n";
            
            if ($sizeMB > 50) {
                echo "✓ SUCCESS: Backup size is now proper for Laravel+database!\n";
            } else {
                echo "⚠ WARNING: Backup size still seems small for full Laravel backup\n";
            }
        }
    } else {
        echo "✗ Backup failed: " . $data->message . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
