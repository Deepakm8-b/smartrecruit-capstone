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

if (!$student || !$student['is_premium']) {
    header('Location: profile.php');
    exit;
}

$studentId = $student['student_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book') {
    $slotId = (int)($_POST['slot_id'] ?? 0);
    $studentGoals = trim($_POST['student_goals'] ?? '');

    if ($slotId <= 0 || empty($studentGoals)) {
        $error = 'Please select a slot and describe your career goals';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT available_date, admin_id FROM consultation_slots WHERE slot_id = ? AND status = ?');
            $stmt->execute([$slotId, 'available']);
            $slot = $stmt->fetch();

            if (!$slot) {
                $error = 'Slot no longer available';
            } else {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('INSERT INTO consultations (student_id, slot_id, admin_id, scheduled_date, status, student_goals) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$studentId, $slotId, $slot['admin_id'], $slot['available_date'], 'pending', $studentGoals]);
                
                $stmt = $pdo->prepare('UPDATE consultation_slots SET status = ? WHERE slot_id = ?');
                $stmt->execute(['booked', $slotId]);
                
                $pdo->commit();
                $message = 'Consultation booked successfully!';
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Booking failed: ' . $e->getMessage();
        }
    }
}

$stmt = $pdo->prepare('SELECT slot_id, available_date, duration_minutes FROM consultation_slots WHERE status = ? AND available_date > NOW() ORDER BY available_date ASC');
$stmt->execute(['available']);
$availableSlots = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT consultation_id, scheduled_date, status, admin_notes, recommendations FROM consultations WHERE student_id = ? ORDER BY scheduled_date DESC');
$stmt->execute([$studentId]);
$bookedConsultations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Consultation — SmartRecruit</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
<?php require 'nav.php'; ?>
<main class="app-main">
    <h1>🎯 Career Consultation Booking</h1>

    <?php if ($message): ?>
        <div class="flash" style="background: #d1fae5; color: #047857;">✓ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="flash" style="background: #fee2e2; color: #991b1b;">✗ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Your Consultations (<?= count($bookedConsultations) ?>)</h2>
        <?php foreach ($bookedConsultations as $con): ?>
            <div style="background: #f0f9ff; border-left: 4px solid #0284c7; padding: 12px; border-radius: 6px; margin-bottom: 8px;">
                <strong>📅 <?= date('M d, Y — H:i', strtotime($con['scheduled_date'])) ?></strong>
                <span style="font-size: 11px; font-weight: 600; padding: 4px 8px; border-radius: 4px; margin-left: 8px; background: <?= $con['status'] === 'completed' ? '#d1fae5; color: #047857' : '#fef3c7; color: #b45309' ?>;"><?= ucfirst($con['status']) ?></span>
                <?php if ($con['admin_notes']): ?>
                    <div style="margin-top: 8px; padding: 8px; background: white; border-radius: 4px; font-size: 13px;">
                        <strong>Expert Feedback:</strong> <?= htmlspecialchars(substr($con['admin_notes'], 0, 100)) ?>...
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <h2>Book a New Consultation</h2>
        <?php if (count($availableSlots) > 0): ?>
            <form method="post">
                <input type="hidden" name="action" value="book">
                
                <label class="field">Select a Time Slot *
                    <div style="margin-top: 8px;">
                        <?php foreach ($availableSlots as $slot): ?>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="radio" name="slot_id" value="<?= $slot['slot_id'] ?>" required>
                                📅 <?= date('M d, Y — H:i', strtotime($slot['available_date'])) ?> (<?= $slot['duration_minutes'] ?> mins)
                            </label>
                        <?php endforeach; ?>
                    </div>
                </label>

                <label class="field">Your Career Goals *
                    <textarea name="student_goals" rows="4" required placeholder="What are your career objectives? What would you like to discuss?"></textarea>
                </label>

                <button type="submit" class="btn" style="width: 100%;">Book Consultation</button>
            </form>
        <?php else: ?>
            <p class="muted">No available slots. Check back soon!</p>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
