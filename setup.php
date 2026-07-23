<?php
/**
 * Smart Recruit - Database Initialization and Seeding Script
 * Run this script to automatically create the database and seed it with mock data.
 */

header('Content-Type: text/plain');

$host = '127.0.0.1';
$username = 'root';
$password = 'root';
$dbName = 'smart_recruit';

try {
    // 1. Connect to MySQL Server (without database)
    echo "Connecting to MySQL server at $host...\n";
    $pdo = new PDO("mysql:host=$host;port=8889", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Read and parse schema.sql
    echo "Reading schema.sql...\n";
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("schema.sql not found in " . __DIR__);
    }
    $sql = file_get_contents($schemaFile);

    // 3. Execute schema
    echo "Creating database and tables...\n";
    // We execute the schema queries
    $pdo->exec($sql);
    echo "Database schema created successfully!\n\n";

    // Reconnect directly to the new database
    $pdo = new PDO("mysql:host=$host;port=8889;dbname=$dbName", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Seeding skills directory...\n";
    $skillsData = [
        ['SQL', 'Database', 'Intermediate'],
        ['PHP', 'Backend', 'Intermediate'],
        ['HTML', 'Frontend', 'Intermediate'],
        ['CSS', 'Frontend', 'Intermediate'],
        ['JavaScript', 'Frontend', 'Intermediate'],
        ['Python', 'Programming', 'Intermediate'],
        ['Java', 'Programming', 'Intermediate'],
        ['Git', 'Tools', 'Intermediate'],
        ['MVC Architecture', 'Concepts', 'Intermediate'],
        ['Network Security', 'Cybersecurity', 'Advanced'],
        ['Threat Analysis', 'Cybersecurity', 'Intermediate'],
        ['Project Management', 'Business', 'Intermediate'],
        ['Agile Methodologies', 'Business', 'Intermediate'],
        ['Data Analysis', 'Data Science', 'Intermediate'],
        ['Machine Learning', 'Data Science', 'Advanced'],
        ['React.js', 'Frontend', 'Intermediate']
    ];

    $stmtSkill = $pdo->prepare("INSERT INTO skills (skill_name, category, level) VALUES (?, ?, ?)");
    foreach ($skillsData as $skill) {
        $stmtSkill->execute($skill);
    }
    echo "Inserted " . count($skillsData) . " skills.\n";

    echo "Seeding user accounts...\n";
    $usersData = [
        // email, password, role, is_verified
        ['admin@smartrecruit.com.au', password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 10]), 'admin', 1],
        ['deepak@excelsia.edu.au', password_hash('student123', PASSWORD_BCRYPT, ['cost' => 10]), 'student', 1],
        ['john@usyd.edu.au', password_hash('student123', PASSWORD_BCRYPT, ['cost' => 10]), 'student', 1],
        ['sarah@unsw.edu.au', password_hash('student123', PASSWORD_BCRYPT, ['cost' => 10]), 'student', 1],
        ['recruiter@canva.com', password_hash('company123', PASSWORD_BCRYPT, ['cost' => 10]), 'organisation', 1],
        ['recruiter@atlassian.com', password_hash('company123', PASSWORD_BCRYPT, ['cost' => 10]), 'organisation', 1],
        ['hiring@commbank.com.au', password_hash('company123', PASSWORD_BCRYPT, ['cost' => 10]), 'organisation', 1]
    ];

    $stmtUser = $pdo->prepare("INSERT INTO users (email, password, role, is_verified) VALUES (?, ?, ?, ?)");
    foreach ($usersData as $user) {
        $stmtUser->execute($user);
    }

    // Get user IDs
    $uAdmin = $pdo->query("SELECT user_id FROM users WHERE email = 'admin@smartrecruit.com.au'")->fetchColumn();
    $uDeepak = $pdo->query("SELECT user_id FROM users WHERE email = 'deepak@excelsia.edu.au'")->fetchColumn();
    $uJohn = $pdo->query("SELECT user_id FROM users WHERE email = 'john@usyd.edu.au'")->fetchColumn();
    $uSarah = $pdo->query("SELECT user_id FROM users WHERE email = 'sarah@unsw.edu.au'")->fetchColumn();
    $uCanva = $pdo->query("SELECT user_id FROM users WHERE email = 'recruiter@canva.com'")->fetchColumn();
    $uAtlassian = $pdo->query("SELECT user_id FROM users WHERE email = 'recruiter@atlassian.com'")->fetchColumn();
    $uCBA = $pdo->query("SELECT user_id FROM users WHERE email = 'hiring@commbank.com.au'")->fetchColumn();

    echo "Seeding student profiles...\n";
    $studentsData = [
        [$uDeepak, 'Deepak Bhandari', 'Excelsia University College', 'Bachelor of Information Technology', 3.80, 'resumes/deepak_resume.pdf', 85, 0],
        [$uJohn, 'John Smith', 'University of Sydney', 'Bachelor of Computer Science', 3.65, 'resumes/john_resume.pdf', 90, 1],
        [$uSarah, 'Sarah Jenkins', 'UNSW Sydney', 'Bachelor of Software Engineering', 3.92, 'resumes/sarah_resume.pdf', 95, 1]
    ];

    $stmtStudent = $pdo->prepare("INSERT INTO students (user_id, full_name, university, degree, gpa, resume_url, profile_score, is_premium) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($studentsData as $student) {
        $stmtStudent->execute($student);
    }

    // Get Student IDs
    $sDeepak = $pdo->query("SELECT student_id FROM students WHERE user_id = $uDeepak")->fetchColumn();
    $sJohn = $pdo->query("SELECT student_id FROM students WHERE user_id = $uJohn")->fetchColumn();
    $sSarah = $pdo->query("SELECT student_id FROM students WHERE user_id = $uSarah")->fetchColumn();

    echo "Seeding student skills...\n";
    // Skills Map
    $skillIds = $pdo->query("SELECT skill_name, skill_id FROM skills")->fetchAll(PDO::FETCH_KEY_PAIR);

    $studentSkills = [
        // student_id, skill_id, proficiency, verified
        // Deepak
        [$sDeepak, $skillIds['SQL'], 'Intermediate', 1],
        [$sDeepak, $skillIds['HTML'], 'Advanced', 1],
        [$sDeepak, $skillIds['CSS'], 'Advanced', 1],
        [$sDeepak, $skillIds['JavaScript'], 'Intermediate', 0],
        [$sDeepak, $skillIds['PHP'], 'Beginner', 0],
        // John
        [$sJohn, $skillIds['SQL'], 'Advanced', 1],
        [$sJohn, $skillIds['PHP'], 'Intermediate', 1],
        [$sJohn, $skillIds['Java'], 'Advanced', 1],
        [$sJohn, $skillIds['Git'], 'Intermediate', 1],
        // Sarah
        [$sSarah, $skillIds['SQL'], 'Advanced', 1],
        [$sSarah, $skillIds['HTML'], 'Advanced', 1],
        [$sSarah, $skillIds['CSS'], 'Advanced', 1],
        [$sSarah, $skillIds['JavaScript'], 'Advanced', 1],
        [$sSarah, $skillIds['Python'], 'Intermediate', 1],
        [$sSarah, $skillIds['Git'], 'Advanced', 1],
        [$sSarah, $skillIds['React.js'], 'Intermediate', 1]
    ];

    $stmtStudSkill = $pdo->prepare("INSERT INTO student_skills (student_id, skill_id, proficiency, verified) VALUES (?, ?, ?, ?)");
    foreach ($studentSkills as $ss) {
        $stmtStudSkill->execute($ss);
    }

    echo "Seeding organisation profiles...\n";
    $organisationsData = [
        [$uCanva, 'Canva Pty Ltd', 'ABN-882947192', 'Design & Technology', 'Surry Hills, Sydney', 1],
        [$uAtlassian, 'Atlassian Corp', 'ABN-119284719', 'Enterprise Software', 'Sydney CBD, Sydney', 1],
        [$uCBA, 'Commonwealth Bank of Australia', 'ABN-553829104', 'Financial Services', 'Darling Harbour, Sydney', 0]
    ];

    $stmtOrg = $pdo->prepare("INSERT INTO organisations (user_id, org_name, abn, industry, location, is_partner) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($organisationsData as $org) {
        $stmtOrg->execute($org);
    }

    // Get Org IDs
    $oCanva = $pdo->query("SELECT org_id FROM organisations WHERE user_id = $uCanva")->fetchColumn();
    $oAtlassian = $pdo->query("SELECT org_id FROM organisations WHERE user_id = $uAtlassian")->fetchColumn();
    $oCBA = $pdo->query("SELECT org_id FROM organisations WHERE user_id = $uCBA")->fetchColumn();

    echo "Seeding job postings...\n";
    $jobsData = [
        [$oCanva, 'Junior Full Stack Developer', 'We are looking for a motivated Junior Developer to join our core product team. You will work across both client and server side codebases.', 'Full-time', 70000, 85000, date('Y-m-d', strtotime('+30 days')), 'active'],
        [$oAtlassian, 'Graduate Software Engineer', 'Join Atlassian as a Graduate Engineer! Develop highly concurrent backend systems and scalable collaboration interfaces used by millions.', 'Full-time', 85000, 95000, date('Y-m-d', strtotime('+45 days')), 'active'],
        [$oCBA, 'Junior Cybersecurity Specialist', 'Support CommBank\'s digital safety operations. Help scan networks, review alerts, and run threat analyses.', 'Full-time', 78000, 88000, date('Y-m-d', strtotime('+15 days')), 'active'],
        [$oCanva, 'Graduate Web Designer', 'Design and implement outstanding web templates. Requires strong HTML, CSS, and interactive design skills.', 'Full-time', 65000, 75000, date('Y-m-d', strtotime('+60 days')), 'pending']
    ];

    $stmtJob = $pdo->prepare("INSERT INTO job_postings (org_id, title, description, type, salary_min, salary_max, deadline, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($jobsData as $job) {
        $stmtJob->execute($job);
    }

    // Get Job IDs
    $jFullStackCanva = $pdo->query("SELECT job_id FROM job_postings WHERE title = 'Junior Full Stack Developer'")->fetchColumn();
    $jGradAtlassian = $pdo->query("SELECT job_id FROM job_postings WHERE title = 'Graduate Software Engineer'")->fetchColumn();
    $jCyberCBA = $pdo->query("SELECT job_id FROM job_postings WHERE title = 'Junior Cybersecurity Specialist'")->fetchColumn();
    $jWebCanva = $pdo->query("SELECT job_id FROM job_postings WHERE title = 'Graduate Web Designer'")->fetchColumn();

    echo "Seeding job skills requirements...\n";
    $jobSkills = [
        // job_id, skill_id, importance
        // Canva Full Stack Developer
        [$jFullStackCanva, $skillIds['SQL'], 'Essential'],
        [$jFullStackCanva, $skillIds['PHP'], 'Essential'],
        [$jFullStackCanva, $skillIds['HTML'], 'Essential'],
        [$jFullStackCanva, $skillIds['CSS'], 'Essential'],
        [$jFullStackCanva, $skillIds['JavaScript'], 'Desirable'],
        // Atlassian Graduate Software Engineer
        [$jGradAtlassian, $skillIds['Java'], 'Essential'],
        [$jGradAtlassian, $skillIds['Git'], 'Essential'],
        [$jGradAtlassian, $skillIds['SQL'], 'Desirable'],
        [$jGradAtlassian, $skillIds['MVC Architecture'], 'Desirable'],
        // CBA Cybersecurity
        [$jCyberCBA, $skillIds['Network Security'], 'Essential'],
        [$jCyberCBA, $skillIds['Threat Analysis'], 'Desirable'],
        [$jCyberCBA, $skillIds['Python'], 'Desirable'],
        // Canva Web Designer
        [$jWebCanva, $skillIds['HTML'], 'Essential'],
        [$jWebCanva, $skillIds['CSS'], 'Essential'],
        [$jWebCanva, $skillIds['JavaScript'], 'Desirable']
    ];

    $stmtJobSkill = $pdo->prepare("INSERT INTO job_skills (job_id, skill_id, importance) VALUES (?, ?, ?)");
    foreach ($jobSkills as $js) {
        $stmtJobSkill->execute($js);
    }

    echo "Seeding applications...\n";
    // We calculate the matching score for John -> Job 1 (Junior Full Stack Canva)
    // John skills: SQL, PHP, Java, Git.
    // Job 1 skills: SQL, PHP, HTML, CSS, JavaScript (5 skills total).
    // John matches SQL and PHP (2 matching skills). Score = 2 / 5 * 100 = 40.00%.
    // Sarah skills: SQL, HTML, CSS, JavaScript, Python, Git, React.js.
    // Job 1 skills: SQL, PHP, HTML, CSS, JavaScript.
    // Sarah matches SQL, HTML, CSS, JavaScript (4 matching). Score = 4 / 5 * 100 = 80.00%.
    // Deepak skills: SQL, HTML, CSS, JavaScript, PHP.
    // Job 1 skills: SQL, PHP, HTML, CSS, JavaScript.
    // Deepak matches SQL, HTML, CSS, JavaScript, PHP (5 matching). Score = 100.00%.
    
    $applicationsData = [
        [$sDeepak, $jFullStackCanva, 100.00, 'pending', 0],
        [$sJohn, $jGradAtlassian, 75.00, 'shortlisted', 1],
        [$sSarah, $jFullStackCanva, 80.00, 'shortlisted', 0]
    ];

    $stmtApp = $pdo->prepare("INSERT INTO applications (student_id, job_id, match_score, status, is_referred) VALUES (?, ?, ?, ?, ?)");
    foreach ($applicationsData as $app) {
        $stmtApp->execute($app);
    }

    echo "Seeding career guidance & roadmaps...\n";
    $roadmapSarah = json_encode([
        ["milestone" => "Profile Launch & Skills Verification", "status" => "completed", "details" => "Verify key skillsets on your profile: HTML, CSS, SQL, Git"],
        ["milestone" => "Advanced JS & Frontend Libraries", "status" => "active", "details" => "Work on React.js project tasks and dynamic state management models"],
        ["milestone" => "Mock Technical Interview Practice", "status" => "pending", "details" => "Complete structured 1-on-1 mock interviews with technical mentors"],
        ["milestone" => "Referral Submission to Canva", "status" => "pending", "details" => "Submit verified profile details directly to Canva's design leads"]
    ]);
    
    $gapsSarah = json_encode(['PHP', 'MVC Architecture']);

    $recommendationsData = [
        [$sSarah, 'Full Stack Developer', $roadmapSarah, $gapsSarah, 50.00],
        [$sJohn, 'Software Engineer', json_encode([
            ["milestone" => "Verify backend database skills", "status" => "completed", "details" => "SQL verified by platform Admin"],
            ["milestone" => "Explore MVC principles in PHP", "status" => "completed", "details" => "Learn routing and model structures"],
            ["milestone" => "Placement Referral", "status" => "active", "details" => "Direct recommendation sent to Atlassian Corp"]
        ]), json_encode(['MVC Architecture']), 66.67]
    ];

    $stmtRec = $pdo->prepare("INSERT INTO recommendations (student_id, target_role, career_roadmap, skill_gaps, completion_pct) VALUES (?, ?, ?, ?, ?)");
    foreach ($recommendationsData as $rec) {
        $stmtRec->execute($rec);
    }

    echo "Seeding active Premium subscriptions...\n";
    $subscriptionsData = [
        [$sJohn, 'Premium', 49.00, date('Y-m-d', strtotime('-15 days')), date('Y-m-d', strtotime('+15 days')), 'PAYID-MOCKSUB112233', 'active'],
        [$sSarah, 'Premium', 49.00, date('Y-m-d', strtotime('-5 days')), date('Y-m-d', strtotime('+25 days')), 'PAYID-MOCKSUB998877', 'active']
    ];

    $stmtSub = $pdo->prepare("INSERT INTO subscriptions (student_id, plan, amount_paid, start_date, end_date, payment_ref, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($subscriptionsData as $sub) {
        $stmtSub->execute($sub);
    }

    echo "Seeding admin referral records...\n";
    $referralsData = [
        // student_id, org_id, job_id, admin_id, status
        [$sJohn, $oAtlassian, $jGradAtlassian, $uAdmin, 'hired']
    ];

    $stmtRef = $pdo->prepare("INSERT INTO referrals (student_id, org_id, job_id, admin_id, status) VALUES (?, ?, ?, ?, ?)");
    foreach ($referralsData as $ref) {
        $stmtRef->execute($ref);
    }

    // Get referral ID
    $refId = $pdo->query("SELECT referral_id FROM referrals LIMIT 1")->fetchColumn();

    echo "Seeding commission invoice...\n";
    // Atlassian hired John. Salary: $85,000. Rate: 15%. Commission: $12,750
    $commissionsData = [
        [$refId, $oAtlassian, 85000.00, 15.00, 12750.00, date('Y-m-d', strtotime('-5 days')), date('Y-m-d', strtotime('+25 days')), 'pending']
    ];

    $stmtComm = $pdo->prepare("INSERT INTO commissions (referral_id, org_id, first_year_salary, commission_rate, amount_due, invoice_date, due_date, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($commissionsData as $comm) {
        $stmtComm->execute($comm);
    }

    echo "Seeding training sessions...\n";
    $sessionsData = [
        ['Agile Methods & Scrum in Practice', 'Atlassian Corp', 'face-to-face', date('Y-m-d H:i:s', strtotime('+20 days')), 30, 28, 0.00],
        ['Building Scalable Web Apps with React', 'Canva Pty Ltd', 'online', date('Y-m-d H:i:s', strtotime('+25 days')), 100, 97, 15.00]
    ];

    $stmtSess = $pdo->prepare("INSERT INTO training_sessions (title, partner_name, mode, session_date, seats_total, seats_remaining, price) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($sessionsData as $sess) {
        $stmtSess->execute($sess);
    }

    echo "Seeding administrative logs...\n";
    $logsData = [
        [$uAdmin, 'Approved job posting: Junior Full Stack Developer', 'job_postings', null, json_encode(['status' => 'active']), '192.168.1.50'],
        [$uAdmin, 'Referred Student John Smith to Atlassian Corp', 'referrals', null, json_encode(['status' => 'referred']), '192.168.1.50']
    ];

    $stmtLog = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, target_table, old_value, new_value, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($logsData as $log) {
        $stmtLog->execute($log);
    }

    echo "Seeding payments ledger...\n";
    $paymentsData = [
        [$uJohn, 49.00, 'AUD', 'PayPal', 'PAYID-MOCKSUB112233', 'success'],
        [$uSarah, 49.00, 'AUD', 'PayPal', 'PAYID-MOCKSUB998877', 'success']
    ];

    $stmtPay = $pdo->prepare("INSERT INTO payments (payer_id, amount, currency, gateway, gateway_ref, status) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($paymentsData as $pay) {
        $stmtPay->execute($pay);
    }

    echo "\n--------------------------------------------\n";
    echo "SMART RECRUIT SYSTEM SETUP COMPLETED SUCCESSFULLY!\n";
    echo "The database is ready to be used with the web prototype.\n";
    echo "--------------------------------------------\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
