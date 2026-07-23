<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$student = getStudentByUserId($pdo, $userId);
$studentId = $student['student_id'] ?? 0;

$updateSuccess = '';
$updateError = '';
$tab = $_GET['tab'] ?? 'profile';

// Get user data
$stmt = $pdo->prepare('SELECT email FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();
$navEmail = $user['email'] ?? 'unknown@email.com';


// Get preferences
$stmt = $pdo->prepare('SELECT * FROM user_preferences WHERE user_id = ?');
$stmt->execute([$userId]);
$prefs = $stmt->fetch();
if (!$prefs) {
    $stmt = $pdo->prepare('INSERT INTO user_preferences (user_id) VALUES (?)');
    $stmt->execute([$userId]);
    $prefs = ['email_notifications' => 1, 'profile_visibility' => 'private', 'newsletter_opt_in' => 1, 'two_factor_enabled' => 0];
}

// PROFILE UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $fullName = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $university = $_POST['university'] ?? '';
    $degree = $_POST['degree'] ?? '';
    $gpa = $_POST['gpa'] ?? '';
    $careerInterest = $_POST['career_interest'] ?? '';
    $professionalSummary = $_POST['professional_summary'] ?? '';
    
    try {
        $stmt = $pdo->prepare('
            UPDATE students 
            SET full_name = ?, phone = ?, university = ?, degree = ?, gpa = ?, 
                career_interest = ?, professional_summary = ?
            WHERE student_id = ?
        ');
        $stmt->execute([$fullName, $phone, $university, $degree, $gpa, $careerInterest, $professionalSummary, $studentId]);
        $updateSuccess = 'Profile updated successfully!';
        $student = getStudentByUserId($pdo, $userId);
    } catch (Exception $e) {
        $updateError = 'Failed to update profile.';
    }
}

// PASSWORD UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if ($newPassword !== $confirmPassword) {
        $updateError = 'New passwords do not match.';
    } elseif (strlen($newPassword) < 8) {
        $updateError = 'Password must be at least 8 characters.';
    } else {
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE user_id = ?');
        $stmt->execute([$userId]);
        $userRecord = $stmt->fetch();
        
        if ($userRecord && password_verify($currentPassword, $userRecord['password_hash'])) {
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
            $stmt->execute([$newHash, $userId]);
            $updateSuccess = 'Password changed successfully!';
        } else {
            $updateError = 'Current password is incorrect.';
        }
    }
}

// NOTIFICATIONS UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_notifications') {
    $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
    $newsletterOptIn = isset($_POST['newsletter_opt_in']) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare('UPDATE user_preferences SET email_notifications = ?, newsletter_opt_in = ? WHERE user_id = ?');
        $stmt->execute([$emailNotifications, $newsletterOptIn, $userId]);
        $updateSuccess = 'Notification preferences updated!';
        $prefs['email_notifications'] = $emailNotifications;
        $prefs['newsletter_opt_in'] = $newsletterOptIn;
    } catch (Exception $e) {
        $updateError = 'Failed to update preferences.';
    }
}

// PRIVACY UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_privacy') {
    $profileVisibility = $_POST['profile_visibility'] ?? 'private';
    
    try {
        $stmt = $pdo->prepare('UPDATE user_preferences SET profile_visibility = ? WHERE user_id = ?');
        $stmt->execute([$profileVisibility, $userId]);
        $updateSuccess = 'Privacy settings updated!';
        $prefs['profile_visibility'] = $profileVisibility;
    } catch (Exception $e) {
        $updateError = 'Failed to update privacy settings.';
    }
}

// ENABLE 2FA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enable_2fa') {
    // Generate secret (simple base32 encoding of random bytes)
    $secret = bin2hex(random_bytes(20));
    
    try {
        $stmt = $pdo->prepare('UPDATE user_preferences SET two_factor_secret = ? WHERE user_id = ?');
        $stmt->execute([$secret, $userId]);
        $updateSuccess = 'Two-factor authentication enabled! Secret: ' . substr($secret, 0, 16) . '...';
        $prefs['two_factor_enabled'] = 1;
    } catch (Exception $e) {
        $updateError = 'Failed to enable 2FA.';
    }
}

