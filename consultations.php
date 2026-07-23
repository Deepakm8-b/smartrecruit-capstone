<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] !== 'student') {
    header('Location: dashboard.php');
    exit;
}

$userId = $_SESSION['user_id'];
$student = getStudentByUserId($pdo, $userId);

if (!$student) {
    header('Location: login.php');
    exit;
}

$isPremium = $student['is_premium'] ?? 0;
$consultations = [];

if ($isPremium) {
    try {
        $stmt = $pdo->prepare('
            SELECT consultation_id, scheduled_date, status, admin_notes, recommendations
            FROM consultations
            WHERE student_id = ?
            ORDER BY scheduled_date DESC
        ');
        $stmt->execute([$student['student_id']]);
        $consultations = $stmt->fetchAll();
    } catch (Exception $e) {
        $consultations = [];
    }
}

$stmt = $pdo->prepare('SELECT email FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$navEmail = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Consultations — SmartRecruit</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
<?php require 'nav.php'; ?>
<main class="app-main">
    <h1>🎯 Career Consultations</h1>
    
    <?php if (!$isPremium): ?>
        <div class="card" style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 24px;">
            <h2 style="margin-top: 0; color: #b45309;">🔒 Premium Feature</h2>
            <p>Career consultations available for Premium members only.</p>
            <a href="profile.php" class="btn">Upgrade to Premium</a>
        </div>
    <?php else: ?>
        <div class="card">
            <h2>Your Consultations (<?= count($consultations) ?>)</h2>
            <?php if (count($consultations) > 0): ?>
                <?php foreach ($consultations as $con): ?>
                    <div style="background: #f0f9ff; border-left: 4px solid #0284c7; padding: 16px; border-radius: 6px; margin-bottom: 12px;">
                        <div><strong>📅 <?= date('M d, Y — H:i', strtotime($con['scheduled_date'])) ?></strong>
                        <span style="font-size: 11px; font-weight: 600; padding: 4px 8px; border-radius: 4px; margin-left: 8px; background: <?= $con['status'] === 'completed' ? '#d1fae5; color: #047857' : '#fef3c7; color: #b45309' ?>;"><?= ucfirst($con['status']) ?></span>
                        </div>
                        <?php if ($con['admin_notes']): ?>
                            <div style="margin-top: 12px; padding: 12px; background: white; border-radius: 4px;">
                                <strong>✓ Expert Feedback:</strong>
                                <p style="margin: 8px 0 0 0;"><?= nl2br(htmlspecialchars($con['admin_notes'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="muted">No consultations yet. <a href="book_consultation.php">Book one</a>.</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Book a Consultation</h2>
            <p class="muted">Get expert guidance on your career path and skill development.</p>
            <a href="book_consultation.php" class="btn" style="width: 100%;">📅 Book Now</a>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
