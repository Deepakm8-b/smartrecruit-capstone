<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

// Get all badges
$stmt = $pdo->prepare('SELECT * FROM badges ORDER BY category, badge_id');
$stmt->execute();
$allBadges = $stmt->fetchAll();

// Get earned badges
$stmt = $pdo->prepare('SELECT badge_id FROM student_badges WHERE student_id = ?');
$stmt->execute([$studentId]);
$earnedBadgeIds = array_column($stmt->fetchAll(), 'badge_id');

// Simple stats
$applicationsCount = 0;
$coursesEnrolled = 0;
$stepsCompleted = 0;
$isPremium = false;
$profileCompletion = 0;

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
    <title>Badges — SmartRecruit</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .badges-container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        .badges-header { margin-bottom: 40px; }
        .badges-header a { color: #1e40af; text-decoration: none; font-weight: 600; font-size: 14px; }
        .badges-header h1 { font-size: 28px; font-weight: 700; margin: 16px 0 8px 0; }
        .badges-header p { color: #6b7280; margin: 0; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 40px; }
        @media (max-width: 768px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        
        .stat-card { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; text-align: center; }
        .stat-number { font-size: 24px; font-weight: 700; color: #1e40af; }
        .stat-label { font-size: 12px; color: #6b7280; margin-top: 8px; }
        
        .badges-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 20px; }
        @media (max-width: 768px) { .badges-grid { grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); } }
        
        .badge-card { background: white; border: 2px solid #e5e7eb; border-radius: 12px; padding: 20px; text-align: center; transition: all 0.3s; }
        .badge-card:hover { border-color: #1e40af; box-shadow: 0 4px 12px rgba(30, 64, 175, 0.1); }
        .badge-card.earned { border-color: #10b981; background: #f0fdf4; }
        .badge-card.locked { opacity: 0.6; }
        
        .badge-icon { font-size: 48px; margin-bottom: 12px; }
        .badge-name { font-size: 14px; font-weight: 700; color: #1f2937; margin-bottom: 4px; }
        .badge-desc { font-size: 11px; color: #6b7280; line-height: 1.4; }
        .badge-status { font-size: 12px; margin-top: 8px; padding: 4px 8px; border-radius: 4px; }
        .badge-earned { background: #d1fae5; color: #047857; font-weight: 600; }
        .badge-locked { background: #f3f4f6; color: #6b7280; }
        
        .category-title { font-size: 18px; font-weight: 700; color: #1f2937; margin: 32px 0 16px 0; padding-bottom: 8px; border-bottom: 2px solid #e5e7eb; }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="badges-container">
    <div class="badges-header">
        <a href="dashboard.php">← Back to Dashboard</a>
        <h1>🏆 Achievement Badges</h1>
        <p>Unlock badges as you progress through your career roadmap</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $stepsCompleted ?>/4</div>
            <div class="stat-label">Roadmap Steps</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $applicationsCount ?></div>
            <div class="stat-label">Applications Sent</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $coursesEnrolled ?></div>
            <div class="stat-label">Courses Enrolled</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= count($earnedBadgeIds) ?>/8</div>
            <div class="stat-label">Badges Earned</div>
        </div>
    </div>

    <?php 
    $currentCategory = '';
    foreach ($allBadges as $badge):
        if ($badge['category'] !== $currentCategory):
            if ($currentCategory !== '') echo '</div>';
            $currentCategory = $badge['category'];
            $categoryTitle = ucfirst(str_replace('_', ' ', $badge['category']));
            echo '<div class="category-title">' . htmlspecialchars($categoryTitle) . '</div>';
            echo '<div class="badges-grid">';
        endif;
        
        $isEarned = in_array($badge['badge_id'], $earnedBadgeIds);
    ?>
        <div class="badge-card <?= $isEarned ? 'earned' : 'locked' ?>">
            <div class="badge-icon"><?= htmlspecialchars($badge['icon']) ?></div>
            <div class="badge-name"><?= htmlspecialchars($badge['badge_name']) ?></div>
            <div class="badge-desc"><?= htmlspecialchars($badge['description']) ?></div>
            <div class="badge-status <?= $isEarned ? 'badge-earned' : 'badge-locked' ?>">
                <?= $isEarned ? '✓ Earned' : '🔒 Locked' ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>

</body>
</html>
