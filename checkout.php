<?php
ob_start(); // header.php පස්සේ redirect කරන්න පුළුවන් වෙන්න
$page_title = 'Checkout';
require_once 'includes/header.php';
require_once 'includes/paycorp-client.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$pdo = getDB();

// ---- Login check (ඔයාගේ login session key එක වෙනස් නම් මෙතන හදන්න) ----
$userId = $_SESSION['user_id'] ?? null;
$slug = $_GET['slug'] ?? ($_POST['slug'] ?? '');
if (!$userId) {
    header('Location: login.php?redirect=' . urlencode('checkout.php?slug=' . $slug));
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM courses WHERE slug = ? AND is_published = 1 LIMIT 1");
$stmt->execute([$slug]);
$course = $stmt->fetch();
if (!$course) { header('Location: courses.php'); exit; }

$price = (float)$course['price'];

$st = $pdo->prepare("SELECT order_no FROM course_orders WHERE user_id=? AND course_id=? AND status='paid' LIMIT 1");
$st->execute([$userId, $course['id']]);
$alreadyPaid = $st->fetch();

$u = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$u->execute([$userId]);
$user = $u->fetch() ?: [];

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

$errors = [];
$form = [
    'name'  => $user['name'] ?? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
    'email' => $user['email'] ?? '',
    'phone' => $user['phone'] ?? '',
];

if (!$alreadyPaid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $k => $_) $form[$k] = trim($_POST[$k] ?? '');

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) $errors[] = 'Session expired. Please refresh and try again.';
    if ($form['name'] === '') $errors[] = 'Please enter your name.';
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
    if (!preg_match('/^[0-9+\-\s]{9,15}$/', $form['phone'])) $errors[] = 'Please enter a valid phone number.';
    // ★ Checkbox - server side validation
    if (empty($_POST['agree_terms'])) $errors[] = 'You must agree to the Terms & Conditions to continue.';

    if (!$errors) {
        $orderNo = 'SW' . date('ymd') . strtoupper(bin2hex(random_bytes(4)));
        $isFree  = $price <= 0;

        $ins = $pdo->prepare("INSERT INTO course_orders
            (order_no,user_id,course_id,amount,currency,status,billing_name,billing_email,billing_phone,terms_accepted,terms_accepted_at,paid_at)
            VALUES (?,?,?,?,?,?,?,?,?,1,NOW(),?)");
        $ins->execute([
            $orderNo, $userId, $course['id'], $price, PAYCORP_CURRENCY,
            $isFree ? 'paid' : 'pending',
            $form['name'], $form['email'], $form['phone'],
            $isFree ? date('Y-m-d H:i:s') : null,
        ]);

        if ($isFree) {
            header('Location: payment-success.php?order=' . urlencode($orderNo));
            exit;
        }

        try {
            $init = paycorpInit($orderNo, $price, PAYCORP_BASE_URL . '/payment-return.php', mb_substr($course['title'], 0, 100));
            if (empty($init['paymentPageUrl']) || empty($init['reqid'])) throw new Exception('No payment page URL returned');

            $pdo->prepare("UPDATE course_orders SET paycorp_reqid=? WHERE order_no=?")->execute([$init['reqid'], $orderNo]);
            header('Location: ' . $init['paymentPageUrl']);
            exit;
        } catch (Exception $ex) {
            error_log('[Paycorp init] ' . $ex->getMessage());
            $pdo->prepare("UPDATE course_orders SET status='failed' WHERE order_no=?")->execute([$orderNo]);
            $errors[] = 'Could not start the payment. Please try again in a moment.';
            if (PAYCORP_DEBUG) $errors[] = 'DEBUG: ' . $ex->getMessage();
        }
    }
}
?>
<style>
.co-card{background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.07);padding:24px}

