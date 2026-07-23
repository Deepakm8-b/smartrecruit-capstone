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

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll'])) {
    $sessionId = intval($_POST['session_id'] ?? 0);
    
    if ($sessionId > 0) {
        try {
            $stmt = $pdo->prepare('INSERT INTO bookings (student_id, session_id, amount_paid, status, attended) VALUES (?, ?, 0, "booked", 0)');
            $stmt->execute([$studentId, $sessionId]);
            $success = true;
        } catch (Exception $e) {
            $error = 'Already enrolled in this session or booking failed.';
        }
    }
}

$stmt = $pdo->prepare('SELECT * FROM training_sessions ORDER BY session_date ASC');
$stmt->execute();
$sessions = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT session_id FROM bookings WHERE student_id = ? AND status = "booked"');
$stmt->execute([$studentId]);
$enrolledSessions = $stmt->fetchAll(PDO::FETCH_COLUMN);
$enrolledSessionIds = array_map('intval', $enrolledSessions);

$navEmail = '';
$stmt = $pdo->prepare('SELECT email FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$navEmail = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Courses — SmartRecruit</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .training-container { max-width: 1000px; margin: 0 auto; padding: 40px 20px; }
        .training-header { margin-bottom: 40px; }
        .training-header a { color: #1e40af; text-decoration: none; font-weight: 600; font-size: 14px; }
        .training-header h1 { font-size: 28px; font-weight: 700; margin: 16px 0 8px 0; }
        .training-header p { color: #6b7280; margin: 0; }
        
        .success-banner { background: #d1fae5; border: 1px solid #6ee7b7; color: #047857; padding: 12px 16px; border-radius: 6px; margin-bottom: 24px; }
        .error-banner { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 24px; }
        
        .filters { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
        .filter-btn { padding: 8px 16px; border: 1px solid #d1d5db; border-radius: 6px; background: white; cursor: pointer; font-size: 14px; font-weight: 600; }
        .filter-btn.active { background: #1e40af; color: white; border-color: #1e40af; }
        
        .sessions-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; }
        .session-card { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; }
        .session-card.enrolled { border-color: #10b981; background: #f0fdf4; }
        
        .session-title { font-size: 16px; font-weight: 700; color: #1f2937; margin-bottom: 8px; }
        .session-partner { font-size: 13px; color: #6b7280; margin-bottom: 12px; }
        
        .session-details { display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; }
        .detail-item { display: flex; gap: 8px; font-size: 13px; }
        .detail-label { color: #6b7280; min-width: 70px; }
        .detail-value { color: #1f2937; font-weight: 600; }
        
        .mode-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .mode-online { background: #dbeafe; color: #1e40af; }
        .mode-in-person { background: #fef3c7; color: #92400e; }
        
        .seats-info { font-size: 13px; color: #6b7280; margin-bottom: 16px; }
        .seats-info strong { color: #1f2937; }
        
        .button-group { display: flex; gap: 12px; }
        .enroll-btn { flex: 1; padding: 10px 16px; background: #1e40af; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .enroll-btn:hover { background: #1e3a8a; }
        .enrolled-badge { flex: 1; padding: 10px 16px; background: #10b981; color: white; border-radius: 6px; font-weight: 600; text-align: center; }
        .view-btn { flex: 1; padding: 10px 16px; background: #f3f4f6; color: #1f2937; border: 1px solid #d1d5db; border-radius: 6px; font-weight: 600; cursor: pointer; text-decoration: none; text-align: center; }
        
        .no-sessions { text-align: center; color: #6b7280; padding: 40px 20px; }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="training-container">
    <div class="training-header">
        <a href="dashboard.php">← Back to Dashboard</a>
        <h1>📚 Training & Upskilling Courses</h1>
        <p>Enroll in professional development courses to boost your IT career</p>
    </div>

    <?php if ($success): ?>
        <div class="success-banner">✓ Successfully enrolled in training course! Check "My Booked Sessions" to view your enrollments.</div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error-banner">✗ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="filters">
        <button class="filter-btn active" onclick="filterSessions('all')">All Courses</button>
        <button class="filter-btn" onclick="filterSessions('online')">Online</button>
        <button class="filter-btn" onclick="filterSessions('in_person')">In-Person</button>
        <button class="filter-btn" onclick="filterSessions('enrolled')">My Enrollments</button>
    </div>

    <?php if (count($sessions) > 0): ?>
        <div class="sessions-grid" id="sessions-grid">
            <?php foreach ($sessions as $session): ?>
                <?php 
                    $isEnrolled = in_array($session['session_id'], $enrolledSessionIds);
                    $seatsRemaining = intval($session['seats_remaining'] ?? 0);
                    $sessionDate = new DateTime($session['session_date']);
                    $formattedDate = $sessionDate->format('M d, Y');
                    $formattedTime = $sessionDate->format('h:i A');
                ?>
                <div class="session-card <?= $isEnrolled ? 'enrolled' : '' ?>" data-mode="<?= htmlspecialchars($session['mode']) ?>" data-enrolled="<?= $isEnrolled ? '1' : '0' ?>">
                    <div class="session-title"><?= htmlspecialchars($session['title']) ?></div>
                    <div class="session-partner">by <?= htmlspecialchars($session['partner_name'] ?? 'SmartRecruit') ?></div>
                    
                    <div class="session-details">
                        <div class="detail-item">
                            <span class="detail-label">📅 Date:</span>
                            <span class="detail-value"><?= $formattedDate ?> @ <?= $formattedTime ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">📍 Mode:</span>
                            <span class="mode-badge mode-<?= htmlspecialchars($session['mode']) ?>"><?= ucfirst(str_replace('_', ' ', $session['mode'])) ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">💰 Price:</span>
                            <span class="detail-value">$<?= number_format($session['price'], 2) ?></span>
                        </div>
                    </div>
                    
                    <div class="seats-info">
                        <strong><?= $seatsRemaining ?></strong> seats available (<?= intval($session['seats_total']) ?> total)
                    </div>
                    
                    <div class="button-group">
                        <?php if ($isEnrolled): ?>
                            <div class="enrolled-badge">✓ Enrolled</div>
                        <?php else: ?>
                            <form method="POST" style="flex: 1;">
                                <input type="hidden" name="session_id" value="<?= $session['session_id'] ?>">
                                <button type="submit" name="enroll" class="enroll-btn" <?= $seatsRemaining <= 0 ? 'disabled' : '' ?>>
                                    <?= $seatsRemaining <= 0 ? 'No Seats Available' : 'Enroll Now' ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-sessions">
            <p>No training courses available at the moment.</p>
            <p style="margin-top: 12px;"><a href="dashboard.php" style="color: #1e40af;">← Return to Dashboard</a></p>
        </div>
    <?php endif; ?>
</div>

<script>
function filterSessions(mode) {
    const grid = document.getElementById('sessions-grid');
    const cards = grid.querySelectorAll('.session-card');
    const buttons = document.querySelectorAll('.filter-btn');
    
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    cards.forEach(card => {
        if (mode === 'all') {
            card.style.display = 'block';
        } else if (mode === 'enrolled') {
            card.style.display = card.dataset.enrolled === '1' ? 'block' : 'none';
        } else {
            card.style.display = card.dataset.mode === mode ? 'block' : 'none';
        }
    });
}
</script>

</body>
</html>
