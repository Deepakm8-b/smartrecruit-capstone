<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$consultationId = (int)($_GET['id'] ?? 0);
if ($consultationId <= 0) {
    header('Location: admin_consultation_schedule.php');
    exit;
}

$adminId = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT c.*, s.full_name, s.student_id FROM consultations c JOIN students s ON c.student_id = s.student_id WHERE c.consultation_id = ? AND c.admin_id = ?');
$stmt->execute([$consultationId, $adminId]);
$consultation = $stmt->fetch();

if (!$consultation) {
    header('Location: admin_consultation_schedule.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adminNotes = trim($_POST['admin_notes'] ?? '');
    $recommendations = trim($_POST['recommendations'] ?? '');
    $status = $_POST['status'] ?? 'pending';

    if (empty($adminNotes)) {
        $error = 'Please add consultation notes';
    } else {
        try {
            $stmt = $pdo->prepare('UPDATE consultations SET admin_notes = ?, recommendations = ?, status = ?, completed_at = NOW() WHERE consultation_id = ?');
            $stmt->execute([$adminNotes, $recommendations, $status, $consultationId]);
            $message = 'Consultation updated successfully';
            
            $stmt = $pdo->prepare('SELECT c.*, s.full_name FROM consultations c JOIN students s ON c.student_id = s.student_id WHERE c.consultation_id = ?');
            $stmt->execute([$consultationId]);
            $consultation = $stmt->fetch();
        } catch (Exception $e) {
            $error = 'Update failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation Notes — SmartRecruit Admin</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
<?php require 'nav.php'; ?>
<main class="app-main">
    <a href="admin_consultation_schedule.php" style="color: #0284c7; font-weight: 600; margin-bottom: 16px; display: inline-block;">← Back</a>

    <h1>📝 Consultation Notes</h1>

    <?php if ($message): ?>
        <div class="flash" style="background: #d1fae5; color: #047857;">✓ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="flash" style="background: #fee2e2; color: #991b1b;">✗ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card" style="background: #f0f9ff; border-left: 4px solid #0284c7;">
        <h3 style="margin-top: 0;">Student: <?= htmlspecialchars($consultation['full_name']) ?></h3>
        <p style="margin: 8px 0;">Scheduled: <?= date('M d, Y — H:i', strtotime($consultation['scheduled_date'])) ?></p>
        <p style="margin: 8px 0;">Student's Goals: <?= htmlspecialchars($consultation['student_goals']) ?></p>
    </div>

    <div class="card">
        <h2 style="margin-top: 0;">Consultation Outcome</h2>
        <form method="post">
            
            <label class="field">Your Consultation Notes *
                <textarea name="admin_notes" rows="5" required placeholder="What did you discuss? Key advice?"><?= htmlspecialchars($consultation['admin_notes'] ?? '') ?></textarea>
            </label>

            <label class="field">Recommended Next Steps
                <textarea name="recommendations" rows="4" placeholder="e.g., Complete CompTIA A+, Build portfolio projects"><?= htmlspecialchars($consultation['recommendations'] ?? '') ?></textarea>
            </label>

            <label class="field">Mark as Completed
                <select name="status">
                    <option value="pending" <?= $consultation['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="completed" <?= $consultation['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                </select>
            </label>

            <button type="submit" class="btn" style="width: 100%;">Save & Notify Student</button>
        </form>
    </div>
</main>
</body>
</html>
