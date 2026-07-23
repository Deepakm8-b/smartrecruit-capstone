<?php
/**
 * org_profile.php — Organisation profile setup (FR10)
 * Author: Deepak Bhandari (2443463047)
 */
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'organisation') {
    header('Location: dashboard.php'); exit;
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT email FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$navEmail = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT * FROM organisations WHERE user_id = ?');
$stmt->execute([$userId]);
$org = $stmt->fetch();
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['org_name'] ?? '');
    $abn      = trim($_POST['abn'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $location = trim($_POST['location'] ?? '');

    if ($org) {
        $stmt = $pdo->prepare('UPDATE organisations SET org_name=?, abn=?, industry=?, location=? WHERE org_id=?');
        $stmt->execute([$name, $abn, $industry, $location, $org['org_id']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO organisations (user_id, org_name, abn, industry, location) VALUES (?,?,?,?,?)');
        $stmt->execute([$userId, $name, $abn, $industry, $location]);
    }
    $saved = true;
    $stmt = $pdo->prepare('SELECT * FROM organisations WHERE user_id = ?');
    $stmt->execute([$userId]);
    $org = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organisation Profile — SmartRecruit</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
<?php require 'nav.php'; ?>
<main class="app-main">
    <h1>Organisation Profile</h1>
    <p class="subtitle">Set up your company details to start posting jobs.</p>

    <?php if ($saved): ?>
        <div class="flash">Organisation profile saved.</div>
    <?php endif; ?>

    <form method="post" class="card">
        <label class="field">Company name
            <input type="text" name="org_name" required value="<?= htmlspecialchars($org['org_name'] ?? '') ?>">
        </label>
        <label class="field">ABN (Australian Business Number)
            <input type="text" name="abn" maxlength="11" placeholder="11 digits" value="<?= htmlspecialchars($org['abn'] ?? '') ?>">
        </label>
        <label class="field">Industry
            <input type="text" name="industry" placeholder="e.g. IT Services, Finance" value="<?= htmlspecialchars($org['industry'] ?? '') ?>">
        </label>
        <label class="field">Location
            <input type="text" name="location" placeholder="e.g. Sydney NSW" value="<?= htmlspecialchars($org['location'] ?? '') ?>">
        </label>
        <button class="btn" type="submit" style="width:100%;padding:14px;">Save Profile →</button>
    </form>

    <?php if ($org): ?>
        <p style="margin-top:18px;"><a class="btn secondary" href="post_job.php">Post a new job →</a></p>
    <?php endif; ?>
</main>
</body>
</html>
