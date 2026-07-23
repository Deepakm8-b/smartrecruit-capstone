<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$userId = $_SESSION['user_id'];
$student = getStudentByUserId($pdo, $userId);
$studentId = $student['student_id'];
$error = '';
$success = '';

// Handle download resume
if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['resume_id'])) {
    $resumeId = intval($_GET['resume_id']);
    
    $stmt = $pdo->prepare('SELECT * FROM resume_versions WHERE resume_id = ? AND student_id = ?');
    $stmt->execute([$resumeId, $studentId]);
    $resume = $stmt->fetch();
    
    if ($resume) {
        $filePath = '/Applications/XAMPP/xamppfiles/htdocs/smartrecruit/uploads/resumes/' . $resume['file_path'];
        
        if (file_exists($filePath)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($resume['file_name']) . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Pragma: no-cache');
            header('Expires: 0');
            
            readfile($filePath);
            exit;
        } else {
            $error = 'File not found: ' . $filePath;
        }
    }
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['resume_file'])) {
    $file = $_FILES['resume_file'];
    
    error_log('=== UPLOAD DEBUG ===');
    error_log('Name: ' . $file['name']);
    error_log('Tmp: ' . $file['tmp_name']);
    error_log('Error: ' . $file['error']);
    error_log('Type: ' . $file['type']);
    error_log('Size from POST: ' . $file['size']);
    error_log('Tmp file exists: ' . (file_exists($file['tmp_name']) ? 'YES' : 'NO'));
    error_log('Tmp file size: ' . (file_exists($file['tmp_name']) ? filesize($file['tmp_name']) : 'N/A'));
    error_log('Tmp file readable: ' . (is_readable($file['tmp_name']) ? 'YES' : 'NO'));
    error_log('===================');
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize (php.ini)',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'No temp directory',
            UPLOAD_ERR_CANT_WRITE => 'Cannot write to temp',
            UPLOAD_ERR_EXTENSION => 'Extension blocked'
        ];
        $error = 'Upload error: ' . ($uploadErrors[$file['error']] ?? 'Unknown');
    }
    else if ($file['size'] === 0) {
        $error = 'File appears to be empty (0 bytes). Check your file and try again.';
    }
    else {
        $maxSize = 5 * 1024 * 1024;
        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $allowedExtensions = ['pdf', 'doc', 'docx'];
        
        if ($file['size'] > $maxSize) {
            $error = 'File size exceeds 5MB limit.';
        }
        elseif (!in_array($file['type'], $allowedTypes)) {
            $error = 'Only PDF, DOC, and DOCX files are allowed. You uploaded: ' . $file['type'];
        }
        else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExtensions)) {
                $error = 'Invalid file extension. Use PDF, DOC, or DOCX.';
            }
            else {
                $uploadDir = '/Applications/XAMPP/xamppfiles/htdocs/smartrecruit/uploads/resumes/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                if (!is_writable($uploadDir)) {
                    $error = 'Upload directory not writable.';
                } else {
                    $fileName = 'resume_' . $studentId . '_' . time() . '.' . $ext;
                    $filePath = $uploadDir . $fileName;
                    
                    // Use file_get_contents + file_put_contents (more reliable)
                    try {
                        $fileContent = file_get_contents($file['tmp_name']);
                        
                        if ($fileContent === false) {
                            $error = 'Could not read uploaded file.';
                        } else if (strlen($fileContent) === 0) {
                            $error = 'File is empty after reading.';
                        } else if (file_put_contents($filePath, $fileContent) === false) {
                            $error = 'Could not write file to server.';
                        } else {
                            $uploadedSize = filesize($filePath);
                            error_log('File written successfully: ' . $filePath . ' Size: ' . $uploadedSize);
                            
                            try {
                                $stmt = $pdo->prepare('UPDATE resume_versions SET is_primary = FALSE WHERE student_id = ?');
                                $stmt->execute([$studentId]);
                                
                                $stmt = $pdo->prepare('INSERT INTO resume_versions (student_id, file_name, file_path, file_size, file_type, is_primary, uploaded_date) VALUES (?, ?, ?, ?, ?, TRUE, NOW())');
                                $stmt->execute([$studentId, $file['name'], $fileName, $uploadedSize, $ext]);
                                
                                $stmt = $pdo->prepare('UPDATE students SET resume = ? WHERE student_id = ?');
                                $stmt->execute([$fileName, $studentId]);
                                
                                $success = 'Resume uploaded successfully! (' . round($uploadedSize / 1024, 1) . ' KB)';
                            } catch (Exception $e) {
                                $error = 'Database error: ' . $e->getMessage();
                                unlink($filePath);
                            }
                        }
                    } catch (Exception $e) {
                        $error = 'File processing error: ' . $e->getMessage();
                    }
                    
                    // Clean up temp file
                    @unlink($file['tmp_name']);
                }
            }
        }
    }
}

