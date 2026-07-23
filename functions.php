<?php
/**
 * functions.php — SmartRecruit shared functions (Week 2)
 * Author: Deepak Bhandari (2443463047)
 */

/**
 * calculateMatchScore — FR03, the platform's core feature.
 *
 * Formula (from the Capstone A report):
 *   (matching_skills / total_job_skills) * 100, stored as DECIMAL(5,2)
 *
 * "Matching" = skills that appear in BOTH the student's profile
 * (student_skills) and the job's requirements (job_skills).
 *
 * @return float score 0.00–100.00 (0.0 if the job lists no skills)
 */
function calculateMatchScore(PDO $pdo, int $studentId, int $jobId): float
{
    // Total skills the job requires
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM job_skills WHERE job_id = ?');
    $stmt->execute([$jobId]);
    $totalJobSkills = (int)$stmt->fetchColumn();

    if ($totalJobSkills === 0) {
        return 0.0;   // no requirements defined — cannot match
    }

    // Skills present in BOTH lists (the overlap)
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM job_skills js
         JOIN student_skills ss
              ON ss.skill_id = js.skill_id AND ss.student_id = ?
         WHERE js.job_id = ?'
    );
    $stmt->execute([$studentId, $jobId]);
    $matching = (int)$stmt->fetchColumn();

    return round(($matching / $totalJobSkills) * 100, 2);
}

/**
 * getStudentByUserId — fetch the students row for a logged-in user.
 * Returns the row array, or false if the profile is not created yet.
 */
function getStudentByUserId(PDO $pdo, int $userId)
{
    $stmt = $pdo->prepare('SELECT * FROM students WHERE user_id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetch();
}
