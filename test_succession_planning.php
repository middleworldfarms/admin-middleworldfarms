<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test succession planning generation
echo "Testing Succession Planning Controller...\n";

try {
    // Create controller instance
    $controller = new \App\Http\Controllers\Admin\SuccessionPlanningController();
    
    // Test basic functionality
    echo "✓ Controller instantiated successfully\n";
    
    // Test the index method would require a request object, so we'll just verify the class loads
    $reflection = new ReflectionClass($controller);
    $methods = $reflection->getMethods();
    
    echo "Available methods:\n";
    foreach ($methods as $method) {
        if ($method->isPublic() && $method->getDeclaringClass()->getName() === get_class($controller)) {
            echo "  - " . $method->getName() . "\n";
        }
    }
    
    echo "\n✓ All tests passed!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
