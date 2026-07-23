<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'organisation') {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT org_id FROM organisations WHERE user_id = ?');
$stmt->execute([$userId]);
$org = $stmt->fetch();
$orgId = $org['org_id'];

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jobTitle = $_POST['job_title'] ?? '';
    $company = $_POST['company'] ?? '';
    $location = $_POST['location'] ?? '';
    $jobType = $_POST['job_type'] ?? '';
    $salaryRange = $_POST['salary_range'] ?? '';
    $description = $_POST['description'] ?? '';
    $requiredSkills = $_POST['required_skills'] ?? '';
    $closesDate = $_POST['closes_date'] ?? '';

    if ($jobTitle && $company && $location && $jobType && $salaryRange && $description) {
        $stmt = $pdo->prepare('INSERT INTO jobs (organisation_id, job_title, company, location, job_type, salary_range, description, required_skills, closes_date, created_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$orgId, $jobTitle, $company, $location, $jobType, $salaryRange, $description, $requiredSkills, $closesDate]);
        header('Location: job_postings_recruiter.php');
        exit;
    } else {
        $error = 'Please fill all required fields';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Post Job - SmartRecruit</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI'; background: #f5f7fa; }
.navbar { background: white; padding: 16px 24px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; }
.navbar h1 { font-size: 22px; font-weight: 700; }
.navbar a { color: #1e40af; text-decoration: none; font-weight: 600; }
.container { max-width: 800px; margin: 0 auto; padding: 24px 20px; }
.back-link { margin-bottom: 20px; }
.back-link a { color: #1e40af; text-decoration: none; font-weight: 600; }
h2 { font-size: 28px; font-weight: 700; margin-bottom: 24px; }
.form-section { background: white; padding: 24px; border-radius: 8px; border: 1px solid #e5e7eb; }
.form-group { margin-bottom: 20px; }
label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; }
input, textarea, select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; font-family: inherit; }
textarea { min-height: 120px; }
input:focus, textarea:focus, select:focus { border-color: #1e40af; outline: none; }
button { width: 100%; padding: 12px; background: #1e40af; color: white; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 14px; }
button:hover { background: #1e3a8a; }
.error { color: #dc2626; background: #fee2e2; padding: 12px; border-radius: 6px; margin-bottom: 20px; }
.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
</style>
</head>
<body>

<div class="navbar">
    <h1>Smart<span style="color: #1e40af;">Recruit</span></h1>
    <a href="logout.php">Logout</a>
</div>

<div class="container">
    <div class="back-link">
        <a href="job_postings_recruiter.php">← Back to Job Postings</a>
    </div>

    <h2>💼 Post a New Job</h2>

    <div class="form-section">
        <?php if ($error): echo "<div class='error'>$error</div>"; endif; ?>

        <form method="post">
            <div class="grid">
                <div class="form-group">
                    <label>Job Title *</label>
                    <input type="text" name="job_title" required>
                </div>
                <div class="form-group">
                    <label>Company *</label>
                    <input type="text" name="company" required>
                </div>
            </div>

            <div class="grid">
                <div class="form-group">
                    <label>Location *</label>
                    <input type="text" name="location" placeholder="e.g. Sydney, NSW" required>
                </div>
                <div class="form-group">
                    <label>Job Type *</label>
                    <select name="job_type" required>
                        <option value="">Select job type</option>
                        <option value="Full-time">Full-time</option>
                        <option value="Part-time">Part-time</option>
                        <option value="Internship">Internship</option>
                        <option value="Contract">Contract</option>
                    </select>
                </div>
            </div>

            <div class="grid">
                <div class="form-group">
                    <label>Salary Range *</label>
                    <input type="text" name="salary_range" placeholder="e.g. $60,000 - $80,000" required>
                </div>
                <div class="form-group">
                    <label>Closing Date</label>
                    <input type="date" name="closes_date">
                </div>
            </div>

            <div class="form-group">
                <label>Job Description *</label>
                <textarea name="description" required placeholder="Describe the role, responsibilities, and requirements..."></textarea>
            </div>

            <div class="form-group">
                <label>Required Skills</label>
                <input type="text" name="required_skills" placeholder="e.g. Python, MySQL, Linux">
            </div>

            <button type="submit">Post Job →</button>
        </form>
    </div>
</div>

</body>
</html>
