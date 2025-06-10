<?php
// Check subscription statuses
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::createFromGlobals();
$kernel->bootstrap();

$directDb = new \App\Services\DirectDatabaseService();
$rawData = $directDb->getDeliveryScheduleData(100);

$statuses = [];
foreach ($rawData['collections'] as $collection) {
    $status = $collection['status'];
    if (!isset($statuses[$status])) {
        $statuses[$status] = 0;
    }
    $statuses[$status]++;
}

echo "Subscription Statuses:\n";
foreach ($statuses as $status => $count) {
    echo "- $status: $count\n";
}
?>
