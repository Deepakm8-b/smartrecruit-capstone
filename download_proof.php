<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$student = getStudentByUserId($pdo, $userId);
$studentId = $student['student_id'] ?? 0;

$proofId = intval($_GET['proof_id'] ?? 0);

if ($proofId > 0) {
    $stmt = $pdo->prepare('
        SELECT file_path, file_name 
        FROM step_completion_proofs 
        WHERE proof_id = ? AND student_id = ?
    ');
    $stmt->execute([$proofId, $studentId]);
    $proof = $stmt->fetch();
    
    if ($proof && file_exists($proof['file_path'])) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($proof['file_name']) . '"');
        header('Content-Length: ' . filesize($proof['file_path']));
        readfile($proof['file_path']);
        exit;
    }
}

header('Location: my_proofs.php');
exit;
?>
