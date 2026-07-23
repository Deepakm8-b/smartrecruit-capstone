<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$approveError = '';
$approveSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $proofId = intval($_POST['proof_id'] ?? 0);
    $action = $_POST['action']; // 'approve' or 'reject'
    $adminNotes = $_POST['admin_notes'] ?? '';
    $userId = $_SESSION['user_id'];
    
    if ($proofId > 0 && in_array($action, ['approve', 'reject'])) {
        try {
            $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
            $stmt = $pdo->prepare('
                UPDATE step_completion_proofs 
                SET status = ?, admin_notes = ?, reviewed_by = ?, reviewed_date = NOW()
                WHERE proof_id = ?
            ');
            $stmt->execute([$newStatus, $adminNotes, $userId, $proofId]);
            $approveSuccess = true;
        } catch (Exception $e) {
            $approveError = 'Failed to update proof status.';
        }
    }
}

$stmt = $pdo->prepare('
    SELECT 
        scp.proof_id,
        scp.student_id,
        scp.file_name,
        scp.file_type,
        scp.uploaded_date,
        scp.status,
        scp.admin_notes,
        scp.reviewed_by,
        rs.step_title,
        rs.step_number,
        st.full_name,
        u.email
    FROM step_completion_proofs scp
    JOIN roadmap_steps rs ON scp.step_id = rs.step_id
    JOIN students st ON scp.student_id = st.student_id
    JOIN users u ON st.user_id = u.user_id
    WHERE scp.status = "pending"
    ORDER BY scp.uploaded_date ASC
');
$stmt->execute();
$pendingProofs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Proofs — SmartRecruit Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        .admin-header { margin-bottom: 40px; }
        .admin-header h1 { font-size: 28px; font-weight: 700; margin: 0 0 8px 0; }
        .admin-header p { color: #6b7280; margin: 0; }
        
        .success-banner { background: #d1fae5; border: 1px solid #6ee7b7; color: #047857; padding: 12px 16px; border-radius: 6px; margin-bottom: 24px; }
        .error-banner { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 24px; }
        
        .proof-card { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 16px; }
        .proof-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
        .proof-title { font-size: 16px; font-weight: 700; color: #1f2937; }
        .proof-student { font-size: 13px; color: #6b7280; margin-top: 4px; }
        .proof-meta { font-size: 12px; color: #9ca3af; margin-top: 8px; }
        
        .proof-actions { display: flex; gap: 12px; margin-top: 16px; }
        .btn-approve { background: #10b981; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .btn-approve:hover { background: #059669; }
        .btn-reject { background: #ef4444; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .btn-reject:hover { background: #dc2626; }
        
        .no-proofs { text-align: center; padding: 40px; background: white; border-radius: 8px; color: #6b7280; }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="admin-container">
    <div class="admin-header">
        <h1>📋 Review Student Proofs</h1>
        <p>Approve or reject completion proofs for roadmap steps</p>
    </div>

    <?php if ($approveSuccess): ?>
        <div class="success-banner">✓ Proof status updated successfully!</div>
    <?php endif; ?>

    <?php if ($approveError): ?>
        <div class="error-banner">✗ <?= htmlspecialchars($approveError) ?></div>
    <?php endif; ?>

    <?php if (count($pendingProofs) > 0): ?>
        <?php foreach ($pendingProofs as $proof): ?>
            <div class="proof-card">
                <div class="proof-header">
                    <div>
                        <div class="proof-title">Step <?= $proof['step_number'] ?>: <?= htmlspecialchars($proof['step_title']) ?></div>
                        <div class="proof-student">Student: <?= htmlspecialchars($proof['full_name']) ?> (<?= htmlspecialchars($proof['email']) ?>)</div>
                        <div class="proof-meta">📄 <?= htmlspecialchars($proof['file_name']) ?> | Uploaded: <?= date('M d, Y H:i', strtotime($proof['uploaded_date'])) ?></div>
                    </div>
                </div>
                
                <div style="background: #f9fafb; padding: 12px; border-radius: 4px; margin-bottom: 16px;">
                    <a href="download_proof.php?proof_id=<?= $proof['proof_id'] ?>" style="color: #1e40af; font-weight: 600; text-decoration: none;">📥 Download Proof</a>
                </div>

                <form method="POST" style="background: #f3f4f6; padding: 12px; border-radius: 4px; margin-bottom: 16px;">
                    <input type="hidden" name="proof_id" value="<?= $proof['proof_id'] ?>">
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; color: #374151;">Admin Notes (optional):</label>
                    <textarea name="admin_notes" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px; resize: none;" rows="3" placeholder="Add notes about this submission..."></textarea>
                </form>

                <div class="proof-actions">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="proof_id" value="<?= $proof['proof_id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="admin_notes" value="">
                        <button type="submit" class="btn-approve">✓ Approve</button>
                    </form>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="proof_id" value="<?= $proof['proof_id'] ?>">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="admin_notes" value="">
                        <button type="submit" class="btn-reject">✕ Reject</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-proofs">
            <p style="font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 8px;">✓ All Caught Up!</p>
            <p>No pending proofs to review.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
