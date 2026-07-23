<?php
/**
 * record_payment.php — SmartRecruit payment recording (T10)
 * Author: Deepak Bhandari (2443463047)
 *
 * Called by premium.php after PayPal (sandbox) captures the order.
 * Records the payment, creates the subscription, and upgrades the
 * student — all in ONE transaction: either everything is saved,
 * or nothing is.
 *
 * Returns JSON: {"ok":true} or {"ok":false,"error":"..."}
 */

session_start();
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json');

function fail(string $msg): void {
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    fail('Not authorised.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('POST required.');
}

$input   = json_decode(file_get_contents('php://input'), true) ?? [];
$orderId = substr(trim($input['order_id'] ?? ''), 0, 100);
if ($orderId === '') {
    fail('Missing PayPal order id.');
}

$student = getStudentByUserId($pdo, $_SESSION['user_id']);
if (!$student) {
    fail('No student profile.');
}

try {
    $pdo->beginTransaction();

    // 1. Payment record (gateway reference = PayPal order id)
    $stmt = $pdo->prepare(
        "INSERT INTO payments (payer_id, amount, currency, gateway, gateway_ref, status)
         VALUES (?, 49.00, 'AUD', 'PayPal', ?, 'completed')"
    );
    $stmt->execute([$_SESSION['user_id'], $orderId]);

    // 2. Subscription: one month from today
    $stmt = $pdo->prepare(
        "INSERT INTO subscriptions (student_id, plan, amount_paid, start_date, end_date, payment_ref, status)
         VALUES (?, 'premium_monthly', 49.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH), ?, 'active')"
    );
    $stmt->execute([$student['student_id'], $orderId]);

    // 3. Premium flag on the student
    $pdo->prepare('UPDATE students SET is_premium = 1 WHERE student_id = ?')
        ->execute([$student['student_id']]);

    $pdo->commit();
    $_SESSION['flash'] = '⭐ Premium activated — welcome aboard!';
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    fail('Database error: ' . $e->getMessage());
}
