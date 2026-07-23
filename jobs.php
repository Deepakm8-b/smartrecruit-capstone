<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

$userId  = $_SESSION['user_id'] ?? null;
$role    = $_SESSION['role'] ?? '';
$student = null;
$appliedJobIds = [];

if ($userId && $role === 'student') {
    $student = getStudentByUserId($pdo, $userId);
    if ($student) {
        $stmt = $pdo->prepare('SELECT job_id FROM applications WHERE student_id = ?');
        $stmt->execute([$student['student_id']]);
        $appliedJobIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

$stmt = $pdo->query('SELECT j.job_id, j.job_title, j.company, j.location, j.job_type, j.salary_range, j.required_skills, j.description FROM jobs j ORDER BY j.job_id DESC');
$jobs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Listings — SmartRecruit</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'nav.php'; ?>

<div style="max-width: 900px; margin: 0 auto; padding: 0 20px;">
    
    <div style="margin-bottom: 20px;">
        <a href="dashboard.php" style="color: #1e40af; text-decoration: none; font-weight: 600;">← Back to Dashboard</a>
    </div>

    <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 24px; color: #1f2937;">💼 Job Listings</h1>

    <?php if (count($jobs) > 0): ?>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($jobs as $job): ?>
                <a href="job_details.php?job_id=<?= $job['job_id'] ?>" style="text-decoration: none; color: inherit; display: block;">
                    <div style="background: white; padding: 20px; border: 1px solid #e5e7eb; border-radius: 8px; cursor: pointer; transition: all 0.2s ease;" onmouseover="this.style.borderColor='#1e40af'; this.style.boxShadow='0 2px 8px rgba(30, 64, 175, 0.1)';" onmouseout="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';">
                        
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                            <div>
                                <h3 style="font-size: 16px; font-weight: 700; color: #1e40af; margin: 0 0 4px 0;"><?= htmlspecialchars($job['job_title']) ?></h3>
                                <p style="font-size: 13px; color: #6b7280; margin: 0;"><?= htmlspecialchars($job['company']) ?> · <?= htmlspecialchars($job['location']) ?></p>
                            </div>
                            <?php if (in_array($job['job_id'], $appliedJobIds)): ?>
                                <span style="background: #d1d5db; color: #374151; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600;">✓ Applied</span>
                            <?php endif; ?>
                        </div>

                        <p style="font-size: 13px; color: #6b7280; margin-bottom: 12px;"><?= htmlspecialchars(substr($job['description'], 0, 100)) ?>...</p>

                        <?php if ($job['required_skills']): ?>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;">
                                <?php foreach (explode(',', $job['required_skills']) as $skill): ?>
                                    <span style="background: #f3f4f6; color: #6b7280; padding: 4px 8px; border-radius: 3px; font-size: 11px;"><?= htmlspecialchars(trim($skill)) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <p style="font-size: 12px; color: #6b7280; margin: 0;"><?= htmlspecialchars($job['job_type']) ?> · <?= htmlspecialchars($job['salary_range']) ?></p>
                            <span style="background: #1e40af; color: white; padding: 8px 16px; border-radius: 4px; text-decoration: none; font-weight: 600; font-size: 13px;">Apply Now →</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div style="background: white; padding: 40px; border-radius: 8px; text-align: center;">
            <p style="color: #6b7280; margin-bottom: 20px;">No jobs available at the moment.</p>
            <a href="dashboard.php" style="background: #1e40af; color: white; padding: 11px 18px; border-radius: 4px; text-decoration: none; font-weight: 600;">Back to Dashboard</a>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
