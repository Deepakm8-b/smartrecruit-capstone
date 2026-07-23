<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

$cancelError = '';
$cancelSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $bookingId = intval($_POST['booking_id'] ?? 0);
    
    if ($bookingId > 0) {
        try {
            $stmt = $pdo->prepare('UPDATE bookings SET status = "cancelled" WHERE booking_id = ? AND student_id = ?');
            $stmt->execute([$bookingId, $studentId]);
            $cancelSuccess = true;
        } catch (Exception $e) {
            $cancelError = 'Failed to cancel booking. Please try again.';
        }
    }
}

$stmt = $pdo->prepare('
    SELECT 
        b.booking_id,
        b.status,
        b.attended,
        ts.session_id,
        ts.title,
        ts.partner_name,
        ts.mode,
        ts.session_date,
        ts.price,
        ts.seats_total,
        ts.seats_remaining
    FROM bookings b
    JOIN training_sessions ts ON b.session_id = ts.session_id
    WHERE b.student_id = ?
    ORDER BY ts.session_date DESC
');
$stmt->execute([$studentId]);
$bookings = $stmt->fetchAll();

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
    <title>My Booked Sessions — SmartRecruit</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .sessions-container { max-width: 1000px; margin: 0 auto; padding: 40px 20px; }
        .sessions-header { margin-bottom: 40px; }
        .sessions-header a { color: #1e40af; text-decoration: none; font-weight: 600; font-size: 14px; }
        .sessions-header h1 { font-size: 28px; font-weight: 700; margin: 16px 0 8px 0; }
        .sessions-header p { color: #6b7280; margin: 0; }
        
        .success-banner { background: #d1fae5; border: 1px solid #6ee7b7; color: #047857; padding: 12px 16px; border-radius: 6px; margin-bottom: 24px; }
        .error-banner { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 24px; }
        
        .filters { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
        .filter-btn { padding: 8px 16px; border: 1px solid #d1d5db; border-radius: 6px; background: white; cursor: pointer; font-size: 14px; font-weight: 600; }
        .filter-btn.active { background: #1e40af; color: white; border-color: #1e40af; }
        
        .sessions-list { display: flex; flex-direction: column; gap: 16px; }
        .session-item { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; display: flex; justify-content: space-between; align-items: flex-start; }
        .session-item.cancelled { opacity: 0.6; background: #f9fafb; }
        .session-item.attended { border-color: #10b981; background: #f0fdf4; }
        
        .session-info { flex: 1; }
        .session-title { font-size: 18px; font-weight: 700; color: #1f2937; margin-bottom: 8px; }
        .session-partner { font-size: 13px; color: #6b7280; margin-bottom: 12px; }
        
        .session-details { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 12px; }
        .detail { display: flex; gap: 8px; font-size: 13px; }
        .detail-label { color: #6b7280; min-width: 70px; }
        .detail-value { color: #1f2937; font-weight: 600; }
        
        .status-badges { display: flex; gap: 8px; margin-bottom: 12px; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .badge-enrolled { background: #dbeafe; color: #1e40af; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .badge-attended { background: #d1fae5; color: #047857; }
        .badge-online { background: #dbeafe; color: #1e40af; }
        .badge-in-person { background: #fef3c7; color: #92400e; }
        
        .session-actions { display: flex; gap: 12px; }
        .cancel-btn { padding: 10px 16px; background: #ef4444; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 13px; }
        .cancel-btn:hover { background: #dc2626; }
        .cancel-btn:disabled { background: #9ca3af; cursor: not-allowed; }
        
        .no-sessions { text-align: center; color: #6b7280; padding: 40px 20px; background: white; border-radius: 8px; border: 1px solid #e5e7eb; }
        .no-sessions-icon { font-size: 48px; margin-bottom: 16px; }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="sessions-container">
    <div class="sessions-header">
        <a href="dashboard.php">← Back to Dashboard</a>
        <h1>📅 My Booked Training Sessions</h1>
        <p>View and manage all your enrolled training courses</p>
    </div>

    <?php if ($cancelSuccess): ?>
        <div class="success-banner">✓ Booking cancelled successfully.</div>
    <?php endif; ?>

    <?php if ($cancelError): ?>
        <div class="error-banner">✗ <?= htmlspecialchars($cancelError) ?></div>
    <?php endif; ?>

    <?php if (count($bookings) > 0): ?>
        <div class="filters">
            <button class="filter-btn active" onclick="filterSessions('all')">All Sessions</button>
            <button class="filter-btn" onclick="filterSessions('enrolled')">Enrolled</button>
            <button class="filter-btn" onclick="filterSessions('attended')">Completed</button>
            <button class="filter-btn" onclick="filterSessions('cancelled')">Cancelled</button>
        </div>

        <div class="sessions-list">
            <?php foreach ($bookings as $booking): ?>
                <?php 
                    $sessionDate = new DateTime($booking['session_date']);
                    $formattedDate = $sessionDate->format('M d, Y');
                    $formattedTime = $sessionDate->format('h:i A');
                    $status = $booking['status'];
                    $attended = $booking['attended'];
                    $filterData = $attended ? 'attended' : ($status === 'cancelled' ? 'cancelled' : 'enrolled');
                ?>
                <div class="session-item <?= $attended ? 'attended' : ($status === 'cancelled' ? 'cancelled' : '') ?>" data-filter="<?= $filterData ?>">
                    <div class="session-info">
                        <div class="session-title"><?= htmlspecialchars($booking['title']) ?></div>
                        <div class="session-partner">by <?= htmlspecialchars($booking['partner_name'] ?? 'SmartRecruit') ?></div>
                        
                        <div class="session-details">
                            <div class="detail">
                                <span class="detail-label">📅 Session:</span>
                                <span class="detail-value"><?= $formattedDate ?> @ <?= $formattedTime ?></span>
                            </div>
                            
                            <div class="detail">
                                <span class="detail-label">💰 Price:</span>
                                <span class="detail-value">$<?= number_format($booking['price'], 2) ?></span>
                            </div>
                        </div>
                        
                        <div class="status-badges">
                            <?php if ($attended): ?>
                                <span class="badge badge-attended">✓ Completed</span>
                            <?php elseif ($status === 'cancelled'): ?>
                                <span class="badge badge-cancelled">✕ Cancelled</span>
                            <?php else: ?>
                                <span class="badge badge-enrolled">Enrolled</span>
                            <?php endif; ?>
                            <span class="badge badge-<?= htmlspecialchars($booking['mode']) ?>"><?= ucfirst(str_replace('_', ' ', $booking['mode'])) ?></span>
                        </div>
                    </div>
                    
                    <div class="session-actions">
                        <?php if ($status !== 'cancelled' && !$attended): ?>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                                <input type="hidden" name="booking_id" value="<?= $booking['booking_id'] ?>">
                                <button type="submit" name="cancel_booking" class="cancel-btn">Cancel Booking</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-sessions">
            <div class="no-sessions-icon">📚</div>
            <p style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 8px;">No Booked Sessions Yet</p>
            <p>You haven't enrolled in any training courses yet.</p>
            <p style="margin-top: 16px;"><a href="training.php" style="color: #1e40af; font-weight: 600;">Browse Available Courses →</a></p>
        </div>
    <?php endif; ?>
</div>

<script>
function filterSessions(filter) {
    const items = document.querySelectorAll('.session-item');
    const buttons = document.querySelectorAll('.filter-btn');
    
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    items.forEach(item => {
        if (filter === 'all') {
            item.style.display = 'flex';
        } else {
            item.style.display = item.dataset.filter === filter ? 'flex' : 'none';
        }
    });
}
</script>

</body>
</html>
