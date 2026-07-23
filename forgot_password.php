<?php
session_start();
require_once 'db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Please enter your email address';
    } else {
        try {
            // Check if email exists
            $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'No account found with this email';
            } else {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry

                // Save token to database
                $stmt = $pdo->prepare('UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE user_id = ?');
                $stmt->execute([$token, $expiry, $user['user_id']]);

                $message = 'Password reset link has been sent to your email. (In production, this would send an email)';
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_token'] = $token;
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — SmartRecruit</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
<nav class="app-nav">
    <a class="brand" href="index.php">Smart<span>Recruit</span></a>
    <div class="links">
        <a href="index.php">Home</a>
        <a href="login.php">Back to Login</a>
    </div>
</nav>

<main class="app-main" style="max-width: 500px; margin: 80px auto;">
    <div class="card">
        <h1 style="margin-top: 0;">🔐 Reset Password</h1>
        
        <?php if ($message): ?>
            <div class="flash" style="background: #d1fae5; color: #047857;">✓ <?= htmlspecialchars($message) ?></div>
            <div style="margin-top: 24px; padding: 16px; background: #f0f9ff; border-radius: 6px;">
                <p style="margin: 0; font-size: 14px; color: #0284c7;">
                    <strong>Next step:</strong> Check your email for the reset link, or <a href="reset_password.php?token=<?= urlencode($_SESSION['reset_token'] ?? '') ?>" style="color: #0284c7; font-weight: 600;">click here to reset your password</a>
                </p>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="flash" style="background: #fee2e2; color: #991b1b;">✗ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <p class="muted" style="margin-bottom: 24px;">Enter your email address and we'll send you a link to reset your password.</p>

            <form method="post">
                <label class="field">Email Address *
                    <input type="email" name="email" required placeholder="Enter your email">
                </label>

                <button type="submit" class="btn" style="width: 100%;">Send Reset Link</button>
            </form>

            <p style="margin-top: 16px; text-align: center; font-size: 14px;">
                Remember your password? <a href="login.php" style="color: #0284c7; font-weight: 600;">Back to login</a>
            </p>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
