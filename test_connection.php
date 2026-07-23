<?php
/**
 * test_connection.php — SmartRecruit database smoke test (Capstone B, Week 1)
 * Author: Deepak Bhandari (2443463047)
 *
 * Proves db.php works by listing every table in the database
 * with its row count. Screenshot the output — it is Week 1
 * evidence for the report (database layer operational).
 *
 * DELETE THIS FILE before deploying to production.
 */

require_once 'db.php';

echo '<h1>SmartRecruit — Database Connection Test</h1>';
echo '<p>Connected to database: <strong>' . htmlspecialchars(DB_NAME) . '</strong></p>';

// List all tables in the connected database
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

if (count($tables) === 0) {
    echo '<p style="color:red;">Connected, but the database is EMPTY. ';
    echo 'Import SmartRecruit_Database_COMPLETE.sql in phpMyAdmin first.</p>';
    exit;
}

echo '<p>Tables found: <strong>' . count($tables) . '</strong> (expected: 17)</p>';
echo '<table border="1" cellpadding="6" style="border-collapse:collapse;">';
echo '<tr><th>#</th><th>Table</th><th>Rows</th></tr>';

foreach ($tables as $i => $table) {
    // Table names come from SHOW TABLES (trusted), not user input,
    // so backtick-quoting them directly here is safe.
    $count = $pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
    echo '<tr><td>' . ($i + 1) . '</td>'
       . '<td>' . htmlspecialchars($table) . '</td>'
       . '<td>' . $count . '</td></tr>';
}

echo '</table>';
echo '<p style="color:green;"><strong>Connection layer working. Week 1 Task 1 complete.</strong></p>';
