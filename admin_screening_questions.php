<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$jobId = intval($_GET['job_id'] ?? 0);

// Get job details
$stmt = $pdo->prepare('SELECT * FROM jobs WHERE job_id = ?');
$stmt->execute([$jobId]);
$job = $stmt->fetch();

if (!$job) { header('Location: dashboard.php'); exit; }

// Handle adding new question
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $questionText = trim($_POST['question_text'] ?? '');
    $questionType = $_POST['question_type'] ?? 'text';
    $required = isset($_POST['required']) ? 1 : 0;
    
    if (!empty($questionText)) {
        $stmt = $pdo->prepare('INSERT INTO job_screening_questions (job_id, question_text, question_type, required) VALUES (?, ?, ?, ?)');
        $stmt->execute([$jobId, $questionText, $questionType, $required]);
        $success = "Question added successfully!";
    }
}

// Get existing questions
$stmt = $pdo->prepare('SELECT * FROM job_screening_questions WHERE job_id = ? ORDER BY question_id DESC');
$stmt->execute([$jobId]);
$questions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manage Screening Questions — SmartRecruit</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial; background: #f8fafc; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { margin-bottom: 24px; }
        .header h1 { font-size: 20px; font-weight: 800; margin-bottom: 4px; }
        .header p { color: #666; font-size: 13px; }
        .card { background: white; padding: 20px; border: 1px solid #e5e7eb; margin-bottom: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #d1d5db; font-family: inherit; font-size: 13px; }
        .form-group textarea { resize: vertical; min-height: 60px; }
        .checkbox { display: flex; gap: 8px; align-items: center; }
        .checkbox input { width: auto; }
        .btn { padding: 10px 16px; border: none; font-weight: 600; cursor: pointer; font-size: 13px; }
        .btn-primary { background: #1e40af; color: white; }
        .btn-danger { background: #ef4444; color: white; font-size: 11px; padding: 6px 10px; }
        .success { background: #dcfce7; color: #047857; padding: 12px; border: 1px solid #6ee7b7; margin-bottom: 16px; }
        .question-item { background: #f9fafb; padding: 12px; border-left: 3px solid #1e40af; margin-bottom: 8px; }
        .question-item strong { display: block; margin-bottom: 4px; }
        .question-item .meta { font-size: 11px; color: #666; }
        .question-item .delete-btn { margin-top: 8px; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="header">
        <h1>Screening Questions for <?= htmlspecialchars($job['job_title']) ?></h1>
        <p><?= htmlspecialchars($job['company']) ?> — <?= htmlspecialchars($job['location']) ?></p>
    </div>

    <?php if (isset($success)): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- ADD NEW QUESTION -->
    <div class="card">
        <h2 style="font-size: 14px; font-weight: 800; margin-bottom: 16px;">Add Screening Question</h2>
        
        <form method="post">
            <div class="form-group">
                <label>Question Text *</label>
                <textarea name="question_text" placeholder="e.g., Do you have experience with this technology?" required></textarea>
            </div>

            <div class="form-group">
                <label>Question Type *</label>
                <select name="question_type" required>
                    <option value="text">Text Answer</option>
                    <option value="yes_no">Yes/No</option>
                    <option value="multiple_choice">Multiple Choice</option>
                </select>
            </div>

            <div class="form-group checkbox">
                <input type="checkbox" name="required" id="required" checked>
                <label for="required" style="margin: 0;">Required field</label>
            </div>

            <button type="submit" class="btn btn-primary">Add Question</button>
        </form>
    </div>

    <!-- EXISTING QUESTIONS -->
    <div class="card">
        <h2 style="font-size: 14px; font-weight: 800; margin-bottom: 16px;">Questions (<?= count($questions) ?>)</h2>
        
        <?php if (count($questions) > 0): ?>
            <?php foreach ($questions as $q): ?>
                <div class="question-item">
                    <strong><?= htmlspecialchars($q['question_text']) ?></strong>
                    <div class="meta">
                        Type: <strong><?= $q['question_type'] === 'yes_no' ? 'Yes/No' : ($q['question_type'] === 'text' ? 'Text' : 'Multiple Choice') ?></strong>
                        | Required: <strong><?= $q['required'] ? 'Yes' : 'No' ?></strong>
                    </div>
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="delete_question" value="<?= $q['question_id'] ?>">
                        <button type="submit" class="btn btn-danger delete-btn" onclick="return confirm('Delete this question?');">Delete</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #666; text-align: center; padding: 20px;">No questions added yet.</p>
        <?php endif; ?>
    </div>

    <a href="dashboard.php" style="color: #1e40af; text-decoration: none;">← Back to Dashboard</a>

</div>

</body>
</html>
