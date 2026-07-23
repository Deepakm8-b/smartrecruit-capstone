<?php
session_start();
require_once 'db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        
        if ($user['role'] === 'organisation') {
            header('Location: recruiter_dashboard.php');
        } else {
            header('Location: dashboard.php');
        }
        exit;
    } else {
        $error = 'Invalid credentials';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Login - SmartRecruit</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI'; background: linear-gradient(135deg, #1e3c72, #2a5298); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
.container { display: flex; width: 100%; max-width: 1100px; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
.left { flex: 1; padding: 60px 40px; background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; }
.left-brand { margin-bottom: 50px; }
.left-brand h2 { font-size: 32px; font-weight: 700; margin-bottom: 8px; }
.left-brand h2 span { color: #ffc107; }
.left-brand p { font-size: 14px; opacity: 0.9; }
.features { margin-top: 40px; }
.feature { display: flex; gap: 16px; margin-bottom: 32px; }
.feature-icon { font-size: 28px; min-width: 28px; }
.feature-content h4 { font-weight: 700; margin-bottom: 4px; font-size: 15px; }
.feature-content p { font-size: 13px; opacity: 0.85; }
.right { flex: 1; padding: 60px 50px; }
.right-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
.right-header h1 { font-size: 28px; font-weight: 700; }
.right-header a { color: #1e40af; text-decoration: none; font-weight: 600; font-size: 14px; }
.right-subtext { color: #6b7280; font-size: 14px; margin-bottom: 30px; }
.form-group { margin-bottom: 24px; }
label { display: block; margin-bottom: 8px; font-weight: 700; font-size: 14px; color: #1f2937; }
input { width: 100%; padding: 12px 16px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
input:focus { border-color: #1e40af; outline: none; box-shadow: 0 0 0 3px rgba(30,64,175,0.1); }
button { width: 100%; padding: 14px; background: #1e40af; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 15px; cursor: pointer; margin-top: 10px; }
button:hover { background: #1e3a8a; }
.error { color: #dc2626; background: #fee2e2; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
.signin-link { text-align: center; margin-top: 20px; font-size: 13px; color: #6b7280; }
.signin-link a { color: #1e40af; text-decoration: none; font-weight: 700; }
</style>
</head>
<body>

<div class="container">
    <div class="left">
        <div class="left-brand">
            <h2>Smart<span>Recruit</span></h2>
            <p>Australia's Smart Graduate Platform</p>
        </div>

        <div class="features">
            <div class="feature">
                <div class="feature-icon">🎯</div>
                <div class="feature-content">
                    <h4>AI Skill Matching</h4>
                    <p>Match score on every job instantly</p>
                </div>
            </div>
            <div class="feature">
                <div class="feature-icon">🗺️</div>
                <div class="feature-content">
                    <h4>Career Roadmap</h4>
                    <p>Step-by-step plan to your target role</p>
                </div>
            </div>
            <div class="feature">
                <div class="feature-icon">🔗</div>
                <div class="feature-content">
                    <h4>Recruiter Referrals</h4>
                    <p>Get referred to partner organisations</p>
                </div>
            </div>
            <div class="feature">
                <div class="feature-icon">🔒</div>
                <div class="feature-content">
                    <h4>Secure Payments</h4>
                    <p>BCrypt hashing · PDO prepared statements</p>
                </div>
            </div>
        </div>
    </div>

    <div class="right">
        <div class="right-header">
            <h1>Welcome back</h1>
            <a href="index.php">← Home</a>
        </div>
        
        <p class="right-subtext">Sign in to your SmartRecruit account</p>

        <?php if ($error): echo "<div class='error'>$error</div>"; endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Sign In →</button>
        </form>

        <div class="signin-link">
            Don't have an account? <a href="register.php">Create one →</a>
        </div>
    </div>
</div>

</body>
</html>
