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

$uploadError = '';
$uploadSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['proof_file'])) {
    $progressId = intval($_POST['progress_id'] ?? 0);
    $file = $_FILES['proof_file'];
    
    if ($progressId > 0 && $file['size'] > 0) {
        $maxSize = 5 * 1024 * 1024;
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        
        if ($file['size'] > $maxSize) {
            $uploadError = 'File size exceeds 5MB limit.';
        } elseif (!in_array($file['type'], $allowedTypes)) {
            $uploadError = 'Invalid file type. Allowed: PDF, JPG, PNG, DOC, DOCX';
        } else {
            $uploadDir = '/Applications/XAMPP/xamppfiles/htdocs/smartrecruit/uploads/proofs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = $studentId . '_' . $progressId . '_' . time() . '_' . basename($file['name']);
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                try {
                    $stmt = $pdo->prepare('
                        SELECT rs.step_id 
                        FROM student_roadmap_progress srp
                        JOIN roadmap_steps rs ON srp.step_id = rs.step_id
                        WHERE srp.progress_id = ? AND srp.roadmap_id IN (
                            SELECT roadmap_id FROM student_roadmaps WHERE student_id = ?
                        )
                    ');
                    $stmt->execute([$progressId, $studentId]);
                    $stepData = $stmt->fetch();
                    
                    if ($stepData) {
                        $stmt = $pdo->prepare('
                            INSERT INTO step_completion_proofs 
                            (progress_id, student_id, step_id, file_path, file_name, file_type, status) 
                            VALUES (?, ?, ?, ?, ?, ?, "pending")
                        ');
                        $stmt->execute([$progressId, $studentId, $stepData['step_id'], $filePath, $fileName, $file['type']]);
                        $uploadSuccess = true;
                    } else {
                        $uploadError = 'Invalid progress ID.';
                        unlink($filePath);
                    }
                } catch (Exception $e) {
                    $uploadError = 'Failed to save proof: ' . $e->getMessage();
                    unlink($filePath);
                }
            } else {
                $uploadError = 'Failed to upload file.';
            }
        }
    } else {
        $uploadError = 'Invalid file or progress ID.';
    }
}

if ($uploadSuccess) {
    header('Location: view_roadmap.php?upload=success');
    exit;
} else {
    header('Location: view_roadmap.php?upload=error&msg=' . urlencode($uploadError));
    exit;
}
?>
