<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$userId = $_SESSION['user_id'];
$student = getStudentByUserId($pdo, $userId);

// Get user's premium status
$stmt = $pdo->prepare('SELECT premium_status, premium_expiry FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();
$isPremium = $user['premium_status'] === 'premium';
$premiumExpiry = $user['premium_expiry'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Membership — SmartRecruit</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f8fafc; }
        .header { background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%); color: white; padding: 40px 20px; text-align: center; }
        .header h1 { font-size: 32px; font-weight: 800; margin-bottom: 8px; }
        .header p { font-size: 16px; opacity: 0.9; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .status-banner { background: #dbeafe; border-left: 4px solid #1e40af; padding: 16px; margin-bottom: 40px; border-radius: 0; }
        .status-banner.premium { background: #dcfce7; border-left-color: #10b981; }
        .status-banner strong { color: #1e40af; }
        .status-banner.premium strong { color: #047857; }
        .comparison { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 40px; }
        .plan-card { background: white; border: 2px solid #e5e7eb; padding: 24px; }
        .plan-card.premium-card { border-color: #10b981; background: #f0fdf4; }
        .plan-name { font-size: 20px; font-weight: 800; margin-bottom: 8px; }
        .plan-price { font-size: 28px; font-weight: 800; color: #1e40af; margin-bottom: 4px; }
        .plan-price.premium-price { color: #10b981; }
        .plan-price span { font-size: 14px; color: #666; }
        .plan-desc { color: #666; font-size: 13px; margin-bottom: 24px; line-height: 1.6; }
        .features-list { list-style: none; margin-bottom: 24px; }
        .features-list li { padding: 8px 0; font-size: 13px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; }
        .features-list li:before { content: '✓'; color: #10b981; font-weight: bold; margin-right: 8px; font-size: 14px; }
        .features-list li.disabled { color: #999; }
        .features-list li.disabled:before { content: '✗'; color: #ef4444; }
        .btn { padding: 12px 24px; border: none; font-weight: 600; cursor: pointer; font-size: 14px; border-radius: 0; }
        .btn-primary { background: #1e40af; color: white; width: 100%; }
        .btn-primary:hover { background: #1e3a8a; }
        .btn-success { background: #10b981; color: white; width: 100%; }
        .btn-success:hover { background: #059669; }
        .btn-secondary { background: #6b7280; color: white; width: 100%; }
        .features-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 40px; }
        .feature-card { background: white; padding: 20px; border: 1px solid #e5e7eb; text-align: center; }
        .feature-card h3 { font-size: 16px; font-weight: 800; margin-bottom: 8px; color: #1e40af; }
        .feature-card p { font-size: 12px; color: #666; line-height: 1.5; }
        .feature-icon { font-size: 24px; margin-bottom: 8px; }
        @media (max-width: 768px) {
            .comparison { grid-template-columns: 1fr; }
            .features-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="header">
    <h1>⭐ SmartRecruit Premium</h1>
    <p>Unlock your career potential with premium features</p>
</div>

<div class="container">
    
    <?php if ($isPremium): ?>
        <div class="status-banner premium">
            <strong>✓ You're a Premium Member!</strong>
            Expiry Date: <?= date('M d, Y', strtotime($premiumExpiry)) ?>
        </div>
    <?php else: ?>
        <div class="status-banner">
            <strong>Free Plan Active</strong> — Upgrade to Premium for advanced features
        </div>
    <?php endif; ?>

    <!-- COMPARISON TABLE -->
    <div class="comparison">
        
        <!-- FREE PLAN -->
        <div class="plan-card">
            <div class="plan-name">📌 Free Plan</div>
            <div class="plan-price"><span>$0</span></div>
            <div class="plan-desc">Perfect for getting started with your career journey</div>
            
            <ul class="features-list">
                <li>Basic job matching</li>
                <li>Limited job applications (5/month)</li>
                <li>Basic profile</li>
                <li>Roadmap access <span style="color: #999;"> (basic only)</span></li>
                <li class="disabled">Skill gap analysis</li>
                <li class="disabled">Career guidance (1-on-1)</li>
                <li class="disabled">Premium training sessions</li>
                <li class="disabled">Certificates</li>
                <li class="disabled">Recruiter referral service</li>
                <li class="disabled">Resume reviews</li>
            </ul>
            
            <?php if ($isPremium): ?>
                <button class="btn btn-secondary" disabled>Current Plan</button>
            <?php else: ?>
                <button class="btn btn-secondary" disabled>Current Plan</button>
            <?php endif; ?>
        </div>

        <!-- PREMIUM PLAN -->
        <div class="plan-card premium-card">
            <div class="plan-name">⭐ Premium Plan</div>
            <div class="plan-price premium-price">$49<span>/month</span></div>
            <div class="plan-desc">Everything you need to accelerate your career growth</div>
            
            <ul class="features-list">
                <li>✓ Advanced job matching (AI-powered)</li>
                <li>✓ Unlimited job applications</li>
                <li>✓ Enhanced profile features</li>
                <li>✓ Full roadmap access (personalized)</li>
                <li>✓ Skill gap analysis & recommendations</li>
                <li>✓ 1-on-1 career guidance sessions</li>
                <li>✓ Exclusive training courses</li>
                <li>✓ Certificate programs</li>
                <li>✓ Recruiter referral service</li>
                <li>✓ Professional resume reviews</li>
            </ul>
            
            <?php if ($isPremium): ?>
                <button class="btn btn-success" disabled>✓ Active</button>
            <?php else: ?>
                <a href="upgrade_premium.php" style="text-decoration: none;">
                    <button class="btn btn-success">Upgrade Now</button>
                </a>
            <?php endif; ?>
        </div>

    </div>

    <!-- PREMIUM FEATURES SHOWCASE -->
    <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 24px; text-align: center;">Premium Features</h2>
    
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <h3>Skill Gap Analysis</h3>
            <p>Identify skill gaps and get personalized recommendations to reach your career goals</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">🎓</div>
            <h3>Training Sessions</h3>
            <p>Access exclusive training courses taught by industry experts</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">📜</div>
            <h3>Certificates</h3>
            <p>Earn professional certificates to boost your resume</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">👔</div>
            <h3>Career Guidance</h3>
            <p>Get 1-on-1 coaching sessions with career experts</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">🔗</div>
            <h3>Recruiter Referrals</h3>
            <p>Get connected with recruiters actively hiring for your role</p>
        </div>
        
        <div class="feature-card">
            <div class="feature-icon">🎯</div>
            <h3>Advanced Matching</h3>
            <p>AI-powered job matching algorithm for better recommendations</p>
        </div>
    </div>

    <div style="text-align: center; margin-top: 40px;">
        <?php if ($isPremium): ?>
            <p style="color: #666; font-size: 13px;">Enjoy your premium benefits!</p>
            <a href="dashboard.php" style="color: #1e40af; text-decoration: none;">← Back to Dashboard</a>
        <?php else: ?>
            <p style="color: #666; font-size: 13px;">Ready to advance your career?</p>
            <a href="upgrade_premium.php" style="background: #1e40af; color: white; padding: 12px 24px; text-decoration: none; display: inline-block; font-weight: 600;">Upgrade to Premium</a>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
