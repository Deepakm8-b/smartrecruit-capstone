-- Guest applications table — no account required
-- Separate from the main applications table to preserve FK integrity
USE smartrecruit;

CREATE TABLE IF NOT EXISTS guest_applications (
    guest_app_id  INT AUTO_INCREMENT PRIMARY KEY,
    job_id        INT NOT NULL,
    full_name     VARCHAR(120) NOT NULL,
    email         VARCHAR(255) NOT NULL,
    phone         VARCHAR(20),
    cover_letter  TEXT,
    resume_text   TEXT,
    applied_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status        ENUM('submitted','reviewed','rejected','shortlisted') NOT NULL DEFAULT 'submitted',
    FOREIGN KEY (job_id) REFERENCES job_postings(job_id) ON DELETE CASCADE
) ENGINE=InnoDB;
