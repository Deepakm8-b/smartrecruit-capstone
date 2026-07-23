<?php
try {
    $pdo = new PDO(
        'mysql:host=aws.connect.psdb.cloud;dbname=smartrecruit;charset=utf8mb4',
        'ud613zr2bhkrz75e1ifc',
        'pscale_pw_YFhN8CyGU9t6sRtPAiMuVlP76rIb50T5Y8VAiuLyLT',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
?>
