<?php
session_start();
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $role = $_POST['role'] ?? '';

    if (empty($email) || empty($password) || empty($name) || empty($role)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $password_confirm) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already registered';
            } else {
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)');
                $stmt->execute([$email, $password_hash, $role]);
                $user_id = $pdo->lastInsertId();

                if ($role === 'student') {
                    $stmt = $pdo->prepare('INSERT INTO students (user_id, full_name) VALUES (?, ?)');
                    $stmt->execute([$user_id, $name]);
                } elseif ($role === 'organisation') {
                    $stmt = $pdo->prepare('INSERT INTO organisations (user_id, org_name) VALUES (?, ?)');
                    $stmt->execute([$user_id, $name]);
                }

                $success = 'Registration successful! Redirecting to login...';
                header('Refresh: 2; URL=login.php');
            }
        } catch (Exception $e) {
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — SmartRecruit</title>
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
            overflow-y: auto;
        }
        .auth-content h1 { font-size: 1.4rem; margin-bottom: 12px; color: #1F2937; }
        .auth-content p { margin-bottom: 28px; color: #6B7280; font-size: 0.95rem; }
        input, select {
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
        .success {
            background: #d1fae5;
            color: #047857;
            padding: 12px;
            border-radius: 0;
            margin-bottom: 16px;
            font-size: 0.9rem;
        }
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
                <a href="login.php">Sign In</a>
                <a href="register.php" class="active">Create Account</a>
            </div>
            
            <div class="auth-content">
                <h1>Get started</h1>
                <p>Create your SmartRecruit account</p>
                
                <?php if ($success): ?>
                    <div class="success"><?= htmlspecialchars($success) ?></div>
                <?php elseif ($error): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <form method="post">
                    <input type="text" name="name" placeholder="Full name" required>
                    <input type="email" name="email" placeholder="you@university.edu.au" required>
                    <select name="role" required>
                        <option value="">Select your role</option>
                        <option value="student">Student</option>
                        <option value="organisation">Organisation/Recruiter</option>
                    </select>
                    <input type="password" name="password" placeholder="Create password" required>
                    <input type="password" name="password_confirm" placeholder="Confirm password" required>
                    <button type="submit">Create Account →</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