/* ★ Course image - crop නොවී සම්පූර්ණ image එකම පෙන්නනවා */
.co-img-wrap{
    background:linear-gradient(135deg,#f8f9fa,#eef0f3);
    border:1px solid #e9ecef;
    border-radius:12px;
    padding:8px;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
}
.co-img-wrap img{
    display:block;
    width:100%;
    height:auto;          /* image එකේ original ratio එක තියාගන්නවා */
    max-height:340px;     /* ඉතා උස image වලට සීමාවක් */
    object-fit:contain;   /* කැපෙන්නේ නැතුව සම්පූර්ණයෙන්ම පෙන්නනවා */
    border-radius:8px;
}

.co-total{font-size:1.5rem;font-weight:700;color:#E30613}
.co-terms{background:#f8f9fa;border:1px solid #e5e5e5;border-radius:10px;padding:14px}
.co-terms input{width:18px;height:18px;margin-top:3px}
</style>

<section class="page-header"><div class="container position-relative"><h1>Checkout</h1>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="index.php">Home</a></li><li class="breadcrumb-item"><a href="courses.php">Courses</a></li><li class="breadcrumb-item active">Checkout</li></ol></nav></div></section>

<section class="section"><div class="container">
<?php if ($alreadyPaid): ?>
    <div class="co-card text-center py-5">
        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
        <h4>You have already enrolled in this class</h4>
        <a href="courses.php" class="btn btn-primary mt-2">Back to Classes</a>
    </div>
<?php else: ?>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="co-card">
            <h5 class="mb-3"><i class="fas fa-user me-2"></i>Your Details</h5>
            <?php if ($errors): ?>
                <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $er) echo '<li>' . e($er) . '</li>'; ?></ul></div>
            <?php endif; ?>
            <form method="POST" novalidate>
                <input type="hidden" name="csrf" value="<?php echo e($_SESSION['csrf']); ?>">
                <input type="hidden" name="slug" value="<?php echo e($course['slug']); ?>">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" value="<?php echo e($form['name']); ?>" required></div>
                    <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?php echo e($form['email']); ?>" required></div>
                    <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" placeholder="07XXXXXXXX" value="<?php echo e($form['phone']); ?>" required></div>
                </div>

                <!-- ★ Terms checkbox -->
                <div class="co-terms mt-4 d-flex gap-2">
                    <input type="checkbox" id="agree_terms" name="agree_terms" value="1" required <?php echo !empty($_POST['agree_terms']) ? 'checked' : ''; ?>>
                    <label for="agree_terms" class="small mb-0">
                        I have read and agree to the <a href="terms.php" target="_blank">Terms &amp; Conditions</a> and
                        <a href="terms.php#refund" target="_blank">Refund Policy</a>.
                    </label>
                </div>

                <button type="submit" id="payBtn" class="btn btn-primary btn-lg w-100 mt-4" disabled>
                    <i class="fas fa-lock me-2"></i><?php echo $price > 0 ? 'Pay ' . e(formatPrice($price)) . ' Securely' : 'Enroll for Free'; ?>
                </button>
                <p class="text-center text-muted small mt-2 mb-0"><i class="fas fa-shield-alt me-1"></i>Secure card payment via DFCC Bank (Paycorp). Card details are entered on the bank's secure page, not on our site.</p>
            </form>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="co-card co-summary">
            <h5 class="mb-3"><i class="fas fa-shopping-cart me-2"></i>Order Summary</h5>
            <?php $img = imageUrl('courses', $course['thumbnail'] ?? null); if ($img): ?>
                <div class="co-img-wrap mb-3">
                    <img src="<?php echo e($img); ?>" alt="<?php echo e($course['title']); ?>" loading="lazy">
                </div>
            <?php endif; ?>
            <h6><?php echo e($course['title']); ?></h6>
            <?php if (!empty($course['duration'])): ?><p class="text-muted small mb-2"><i class="far fa-clock"></i> <?php echo e($course['duration']); ?></p><?php endif; ?>
            <hr>
            <div class="d-flex justify-content-between align-items-center"><span>Total</span><span class="co-total"><?php echo formatPrice($price); ?></span></div>
        </div>
    </div>
</div>
<script>
(function(){
  var cb=document.getElementById('agree_terms'), btn=document.getElementById('payBtn');
  function sync(){ btn.disabled=!cb.checked; }
  cb.addEventListener('change',sync); sync();
})();
</script>
<?php endif; ?>
</div></section>
<?php require_once 'includes/footer.php'; ?>