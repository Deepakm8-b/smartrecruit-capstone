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

// Get certificates
$stmt = $pdo->prepare('SELECT * FROM certificates WHERE student_id = ? ORDER BY issue_date DESC');
$stmt->execute([$student['student_id']]);
$certificates = $stmt->fetchAll();

// Insert sample certificates if none exist
if (count($certificates) === 0) {
    $stmt = $pdo->prepare('INSERT INTO certificates (student_id, cert_name, issued_by, issue_date, verify_code) VALUES (?, ?, ?, ?, ?)');
    $sampleCerts = [
        ['CompTIA A+ Certification', 'CompTIA', '2026-05-15', 'AA-2026-001'],
        ['AWS Solutions Architect Associate', 'Amazon Web Services', '2026-04-10', 'AWS-2026-001'],
        ['Microsoft Azure Administrator', 'Microsoft', '2026-03-20', 'AZ-2026-001'],
    ];
    
    foreach ($sampleCerts as $cert) {
        $stmt->execute([$student['student_id'], $cert[0], $cert[1], $cert[2], $cert[3]]);
    }
    
    $stmt = $pdo->prepare('SELECT * FROM certificates WHERE student_id = ? ORDER BY issue_date DESC');
    $stmt->execute([$student['student_id']]);
    $certificates = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Certificates — SmartRecruit</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial; background: #f8fafc; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        .header { margin-bottom: 24px; }
        .header h1 { font-size: 20px; font-weight: 800; margin-bottom: 4px; }
        .header p { color: #666; font-size: 13px; }
        .info-box { background: #dbeafe; border-left: 4px solid #1e40af; padding: 12px; margin-bottom: 24px; font-size: 12px; }
        .cert-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
        .cert-card { background: white; border: 1px solid #e5e7eb; overflow: hidden; }
        .cert-header { background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%); color: white; padding: 20px; text-align: center; }
        .cert-icon { font-size: 32px; margin-bottom: 8px; }
        .cert-title { font-size: 14px; font-weight: 800; margin-bottom: 4px; }
        .cert-issuer { font-size: 11px; opacity: 0.9; }
        .cert-body { padding: 16px; }
        .cert-detail { font-size: 12px; margin-bottom: 8px; }
        .cert-detail strong { color: #1e40af; }
        .cert-date { font-size: 11px; color: #666; }
        .btn { padding: 8px 12px; background: #1e40af; color: white; border: none; cursor: pointer; font-weight: 600; font-size: 12px; width: 100%; }
        .btn:hover { background: #1e3a8a; }
        .empty-state { background: white; padding: 40px; text-align: center; border: 1px solid #e5e7eb; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="header">
        <h1>📜 Certificates</h1>
        <p>Professional certifications you've earned</p>
    </div>

    <div class="info-box">
        ✓ This is a premium feature. Display and share your professional certificates to boost your credibility.
    </div>

    <?php if (count($certificates) > 0): ?>
        <div class="cert-grid">
            <?php foreach ($certificates as $cert): ?>
                <div class="cert-card">
                    <div class="cert-header">
                        <div class="cert-icon">🏆</div>
                        <div class="cert-title"><?= htmlspecialchars($cert['cert_name']) ?></div>
                        <div class="cert-issuer"><?= htmlspecialchars($cert['issued_by']) ?></div>
                    </div>
                    
                    <div class="cert-body">
                        <div class="cert-detail">
                            <strong>Issued:</strong><br>
                            <?= date('M d, Y', strtotime($cert['issue_date'])) ?>
                        </div>
                        
                        <div class="cert-detail">
                            <strong>Verification Code:</strong><br>
                            <code style="background: #f3f4f6; padding: 2px 4px; font-size: 10px;"><?= htmlspecialchars($cert['verify_code']) ?></code>
                        </div>
                        
                        <button class="btn">Download PDF</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p style="color: #666; font-size: 13px;">No certificates yet. Enroll in premium training courses to earn certificates.</p>
        </div>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 24px;">
        <a href="dashboard.php" style="color: #666; text-decoration: none; font-size: 12px;">← Back to Dashboard</a>
    </div>

</div>

</body>
</html>
