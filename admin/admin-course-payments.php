<?php
require_once __DIR__.'/includes/admin-auth.php';
$pdo = getDB();

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
$st = $pdo->prepare("SELECT c.*, cat.name AS cat_name FROM courses c LEFT JOIN categories cat ON c.category_id=cat.id WHERE c.id=? LIMIT 1");
$st->execute([$id]);
$course = $st->fetch();
if (!$course) redirect('admin-courses.php');

$status = $_GET['status'] ?? ($_POST['f_status'] ?? '');
$q = trim($_GET['q'] ?? ($_POST['f_q'] ?? ''));
$allowed = ['pending','paid','failed','cancelled','chargedback'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $back = 'admin-course-payments.php?' . http_build_query(['id' => $id, 'status' => $status, 'q' => $q]);

    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Invalid request token. Please try again.'];
        redirect($back);
    }

    $action = $_POST['action'] ?? '';

    
    if ($action === 'edit_order') {
        $oid    = (int)($_POST['order_id'] ?? 0);
        $name   = trim($_POST['billing_name'] ?? '');
        $email  = trim($_POST['billing_email'] ?? '');
        $phone  = trim($_POST['billing_phone'] ?? '');
        $newSt  = $_POST['order_status'] ?? '';
        $txn    = trim($_POST['txn_reference'] ?? '');

        if (!in_array($newSt, $allowed, true)) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Invalid status selected.'];
            redirect($back);
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Please enter a valid email address.'];
            redirect($back);
        }

        try {
           
            $up = $pdo->prepare("UPDATE course_orders
                SET billing_name = ?, billing_email = ?, billing_phone = ?, status = ?, txn_reference = ?,
                    paid_at = CASE WHEN ? = 'paid' AND paid_at IS NULL THEN NOW() ELSE paid_at END
                WHERE id = ? AND course_id = ?");
            $up->execute([$name, $email, $phone, $newSt, $txn, $newSt, $oid, $id]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Payment record updated successfully.'];
        } catch (PDOException $ex) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Could not update this payment record.'];
        }
        redirect($back);
    }

    
    if ($action === 'delete_order') {
        $oid = (int)($_POST['order_id'] ?? 0);
        try {
            $del = $pdo->prepare("DELETE FROM course_orders WHERE id = ? AND course_id = ?");
            $del->execute([$oid, $id]);
            if ($del->rowCount() > 0) {
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Payment record deleted successfully.'];
            } else {
                $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'Payment record not found.'];
            }
        } catch (PDOException $ex) {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Could not delete this payment. It may be linked to other records.'];
        }
        redirect($back);
    }

    
    if ($action === 'delete_course') {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM course_orders WHERE course_id = ? AND status = 'paid'");
        $chk->execute([$id]);
        $paidCount = (int)$chk->fetchColumn();

        
        if ($paidCount > 0 && ($_POST['confirm_text'] ?? '') !== 'DELETE') {
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'This course has paid orders. Deletion was not confirmed.'];
            redirect($back);
        }
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM course_orders WHERE course_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM courses WHERE id = ?")->execute([$id]);
            $pdo->commit();
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Course deleted successfully.'];
            redirect('admin-courses.php');
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Could not delete this course. It may be linked to other records.'];
            redirect($back);
        }
    }

    redirect($back);
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$where = "o.course_id = ?";
$params = [$id];
if (in_array($status, $allowed, true)) { $where .= " AND o.status = ?"; $params[] = $status; }
if ($q !== '') {
    $where .= " AND (o.billing_name LIKE ? OR o.billing_email LIKE ? OR o.billing_phone LIKE ? OR o.order_no LIKE ? OR o.txn_reference LIKE ?)";
    for ($k = 0; $k < 5; $k++) $params[] = "%$q%";
}

$sql = "SELECT o.* FROM course_orders o WHERE $where ORDER BY o.created_at DESC";
$ps = $pdo->prepare($sql);
$ps->execute($params);
$orders = $ps->fetchAll();


