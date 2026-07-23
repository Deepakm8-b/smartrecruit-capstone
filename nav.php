<?php
/**
 * nav.php — SmartRecruit shared navigation (Design Pass v4)
 * Home link always visible. Jobs public. Auth features when logged in.
 */
$navRole  = $_SESSION['role'] ?? '';
$navEmail = $navEmail ?? ($_SESSION['nav_email'] ?? '');
?>
<nav class="app-nav">
    <a class="brand" href="index.php">Smart<span>Recruit</span></a>
    <div class="links">
        <a href="index.php">Home</a>
        <a href="jobs.php">Jobs</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="dashboard.php">Dashboard</a>
            <?php if ($navRole === 'student'): ?>
                <a href="profile.php">Profile</a>
                <a href="view_roadmap.php" style="color: #34D399; font-weight: 600;">🎯 Roadmap</a>
                <a href="my_booked_sessions.php">📅 My Sessions</a>
                <a href="training.php">Training</a>
                <a href="premium.php">Premium</a>
                <a href="ask_expert.php" style="color: #FCD34D; font-weight: 600;">💬 Ask Expert</a>
            <?php elseif ($navRole === 'organisation'): ?>
                <a href="org_profile.php">Company</a>
                <a href="post_job.php">Post Job</a>
                <a href="candidates.php">Candidates</a>
            <?php elseif ($navRole === 'admin'): ?>
                <a href="admin.php">Admin Panel</a>
            <?php endif; ?>
            <a href="terms.html">Terms</a>
            <span class="user"><?= htmlspecialchars($navEmail) ?> (<?= htmlspecialchars($navRole) ?>)</span>
            <a class="logout" href="logout.php">Log out</a>
        <?php else: ?>
            <a href="terms.html">Terms</a>
            <a class="logout" href="login.php">Log in</a>
        <?php endif; ?>
    </div>
</nav>
