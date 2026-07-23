-- add_admin_and_pending_job.sql
-- Seeds: (1) the admin account, (2) one pending job so the
-- approval workflow (FR16) can be demonstrated live.
-- Run: /Applications/XAMPP/xamppfiles/bin/mysql -u root smartrecruit < ~/Downloads/add_admin_and_pending_job.sql

USE smartrecruit;

-- Admin login: admin@smartrecruit.test / Admin123!
INSERT INTO users (email, password_hash, role, is_verified) VALUES
('admin@smartrecruit.test',
 '$2y$10$es5gPDTBcNqJQBWpHdM12e4alb6.UAikcqu7rC1zFKy9TGXPpWL0i',
 'admin', 1);

-- A second job, pending approval (posted by the demo organisation)
INSERT INTO job_postings (org_id, title, description, job_type, salary_min, salary_max, deadline, status)
VALUES (1, 'Junior Systems Administrator',
        'Entry-level sysadmin: user accounts, patching, backups, monitoring.',
        'full_time', 65000, 75000, '2026-09-15', 'pending_approval');

INSERT INTO job_skills (job_id, skill_id, importance)
SELECT jp.job_id, s.skill_id, 'essential'
FROM job_postings jp, skills s
WHERE jp.title = 'Junior Systems Administrator'
  AND s.skill_name IN ('Windows Administration', 'Networking', 'Active Directory');
