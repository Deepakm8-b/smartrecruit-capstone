<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) { die('Not logged in'); }

$userId = $_SESSION['user_id'];
$student = getStudentByUserId($pdo, $userId);
$jobId = intval($_GET['job_id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM jobs WHERE job_id = ?');
$stmt->execute([$jobId]);
$job = $stmt->fetch();

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "POST received<br>";
    echo "Student ID: " . $student['student_id'] . "<br>";
    echo "Job ID: " . $jobId . "<br>";
    echo "Resume: " . ($student['resume'] ?? 'NULL') . "<br>";
    
    try {
        $stmt = $pdo->prepare('INSERT INTO applications (student_id, job_id, resume_used, status) VALUES (?, ?, ?, ?)');
        $result = $stmt->execute([$student['student_id'], $jobId, $student['resume'] ?? 'default', 'Applied']);
        echo "Insert result: " . ($result ? 'SUCCESS' : 'FAILED') . "<br>";
        $success = true;
    } catch (Exception $e) {
        $error = 'ERROR: ' . $e->getMessage();
        echo $error . "<br>";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Apply Debug</title></head>
<body style="font-family: Arial; padding: 20px;">

<?php if ($success): ?>
    <h2 style="color: green;">✓ Application Submitted!</h2>
<?php else: ?>
    <h1>Apply for <?= $job['job_title'] ?></h1>
    <form method="post">
        <button type="submit">Submit</button>
    </form>
<?php endif; ?>

</body>
</html>
