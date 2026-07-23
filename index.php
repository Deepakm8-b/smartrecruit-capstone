<?php
/**
 * Smart Recruit - Landing Page
 */
require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. Hero Banner with Platform Statistics -->
<section class="hero-section">
  <h1>Smart Student–Organisation Recruitment</h1>
  <p>Bridging the gap between university graduates and hiring organizations across Australia using automated skill-based matching and JSON-driven career roadmaps.</p>
  
  <div class="hero-stats">
    <div class="hero-stat-item">
      <span class="val">99.2%</span>
      <span class="lbl">Match Accuracy</span>
    </div>
    <div class="hero-stat-item">
      <span class="val">50,000+</span>
      <span class="lbl">Students Expected</span>
    </div>
    <div class="hero-stat-item">
      <span class="val">10,000+</span>
      <span class="lbl">Active Listings</span>
    </div>
    <div class="hero-stat-item">
      <span class="val">10-20%</span>
      <span class="lbl">Placement Commission</span>
    </div>
  </div>
</section>

<!-- Quick Test Logins Section (Highly useful for prototype evaluation) -->
<section class="card" style="margin-bottom: 40px; border-left: 5px solid var(--premium-amber);">
  <h3 class="card-title text-premium"><i class="fa-solid fa-flask"></i> Interactive Prototype Quick-Login Accounts</h3>
  <p style="margin-bottom: 20px;">Use these pre-seeded test accounts to evaluate different workflows and roles on the platform. Click <strong>Go to Login Portal</strong> and enter the credentials.</p>
  
  <div class="metrics-row" style="margin-bottom: 20px;">
    <div class="stat-card stat-premium" style="flex-direction: column; align-items: flex-start; gap: 8px;">
      <span class="stat-lbl"><i class="fa-solid fa-user-graduate"></i> Student (Free)</span>
      <span class="stat-val" style="font-size: 1.1rem;">Deepak Bhandari</span>
      <span style="font-size: 0.85rem; color: var(--text-muted);">
        Email: <strong>deepak@excelsia.edu.au</strong><br>
        Pass: <strong>student123</strong>
      </span>
    </div>
    
    <div class="stat-card stat-premium" style="flex-direction: column; align-items: flex-start; gap: 8px;">
      <span class="stat-lbl"><i class="fa-solid fa-crown text-premium"></i> Student (Premium)</span>
      <span class="stat-val" style="font-size: 1.1rem;">Sarah Jenkins</span>
      <span style="font-size: 0.85rem; color: var(--text-muted);">
        Email: <strong>sarah@unsw.edu.au</strong><br>
        Pass: <strong>student123</strong>
      </span>
    </div>
    
    <div class="stat-card" style="flex-direction: column; align-items: flex-start; gap: 8px; border-left-color: var(--secondary-color);">
      <span class="stat-lbl"><i class="fa-solid fa-handshake"></i> Partner Company</span>
      <span class="stat-val" style="font-size: 1.1rem;">Canva Pty Ltd</span>
      <span style="font-size: 0.85rem; color: var(--text-muted);">
        Email: <strong>recruiter@canva.com</strong><br>
        Pass: <strong>company123</strong>
      </span>
    </div>
    
    <div class="stat-card" style="flex-direction: column; align-items: flex-start; gap: 8px; border-left-color: var(--primary-color);">
      <span class="stat-lbl"><i class="fa-solid fa-user-shield"></i> Platform Admin</span>
      <span class="stat-val" style="font-size: 1.1rem;">Excelsia Admin</span>
      <span style="font-size: 0.85rem; color: var(--text-muted);">
        Email: <strong>admin@smartrecruit.com.au</strong><br>
        Pass: <strong>admin123</strong>
      </span>
    </div>
  </div>
  
  <div style="text-align: center;">
    <a href="login.php" class="btn btn-primary"><i class="fa-solid fa-sign-in-alt"></i> Go to Login Portal</a>
  </div>
</section>

