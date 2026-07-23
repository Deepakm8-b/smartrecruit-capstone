<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'organisation') {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT org_id FROM organisations WHERE user_id = ?');
$stmt->execute([$userId]);
$org = $stmt->fetch();
$orgId = $org['org_id'];

$stmt = $pdo->prepare('SELECT j.job_id, j.job_title, j.company, j.location, j.job_type, j.salary_range, j.created_date, COUNT(a.application_id) as applicant_count FROM jobs j LEFT JOIN applications a ON j.job_id = a.job_id WHERE j.organisation_id = ? GROUP BY j.job_id ORDER BY j.created_date DESC');
$stmt->execute([$orgId]);
$jobs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Job Postings - SmartRecruit</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI'; background: #f5f7fa; }
.navbar { background: white; padding: 16px 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; }
.navbar h1 { font-size: 22px; font-weight: 700; }
.navbar a { color: #1e40af; text-decoration: none; font-weight: 600; }
.container { max-width: 1200px; margin: 0 auto; padding: 24px 20px; }
.back-link { margin-bottom: 20px; }
.back-link a { color: #1e40af; text-decoration: none; font-weight: 600; }
h2 { font-size: 28px; font-weight: 700; margin-bottom: 24px; }
.job-card { background: white; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: flex-start; }
.job-info { flex: 1; }
.job-title { font-size: 18px; font-weight: 700; color: #1e40af; margin-bottom: 8px; }
.job-meta { font-size: 13px; color: #6b7280; line-height: 1.6; }
.job-actions { display: flex; gap: 8px; flex-direction: column; margin-left: 20px; }
.btn { padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13px; text-align: center; background: #1e40af; color: white; border: none; cursor: pointer; }
.btn:hover { background: #1e3a8a; }
.applicant-badge { background: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
.empty { text-align: center; padding: 40px; color: #6b7280; }
</style>
</head>
<body>

<div class="navbar">
    <h1>Smart<span style="color: #1e40af;">Recruit</span></h1>
    <a href="logout.php">Logout</a>
</div>

<div class="container">
    <div class="back-link">
        <a href="recruiter_dashboard.php">← Back to Dashboard</a>
    </div>

    <h2>💼 Job Postings</h2>

    <?php if (count($jobs) > 0): ?>
        <?php foreach ($jobs as $job): ?>
            <div class="job-card">
                <div class="job-info">
                    <div class="job-title"><?php echo htmlspecialchars($job['job_title']); ?></div>
                    <div class="job-meta">
                        <?php echo htmlspecialchars($job['company']); ?> • <?php echo htmlspecialchars($job['location']); ?><br>
                        <?php echo htmlspecialchars($job['job_type']); ?> • <?php echo htmlspecialchars($job['salary_range']); ?><br>
                        Posted: <?php echo date('M d, Y', strtotime($job['created_date'])); ?>
                    </div>
                </div>
                <div class="job-actions">
                    <span class="applicant-badge"><?php echo $job['applicant_count']; ?> Applicants</span>
                    <a href="view_candidates.php" class="btn">View Applicants</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty">
            <p>No job postings yet</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
