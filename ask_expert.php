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

$submitSuccess = '';
$submitError = '';

// Submit new query
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_query') {
    $queryTitle = trim($_POST['query_title'] ?? '');
    $queryText = trim($_POST['query_text'] ?? '');
    
    if (empty($queryTitle) || empty($queryText)) {
        $submitError = 'Please fill in both title and question.';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO expert_queries (student_id, query_title, query_text, status) VALUES (?, ?, ?, "pending")');
            $stmt->execute([$studentId, $queryTitle, $queryText]);
            $submitSuccess = 'Your question has been submitted! An expert will respond soon.';
        } catch (Exception $e) {
            $submitError = 'Failed to submit question.';
        }
    }
}

// Get all queries for this student
$stmt = $pdo->prepare('SELECT * FROM expert_queries WHERE student_id = ? ORDER BY created_at DESC');
$stmt->execute([$studentId]);
$queries = $stmt->fetchAll();

// Get user email
$stmt = $pdo->prepare('SELECT email FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$navEmail = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ask Expert — SmartRecruit</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .expert-container { max-width: 900px; margin: 0 auto; padding: 40px 20px; }
        .expert-header { margin-bottom: 40px; }
        .expert-header a { color: #1e40af; text-decoration: none; font-weight: 600; font-size: 14px; }
        .expert-header h1 { font-size: 28px; font-weight: 700; margin: 16px 0 8px 0; }
        .expert-header p { color: #6b7280; margin: 0; }
        
        .success-banner { background: #d1fae5; border: 1px solid #6ee7b7; color: #047857; padding: 12px 16px; border-radius: 6px; margin-bottom: 24px; }
        .error-banner { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 24px; }
        
        .query-form { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px; margin-bottom: 32px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; color: #1f2937; margin-bottom: 8px; font-size: 14px; }
        .form-group input,
        .form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; font-family: inherit; }
        .form-group textarea { resize: vertical; min-height: 120px; }
        .form-group input:focus,
        .form-group textarea:focus { outline: none; border-color: #1e40af; box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1); }
        
        .submit-btn { background: #1e40af; color: white; padding: 10px 24px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
        .submit-btn:hover { background: #1e3a8a; }
        
        .queries-section h2 { font-size: 20px; font-weight: 700; color: #1f2937; margin-bottom: 16px; }
        
        .query-card { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 16px; }
        .query-title { font-size: 16px; font-weight: 700; color: #1f2937; }
        .query-meta { font-size: 13px; color: #6b7280; margin: 8px 0; }
        .query-text { color: #374151; line-height: 1.6; margin: 12px 0; }
        
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-answered { background: #d1fae5; color: #047857; }
        .status-closed { background: #f3f4f6; color: #4b5563; }
        
        .expert-response { background: #f0fdf4; border-left: 4px solid #10b981; padding: 16px; margin-top: 16px; border-radius: 4px; }
        .expert-response-title { font-weight: 700; color: #047857; margin-bottom: 8px; }
        .expert-response-text { color: #374151; line-height: 1.6; }
        
        .no-queries { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 20px; text-align: center; color: #6b7280; }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="expert-container">
    <div class="expert-header">
        <a href="dashboard.php">← Back to Dashboard</a>
        <h1>💬 Ask Expert</h1>
        <p>Get answers to your career and technical questions from industry experts</p>
    </div>

    <?php if ($submitSuccess): ?>
        <div class="success-banner">✓ <?= htmlspecialchars($submitSuccess) ?></div>
    <?php endif; ?>

    <?php if ($submitError): ?>
        <div class="error-banner">✗ <?= htmlspecialchars($submitError) ?></div>
    <?php endif; ?>

    <div class="query-form">
        <h2 style="margin-top: 0; font-size: 18px; color: #1f2937;">Ask a Question</h2>
        <form method="POST">
            <input type="hidden" name="action" value="submit_query">
            
            <div class="form-group">
                <label>Question Title</label>
                <input type="text" name="query_title" placeholder="e.g., How do I prepare for my first IT Support interview?" required>
            </div>

            <div class="form-group">
                <label>Your Question</label>
                <textarea name="query_text" placeholder="Describe your question in detail. The more information you provide, the better the expert can help you." required></textarea>
            </div>

            <button type="submit" class="submit-btn">Submit Question</button>
        </form>
    </div>

    <div class="queries-section">
        <h2>Your Questions</h2>
        
        <?php if (count($queries) === 0): ?>
            <div class="no-queries">
                No questions yet. Ask your first question above!
            </div>
        <?php else: ?>
            <?php foreach ($queries as $query): ?>
                <div class="query-card">
                    <div style="display: flex; justify-content: space-between; align-items: start; gap: 16px;">
                        <div style="flex: 1;">
                            <div class="query-title"><?= htmlspecialchars($query['query_title']) ?></div>
                            <div class="query-meta">
                                <?= date('M d, Y H:i', strtotime($query['created_at'])) ?>
                                <span class="status-badge status-<?= htmlspecialchars($query['status']) ?>">
                                    <?= strtoupper($query['status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="query-text"><?= nl2br(htmlspecialchars($query['query_text'])) ?></div>
                    
                    <?php if (!empty($query['expert_response'])): ?>
                        <div class="expert-response">
                            <div class="expert-response-title">✓ Expert Response</div>
                            <div class="expert-response-text"><?= nl2br(htmlspecialchars($query['expert_response'])) ?></div>
                            <?php if (!empty($query['answered_at'])): ?>
                                <div style="font-size: 12px; color: #6b7280; margin-top: 8px;">
                                    Answered on <?= date('M d, Y H:i', strtotime($query['answered_at'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