<!-- 2. Core Features -->
<h2 class="section-title">Core Services</h2>
<div class="features-grid">
  <div class="card feature-card">
    <i class="fa-solid fa-magnifying-glass-chart"></i>
    <h3>AI Skill Matching</h3>
    <p>Automatically calculates job compatibility scores based on skill overlap percentages between students and listings.</p>
  </div>
  
  <div class="card feature-card">
    <i class="fa-solid fa-route"></i>
    <h3>Visual Career Roadmaps</h3>
    <p>Provides Premium students with custom JSON-structured roadmaps identifying skills gaps and milestones.</p>
  </div>
  
  <div class="card feature-card">
    <i class="fa-solid fa-user-tie"></i>
    <h3>Recruiter Referrals</h3>
    <p>Direct placement services linking top-performing premium students to verified partner organizations.</p>
  </div>
  
  <div class="card feature-card">
    <i class="fa-solid fa-calendar-check"></i>
    <h3>Training Workshops</h3>
    <p>Interactive industry-led workshops and training events, giving certifications upon completion.</p>
  </div>
</div>

<!-- 3. How It Works -->
<h2 class="section-title">How It Works</h2>
<div class="features-grid" style="margin-bottom: 50px;">
  <div class="card" style="text-align: left;">
    <h4 style="margin-bottom: 10px;"><span style="color: var(--secondary-color); font-size: 1.5rem; font-weight: 800; margin-right: 8px;">1</span> Register Account</h4>
    <p>Students register using institutional emails; organisations sign up with verified Australian Business Numbers (ABNs).</p>
  </div>
  
  <div class="card" style="text-align: left;">
    <h4 style="margin-bottom: 10px;"><span style="color: var(--secondary-color); font-size: 1.5rem; font-weight: 800; margin-right: 8px;">2</span> Align & Match</h4>
    <p>The system compares student skills with job descriptions in real-time to generate match compatibility rates.</p>
  </div>
  
  <div class="card" style="text-align: left;">
    <h4 style="margin-bottom: 10px;"><span style="color: var(--secondary-color); font-size: 1.5rem; font-weight: 800; margin-right: 8px;">3</span> Placement & Pay</h4>
    <p>Once a student is placed, the admin bills the company. The secure commission payment is handled through PayPal.</p>
  </div>
</div>

<!-- 4. Revenue Model -->
<section class="card" style="margin-bottom: 50px; background: linear-gradient(135deg, rgba(30, 58, 95, 0.02) 0%, rgba(24, 95, 165, 0.04) 100%);">
  <h3 class="card-title"><i class="fa-solid fa-sack-dollar"></i> Dual Revenue Business Model</h3>
  <div class="dashboard-grid">
    <div style="display: flex; flex-direction: column; justify-content: center; gap: 15px;">
      <h4>1. Student Freemium Model</h4>
      <p>Basic students access the job board and matches for free. **Premium Students ($49/mo)** unlock customized career coaching, reference letters, partner referrals, and free workshop bookings.</p>
      
      <h4>2. Organisation Placement Commission</h4>
      <p>Organisations list jobs and review matches for free. On successful recruitment of a SmartRecruit-referred student, a commission fee of **10% to 20%** of the student's first-year salary is invoiced.</p>
    </div>
    <div style="display: flex; align-items: center; justify-content: center;">
      <div class="stat-card stat-premium" style="width: 100%; max-width: 320px; flex-direction: column; padding: 30px;">
        <span class="stat-lbl">Premium Subscription</span>
        <span class="stat-val" style="font-size: 2.5rem; margin: 10px 0;">$49 <span style="font-size: 1.2rem; font-weight: 500; color: var(--text-muted);">/ month</span></span>
        <a href="student/premium.php" class="btn btn-premium btn-sm" style="width: 100%;">View Plan Details</a>
      </div>
    </div>
  </div>
</section>

