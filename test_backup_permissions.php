<?php

// Test backup permissions and ZIP creation
echo "Testing backup system permissions...\n";

$backupPath = __DIR__ . '/storage/app/backups';
echo "Backup path: $backupPath\n";

// Check if directory exists
if (!is_dir($backupPath)) {
    echo "Creating backup directory...\n";
    mkdir($backupPath, 0775, true);
}

// Check permissions
echo "Directory permissions: " . substr(sprintf('%o', fileperms($backupPath)), -4) . "\n";
echo "Directory owner: " . posix_getpwuid(fileowner($backupPath))['name'] . "\n";
echo "Current user: " . posix_getpwuid(posix_geteuid())['name'] . "\n";

// Test ZIP creation
$testZipPath = $backupPath . '/test_' . date('Y-m-d_H-i-s') . '.zip';
echo "Testing ZIP creation at: $testZipPath\n";

if (!extension_loaded('zip')) {
    echo "ERROR: ZIP extension not loaded\n";
    exit(1);
}

$zip = new ZipArchive();
$result = $zip->open($testZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

if ($result !== TRUE) {
    echo "ERROR: Cannot create ZIP file. Error code: $result\n";
    
    // Error code meanings
    $errors = [
        ZipArchive::ER_OK => 'No error',
        ZipArchive::ER_MULTIDISK => 'Multi-disk zip archives not supported',
        ZipArchive::ER_RENAME => 'Renaming temporary file failed',
        ZipArchive::ER_CLOSE => 'Closing zip archive failed',
        ZipArchive::ER_SEEK => 'Seek error',
        ZipArchive::ER_READ => 'Read error',
        ZipArchive::ER_WRITE => 'Write error',
        ZipArchive::ER_CRC => 'CRC error',
        ZipArchive::ER_ZIPCLOSED => 'Containing zip archive was closed',
        ZipArchive::ER_NOENT => 'No such file',
        ZipArchive::ER_EXISTS => 'File already exists',
        ZipArchive::ER_OPEN => 'Can\'t open file',
        ZipArchive::ER_TMPOPEN => 'Failure to create temporary file',
        ZipArchive::ER_ZLIB => 'Zlib error',
        ZipArchive::ER_MEMORY => 'Memory allocation failure',
        ZipArchive::ER_CHANGED => 'Entry has been changed',
        ZipArchive::ER_COMPNOTSUPP => 'Compression method not supported',
        ZipArchive::ER_EOF => 'Premature EOF',
        ZipArchive::ER_INVAL => 'Invalid argument',
        ZipArchive::ER_NOZIP => 'Not a zip archive',
        ZipArchive::ER_INTERNAL => 'Internal error',
        ZipArchive::ER_INCONS => 'Zip archive inconsistent',
        ZipArchive::ER_REMOVE => 'Can\'t remove file',
        ZipArchive::ER_DELETED => 'Entry has been deleted',
    ];
    
    echo "Error meaning: " . ($errors[$result] ?? 'Unknown error') . "\n";
    exit(1);
}

// Test adding a simple file
$zip->addFromString('test.txt', 'This is a test backup file created at ' . date('Y-m-d H:i:s'));

if ($zip->close()) {
    echo "SUCCESS: ZIP file created successfully!\n";
    echo "File size: " . filesize($testZipPath) . " bytes\n";
    echo "ZIP file location: $testZipPath\n";
    
    // Clean up test file
    unlink($testZipPath);
    echo "Test file cleaned up.\n";
} else {
    echo "ERROR: Failed to close ZIP file\n";
    exit(1);
}

echo "Backup system test completed successfully!\n";
