<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$resumeId = intval($_GET['resume_id'] ?? 0);

if ($resumeId > 0) {
    $student = getStudentByUserId($pdo, $userId);
    $studentId = $student['student_id'] ?? 0;
    
    $stmt = $pdo->prepare('SELECT file_path, file_name FROM resume_versions WHERE resume_id = ? AND student_id = ?');
    $stmt->execute([$resumeId, $studentId]);
    $resume = $stmt->fetch();
    
    if ($resume && file_exists($resume['file_path'])) {
        $filePath = $resume['file_path'];
        $fileName = $resume['file_name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // For PDFs, display in iframe
        if ($fileExt === 'pdf') {
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <title><?= htmlspecialchars($fileName) ?></title>
                <style>
                    body { margin: 0; padding: 0; }
                    #pdf-viewer { width: 100%; height: 100vh; }
                    .toolbar { background: #333; color: white; padding: 10px; text-align: center; }
                    a { color: white; text-decoration: none; margin-right: 20px; }
                </style>
            </head>
            <body>
                <div class="toolbar">
                    <a href="javascript:history.back()">← Back</a>
                    <a href="download_resume.php?resume_id=<?= $resumeId ?>">⬇ Download</a>
                    <span><?= htmlspecialchars($fileName) ?></span>
                </div>
                <iframe id="pdf-viewer" src="<?= htmlspecialchars($filePath) ?>"></iframe>
            </body>
            </html>
            <?php
        } else {
            // For non-PDFs, redirect to download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . htmlspecialchars($fileName) . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
        }
        exit;
    }
}

header('Location: dashboard.php');
exit;
?>
