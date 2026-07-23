<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) { 
    die('Not logged in');
}

$userId = $_SESSION['user_id'];
$student = getStudentByUserId($pdo, $userId);
$jobId = $_GET['job_id'] ?? null;

if (!$jobId) { 
    die('No job_id');
}

$stmt = $pdo->prepare('SELECT * FROM jobs WHERE job_id = ?');
$stmt->execute([$jobId]);
$job = $stmt->fetch();

if (!$job) { 
    die('Job not found');
}

echo "Job: " . $job['job_title'];
echo "<br>Student: " . $student['full_name'];
?>
