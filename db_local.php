<?php
/**
 * db.php — SmartRecruit database connection (Capstone B)
 * Author: Deepak Bhandari (2443463047)
 *
 * Creates a single PDO connection to the MySQL database.
 * Every other PHP file includes this one:  require_once 'db.php';
 *
 * Why PDO with prepared statements:
 * - Prevents SQL injection (OWASP Top 10, A03:2021 — Injection)
 * - Works with the same code on XAMPP (local) and Hostinger (production)
 */

// ---- Configuration (change ONLY these four lines when deploying) ----
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'smartrecruit');   // must match the database name in phpMyAdmin
define('DB_USER', 'root');           // XAMPP default user
define('DB_PASS', '');               // XAMPP default password is empty

// ---- Connection ----
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            // Throw exceptions on errors so we see problems immediately
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Return rows as associative arrays: $row['email'] not $row[2]
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Use real prepared statements, not emulated ones
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Never echo $e->getMessage() to real users in production —
    // it can leak credentials. Fine while developing locally.
    die('Database connection failed: ' . $e->getMessage());
}
