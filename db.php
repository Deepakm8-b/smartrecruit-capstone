<?php
define('DB_HOST', 'sakura.proxy.rlwy.net');
define('DB_NAME', 'railway');
define('DB_USER', 'root');
define('DB_PASS', 'vQUSlGXnLhUIkdAOPMuehREotZMfDwqd');
define('DB_PORT', 43594);

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
?>
