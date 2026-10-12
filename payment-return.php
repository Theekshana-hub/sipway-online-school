<?php
// Paycorp payment page එකෙන් user ආපහු එන තැන. HTML output නෑ, verify කරලා redirect විතරයි.
// header.php එක (getDB, session, helpers load කරන) buffer කරලා, HTML output එක ඉවත් කරනවා.
// ඒ නිසා config file එකේ නම දැනගන්න ඕන නෑ.
ob_start();
$page_title = 'Payment';
require_once 'includes/header.php';
ob_end_clean();
require_once 'includes/paycorp-client.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$reqid = trim($_GET['reqid'] ?? '');
if ($reqid === '') { header('Location: courses.php'); exit; }

$pdo = getDB();
$st = $pdo->prepare("SELECT * FROM course_orders WHERE paycorp_reqid = ? LIMIT 1");
$st->execute([$reqid]);
$order = $st->fetch();
if (!$order) { header('Location: courses.php'); exit; }

// Already processed (refresh / double return)
if ($order['status'] === 'pending') {
    try {
        $r = paycorpComplete($reqid);
        $code   = $r['responseCode'] ?? '';
        $paidC  = (int)($r['transactionAmount']['paymentAmount'] ?? 0);
        $curr   = $r['transactionAmount']['currency'] ?? '';
        $expect = (int)round($order['amount'] * 100);

        if ($code === '00' && $paidC === $expect && $curr === $order['currency']) {
            $pdo->prepare("UPDATE course_orders SET status='paid', paid_at=NOW(), txn_reference=?, response_code=? WHERE id=? AND status='pending'")
                ->execute([$r['txnReference'] ?? null, $code, $order['id']]);
            enrollStudent($pdo, $order['user_id'], $order['course_id']);
        } else {
            $pdo->prepare("UPDATE course_orders SET status='failed', response_code=? WHERE id=? AND status='pending'")
                ->execute([$code ?: 'ERR', $order['id']]);
            if ($code === '00') error_log("[Paycorp] amount/currency mismatch for {$order['order_no']}: $paidC $curr vs $expect");
        }
    } catch (Exception $ex) {
        // Network error: pending ලෙසම තියනවා, success page එක refresh කළාම ආයෙත් try කරන්න පුළුවන්
        error_log('[Paycorp complete] ' . $ex->getMessage());
    }
}
header('Location: payment-success.php?order=' . urlencode($order['order_no']));
exit;