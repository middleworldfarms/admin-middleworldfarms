<?php

echo "=== BACKUP SYSTEM DIAGNOSTIC ===\n";

// 1. Basic environment checks
echo "\n1. ENVIRONMENT CHECKS:\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "ZIP Extension: " . (extension_loaded('zip') ? "✅ Loaded" : "❌ Missing") . "\n";
echo "Current User: " . get_current_user() . "\n";
echo "Current Working Directory: " . getcwd() . "\n";

// 2. Directory permissions
echo "\n2. DIRECTORY PERMISSIONS:\n";
$storageDir = __DIR__ . '/storage';
$appDir = $storageDir . '/app';
$backupsDir = $appDir . '/backups';

foreach ([$storageDir, $appDir, $backupsDir] as $dir) {
    if (is_dir($dir)) {
        echo "✅ $dir exists\n";
        echo "   Permissions: " . substr(sprintf('%o', fileperms($dir)), -4) . "\n";
        echo "   Writable: " . (is_writable($dir) ? "Yes" : "No") . "\n";
        echo "   Owner: " . (function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($dir))['name'] : 'unknown') . "\n";
    } else {
        echo "❌ $dir does not exist\n";
    }
}

// 3. Disk space
echo "\n3. DISK SPACE:\n";
$freeBytes = disk_free_space($backupsDir);
if ($freeBytes !== false) {
    echo "Free space: " . round($freeBytes / 1024 / 1024, 2) . " MB\n";
} else {
    echo "Unable to determine free space\n";
}

// 4. Test ZIP creation
echo "\n4. ZIP CREATION TEST:\n";
$testFile = $backupsDir . '/diagnostic_test.zip';

try {
    if (!is_dir($backupsDir)) {
        mkdir($backupsDir, 0775, true);
        echo "Created backups directory\n";
    }
    
    $zip = new ZipArchive();
    $result = $zip->open($testFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    
    if ($result === TRUE) {
        echo "✅ ZIP archive opened successfully\n";
        
        // Add test content
        $zip->addFromString('test.txt', 'Diagnostic test at ' . date('Y-m-d H:i:s'));
        echo "✅ Test content added\n";
        
        // Try to close
        if ($zip->close()) {
            echo "✅ ZIP archive closed successfully\n";
            echo "✅ Test file created: $testFile\n";
            echo "   File size: " . filesize($testFile) . " bytes\n";
            
            // Clean up
            unlink($testFile);
            echo "✅ Test file cleaned up\n";
        } else {
            echo "❌ Failed to close ZIP archive\n";
            $lastError = error_get_last();
            if ($lastError) {
                echo "   Last error: " . $lastError['message'] . "\n";
            }
        }
    } else {
        echo "❌ Failed to open ZIP archive\n";
        echo "   Error code: $result\n";
        
        // Error meanings
        $errors = [
            ZipArchive::ER_OPEN => 'Can\'t open file',
            ZipArchive::ER_TMPOPEN => 'Failure to create temporary file',
            ZipArchive::ER_WRITE => 'Write error',
            ZipArchive::ER_MEMORY => 'Memory allocation failure',
            ZipArchive::ER_INVAL => 'Invalid argument',
        ];
        
        if (isset($errors[$result])) {
            echo "   Error meaning: " . $errors[$result] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception during ZIP test: " . $e->getMessage() . "\n";
}

// 5. Laravel-specific checks
echo "\n5. LARAVEL ENVIRONMENT:\n";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    
    if (file_exists(__DIR__ . '/bootstrap/app.php')) {
        try {
            $app = require_once __DIR__ . '/bootstrap/app.php';
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            echo "✅ Laravel bootstrapped successfully\n";
            
            // Test Storage facade
            echo "Storage disk: " . config('filesystems.default') . "\n";
            echo "Storage path: " . storage_path() . "\n";
            
        } catch (Exception $e) {
            echo "❌ Laravel bootstrap failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Laravel bootstrap file not found\n";
    }
} else {
    echo "❌ Composer autoload not found\n";
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
