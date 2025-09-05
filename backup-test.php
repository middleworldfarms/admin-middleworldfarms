<?php
echo "Backup System Diagnostic\n";
echo "========================\n\n";

// Check if we're in the right directory
echo "Current directory: " . getcwd() . "\n";
echo "Laravel app exists: " . (file_exists('artisan') ? 'YES' : 'NO') . "\n\n";

// Check storage permissions
echo "Storage directory permissions:\n";
$storageDir = 'storage/app';
if (file_exists($storageDir)) {
    echo "storage/app exists: YES\n";
    echo "Permissions: " . substr(sprintf('%o', fileperms($storageDir)), -4) . "\n";
    echo "Writable: " . (is_writable($storageDir) ? 'YES' : 'NO') . "\n";
} else {
    echo "storage/app exists: NO\n";
}

// Check if backup temp directory exists
$backupTempDir = 'storage/app/backup-temp';
if (file_exists($backupTempDir)) {
    echo "backup-temp exists: YES\n";
} else {
    echo "backup-temp exists: NO - creating...\n";
    @mkdir($backupTempDir, 0755, true);
}

// Check disk space
echo "\nDisk space:\n";
$bytes = disk_free_space('.');
echo "Free space: " . round($bytes / 1024 / 1024 / 1024, 2) . " GB\n";

// Check if Spatie backup is installed
echo "\nPackage check:\n";
if (file_exists('vendor/spatie/laravel-backup')) {
    echo "Spatie Laravel Backup: INSTALLED\n";
} else {
    echo "Spatie Laravel Backup: NOT FOUND\n";
}

// Check database connection
echo "\nDatabase connection test:\n";
try {
    require_once 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    $connection = DB::connection();
    $connection->getPdo();
    echo "Database connection: SUCCESS\n";
} catch (Exception $e) {
    echo "Database connection: FAILED - " . $e->getMessage() . "\n";
}

echo "\nDiagnostic complete!\n";
