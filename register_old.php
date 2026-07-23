<?php
/**
 * register.php — SmartRecruit account registration (FR01)
 * Author: Deepak Bhandari (2443463047)
 *
 * Implements FR01: students (and organisations) register with a valid
 * email and a secure account. Passwords are hashed with BCrypt via
 * password_hash() — never stored as plain text.
 *
 * !! COLUMN NAME CHECK (do once) !!
 * In phpMyAdmin run:  DESCRIBE users;
 * This file assumes columns: user_id, email, password_hash, role.
 * If your SQL uses different names (e.g. `password` instead of
 * `password_hash`), change ONLY the marked lines below.
 */

session_start();
require_once 'db.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Collect and trim input
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $role     = $_POST['role'] ?? 'student';

    // 2. Validate (server-side — never trust the browser)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (!in_array($role, ['student', 'organisation'], true)) {
        $errors[] = 'Invalid role selected.';   // admin accounts are never self-registered
    }

    // 3. Check the email is not already registered
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'This email is already registered. Try logging in.';
        }
    }

    // 4. Insert the new account
    if (!$errors) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        // CHECK: column names `email`, `password_hash`, `role`
        $stmt = $pdo->prepare(
            'INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)'
        );
        $stmt->execute([$email, $hash, $role]);

        $_SESSION['flash'] = 'Account created. Please log in.';
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — SmartRecruit</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main style="max-width:420px;margin:60px auto;padding:0 16px;">
        <h1>Create your account</h1>

        <?php foreach ($errors as $e): ?>
            <p style="color:#b00020;"><?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>

        <form method="post" action="register.php">
            <label>Email
                <input type="email" name="email" required
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </label><br><br>

            <label>Password (min 8 characters)
                <input type="password" name="password" minlength="8" required>
            </label><br><br>

            <label>Confirm password
                <input type="password" name="confirm_password" required>
            </label><br><br>

            <label>I am a
                <select name="role">
                    <option value="student">Student</option>
                    <option value="organisation">Organisation</option>
                </select>
            </label><br><br>

            <button type="submit">Register</button>
        </form>

        <p>Already have an account? <a href="login.php">Log in</a></p>
    </main>
</body>
</html>
