<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$userId = $_SESSION['user_id'];
$student = getStudentByUserId($pdo, $userId);

// Check premium status
$stmt = $pdo->prepare('SELECT premium_status FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();
$isPremium = $user['premium_status'] === 'premium';

if (!$isPremium) {
    header('Location: premium.php');
    exit;
}

// Get skill gaps
$stmt = $pdo->prepare('SELECT * FROM skill_gaps WHERE student_id = ? ORDER BY gap_percentage DESC');
$stmt->execute([$student['student_id']]);
$skillGaps = $stmt->fetchAll();

// Insert sample skill gaps if none exist
if (count($skillGaps) === 0) {
    $stmt = $pdo->prepare('INSERT INTO skill_gaps (student_id, skill_name, current_level, target_level, gap_percentage, recommendation) VALUES (?, ?, ?, ?, ?, ?)');
    $sampleSkills = [
        ['Python', 'intermediate', 'advanced', 35, 'Complete advanced Python course on Udemy and practice with real projects'],
        ['System Administration', 'beginner', 'intermediate', 60, 'Take Linux administration course and get CompTIA A+ certification'],
        ['Cloud Computing (AWS)', 'beginner', 'intermediate', 70, 'Enroll in AWS Solutions Architect training program'],
        ['Networking', 'intermediate', 'advanced', 40, 'Study for CCNA certification and hands-on lab practice'],
        ['PowerShell Scripting', 'beginner', 'intermediate', 55, 'Complete PowerShell scripting bootcamp and build 3 automation projects'],
    ];
    
    foreach ($sampleSkills as $skill) {
        $stmt->execute([$student['student_id'], $skill[0], $skill[1], $skill[2], $skill[3], $skill[4]]);
    }
    
    // Fetch again
    $stmt = $pdo->prepare('SELECT * FROM skill_gaps WHERE student_id = ? ORDER BY gap_percentage DESC');
    $stmt->execute([$student['student_id']]);
    $skillGaps = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Skill Gap Analysis — SmartRecruit</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial; background: #f8fafc; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        .header { margin-bottom: 24px; }
        .header h1 { font-size: 20px; font-weight: 800; margin-bottom: 4px; }
        .header p { color: #666; font-size: 13px; }
        .info-box { background: #dbeafe; border-left: 4px solid #1e40af; padding: 12px; margin-bottom: 24px; font-size: 12px; }
        .skill-card { background: white; padding: 20px; border: 1px solid #e5e7eb; margin-bottom: 16px; }
        .skill-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .skill-name { font-size: 14px; font-weight: 800; }
        .skill-level { font-size: 11px; color: #666; background: #f3f4f6; padding: 4px 8px; border-radius: 0; }
        .progress-bar { background: #e5e7eb; height: 8px; border-radius: 0; overflow: hidden; margin-bottom: 8px; }
        .progress-fill { background: linear-gradient(90deg, #ef4444, #f97316, #eab308, #10b981); height: 100%; }
        .gap-percentage { font-size: 12px; font-weight: 600; color: #1e40af; }
        .recommendation { background: #f0fdf4; border-left: 3px solid #10b981; padding: 12px; font-size: 12px; color: #065f46; line-height: 1.5; }
        .summary { background: white; padding: 20px; border: 1px solid #e5e7eb; margin-bottom: 24px; }
        .summary-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .summary-item { text-align: center; }
        .summary-number { font-size: 24px; font-weight: 800; color: #1e40af; }
        .summary-label { font-size: 11px; color: #666; }
        .btn { padding: 10px 16px; background: #1e40af; color: white; border: none; cursor: pointer; font-weight: 600; font-size: 12px; }
        .btn:hover { background: #1e3a8a; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="header">
        <h1>📊 Skill Gap Analysis</h1>
        <p>Identify skills to improve for your target role</p>
    </div>

    <div class="info-box">
        ✓ This is a premium feature. Your personalized skill gap analysis shows which skills need development to reach your career goal: <strong>IT Support Specialist</strong>
    </div>

    <!-- SUMMARY -->
    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-number"><?= count($skillGaps) ?></div>
                <div class="summary-label">Skills Analyzed</div>
            </div>
            <div class="summary-item">
                <div class="summary-number"><?php 
                    $avgGap = round(array_sum(array_column($skillGaps, 'gap_percentage')) / count($skillGaps));
                    echo $avgGap;
                ?>%</div>
                <div class="summary-label">Average Gap</div>
            </div>
            <div class="summary-item">
                <div class="summary-number"><?= count(array_filter($skillGaps, function($s) { return $s['gap_percentage'] < 40; })) ?></div>
                <div class="summary-label">Skills to Improve</div>
            </div>
        </div>
    </div>

    <!-- SKILL GAPS -->
    <?php foreach ($skillGaps as $gap): ?>
        <div class="skill-card">
            <div class="skill-header">
                <div class="skill-name"><?= htmlspecialchars($gap['skill_name']) ?></div>
                <div>
                    <span class="skill-level"><?= ucfirst($gap['current_level']) ?> → <?= ucfirst($gap['target_level']) ?></span>
                    <span class="gap-percentage" style="margin-left: 12px;"><?= $gap['gap_percentage'] ?>% gap</span>
                </div>
            </div>
            
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= (100 - $gap['gap_percentage']) ?>%;"></div>
            </div>
            
            <div class="recommendation">
                <strong>Recommendation:</strong> <?= htmlspecialchars($gap['recommendation']) ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div style="text-align: center; margin-top: 24px;">
        <a href="training.php" style="color: #1e40af; text-decoration: none; font-weight: 600;">View Training Courses →</a>
        <br><br>
        <a href="dashboard.php" style="color: #666; text-decoration: none; font-size: 12px;">← Back to Dashboard</a>
    </div>

</div>

</body>
</html>