// DISABLE 2FA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'disable_2fa') {
    try {
        $stmt = $pdo->prepare('UPDATE user_preferences SET two_factor_enabled = 0, two_factor_secret = NULL WHERE user_id = ?');
        $stmt->execute([$userId]);
        $updateSuccess = 'Two-factor authentication disabled.';
        $prefs['two_factor_enabled'] = 0;
    } catch (Exception $e) {
        $updateError = 'Failed to disable 2FA.';
    }
}

// GET SESSIONS
$stmt = $pdo->prepare('SELECT * FROM user_sessions WHERE user_id = ? ORDER BY last_activity DESC');
$stmt->execute([$userId]);
$sessions = $stmt->fetchAll();

// LOGOUT OTHER SESSIONS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout_others') {
    try {
        $stmt = $pdo->prepare('DELETE FROM user_sessions WHERE user_id = ? AND session_id != ?');
        $currentSessionId = $_SESSION['session_id'] ?? 0;
        $stmt->execute([$userId, $currentSessionId]);
        $updateSuccess = 'Logged out from all other devices.';
        $sessions = [];
    } catch (Exception $e) {
        $updateError = 'Failed to logout from other sessions.';
    }
}

// DELETE ACCOUNT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_account') {
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE user_id = ?');
    $stmt->execute([$userId]);
    $userRecord = $stmt->fetch();
    
    if ($userRecord && password_verify($confirmPassword, $userRecord['password_hash'])) {
        try {
            // Delete user data
            $stmt = $pdo->prepare('DELETE FROM user_preferences WHERE user_id = ?');
            $stmt->execute([$userId]);
            $stmt = $pdo->prepare('DELETE FROM students WHERE user_id = ?');
            $stmt->execute([$userId]);
            $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = ?');
            $stmt->execute([$userId]);
            
            session_destroy();
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $updateError = 'Failed to delete account.';
        }
    } else {
        $updateError = 'Password is incorrect.';
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings — SmartRecruit</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .settings-container { max-width: 1000px; margin: 0 auto; padding: 40px 20px; }
        .settings-header { margin-bottom: 40px; }
        .settings-header a { color: #1e40af; text-decoration: none; font-weight: 600; font-size: 14px; }
        .settings-header h1 { font-size: 28px; font-weight: 700; margin: 16px 0 8px 0; }
        .settings-header p { color: #6b7280; margin: 0; }
        
        .success-banner { background: #d1fae5; border: 1px solid #6ee7b7; color: #047857; padding: 12px 16px; border-radius: 6px; margin-bottom: 24px; }
        .error-banner { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 24px; }
        
        .settings-tabs { display: flex; gap: 0; border-bottom: 2px solid #e5e7eb; margin-bottom: 32px; overflow-x: auto; }
        .settings-tab { padding: 12px 16px; cursor: pointer; font-weight: 600; color: #6b7280; border-bottom: 3px solid transparent; transition: all 0.3s; white-space: nowrap; }
        .settings-tab.active { color: #1e40af; border-bottom-color: #1e40af; }
        .settings-tab:hover { color: #1f2937; }
        
        .settings-form { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; color: #1f2937; margin-bottom: 8px; font-size: 14px; }
        .form-group input,
        .form-group textarea,
        .form-group select { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus { outline: none; border-color: #1e40af; box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1); }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
        }
        
        .checkbox-group { display: flex; align-items: center; gap: 8px; }
        .checkbox-group input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; }
        
        .submit-btn { background: #1e40af; color: white; padding: 10px 24px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .submit-btn:hover { background: #1e3a8a; }
        .submit-btn-danger { background: #ef4444; }
        .submit-btn-danger:hover { background: #dc2626; }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .form-help { font-size: 12px; color: #6b7280; margin-top: 4px; }
        
        .session-item { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; margin-bottom: 12px; }
        .session-device { font-weight: 600; color: #1f2937; }
        .session-info { font-size: 13px; color: #6b7280; margin-top: 4px; }
        
        .danger-zone { background: #fef2f2; border: 1px solid #fee2e2; border-radius: 6px; padding: 20px; margin-top: 32px; }
        .danger-zone h3 { color: #991b1b; margin: 0 0 16px 0; }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="settings-container">
    <div class="settings-header">
        <a href="dashboard.php">← Back to Dashboard</a>
        <h1>⚙️ Settings</h1>
        <p>Manage your account, security, and preferences</p>
        <div style="background: #f3f4f6; padding: 12px 16px; border-radius: 6px; margin-top: 16px; font-size: 13px;">
            <strong>Logged in as:</strong> <?= htmlspecialchars($navEmail) ?> | <strong>User ID:</strong> <?= $userId ?> | <strong>Student ID:</strong> <?= $studentId ?>
        </div>
    </div>

    <?php if ($updateSuccess): ?>
        <div class="success-banner">✓ <?= htmlspecialchars($updateSuccess) ?></div>
    <?php endif; ?>

    <?php if ($updateError): ?>
        <div class="error-banner">✗ <?= htmlspecialchars($updateError) ?></div>
    <?php endif; ?>

    <div class="settings-tabs">
        <div class="settings-tab <?= $tab === 'profile' ? 'active' : '' ?>" onclick="switchTab('profile')">Profile</div>
        <div class="settings-tab <?= $tab === 'security' ? 'active' : '' ?>" onclick="switchTab('security')">Security</div>
        <div class="settings-tab <?= $tab === 'notifications' ? 'active' : '' ?>" onclick="switchTab('notifications')">Notifications</div>
        <div class="settings-tab <?= $tab === 'privacy' ? 'active' : '' ?>" onclick="switchTab('privacy')">Privacy</div>
        <div class="settings-tab <?= $tab === 'sessions' ? 'active' : '' ?>" onclick="switchTab('sessions')">Sessions</div>
        <div class="settings-tab <?= $tab === 'account' ? 'active' : '' ?>" onclick="switchTab('account')">Account</div>
    </div>

    <!-- PROFILE TAB -->
    <div id="profile" class="tab-content <?= $tab === 'profile' ? 'active' : '' ?>">
        <div class="settings-form">
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($student['full_name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" value="<?= htmlspecialchars($navEmail) ?>" disabled style="background: #f3f4f6; color: #6b7280;">
                    <div class="form-help">Email cannot be changed. Contact support if needed.</div>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($student['phone'] ?? '') ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>University</label>
                        <input type="text" name="university" value="<?= htmlspecialchars($student['university'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Degree</label>
                        <input type="text" name="degree" value="<?= htmlspecialchars($student['degree'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>GPA</label>
                        <input type="number" name="gpa" step="0.01" min="0" max="4" value="<?= htmlspecialchars($student['gpa'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Career Interest</label>
                        <input type="text" name="career_interest" value="<?= htmlspecialchars($student['career_interest'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Professional Summary</label>
                    <textarea name="professional_summary"><?= htmlspecialchars($student['professional_summary'] ?? '') ?></textarea>
                    <div class="form-help">Brief overview of your background and goals</div>
                </div>

                <button type="submit" class="submit-btn">Save Profile</button>
            </form>
        </div>
    </div>

    <!-- SECURITY TAB -->
    <div id="security" class="tab-content <?= $tab === 'security' ? 'active' : '' ?>">
        <div class="settings-form">
            <h3 style="margin-top: 0; color: #1f2937;">Change Password</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_password">
                
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required>
                    <div class="form-help">At least 8 characters</div>
                </div>

                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                </div>

                <button type="submit" class="submit-btn">Change Password</button>
            </form>

            <hr style="margin: 32px 0; border: none; border-top: 1px solid #e5e7eb;">

            <h3 style="margin-top: 0; color: #1f2937;">Two-Factor Authentication</h3>
            <?php if ($prefs['two_factor_enabled']): ?>
                <p style="color: #047857; font-weight: 600;">✓ Two-factor authentication is ENABLED</p>
                <form method="POST" style="margin-top: 16px;">
                    <input type="hidden" name="action" value="disable_2fa">
                    <button type="submit" class="submit-btn submit-btn-danger">Disable 2FA</button>
                </form>
            <?php else: ?>
                <p style="color: #6b7280;">Two-factor authentication adds an extra layer of security to your account.</p>
                <form method="POST" style="margin-top: 16px;">
                    <input type="hidden" name="action" value="enable_2fa">
                    <button type="submit" class="submit-btn">Enable 2FA</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- NOTIFICATIONS TAB -->
    <div id="notifications" class="tab-content <?= $tab === 'notifications' ? 'active' : '' ?>">
        <div class="settings-form">
            <form method="POST">
                <input type="hidden" name="action" value="update_notifications">
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="email_notifications" name="email_notifications" <?= $prefs['email_notifications'] ? 'checked' : '' ?>>
                        <label for="email_notifications" style="margin: 0;">Send me email notifications about applications and roadmap updates</label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="newsletter_opt_in" name="newsletter_opt_in" <?= $prefs['newsletter_opt_in'] ? 'checked' : '' ?>>
                        <label for="newsletter_opt_in" style="margin: 0;">Subscribe to SmartRecruit newsletter for tips and career insights</label>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Save Preferences</button>
            </form>
        </div>
    </div>

    <!-- PRIVACY TAB -->
    <div id="privacy" class="tab-content <?= $tab === 'privacy' ? 'active' : '' ?>">
        <div class="settings-form">
            <form method="POST">
                <input type="hidden" name="action" value="update_privacy">
                
                <div class="form-group">
                    <label>Profile Visibility</label>
                    <select name="profile_visibility">
                        <option value="private" <?= $prefs['profile_visibility'] === 'private' ? 'selected' : '' ?>>Private - Only recruiters I apply to can see my profile</option>
                        <option value="public" <?= $prefs['profile_visibility'] === 'public' ? 'selected' : '' ?>>Public - My profile can be discovered by recruiters</option>
                    </select>
                    <div class="form-help">Control who can see your profile information</div>
                </div>

                <button type="submit" class="submit-btn">Save Privacy Settings</button>
            </form>
        </div>
    </div>

    <!-- SESSIONS TAB -->
    <div id="sessions" class="tab-content <?= $tab === 'sessions' ? 'active' : '' ?>">
        <div class="settings-form">
            <h3 style="margin-top: 0; color: #1f2937;">Active Sessions</h3>
            <p style="color: #6b7280; font-size: 14px;">Manage your active login sessions across devices</p>

            <?php if (count($sessions) > 0): ?>
                <?php foreach ($sessions as $session): ?>
                    <div class="session-item">
                        <div class="session-device">📱 <?= htmlspecialchars($session['user_agent'] ?? 'Unknown Device') ?></div>
                        <div class="session-info">IP: <?= htmlspecialchars($session['ip_address'] ?? 'Unknown') ?></div>
                        <div class="session-info">Last active: <?= $session['last_activity'] ? date('M d, Y H:i', strtotime($session['last_activity'])) : 'Never' ?></div>
                    </div>
                <?php endforeach; ?>

                <form method="POST" style="margin-top: 20px;">
                    <input type="hidden" name="action" value="logout_others">
                    <button type="submit" class="submit-btn submit-btn-danger">Logout from All Other Devices</button>
                </form>
            <?php else: ?>
                <p style="color: #6b7280;">No other active sessions</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ACCOUNT TAB -->
    <div id="account" class="tab-content <?= $tab === 'account' ? 'active' : '' ?>">
        <div class="settings-form">
            <div class="danger-zone">
                <h3>⚠️ Delete Account</h3>
                <p style="color: #6b7280;">Permanently delete your SmartRecruit account. This action cannot be undone.</p>
                
                <form method="POST" onsubmit="return confirm('Are you absolutely sure? This will permanently delete your account and all associated data.');">
                    <input type="hidden" name="action" value="delete_account">
                    
                    <div class="form-group">
                        <label>Enter your password to confirm account deletion</label>
                        <input type="password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="submit-btn submit-btn-danger">Permanently Delete Account</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.settings-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
    
    window.history.pushState(null, null, '?tab=' + tabName);
}
</script>

</body>
</html>
