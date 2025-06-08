<?php
// Direct print page for deliveries - bypass all Laravel complexity
require_once '../vendor/autoload.php';

// Load environment from correct path
$dotenv = Dotenv\Dotenv::createImmutable('../');
$dotenv->load();

// Simple API call to get delivery data
$api_key = $_ENV['MWF_API_KEY'] ?? 'mwf_10699ba2e5423ac0af9f8a0b37c524b1';
$week = $_GET['week'] ?? date('W');
$year = $_GET['year'] ?? date('Y');

$url = "https://middleworldfarms.org/wp-json/mwf/v1/delivery-schedule?week={$week}&year={$year}&api_key={$api_key}";

$response = file_get_contents($url);
$data = json_decode($response, true);

?>
<!DOCTYPE html>
<html>
<head>
    <title>🚚 Delivery Schedule - Week <?php echo $week; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 25mm; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f8f9fa; font-weight: bold; }
        .table-striped tbody tr:nth-of-type(odd) { background-color: rgba(0,123,255,.1); }
        .badge { background-color: #198754; color: white; padding: 0.25em 0.6em; border-radius: 0.25rem; font-size: 0.75em; }
        h1 { color: #0d6efd; border-bottom: 2px solid #0d6efd; padding-bottom: 10px; }
        h4 { margin-top: 20px; color: #333; }
        @media print {
            body { margin: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <h1>🚚 Delivery Schedule - Week <?php echo $week; ?> of <?php echo $year; ?></h1>
    <p><strong>Generated:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    
    <?php if ($data && isset($data['data'])): ?>
        <?php foreach ($data['data'] as $date => $dateData): ?>
            <?php if (isset($dateData['deliveries']) && count($dateData['deliveries']) > 0): ?>
                <h4><?php echo $dateData['date_formatted'] ?? $date; ?></h4>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Address</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dateData['deliveries'] as $delivery): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($delivery['customer_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($delivery['address'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($delivery['phone'] ?? ''); ?></td>
                                <td><span class="badge"><?php echo htmlspecialchars($delivery['status'] ?? ''); ?></span></td>
                                <td><?php echo htmlspecialchars($delivery['notes'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No delivery data available.</p>
    <?php endif; ?>
</body>
</html>
