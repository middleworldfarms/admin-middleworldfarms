<!DOCTYPE html>
<html>
<head>
    <title>MWF Admin - User Setup Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #27ae60; }
        .error { color: #e74c3c; }
        .info { color: #3498db; }
        .credential-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .login-link { background: #27ae60; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛡️ MWF Admin Authentication Setup</h1>
        
        <?php
        // Load the admin users config
        $configPath = __DIR__ . '/../config/admin_users.php';
        
        if (file_exists($configPath)) {
            $config = include $configPath;
            $users = $config['users'] ?? [];
            
            echo "<p class='success'>✅ Admin users config loaded successfully!</p>";
            
            echo "<h3>👥 Configured Admin Users:</h3>";
            
            foreach ($users as $user) {
                $status = $user['active'] ? '🟢 Active' : '🔴 Inactive';
                $roleIcon = $user['role'] === 'super_admin' ? '👑' : '🔧';
                
                echo "<div class='credential-box'>";
                echo "<strong>{$roleIcon} {$user['name']}</strong> ({$user['role']})<br>";
                echo "<strong>Email:</strong> {$user['email']}<br>";
                echo "<strong>Status:</strong> {$status}<br>";
                echo "<strong>Password:</strong> " . str_repeat('*', strlen($user['password'])) . "<br>";
                echo "</div>";
            }
            
        } else {
            echo "<p class='error'>❌ Admin users config file not found!</p>";
        }
        ?>
        
        <h3>🔑 Your Login Credentials (Martin):</h3>
        <div class="credential-box">
            <strong>Email:</strong> martin@middleworldfarms.org<br>
            <strong>Password:</strong> Gogmyk-medmyt-3himsu<br>
            <strong>Role:</strong> Super Admin<br>
        </div>
        
        <h3>🚀 Next Steps:</h3>
        <ol>
            <li>Click the login link below</li>
            <li>Use your credentials to log in</li>
            <li>You'll have full access to the admin dashboard</li>
        </ol>
        
        <a href="https://admin.middleworldfarms.org/admin/login" class="login-link">
            🔐 Go to Admin Login
        </a>
        
        <h3>📝 Notes:</h3>
        <ul>
            <li>Your account has super_admin privileges</li>
            <li>Session timeout: 4 hours</li>
            <li>All login attempts are logged</li>
            <li>Fallback admin account also available</li>
        </ul>
        
        <p class="info">
            <strong>Security:</strong> This test page should be removed after confirming login works.
        </p>
    </div>
</body>
</html>
