<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) { die('Not logged in'); }

$userId = $_SESSION['user_id'];
$student = getStudentByUserId($pdo, $userId);

echo "Student ID: " . $student['student_id'] . "<br>";

try {
    $stmt = $pdo->prepare('
        SELECT 
            a.application_id,
            a.status,
            a.applied_date,
            j.job_title,
            j.company
        FROM applications a
        JOIN jobs j ON a.job_id = j.job_id
        WHERE a.student_id = ?
        ORDER BY a.applied_date DESC
    ');
    $stmt->execute([$student['student_id']]);
    $applications = $stmt->fetchAll();
    
    echo "Applications found: " . count($applications) . "<br><br>";
    
    foreach ($applications as $app) {
        echo $app['job_title'] . " at " . $app['company'] . " - " . $app['status'] . "<br>";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
