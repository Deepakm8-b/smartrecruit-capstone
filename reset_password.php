<?php
session_start();
require_once 'db.php';

$error = '';
$message = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Invalid or missing reset token';
}

// Verify token exists and hasn't expired
if (!$error) {
    $stmt = $pdo->prepare('SELECT user_id, email FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()');
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = 'Reset link is invalid or has expired. Please request a new one.';
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['password_confirm'] ?? '';

    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in all password fields';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        try {
            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
            
            $stmt = $pdo->prepare('
                UPDATE users 
                SET password_hash = ?, reset_token = NULL, reset_token_expiry = NULL 
                WHERE reset_token = ?
            ');
            $stmt->execute([$passwordHash, $token]);

            $message = 'Password reset successfully! You can now log in with your new password.';
            $_SESSION['password_reset_success'] = true;
        } catch (Exception $e) {
            $error = 'Error resetting password: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — SmartRecruit</title>
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
        <h1 style="margin-top: 0;">🔐 Create New Password</h1>

        <?php if ($message): ?>
            <div class="flash" style="background: #d1fae5; color: #047857;">✓ <?= htmlspecialchars($message) ?></div>
            <div style="margin-top: 24px;">
                <a href="login.php" class="btn" style="display: block; text-align: center;">Go to Login</a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="flash" style="background: #fee2e2; color: #991b1b;">✗ <?= htmlspecialchars($error) ?></div>
            <?php else: ?>
                <p class="muted" style="margin-bottom: 24px;">Enter your new password below.</p>

                <form method="post">
                    <label class="field">New Password *
                        <input type="password" name="password" required placeholder="Enter new password">
                    </label>

                    <label class="field">Confirm Password *
                        <input type="password" name="password_confirm" required placeholder="Confirm password">
                    </label>

                    <button type="submit" class="btn" style="width: 100%;">Reset Password</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
