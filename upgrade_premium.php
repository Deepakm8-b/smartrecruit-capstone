<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$userId = $_SESSION['user_id'];
$student = getStudentByUserId($pdo, $userId);

// Check if already premium
$stmt = $pdo->prepare('SELECT premium_status FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();
$isPremium = $user['premium_status'] === 'premium';

if ($isPremium) {
    header('Location: premium.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Upgrade to Premium — SmartRecruit</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial; background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%); min-height: 100vh; display: flex; align-items: center; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 0; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 32px; }
        .header h1 { font-size: 24px; font-weight: 800; margin-bottom: 8px; color: #1e40af; }
        .header p { color: #666; font-size: 13px; }
        .price-box { background: #f0fdf4; border: 2px solid #10b981; padding: 24px; text-align: center; margin-bottom: 24px; border-radius: 0; }
        .price-box .amount { font-size: 36px; font-weight: 800; color: #10b981; }
        .price-box .period { font-size: 14px; color: #666; }
        .features-list { list-style: none; margin-bottom: 24px; }
        .features-list li { padding: 8px 0; font-size: 13px; display: flex; align-items: center; border-bottom: 1px solid #e5e7eb; }
        .features-list li:before { content: '✓'; color: #10b981; font-weight: bold; margin-right: 8px; font-size: 14px; }
        .form-section { margin-bottom: 24px; }
        .form-section label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 8px; color: #374151; }
        .form-section input { width: 100%; padding: 10px; border: 1px solid #d1d5db; font-family: inherit; font-size: 13px; }
        .terms { font-size: 11px; color: #666; line-height: 1.5; margin-bottom: 16px; }
        .terms strong { color: #1e40af; }
        .btn { width: 100%; padding: 12px; background: #10b981; color: white; border: none; font-weight: 600; cursor: pointer; font-size: 14px; margin-bottom: 12px; }
        .btn:hover { background: #059669; }
        .btn-secondary { background: #6b7280; }
        .btn-secondary:hover { background: #4b5563; }
        .divider { text-align: center; color: #999; margin: 16px 0; font-size: 12px; }
        .paypal-note { background: #fef3c7; border: 1px solid #fcd34d; padding: 12px; border-radius: 0; font-size: 12px; color: #92400e; margin-bottom: 16px; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="header">
        <h1>⭐ Upgrade to Premium</h1>
        <p>Unlock advanced features and accelerate your career</p>
    </div>

    <div class="price-box">
        <div class="amount">$49</div>
        <div class="period">per month (cancel anytime)</div>
    </div>

    <h3 style="font-size: 13px; font-weight: 800; margin-bottom: 12px;">What You'll Get:</h3>
    <ul class="features-list">
        <li>Unlimited job applications</li>
        <li>Skill gap analysis & recommendations</li>
        <li>1-on-1 career guidance sessions</li>
        <li>Exclusive training courses</li>
        <li>Professional certificates</li>
        <li>Recruiter referral service</li>
        <li>Advanced job matching (AI-powered)</li>
        <li>Full personalized roadmap access</li>
    </ul>

    <form method="post" action="payment.php">
        <h3 style="font-size: 13px; font-weight: 800; margin-bottom: 12px;">Billing Information</h3>
        
        <div class="form-section">
            <label>Full Name *</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($student['full_name'] ?? '') ?>" required>
        </div>

        <div class="form-section">
            <label>Email *</label>
            <input type="email" name="email" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" required>
        </div>

        <div class="form-section">
            <label>Billing Amount</label>
            <input type="text" value="$49.00 AUD" disabled style="background: #f3f4f6; color: #666;">
        </div>

        <div class="paypal-note">
            💳 You'll be redirected to PayPal to complete your payment securely.
        </div>

        <div class="terms">
            By clicking "Proceed to Payment", you agree to our Terms of Service and authorize the recurring charge of <strong>$49/month</strong> to your account. You can cancel anytime.
        </div>

        <button type="submit" class="btn">💳 Proceed to Payment</button>
        <a href="premium.php" style="text-decoration: none;">
            <button type="button" class="btn btn-secondary">← Back to Plans</button>
        </a>
    </form>

    <div class="divider">Have questions? <a href="ask_expert.php" style="color: #1e40af; text-decoration: none;">Contact support</a></div>

</div>

</body>
</html>
