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

$stmt = $pdo->prepare('SELECT a.application_id, s.full_name, s.degree, s.university, a.status, a.applied_date, j.job_title FROM applications a JOIN jobs j ON a.job_id = j.job_id JOIN students s ON a.student_id = s.student_id WHERE j.organisation_id = ? ORDER BY a.applied_date DESC');
$stmt->execute([$orgId]);
$candidates = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Candidates - SmartRecruit</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI'; background: #f5f7fa; }
.navbar { background: white; padding: 16px 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; }
.navbar h1 { font-size: 22px; font-weight: 700; }
.navbar h1 span { color: #1e40af; }
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
.candidate-name { color: #1e40af; font-weight: 700; cursor: pointer; }
.candidate-name:hover { text-decoration: underline; }
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
    <h1>Smart<span>Recruit</span></h1>
    <a href="logout.php">Logout</a>
</div>

<div class="container">
    <div class="back-link">
        <a href="recruiter_dashboard.php">← Back to Dashboard</a>
    </div>

    <h2>👥 Candidates</h2>

    <?php if (count($candidates) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Candidate Name</th>
                    <th>Education</th>
                    <th>Position Applied</th>
                    <th>Applied Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($candidates as $candidate): ?>
                    <tr>
                        <td><a href="candidate_profile.php?app_id=<?php echo $candidate['application_id']; ?>" class="candidate-name"><?php echo htmlspecialchars($candidate['full_name']); ?></a></td>
                        <td><?php echo htmlspecialchars($candidate['degree']); ?> - <?php echo htmlspecialchars($candidate['university']); ?></td>
                        <td><?php echo htmlspecialchars($candidate['job_title']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($candidate['applied_date'])); ?></td>
                        <td><span class="badge badge-<?php echo strtolower($candidate['status']); ?>"><?php echo $candidate['status']; ?></span></td>
                        <td><a href="candidate_profile.php?app_id=<?php echo $candidate['application_id']; ?>" style="color: #1e40af; text-decoration: none; font-weight: 600;">View →</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty">
            <p>No candidates yet</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
