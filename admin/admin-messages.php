<?php
require_once __DIR__.'/includes/admin-auth.php';
$pdo = getDB();
if (isset($_GET['read'])) {
    $pdo->prepare("UPDATE contact_messages SET is_read=1 WHERE id=?")->execute([(int)$_GET['read']]);
    redirect('admin-messages.php');
}
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM contact_messages WHERE id=?")->execute([(int)$_GET['delete']]);
    redirect('admin-messages.php?msg=deleted');
}
$messages = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll();
$page_title = 'Messages';
require_once 'includes/admin-header.php';
?>
<?php if (isset($_GET['msg'])): ?><div class="alert alert-success">Deleted!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<div class="table-card"><div class="card-header">Messages (<?php echo count($messages); ?>)</div>
<div class="table-responsive"><table class="table table-hover mb-0">
<thead class="table-light"><tr><th>Status</th><th>Name</th><th>Email</th><th>Subject</th><th>Date</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($messages as $m): ?><tr class="<?php echo !$m['is_read']?'table-warning':''; ?>">
<td><?php echo !$m['is_read']?'<span class="badge bg-warning text-dark">New</span>':'<span class="badge bg-secondary">Read</span>'; ?></td>
<td><strong><?php echo e($m['name']); ?></strong></td>
<td><?php echo e($m['email']); ?></td>
<td><?php echo e($m['subject']?:'-'); ?></td>
<td><?php echo date('M d, Y H:i', strtotime($m['created_at'])); ?></td>
<td>
<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#m<?php echo $m['id']; ?>"><i class="fas fa-eye"></i></button>
<?php if (!$m['is_read']): ?><a href="?read=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-check"></i></a><?php endif; ?>
<a href="?delete=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
</td></tr>
<div class="modal fade" id="m<?php echo $m['id']; ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title">From <?php echo e($m['name']); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<p><strong>Email:</strong> <?php echo e($m['email']); ?></p>
<p><strong>Phone:</strong> <?php echo e($m['phone']?:'-'); ?></p>
<p><strong>Subject:</strong> <?php echo e($m['subject']?:'-'); ?></p><hr>
<p><?php echo nl2br(e($m['message'])); ?></p>
</div>
<div class="modal-footer"><a href="mailto:<?php echo e($m['email']); ?>" class="btn btn-primary">Reply</a><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
</div></div></div>
<?php endforeach; ?>
<?php if (empty($messages)): ?><tr><td colspan="6" class="text-center text-muted py-3">No messages</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require_once 'includes/admin-footer.php'; ?>
