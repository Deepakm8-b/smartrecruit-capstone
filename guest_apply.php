<?php
/**
 * guest_apply.php — Quick Apply without an account (Cesar feedback)
 * Author: Deepak Bhandari (2443463047)
 *
 * Allows visitors to apply for a job with just their name, email,
 * and optional resume/cover letter — no registration required.
 * Encourages signup for full features (profile, match score, tracking).
 */
session_start();
require_once 'db.php';

$jobId = (int)($_GET['job_id'] ?? $_POST['job_id'] ?? 0);
$navEmail = '';
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT email FROM users WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $navEmail = $stmt->fetchColumn();
}

// Fetch the job
$stmt = $pdo->prepare("SELECT j.*, o.org_name, o.location FROM job_postings j JOIN organisations o ON o.org_id=j.org_id WHERE j.job_id=? AND j.status='open'");
$stmt->execute([$jobId]);
$job = $stmt->fetch();

if (!$job) {
    $_SESSION['flash'] = 'Job not found or no longer available.';
    header('Location: jobs.php');
    exit;
}

// If logged in as student, redirect to the proper apply flow
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student') {
    header('Location: dashboard.php');
    exit;
}

// Job skills
$skills = $pdo->prepare('SELECT s.skill_name FROM job_skills js JOIN skills s ON s.skill_id=js.skill_id WHERE js.job_id=?');
$skills->execute([$jobId]);
$jobSkills = $skills->fetchAll(PDO::FETCH_COLUMN);

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['full_name'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $phone  = trim($_POST['phone'] ?? '');
    $cover  = trim($_POST['cover_letter'] ?? '');
    $resume = trim($_POST['resume_text'] ?? '');

    if ($name === '') $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';

    // Check duplicate guest application
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT guest_app_id FROM guest_applications WHERE job_id=? AND email=?');
        $stmt->execute([$jobId, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'You have already applied for this job with this email.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO guest_applications (job_id, full_name, email, phone, cover_letter, resume_text) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$jobId, $name, $email, $phone, $cover, $resume]);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Apply — SmartRecruit</title>
    <link rel="stylesheet" href="app.css">
    <style>
        .apply-layout { display: grid; grid-template-columns: 1fr 340px; gap: 24px; margin-top: 10px; }
        .job-summary {
            background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow);
            padding: 22px; position: sticky; top: 20px; align-self: start;
        }
        .job-summary h3 { color: var(--navy); margin: 0 0 6px; }
        .job-summary .meta { color: var(--grey); font-size: 0.88rem; margin-bottom: 12px; }
        .skill-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
        .skill-tag { padding: 4px 12px; border-radius: 999px; font-size: 0.78rem; border: 1px solid var(--line); color: var(--grey); }
        .signup-nudge {
            background: linear-gradient(135deg, #1F3864, #2a4a80); color: #fff;
            border-radius: var(--radius); padding: 18px; margin-top: 16px; font-size: 0.88rem;
        }
        .signup-nudge a { color: #60A5FA; font-weight: 700; }
        textarea { display: block; width: 100%; margin-top: 6px; padding: 10px 12px; border: 1.5px solid var(--line); border-radius: 8px; font-size: 0.95rem; font-family: inherit; resize: vertical; }
        .success-box {
            background: var(--green-soft); border-radius: var(--radius); padding: 30px; text-align: center;
        }
        .success-box h2 { color: var(--green); margin: 0 0 10px; }
        @media (max-width: 800px) { .apply-layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<?php require 'nav.php'; ?>
<main class="app-main">
    <p><a href="jobs.php" style="color:var(--blue);text-decoration:none;">← Back to Job Listings</a></p>

    <?php if ($success): ?>
        <div class="success-box">
            <h2>✅ Application submitted!</h2>
            <p>Your application for <strong><?= htmlspecialchars($job['title']) ?></strong> at <?= htmlspecialchars($job['org_name']) ?> has been received.</p>
            <p style="margin-top:16px;color:var(--grey);">Want to track your applications, get match scores, and unlock career roadmaps?</p>
            <a href="register.php" class="btn" style="margin-top:10px;">Create a free account →</a>
        </div>
    <?php else: ?>
        <h1>Quick Apply</h1>
        <p class="subtitle">Apply without creating an account. Just fill in your details below.</p>

        <div class="apply-layout">
            <div>
                <?php foreach ($errors as $e): ?>
                    <div style="background:#FBEAEA;color:#B00020;padding:10px 14px;border-radius:10px;margin-bottom:10px;"><?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>

                <form method="post" class="card">
                    <input type="hidden" name="job_id" value="<?= $jobId ?>">

                    <label class="field">Full name *
                        <input type="text" name="full_name" required placeholder="Your full name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                    </label>

                    <label class="field">Email address *
                        <input type="email" name="email" required placeholder="you@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </label>

                    <label class="field">Phone number
                        <input type="tel" name="phone" placeholder="+61 4XX XXX XXX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </label>

                    <label class="field">Cover letter
                        <textarea name="cover_letter" rows="5" placeholder="Tell the employer why you're a great fit for this role..."><?= htmlspecialchars($_POST['cover_letter'] ?? '') ?></textarea>
                    </label>

                    <label class="field">Resume / key experience
                        <textarea name="resume_text" rows="6" placeholder="Paste your resume summary, key skills, and relevant experience..."><?= htmlspecialchars($_POST['resume_text'] ?? '') ?></textarea>
                    </label>

                    <button class="btn" type="submit" style="width:100%;padding:14px;font-size:1rem;">Submit Application →</button>
                </form>
            </div>

            <div>
                <div class="job-summary">
                    <h3><?= htmlspecialchars($job['title']) ?></h3>
                    <div class="meta">
                        <?= htmlspecialchars($job['org_name']) ?>
                        <?php if ($job['location']): ?> · <?= htmlspecialchars($job['location']) ?><?php endif; ?>
                    </div>
                    <div class="meta">
                        <?= str_replace('_',' ',$job['job_type']) ?> · $<?= number_format($job['salary_min']) ?>–$<?= number_format($job['salary_max']) ?>
                    </div>
                    <div class="meta">Closes <?= htmlspecialchars($job['deadline']) ?></div>
                    <?php if ($job['description']): ?>
                        <p style="font-size:0.88rem;margin-top:10px;color:var(--ink);"><?= htmlspecialchars($job['description']) ?></p>
                    <?php endif; ?>
                    <?php if ($jobSkills): ?>
                        <div class="skill-tags">
                            <?php foreach ($jobSkills as $sk): ?>
                                <span class="skill-tag"><?= htmlspecialchars($sk) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="signup-nudge">
                    <strong>💡 Create a free account to:</strong><br>
                    · See your AI match score for every job<br>
                    · Track all your applications<br>
                    · Build a skills profile<br>
                    · Get a personalised career roadmap (Premium)<br><br>
                    <a href="register.php">Sign up free →</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
