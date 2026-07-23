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

$stmt = $pdo->prepare('SELECT a.application_id, s.full_name, s.degree, s.university, j.job_title, a.status, a.applied_date FROM applications a JOIN jobs j ON a.job_id = j.job_id JOIN students s ON a.student_id = s.student_id WHERE j.organisation_id = ? AND a.status = "Shortlisted" ORDER BY j.job_title, s.full_name');
$stmt->execute([$orgId]);
$shortlisted = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Shortlisted - SmartRecruit</title>
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
.job-group { background: white; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb; margin-bottom: 20px; }
.job-title { font-size: 18px; font-weight: 700; color: #1e40af; margin-bottom: 16px; border-bottom: 2px solid #1e40af; padding-bottom: 12px; }
.candidate-item { padding: 12px; background: #f9fafb; border-radius: 4px; margin-bottom: 8px; cursor: pointer; transition: all 0.2s; }
.candidate-item:hover { background: #e5e7eb; }
.candidate-name { font-weight: 600; color: #1e40af; text-decoration: none; display: block; }
.candidate-name:hover { text-decoration: underline; }
.candidate-education { font-size: 13px; color: #6b7280; }
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

    <h2>⭐ Shortlisted Candidates</h2>

    <?php 
    $groupedByJob = [];
    foreach ($shortlisted as $candidate) {
        $job = $candidate['job_title'];
        if (!isset($groupedByJob[$job])) {
            $groupedByJob[$job] = [];
        }
        $groupedByJob[$job][] = $candidate;
    }
    
    if (count($groupedByJob) > 0):
        foreach ($groupedByJob as $job => $candidates):
    ?>
        <div class="job-group">
            <div class="job-title"><?php echo htmlspecialchars($job); ?></div>
            <?php foreach ($candidates as $candidate): ?>
                <a href="candidate_profile.php?app_id=<?php echo $candidate['application_id']; ?>" style="text-decoration: none;">
                    <div class="candidate-item">
                        <div class="candidate-name"><?php echo htmlspecialchars($candidate['full_name']); ?></div>
                        <div class="candidate-education"><?php echo htmlspecialchars($candidate['degree']); ?> — <?php echo htmlspecialchars($candidate['university']); ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php 
        endforeach;
    else:
    ?>
        <div class="empty">
            <p>No shortlisted candidates yet</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
