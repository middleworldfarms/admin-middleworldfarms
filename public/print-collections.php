<?php
// Direct print page for collections - bypass all Laravel complexity
require_once '../vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable('..');
$dotenv->load();

// Simple API call to get delivery data
$api_key = $_ENV['MWF_API_KEY'];
$week = $_GET['week'] ?? date('W');
$year = $_GET['year'] ?? date('Y');

$url = "https://middleworldfarms.org/wp-json/mwf/v1/delivery-schedule?week={$week}&year={$year}&api_key={$api_key}";

$response = file_get_contents($url);
$data = json_decode($response, true);

?>
<!DOCTYPE html>
<html>
<head>
    <title>📦 Collection Schedule - Week <?php echo $week; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 25mm; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f8f9fa; font-weight: bold; }
        .table-striped tbody tr:nth-of-type(odd) { background-color: rgba(40,167,69,.1); }
        .badge { background-color: #198754; color: white; padding: 0.25em 0.6em; border-radius: 0.25rem; font-size: 0.75em; }
        h1 { color: #198754; border-bottom: 2px solid #198754; padding-bottom: 10px; }
        h4 { margin-top: 20px; color: #333; }
        @media print {
            body { margin: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <h1>📦 Collection Schedule - Week <?php echo $week; ?> of <?php echo $year; ?></h1>
    <p><strong>Generated:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    
    <?php if ($data && isset($data['data'])): ?>
        <?php foreach ($data['data'] as $date => $dateData): ?>
            <?php if (isset($dateData['collections']) && count($dateData['collections']) > 0): ?>
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
                        <?php foreach ($dateData['collections'] as $collection): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($collection['customer_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($collection['address'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($collection['phone'] ?? ''); ?></td>
                                <td><span class="badge"><?php echo htmlspecialchars($collection['status'] ?? ''); ?></span></td>
                                <td><?php echo htmlspecialchars($collection['notes'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No collection data available.</p>
    <?php endif; ?>
</body>
</html>
