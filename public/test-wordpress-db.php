<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Get the Laravel application instance
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Use the DB facade
use Illuminate\Support\Facades\DB;

// Test the WordPress database connection
try {
    echo "<h1>WordPress Database Connection Test</h1>";

    // Check that the wordpress connection is defined
    $dbconfig = config('database.connections.wordpress');
    echo "<h2>WordPress DB Config:</h2>";
    echo "<pre>";
    print_r($dbconfig);
    echo "</pre>";
    
    // Test the connection with a simple query
    echo "<h2>Testing Connection:</h2>";
    $results = DB::connection('wordpress')
        ->table('usermeta') // Let Laravel apply the prefix
        ->where('meta_key', 'preferred_collection_day')
        ->limit(10)
        ->get();
        
    echo "<h2>Collection Day Results:</h2>";
    echo "<pre>";
    print_r($results);
    echo "</pre>";
    
    // Count how many users have collection day preferences set
    $count = DB::connection('wordpress')
        ->table('usermeta') // Let Laravel apply the prefix
        ->where('meta_key', 'preferred_collection_day')
        ->count();
        
    echo "<p>Total users with collection day preferences: {$count}</p>";
    
    // Debug information about the WordPress database connection
    echo "<h2>Database Debug Info:</h2>";
    $wpPrefix = DB::connection('wordpress')->getTablePrefix();
    echo "<p>Table prefix: '{$wpPrefix}'</p>";
    
    // Enable query logging
    DB::connection('wordpress')->enableQueryLog();
    
    // Run test query
    $testQuery = DB::connection('wordpress')
        ->table('usermeta')
        ->where('meta_key', 'preferred_collection_day')
        ->limit(5);
    
    $testResults = $testQuery->get();
    
    // Get the executed query
    $queryLog = DB::connection('wordpress')->getQueryLog();
    echo "<h3>SQL Query:</h3>";
    echo "<pre>";
    print_r(end($queryLog));
    echo "</pre>";
    
    // Direct query to check if table exists
    try {
        $tableExists = DB::connection('wordpress')
            ->select("SHOW TABLES LIKE '{$wpPrefix}usermeta'");
        echo "<p>Table check: " . (!empty($tableExists) ? "Table exists!" : "Table NOT found!") . "</p>";
        
        // Show actual tables in the database
        echo "<h3>First 10 Tables in Database:</h3>";
        $allTables = DB::connection('wordpress')->select('SHOW TABLES');
        echo "<pre>";
        print_r(array_slice($allTables, 0, 10));
        echo "</pre>";
    } catch (Exception $e) {
        echo "<p>Error checking tables: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
