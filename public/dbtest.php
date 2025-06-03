<?php
// dbtest.php: Test MariaDB connection from web server context
$host = 'localhost';
$db   = 'admin_db';
$user = 'martin_admin';
$pass = '2v0d2f#T4';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "<h2>Success: Connected to MariaDB as $user</h2>";
} catch (PDOException $e) {
    echo "<h2>Connection failed:</h2> ", htmlspecialchars($e->getMessage());
}
?>
