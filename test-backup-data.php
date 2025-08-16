<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$controller = new App\Http\Controllers\Admin\BackupController();

$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('getBackupList');
$method->setAccessible(true);

$result = $method->invoke($controller);

echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
