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
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Referrals - SmartRecruit</title>
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
.stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px; }
.stat-card { background: white; padding: 24px; border-radius: 8px; border: 1px solid #e5e7eb; }
.stat-num { font-size: 36px; font-weight: 700; color: #1e40af; }
.stat-label { font-size: 14px; color: #6b7280; margin-top: 8px; }
.empty { text-align: center; padding: 40px; color: #6b7280; background: white; border-radius: 8px; border: 1px solid #e5e7eb; }
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

    <h2>🔗 Referral Payments</h2>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-num">$0.00</div>
            <div class="stat-label">Amount Due</div>
        </div>
        <div class="stat-card">
            <div class="stat-num">$0.00</div>
            <div class="stat-label">Already Paid</div>
        </div>
    </div>

    <div class="empty">
        <p>No referral payments yet</p>
        <p style="font-size: 13px; margin-top: 8px;">Payments will appear when you hire referred students</p>
    </div>
</div>

</body>
</html>
