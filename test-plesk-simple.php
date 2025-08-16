<?php
// Simple test of Plesk CLI access
$pleskPath = '/usr/sbin/plesk';

echo "Testing Plesk CLI access...\n";

// Test basic plesk command
$output = shell_exec("$pleskPath version 2>&1");
echo "Plesk version: " . trim($output) . "\n";

// Test backup listing
$output = shell_exec("$pleskPath bin backup --list 2>&1");
echo "Backup list output:\n";
echo $output . "\n";

// Test dumps directory
$dumpsPath = '/var/lib/psa/dumps';
if (is_dir($dumpsPath)) {
    echo "Dumps directory exists and is accessible\n";
    $files = scandir($dumpsPath);
    echo "Files in dumps directory: " . count($files) . "\n";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "  - $file\n";
        }
    }
} else {
    echo "Dumps directory not accessible\n";
}
