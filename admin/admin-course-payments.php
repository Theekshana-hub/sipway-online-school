<?php
require_once __DIR__.'/includes/admin-auth.php';
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT c.*, cat.name AS cat_name FROM courses c LEFT JOIN categories cat ON c.category_id=cat.id WHERE c.id=? LIMIT 1");
$st->execute([$id]);
$course = $st->fetch();
if (!$course) redirect('admin-courses.php');

// ---- Filters ----
$status = $_GET['status'] ?? '';
$q = trim($_GET['q'] ?? '');
$allowed = ['pending','paid','failed','cancelled','chargedback'];

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

// ---- CSV export (HTML output යන්න කලින්) ----
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="course-' . $id . '-payments-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // Excel UTF-8 BOM
    fputcsv($out, ['Order No','Student Name','Email','Phone','Amount','Currency','Status','Txn Reference','Response Code','Terms Accepted','Created','Paid At']);
    foreach ($orders as $o) {
        fputcsv($out, [$o['order_no'],$o['billing_name'],$o['billing_email'],$o['billing_phone'],$o['amount'],$o['currency'],$o['status'],$o['txn_reference'],$o['response_code'],$o['terms_accepted']?'Yes':'No',$o['created_at'],$o['paid_at']]);
    }
    fclose($out);
    exit;
}

// ---- Stats (filter එකට අදාළ නැතිව, මුළු course එකටම) ----
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

<!-- Course details -->
<div class="table-card mb-3">
<div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="fas fa-book me-2"></i>Course Details</span>
    <a href="admin-edit-course.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit me-1"></i>Edit</a>
</div>
<div class="p-3"><div class="row g-3">
    <?php if ($img): ?><div class="col-md-3"><img src="<?php echo e($img); ?>" alt="" class="img-fluid rounded" style="aspect-ratio:4/3;object-fit:cover;width:100%"></div><?php endif; ?>
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

<!-- Stats -->
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="table-card p-3"><div class="text-muted small">Paid Students</div><div class="fs-4 fw-bold text-success"><?php echo (int)$stats['paid_cnt']; ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="table-card p-3"><div class="text-muted small">Revenue</div><div class="fs-4 fw-bold"><?php echo formatPrice($stats['revenue']); ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="table-card p-3"><div class="text-muted small">Pending</div><div class="fs-4 fw-bold text-warning"><?php echo (int)$stats['pending_cnt']; ?></div></div></div>
    <div class="col-6 col-lg-3"><div class="table-card p-3"><div class="text-muted small">Failed / Cancelled</div><div class="fs-4 fw-bold text-danger"><?php echo (int)$stats['failed_cnt']; ?></div></div></div>
</div>

<!-- Payments -->
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
<thead class="table-light"><tr><th>#</th><th>Student</th><th>Contact</th><th>Order No</th><th>Amount</th><th>Status</th><th>Txn Ref</th><th>Date</th></tr></thead>
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
</tr>
<?php endforeach; ?>
<?php if (empty($orders)): ?><tr><td colspan="8" class="text-center text-muted py-4">No payments found for this course.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require_once 'includes/admin-footer.php'; ?>