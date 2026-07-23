-- Smart Recruit Relational Database Schema (3NF)
-- Designed for ICT307A - IT Capstone Project A
-- Target Database: MySQL 8.0

CREATE DATABASE IF NOT EXISTS `smart_recruit` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `smart_recruit`;

-- Disable foreign key checks to make table recreation easier
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `admin_logs`;
DROP TABLE IF EXISTS `job_skills`;
DROP TABLE IF EXISTS `student_skills`;
DROP TABLE IF EXISTS `skills`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `training_sessions`;
DROP TABLE IF EXISTS `certificates`;
DROP TABLE IF EXISTS `commissions`;
DROP TABLE IF EXISTS `referrals`;
DROP TABLE IF EXISTS `subscriptions`;
DROP TABLE IF EXISTS `recommendations`;
DROP TABLE IF EXISTS `applications`;
DROP TABLE IF EXISTS `job_postings`;
DROP TABLE IF EXISTS `organisations`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. users Table (Central Authentication)
CREATE TABLE `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(150) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'organisation', 'admin') NOT NULL,
  `is_verified` TINYINT(1) DEFAULT 0 NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. students Table (Student Academic Profile)
CREATE TABLE `students` (
  `student_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNIQUE NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `university` VARCHAR(150) NOT NULL,
  `degree` VARCHAR(150) NOT NULL,
  `gpa` DECIMAL(3,2) NOT NULL,
  `resume_url` VARCHAR(255) DEFAULT NULL,
  `profile_score` INT DEFAULT 0 NOT NULL,
  `is_premium` TINYINT(1) DEFAULT 0 NOT NULL,
  CONSTRAINT `fk_student_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. organisations Table (Hiring Company Profile)
CREATE TABLE `organisations` (
  `org_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNIQUE NOT NULL,
  `org_name` VARCHAR(150) NOT NULL,
  `abn` VARCHAR(50) UNIQUE NOT NULL,
  `industry` VARCHAR(100) NOT NULL,
  `location` VARCHAR(100) NOT NULL,
  `is_partner` TINYINT(1) DEFAULT 0 NOT NULL,
  CONSTRAINT `fk_org_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. job_postings Table (Job Ads Posted by Organisations)
CREATE TABLE `job_postings` (
  `job_id` INT AUTO_INCREMENT PRIMARY KEY,
  `org_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `type` ENUM('Full-time', 'Part-time', 'Contract', 'Internship') NOT NULL,
  `salary_min` DECIMAL(10,2) NOT NULL,
  `salary_max` DECIMAL(10,2) NOT NULL,
  `deadline` DATE NOT NULL,
  `status` ENUM('pending', 'active', 'closed') DEFAULT 'pending' NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_job_org` FOREIGN KEY (`org_id`) REFERENCES `organisations` (`org_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. applications Table (Student Job Applications)
