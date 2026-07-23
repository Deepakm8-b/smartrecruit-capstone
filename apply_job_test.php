<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "DEBUG MODE<br>";
echo "POST data: " . print_r($_POST, true) . "<br>";

require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) { die('Not logged in'); }

$userId = $_SESSION['user_id'];
$student = getStudentByUserId($pdo, $userId);
$jobId = intval($_GET['job_id'] ?? 0);

echo "User ID: " . $userId . "<br>";
echo "Student ID: " . $student['student_id'] . "<br>";
echo "Job ID: " . $jobId . "<br>";

$stmt = $pdo->prepare('SELECT * FROM jobs WHERE job_id = ?');
$stmt->execute([$jobId]);
$job = $stmt->fetch();

if (!$job) { die('Job not found'); }

echo "Job loaded: " . $job['job_title'] . "<br>";

$step = intval($_POST['step'] ?? 1);
echo "Current step: " . $step . "<br>";

// Get screening questions
$stmt = $pdo->prepare('SELECT * FROM job_screening_questions WHERE job_id = ? ORDER BY question_id');
$stmt->execute([$jobId]);
$questions = $stmt->fetchAll();

echo "Questions found: " . count($questions) . "<br>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 4) {
    echo "Attempting to submit application...<br>";
    try {
        $resumeUsed = $_POST['resume_choice'] ?? ($student['resume'] ?? 'default');
        echo "Resume: " . $resumeUsed . "<br>";
        
        $stmt = $pdo->prepare('INSERT INTO applications (student_id, job_id, resume_used, status) VALUES (?, ?, ?, ?)');
        $result = $stmt->execute([$student['student_id'], $jobId, $resumeUsed, 'Applied']);
        echo "Insert result: " . ($result ? 'SUCCESS' : 'FAILED') . "<br>";
        
        if ($result) {
            echo "Application saved!<br>";
        }
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "<br>";
    }
}

?>
<h2>4-Step Form Test</h2>
<form method="post">
    <input type="hidden" name="step" value="<?= $step ?>">
    Step: <input type="text" name="test" value="test"><br>
    <button type="submit">Test</button>
</form>
