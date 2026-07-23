<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$student = getStudentByUserId($pdo, $userId);
$studentId = $student['student_id'] ?? 0;

// Check premium access
$stmt = $pdo->prepare("SELECT status FROM subscriptions WHERE student_id = ? AND status = 'active' LIMIT 1");
$stmt->execute([$studentId]);
$hasPremium = $stmt->fetch();

if (!$hasPremium) {
    header('Location: premium.php');
    exit;
}

$updateSuccess = false;
$updateError = '';
$uploadSuccess = false;
$uploadError = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $progressId = intval($_POST['progress_id'] ?? 0);
    $newStatus = $_POST['status'] ?? 'not_started';
    
    if (in_array($newStatus, ['not_started', 'in_progress', 'completed'])) {
        try {
            $completedAt = ($newStatus === 'completed') ? date('Y-m-d H:i:s') : NULL;
            $stmt = $pdo->prepare('UPDATE student_roadmap_progress SET status = ?, completed_at = ? WHERE progress_id = ? AND roadmap_id IN (SELECT roadmap_id FROM student_roadmaps WHERE student_id = ?)');
            $stmt->execute([$newStatus, $completedAt, $progressId, $studentId]);
            $updateSuccess = true;
        } catch (Exception $e) {
            $updateError = 'Failed to update step status.';
        }
    }
}

// Handle file uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['proof_file']) && isset($_POST['upload_proof'])) {
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
                    $stmt = $pdo->prepare('SELECT rs.step_id FROM student_roadmap_progress srp JOIN roadmap_steps rs ON srp.step_id = rs.step_id WHERE srp.progress_id = ? AND srp.roadmap_id IN (SELECT roadmap_id FROM student_roadmaps WHERE student_id = ?)');
                    $stmt->execute([$progressId, $studentId]);
                    $stepData = $stmt->fetch();
                    
                    if ($stepData) {
                        $stmt = $pdo->prepare('INSERT INTO step_completion_proofs (progress_id, student_id, step_id, file_path, file_name, file_type, status) VALUES (?, ?, ?, ?, ?, ?, "pending")');
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
        $uploadError = 'Please select a file.';
    }
}

// Fetch roadmap
$stmt = $pdo->prepare('SELECT sr.roadmap_id, sr.assigned_date, sr.target_completion_date, rt.template_name, rt.description FROM student_roadmaps sr JOIN roadmap_templates rt ON sr.template_id = rt.template_id WHERE sr.student_id = ? LIMIT 1');
$stmt->execute([$studentId]);
$roadmap = $stmt->fetch();

$roadmapId = $roadmap['roadmap_id'] ?? 0;
$progressSteps = [];
$completedCount = 0;

if ($roadmapId > 0) {
    $stmt = $pdo->prepare('SELECT rs.step_id, rs.step_number, rs.step_title, rs.description, rs.duration_weeks, srp.progress_id, srp.status, srp.started_at, srp.completed_at FROM roadmap_steps rs LEFT JOIN student_roadmap_progress srp ON rs.step_id = srp.step_id AND srp.roadmap_id = ? WHERE rs.template_id = (SELECT template_id FROM student_roadmaps WHERE roadmap_id = ?) ORDER BY rs.step_number ASC');
    $stmt->execute([$roadmapId, $roadmapId]);
    $progressSteps = $stmt->fetchAll();
    
    foreach ($progressSteps as $step) {
        if ($step['status'] === 'completed') {
            $completedCount++;
        }
    }
}

$totalSteps = count($progressSteps);
$progressPercent = ($totalSteps > 0) ? round(($completedCount / $totalSteps) * 100) : 0;

