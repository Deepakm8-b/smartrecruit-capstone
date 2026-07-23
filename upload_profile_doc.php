<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    die('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file'])) {
    header('Location: profile.php');
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT student_id FROM students WHERE user_id = ?');
$stmt->execute([$userId]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: profile.php');
    exit;
}

$studentId = $student['student_id'];
$file = $_FILES['file'];
$certName = trim($_POST['cert_name'] ?? '');
$issuedBy = trim($_POST['issued_by'] ?? '');
$issueDate = trim($_POST['issue_date'] ?? '');

$errors = [];

if (empty($certName)) { $errors[] = 'Certificate name required'; }
if ($file['size'] > 5242880) { $errors[] = 'File too large (max 5MB)'; }

$allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$fileType = mime_content_type($file['tmp_name']);
if (!in_array($fileType, $allowedTypes)) { $errors[] = 'Only PDF and DOCX allowed'; }

if (!empty($errors)) {
    $_SESSION['upload_error'] = implode('; ', $errors);
    header('Location: profile.php');
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$uploadFilename = $studentId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$uploadDir = __DIR__ . '/uploads/documents/';
if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }

$uploadPath = $uploadDir . $uploadFilename;

if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    $_SESSION['upload_error'] = 'File save failed';
    header('Location: profile.php');
    exit;
}

try {
    $stmt = $pdo->prepare('
        INSERT INTO certificates (student_id, cert_name, issued_by, issue_date, file_path, file_type)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([$studentId, $certName, !empty($issuedBy) ? $issuedBy : null, !empty($issueDate) ? $issueDate : null, 'uploads/documents/' . $uploadFilename, $fileType]);
    $_SESSION['upload_success'] = 'Document uploaded';
    header('Location: profile.php');
    exit;
} catch (Exception $e) {
    unlink($uploadPath);
    $_SESSION['upload_error'] = 'Save failed: ' . $e->getMessage();
    header('Location: profile.php');
    exit;
}
?>
