<?php
session_start();
$loggedIn = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SmartRecruit - Smart Student Recruitment Platform</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <nav class="nav">
    <div class="nav-brand">Smart<span>Recruit</span></div>
    <div class="nav-links">
      <a href="index.php">Home</a>
      <a href="jobs.php">Jobs</a>
      <?php if ($loggedIn): ?>
        <a href="dashboard.php">Dashboard</a>
        <?php if ($role === 'student'): ?>
          <a href="profile.php">Profile</a>
          <a href="premium.php">Premium</a>
        <?php elseif ($role === 'organisation'): ?>
          <a href="org_profile.php">Company</a>
          <a href="post_job.php">Post Job</a>
          <a href="candidates.php">Candidates</a>
        <?php elseif ($role === 'admin'): ?>
          <a href="admin.php">Admin</a>
        <?php endif; ?>
        <a href="logout.php">Logout</a>
      <?php else: ?>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
      <?php endif; ?>
    </div>
  </nav>

  <section class="hero">
    <div class="hero-tag">🎓 Australia's Smart Graduate Recruitment Platform</div>
    <h1>Find Your First Job<br><span>Based on Your Skills</span></h1>
    <p>SmartRecruit matches university students to hiring organisations using skill-based matching, delivers career roadmaps, and connects graduates with industry referrals.</p>
    <div class="hero-btns">
      <a href="register.php" class="btn-hero-main">🚀 Get Started Free</a>
      <a href="jobs.php" class="btn-hero-sec">💼 Browse Jobs →</a>
    </div>
    <div class="hero-stats">
      <div class="hstat"><div class="hstat-n">4-in-1</div><div class="hstat-l">Job Board + Coaching<br>+ Referrals + Training</div></div>
      <div class="hstat"><div class="hstat-n">17</div><div class="hstat-l">Database tables<br>fully normalised</div></div>
      <div class="hstat"><div class="hstat-n">$49</div><div class="hstat-l">Per month<br>Premium access</div></div>
      <div class="hstat"><div class="hstat-n">10–20%</div><div class="hstat-l">Commission on<br>successful placement</div></div>
    </div>
  </section>

  <section class="section" style="background: white;">
    <div class="section-inner">
      <div class="section-label">Core Features</div>
      <h2 class="section-title">Everything a graduate needs to launch their career</h2>
      <p class="section-sub">SmartRecruit combines four services that currently exist in separate platforms.</p>
      <div class="features-grid">
        <div class="feat-card"><span class="feat-icon">🎯</span><h3>AI Skill Matching</h3><p>Match score calculated between your verified skill profile and every job's requirements. See your percentage fit before you apply.</p></div>
        <div class="feat-card green"><span class="feat-icon">🗺</span><h3>Career Roadmap</h3><p>Premium students receive a personalised career roadmap with required skills, certifications, portfolio projects, and interview preparation.</p></div>
        <div class="feat-card amber"><span class="feat-icon">🤝</span><h3>Recruiter Referrals</h3><p>SmartRecruit directly refers premium students to partner organisations. Organisation pays 10–20% commission on successful hire.</p></div>
        <div class="feat-card"><span class="feat-icon">📚</span><h3>Training Sessions</h3><p>Book industry training sessions from partner providers. Earn digital certificates. Sessions cover IT, cloud, and career development.</p></div>
        <div class="feat-card green"><span class="feat-icon">🔒</span><h3>Secure by Design</h3><p>BCrypt password hashing, PDO prepared statements, session regeneration. PayPal handles all payments — no card details stored.</p></div>
        <div class="feat-card amber"><span class="feat-icon">📊</span><h3>Skill Gap Analysis</h3><p>See exactly which skills you are missing for your target job. The system compares your profile against job requirements and market demand.</p></div>
      </div>
    </div>
  </section>

  <section class="how-section">
    <div class="section-label" style="color:#60A5FA">How It Works</div>
    <h2 class="section-title" style="color:white;margin-bottom:10px">From registration to your first job in 4 steps</h2>
    <p class="section-sub">Simple for students. Powerful for organisations.</p>
    <div class="steps-grid">
      <div class="how-step"><div class="how-num">1</div><h4>Browse Jobs Freely</h4><p>View all open positions, companies, salary ranges, and required skills — no account needed.</p></div>
      <div class="how-step"><div class="how-num">2</div><h4>Register & Build Profile</h4><p>Create your account, add skills and career interests. The system calculates your match score against every job.</p></div>
      <div class="how-step"><div class="how-num">3</div><h4>Apply & Go Premium</h4><p>Apply to matched jobs. Subscribe at $49/month to unlock career roadmap, coaching, training, and referrals.</p></div>
      <div class="how-step"><div class="how-num">4</div><h4>Get Hired and Grow</h4><p>Get referred to partner organisations, attend training, earn certificates, and launch your career.</p></div>
    </div>
  </section>

  <section class="section" style="background:#F8FAFF">
    <div class="section-inner">
      <div class="section-label">Revenue Model</div>
      <h2 class="section-title">Two revenue streams. One platform.</h2>
      <p class="section-sub" style="margin-bottom:32px">Free for students to start. Premium for career acceleration. Organisations pay only on successful hire.</p>
      <div class="rev-grid">
        <div class="rev-card blue">
          <div class="rev-badge b">⭐ Student Premium</div>
          <h3>Premium Subscription</h3>
          <div class="rev-price b">$49<span>/month</span></div>
          <p>Unlock your full career potential. Cancel anytime. Processed securely via PayPal.</p>
          <ul class="rev-list">
            <li><span class="tick">✓</span> AI Career Roadmap with certifications</li>
            <li><span class="tick">✓</span> Skill Gap Analysis</li>
            <li><span class="tick">✓</span> One-on-One Coaching</li>
            <li><span class="tick">✓</span> Direct Recruiter Referrals</li>
            <li><span class="tick">✓</span> Training Sessions & Certificates</li>
            <li><span class="tick">✓</span> Portfolio Project Guidance</li>
          </ul>
          <a href="premium.php" class="btn btn-blue btn-full">Start Premium →</a>
        </div>
        <div class="rev-card green">
          <div class="rev-badge g">🏢 Organisation</div>
          <h3>Placement Commission</h3>
          <div class="rev-price g">10–20%<span> of first-year salary</span></div>
          <p>Only pay when you successfully hire a SmartRecruit-referred candidate. No upfront fees.</p>
          <ul class="rev-list">
            <li><span class="tick">✓</span> Post unlimited job listings</li>
            <li><span class="tick">✓</span> AI-ranked candidate list</li>
            <li><span class="tick">✓</span> Verified skills profiles</li>
            <li><span class="tick">✓</span> Pay only on successful hire</li>
            <li><span class="tick">✓</span> Invoice via PayPal (30 days)</li>
            <li><span class="tick">✓</span> Example: $70,000 × 15% = $10,500</li>
          </ul>
          <a href="register.php" class="btn btn-green btn-full">Register Organisation →</a>
        </div>
      </div>
    </div>
  </section>

  <section class="cta-section">
    <h2>Ready to find your first job smarter?</h2>
    <p>Join SmartRecruit today — free to start. Premium when you are ready to accelerate.</p>
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
      <a href="register.php" class="btn-hero-main">🎓 Student — Get Started Free</a>
      <a href="register.php" class="btn-hero-sec">🏢 Organisation — Post a Job</a>
    </div>
  </section>

  <footer>
    <p><strong>SmartRecruit</strong> — Smart Student–Organisation Recruitment Platform<br>
    Built with PHP · MySQL · HTML · CSS · JavaScript · PayPal API<br>
    <strong>ICT307B Capstone Project B</strong> · Excelsia University College, Sydney · 2026<br>
    Author: <strong>Deepak Bhandari</strong> · Student ID: 2443463047 · Supervisor: Dr Cesar Sanin<br><br>
    <a href="terms.html" style="color:#9FB6DE;">Terms of Service</a> · <a href="agreement.html" style="color:#9FB6DE;">User Agreement</a> · Governing law: NSW, Australia</p>
  </footer>
</body>
</html>
