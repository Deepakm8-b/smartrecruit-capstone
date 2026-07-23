<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'organisation') {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT org_id, org_name FROM organisations WHERE user_id = ?');
$stmt->execute([$userId]);
$org = $stmt->fetch();
$orgId = $org['org_id'];

$stmt = $pdo->prepare('SELECT COUNT(*) as count FROM jobs WHERE organisation_id = ?');
$stmt->execute([$orgId]);
$activeJobs = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) as count FROM applications a JOIN jobs j ON a.job_id = j.job_id WHERE j.organisation_id = ?');
$stmt->execute([$orgId]);
$totalApplicants = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT a.application_id, a.status, s.full_name, j.job_title FROM applications a JOIN jobs j ON a.job_id = j.job_id JOIN students s ON a.student_id = s.student_id WHERE j.organisation_id = ? ORDER BY a.applied_date DESC LIMIT 5');
$stmt->execute([$orgId]);
$recentApps = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Dashboard - SmartRecruit</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI'; background: #f5f7fa; }
.navbar { background: white; padding: 16px 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; }
.navbar h1 { font-size: 22px; font-weight: 700; }
.navbar h1 span { color: #1e40af; }
.navbar a { color: #1e40af; text-decoration: none; font-weight: 600; }
.layout { display: flex; min-height: calc(100vh - 60px); }
.sidebar { width: 220px; background: white; border-right: 1px solid #e5e7eb; padding: 20px; }
.sidebar-nav { display: flex; flex-direction: column; gap: 8px; }
.sidebar-nav a { padding: 12px 16px; border-radius: 6px; text-decoration: none; color: #6b7280; font-weight: 600; font-size: 14px; transition: all 0.2s; }
.sidebar-nav a:hover { background: #f3f4f6; color: #1e40af; }
.sidebar-nav a.active { background: #dbeafe; color: #1e40af; }
.main { flex: 1; padding: 24px 32px; }
h2 { font-size: 28px; font-weight: 700; margin-bottom: 24px; }
.stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 32px; }
.stat-card { background: white; padding: 24px; border-radius: 8px; border: 1px solid #e5e7eb; }
.stat-num { font-size: 36px; font-weight: 700; color: #1e40af; }
.stat-label { font-size: 14px; color: #6b7280; margin-top: 8px; }
.section-title { font-size: 18px; font-weight: 700; margin-bottom: 16px; }
.apps-table { background: white; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
.app-row { padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; cursor: pointer; transition: all 0.2s; }
.app-row:hover { background: #f9fafb; }
.app-row:last-child { border-bottom: none; }
.app-info { flex: 1; }
.app-name { color: #1e40af; font-weight: 700; margin-bottom: 4px; text-decoration: none; }
.app-name:hover { text-decoration: underline; }
.app-job { font-size: 13px; color: #6b7280; }
.badge { display: inline-block; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; }
.badge-applied { background: #dbeafe; color: #1e40af; }
.badge-shortlisted { background: #dcfce7; color: #16a34a; }
.badge-rejected { background: #fee2e2; color: #dc2626; }
</style>
</head>
<body>

<div class="navbar">
    <h1>Smart<span>Recruit</span></h1>
    <a href="logout.php">Logout</a>
</div>

<div class="layout">
    <div class="sidebar">
        <nav class="sidebar-nav">
            <a href="recruiter_dashboard.php" class="active">📊 Dashboard</a>
            <a href="job_postings_recruiter.php">💼 Job Postings</a>
            <a href="post_job.php" style="background: #dbeafe; color: #1e40af;">➕ Post Job</a>
            <a href="view_candidates.php">👥 Candidates</a>
            <a href="shortlisted_candidates.php">⭐ Shortlisted</a>
            <a href="referrals_recruiter.php">🔗 Referrals</a>
            <a href="commission_history.php">💰 Commission</a>
        </nav>
    </div>

    <div class="main">
        <h2>Dashboard</h2>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-num"><?php echo $activeJobs; ?></div>
                <div class="stat-label">Active Jobs</div>
            </div>
            <div class="stat-card">
                <div class="stat-num"><?php echo $totalApplicants; ?></div>
                <div class="stat-label">Total Applicants</div>
            </div>
            <div class="stat-card">
                <div class="stat-num">0</div>
                <div class="stat-label">Shortlisted</div>
            </div>
            <div class="stat-card">
                <div class="stat-num">0</div>
                <div class="stat-label">Hired</div>
            </div>
        </div>

        <div class="section-title">Recent Applications</div>
        <div class="apps-table">
            <?php if (count($recentApps) > 0): ?>
                <?php foreach ($recentApps as $app): ?>
                    <div class="app-row" onclick="window.location='candidate_profile.php?app_id=<?php echo $app['application_id']; ?>'">
                        <div class="app-info">
                            <a href="candidate_profile.php?app_id=<?php echo $app['application_id']; ?>" class="app-name"><?php echo $app['full_name']; ?></a>
                            <div class="app-job"><?php echo $app['job_title']; ?></div>
                        </div>
                        <span class="badge badge-<?php echo strtolower($app['status']); ?>"><?php echo $app['status']; ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="app-row" style="text-align: center; color: #6b7280; cursor: default;">
                    No applications yet
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
