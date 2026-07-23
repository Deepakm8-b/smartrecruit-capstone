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

// Respond to query
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'respond') {
    $queryId = (int)($_POST['query_id'] ?? 0);
    $expertResponse = trim($_POST['expert_response'] ?? '');

    if ($queryId <= 0 || empty($expertResponse)) {
        $error = 'Please provide a response';
    } else {
        try {
            $stmt = $pdo->prepare('UPDATE expert_queries SET expert_response = ?, status = ?, admin_id = ?, answered_at = NOW() WHERE query_id = ?');
            $stmt->execute([$expertResponse, 'answered', $adminId, $queryId]);
            $message = 'Response sent to student!';
        } catch (Exception $e) {
            $error = 'Failed to send: ' . $e->getMessage();
        }
    }
}

// Fetch all queries
$stmt = $pdo->prepare('
    SELECT eq.query_id, eq.query_title, eq.query_text, eq.expert_response, eq.follow_up_text, eq.status, eq.created_at, eq.answered_at, eq.follow_up_at, s.full_name, s.student_id
    FROM expert_queries eq
    JOIN students s ON eq.student_id = s.student_id
    ORDER BY FIELD(eq.status, "pending", "answered"), eq.created_at DESC
');
$stmt->execute([]);
$allQueries = $stmt->fetchAll();

$pendingQueries = array_filter($allQueries, fn($q) => $q['status'] === 'pending');
$answeredQueries = array_filter($allQueries, fn($q) => $q['status'] === 'answered');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expert Queries — SmartRecruit Admin</title>
    <link rel="stylesheet" href="app.css">
    <style>
        .conversation { background: #f0f9ff; border-left: 4px solid #0284c7; padding: 16px; border-radius: 6px; margin-bottom: 12px; }
        .message { padding: 12px; margin-bottom: 8px; border-radius: 4px; }
        .student-msg { background: #e0f2fe; border-left: 3px solid #0284c7; }
        .expert-msg { background: #d1fae5; border-left: 3px solid #10b981; }
        .timestamp { font-size: 12px; color: #6b7280; margin-top: 4px; }
    </style>
</head>
<body>
<?php require 'nav.php'; ?>

<main class="app-main">
    <h1>💬 Expert Queries</h1>

    <?php if ($message): ?>
        <div class="flash" style="background: #d1fae5; color: #047857;">✓ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="flash" style="background: #fee2e2; color: #991b1b;">✗ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Pending Queries -->
    <div class="card">
        <h2>⏳ Pending Queries (<?= count($pendingQueries) ?>)</h2>
        
        <?php if (count($pendingQueries) > 0): ?>
            <?php foreach ($pendingQueries as $q): ?>
                <div class="conversation">
                    <div style="font-weight: 600; margin-bottom: 12px;">
                        📋 <?= htmlspecialchars($q['query_title']) ?>
                        <span style="color: #0284c7; font-size: 14px; margin-left: 12px;">from <?= htmlspecialchars($q['full_name']) ?></span>
                    </div>

                    <!-- Student Message -->
                    <div class="message student-msg">
                        <strong>📝 <?= htmlspecialchars($q['full_name']) ?>:</strong>
                        <p style="margin: 8px 0 0 0;"><?= nl2br(htmlspecialchars($q['query_text'])) ?></p>
                        <div class="timestamp">📅 <?= date('M d, Y — H:i', strtotime($q['created_at'])) ?></div>
                    </div>

                    <!-- Response Form -->
                    <form method="post" style="margin-top: 12px;">
                        <input type="hidden" name="action" value="respond">
                        <input type="hidden" name="query_id" value="<?= $q['query_id'] ?>">
                        
                        <label class="field">Your Response *
                            <textarea name="expert_response" rows="4" required placeholder="Provide expert guidance and advice..."></textarea>
                        </label>

                        <button type="submit" class="btn">Send Response</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="muted">No pending queries.</p>
        <?php endif; ?>
    </div>

    <!-- Answered Queries -->
    <div class="card">
        <h2>✓ Answered Queries (<?= count($answeredQueries) ?>)</h2>
        
        <?php foreach (array_slice($answeredQueries, 0, 20) as $q): ?>
            <div class="conversation">
                <div style="font-weight: 600; margin-bottom: 12px;">
                    ✓ <?= htmlspecialchars($q['query_title']) ?>
                    <span style="color: #0284c7; font-size: 14px; margin-left: 12px;">from <?= htmlspecialchars($q['full_name']) ?></span>
                </div>

                <!-- Student Question -->
                <div class="message student-msg">
                    <strong>📝 <?= htmlspecialchars($q['full_name']) ?>:</strong>
                    <p style="margin: 8px 0 0 0;"><?= nl2br(htmlspecialchars($q['query_text'])) ?></p>
                    <div class="timestamp">📅 <?= date('M d, Y — H:i', strtotime($q['created_at'])) ?></div>
                </div>

                <!-- Expert Response -->
                <div class="message expert-msg">
                    <strong>✓ Expert:</strong>
                    <p style="margin: 8px 0 0 0;"><?= nl2br(htmlspecialchars($q['expert_response'])) ?></p>
                    <div class="timestamp">⏰ <?= date('M d, Y', strtotime($q['answered_at'])) ?></div>
                </div>

                <!-- Student Follow-up (if exists) -->
                <?php if ($q['follow_up_text']): ?>
                    <div class="message student-msg">
                        <strong>📝 <?= htmlspecialchars($q['full_name']) ?> (Follow-up):</strong>
                        <p style="margin: 8px 0 0 0;"><?= nl2br(htmlspecialchars($q['follow_up_text'])) ?></p>
                        <div class="timestamp">📅 <?= date('M d, Y — H:i', strtotime($q['follow_up_at'])) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</main>
</body>
</html>
