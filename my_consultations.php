<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$userId = $_SESSION['user_id'];
$student = getStudentByUserId($pdo, $userId);

// Get consultations
$stmt = $pdo->prepare('SELECT * FROM consultations WHERE student_id = ? ORDER BY consultation_date DESC');
$stmt->execute([$student['student_id']]);
$consultations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>My Consultations — SmartRecruit</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial; background: #f8fafc; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        .header { margin-bottom: 24px; }
        .header h1 { font-size: 20px; font-weight: 800; margin-bottom: 4px; }
        .header p { color: #666; font-size: 13px; }
        .info-box { background: #dbeafe; border-left: 4px solid #1e40af; padding: 12px; margin-bottom: 24px; font-size: 12px; }
        .consultation-card { background: white; padding: 20px; border: 1px solid #e5e7eb; margin-bottom: 16px; display: grid; grid-template-columns: 100px 1fr 120px; gap: 16px; align-items: start; }
        .date-box { background: #f3f4f6; padding: 12px; text-align: center; }
        .date-day { font-size: 18px; font-weight: 800; color: #1e40af; }
        .date-month { font-size: 11px; color: #666; }
        .consultation-info h3 { font-size: 14px; font-weight: 800; margin-bottom: 4px; }
        .consultation-meta { font-size: 12px; color: #666; margin-bottom: 8px; }
        .consultation-status { font-size: 11px; font-weight: 600; padding: 4px 8px; background: #dcfce7; color: #047857; display: inline-block; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-completed { background: #dcfce7; color: #047857; }
        .empty-state { background: white; padding: 40px; text-align: center; border: 1px solid #e5e7eb; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="header">
        <h1>📅 My Consultations</h1>
        <p>Career guidance sessions you've booked</p>
    </div>

    <div class="info-box">
        ✓ View all your booked consultation sessions with career coaches.
    </div>

    <?php if (count($consultations) > 0): ?>
        <?php foreach ($consultations as $c): ?>
            <div class="consultation-card">
                <div class="date-box">
                    <div class="date-day"><?= date('d', strtotime($c['consultation_date'])) ?></div>
                    <div class="date-month"><?= date('M', strtotime($c['consultation_date'])) ?></div>
                </div>
                
                <div class="consultation-info">
                    <h3><?= htmlspecialchars($c['topic'] ?? 'Career Consultation') ?></h3>
                    <div class="consultation-meta">
                        🕐 <?= date('h:i A', strtotime($c['consultation_date'])) ?> 
                        | ⏱ <?= $c['duration_minutes'] ?? 60 ?> mins
                    </div>
                    <p style="font-size: 12px; color: #666; margin-bottom: 8px;">
                        <?= htmlspecialchars($c['notes'] ?? 'No additional notes') ?>
                    </p>
                </div>

                <div style="text-align: right;">
                    <?php 
                    $status = $c['status'] ?? 'pending';
                    $statusClass = $status === 'completed' ? 'status-completed' : 'status-pending';
                    ?>
                    <span class="consultation-status <?= $statusClass ?>">
                        <?= ucfirst($status) ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <p style="color: #666; font-size: 13px;">No consultations booked yet.</p>
            <a href="career_guidance.php" style="color: #1e40af; text-decoration: none; font-weight: 600;">Book a session →</a>
        </div>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 24px;">
        <a href="dashboard.php" style="color: #666; text-decoration: none; font-size: 12px;">← Back to Dashboard</a>
    </div>

</div>

</body>
</html>
