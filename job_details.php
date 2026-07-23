<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

$jobId = intval($_GET['job_id'] ?? 0);

if (!$jobId) {
    header('Location: jobs.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM jobs WHERE job_id = ?');
$stmt->execute([$jobId]);
$job = $stmt->fetch();

if (!$job) {
    die('<h1>Job not found</h1><p><a href="jobs.php">← Back to Job Listings</a></p>');
}

$userId = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($job['job_title']) ?> — SmartRecruit</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'nav.php'; ?>

<div style="max-width: 1000px; margin: 0 auto; padding: 40px 20px;">
    
    <a href="jobs.php" style="color: #1e40af; text-decoration: none; font-weight: 600; margin-bottom: 20px; display: inline-block;">← Back to Job Listings</a>

    <div style="display: grid; grid-template-columns: 1fr 300px; gap: 40px; margin-bottom: 40px;">
        
        <div>
            <h1 style="font-size: 32px; font-weight: 700; margin: 0 0 8px 0; color: #1f2937;"><?= htmlspecialchars($job['job_title']) ?></h1>
            
            <div style="color: #6b7280; margin-bottom: 16px;">
                <p style="margin: 0 0 8px 0;"><strong><?= htmlspecialchars($job['company']) ?></strong></p>
                <p style="margin: 0 0 4px 0;"><?= htmlspecialchars($job['location']) ?></p>
                <p style="margin: 0;"><?= htmlspecialchars($job['job_type']) ?></p>
            </div>

            <p style="font-size: 20px; font-weight: 700; color: #1e40af; margin: 16px 0 32px 0;"><?= htmlspecialchars($job['salary_range']) ?></p>

            <h2 style="font-size: 18px; font-weight: 700; margin: 32px 0 16px 0; color: #1f2937;">About the Job</h2>
            <p style="color: #6b7280; line-height: 1.6; margin: 0;"><?= htmlspecialchars($job['description']) ?></p>

            <?php if ($job['required_skills']): ?>
                <h2 style="font-size: 18px; font-weight: 700; margin: 32px 0 16px 0; color: #1f2937;">Required Skills</h2>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    <?php foreach (explode(',', $job['required_skills']) as $skill): ?>
                        <span style="background: #f3f4f6; color: #6b7280; padding: 6px 12px; border-radius: 4px; font-size: 13px;"><?= htmlspecialchars(trim($skill)) ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div>
            <?php if ($userId && $role === 'student'): ?>
                <a href="apply_job.php?job_id=<?= $jobId ?>" style="display: block; background: #1e40af; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; text-align: center; margin-bottom: 12px;">Apply Now</a>
            <?php else: ?>
                <a href="login.php" style="display: block; background: #1e40af; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; text-align: center; margin-bottom: 12px;">Sign In to Apply</a>
            <?php endif; ?>
            
            <button style="width: 100%; background: #f3f4f6; color: #1f2937; padding: 12px 24px; border: 1px solid #d1d5db; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px;">💾 Save Job</button>

            <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-top: 24px;">
                <p style="font-size: 12px; font-weight: 600; color: #6b7280; margin: 0 0 12px 0;">ABOUT COMPANY</p>
                <p style="font-size: 14px; font-weight: 600; color: #1f2937; margin: 0 0 8px 0;"><?= htmlspecialchars($job['company']) ?></p>
                <p style="font-size: 13px; color: #6b7280; margin: 0;">Hiring for: <strong><?= htmlspecialchars($job['job_title']) ?></strong></p>
            </div>
        </div>

    </div>

</div>

</body>
</html>
