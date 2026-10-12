<?php
require_once __DIR__.'/includes/admin-auth.php';
$pdo = getDB();
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM courses WHERE id=?")->execute([(int)$_GET['delete']]);
    redirect('admin-courses.php?msg=deleted');
}
if (isset($_GET['toggle'])) {
    $pdo->prepare("UPDATE courses SET is_published=NOT is_published WHERE id=?")->execute([(int)$_GET['toggle']]);
    redirect('admin-courses.php?msg=updated');
}

// ★ හැම course එකටම ගෙවපු ගණනයි ආදායමයි එක්කම ගන්නවා
$courses = $pdo->query("
    SELECT c.*, cat.name AS cat_name,
        (SELECT COUNT(*) FROM course_orders o WHERE o.course_id=c.id AND o.status='paid') AS paid_count,
        (SELECT COALESCE(SUM(o.amount),0) FROM course_orders o WHERE o.course_id=c.id AND o.status='paid') AS revenue
    FROM courses c
    LEFT JOIN categories cat ON c.category_id=cat.id
    ORDER BY c.created_at DESC
")->fetchAll();

$totalPaid = array_sum(array_column($courses, 'paid_count'));
$totalRevenue = array_sum(array_column($courses, 'revenue'));

$page_title = 'Courses';
require_once 'includes/admin-header.php';
?>
<?php if (isset($_GET['msg'])): ?><div class="alert alert-success alert-dismissible fade show">Done!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="table-card p-3"><div class="text-muted small">Total Courses</div><div class="fs-4 fw-bold"><?php echo count($courses); ?></div></div></div>
    <div class="col-md-4"><div class="table-card p-3"><div class="text-muted small">Total Paid Enrollments</div><div class="fs-4 fw-bold"><?php echo (int)$totalPaid; ?></div></div></div>
    <div class="col-md-4"><div class="table-card p-3"><div class="text-muted small">Total Revenue</div><div class="fs-4 fw-bold text-success"><?php echo formatPrice($totalRevenue); ?></div></div></div>
</div>

<div class="table-card">
<div class="card-header d-flex justify-content-between"><span>All Courses (<?php echo count($courses); ?>)</span>
<a href="admin-add-course.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Add Course</a></div>
<div class="table-responsive"><table class="table table-hover mb-0 align-middle">
<thead class="table-light"><tr><th>#</th><th>Title</th><th>Category</th><th>Level</th><th>Price</th><th>Students</th><th>Revenue</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($courses as $i=>$c): ?><tr>
<td><?php echo $i+1; ?></td>
<td><strong><?php echo e($c['title']); ?></strong><?php if($c['is_featured']): ?> <span class="badge bg-warning text-dark">Featured</span><?php endif; ?></td>
<td><?php echo e($c['cat_name']??'-'); ?></td>
<td><?php echo e($c['level']); ?></td>
<td><?php echo formatPrice($c['price']); ?></td>
<td><a href="admin-course-payments.php?id=<?php echo $c['id']; ?>" class="badge bg-info text-dark text-decoration-none"><i class="fas fa-users me-1"></i><?php echo (int)$c['paid_count']; ?></a></td>
<td><?php echo formatPrice($c['revenue']); ?></td>
<td><a href="?toggle=<?php echo $c['id']; ?>" class="badge <?php echo $c['is_published']?'bg-success':'bg-secondary'; ?> text-decoration-none"><?php echo $c['is_published']?'Live':'Draft'; ?></a></td>
<td class="text-nowrap">
<a href="admin-course-payments.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-success" title="View details &amp; payments"><i class="fas fa-eye"></i></a>
<a href="admin-edit-course.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
<a href="?delete=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
</td></tr><?php endforeach; ?>
<?php if (empty($courses)): ?><tr><td colspan="9" class="text-center text-muted py-3">No courses. <a href="admin-add-course.php">Add one</a></td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require_once 'includes/admin-footer.php'; ?>