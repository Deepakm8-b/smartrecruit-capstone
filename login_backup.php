<?php
session_start();
require_once 'db.php';
$error = '';
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT user_id, password_hash, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role']    = $user['role'];
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in — SmartRecruit</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; width: 100%; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: linear-gradient(135deg, #0F1B2D 0%, #1a2a4a 100%);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .top-nav {
            background: rgba(15, 27, 45, 0.95);
            padding: 12px 24px;
            border-bottom: 1px solid #1F3864;
        }
        .top-nav a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .top-nav a:hover { color: #4A7FE8; }
        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            margin: 0;
        }
        .login-wrap {
            display: flex;
            width: 900px;
            height: 540px;
            border-radius: 0;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            margin: 0;
            padding: 0;
            border-spacing: 0;
        }
        .left {
            background: linear-gradient(160deg, #1a2a4a 0%, #1F3864 100%);
            width: 450px;
            padding: 44px 36px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: #C8D6EC;
            margin: 0;
            border: none;
        }
        .left .brand { font-size: 1.5rem; font-weight: 800; color: #fff; margin-bottom: 6px; }
        .left .brand span { color: #4A7FE8; }
        .left .tagline { font-size: 0.88rem; margin-bottom: 36px; color: #8BA4CC; }
        .feature { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 22px; }
        .feature .icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: rgba(74,127,232,0.15); font-size: 1.4rem; flex-shrink: 0; }
        .feature strong { display: block; font-size: 0.95rem; margin-bottom: 4px; }
        .feature span { font-size: 0.8rem; color: #8BA4CC; }
        .right {
            background: white;
            width: 450px;
            display: flex;
            flex-direction: column;
            padding: 0;
            margin: 0;
            border: none;
        }
        .auth-tabs {
            display: flex;
            border-bottom: 1px solid #E5E7EB;
            background: #F9FAFB;
        }
        .auth-tabs a {
            flex: 1;
            padding: 16px;
            text-align: center;
            text-decoration: none;
            font-weight: 600;
            color: #6B7280;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .auth-tabs a.active {
            color: #1e40af;
            border-bottom-color: #1e40af;
            background: white;
        }
        .auth-tabs a:hover { color: #1e40af; }
        .auth-content {
            padding: 44px 36px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .auth-content h1 { font-size: 1.4rem; margin-bottom: 12px; color: #1F2937; }
        .auth-content p { margin-bottom: 28px; color: #6B7280; font-size: 0.95rem; }
        input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #D1D5DB;
            border-radius: 0;
            margin-bottom: 16px;
            font-size: 0.95rem;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #1e40af;
            color: white;
            border: none;
            border-radius: 0;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 16px;
        }
        button:hover { background: #1e3a8a; }
        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 0;
            margin-bottom: 16px;
            font-size: 0.9rem;
        }
        .forgot-link {
            text-align: center;
            font-size: 0.9rem;
        }
        .forgot-link a {
            color: #4A7FE8;
            text-decoration: none;
            font-weight: 600;
        }
        .forgot-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="top-nav">
    <a href="index.php">← Home</a>
</div>

<div class="login-container">
    <div class="login-wrap">
        <div class="left">
            <div class="brand">Smart<span>Recruit</span></div>
            <p class="tagline">Australia's Smart Graduate Platform</p>
            
            <div class="feature">
                <div class="icon">🤖</div>
                <div><strong>AI Skill Matching</strong><span>Match score on every job instantly</span></div>
            </div>
            <div class="feature">
                <div class="icon">🎯</div>
                <div><strong>Career Roadmap</strong><span>Step-by-step plan to your target role</span></div>
            </div>
            <div class="feature">
                <div class="icon">👥</div>
                <div><strong>Recruiter Referrals</strong><span>Get referred to partner organisations</span></div>
            </div>
            <div class="feature">
                <div class="icon">🔒</div>
                <div><strong>Secure Payments</strong><span>PayPal Sandbox — no card details stored</span></div>
            </div>
        </div>
        
        <div class="right">
            <div class="auth-tabs">
                <a href="login.php" class="active">Sign In</a>
                <a href="register.php">Create Account</a>
            </div>
            
            <div class="auth-content">
                <h1>Welcome back</h1>
                <p>Sign in to your SmartRecruit account</p>
                
                <?php if ($error): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <input type="email" name="email" placeholder="you@university.edu.au" required>
                    <input type="password" name="password" placeholder="Enter your password" required>
                    <button type="submit">Sign In →</button>
                </form>
                
                <div class="forgot-link">
                    <a href="forgot_password.php">Forgot password?</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