if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="course-' . $id . '-payments-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Order No','Student Name','Email','Phone','Amount','Currency','Status','Txn Reference','Response Code','Terms Accepted','Created','Paid At']);
    foreach ($orders as $o) {
        fputcsv($out, [$o['order_no'],$o['billing_name'],$o['billing_email'],$o['billing_phone'],$o['amount'],$o['currency'],$o['status'],$o['txn_reference'],$o['response_code'],$o['terms_accepted']?'Yes':'No',$o['created_at'],$o['paid_at']]);
    }
    fclose($out);
    exit;
}


$sq = $pdo->prepare("SELECT
        SUM(status='paid') AS paid_cnt,
        SUM(status='pending') AS pending_cnt,
        SUM(status IN ('failed','cancelled')) AS failed_cnt,
        COALESCE(SUM(CASE WHEN status='paid' THEN amount END),0) AS revenue
    FROM course_orders WHERE course_id=?");
$sq->execute([$id]);
$stats = $sq->fetch();

$badge = ['paid'=>'bg-success','pending'=>'bg-warning text-dark','failed'=>'bg-danger','cancelled'=>'bg-secondary','chargedback'=>'bg-dark'];

$page_title = 'Course Details';
require_once 'includes/admin-header.php';
$img = function_exists('imageUrl') ? imageUrl('courses', $course['thumbnail'] ?? null) : null;
?>
<div class="mb-3"><a href="admin-courses.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Courses</a></div>

<?php if ($flash): ?>
<div class="alert alert-<?php echo e($flash['type']); ?> alert-dismissible fade show" role="alert">
    <?php echo e($flash['msg']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<style>

.cd-img-wrap{
    background:linear-gradient(135deg,#f8f9fa,#eef0f3);
    border:1px solid #e9ecef;
    border-radius:12px;
    padding:8px;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden;
}
.cd-img-wrap img{
    display:block;
    width:100%;
    height:auto;          
    max-height:320px;    
    object-fit:contain;   
    border-radius:8px;
}
</style>


<div class="table-card mb-3">
<div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="fas fa-book me-2"></i>Course Details</span>
    <div class="d-flex gap-2">
        <a href="admin-edit-course.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit me-1"></i>Edit</a>
        <form method="POST" class="d-inline" onsubmit="return confirmCourseDelete(this, <?php echo (int)$stats['paid_cnt']; ?>);">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
            <input type="hidden" name="action" value="delete_course">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <input type="hidden" name="confirm_text" value="">
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash me-1"></i>Delete Course</button>
        </form>
    </div>
</div>
<div class="p-3"><div class="row g-3">
    <?php if ($img): ?><div class="col-md-3"><div class="cd-img-wrap"><img src="<?php echo e($img); ?>" alt="<?php echo e($course['title']); ?>" loading="lazy"></div></div><?php endif; ?>
    <div class="<?php echo $img ? 'col-md-9' : 'col-12'; ?>">
        <h4 class="mb-1"><?php echo e($course['title']); ?>
            <?php if (!empty($course['is_featured'])): ?><span class="badge bg-warning text-dark fs-6">Featured</span><?php endif; ?>
            <span class="badge <?php echo !empty($course['is_published']) ? 'bg-success' : 'bg-secondary'; ?> fs-6"><?php echo !empty($course['is_published']) ? 'Live' : 'Draft'; ?></span>
        </h4>
        <?php if (!empty($course['title_si'])): ?><p class="text-muted mb-2"><?php echo e($course['title_si']); ?></p><?php endif; ?>
        <?php if (!empty($course['short_desc'])): ?><p><?php echo e($course['short_desc']); ?></p><?php endif; ?>
        <div class="row small">
            <div class="col-6 col-lg-3 mb-2"><div class="text-muted">Category</div><strong><?php echo e($course['cat_name'] ?? '-'); ?></strong></div>
            <div class="col-6 col-lg-3 mb-2"><div class="text-muted">Level</div><strong><?php echo e($course['level'] ?? '-'); ?></strong></div>
            <div class="col-6 col-lg-3 mb-2"><div class="text-muted">Duration</div><strong><?php echo e($course['duration'] ?? '-'); ?></strong></div>
            <div class="col-6 col-lg-3 mb-2"><div class="text-muted">Price</div><strong><?php echo formatPrice($course['price']); ?></strong></div>
            <div class="col-6 col-lg-3 mb-2"><div class="text-muted">Slug</div><strong><?php echo e($course['slug'] ?? '-'); ?></strong></div>
            <div class="col-6 col-lg-3 mb-2"><div class="text-muted">Created</div><strong><?php echo !empty($course['created_at']) ? e(date('d M Y', strtotime($course['created_at']))) : '-'; ?></strong></div>
        </div>
    </div>
</div></div></div>


<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="table-card p-3"><div class="text-muted small">Paid Students</div><div class="fs-4 fw-bold text-success"><?php echo (int)$stats['paid_cnt']; ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="table-card p-3"><div class="text-muted small">Revenue</div><div class="fs-4 fw-bold"><?php echo formatPrice($stats['revenue']); ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="table-card p-3"><div class="text-muted small">Pending</div><div class="fs-4 fw-bold text-warning"><?php echo (int)$stats['pending_cnt']; ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="table-card p-3"><div class="text-muted small">Failed / Cancelled</div><div class="fs-4 fw-bold text-danger"><?php echo (int)$stats['failed_cnt']; ?></div></div></div>
</div>


<div class="table-card">
<div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span><i class="fas fa-credit-card me-2"></i>Payments (<?php echo count($orders); ?>)</span>
    <form method="GET" class="d-flex flex-wrap gap-2">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <select name="status" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
            <option value="">All statuses</option>
            <?php foreach ($allowed as $s): ?><option value="<?php echo $s; ?>" <?php echo $status===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option><?php endforeach; ?>
        </select>
        <input type="text" name="q" class="form-control form-control-sm" style="width:220px" placeholder="Name / email / phone / order..." value="<?php echo e($q); ?>">
        <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
        <a href="admin-course-payments.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
        <a href="?<?php echo e(http_build_query(['id'=>$id,'status'=>$status,'q'=>$q,'export'=>1])); ?>" class="btn btn-sm btn-success"><i class="fas fa-file-csv me-1"></i>Export CSV</a>
    </form>
</div>
<div class="table-responsive"><table class="table table-hover mb-0 align-middle">
<thead class="table-light"><tr><th>#</th><th>Student</th><th>Contact</th><th>Order No</th><th>Amount</th><th>Status</th><th>Txn Ref</th><th>Date</th><th class="text-end">Action</th></tr></thead>
<tbody>
<?php foreach ($orders as $i => $o): ?>
<tr>
    <td><?php echo $i+1; ?></td>
    <td><strong><?php echo e($o['billing_name'] ?: '-'); ?></strong><div class="small text-muted">User ID: <?php echo (int)$o['user_id']; ?></div></td>
    <td class="small"><?php echo e($o['billing_email'] ?: '-'); ?><br><?php echo e($o['billing_phone'] ?: '-'); ?></td>
    <td class="small"><code><?php echo e($o['order_no']); ?></code></td>
    <td><?php echo formatPrice($o['amount']); ?></td>
    <td><span class="badge <?php echo $badge[$o['status']] ?? 'bg-secondary'; ?>"><?php echo e(ucfirst($o['status'])); ?></span>
        <?php if ($o['status']==='failed' && $o['response_code']): ?><div class="small text-muted">Code: <?php echo e($o['response_code']); ?></div><?php endif; ?></td>
    <td class="small"><?php echo e($o['txn_reference'] ?: '-'); ?></td>
    <td class="small"><?php echo e(date('d M Y, h:i A', strtotime($o['paid_at'] ?: $o['created_at']))); ?>
        <?php if ($o['terms_accepted']): ?><div class="text-success"><i class="fas fa-check-square me-1"></i>Terms accepted</div><?php endif; ?></td>
    <td class="text-end text-nowrap">
        <button type="button" class="btn btn-sm btn-outline-primary btn-edit-order" title="Edit payment"
            data-bs-toggle="modal" data-bs-target="#editOrderModal"
            data-order-id="<?php echo (int)$o['id']; ?>"
            data-order-no="<?php echo e($o['order_no']); ?>"
            data-name="<?php echo e($o['billing_name']); ?>"
            data-email="<?php echo e($o['billing_email']); ?>"
            data-phone="<?php echo e($o['billing_phone']); ?>"
            data-status="<?php echo e($o['status']); ?>"
            data-txn="<?php echo e($o['txn_reference']); ?>"><i class="fas fa-edit"></i></button>
        <?php
            $warn = $o['status'] === 'paid'
                ? "WARNING: This is a PAID order (" . $o['order_no'] . ").\nDeleting it will remove the payment record and affect revenue totals.\n\nDelete anyway?"
                : "Delete order " . $o['order_no'] . "?\nThis cannot be undone.";
        ?>
        <form method="POST" class="d-inline" onsubmit="return confirm(<?php echo e(json_encode($warn)); ?>);">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
            <input type="hidden" name="action" value="delete_order">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <input type="hidden" name="order_id" value="<?php echo (int)$o['id']; ?>">
            <input type="hidden" name="f_status" value="<?php echo e($status); ?>">
            <input type="hidden" name="f_q" value="<?php echo e($q); ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete payment"><i class="fas fa-trash"></i></button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
<?php if (empty($orders)): ?><tr><td colspan="9" class="text-center text-muted py-4">No payments found for this course.</td></tr><?php endif; ?>
</tbody></table></div></div>

<div class="modal fade" id="editOrderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
      <input type="hidden" name="action" value="edit_order">
      <input type="hidden" name="id" value="<?php echo $id; ?>">
      <input type="hidden" name="order_id" id="eo_order_id" value="">
      <input type="hidden" name="f_status" value="<?php echo e($status); ?>">
      <input type="hidden" name="f_q" value="<?php echo e($q); ?>">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Payment <code id="eo_order_no" class="fs-6"></code></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label small">Student Name</label>
            <input type="text" name="billing_name" id="eo_name" class="form-control" maxlength="150">
        </div>
        <div class="mb-3">
            <label class="form-label small">Email</label>
            <input type="email" name="billing_email" id="eo_email" class="form-control" maxlength="150">
        </div>
        <div class="mb-3">
            <label class="form-label small">Phone</label>
            <input type="text" name="billing_phone" id="eo_phone" class="form-control" maxlength="30">
        </div>
        <div class="mb-3">
            <label class="form-label small">Status</label>
            <select name="order_status" id="eo_status" class="form-select">
                <?php foreach ($allowed as $s): ?><option value="<?php echo $s; ?>"><?php echo ucfirst($s); ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="mb-1">
            <label class="form-label small">Txn Reference</label>
            <input type="text" name="txn_reference" id="eo_txn" class="form-control" maxlength="100">
        </div>
        <div class="form-text">Amount and order number cannot be changed here.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>

document.querySelectorAll('.btn-edit-order').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('eo_order_id').value = btn.dataset.orderId || '';
        document.getElementById('eo_order_no').textContent = btn.dataset.orderNo || '';
        document.getElementById('eo_name').value = btn.dataset.name || '';
        document.getElementById('eo_email').value = btn.dataset.email || '';
        document.getElementById('eo_phone').value = btn.dataset.phone || '';
        document.getElementById('eo_status').value = btn.dataset.status || 'pending';
        document.getElementById('eo_txn').value = btn.dataset.txn || '';
    });
});


function confirmCourseDelete(form, paidCount) {
    if (paidCount > 0) {
        var t = prompt(
            'WARNING: This course has ' + paidCount + ' paid order(s).\n' +
            'Deleting it will permanently remove ALL its payment records and revenue history.\n\n' +
            'Type DELETE to confirm:'
        );
        if (t !== 'DELETE') return false;
        form.elements['confirm_text'].value = t;
        return true;
    }
    return confirm('Delete this course permanently?\n\nThis will also remove all its pending / failed payment records. This cannot be undone.');
}
</script>
<?php require_once 'includes/admin-footer.php'; ?>