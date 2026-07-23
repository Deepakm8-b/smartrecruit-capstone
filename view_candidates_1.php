<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'organisation') {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT org_id, org_name FROM organisations WHERE user_id = ?');
$stmt->execute([$userId]);
$org = $stmt->fetch();

if (!$org) {
    header('Location: org_profile.php');
    exit;
}

$orgId = $org['org_id'];

// Get filter parameters
$filterJob = $_GET['job'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$sortBy = $_GET['sort'] ?? 'applied_date';
$sortDir = $_GET['dir'] ?? 'DESC';

// Build query
$query = 'SELECT a.application_id, a.status, a.applied_date, 
                 s.student_id, s.full_name, s.university, s.degree, s.gpa,
                 j.job_id, j.job_title, j.company,
                 GROUP_CONCAT(sk.skill_name SEPARATOR ", ") as skills
          FROM applications a
          JOIN jobs j ON a.job_id = j.job_id
          JOIN students s ON a.student_id = s.student_id
          LEFT JOIN student_skills ss ON s.student_id = ss.student_id
          LEFT JOIN skills sk ON ss.skill_id = sk.skill_id
          WHERE j.organisation_id = ?';

$params = [$orgId];

if ($filterJob) {
    $query .= ' AND j.job_id = ?';
    $params[] = (int)$filterJob;
}

if ($filterStatus) {
    $query .= ' AND a.status = ?';
    $params[] = $filterStatus;
}

$query .= ' GROUP BY a.application_id';

// Validate sort parameters
$allowedSorts = ['applied_date', 'full_name', 'status'];
$allowedDirs = ['ASC', 'DESC'];
if (!in_array($sortBy, $allowedSorts)) $sortBy = 'applied_date';
if (!in_array($sortDir, $allowedDirs)) $sortDir = 'DESC';

$query .= ' ORDER BY ' . $sortBy . ' ' . $sortDir;

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$candidates = $stmt->fetchAll();

// Get all jobs for filtering
$stmt = $pdo->prepare('SELECT job_id, job_title FROM jobs WHERE organisation_id = ? ORDER BY job_title');
$stmt->execute([$orgId]);
$jobs = $stmt->fetchAll();

// Get status options
$statuses = ['Applied', 'Reviewing', 'Shortlisted', 'Rejected', 'Accepted'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidates — SmartRecruit</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: #f5f7fa;
            color: #1f2937;
        }
        .navbar {
            background: white;
            padding: 16px 24px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar h1 { font-size: 20px; font-weight: 700; }
        .navbar h1 span { color: #1e40af; }
        .navbar-right { display: flex; gap: 20px; }
        .navbar-right a { color: #1e40af; text-decoration: none; font-weight: 600; font-size: 14px; }
        .container { max-width: 1200px; margin: 0 auto; padding: 24px 20px; }
        .page-header { margin-bottom: 24px; }
        .page-header h2 { font-size: 24px; font-weight: 700; margin-bottom: 8px; }
        .page-header p { color: #6b7280; font-size: 14px; }
        .filters {
            background: white;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            border: 1px solid #e5e7eb;
        }
        .filter-group { display: flex; gap: 8px; align-items: center; }
        .filter-group label { font-weight: 600; font-size: 13px; color: #6b7280; }
        .filter-group select, .filter-group input {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 13px;
        }
        .filter-btn {
            padding: 8px 16px;
            background: #1e40af;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
        }
        .filter-btn:hover { background: #1e3a8a; }
        .clear-filters {
            padding: 8px 12px;
            background: #f3f4f6;
            color: #1f2937;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
        }
        .clear-filters:hover { background: #e5e7eb; }
        .candidates-table {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        table { width: 100%; border-collapse: collapse; }
        th {
            background: #f9fafb;
            padding: 12px 16px;
            text-align: left;
            font-weight: 700;
            font-size: 13px;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
        }
        th a { color: #1e40af; text-decoration: none; font-weight: 600; }
        th a:hover { text-decoration: underline; }
        td { padding: 16px; border-bottom: 1px solid #e5e7eb; }
        tr:hover { background: #f9fafb; }
        .candidate-name {
            font-weight: 600;
            color: #1e40af;
            text-decoration: none;
        }
        .candidate-name:hover { text-decoration: underline; }
        .candidate-details { font-size: 13px; color: #6b7280; }
        .status-badge {
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            display: inline-block;
        }
        .status-applied { background: #dbeafe; color: #1e40af; }
        .status-reviewing { background: #fef08a; color: #b45309; }
        .status-shortlisted { background: #dcfce7; color: #16a34a; }
        .status-rejected { background: #fee2e2; color: #dc2626; }
        .status-accepted { background: #d1d5db; color: #374151; }
        .action-btn {
            padding: 6px 12px;
            border: 1px solid #d1d5db;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            color: #1e40af;
            text-decoration: none;
        }
        .action-btn:hover { background: #f3f4f6; }
        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: #6b7280;
        }
        .empty-state-icon { font-size: 48px; margin-bottom: 12px; }
    </style>
</head>
<body>

<div class="navbar">
    <h1>Smart<span>Recruit</span></h1>
    <div class="navbar-right">
        <a href="recruiter_dashboard.php">← Back to Dashboard</a>
    </div>
</div>

<div class="container">
    <div class="page-header">
        <h2>Candidates</h2>
        <p>All applications received for your job postings</p>
    </div>

    <form method="get" class="filters">
        <div class="filter-group">
            <label>Job:</label>
            <select name="job">
                <option value="">All Jobs</option>
                <?php foreach ($jobs as $job): ?>
                    <option value="<?php echo $job['job_id']; ?>" <?php echo $filterJob == $job['job_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($job['job_title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label>Status:</label>
            <select name="status">
                <option value="">All Statuses</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?php echo $status; ?>" <?php echo $filterStatus == $status ? 'selected' : ''; ?>>
                        <?php echo $status; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label>Sort:</label>
            <select name="sort">
                <option value="applied_date" <?php echo $sortBy == 'applied_date' ? 'selected' : ''; ?>>Date Applied</option>
                <option value="full_name" <?php echo $sortBy == 'full_name' ? 'selected' : ''; ?>>Name</option>
                <option value="status" <?php echo $sortBy == 'status' ? 'selected' : ''; ?>>Status</option>
            </select>
            <select name="dir">
                <option value="DESC" <?php echo $sortDir == 'DESC' ? 'selected' : ''; ?>>Newest First</option>
                <option value="ASC" <?php echo $sortDir == 'ASC' ? 'selected' : ''; ?>>Oldest First</option>
            </select>
        </div>

        <button type="submit" class="filter-btn">Filter</button>
        <a href="view_candidates.php" class="clear-filters">Clear</a>
    </form>

    <?php if (count($candidates) > 0): ?>
        <div class="candidates-table">
            <table>
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Education</th>
                        <th>Applied For</th>
                        <th>Date Applied</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($candidates as $c): ?>
                        <tr>
                            <td>
                                <a href="candidate_profile.php?app_id=<?php echo $c['application_id']; ?>" class="candidate-name">
                                    <?php echo htmlspecialchars($c['full_name']); ?>
                                </a>
                            </td>
                            <td>
                                <div class="candidate-details">
                                    <?php echo htmlspecialchars($c['degree'] ?? 'N/A'); ?><br>
                                    <?php echo htmlspecialchars($c['university'] ?? 'N/A'); ?><br>
                                    GPA: <?php echo htmlspecialchars($c['gpa'] ?? 'N/A'); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($c['job_title']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($c['applied_date'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($c['status']); ?>">
                                    <?php echo htmlspecialchars($c['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="candidate_profile.php?app_id=<?php echo $c['application_id']; ?>" class="action-btn">
                                    View Profile →
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="candidates-table">
            <div class="empty-state">
                <div class="empty-state-icon">👥</div>
                <p>No candidates found</p>
                <p style="font-size: 13px; margin-top: 8px;">Post a job to start receiving applications</p>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
