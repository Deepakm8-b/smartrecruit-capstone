<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT s.student_id FROM students s WHERE s.user_id = ?');
$stmt->execute([$userId]);
$student = $stmt->fetch();

if (!$student) {
    die('Student record not found');
}

$studentId = $student['student_id'];

$stmt = $pdo->prepare('SELECT a.application_id, a.status, a.applied_date, j.job_title, j.company, j.location FROM applications a JOIN jobs j ON a.job_id = j.job_id WHERE a.student_id = ? ORDER BY a.applied_date DESC');
$stmt->execute([$studentId]);
$applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>My Applications - SmartRecruit</title>
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
table { width: 100%; background: white; border: 1px solid #e5e7eb; border-radius: 8px; border-collapse: collapse; }
thead { background: #f9fafb; }
th { padding: 12px 16px; text-align: left; font-weight: 700; font-size: 13px; border-bottom: 1px solid #e5e7eb; }
td { padding: 16px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
tr:hover { background: #f9fafb; }
.job-title { color: #1e40af; font-weight: 700; }
.badge { display: inline-block; padding: 6px 12px; border-radius: 4px; font-size: 11px; font-weight: 600; }
.badge-applied { background: #dbeafe; color: #1e40af; }
.badge-shortlisted { background: #dcfce7; color: #16a34a; }
.badge-rejected { background: #fee2e2; color: #dc2626; }
.badge-accepted { background: #d1d5db; color: #374151; }
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
        <a href="dashboard.php">← Back to Dashboard</a>
    </div>

    <h2>📋 My Applications</h2>

    <?php if (count($applications) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Job Title</th>
                    <th>Company</th>
                    <th>Location</th>
                    <th>Applied Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $app): ?>
                    <tr>
                        <td class="job-title"><?php echo htmlspecialchars($app['job_title']); ?></td>
                        <td><?php echo htmlspecialchars($app['company']); ?></td>
                        <td><?php echo htmlspecialchars($app['location']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($app['applied_date'])); ?></td>
                        <td><span class="badge badge-<?php echo strtolower($app['status']); ?>"><?php echo $app['status']; ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty">
            <p>No applications yet</p>
            <p style="font-size: 13px; margin-top: 8px;">Start by browsing available jobs and submitting your application</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
