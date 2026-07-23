<?php
/**
 * admin.php — SmartRecruit admin panel (Design Pass, v2)
 * Author: Deepak Bhandari (2443463047)
 * Same logic as v1; reskinned with app.css, nav.php, stat cards.
 */
session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if ($_SESSION['role'] !== 'admin') { header('Location: dashboard.php'); exit; }

$adminId = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT email FROM users WHERE user_id = ?');
$stmt->execute([$adminId]);
$navEmail = $stmt->fetchColumn();
$flash = '';

function logAdmin(PDO $pdo, int $adminId, string $action, string $table, $old, $new): void {
    $stmt = $pdo->prepare('INSERT INTO admin_logs (admin_id, action, target_table, old_value, new_value, ip_address) VALUES (?,?,?,?,?,?)');
    $stmt->execute([$adminId, $action, $table, json_encode($old), json_encode($new), $_SERVER['REMOTE_ADDR'] ?? null]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'approve_job') {
        $jobId = (int)($_POST['job_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE job_postings SET status='open' WHERE job_id=? AND status='pending_approval'");
        $stmt->execute([$jobId]);
        if ($stmt->rowCount()) {
            logAdmin($pdo, $adminId, 'approve_job', 'job_postings', ['job_id'=>$jobId,'status'=>'pending_approval'], ['job_id'=>$jobId,'status'=>'open']);
            $flash = "Job #$jobId approved.";
        }
    } elseif ($action === 'verify_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        $stmt = $pdo->prepare('UPDATE users SET is_verified=1 WHERE user_id=? AND is_verified=0');
        $stmt->execute([$uid]);
        if ($stmt->rowCount()) {
            logAdmin($pdo, $adminId, 'verify_user', 'users', ['user_id'=>$uid,'is_verified'=>0], ['user_id'=>$uid,'is_verified'=>1]);
            $flash = "User #$uid verified.";
        }
    } elseif ($action === 'delete_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid === $adminId) { $flash = 'Cannot delete your own account.'; }
        else {
            $stmt = $pdo->prepare('SELECT email, role FROM users WHERE user_id=?');
            $stmt->execute([$uid]);
            if ($old = $stmt->fetch()) {
                $pdo->prepare('DELETE FROM users WHERE user_id=?')->execute([$uid]);
                logAdmin($pdo, $adminId, 'delete_user', 'users', $old, null);
                $flash = "User #$uid deleted.";
            }
        }
    }
}

$stats = $pdo->query("SELECT
    (SELECT COUNT(*) FROM users) AS users,
    (SELECT COUNT(*) FROM students) AS students,
    (SELECT COUNT(*) FROM organisations) AS orgs,
    (SELECT COUNT(*) FROM job_postings) AS jobs,
    (SELECT COUNT(*) FROM job_postings WHERE status='pending_approval') AS pending,
    (SELECT COUNT(*) FROM applications) AS apps,
    (SELECT COUNT(*) FROM subscriptions WHERE status='active') AS subs,
    (SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed') AS revenue")->fetch();

$users = $pdo->query('SELECT user_id, email, role, is_verified, created_at FROM users ORDER BY user_id')->fetchAll();
$pendingJobs = $pdo->query("SELECT j.job_id, j.title, o.org_name, j.deadline FROM job_postings j JOIN organisations o ON o.org_id=j.org_id WHERE j.status='pending_approval' ORDER BY j.created_at")->fetchAll();
$logs = $pdo->query('SELECT l.created_at, u.email AS admin_email, l.action, l.target_table FROM admin_logs l JOIN users u ON u.user_id=l.admin_id ORDER BY l.log_id DESC LIMIT 10')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — SmartRecruit</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
<?php require 'nav.php'; ?>
<main class="app-main">
    <h1>Admin Panel</h1>
    <p class="subtitle">Platform management · FR15–FR20</p>

    <?php if ($flash): ?>
        <div class="flash"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <h2>Platform statistics</h2>
    <div class="stat-grid">
        <div class="stat"><div class="n"><?= $stats['users'] ?></div><div class="l">Total users</div></div>
        <div class="stat"><div class="n"><?= $stats['students'] ?></div><div class="l">Students</div></div>
        <div class="stat"><div class="n"><?= $stats['orgs'] ?></div><div class="l">Organisations</div></div>
        <div class="stat"><div class="n"><?= $stats['jobs'] ?></div><div class="l">Total jobs</div></div>
        <div class="stat"><div class="n"><?= $stats['pending'] ?></div><div class="l">Pending approval</div></div>
        <div class="stat"><div class="n"><?= $stats['apps'] ?></div><div class="l">Applications</div></div>
        <div class="stat"><div class="n"><?= $stats['subs'] ?></div><div class="l">Active subs</div></div>
        <div class="stat"><div class="n">$<?= number_format($stats['revenue'],2) ?></div><div class="l">Revenue (AUD)</div></div>
    </div>

    <?php if ($pendingJobs): ?>
    <h2>Jobs awaiting approval (FR16)</h2>
    <div class="card">
        <table class="app">
            <tr><th>ID</th><th>Title</th><th>Organisation</th><th>Deadline</th><th></th></tr>
            <?php foreach ($pendingJobs as $j): ?>
            <tr>
                <td><?= $j['job_id'] ?></td>
                <td><strong><?= htmlspecialchars($j['title']) ?></strong></td>
                <td><?= htmlspecialchars($j['org_name']) ?></td>
                <td><?= htmlspecialchars($j['deadline']) ?></td>
                <td>
                    <form method="post" style="margin:0;">
                        <input type="hidden" name="action" value="approve_job">
                        <input type="hidden" name="job_id" value="<?= $j['job_id'] ?>">
                        <button class="btn" type="submit" style="padding:6px 14px;font-size:0.85rem;">Approve</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

    <h2>User management (FR15, FR17)</h2>
    <div class="card">
        <table class="app">
            <tr><th>ID</th><th>Email</th><th>Role</th><th>Verified</th><th>Created</th><th>Actions</th></tr>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['user_id'] ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge <?= $u['role']==='admin'?'high':($u['role']==='organisation'?'mid':'low') ?>"><?= $u['role'] ?></span></td>
                <td><?= $u['is_verified'] ? '<span style="color:var(--green);">✓</span>' : '<span style="color:var(--grey);">✗</span>' ?></td>
                <td style="font-size:0.82rem;"><?= htmlspecialchars($u['created_at']) ?></td>
                <td style="white-space:nowrap;">
                    <?php if (!$u['is_verified']): ?>
                    <form method="post" style="display:inline;margin:0;">
                        <input type="hidden" name="action" value="verify_user">
                        <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                        <button class="btn secondary" type="submit" style="padding:4px 10px;font-size:0.8rem;">Verify</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($u['user_id'] !== $adminId): ?>
                    <form method="post" style="display:inline;margin:0;" onsubmit="return confirm('Delete user #<?= $u['user_id'] ?>?');">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                        <button class="btn danger" type="submit" style="padding:4px 10px;font-size:0.8rem;">Delete</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <h2>Audit log (FR20)</h2>
    <div class="card">
        <?php if (!$logs): ?>
            <p class="muted">No admin actions recorded yet.</p>
        <?php else: ?>
        <table class="app">
            <tr><th>When</th><th>Admin</th><th>Action</th><th>Table</th></tr>
            <?php foreach ($logs as $l): ?>
            <tr>
                <td style="font-size:0.82rem;"><?= htmlspecialchars($l['created_at']) ?></td>
                <td><?= htmlspecialchars($l['admin_email']) ?></td>
                <td><?= htmlspecialchars($l['action']) ?></td>
                <td><?= htmlspecialchars($l['target_table']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