$navEmail = '';
$stmt = $pdo->prepare('SELECT email FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$navEmail = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Roadmap — SmartRecruit</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .roadmap-container { max-width: 1000px; margin: 0 auto; padding: 40px 20px; }
        .roadmap-header { margin-bottom: 40px; }
        .roadmap-header a { color: #1e40af; text-decoration: none; font-weight: 600; font-size: 14px; }
        .roadmap-header h1 { font-size: 28px; font-weight: 700; margin: 16px 0 8px 0; }
        .roadmap-header p { color: #6b7280; margin: 0; }
        
        .success-banner { background: #d1fae5; border: 1px solid #6ee7b7; color: #047857; padding: 12px 16px; border-radius: 6px; margin-bottom: 24px; }
        .error-banner { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 24px; }
        
        .roadmap-info { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 24px; }
        .roadmap-info h2 { font-size: 18px; font-weight: 700; margin: 0 0 8px 0; }
        .roadmap-info p { color: #6b7280; margin: 0; font-size: 14px; }
        
        .progress-section { margin-bottom: 32px; }
        .progress-label { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .progress-label-text { font-weight: 600; color: #1f2937; }
        .progress-percent { font-weight: 700; color: #1e40af; }
        .progress-bar { height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden; }
        .progress-fill { height: 100%; background: #10b981; transition: width 0.3s ease; }
        
        .steps-timeline { position: relative; padding: 20px 0; }
        .step-item { display: flex; gap: 20px; margin-bottom: 24px; position: relative; }
        .step-item:not(:last-child)::before { content: ''; position: absolute; left: 20px; top: 50px; width: 2px; height: calc(100% + 24px); background: #e5e7eb; }
        
        .step-circle { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px; flex-shrink: 0; }
        .step-circle.not_started { background: #f3f4f6; color: #9ca3af; border: 2px solid #d1d5db; }
        .step-circle.in_progress { background: #fef3c7; color: #92400e; border: 2px solid #fcd34d; }
        .step-circle.completed { background: #d1fae5; color: #047857; border: 2px solid #6ee7b7; }
        
        .step-content { flex: 1; }
        .step-title { font-size: 16px; font-weight: 700; color: #1f2937; margin-bottom: 4px; }
        .step-description { font-size: 13px; color: #6b7280; margin-bottom: 8px; line-height: 1.5; }
        .step-duration { font-size: 12px; color: #9ca3af; }
        
        .step-actions { display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; }
        .status-btn { padding: 6px 12px; border: none; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer; }
        .status-btn-not-started { background: #f3f4f6; color: #1f2937; }
        .status-btn-not-started:hover { background: #e5e7eb; }
        .status-btn-in-progress { background: #fef3c7; color: #92400e; }
        .status-btn-in-progress:hover { background: #fcd34d; }
        .status-btn-completed { background: #d1fae5; color: #047857; }
        .status-btn-completed:hover { background: #a7f3d0; }
        .status-btn-upload { background: #8b5cf6; color: white; }
        .status-btn-upload:hover { background: #7c3aed; }
        
        .file-input-hidden { display: none; }
        
        .no-roadmap { text-align: center; color: #6b7280; padding: 40px 20px; background: white; border-radius: 8px; border: 1px solid #e5e7eb; }
        .no-roadmap-icon { font-size: 48px; margin-bottom: 16px; }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="roadmap-container">
    <div class="roadmap-header">
        <a href="dashboard.php">← Back to Dashboard</a>
        <h1>🎯 Your Career Roadmap</h1>
        <p>Track your professional development journey</p>
    </div>

    <?php if ($updateSuccess): ?>
        <div class="success-banner">✓ Step status updated successfully!</div>
    <?php endif; ?>

    <?php if ($updateError): ?>
        <div class="error-banner">✗ <?= htmlspecialchars($updateError) ?></div>
    <?php endif; ?>

    <?php if ($uploadSuccess): ?>
        <div class="success-banner">✓ Proof uploaded successfully! Awaiting admin review.</div>
    <?php endif; ?>

    <?php if ($uploadError): ?>
        <div class="error-banner">✗ <?= htmlspecialchars($uploadError) ?></div>
    <?php endif; ?>

    <?php if ($roadmap): ?>
        <div class="roadmap-info">
            <h2><?= htmlspecialchars($roadmap['template_name']) ?></h2>
            <p><?= htmlspecialchars($roadmap['description']) ?></p>
        </div>

        <div class="progress-section">
            <div class="progress-label">
                <span class="progress-label-text">Overall Progress</span>
                <span class="progress-percent"><?= $progressPercent ?>%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= $progressPercent ?>%;"></div>
            </div>
            <p style="margin-top: 8px; font-size: 13px; color: #6b7280;"><?= $completedCount ?> of <?= $totalSteps ?> steps completed</p>
        </div>

        <?php if (count($progressSteps) > 0): ?>
            <div class="steps-timeline">
                <?php foreach ($progressSteps as $index => $step): ?>
                    <?php $status = $step['status'] ?? 'not_started'; ?>
                    <div class="step-item">
                        <div class="step-circle <?= $status ?>">
                            <?php if ($status === 'completed'): ?>
                                ✓
                            <?php else: ?>
                                <?= $step['step_number'] ?>
                            <?php endif; ?>
                        </div>
                        <div class="step-content">
                            <div class="step-title"><?= htmlspecialchars($step['step_title']) ?></div>
                            <div class="step-description"><?= htmlspecialchars($step['description']) ?></div>
                            <?php if ($step['duration_weeks']): ?>
                                <div class="step-duration">⏱️ Duration: <?= intval($step['duration_weeks']) ?> weeks</div>
                            <?php endif; ?>
                            
                            <?php if ($step['progress_id']): ?>
                                <div class="step-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="progress_id" value="<?= $step['progress_id'] ?>">
                                        <input type="hidden" name="status" value="not_started">
                                        <button type="submit" name="update_status" class="status-btn status-btn-not-started">Not Started</button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="progress_id" value="<?= $step['progress_id'] ?>">
                                        <input type="hidden" name="status" value="in_progress">
                                        <button type="submit" name="update_status" class="status-btn status-btn-in-progress">In Progress</button>
                                    </form>
                                    <?php
                                        $stmt = $pdo->prepare("SELECT proof_id, status FROM step_completion_proofs WHERE progress_id = ? LIMIT 1");
                                        $stmt->execute([$step['progress_id']]);
                                        $proof = $stmt->fetch();
                                        $proofApproved = ($proof && $proof['status'] === 'approved');
                                    ?>
                                    <div class="status-btn" style="background: <?= $proofApproved ? '#10b981' : '#d1d5db' ?>; color: white; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; display: inline-block;">
                                        <?= $proofApproved ? '✓ Approved' : '⏳ Awaiting Approval' ?>
                                    </div>
                                    <form method="POST" enctype="multipart/form-data" style="display: inline;">
                                        <input type="hidden" name="progress_id" value="<?= $step['progress_id'] ?>">
                                        <input type="hidden" name="upload_proof" value="1">
                                        <input type="file" name="proof_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="file-input-hidden" id="file_<?= $step['progress_id'] ?>" onchange="this.form.submit();">
                                        <button type="button" class="status-btn status-btn-upload" onclick="document.getElementById('file_<?= $step['progress_id'] ?>').click();">📤 Upload Proof</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="no-roadmap">
            <div class="no-roadmap-icon">🎯</div>
            <p style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 8px;">No Career Roadmap Assigned</p>
            <p>Your career roadmap will be assigned by an admin. Check back soon!</p>
            <p style="margin-top: 16px;"><a href="dashboard.php" style="color: #1e40af;">← Return to Dashboard</a></p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
