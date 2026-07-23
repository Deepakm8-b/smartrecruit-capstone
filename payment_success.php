<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT premium_status, premium_expiry FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payment Successful — SmartRecruit</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial; background: linear-gradient(135deg, #10b981 0%, #059669 100%); min-height: 100vh; display: flex; align-items: center; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 0; box-shadow: 0 10px 40px rgba(0,0,0,0.1); text-align: center; }
        .success-icon { font-size: 60px; margin-bottom: 16px; }
        .header h1 { font-size: 28px; font-weight: 800; margin-bottom: 8px; color: #047857; }
        .header p { color: #666; font-size: 14px; margin-bottom: 24px; }
        .details-box { background: #f0fdf4; border: 1px solid #6ee7b7; padding: 20px; margin-bottom: 24px; border-radius: 0; }
        .detail-row { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px; }
        .detail-row strong { color: #047857; }
        .features-list { list-style: none; background: #f9fafb; padding: 16px; margin-bottom: 24px; text-align: left; }
        .features-list li { padding: 6px 0; font-size: 12px; display: flex; align-items: center; }
        .features-list li:before { content: '✓'; color: #10b981; font-weight: bold; margin-right: 8px; }
        .btn { width: 100%; padding: 12px; background: #10b981; color: white; border: none; font-weight: 600; cursor: pointer; font-size: 14px; margin-bottom: 12px; }
        .btn:hover { background: #059669; }
        .btn-secondary { background: #1e40af; }
        .btn-secondary:hover { background: #1e3a8a; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="success-icon">✓</div>
    <div class="header">
        <h1>Payment Successful!</h1>
        <p>Welcome to SmartRecruit Premium</p>
    </div>

    <div class="details-box">
        <div class="detail-row">
            <span>Plan</span>
            <strong>Premium Monthly</strong>
        </div>
        <div class="detail-row">
            <span>Amount Paid</span>
            <strong>$49.00 AUD</strong>
        </div>
        <div class="detail-row">
            <span>Expiry Date</span>
            <strong><?= date('M d, Y', strtotime($user['premium_expiry'])) ?></strong>
        </div>
        <div class="detail-row">
            <span>Status</span>
            <strong style="color: #10b981;">Active ✓</strong>
        </div>
    </div>

    <h3 style="font-size: 13px; font-weight: 800; margin-bottom: 12px; text-align: left;">Now You Can Access:</h3>
    <ul class="features-list">
        <li>Unlimited job applications</li>
        <li>Skill gap analysis</li>
        <li>Career guidance sessions</li>
        <li>Premium training courses</li>
        <li>Professional certificates</li>
        <li>Recruiter referral service</li>
        <li>Advanced job matching</li>
        <li>Full personalized roadmap</li>
    </ul>

    <a href="dashboard.php" style="text-decoration: none;">
        <button class="btn">🎉 Go to Dashboard</button>
    </a>
    
    <a href="skill_gap_analysis.php" style="text-decoration: none;">
        <button class="btn btn-secondary">📊 View Skill Analysis</button>
    </a>

    <div style="font-size: 11px; color: #666; margin-top: 16px;">
        A confirmation email has been sent to your inbox.<br>
        Questions? <a href="ask_expert.php" style="color: #10b981; text-decoration: none;">Contact support</a>
    </div>

</div>

</body>
</html>
