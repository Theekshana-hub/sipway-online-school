<?php
$page_title = 'Dashboard';
require_once 'includes/admin-header.php';
$pdo = getDB();
$stats = [
    'courses' => $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn(),
    'students' => $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn(),
    'teachers' => $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn(),
    'enrollments' => $pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn(),
    'messages' => $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read=0")->fetchColumn(),
];
$recent = $pdo->query("SELECT c.title, cat.name as cat, c.price, c.is_published FROM courses c LEFT JOIN categories cat ON c.category_id=cat.id ORDER BY c.created_at DESC LIMIT 5")->fetchAll();
$msgs = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<div class="row g-3 mb-4">
<?php
$cards = [
    ['courses','Total Courses','#E30613','fas fa-book'],
    ['students','Students','#F5A623','fas fa-users'],
    ['teachers','Teachers','#1A1A1A','fas fa-chalkboard-teacher'],
    ['enrollments','Enrollments','#10b981','fas fa-user-graduate'],
];
foreach ($cards as $c): ?>
<div class="col-xl-3 col-md-6"><div class="stat-card"><div class="d-flex justify-content-between align-items-center">
<div><p class="text-muted mb-1 small"><?php echo $c[1]; ?></p><h3 class="mb-0"><?php echo $stats[$c[0]]; ?></h3></div>
<div class="icon" style="background:<?php echo $c[2]; ?>"><i class="<?php echo $c[3]; ?>"></i></div>
</div></div></div>
<?php endforeach; ?>
</div>
<div class="row g-3">
<div class="col-lg-7"><div class="table-card">
<div class="card-header d-flex justify-content-between"><span>Recent Courses</span><a href="admin-courses.php" class="btn btn-sm btn-primary">View All</a></div>
<div class="table-responsive"><table class="table table-hover mb-0"><thead class="table-light"><tr><th>Title</th><th>Category</th><th>Price</th><th>Status</th></tr></thead><tbody>
<?php foreach ($recent as $r): ?><tr>
<td><strong><?php echo e($r['title']); ?></strong></td>
<td><?php echo e($r['cat']??'-'); ?></td>
<td><?php echo formatPrice($r['price']); ?></td>
<td><?php echo $r['is_published']?'<span class="badge bg-success">Live</span>':'<span class="badge bg-secondary">Draft</span>'; ?></td>
</tr><?php endforeach; ?>
<?php if (empty($recent)): ?><tr><td colspan="4" class="text-center text-muted py-3">No courses</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<div class="col-lg-5"><div class="table-card">
<div class="card-header d-flex justify-content-between"><span>Messages <?php if($stats['messages']): ?><span class="badge bg-danger"><?php echo $stats['messages']; ?></span><?php endif; ?></span><a href="admin-messages.php" class="btn btn-sm btn-primary">View All</a></div>
<div class="p-3">
<?php if (empty($msgs)): ?><p class="text-muted text-center py-2">No messages</p>
<?php else: foreach ($msgs as $m): ?>
<div class="d-flex gap-2 mb-2 pb-2 border-bottom">
<div class="user-av flex-shrink-0" style="width:32px;height:32px;font-size:.75rem"><?php echo strtoupper(substr($m['name'],0,1)); ?></div>
<div><strong class="d-block small"><?php echo e($m['name']); ?></strong>
<small class="text-muted"><?php echo e(mb_substr($m['message'],0,40)); ?>...</small></div>
</div>
<?php endforeach; endif; ?>
</div></div></div>
</div>
<?php require_once 'includes/admin-footer.php'; ?>
