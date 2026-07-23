<?php
/**
 * apply.php — SmartRecruit job application (T07)
 * Author: Deepak Bhandari (2443463047)
 *
 * POST job_id → validates, computes the live match score, inserts
 * the application. Duplicates are blocked twice: an explicit check
 * here, and the UNIQUE (student_id, job_id) key in the database —
 * defence in depth.
 */

session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['role'] !== 'student' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$jobId   = (int)($_POST['job_id'] ?? 0);
$student = getStudentByUserId($pdo, $_SESSION['user_id']);

// Must have a profile first — the match score needs skills to compare
if (!$student) {
    $_SESSION['flash'] = 'Please complete your profile before applying.';
    header('Location: profile.php');
    exit;
}

// Job must exist and be open
$stmt = $pdo->prepare("SELECT job_id FROM job_postings WHERE job_id = ? AND status = 'open'");
$stmt->execute([$jobId]);
if (!$stmt->fetch()) {
    $_SESSION['flash'] = 'That job is not available.';
    header('Location: dashboard.php');
    exit;
}

// Duplicate check (the DB UNIQUE key is the backstop)
$stmt = $pdo->prepare('SELECT app_id FROM applications WHERE student_id = ? AND job_id = ?');
$stmt->execute([$student['student_id'], $jobId]);
if ($stmt->fetch()) {
    $_SESSION['flash'] = 'You have already applied for this job.';
    header('Location: dashboard.php');
    exit;
}

// Live match score at the moment of application (FR03)
$score = calculateMatchScore($pdo, (int)$student['student_id'], $jobId);

$stmt = $pdo->prepare(
    'INSERT INTO applications (student_id, job_id, match_score) VALUES (?, ?, ?)'
);
$stmt->execute([$student['student_id'], $jobId, $score]);

$_SESSION['flash'] = "Application submitted — your match score is {$score}%.";
header('Location: dashboard.php');
exit;
