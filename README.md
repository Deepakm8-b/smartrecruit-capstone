# SmartRecruit — Student-Organisation Recruitment Platform

**ICT307B IT Capstone Project B**  
Excelsia University College  
Submission Deadline: July 31, 2026

## 🚀 Quick Start

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Browser

### Installation

1. **Start XAMPP** → Click Start for Apache & MySQL
2. **Open phpMyAdmin** → `http://localhost/phpmyadmin`
3. **Create Database** → Click New → Name: `smartrecruit` → Create
4. **Import Schema** → Click Import → Select `schema.sql` → Go
5. **Start Server** → Run: `php -S localhost:9000`
6. **Access App** → Visit: `http://localhost:9000/login.php`

### Test Accounts

**Student:** `demo2@uni.edu.au` (password in phpMyAdmin)  
**Organisation:** `demo.org@smartrecruit.test` / `org123456`  
**Admin:** `admin@smartrecruit.test` / `admin123`

## ✨ Features

✅ Student & Organisation dashboards  
✅ Job posting & application system  
✅ Resume management  
✅ Certificate upload/approval  
✅ Achievement badges (8 badges)  
✅ Career roadmap (15+ milestones)  
✅ Expert Q&A platform  
✅ Email notifications  
✅ Settings (6 tabs)  
✅ Role-based access control  

## 🗂️ Database

- 38 tables
- Users, Students, Organisations, Jobs, Applications
- Resume versions, Badges, Roadmap steps
- Certificate proofs, Expert queries, Email logs

## 🔒 Security

✅ SQL injection prevention (PDO prepared statements)  
✅ XSS prevention (htmlspecialchars)  
✅ CSRF token validation  
✅ Password hashing  
✅ Session management  
✅ File upload validation  

## 📝 Project Structure

- `login.php` — Authentication
- `dashboard.php` — Student dashboard
- `recruiter_dashboard.php` — Organisation dashboard
- `jobs.php` — Job listings
- `apply_job.php` — Application form
- `resume_management.php` — Resume upload/download
- `settings.php` — User preferences
- `badge_progress.php` — Achievements
- `ask_expert.php` — Q&A platform
- `upload_proof.php` — Certificate uploads
- `admin_certificates.php` — Admin approvals
- `db.php` — Database connection
- `schema.sql` — Database schema

## 🔧 Database Configuration

File: `db.php`

```php
$pdo = new PDO(
    'mysql:host=127.0.0.1;port=3306;dbname=smartrecruit;charset=utf8mb4',
    'root',
    ''
);
```

## 📧 Email Setup

Emails sent via Gmail SMTP on:
- Application status changes
- Certificate approval/rejection
- Expert responses
- Milestone completion

From: `deepakbhandari982@gmail.com`

## 🎯 Tested Workflows

✅ Student login & dashboard  
✅ Browse & apply for jobs  
✅ Track applications  
✅ Upload & manage resumes  
✅ Upload & get certificates approved  
✅ Earn & view badges  
✅ Ask expert questions  
✅ Update profile & settings  
✅ Organisation job posting  
✅ Recruiter candidate review  
✅ Admin certificate approvals  

## ⚠️ Known Limitations

- 2FA toggle exists but SMS/email verification not implemented
- Resume PDFs download instead of preview in browser
- Email addresses not verified on signup
- Premium features UI only (no payment processing)
- Job listings not searchable/filterable

## 👨‍💼 Supervisor

Dr Cesar Sanin  
Excelsia University College

## 📅 Submission

**Deadline:** July 31, 2026  
**Status:** Ready for submission  
**Local Deployment:** XAMPP required

---

**SmartRecruit © 2026 — Capstone Project**
