<?php
function calculateProfileStrength($pdo, $studentId) {
    $stmt = $pdo->prepare('SELECT full_name, phone, university, degree, gpa, professional_summary, resume FROM students WHERE student_id = ?');
    $stmt->execute([$studentId]);
    $student = $stmt->fetch();
    
    if (!$student) return 0;
    
    $fields = ['full_name', 'phone', 'university', 'degree', 'gpa', 'professional_summary', 'resume'];
    $completed = 0;
    
    foreach ($fields as $field) {
        if (!empty($student[$field])) {
            $completed++;
        }
    }
    
    $strength = round(($completed / count($fields)) * 100);
    
    // Update database
    $pdo->prepare('UPDATE students SET profile_score = ? WHERE student_id = ?')->execute([$strength, $studentId]);
    
    return $strength;
}
?>