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

$stmt = $pdo->prepare('
    SELECT 
        scp.proof_id,
        scp.file_name,
        scp.file_type,
        scp.uploaded_date,
        scp.status,
        rs.step_title,
        rs.step_number
    FROM step_completion_proofs scp
    JOIN roadmap_steps rs ON scp.step_id = rs.step_id
    WHERE scp.student_id = ?
    ORDER BY scp.uploaded_date DESC
');
$stmt->execute([$studentId]);
$proofs = $stmt->fetchAll();

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
    <title>My Proofs — SmartRecruit</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .proofs-container { max-width: 1000px; margin: 0 auto; padding: 40px 20px; }
        .proofs-header { margin-bottom: 40px; }
        .proofs-header a { color: #1e40af; text-decoration: none; font-weight: 600; font-size: 14px; }
        .proofs-header h1 { font-size: 28px; font-weight: 700; margin: 16px 0 8px 0; }
        .proofs-header p { color: #6b7280; margin: 0; }
        
        .proofs-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .proofs-table th { background: #f3f4f6; padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; font-size: 13px; }
        .proofs-table td { padding: 12px 16px; border-top: 1px solid #e5e7eb; }
        .proofs-table tr:hover { background: #f9fafb; }
        
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #d1fae5; color: #047857; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        
        .download-btn { background: #1e40af; color: white; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-block; }
        .download-btn:hover { background: #1e3a8a; }
        
        .no-proofs { text-align: center; padding: 40px 20px; background: white; border-radius: 8px; color: #6b7280; }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="proofs-container">
    <div class="proofs-header">
        <a href="view_roadmap.php">← Back to Roadmap</a>
        <h1>📄 My Uploaded Proofs</h1>
        <p>View and download all your submitted completion proofs</p>
    </div>

    <?php if (count($proofs) > 0): ?>
        <table class="proofs-table">
            <thead>
                <tr>
                    <th>Step</th>
                    <th>File</th>
                    <th>Uploaded</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($proofs as $proof): ?>
                    <tr>
                        <td><?= $proof['step_number'] ?>. <?= htmlspecialchars($proof['step_title']) ?></td>
                        <td><?= htmlspecialchars($proof['file_name']) ?></td>
                        <td><?= date('M d, Y H:i', strtotime($proof['uploaded_date'])) ?></td>
                        <td>
                            <span class="status-badge status-<?= $proof['status'] ?>">
                                <?php if ($proof['status'] === 'pending'): ?>
                                    ⏳ Pending Review
                                <?php elseif ($proof['status'] === 'approved'): ?>
                                    ✓ Approved
                                <?php elseif ($proof['status'] === 'rejected'): ?>
                                    ✕ Rejected
                                <?php endif; ?>
                            </span>
                        </td>
                        <td>
                            <a href="download_proof.php?proof_id=<?= $proof['proof_id'] ?>" class="download-btn">Download</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-proofs">
            <p style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 8px;">No Proofs Uploaded</p>
            <p>You haven't uploaded any completion proofs yet.</p>
            <p style="margin-top: 16px;"><a href="view_roadmap.php" style="color: #1e40af;">Go to Roadmap →</a></p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
