<?php
$page_title = 'Payment';
require_once 'includes/header.php';
require_once 'includes/paycorp-client.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$pdo = getDB();
$orderNo = $_GET['order'] ?? '';

$q = "SELECT o.*, c.title, c.slug FROM course_orders o JOIN courses c ON c.id=o.course_id WHERE o.order_no=? AND o.user_id=? LIMIT 1";
$st = $pdo->prepare($q);
$st->execute([$orderNo, $_SESSION['user_id'] ?? 0]);
$o = $st->fetch();

// Pending + reqid තියෙනවා නම් (return page එකේ network error වුණා නම්) ආයෙත් verify කරනවා
if ($o && $o['status'] === 'pending' && !empty($o['paycorp_reqid'])) {
    try {
        $r = paycorpComplete($o['paycorp_reqid']);
        $code = $r['responseCode'] ?? '';
        $ok = $code === '00'
            && (int)($r['transactionAmount']['paymentAmount'] ?? 0) === (int)round($o['amount'] * 100)
            && ($r['transactionAmount']['currency'] ?? '') === $o['currency'];
        if ($ok) {
            $pdo->prepare("UPDATE course_orders SET status='paid', paid_at=NOW(), txn_reference=?, response_code=? WHERE id=? AND status='pending'")
                ->execute([$r['txnReference'] ?? null, $code, $o['id']]);
        } elseif ($code !== '') {
            $pdo->prepare("UPDATE course_orders SET status='failed', response_code=? WHERE id=? AND status='pending'")->execute([$code, $o['id']]);
        }
        $st->execute([$orderNo, $_SESSION['user_id'] ?? 0]);
        $o = $st->fetch();
    } catch (Exception $ex) { error_log('[Paycorp recheck] ' . $ex->getMessage()); }
}

// Paid නම් enrollment එක තියෙනවාද බලලා නැත්නම් දානවා
if ($o && $o['status'] === 'paid') enrollStudent($pdo, $o['user_id'], $o['course_id']);
?>
<section class="page-header"><div class="container"><h1>Payment Status</h1></div></section>
<section class="section"><div class="container"><div class="row justify-content-center"><div class="col-lg-6">
<div class="card shadow-sm border-0"><div class="card-body text-center p-5">
<?php if (!$o): ?>
    <i class="fas fa-question-circle fa-3x text-muted mb-3"></i><h4>Order not found</h4>
    <a href="courses.php" class="btn btn-primary mt-3">Back to Classes</a>
<?php elseif ($o['status'] === 'paid'): ?>
    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
    <h3>Payment Successful!</h3>
    <p class="text-muted">You are now enrolled in <strong><?php echo e($o['title']); ?></strong>.</p>
    <p class="small">Order No: <strong><?php echo e($o['order_no']); ?></strong>
        <?php if ($o['txn_reference']): ?><br>Transaction Ref: <strong><?php echo e($o['txn_reference']); ?></strong><?php endif; ?>
        <br>Amount: <?php echo formatPrice($o['amount']); ?></p>
    <a href="courses.php" class="btn btn-primary mt-2">Browse More Classes</a>
<?php elseif ($o['status'] === 'pending'): ?>
    <i class="fas fa-hourglass-half fa-4x text-warning mb-3"></i>
    <h3>Confirming your payment...</h3>
    <p class="text-muted">තවම confirm වුණේ නෑ. තත්පර කීපයකින් refresh කරන්න.</p>
    <p class="small">Order No: <strong><?php echo e($o['order_no']); ?></strong></p>
    <a href="payment-success.php?order=<?php echo e($o['order_no']); ?>" class="btn btn-outline-primary">Refresh</a>
<?php else: ?>
    <i class="fas fa-times-circle fa-4x text-danger mb-3"></i>
    <h3>Payment Not Completed</h3>
    <p class="text-muted">ගෙවීම සාර්ථක වුණේ නෑ. ඔයාගේ card එකෙන් මුදල් අය වුණා නම් අපිට දන්වන්න (Order No: <strong><?php echo e($o['order_no']); ?></strong>).</p>
    <a href="checkout.php?slug=<?php echo e($o['slug']); ?>" class="btn btn-primary mt-2">Try Again</a>
<?php endif; ?>
</div></div></div></div></div></section>
<?php require_once 'includes/footer.php'; ?>