<?php
// Simple test to show what's happening with profile URLs
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profile URL Test</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .url-test { font-family: monospace; font-size: 12px; background: #f9f9f9; padding: 4px; }
    </style>
</head>
<body>
    <h2>Profile URL Generation Test</h2>
    <p>This test simulates the PHP variables that would be used in the Blade templates.</p>
    
    <table>
        <tr>
            <th>Customer Name</th>
            <th>Customer ID (Variable)</th>
            <th>Generated Profile URL</th>
            <th>Test Link</th>
        </tr>
        
        <?php
        // Simulate different customers with different IDs
        $testCustomers = [
            ['customer_id' => '45', 'name' => 'Alice Wilson'],
            ['customer_id' => '67', 'name' => 'Bob Smith'],
            ['customer_id' => '123', 'name' => 'Carol Johnson'],
            ['customer_id' => '456', 'name' => 'David Brown']
        ];
        
        foreach ($testCustomers as $delivery) {
            $customerId = $delivery['customer_id'] ?? null;
            $customerName = $delivery['name'] ?? 'Customer';
            
            // This is exactly what happens in the Blade template
            $profileUrl = "https://middleworldfarms.org/wp-admin/user-edit.php?user_id=" . $customerId;
            ?>
            
            <tr>
                <td><?= htmlspecialchars($customerName) ?></td>
                <td style="font-weight: bold; color: blue;"><?= htmlspecialchars($customerId) ?></td>
                <td class="url-test"><?= htmlspecialchars($profileUrl) ?></td>
                <td><a href="<?= htmlspecialchars($profileUrl) ?>" target="_blank">Open Profile (ID: <?= $customerId ?>)</a></td>
            </tr>
            
        <?php } ?>
    </table>
    
    <h3>Test Instructions:</h3>
    <ol>
        <li>Click each "Open Profile" link</li>
        <li>Check if they all go to the same person (Ruth Sanders) or different people</li>
        <li>If they all go to Ruth Sanders, the issue is NOT in the Laravel code but in WordPress</li>
        <li>If they go to different people, then the Laravel code has a variable issue</li>
    </ol>
    
    <p><strong>Expected behavior:</strong> Each link should go to a different user profile (or show "user not found" for test IDs).</p>
    <p><strong>Current problem:</strong> All links apparently go to Ruth Sanders' profile.</p>
</body>
</html>
