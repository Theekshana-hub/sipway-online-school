<?php
require_once __DIR__.'/includes/admin-auth.php';
$pdo = getDB();
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM teachers WHERE id=?")->execute([(int)$_GET['delete']]);
    redirect('admin-teachers.php?msg=deleted');
}
$teachers = $pdo->query("SELECT * FROM teachers ORDER BY full_name")->fetchAll();
$page_title = 'Teachers';
require_once 'includes/admin-header.php';
?>
<?php if (isset($_GET['msg'])): ?><div class="alert alert-success">Done!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<div class="table-card">
<div class="card-header d-flex justify-content-between"><span>Teachers (<?php echo count($teachers); ?>)</span>
<a href="admin-add-teacher.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Add Teacher</a></div>
<div class="table-responsive"><table class="table table-hover mb-0">
<thead class="table-light"><tr><th>#</th><th>Name</th><th>Subject</th><th>Phone</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($teachers as $i=>$t): ?><tr>
<td><?php echo $i+1; ?></td>
<td>
<?php $tp = imageUrl('teachers', $t['photo']??null); ?>
<?php if ($tp): ?><img src="<?php echo e($tp); ?>" style="width:32px;height:32px;object-fit:cover;border-radius:50%;vertical-align:middle;margin-right:6px"><?php endif; ?>
<strong><?php echo e($t['full_name']); ?></strong>
</td>
<td><?php echo e($t['subject']); ?></td>
<td><?php echo e($t['phone']?:'-'); ?></td>
<td><span class="badge <?php echo $t['status']==='active'?'bg-success':'bg-secondary'; ?>"><?php echo ucfirst($t['status']); ?></span></td>
<td>
<a href="admin-edit-teacher.php?id=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
<a href="?delete=<?php echo $t['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
</td></tr><?php endforeach; ?>
<?php if (empty($teachers)): ?><tr><td colspan="6" class="text-center text-muted py-3">No teachers</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require_once 'includes/admin-footer.php'; ?>
