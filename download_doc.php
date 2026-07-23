<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    die('Unauthorized');
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    die('Invalid request');
}

$userId = $_SESSION['user_id'];
$certId = (int)$_GET['id'];

$stmt = $pdo->prepare('
    SELECT c.cert_id, c.cert_name, c.file_path, c.file_type, s.student_id
    FROM certificates c
    JOIN students s ON c.student_id = s.student_id
    WHERE c.cert_id = ? AND s.user_id = ?
');
$stmt->execute([$certId, $userId]);
$cert = $stmt->fetch();

if (!$cert) {
    http_response_code(404);
    die('Not found');
}

$filePath = __DIR__ . '/' . $cert['file_path'];

if (strpos(realpath($filePath), realpath(__DIR__)) !== 0 || !file_exists($filePath)) {
    http_response_code(404);
    die('File not found');
}

header('Content-Type: ' . $cert['file_type']);
header('Content-Disposition: attachment; filename="' . basename($cert['cert_name']) . '.' . pathinfo($filePath, PATHINFO_EXTENSION) . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
?>
