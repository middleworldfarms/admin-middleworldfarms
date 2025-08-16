<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing Enhanced Backup System\n";
echo "================================\n";

// Check AI service directory
$aiServicePath = base_path('ai_service');
echo "AI Service Directory: " . ($aiServicePath && is_dir($aiServicePath) ? "✅ EXISTS" : "❌ MISSING") . "\n";

// Check if AI service files exist
$aiFiles = [
    'main.py',
    'requirements.txt', 
    'README.md'
];

echo "\nAI Service Files:\n";
foreach ($aiFiles as $file) {
    $filePath = $aiServicePath . '/' . $file;
    echo "  $file: " . (file_exists($filePath) ? "✅ EXISTS" : "❌ MISSING") . "\n";
}

// Test backup controller instantiation
try {
    $controller = new App\Http\Controllers\Admin\BackupController();
    echo "\n✅ BackupController instantiated successfully\n";
    
    // Use reflection to test AI service status check
    $reflection = new ReflectionClass($controller);
    $aiStatusMethod = $reflection->getMethod('checkAiServiceStatus');
    $aiStatusMethod->setAccessible(true);
    
    $aiStatus = $aiStatusMethod->invoke($controller);
    echo "\nAI Service Status:\n";
    foreach ($aiStatus as $key => $value) {
        $status = is_bool($value) ? ($value ? "✅ TRUE" : "❌ FALSE") : $value;
        echo "  $key: $status\n";
    }
    
    // Test vector database status check
    $vectorStatusMethod = $reflection->getMethod('checkVectorDatabaseStatus');
    $vectorStatusMethod->setAccessible(true);
    
    $vectorStatus = $vectorStatusMethod->invoke($controller);
    echo "\nVector Database Status:\n";
    foreach ($vectorStatus as $key => $value) {
        $status = is_bool($value) ? ($value ? "✅ TRUE" : "❌ FALSE") : $value;
        echo "  $key: $status\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error testing backup controller: " . $e->getMessage() . "\n";
}

echo "\n✨ Enhanced backup system validation completed!\n";
