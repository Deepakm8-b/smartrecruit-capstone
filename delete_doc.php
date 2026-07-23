<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    header('Location: profile.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: profile.php');
    exit;
}

$userId = $_SESSION['user_id'];
$certId = (int)$_GET['id'];

$stmt = $pdo->prepare('
    SELECT c.cert_id, c.file_path, s.student_id
    FROM certificates c
    JOIN students s ON c.student_id = s.student_id
    WHERE c.cert_id = ? AND s.user_id = ?
');
$stmt->execute([$certId, $userId]);
$cert = $stmt->fetch();

if (!$cert) {
    $_SESSION['delete_error'] = 'Document not found';
    header('Location: profile.php');
    exit;
}

try {
    $filePath = __DIR__ . '/' . $cert['file_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    $stmt = $pdo->prepare('DELETE FROM certificates WHERE cert_id = ?');
    $stmt->execute([$certId]);
    
    $_SESSION['delete_success'] = 'Document deleted';
    header('Location: profile.php');
    exit;
} catch (Exception $e) {
    $_SESSION['delete_error'] = 'Delete failed';
    header('Location: profile.php');
    exit;
}
?>