CREATE TABLE `applications` (
  `app_id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `job_id` INT NOT NULL,
  `match_score` DECIMAL(5,2) DEFAULT 0.00 NOT NULL,
  `status` ENUM('pending', 'shortlisted', 'rejected', 'hired') DEFAULT 'pending' NOT NULL,
  `is_referred` TINYINT(1) DEFAULT 0 NOT NULL,
  `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_student_job` (`student_id`, `job_id`),
  CONSTRAINT `fk_app_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_app_job` FOREIGN KEY (`job_id`) REFERENCES `job_postings` (`job_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. recommendations Table (JSON-based Career Roadmaps for Premium Students)
CREATE TABLE `recommendations` (
  `rec_id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `target_role` VARCHAR(150) NOT NULL,
  `career_roadmap` JSON DEFAULT NULL,
  `skill_gaps` JSON DEFAULT NULL,
  `completion_pct` DECIMAL(5,2) DEFAULT 0.00 NOT NULL,
  CONSTRAINT `fk_rec_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. subscriptions Table (Premium Membership Records)
CREATE TABLE `subscriptions` (
  `sub_id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `plan` ENUM('Free', 'Premium') DEFAULT 'Free' NOT NULL,
  `amount_paid` DECIMAL(10,2) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `payment_ref` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('active', 'expired', 'cancelled') DEFAULT 'active' NOT NULL,
  CONSTRAINT `fk_sub_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. referrals Table (Admin Referrals to Partners)
CREATE TABLE `referrals` (
  `referral_id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `org_id` INT NOT NULL,
  `job_id` INT NOT NULL,
  `admin_id` INT NOT NULL,
  `referred_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('referred', 'hired', 'rejected') DEFAULT 'referred' NOT NULL,
  CONSTRAINT `fk_ref_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ref_org` FOREIGN KEY (`org_id`) REFERENCES `organisations` (`org_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ref_job` FOREIGN KEY (`job_id`) REFERENCES `job_postings` (`job_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ref_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. commissions Table (Invoices for Successfully Recruited Students)
CREATE TABLE `commissions` (
  `commission_id` INT AUTO_INCREMENT PRIMARY KEY,
  `referral_id` INT NOT NULL,
  `org_id` INT NOT NULL,
  `first_year_salary` DECIMAL(10,2) NOT NULL,
  `commission_rate` DECIMAL(4,2) NOT NULL,
  `amount_due` DECIMAL(10,2) NOT NULL,
  `invoice_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `payment_status` ENUM('pending', 'paid', 'disputed') DEFAULT 'pending' NOT NULL,
  CONSTRAINT `fk_comm_ref` FOREIGN KEY (`referral_id`) REFERENCES `referrals` (`referral_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comm_org` FOREIGN KEY (`org_id`) REFERENCES `organisations` (`org_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. certificates Table (Credentials Issued by Admin)
CREATE TABLE `certificates` (
  `cert_id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `cert_name` VARCHAR(150) NOT NULL,
  `issued_by` VARCHAR(150) NOT NULL,
  `issue_date` DATE NOT NULL,
  `verify_code` VARCHAR(50) UNIQUE NOT NULL,
  CONSTRAINT `fk_cert_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. training_sessions Table (Available Workshops from Partners)
CREATE TABLE `training_sessions` (
  `session_id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `partner_name` VARCHAR(150) NOT NULL,
  `mode` ENUM('online', 'face-to-face') NOT NULL,
  `session_date` DATETIME NOT NULL,
  `seats_total` INT NOT NULL,
  `seats_remaining` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. bookings Table (Workshop Seats Booked by Students)
CREATE TABLE `bookings` (
  `booking_id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `session_id` INT NOT NULL,
  `amount_paid` DECIMAL(10,2) NOT NULL,
  `status` ENUM('confirmed', 'cancelled', 'refunded') DEFAULT 'confirmed' NOT NULL,
  `attended` TINYINT(1) DEFAULT 0 NOT NULL,
  UNIQUE KEY `unique_student_session` (`student_id`, `session_id`),
  CONSTRAINT `fk_booking_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_session` FOREIGN KEY (`session_id`) REFERENCES `training_sessions` (`session_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. skills Table (Predefined Normalized Skills Directory)
CREATE TABLE `skills` (
  `skill_id` INT AUTO_INCREMENT PRIMARY KEY,
  `skill_name` VARCHAR(100) UNIQUE NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `level` ENUM('Beginner', 'Intermediate', 'Advanced') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. student_skills Table (Skill Inventory of Students)
CREATE TABLE `student_skills` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL,
  `skill_id` INT NOT NULL,
  `proficiency` ENUM('Beginner', 'Intermediate', 'Advanced') NOT NULL,
  `verified` TINYINT(1) DEFAULT 0 NOT NULL,
  CONSTRAINT `fk_studsk_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_studsk_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. job_skills Table (Skill Requirements for Job Postings)
CREATE TABLE `job_skills` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `job_id` INT NOT NULL,
  `skill_id` INT NOT NULL,
  `importance` ENUM('Essential', 'Desirable') NOT NULL,
  CONSTRAINT `fk_jobsk_job` FOREIGN KEY (`job_id`) REFERENCES `job_postings` (`job_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jobsk_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. admin_logs Table (Administrative Audit Trail)
CREATE TABLE `admin_logs` (
  `log_id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT NOT NULL,
  `action` VARCHAR(255) NOT NULL,
  `target_table` VARCHAR(100) NOT NULL,
  `old_value` JSON DEFAULT NULL,
  `new_value` JSON DEFAULT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_log_admin` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17. payments Table (Payment Ledger and Audits)
CREATE TABLE `payments` (
  `payment_id` INT AUTO_INCREMENT PRIMARY KEY,
  `payer_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(10) DEFAULT 'AUD' NOT NULL,
  `gateway` VARCHAR(50) DEFAULT 'PayPal' NOT NULL,
  `gateway_ref` VARCHAR(100) UNIQUE NOT NULL,
  `status` ENUM('pending', 'success', 'failed') DEFAULT 'pending' NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_pay_payer` FOREIGN KEY (`payer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
