<?php
// envtest.php

function parseEnv($path) {
    $vars = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || trim($line) === '') continue;
        if (!strpos($line, '=')) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (preg_match('/^["\'](.*)["\']$/', $value, $matches)) {
            $value = $matches[1];
        }
        $vars[$name] = $value;
    }
    return $vars;
}

$envPath = dirname(__DIR__) . '/.env';
if (!file_exists($envPath)) {
    die("Could not find .env file at $envPath\n");
}
$env = parseEnv($envPath);

echo "<h2>.env Settings Test</h2>";
echo "<pre>";

echo "APP_ENV: " . ($env['APP_ENV'] ?? 'not set') . "\n";
echo "APP_DEBUG: " . ($env['APP_DEBUG'] ?? 'not set') . "\n";
echo "APP_URL: " . ($env['APP_URL'] ?? 'not set') . "\n\n";

// Test DB connection
echo "Testing DB connection...\n";
try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $env['DB_HOST'] ?? 'localhost',
        $env['DB_PORT'] ?? '3306',
        $env['DB_DATABASE'] ?? ''
    );
    $pdo = new PDO($dsn, $env['DB_USERNAME'] ?? '', $env['DB_PASSWORD'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $stmt = $pdo->query('SELECT VERSION() as version');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "DB connection: SUCCESS (MySQL version: " . $row['version'] . ")\n";
} catch (Exception $e) {
    echo "DB connection: FAILED (" . $e->getMessage() . ")\n";
}

// Test Redis connection if enabled
if (($env['REDIS_HOST'] ?? null) && ($env['REDIS_PORT'] ?? null)) {
    echo "\nTesting Redis connection...\n";
    try {
        $redis = new Redis();
        $redis->connect($env['REDIS_HOST'], (int)$env['REDIS_PORT']);
        if (!empty($env['REDIS_PASSWORD']) && $env['REDIS_PASSWORD'] !== 'null') {
            $redis->auth($env['REDIS_PASSWORD']);
        }
        $redis->ping();
        echo "Redis connection: SUCCESS\n";
    } catch (Exception $e) {
        echo "Redis connection: FAILED (" . $e->getMessage() . ")\n";
    }
}

// Test Mail settings (just output, not a real connection)
echo "\nMail settings:\n";
echo "MAIL_MAILER: " . ($env['MAIL_MAILER'] ?? 'not set') . "\n";
echo "MAIL_HOST: " . ($env['MAIL_HOST'] ?? 'not set') . "\n";
echo "MAIL_PORT: " . ($env['MAIL_PORT'] ?? 'not set') . "\n";
echo "MAIL_USERNAME: " . ($env['MAIL_USERNAME'] ?? 'not set') . "\n";
echo "MAIL_FROM_ADDRESS: " . ($env['MAIL_FROM_ADDRESS'] ?? 'not set') . "\n";

echo "</pre>";
?>