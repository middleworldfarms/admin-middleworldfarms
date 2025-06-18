<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $wpApi = app('App\Services\WpApiService');
    $rawData = $wpApi->getDeliveryScheduleData(20);
    
    echo "=== Raw Status Analysis ===\n";
    $statusCounts = [];
    $shippingAnalysis = [];
    
    foreach ($rawData as $sub) {
        $status = $sub['status'];
        $shippingTotal = (float) ($sub['shipping_total'] ?? 0);
        $type = $shippingTotal > 0 ? 'delivery' : 'collection';
        
        if (!isset($statusCounts[$status])) {
            $statusCounts[$status] = ['delivery' => 0, 'collection' => 0];
        }
        $statusCounts[$status][$type]++;
        
        $shippingAnalysis[] = [
            'id' => $sub['id'],
            'status' => $status,
            'shipping_total' => $shippingTotal,
            'type' => $type
        ];
    }
    
    echo "Status breakdown:\n";
    foreach ($statusCounts as $status => $counts) {
        echo "  $status: {$counts['delivery']} deliveries, {$counts['collection']} collections\n";
    }
    
    echo "\nFirst few items:\n";
    foreach (array_slice($shippingAnalysis, 0, 5) as $item) {
        echo "  ID {$item['id']}: {$item['status']} - shipping: {$item['shipping_total']} - type: {$item['type']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