<!-- 5. Technology Stack -->
<h2 class="section-title">System Architecture & Tech Stack</h2>
<div class="table-responsive" style="margin-bottom: 50px;">
  <table class="table">
    <thead>
      <tr>
        <th>Layer</th>
        <th>Technology</th>
        <th>Purpose / Role</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><strong>Presentation View</strong></td>
        <td>HTML5, CSS3, ES6 JavaScript</td>
        <td>Responsive design system using Plus Jakarta Sans, DM Sans, and FontAwesome icons.</td>
      </tr>
      <tr>
        <td><strong>Application Controller</strong></td>
        <td>PHP 8.1 / 8.2</td>
        <td>Processes request routing, session states, calculations, and database integrations.</td>
      </tr>
      <tr>
        <td><strong>Data Layer</strong></td>
        <td>MySQL 8.0 (3NF Normalised)</td>
        <td>Stores persistent system records across 17 database entities with referential integrity.</td>
      </tr>
      <tr>
        <td><strong>Security Core</strong></td>
        <td>BCrypt Hashing & PDO Prepared Statements</td>
        <td>Secures passwords using blowfish crypt (cost 10) and blocks SQL injections.</td>
      </tr>
      <tr>
        <td><strong>Payment Processing</strong></td>
        <td>PayPal SDK / Sandbox Gateway</td>
        <td>Simulates secure external transactions for subscriptions and invoices.</td>
      </tr>
    </tbody>
  </table>
</div>

<!-- 6. Database Entities Overview -->
<h2 class="section-title">Relational Tables (3NF Schema)</h2>
<div class="card" style="margin-bottom: 50px;">
  <p style="margin-bottom: 15px;">Smart Recruit implements a 17-table relational schema. Below is a summary of the entities designed and seeded:</p>
  <div class="skill-tags">
    <span class="skill-tag essential">1. users</span>
    <span class="skill-tag essential">2. students</span>
    <span class="skill-tag essential">3. organisations</span>
    <span class="skill-tag essential">4. job_postings</span>
    <span class="skill-tag essential">5. applications</span>
    <span class="skill-tag desirable">6. recommendations</span>
    <span class="skill-tag desirable">7. subscriptions</span>
    <span class="skill-tag desirable">8. referrals</span>
    <span class="skill-tag desirable">9. commissions</span>
    <span class="skill-tag desirable">10. certificates</span>
    <span class="skill-tag">11. training_sessions</span>
    <span class="skill-tag">12. bookings</span>
    <span class="skill-tag">13. skills</span>
    <span class="skill-tag">14. student_skills</span>
    <span class="skill-tag">15. job_skills</span>
    <span class="skill-tag">16. admin_logs</span>
    <span class="skill-tag">17. payments</span>
  </div>
</div>

<!-- 7. Timeline and Team -->
<h2 class="section-title">IT Capstone Project Specifications</h2>
<div class="dashboard-grid" style="margin-bottom: 30px;">
  <div class="card">
    <h3 class="card-title"><i class="fa-solid fa-calendar-days"></i> Project Milestones</h3>
    <ul class="roadmap-timeline">
      <li class="roadmap-step completed">
        <div class="roadmap-step-dot"></div>
        <div class="roadmap-step-content" style="padding: 10px 15px;">
          <strong>Weeks 1–2: Planning & Risk Assessment</strong>
        </div>
      </li>
      <li class="roadmap-step completed">
        <div class="roadmap-step-dot"></div>
        <div class="roadmap-step-content" style="padding: 10px 15px;">
          <strong>Weeks 3–4: Requirements Gathering (FR & NFR)</strong>
        </div>
      </li>
      <li class="roadmap-step completed">
        <div class="roadmap-step-dot"></div>
        <div class="roadmap-step-content" style="padding: 10px 15px;">
          <strong>Weeks 4–7: System Design (ERD, DFD, Wireframes)</strong>
        </div>
      </li>
      <li class="roadmap-step active">
        <div class="roadmap-step-dot"></div>
        <div class="roadmap-step-content" style="padding: 10px 15px;">
          <strong>Week 8: Core Development & Seeding Script</strong>
        </div>
      </li>
    </ul>
  </div>
  
  <div class="card">
    <h3 class="card-title"><i class="fa-solid fa-users"></i> Project Roles & RACI</h3>
    <p>Smart Recruit is developed as a Capstone Project at **Excelsia University College, Sydney**.</p>
    <div style="margin-top: 15px;">
      <p><strong>Deepak Bhandari</strong> (Student Developer) — *Responsible* for planning, design, and coding.</p>
      <p style="margin-top: 8px;"><strong>Dr Cesar Sanin</strong> (Lecturer / Supervisor) — *Accountable* for reviews and approvals.</p>
    </div>
  </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
