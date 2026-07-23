<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$userId = $_SESSION['user_id'];
$student = getStudentByUserId($pdo, $userId);
$amount = 49.00;
$success = false;

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $cardLast4 = substr($_POST['card_number'] ?? '0000', -4);
    
    // Simulate payment processing
    $transactionId = 'TXN-' . date('YmdHis') . '-' . rand(1000, 9999);
    
    try {
        // Record payment
        $stmt = $pdo->prepare('INSERT INTO payments (payer_id, amount, currency, gateway, gateway_ref, status) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$student['student_id'], $amount, 'AUD', 'PayPal', $transactionId, 'completed']);
        
        // Update user premium status
        $expiryDate = date('Y-m-d', strtotime('+30 days'));
        $stmt = $pdo->prepare('UPDATE users SET premium_status = ?, premium_expiry = ? WHERE user_id = ?');
        $stmt->execute(['premium', $expiryDate, $userId]);
        
        // Create/update subscription
        $stmt = $pdo->prepare('INSERT INTO subscriptions (student_id, plan, amount_paid, start_date, end_date, payment_ref, status) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = \'active\', end_date = ?');
        $stmt->execute([
            $student['student_id'],
            'premium_monthly',
            $amount,
            date('Y-m-d'),
            $expiryDate,
            $transactionId,
            'active',
            $expiryDate
        ]);
        
        $success = true;
    } catch (Exception $e) {
        // Handle error
    }
}

if ($success) {
    header('Location: payment_success.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payment — SmartRecruit</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial; background: linear-gradient(135deg, #1e40af 0%, #7c3aed 100%); min-height: 100vh; display: flex; align-items: center; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: white; padding: 40px; border-radius: 0; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 32px; }
        .header h1 { font-size: 22px; font-weight: 800; margin-bottom: 8px; color: #1e40af; }
        .order-summary { background: #f3f4f6; padding: 16px; margin-bottom: 24px; border-radius: 0; }
        .summary-row { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px; }
        .summary-row.total { font-weight: 800; font-size: 16px; color: #1e40af; border-top: 1px solid #d1d5db; padding-top: 8px; margin-top: 8px; }
        .form-section { margin-bottom: 16px; }
        .form-section label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; }
        .form-section input { width: 100%; padding: 10px; border: 1px solid #d1d5db; font-family: inherit; font-size: 13px; }
        .form-row { display: grid; grid-template-columns: 2fr 1fr; gap: 12px; }
        .btn { width: 100%; padding: 12px; background: #10b981; color: white; border: none; font-weight: 600; cursor: pointer; font-size: 14px; margin-bottom: 12px; }
        .btn:hover { background: #059669; }
        .btn-secondary { background: #6b7280; }
        .btn-secondary:hover { background: #4b5563; }
        .note { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; font-size: 11px; color: #92400e; margin-bottom: 16px; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="header">
        <h1>💳 Payment</h1>
        <p>Complete your upgrade to Premium</p>
    </div>

    <div class="order-summary">
        <div class="summary-row">
            <span>Premium Monthly Plan</span>
            <span>$49.00 AUD</span>
        </div>
        <div class="summary-row">
            <span>Billing Period</span>
            <span>1 Month</span>
        </div>
        <div class="summary-row total">
            <span>Total Due</span>
            <span>$49.00 AUD</span>
        </div>
    </div>

    <div class="note">
        ✓ This is a payment simulation for demonstration purposes. In production, this integrates with PayPal.
    </div>

    <form method="post">
        <h3 style="font-size: 13px; font-weight: 800; margin-bottom: 12px;">Card Details</h3>
        
        <div class="form-section">
            <label>Cardholder Name *</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
        </div>

        <div class="form-section">
            <label>Email *</label>
            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>

        <div class="form-section">
            <label>Card Number * (Simulated)</label>
            <input type="text" name="card_number" placeholder="4111 1111 1111 1111" value="4111111111111111" maxlength="16" required>
        </div>

        <div class="form-row">
            <div class="form-section">
                <label>Expiry (MM/YY) *</label>
                <input type="text" name="expiry" placeholder="12/25" value="12/28" required>
            </div>
            <div class="form-section">
                <label>CVV *</label>
                <input type="text" name="cvv" placeholder="123" value="123" maxlength="3" required>
            </div>
        </div>

        <div class="form-section">
            <label>Postcode *</label>
            <input type="text" name="postcode" value="2000" required>
        </div>

        <input type="hidden" name="amount" value="49.00">

        <button type="submit" class="btn">💳 Pay $49.00 AUD</button>
        <a href="upgrade_premium.php" style="text-decoration: none;">
            <button type="button" class="btn btn-secondary">← Back</button>
        </a>
    </form>

</div>

</body>
</html>
