<?php
$page_title = 'Enrollments';
require_once 'includes/admin-header.php';
$pdo = getDB();
$rows = $pdo->query("SELECT e.*, u.full_name as student, u.email, c.title as course FROM enrollments e JOIN users u ON e.user_id=u.id JOIN courses c ON e.course_id=c.id ORDER BY e.enrolled_at DESC")->fetchAll();
?>
<div class="table-card"><div class="card-header">Enrollments (<?php echo count($rows); ?>)</div>
<div class="table-responsive"><table class="table table-hover mb-0">
<thead class="table-light"><tr><th>#</th><th>Student</th><th>Email</th><th>Course</th><th>Progress</th><th>Status</th><th>Date</th></tr></thead>
<tbody>
<?php foreach ($rows as $i=>$r): ?><tr>
<td><?php echo $i+1; ?></td>
<td><strong><?php echo e($r['student']); ?></strong></td>
<td><?php echo e($r['email']); ?></td>
<td><?php echo e($r['course']); ?></td>
<td>
<div class="progress" style="height:6px;width:80px;display:inline-flex"><div class="progress-bar" style="width:<?php echo $r['progress']; ?>%;background:#E30613"></div></div>
<small><?php echo $r['progress']; ?>%</small>
</td>
<td><span class="badge <?php echo $r['status']==='active'?'bg-success':'bg-primary'; ?>"><?php echo ucfirst($r['status']); ?></span></td>
<td><?php echo date('M d, Y', strtotime($r['enrolled_at'])); ?></td>
</tr><?php endforeach; ?>
<?php if (empty($rows)): ?><tr><td colspan="7" class="text-center text-muted py-3">No enrollments yet</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require_once 'includes/admin-footer.php'; ?>
