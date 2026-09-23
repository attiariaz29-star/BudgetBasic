<?php
// TEMP diagnostic — delete after fix. Visit: http://localhost/BudgetBasics/test-db.php
header('Content-Type: text/plain; charset=utf-8');
echo "PHP: " . PHP_VERSION . "\n";
echo "pdo_mysql: " . (extension_loaded('pdo_mysql') ? "OK" : "MISSING - php.ini me extension=pdo_mysql enable karo") . "\n";
$H = getenv('DB_HOST') ?: '127.0.0.1';
$N = getenv('DB_NAME') ?: 'budgetbasics';
$U = getenv('DB_USER') ?: 'root';
$P = getenv('DB_PASS') ?: '';
$port = getenv('DB_PORT') ?: '3306';
echo "Trying: host=$H port=$port db=$N user=$U\n";
try {
    $pdo = new PDO("mysql:host=$H;port=$port;dbname=$N;charset=utf8mb4", $U, $P, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "SUCCESS: DB connected!\n";
    echo "users table count: " . $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() . "\n";
} catch (PDOException $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    echo "\n--- FIX ---\n";
    echo "1. XAMPP me MySQL Started hai?\n";
    echo "2. phpMyAdmin me 'budgetbasics' database bani + SQL import hua?\n";
    echo "3. Agar MySQL ka password hai to config/database.php me \$DB_PASS update karo.\n";
}
