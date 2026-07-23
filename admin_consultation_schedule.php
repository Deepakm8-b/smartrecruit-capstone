<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$adminId = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_slot') {
    $availableDate = trim($_POST['available_date'] ?? '');
    $durationMinutes = (int)($_POST['duration_minutes'] ?? 60);

    if (empty($availableDate) || $durationMinutes <= 0) {
        $error = 'Please provide a valid date and duration';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO consultation_slots (admin_id, available_date, duration_minutes, status) VALUES (?, ?, ?, ?)');
            $stmt->execute([$adminId, $availableDate, $durationMinutes, 'available']);
            $message = 'Consultation slot created successfully';
        } catch (Exception $e) {
            $error = 'Failed: ' . $e->getMessage();
        }
    }
}

$stmt = $pdo->prepare('SELECT slot_id, available_date, duration_minutes, status FROM consultation_slots WHERE admin_id = ? ORDER BY available_date DESC');
$stmt->execute([$adminId]);
$slots = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT c.consultation_id, c.scheduled_date, c.status, s.full_name FROM consultations c JOIN students s ON c.student_id = s.student_id JOIN consultation_slots cs ON c.slot_id = cs.slot_id WHERE cs.admin_id = ? ORDER BY c.scheduled_date DESC');
$stmt->execute([$adminId]);
$consultations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation Schedule — SmartRecruit Admin</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
<?php require 'nav.php'; ?>
<main class="app-main">
    <h1>📅 Consultation Schedule</h1>
    
    <?php if ($message): ?>
        <div class="flash" style="background: #d1fae5; color: #047857;">✓ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="flash" style="background: #fee2e2; color: #991b1b;">✗ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <h2 style="margin-top: 0;">Create New Consultation Slot</h2>
        <form method="post">
            <input type="hidden" name="action" value="create_slot">
            <label class="field">Date & Time
                <input type="datetime-local" name="available_date" required>
            </label>
            <label class="field">Duration (minutes)
                <input type="number" name="duration_minutes" value="60" min="30" max="180" required>
            </label>
            <button type="submit" class="btn">Create Slot</button>
        </form>
    </div>

    <div class="card">
        <h2>Available Slots (<?= count(array_filter($slots, fn($s) => $s['status'] === 'available')) ?>)</h2>
        <?php foreach ($slots as $slot): ?>
            <?php if ($slot['status'] === 'available'): ?>
                <div style="background: #f0f9ff; border-left: 4px solid #0284c7; padding: 12px; border-radius: 6px; margin-bottom: 8px;">
                    📅 <?= date('M d, Y — H:i', strtotime($slot['available_date'])) ?> (<?= $slot['duration_minutes'] ?> mins)
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <h2>Upcoming Consultations (<?= count($consultations) ?>)</h2>
        <?php foreach ($consultations as $con): ?>
            <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px; border-radius: 6px; margin-bottom: 8px;">
                📞 <?= htmlspecialchars($con['full_name']) ?> — <?= date('M d, Y — H:i', strtotime($con['scheduled_date'])) ?>
                <a href="admin_consultation_notes.php?id=<?= $con['consultation_id'] ?>" style="margin-left: 16px; color: #0284c7; font-weight: 600;">Add Notes →</a>
            </div>
        <?php endforeach; ?>
    </div>
</main>
</body>
</html>
