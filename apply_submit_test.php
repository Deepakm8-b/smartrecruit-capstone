<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) { die('Not logged in'); }

$userId = $_SESSION['user_id'];
$student = getStudentByUserId($pdo, $userId);
$jobId = intval($_GET['job_id'] ?? 1);

echo "DEBUG: POST method: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "DEBUG: Step value: " . ($_POST['step'] ?? 'NOT SET') . "<br>";
echo "DEBUG: Student ID: " . $student['student_id'] . "<br>";
echo "DEBUG: Job ID: " . $jobId . "<br>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = intval($_POST['step'] ?? 0);
    echo "DEBUG: Processing step " . $step . "<br>";
    
    if ($step === 4) {
        echo "DEBUG: Attempting to submit...<br>";
        
        try {
            $resumeUsed = $_POST['resume_choice'] ?? ($student['resume'] ?? 'default');
            echo "DEBUG: Resume used: " . $resumeUsed . "<br>";
            
            $stmt = $pdo->prepare('INSERT INTO applications (student_id, job_id, resume_used, status) VALUES (?, ?, ?, ?)');
            $result = $stmt->execute([$student['student_id'], $jobId, $resumeUsed, 'Applied']);
            
            echo "DEBUG: Execute result: " . ($result ? 'SUCCESS' : 'FAILED') . "<br>";
            
            if ($result) {
                echo "<h2 style='color: green;'>✓ Application Submitted!</h2>";
                echo "<a href='applications.php'>View My Applications</a>";
            }
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "DEBUG: Not step 4, step is: " . $step . "<br>";
    }
} else {
    echo "DEBUG: Not a POST request<br>";
}

?>

<form method="post">
    <input type="hidden" name="step" value="4">
    <input type="hidden" name="resume_choice" value="<?= $student['resume'] ?? 'default' ?>">
    <button type="submit">Test Submit</button>
</form>

