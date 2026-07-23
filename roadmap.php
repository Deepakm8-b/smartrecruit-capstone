<?php
/**
 * roadmap.php — Personalised Career Roadmap (FR04, FR07, FR09)
 * Author: Deepak Bhandari (2443463047)
 *
 * Premium-only. Assesses the student's career goals, current skills,
 * and experience, then generates a detailed step-by-step roadmap
 * tailored to their chosen career path in the current job market.
 *
 * Roadmap includes: required skills, certifications, portfolio projects,
 * practical tasks, learning resources, timelines, job strategies,
 * and market expectations — stored as JSON in recommendations table.
 */
session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if ($_SESSION['role'] !== 'student') { header('Location: dashboard.php'); exit; }

$userId = $_SESSION['user_id'];
$student = getStudentByUserId($pdo, $userId);
$stmt = $pdo->prepare('SELECT email FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$navEmail = $stmt->fetchColumn();

if (!$student || !$student['is_premium']) {
    $_SESSION['flash'] = 'Career Roadmap is a Premium feature. Upgrade to access it.';
    header('Location: premium.php'); exit;
}

// Student's current skills
$stmt = $pdo->prepare('SELECT s.skill_id, s.skill_name, s.category FROM student_skills ss JOIN skills s ON s.skill_id=ss.skill_id WHERE ss.student_id=?');
$stmt->execute([$student['student_id']]);
$mySkills = $stmt->fetchAll();
$mySkillNames = array_column($mySkills, 'skill_name');

// Market-demanded skills
$demandedSkills = $pdo->query("SELECT DISTINCT s.skill_id, s.skill_name, s.category, COUNT(js.job_id) AS job_count
    FROM job_skills js JOIN skills s ON s.skill_id=js.skill_id
    JOIN job_postings j ON j.job_id=js.job_id AND j.status='open'
    GROUP BY s.skill_id, s.skill_name, s.category ORDER BY job_count DESC")->fetchAll();
$mySkillIds = array_column($mySkills, 'skill_id');
$gaps = array_filter($demandedSkills, fn($s) => !in_array($s['skill_id'], $mySkillIds));
$matched = array_filter($demandedSkills, fn($s) => in_array($s['skill_id'], $mySkillIds));
$totalDemanded = count($demandedSkills);
$strength = $totalDemanded > 0 ? round(count($matched) / $totalDemanded * 100) : 0;

// Career path templates — personalised roadmaps per target role
$careerPath = strtolower($student['career_interest'] ?? 'it support');

$roadmaps = [
    'it support' => [
        'title' => 'IT Support / Helpdesk Analyst',
        'summary' => 'Entry-level IT support roles are in high demand across Australia. This roadmap will take you from graduate to a confident Level 1-2 support analyst within 6-12 months.',
        'salary_range' => '$55,000 – $75,000 AUD',
        'demand' => 'High — growing 12% annually in Australia (ABS 2025)',
        'timeline' => '3–6 months to job-ready, 6–12 months to confident L2',
        'skills' => [
            ['name' => 'Windows Administration', 'level' => 'Essential', 'desc' => 'Active Directory, Group Policy, user account management, Windows 10/11 troubleshooting'],
            ['name' => 'Networking', 'level' => 'Essential', 'desc' => 'TCP/IP, DNS, DHCP, subnetting, basic firewall rules, Wi-Fi troubleshooting'],
            ['name' => 'Customer Service', 'level' => 'Essential', 'desc' => 'Ticket management, SLA awareness, clear communication, de-escalation techniques'],
            ['name' => 'Troubleshooting', 'level' => 'Essential', 'desc' => 'Systematic problem-solving methodology, remote support tools, hardware diagnostics'],
            ['name' => 'Active Directory', 'level' => 'Important', 'desc' => 'User/group management, password resets, OU structure, basic GPO editing'],
            ['name' => 'ITIL Foundations', 'level' => 'Desirable', 'desc' => 'Incident, problem, and change management frameworks'],
            ['name' => 'Linux Basics', 'level' => 'Desirable', 'desc' => 'Command line navigation, file permissions, SSH, basic scripting'],
            ['name' => 'Cloud Fundamentals', 'level' => 'Emerging', 'desc' => 'Azure AD / Entra ID, Microsoft 365 admin, basic AWS concepts'],
        ],
        'certifications' => [
            ['name' => 'CompTIA A+ (220-1101 & 220-1102)', 'priority' => 'High', 'timeline' => 'Month 1–2', 'desc' => 'Industry-standard entry certification. Covers hardware, networking, mobile, troubleshooting, and security.', 'cost' => '~$550 AUD for both exams'],
            ['name' => 'CompTIA Network+ (N10-009)', 'priority' => 'Medium', 'timeline' => 'Month 3–4', 'desc' => 'Validates networking knowledge: infrastructure, security, troubleshooting.', 'cost' => '~$450 AUD'],
            ['name' => 'Microsoft 365 Certified: Fundamentals (MS-900)', 'priority' => 'Medium', 'timeline' => 'Month 2–3', 'desc' => 'Cloud and M365 basics. Free exam vouchers often available through Microsoft Learn.', 'cost' => 'Free–$100 AUD'],
            ['name' => 'ITIL 4 Foundation', 'priority' => 'Desirable', 'timeline' => 'Month 4–5', 'desc' => 'IT service management framework. Many employers prefer ITIL awareness.', 'cost' => '~$500 AUD'],
        ],
        'portfolio' => [
            ['project' => 'Home Lab Setup', 'desc' => 'Build a virtualised lab (VirtualBox/Hyper-V) with Windows Server, Active Directory, DNS, DHCP. Document the setup with screenshots and a written guide.'],
            ['project' => 'Troubleshooting Case Studies', 'desc' => 'Document 5–10 real or simulated troubleshooting scenarios using the CompTIA methodology: identify, theory, test, plan, verify, document.'],
            ['project' => 'Networking Diagram', 'desc' => 'Design a small business network (draw.io) showing VLANs, firewall, switches, Wi-Fi, server room, and IP addressing scheme.'],
            ['project' => 'Ticketing System Demo', 'desc' => 'Set up a free ticketing system (osTicket or Spiceworks) and create sample tickets showing SLA tracking, escalation, and resolution.'],
        ],
        'practical_tasks' => [
            'Set up Windows Server 2022 in a VM with Active Directory Domain Services',
            'Create 10 user accounts, organise into OUs, apply a Group Policy (password policy)',
            'Configure DNS and DHCP on the server, join a client PC to the domain',
            'Practice subnetting: calculate 10 subnet problems from scratch (no calculator)',
            'Set up a shared folder with NTFS permissions for different user groups',
            'Install and configure a network printer, troubleshoot a simulated paper jam',
            'Use Wireshark to capture and analyse HTTP traffic on your home network',
            'Write a PowerShell script that exports all AD users to a CSV file',
            'Install Linux (Ubuntu Server) in a VM, practise SSH, file permissions, cron jobs',
            'Create a Microsoft 365 trial tenant, add users, configure MFA',
        ],
        'job_strategy' => [
            'target_sites' => 'Seek, Indeed, Hays, Robert Half, LinkedIn',
            'keywords' => 'IT Support, Helpdesk, Desktop Support, Level 1, Service Desk Analyst',
            'resume_tips' => 'Lead with certifications + home lab experience. Quantify: "resolved 50+ simulated tickets." Include a skills matrix showing proficiency levels.',
            'interview_prep' => [
                'Explain the OSI model and give an example of troubleshooting at each layer',
                'Walk me through how you would troubleshoot a user who cannot access a shared drive',
                'What is the difference between TCP and UDP? Give examples of each',
                'How would you handle a frustrated user whose issue you cannot immediately resolve?',
                'Describe your experience with Active Directory and Group Policy',
            ],
            'market_notes' => 'Perth and Sydney have the strongest demand for IT support. Contract roles (3-6 months) are excellent for first experience. Many permanent roles require Australian citizenship or PR — check visa requirements early.',
        ],
    ],
];

// Find best matching template
$roadmap = $roadmaps['it support']; // default
foreach ($roadmaps as $key => $r) {
    if (stripos($careerPath, $key) !== false) { $roadmap = $r; break; }
}

// Check which roadmap skills student already has
$hasSkill = fn($name) => in_array($name, $mySkillNames);

// Save to recommendations table as JSON (FR04)
$fullRoadmapJson = json_encode([
    'target_role' => $roadmap['title'],
    'student_profile' => [
        'name' => $student['full_name'],
        'degree' => $student['degree'],
        'university' => $student['university'],
        'gpa' => $student['gpa'],
        'skills' => $mySkillNames,
        'career_interest' => $student['career_interest'],
    ],
    'roadmap' => $roadmap,
    'skill_gaps' => array_column($gaps, 'skill_name'),
    'profile_strength' => $strength,
    'generated_at' => date('Y-m-d H:i:s'),
], JSON_PRETTY_PRINT);

$stmt = $pdo->prepare('SELECT rec_id FROM recommendations WHERE student_id = ?');
$stmt->execute([$student['student_id']]);
if ($stmt->fetch()) {
    $pdo->prepare('UPDATE recommendations SET career_roadmap=?, skill_gaps=?, completion_pct=?, target_role=? WHERE student_id=?')
        ->execute([$fullRoadmapJson, json_encode(array_column($gaps, 'skill_name')), $strength, $roadmap['title'], $student['student_id']]);
} else {
    $pdo->prepare('INSERT INTO recommendations (student_id, target_role, career_roadmap, skill_gaps, completion_pct) VALUES (?,?,?,?,?)')
        ->execute([$student['student_id'], $roadmap['title'], $fullRoadmapJson, json_encode(array_column($gaps, 'skill_name')), $strength]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Roadmap — SmartRecruit</title>
    <link rel="stylesheet" href="app.css">
    <style>
        .roadmap-hero {
            background: linear-gradient(135deg, #1F3864, #2a4a80);
            color: #fff; border-radius: var(--radius); padding: 30px 34px;
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;
        }
        .roadmap-hero h1 { margin: 0; font-size: 1.6rem; }
        .roadmap-hero .sub { color: #C8D6EC; margin-top: 6px; }
        .strength-ring {
            width: 110px; height: 110px; border-radius: 50%;
            background: conic-gradient(#4ADE80 <?= $strength ?>%, rgba(255,255,255,0.15) <?= $strength ?>%);
            display: flex; align-items: center; justify-content: center;
        }
        .strength-inner {
            width: 82px; height: 82px; border-radius: 50%; background: #1F3864;
            display: flex; align-items: center; justify-content: center; flex-direction: column;
        }
        .strength-inner .pct { font-size: 1.6rem; font-weight: 800; }
        .strength-inner .lbl { font-size: 0.65rem; color: #C8D6EC; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin: 20px 0; }
        .info-box { background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow); padding: 16px; }
        .info-box .label { font-size: 0.78rem; color: var(--grey); text-transform: uppercase; font-weight: 600; }
        .info-box .value { font-size: 1.05rem; font-weight: 700; color: var(--navy); margin-top: 4px; }
        .section-card { background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow); padding: 24px; margin-bottom: 20px; }
        .section-card h2 { color: var(--navy); margin: 0 0 16px; font-size: 1.2rem; }
        .skill-row { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--line); }
        .skill-row:last-child { border-bottom: none; }
        .skill-status { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700; flex-shrink: 0; }
        .skill-status.have { background: var(--green-soft); color: var(--green); }
        .skill-status.need { background: #FEE; color: #E74C3C; }
        .skill-detail { flex: 1; }
        .skill-detail h4 { margin: 0; color: var(--navy); font-size: 0.95rem; }
        .skill-detail p { margin: 2px 0 0; color: var(--grey); font-size: 0.82rem; }
        .level-badge { padding: 3px 10px; border-radius: 999px; font-size: 0.72rem; font-weight: 700; }
        .level-badge.essential { background: #FEE; color: #B00020; }
        .level-badge.important { background: var(--amber-soft); color: var(--amber); }
        .level-badge.desirable { background: var(--blue-soft); color: var(--navy); }
        .level-badge.emerging { background: #F0F0F0; color: var(--grey); }
        .cert-card { display: flex; gap: 16px; padding: 16px; border: 1px solid var(--line); border-radius: 10px; margin-bottom: 12px; }
        .cert-card .priority { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.85rem; flex-shrink: 0; }
        .cert-card .priority.high { background: #FEE; color: #B00020; }
        .cert-card .priority.medium { background: var(--amber-soft); color: var(--amber); }
        .cert-card .priority.desirable { background: var(--blue-soft); color: var(--navy); }
        .cert-card h4 { margin: 0 0 4px; color: var(--navy); }
        .cert-card .meta { font-size: 0.82rem; color: var(--grey); }
        .project-card { border-left: 4px solid var(--navy); padding: 14px 18px; margin-bottom: 12px; background: var(--blue-soft); border-radius: 0 10px 10px 0; }
        .project-card h4 { margin: 0 0 4px; color: var(--navy); }
        .project-card p { margin: 0; font-size: 0.88rem; color: var(--grey); }
        .task-list { list-style: none; padding: 0; counter-reset: task; }
        .task-list li { counter-increment: task; padding: 10px 0 10px 40px; border-bottom: 1px solid var(--line); position: relative; font-size: 0.92rem; }
        .task-list li:last-child { border-bottom: none; }
        .task-list li::before {
            content: counter(task); position: absolute; left: 0; top: 10px;
            width: 28px; height: 28px; border-radius: 50%; background: var(--navy); color: #fff;
            display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700;
        }
        .interview-q { padding: 10px 16px; background: #F8FAFF; border-radius: 8px; margin-bottom: 8px; font-size: 0.9rem; color: var(--ink); border-left: 3px solid var(--blue); }
    </style>
</head>
<body>
<?php require 'nav.php'; ?>
<main class="app-main">

    <!-- Hero -->
    <div class="roadmap-hero">
        <div>
            <h1>🗺 Your Career Roadmap</h1>
            <p class="sub">Personalised for <strong><?= htmlspecialchars($student['full_name']) ?></strong> · Target: <?= htmlspecialchars($roadmap['title']) ?></p>
            <p class="sub" style="font-size:0.82rem;margin-top:4px;">Based on your profile, skills, and current job market demand in Australia</p>
        </div>
        <div style="text-align:center;">
            <div class="strength-ring"><div class="strength-inner"><span class="pct"><?= $strength ?>%</span><span class="lbl">Profile<br>Strength</span></div></div>
        </div>
    </div>

    <!-- Quick info -->
    <div class="info-grid">
        <div class="info-box"><div class="label">Target role</div><div class="value"><?= htmlspecialchars($roadmap['title']) ?></div></div>
        <div class="info-box"><div class="label">Salary range</div><div class="value"><?= $roadmap['salary_range'] ?></div></div>
        <div class="info-box"><div class="label">Market demand</div><div class="value"><?= $roadmap['demand'] ?></div></div>
        <div class="info-box"><div class="label">Estimated timeline</div><div class="value"><?= $roadmap['timeline'] ?></div></div>
    </div>

    <p style="color:var(--grey);font-size:0.9rem;margin-bottom:24px;"><?= $roadmap['summary'] ?></p>

    <!-- 1. Required Skills -->
    <div class="section-card">
        <h2>📋 Required Technical Skills & Knowledge</h2>
        <p class="muted" style="margin:-10px 0 16px;">Skills assessed against your current profile. Green = you have it. Red = skill gap to close.</p>
        <?php foreach ($roadmap['skills'] as $sk): ?>
        <div class="skill-row">
            <div class="skill-status <?= $hasSkill($sk['name']) ? 'have' : 'need' ?>"><?= $hasSkill($sk['name']) ? '✓' : '✗' ?></div>
            <div class="skill-detail">
                <h4><?= htmlspecialchars($sk['name']) ?></h4>
                <p><?= htmlspecialchars($sk['desc']) ?></p>
            </div>
            <span class="level-badge <?= strtolower($sk['level']) ?>"><?= $sk['level'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- 2. Certifications -->
    <div class="section-card">
        <h2>🏅 Recommended Certifications</h2>
        <p class="muted" style="margin:-10px 0 16px;">Industry-recognised credentials ranked by employer demand in Australia.</p>
        <?php foreach ($roadmap['certifications'] as $c): ?>
        <div class="cert-card">
            <div class="priority <?= strtolower($c['priority']) ?>"><?= strtoupper(substr($c['priority'],0,1)) ?></div>
            <div>
                <h4><?= htmlspecialchars($c['name']) ?></h4>
                <p class="meta"><?= htmlspecialchars($c['desc']) ?></p>
                <p class="meta" style="margin-top:4px;">⏱ <?= $c['timeline'] ?> · 💰 <?= $c['cost'] ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- 3. Portfolio Projects -->
    <div class="section-card">
        <h2>📁 Portfolio Projects & Documentation</h2>
        <p class="muted" style="margin:-10px 0 16px;">Build these to demonstrate hands-on capability to employers.</p>
        <?php foreach ($roadmap['portfolio'] as $p): ?>
        <div class="project-card">
            <h4><?= htmlspecialchars($p['project']) ?></h4>
            <p><?= htmlspecialchars($p['desc']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- 4. Practical Tasks -->
    <div class="section-card">
        <h2>🔧 Practical Experience & Hands-On Tasks</h2>
        <p class="muted" style="margin:-10px 0 16px;">Complete these to build real experience. Check them off as you go.</p>
        <ol class="task-list">
            <?php foreach ($roadmap['practical_tasks'] as $task): ?>
                <li><?= htmlspecialchars($task) ?></li>
            <?php endforeach; ?>
        </ol>
    </div>

    <!-- 5. Job Strategy -->
    <div class="section-card">
        <h2>🎯 Job Application Strategy</h2>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
            <div class="info-box"><div class="label">Target job sites</div><div class="value" style="font-size:0.9rem;"><?= $roadmap['job_strategy']['target_sites'] ?></div></div>
            <div class="info-box"><div class="label">Search keywords</div><div class="value" style="font-size:0.9rem;"><?= $roadmap['job_strategy']['keywords'] ?></div></div>
        </div>
        <h3 style="color:var(--navy);font-size:1rem;margin:16px 0 8px;">📝 Resume tips</h3>
        <p style="font-size:0.9rem;color:var(--ink);"><?= htmlspecialchars($roadmap['job_strategy']['resume_tips']) ?></p>
        <h3 style="color:var(--navy);font-size:1rem;margin:16px 0 8px;">🎤 Interview preparation — practice these questions</h3>
        <?php foreach ($roadmap['job_strategy']['interview_prep'] as $q): ?>
            <div class="interview-q"><?= htmlspecialchars($q) ?></div>
        <?php endforeach; ?>
        <h3 style="color:var(--navy);font-size:1rem;margin:16px 0 8px;">📊 Market expectations</h3>
        <p style="font-size:0.9rem;color:var(--ink);"><?= htmlspecialchars($roadmap['job_strategy']['market_notes']) ?></p>
    </div>

    <p class="muted" style="margin-top:10px;">This roadmap is personalised for <?= htmlspecialchars($student['full_name']) ?> and stored as JSON in the recommendations table (FR04). Last generated: <?= date('j M Y, g:ia') ?>.</p>

</main>
</body>
</html>
