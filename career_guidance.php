<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$userId = $_SESSION['user_id'];
$student = getStudentByUserId($pdo, $userId);

// Check premium status
$stmt = $pdo->prepare('SELECT premium_status FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();
$isPremium = $user['premium_status'] === 'premium';

if (!$isPremium) {
    header('Location: premium.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Career Guidance — SmartRecruit</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial; background: #f8fafc; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        .header { margin-bottom: 24px; }
        .header h1 { font-size: 20px; font-weight: 800; margin-bottom: 4px; }
        .header p { color: #666; font-size: 13px; }
        .info-box { background: #dbeafe; border-left: 4px solid #1e40af; padding: 12px; margin-bottom: 24px; font-size: 12px; }
        .tabs { display: flex; gap: 8px; margin-bottom: 24px; border-bottom: 1px solid #e5e7eb; }
        .tab { padding: 12px 16px; cursor: pointer; font-weight: 600; font-size: 13px; color: #666; border-bottom: 2px solid transparent; }
        .tab.active { color: #1e40af; border-bottom-color: #1e40af; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .card { background: white; padding: 20px; border: 1px solid #e5e7eb; margin-bottom: 16px; }
        .coach-card { display: grid; grid-template-columns: 80px 1fr; gap: 16px; align-items: start; }
        .coach-avatar { width: 80px; height: 80px; background: #1e40af; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 24px; }
        .coach-info h3 { font-size: 14px; font-weight: 800; margin-bottom: 4px; }
        .coach-info p { font-size: 12px; color: #666; line-height: 1.5; margin-bottom: 8px; }
        .coach-expertise { font-size: 11px; background: #f3f4f6; padding: 4px 8px; display: inline-block; margin-right: 4px; margin-bottom: 8px; }
        .btn { padding: 10px 16px; background: #1e40af; color: white; border: none; cursor: pointer; font-weight: 600; font-size: 12px; }
        .btn:hover { background: #1e3a8a; }
        .session-item { background: #f9fafb; padding: 16px; border-left: 3px solid #10b981; margin-bottom: 12px; }
        .session-item h4 { font-size: 13px; font-weight: 800; margin-bottom: 4px; }
        .session-item p { font-size: 12px; color: #666; line-height: 1.5; }
        .session-date { font-size: 11px; color: #999; margin-top: 8px; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="header">
        <h1>👔 Career Guidance & Coaching</h1>
        <p>Get personalized 1-on-1 coaching from industry experts</p>
    </div>

    <div class="info-box">
        ✓ This is a premium feature. Access expert career guidance to accelerate your growth.
    </div>

    <!-- TABS -->
    <div class="tabs">
        <div class="tab active" onclick="switchTab(event, 'coaches')">Available Coaches</div>
        <div class="tab" onclick="switchTab(event, 'upcoming')">Upcoming Sessions</div>
        <div class="tab" onclick="switchTab(event, 'book')">Book a Session</div>
    </div>

    <!-- COACHES TAB -->
    <div id="coaches" class="tab-content active">
        
        <div class="card">
            <div class="coach-card">
                <div class="coach-avatar">JD</div>
                <div class="coach-info">
                    <h3>James Davis</h3>
                    <p>Senior IT Support Manager with 12+ years of industry experience. Specializes in career transition and technical skill development.</p>
                    <div>
                        <span class="coach-expertise">IT Support</span>
                        <span class="coach-expertise">Leadership</span>
                        <span class="coach-expertise">Career Growth</span>
                    </div>
                    <button class="btn">Book Session</button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="coach-card">
                <div class="coach-avatar" style="background: #7c3aed;">SM</div>
                <div class="coach-info">
                    <h3>Sarah Mitchell</h3>
                    <p>Career strategist and recruiter with 8+ years of experience placing IT professionals. Expert in resume optimization and interview prep.</p>
                    <div>
                        <span class="coach-expertise">Resume Review</span>
                        <span class="coach-expertise">Interview Prep</span>
                        <span class="coach-expertise">Recruiter Insights</span>
                    </div>
                    <button class="btn">Book Session</button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="coach-card">
                <div class="coach-avatar" style="background: #10b981;">RC</div>
                <div class="coach-info">
                    <h3>Robert Chen</h3>
                    <p>Technical architect and skills coach. Helps professionals upskill in emerging technologies and advance their technical expertise.</p>
                    <div>
                        <span class="coach-expertise">Technical Skills</span>
                        <span class="coach-expertise">Cloud Computing</span>
                        <span class="coach-expertise">System Design</span>
                    </div>
                    <button class="btn">Book Session</button>
                </div>
            </div>
        </div>

    </div>

    <!-- UPCOMING SESSIONS TAB -->
    <div id="upcoming" class="tab-content">
        
        <div class="card">
            <h3 style="font-size: 14px; font-weight: 800; margin-bottom: 16px;">Your Scheduled Sessions</h3>
            
            <div class="session-item">
                <h4>Career Roadmap Review with James Davis</h4>
                <p>Discuss your IT Support Specialist roadmap and identify key milestones for the next 6 months.</p>
                <div class="session-date">📅 Jul 25, 2026 at 2:00 PM (60 mins) • <strong>Upcoming</strong></div>
            </div>

            <div class="session-item">
                <h4>Resume & LinkedIn Optimization with Sarah Mitchell</h4>
                <p>Get expert feedback on your resume and learn how to optimize your LinkedIn profile for recruiter visibility.</p>
                <div class="session-date">📅 Jul 30, 2026 at 3:00 PM (45 mins) • <strong>Scheduled</strong></div>
            </div>

        </div>

    </div>

    <!-- BOOK SESSION TAB -->
    <div id="book" class="tab-content">
        
        <div class="card">
            <h3 style="font-size: 14px; font-weight: 800; margin-bottom: 16px;">Book a Coaching Session</h3>
            
            <form style="display: grid; gap: 16px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px;">Select Coach *</label>
                    <select style="width: 100%; padding: 10px; border: 1px solid #d1d5db; font-family: inherit; font-size: 13px;">
                        <option>Choose a coach...</option>
                        <option>James Davis - IT Support Manager</option>
                        <option>Sarah Mitchell - Career Strategist</option>
                        <option>Robert Chen - Technical Architect</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px;">Session Topic *</label>
                    <select style="width: 100%; padding: 10px; border: 1px solid #d1d5db; font-family: inherit; font-size: 13px;">
                        <option>Choose topic...</option>
                        <option>Career planning</option>
                        <option>Resume review</option>
                        <option>Interview preparation</option>
                        <option>Skill development</option>
                        <option>Job search strategy</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px;">Preferred Date *</label>
                    <input type="date" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; font-family: inherit; font-size: 13px;">
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px;">Additional Notes</label>
                    <textarea style="width: 100%; padding: 10px; border: 1px solid #d1d5db; font-family: inherit; font-size: 13px; min-height: 80px;" placeholder="Tell the coach what you'd like to focus on..."></textarea>
                </div>

                <button type="submit" class="btn">Request Session</button>
            </form>
        </div>

    </div>

    <div style="text-align: center; margin-top: 24px;">
        <a href="dashboard.php" style="color: #666; text-decoration: none; font-size: 12px;">← Back to Dashboard</a>
    </div>

</div>

<script>
function switchTab(e, tabName) {
    // Hide all tab contents
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(c => c.classList.remove('active'));
    
    // Remove active class from all tabs
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(t => t.classList.remove('active'));
    
    // Show selected tab
    document.getElementById(tabName).classList.add('active');
    e.target.classList.add('active');
}
</script>

</body>
</html>
