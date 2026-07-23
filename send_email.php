<?php
require_once 'email_config.php';

function sendStatusEmail($studentEmail, $studentName, $jobTitle, $status, $company) {
    $subject = "Application Status Update - SmartRecruit";
    
    if ($status === 'Shortlisted') {
        $message = "Hi $studentName,\n\n";
        $message .= "Great news! You have been SHORTLISTED for the position of $jobTitle at $company.\n\n";
        $message .= "The recruiter is interested in your application. Next steps will be communicated shortly.\n\n";
    } elseif ($status === 'Accepted') {
        $message = "Hi $studentName,\n\n";
        $message .= "Congratulations! You have been HIRED for the position of $jobTitle at $company.\n\n";
        $message .= "Welcome aboard! Please check your email for next steps.\n\n";
    } elseif ($status === 'Rejected') {
        $message = "Hi $studentName,\n\n";
        $message .= "Thank you for applying for the position of $jobTitle at $company.\n\n";
        $message .= "Unfortunately, we have decided to move forward with other candidates.\n\n";
        $message .= "We encourage you to apply for other opportunities.\n\n";
    } else {
        $message = "Hi $studentName,\n\n";
        $message .= "Your application status for $jobTitle at $company has been updated to: $status\n\n";
    }
    
    $message .= "Best regards,\nSmartRecruit Team";
    
    $headers = "From: " . MAIL_FROM_EMAIL . "\r\n";
    $headers .= "Reply-To: " . MAIL_FROM_EMAIL . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    $result = mail($studentEmail, $subject, $message, $headers);
    
    return $result;
}
?>
