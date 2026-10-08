<?php
$page_title = 'Students';
require_once 'includes/admin-header.php';
$pdo = getDB();
$students = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM enrollments WHERE user_id=u.id) as enrolls FROM users u WHERE u.role='student' ORDER BY u.created_at DESC")->fetchAll();
?>
<div class="table-card"><div class="card-header">Students (<?php echo count($students); ?>)</div>
<div class="table-responsive"><table class="table table-hover mb-0">
<thead class="table-light"><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Enrollments</th><th>Status</th><th>Joined</th></tr></thead>
<tbody>
<?php foreach ($students as $i=>$s): ?><tr>
<td><?php echo $i+1; ?></td>
<td><strong><?php echo e($s['full_name']); ?></strong></td>
<td><?php echo e($s['email']); ?></td>
<td><?php echo e($s['phone']?:'-'); ?></td>
<td><span class="badge bg-info"><?php echo $s['enrolls']; ?></span></td>
<td><span class="badge <?php echo $s['status']==='active'?'bg-success':'bg-secondary'; ?>"><?php echo ucfirst($s['status']); ?></span></td>
<td><?php echo date('M d, Y', strtotime($s['created_at'])); ?></td>
</tr><?php endforeach; ?>
<?php if (empty($students)): ?><tr><td colspan="7" class="text-center text-muted py-3">No students yet</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require_once 'includes/admin-footer.php'; ?>
