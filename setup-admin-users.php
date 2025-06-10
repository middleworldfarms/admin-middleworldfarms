<?php
require_once __DIR__ . '/vendor/autoload.php';

// Your credentials
$martinPassword = 'Gogmyk-medmyt-3himsu';
$adminPassword = 'MWF2025Admin!';

// Generate secure password hashes
$martinHash = password_hash($martinPassword, PASSWORD_DEFAULT);
$adminHash = password_hash($adminPassword, PASSWORD_DEFAULT);

echo "=== MWF Admin User Setup ===\n\n";

echo "Martin's password hash:\n";
echo $martinHash . "\n\n";

echo "Admin password hash:\n";
echo $adminHash . "\n\n";

// Update the config file
$configPath = __DIR__ . '/config/admin_users.php';
$configContent = file_get_contents($configPath);

// Replace Martin's password hash
$configContent = str_replace(
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    $martinHash,
    $configContent
);

// Replace admin password hash
$configContent = str_replace(
    '$2y$12$example_hash_will_be_replaced',
    $adminHash,
    $configContent
);

// Write updated config
if (file_put_contents($configPath, $configContent)) {
    echo "✅ Config file updated successfully!\n\n";
} else {
    echo "❌ Failed to update config file!\n\n";
}

echo "=== User Credentials ===\n";
echo "Martin:\n";
echo "  Email: martin@middleworldfarms.org\n";
echo "  Password: Gogmyk-medmyt-3himsu\n";
echo "  Role: super_admin\n\n";

echo "Fallback Admin:\n";
echo "  Email: admin@middleworldfarms.org\n";
echo "  Password: MWF2025Admin!\n";
echo "  Role: admin\n\n";

echo "Login URL: https://admin.middleworldfarms.org/admin/login\n";
echo "\n=== Setup Complete! ===\n";
?>
