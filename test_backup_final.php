<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing enhanced backup system with improved error handling...\n";

try {
    $controller = new App\Http\Controllers\Admin\BackupController();
    
    // Create a simple test backup with minimal components
    $request = new Illuminate\Http\Request([
        'name' => 'enhanced_test',
        'include_database' => false,  // Start simple
        'include_files' => false      // Start simple
    ]);
    
    echo "Attempting to create backup...\n";
    $response = $controller->create($request);
    
    if ($response->getStatusCode() === 200) {
        $data = json_decode($response->getContent(), true);
        echo "✅ SUCCESS: " . $data['message'] . "\n";
        echo "✅ Backup file: " . ($data['backup'] ?? 'unknown') . "\n";
        
        // Check if file actually exists
        $backupPath = storage_path('app/backups/' . ($data['backup'] ?? ''));
        if (file_exists($backupPath)) {
            echo "✅ Backup file exists on disk\n";
            echo "📊 File size: " . round(filesize($backupPath) / 1024, 2) . " KB\n";
        } else {
            echo "❌ Backup file not found on disk: $backupPath\n";
        }
    } else {
        echo "❌ HTTP Error " . $response->getStatusCode() . "\n";
        echo "Response: " . $response->getContent() . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    // Check Laravel logs for more details
    $logPath = storage_path('logs/laravel.log');
    if (file_exists($logPath)) {
        echo "\nChecking Laravel logs for additional details...\n";
        $logContent = file_get_contents($logPath);
        $lines = explode("\n", $logContent);
        $recentLines = array_slice($lines, -10);
        foreach ($recentLines as $line) {
            if (strpos($line, 'backup') !== false || strpos($line, 'ERROR') !== false) {
                echo "LOG: $line\n";
            }
        }
    }
}

echo "\nTest completed.\n";