// Handle delete resume
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['resume_id'])) {
    $resumeId = intval($_GET['resume_id']);
    
    $stmt = $pdo->prepare('SELECT * FROM resume_versions WHERE resume_id = ? AND student_id = ?');
    $stmt->execute([$resumeId, $studentId]);
    $resume = $stmt->fetch();
    
    if ($resume) {
        $filePath = '/Applications/XAMPP/xamppfiles/htdocs/smartrecruit/uploads/resumes/' . $resume['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        $stmt = $pdo->prepare('DELETE FROM resume_versions WHERE resume_id = ?');
        $stmt->execute([$resumeId]);
        
        $stmt = $pdo->prepare('SELECT * FROM resume_versions WHERE student_id = ? LIMIT 1');
        $stmt->execute([$studentId]);
        $nextResume = $stmt->fetch();
        
        if ($nextResume) {
            $stmt = $pdo->prepare('UPDATE resume_versions SET is_primary = TRUE WHERE resume_id = ?');
            $stmt->execute([$nextResume['resume_id']]);
            
            $stmt = $pdo->prepare('UPDATE students SET resume = ? WHERE student_id = ?');
            $stmt->execute([$nextResume['file_path'], $studentId]);
        } else {
            $stmt = $pdo->prepare('UPDATE students SET resume = NULL WHERE student_id = ?');
            $stmt->execute([$studentId]);
        }
        
        $success = 'Resume deleted successfully!';
    }
}

// Handle set as primary
if (isset($_GET['action']) && $_GET['action'] === 'setprimary' && isset($_GET['resume_id'])) {
    $resumeId = intval($_GET['resume_id']);
    
    $stmt = $pdo->prepare('SELECT * FROM resume_versions WHERE resume_id = ? AND student_id = ?');
    $stmt->execute([$resumeId, $studentId]);
    $resume = $stmt->fetch();
    
    if ($resume) {
        $stmt = $pdo->prepare('UPDATE resume_versions SET is_primary = FALSE WHERE student_id = ?');
        $stmt->execute([$studentId]);
        
        $stmt = $pdo->prepare('UPDATE resume_versions SET is_primary = TRUE WHERE resume_id = ?');
        $stmt->execute([$resumeId]);
        
        $stmt = $pdo->prepare('UPDATE students SET resume = ? WHERE student_id = ?');
        $stmt->execute([$resume['file_path'], $studentId]);
        
        $success = 'Resume set as primary!';
    }
}

// Get all resumes
$stmt = $pdo->prepare('SELECT * FROM resume_versions WHERE student_id = ? ORDER BY is_primary DESC, uploaded_date DESC');
$stmt->execute([$studentId]);
$resumes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Resume Management — SmartRecruit</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; background: #f5f7fa; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { margin-bottom: 32px; }
        .header h1 { font-size: 24px; font-weight: 700; color: #1f2937; margin-bottom: 8px; }
        .header p { font-size: 14px; color: #6b7280; }
        .back-link { color: #1e40af; text-decoration: none; font-size: 14px; font-weight: 600; margin-bottom: 20px; display: inline-block; }
        .alert { padding: 12px 16px; border-radius: 4px; margin-bottom: 20px; font-size: 13px; }
        .alert-success { background: #dcfce7; border-left: 4px solid #10b981; color: #065f46; }
        .alert-error { background: #fee2e2; border-left: 4px solid #dc2626; color: #991b1b; }
        .card { background: white; padding: 24px; border: 1px solid #e5e7eb; margin-bottom: 20px; }
        .upload-area { border: 2px dashed #d1d5db; border-radius: 4px; padding: 32px 20px; text-align: center; transition: all 0.2s; cursor: pointer; }
        .upload-area:hover { border-color: #1e40af; background: #f0f9ff; }
        .upload-area.dragover { border-color: #1e40af; background: #f0f9ff; }
        .upload-icon { font-size: 32px; margin-bottom: 12px; }
        .upload-text { font-size: 14px; font-weight: 600; color: #1f2937; margin-bottom: 4px; }
        .upload-hint { font-size: 12px; color: #6b7280; }
        .file-preview { margin-top: 12px; padding: 12px; background: #f0f9ff; border: 1px solid #bfdbfe; border-radius: 4px; display: none; }
        .file-preview.show { display: block; }
        .file-preview-name { font-weight: 600; font-size: 13px; color: #1e40af; }
        .file-preview-size { font-size: 12px; color: #6b7280; margin-top: 4px; }
        .file-input { display: none; }
        .btn { padding: 10px 16px; border: none; font-weight: 600; cursor: pointer; font-size: 13px; border-radius: 4px; }
        .btn-primary { background: #1e40af; color: white; }
        .btn-primary:hover { background: #1e3a8a; }
        .btn-secondary { background: #f3f4f6; color: #1f2937; }
        .btn-secondary:hover { background: #e5e7eb; }
        .btn-danger { background: #fee2e2; color: #dc2626; }
        .btn-danger:hover { background: #fecaca; }
        .resumes-list { margin-top: 24px; }
        .resume-item { background: #f9fafb; padding: 16px; border: 1px solid #e5e7eb; border-radius: 4px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
        .resume-info { flex: 1; }
        .resume-name { font-weight: 600; font-size: 14px; color: #1f2937; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
        .resume-meta { font-size: 12px; color: #6b7280; }
        .primary-badge { background: #dcfce7; color: #047857; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .resume-actions { display: flex; gap: 8px; }
        .empty-state { text-align: center; padding: 32px 20px; color: #6b7280; }
        .empty-icon { font-size: 48px; margin-bottom: 12px; }
    </style>
</head>
<body>

<div class="container">
    
    <a href="profile.php" class="back-link">← Back to Profile</a>
    
    <div class="header">
        <h1>📄 Resume Management</h1>
        <p>Upload and manage your resumes</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">✗ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- UPLOAD SECTION -->
    <div class="card">
        <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 16px; color: #1f2937;">Upload New Resume</h2>
        
        <form method="post" enctype="multipart/form-data" id="uploadForm">
            <div class="upload-area" id="uploadArea">
                <div class="upload-icon">📤</div>
                <div class="upload-text">Drag and drop your resume here</div>
                <div class="upload-hint">or click to browse (PDF, DOC, DOCX - Max 5MB)</div>
                <input type="file" id="fileInput" name="resume_file" accept=".pdf,.doc,.docx" class="file-input">
                
                <div class="file-preview" id="filePreview">
                    <div class="file-preview-name" id="previewName"></div>
                    <div class="file-preview-size" id="previewSize"></div>
                </div>
            </div>
            
            <div style="margin-top: 16px;">
                <button type="submit" class="btn btn-primary">Upload Resume</button>
            </div>
        </form>
    </div>

    <!-- RESUMES LIST -->
    <div class="card">
        <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 16px; color: #1f2937;">Your Resumes</h2>
        
        <?php if (count($resumes) > 0): ?>
            <div class="resumes-list">
                <?php foreach ($resumes as $resume): ?>
                    <div class="resume-item">
                        <div class="resume-info">
                            <div class="resume-name">
                                📄 <?= htmlspecialchars($resume['file_name']) ?>
                                <?php if ($resume['is_primary']): ?>
                                    <span class="primary-badge">Primary</span>
                                <?php endif; ?>
                            </div>
                            <div class="resume-meta">
                                Uploaded: <?= date('M d, Y', strtotime($resume['uploaded_date'])) ?>
                                | Size: <?= round($resume['file_size'] / 1024, 1) ?> KB
                                | Type: <?= strtoupper($resume['file_type']) ?>
                            </div>
                        </div>
                        <div class="resume-actions">
                            <a href="resume_management.php?action=download&resume_id=<?= $resume['resume_id'] ?>" class="btn btn-secondary">Download</a>
                            <?php if (!$resume['is_primary']): ?>
                                <a href="resume_management.php?action=setprimary&resume_id=<?= $resume['resume_id'] ?>" class="btn btn-secondary">Set Primary</a>
                            <?php endif; ?>
                            <a href="resume_management.php?action=delete&resume_id=<?= $resume['resume_id'] ?>" onclick="return confirm('Are you sure?');" class="btn btn-danger">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <p>No resumes uploaded yet</p>
                <p style="font-size: 12px; margin-top: 8px;">Upload your first resume using the form above</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
const uploadArea = document.getElementById('uploadArea');
const fileInput = document.getElementById('fileInput');
const filePreview = document.getElementById('filePreview');
const previewName = document.getElementById('previewName');
const previewSize = document.getElementById('previewSize');

uploadArea.addEventListener('click', () => fileInput.click());

fileInput.addEventListener('change', () => {
    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        const sizeMB = (file.size / 1024 / 1024).toFixed(2);
        
        previewName.textContent = '✓ ' + file.name;
        previewSize.textContent = 'Size: ' + sizeMB + ' MB';
        filePreview.classList.add('show');
    }
});

uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('dragover');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        
        const file = files[0];
        const sizeMB = (file.size / 1024 / 1024).toFixed(2);
        
        previewName.textContent = '✓ ' + file.name;
        previewSize.textContent = 'Size: ' + sizeMB + ' MB';
        filePreview.classList.add('show');
    }
});
</script>

</body>
</html>
