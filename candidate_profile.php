<?php
session_start();
require_once 'db.php';
require_once 'email_config.php';
require_once 'send_email.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'organisation') {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$appId = intval($_GET['app_id'] ?? 0);

$stmt = $pdo->prepare('SELECT org_id FROM organisations WHERE user_id = ?');
$stmt->execute([$userId]);
$org = $stmt->fetch();
$orgId = $org['org_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $allowedActions = ['Shortlisted', 'Rejected', 'Accepted', 'Reviewing'];
    
    if (in_array($action, $allowedActions)) {
        $stmt = $pdo->prepare('UPDATE applications SET status = ?, updated_date = NOW() WHERE application_id = ?');
        $stmt->execute([$action, $appId]);
        
        $stmt = $pdo->prepare('SELECT u.email, s.full_name, j.job_title, j.company FROM applications a JOIN jobs j ON a.job_id = j.job_id JOIN students s ON a.student_id = s.student_id JOIN users u ON s.user_id = u.user_id WHERE a.application_id = ?');
        $stmt->execute([$appId]);
        $appData = $stmt->fetch();
        
        if ($appData) {
            sendStatusEmail($appData['email'], $appData['full_name'], $appData['job_title'], $action, $appData['company']);
        }
    }
}

$stmt = $pdo->prepare('SELECT a.application_id, a.status, a.applied_date, a.resume_used, s.student_id, s.full_name, s.phone, u.email, s.university, s.degree, s.gpa, j.job_id, j.job_title, j.company FROM applications a JOIN jobs j ON a.job_id = j.job_id JOIN students s ON a.student_id = s.student_id JOIN users u ON s.user_id = u.user_id WHERE a.application_id = ? AND j.organisation_id = ?');
$stmt->execute([$appId, $orgId]);
$application = $stmt->fetch();

if (!$application) {
    header('Location: view_candidates.php');
    exit;
}

$stmt = $pdo->prepare('SELECT jq.question_text, aa.answer_text FROM application_answers aa JOIN job_screening_questions jq ON aa.question_id = jq.question_id WHERE aa.application_id = ? ORDER BY aa.answer_id');
$stmt->execute([$appId]);
$answers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($application['full_name']); ?> — SmartRecruit</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI'; background: #f5f7fa; }
.navbar { background: white; padding: 16px 24px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; }
.navbar h1 { font-size: 22px; font-weight: 700; }
.navbar a { color: #1e40af; text-decoration: none; font-weight: 600; }
.container { max-width: 1000px; margin: 0 auto; padding: 24px 20px; }
.back-link { margin-bottom: 20px; }
.back-link a { color: #1e40af; text-decoration: none; font-weight: 600; }
.header { background: white; padding: 24px; border-radius: 8px; border: 1px solid #e5e7eb; margin-bottom: 20px; display: flex; justify-content: space-between; }
.header-left { flex: 1; }
.header-right { display: flex; gap: 12px; flex-direction: column; margin-left: 20px; }
.candidate-name { font-size: 24px; font-weight: 700; margin-bottom: 12px; }
.candidate-meta { font-size: 13px; color: #6b7280; line-height: 1.6; }
.candidate-meta a { color: #1e40af; text-decoration: none; }
.status-badge { display: inline-block; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; margin-bottom: 12px; }
.status-applied { background: #dbeafe; color: #1e40af; }
.status-shortlisted { background: #dcfce7; color: #16a34a; }
.status-rejected { background: #fee2e2; color: #dc2626; }
.status-accepted { background: #d1d5db; color: #374151; }
.btn { padding: 10px 16px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 13px; width: 100%; }
.btn-shortlist { background: #dcfce7; color: #16a34a; }
.btn-reject { background: #fee2e2; color: #dc2626; }
.btn-hire { background: #1e40af; color: white; }
.btn:hover { opacity: 0.9; }
.section { background: white; padding: 24px; border-radius: 8px; border: 1px solid #e5e7eb; margin-bottom: 20px; }
.section-title { font-size: 16px; font-weight: 700; margin-bottom: 16px; border-bottom: 1px solid #e5e7eb; padding-bottom: 12px; }
.answer-item { padding: 12px; background: #f9fafb; border-radius: 4px; margin-bottom: 12px; }
.answer-question { font-weight: 600; margin-bottom: 8px; }
.answer-text { color: #6b7280; font-size: 13px; }
.success { background: #dcfce7; color: #16a34a; padding: 12px; border-radius: 6px; margin-bottom: 20px; }
</style>
</head>
<body>

<div class="navbar">
    <h1>Smart<span style="color: #1e40af;">Recruit</span></h1>
    <a href="logout.php">Logout</a>
</div>

<div class="container">
    <div class="back-link">
        <a href="view_candidates.php">← Back to Candidates</a>
    </div>

    <div class="header">
        <div class="header-left">
            <div class="candidate-name"><?php echo htmlspecialchars($application['full_name']); ?></div>
            <div class="candidate-meta">
                <strong><?php echo htmlspecialchars($application['degree']); ?></strong> — <?php echo htmlspecialchars($application['university']); ?><br>
                GPA: <?php echo htmlspecialchars($application['gpa'] ?? 'N/A'); ?> | Phone: <?php echo htmlspecialchars($application['phone'] ?? 'N/A'); ?><br>
                Email: <a href="mailto:<?php echo htmlspecialchars($application['email']); ?>"><?php echo htmlspecialchars($application['email']); ?></a>
            </div>
            <span class="status-badge status-<?php echo strtolower($application['status']); ?>">
                <?php echo htmlspecialchars($application['status']); ?>
            </span>
        </div>

        <div class="header-right">
            <form method="post">
                <?php if ($application['status'] !== 'Shortlisted'): ?>
                    <button type="submit" name="action" value="Shortlisted" class="btn btn-shortlist">⭐ Shortlist</button>
                <?php endif; ?>
                <?php if ($application['status'] !== 'Accepted'): ?>
                    <button type="submit" name="action" value="Accepted" class="btn btn-hire">✓ Hire</button>
                <?php endif; ?>
                <?php if ($application['status'] !== 'Rejected'): ?>
                    <button type="submit" name="action" value="Rejected" class="btn btn-reject">✗ Reject</button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])): ?>
        <div class="success">✓ Status updated & email sent to <?php echo htmlspecialchars($application['full_name']); ?></div>
    <?php endif; ?>

    <div class="section">
        <div class="section-title">Position Applied For</div>
        <strong><?php echo htmlspecialchars($application['job_title']); ?></strong> at <?php echo htmlspecialchars($application['company']); ?><br>
        <span style="font-size: 13px; color: #6b7280;">Applied: <?php echo date('F j, Y', strtotime($application['applied_date'])); ?></span>
    </div>

    <?php if (count($answers) > 0): ?>
        <div class="section">
            <div class="section-title">Application Responses</div>
            <?php foreach ($answers as $answer): ?>
                <div class="answer-item">
                    <div class="answer-question"><?php echo htmlspecialchars($answer['question_text']); ?></div>
                    <div class="answer-text"><?php echo nl2br(htmlspecialchars($answer['answer_text'])); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="section">
        <div class="section-title">Resume</div>
        <?php if ($application['resume_used'] && $application['resume_used'] !== 'no_resume'): ?>
            <p style="font-size: 13px;">📄 <?php echo htmlspecialchars($application['resume_used']); ?></p>
        <?php else: ?>
            <p style="color: #6b7280; font-size: 13px;">No resume uploaded</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
