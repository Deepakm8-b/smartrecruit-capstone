<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if ($_SESSION['role'] !== 'student') { header('Location: dashboard.php'); exit; }

$userId  = $_SESSION['user_id'];
$student = getStudentByUserId($pdo, $userId);
$saved   = false;
$error   = '';

$stmt = $pdo->prepare('SELECT email FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$navEmail = $stmt->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $uni      = trim($_POST['university'] ?? '');
    $degree   = trim($_POST['degree'] ?? '');
    $gpa      = $_POST['gpa'] !== '' ? (float)$_POST['gpa'] : null;
    $interest = trim($_POST['career_interest'] ?? '');
    $bio      = trim($_POST['professional_summary'] ?? '');
    $skillIds = $_POST['skills'] ?? [];
    $resume   = $student['resume'] ?? null;

    // Handle resume upload
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['resume'];
        $allowed = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowed)) {
            $error = 'Only PDF and DOCX files allowed';
        } elseif ($file['size'] > $maxSize) {
            $error = 'File size must be under 5MB';
        } else {
            $uploadDir = '/Applications/XAMPP/xamppfiles/htdocs/smartrecruit/uploads/resumes/';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
            
            $fileName = 'resume_' . $userId . '_' . time() . '.' . ($file['type'] === 'application/pdf' ? 'pdf' : 'docx');
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $resume = $fileName;
            } else {
                $error = 'Failed to upload file';
            }
        }
    }

    if (!$error) {
        $pdo->beginTransaction();
        try {
            if ($student) {
                $stmt = $pdo->prepare(
                    'UPDATE students SET full_name=?, phone=?, university=?, degree=?, gpa=?, career_interest=?, professional_summary=?, resume=? WHERE student_id=?'
                );
                $stmt->execute([$fullName, $phone, $uni, $degree, $gpa, $interest, $bio, $resume, $student['student_id']]);
                $studentId = $student['student_id'];
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO students (user_id, full_name, phone, university, degree, gpa, career_interest, professional_summary, resume) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([$userId, $fullName, $phone, $uni, $degree, $gpa, $interest, $bio, $resume]);
                $studentId = (int)$pdo->lastInsertId();
            }

            $pdo->prepare('DELETE FROM student_skills WHERE student_id = ?')->execute([$studentId]);
            $ins = $pdo->prepare('INSERT INTO student_skills (student_id, skill_id) VALUES (?, ?)');
            foreach ($skillIds as $sid) { $ins->execute([$studentId, (int)$sid]); }

            $pdo->commit();
            $saved = true;
            $student = getStudentByUserId($pdo, $userId);
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

$allSkills = [];
$stmt = $pdo->query('SELECT skill_id, skill_name FROM skills ORDER BY skill_name');
while ($row = $stmt->fetch()) { $allSkills[] = $row; }

$studentSkills = [];
if ($student) {
    $stmt = $pdo->prepare('SELECT skill_id FROM student_skills WHERE student_id = ?');
    $stmt->execute([$student['student_id']]);
    $studentSkills = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — SmartRecruit</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'nav.php'; ?>
<div class="layout">
    <?php include 'sidebar.php'; ?>
    <div class="main">
        <div class="page-header">
            <h1 class="page-title">My Profile</h1>
            <p class="page-sub">Update your professional details</p>
        </div>

        <?php if ($saved): ?>
            <div style="background: #d1fae5; color: #047857; padding: 12px; border-radius: 0; margin-bottom: 16px; border: 1px solid #6ee7b7;">
                ✓ Profile saved successfully
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 0; margin-bottom: 16px; border: 1px solid #fecaca;">
                ✗ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" style="background: white; padding: 24px; border: 1px solid #e2e8f0;">
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px;">Full Name</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($student['full_name'] ?? '') ?>" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 0;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px;">Phone Number</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($student['phone'] ?? '') ?>" placeholder="+61 412 345 678" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 0;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px;">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($navEmail ?? '') ?>" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 0;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px;">University</label>
                <input type="text" name="university" value="<?= htmlspecialchars($student['university'] ?? '') ?>" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 0;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px;">Degree</label>
                <input type="text" name="degree" value="<?= htmlspecialchars($student['degree'] ?? '') ?>" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 0;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px;">GPA</label>
                <input type="number" name="gpa" step="0.01" min="0" max="4" value="<?= htmlspecialchars($student['gpa'] ?? '') ?>" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 0;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px;">Career Interest</label>
                <input type="text" name="career_interest" value="<?= htmlspecialchars($student['career_interest'] ?? '') ?>" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 0;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px;">Professional Summary</label>
                <textarea name="professional_summary" rows="4" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 0;"><?= htmlspecialchars($student['professional_summary'] ?? '') ?></textarea>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px;">Resume (PDF or DOCX, max 5MB)</label>
                <input type="file" name="resume" accept=".pdf,.docx" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 0;">
                <?php if ($student['resume']): ?>
                    <p style="font-size: 12px; color: #047857; margin-top: 6px;">
                        ✓ Current: <?= htmlspecialchars($student['resume']) ?>
                    </p>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px;">Skills</label>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                    <?php foreach ($allSkills as $skill): ?>
                        <label style="display: flex; gap: 8px; align-items: center;">
                            <input type="checkbox" name="skills[]" value="<?= $skill['skill_id'] ?>" <?= in_array($skill['skill_id'], $studentSkills) ? 'checked' : '' ?>>
                            <span style="font-size: 13px;"><?= htmlspecialchars($skill['skill_name']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" style="background: #1e40af; color: white; padding: 11px 18px; border: none; border-radius: 0; font-weight: 600; cursor: pointer;">Save Profile</button>
        </form>
    </div>
</div>
</body>
</html>