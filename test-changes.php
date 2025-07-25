<?php
// Test file to verify our changes are working
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

// Check if the file exists and contains our debug message
$viewFile = __DIR__ . '/resources/views/admin/deliveries/index.blade.php';
$content = file_get_contents($viewFile);

echo "=== FILE TEST RESULTS ===\n";
echo "File exists: " . (file_exists($viewFile) ? 'YES' : 'NO') . "\n";
echo "File size: " . filesize($viewFile) . " bytes\n";
echo "Last modified: " . date('Y-m-d H:i:s', filemtime($viewFile)) . "\n";
echo "Contains debug message: " . (strpos($content, 'DEBUG: THIS FILE HAS BEEN UPDATED') !== false ? 'YES' : 'NO') . "\n";

if (strpos($content, 'printDeliveries()') !== false) {
    echo "Contains printDeliveries function: YES\n";
} else {
    echo "Contains printDeliveries function: NO\n";
}

if (strpos($content, 'printCollections()') !== false) {
    echo "Contains printCollections function: YES\n";
} else {
    echo "Contains printCollections function: NO\n";
}

// Test the controller
try {
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    $request = Illuminate\Http\Request::create('/admin/deliveries', 'GET');
    $response = $kernel->handle($request);
    
    echo "\n=== CONTROLLER TEST RESULTS ===\n";
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response contains debug message: " . (strpos($response->getContent(), 'DEBUG: THIS FILE HAS BEEN UPDATED') !== false ? 'YES' : 'NO') . "\n";
    
} catch (Exception $e) {
    echo "\n=== CONTROLLER ERROR ===\n";
    echo "Error: " . $e->getMessage() . "\n";
}
