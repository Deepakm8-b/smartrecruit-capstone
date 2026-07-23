<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$userId = $_SESSION['user_id'];
$student = getStudentByUserId($pdo, $userId);

// Check premium status
$stmt = $pdo->prepare('SELECT premium_status FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();
$isPremium = $user['premium_status'] === 'premium';

if (!$isPremium) {
    header('Location: premium.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Recruiter Referral Status — SmartRecruit</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial; background: #f8fafc; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        .header { margin-bottom: 24px; }
        .header h1 { font-size: 20px; font-weight: 800; margin-bottom: 4px; }
        .header p { color: #666; font-size: 13px; }
        .info-box { background: #dbeafe; border-left: 4px solid #1e40af; padding: 12px; margin-bottom: 24px; font-size: 12px; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: white; padding: 20px; border: 1px solid #e5e7eb; text-align: center; }
        .stat-number { font-size: 28px; font-weight: 800; color: #1e40af; }
        .stat-label { font-size: 12px; color: #666; margin-top: 4px; }
        .card { background: white; padding: 20px; border: 1px solid #e5e7eb; margin-bottom: 16px; }
        .card h3 { font-size: 14px; font-weight: 800; margin-bottom: 16px; }
        .recruiter-item { background: #f9fafb; padding: 16px; border-left: 3px solid #10b981; margin-bottom: 12px; }
        .recruiter-name { font-size: 13px; font-weight: 800; margin-bottom: 4px; }
        .recruiter-company { font-size: 12px; color: #666; margin-bottom: 8px; }
        .recruiter-role { font-size: 11px; color: #999; }
        .status-badge { display: inline-block; background: #dcfce7; color: #047857; padding: 4px 8px; font-size: 11px; font-weight: 600; margin-top: 8px; }
        .btn { padding: 10px 16px; background: #1e40af; color: white; border: none; cursor: pointer; font-weight: 600; font-size: 12px; }
        .btn:hover { background: #1e3a8a; }
        .progress-bar { height: 6px; background: #e5e7eb; border-radius: 0; overflow: hidden; margin-bottom: 8px; }
        .progress-fill { background: #10b981; height: 100%; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="header">
        <h1>🔗 Recruiter Referral Status</h1>
        <p>Your connection with active recruiters</p>
    </div>

    <div class="info-box">
        ✓ This is a premium feature. Get connected with recruiters actively hiring for your role.
    </div>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">3</div>
            <div class="stat-label">Connected Recruiters</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">7</div>
            <div class="stat-label">Job Opportunities</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">2</div>
            <div class="stat-label">Interview Requests</div>
        </div>
    </div>

    <!-- RECRUITERS -->
    <div class="card">
        <h3>Active Recruiter Network</h3>
        
        <div class="recruiter-item">
            <div class="recruiter-name">Sarah Johnson</div>
            <div class="recruiter-company">TechRecruit Staffing Solutions</div>
            <div class="recruiter-role">Senior IT Recruiter • Specializes in IT Support roles</div>
            <div class="status-badge">Connected</div>
        </div>

        <div class="recruiter-item">
            <div class="recruiter-name">Michael Chen</div>
            <div class="recruiter-company">Global Tech Placement Agency</div>
            <div class="recruiter-role">Recruitment Manager • Focus: Infrastructure & Support</div>
            <div class="status-badge">Connected</div>
        </div>

        <div class="recruiter-item">
            <div class="recruiter-name">Jessica Williams</div>
            <div class="recruiter-company">Direct Hire Recruitment</div>
            <div class="recruiter-role">Executive Recruiter • Enterprise clients</div>
            <div class="status-badge">Connected</div>
        </div>
    </div>

    <!-- OPPORTUNITIES -->
    <div class="card">
        <h3>Recommended Opportunities</h3>
        
        <div style="background: #f9fafb; padding: 12px; margin-bottom: 12px; font-size: 12px; line-height: 1.5;">
            <div style="font-weight: 800; margin-bottom: 4px;">IT Support Specialist • Westpac Banking Corporation</div>
            <div style="color: #666;">Sydney, NSW • Full-time • $60,000 - $75,000 • Referred by Sarah Johnson</div>
            <div style="margin-top: 8px;">
                <div style="font-size: 11px; margin-bottom: 4px;">Profile Match: 87%</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 87%;"></div>
                </div>
            </div>
            <button class="btn" style="margin-top: 8px; width: 100%;">View Opportunity</button>
        </div>

        <div style="background: #f9fafb; padding: 12px; margin-bottom: 12px; font-size: 12px; line-height: 1.5;">
            <div style="font-weight: 800; margin-bottom: 4px;">Systems Administrator • NAB (National Australia Bank)</div>
            <div style="color: #666;">Melbourne, VIC • Full-time • $70,000 - $85,000 • Referred by Michael Chen</div>
            <div style="margin-top: 8px;">
                <div style="font-size: 11px; margin-bottom: 4px;">Profile Match: 82%</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 82%;"></div>
                </div>
            </div>
            <button class="btn" style="margin-top: 8px; width: 100%;">View Opportunity</button>
        </div>
    </div>

    <div style="text-align: center; margin-top: 24px;">
        <a href="dashboard.php" style="color: #666; text-decoration: none; font-size: 12px;">← Back to Dashboard</a>
    </div>

</div>

</body>
</html>
