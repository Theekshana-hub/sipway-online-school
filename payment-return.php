<?php
// Paycorp payment page එකෙන් user ආපහු එන තැන. HTML output නෑ, verify කරලා redirect විතරයි.
// ⚠ header.php මෙතන include කරන්න එපා (output යනවා). getDB() තියෙන file එක විතරක් include කරන්න.
require_once 'includes/config.php';          // <-- getDB() තියෙන file එක. නම වෙනස් නම් හදන්න
require_once 'includes/paycorp-client.php';

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