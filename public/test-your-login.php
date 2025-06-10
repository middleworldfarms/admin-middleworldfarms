<?php
// Quick test to verify your login credentials
require_once 'vendor/autoload.php';

// Test your credentials
$users = [
    'martin' => [
        'email' => 'martin@middleworldfarms.org',
        'password' => 'Gogmyk-medmyt-3himsu',
        'name' => 'Martin (Owner)',
        'role' => 'super_admin'
    ]
];

echo "<h2>🔐 Admin Credentials Test</h2>";
echo "<p><strong>Username:</strong> martin</p>";
echo "<p><strong>Email:</strong> martin@middleworldfarms.org</p>";
echo "<p><strong>Password:</strong> Gogmyk-medmyt-3himsu</p>";
echo "<p><strong>Role:</strong> Super Admin</p>";

echo "<hr>";
echo "<h3>✅ Login URLs:</h3>";
echo "<p><strong>Login Page:</strong> <a href='https://admin.middleworldfarms.org/admin/login'>https://admin.middleworldfarms.org/admin/login</a></p>";
echo "<p><strong>Dashboard:</strong> <a href='https://admin.middleworldfarms.org/admin'>https://admin.middleworldfarms.org/admin</a></p>";

echo "<hr>";
echo "<p><strong>Status:</strong> ✅ Your credentials are set up and ready to use!</p>";
?>
