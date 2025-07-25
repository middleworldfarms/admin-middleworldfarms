<?php
// Test file to check redirect location
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

try {
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    
    $request = Illuminate\Http\Request::create('/admin/deliveries', 'GET');
    $response = $kernel->handle($request);
    
    echo "Response status: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() === 302) {
        echo "Redirect location: " . $response->headers->get('Location') . "\n";
    }
    
    // Test with a simulated admin session
    echo "\n=== Testing with admin session ===\n";
    
    // Check if there's an admin.auth middleware
    $router = $app->make('router');
    $routes = $router->getRoutes();
    
    foreach ($routes as $route) {
        if ($route->uri === 'admin/deliveries') {
            echo "Route middleware: " . implode(', ', $route->middleware()) . "\n";
            break;
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
