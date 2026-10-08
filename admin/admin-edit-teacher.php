<?php
require_once __DIR__.'/includes/admin-auth.php';
$pdo = getDB();
$id = (int)($_GET['id']??0);
$stmt = $pdo->prepare("SELECT * FROM teachers WHERE id=?");
$stmt->execute([$id]);
$t = $stmt->fetch();
if (!$t) redirect('admin-teachers.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name']??'');
    $email = trim($_POST['email']??'');
    $phone = trim($_POST['phone']??'');
    $subject = trim($_POST['subject']??'');
    $bio = trim($_POST['bio']??'');
    $status = $_POST['status']??'active';
    if (!$name) $error = 'Name required.';
    else {
        $photo = uploadImage('photo', 'teachers', $t['photo']);
        if ($photo === false) $error = 'Image upload failed. Use JPG/PNG/WebP under 5MB.';
        else {
            $pdo->prepare("UPDATE teachers SET full_name=?,email=?,phone=?,subject=?,bio=?,photo=?,status=? WHERE id=?")
                ->execute([$name,$email,$phone,$subject,$bio,$photo,$status,$id]);
            redirect('admin-teachers.php?msg=updated');
        }
    }
}
$page_title = 'Edit Teacher';
require_once 'includes/admin-header.php';
$photoUrl = imageUrl('teachers', $t['photo']);
?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
<div class="table-card"><div class="card-header">Edit: <?php echo e($t['full_name']); ?></div><div class="p-4">
<form method="POST" enctype="multipart/form-data"><div class="row g-3">
<div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control" required value="<?php echo e($t['full_name']); ?>"></div>
<div class="col-md-6"><label class="form-label">Subject</label><input type="text" name="subject" class="form-control" value="<?php echo e($t['subject']); ?>"></div>
<div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?php echo e($t['email']); ?>"></div>
<div class="col-md-6"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control" value="<?php echo e($t['phone']); ?>"></div>
<div class="col-md-6">
<label class="form-label">Photo</label>
<?php if ($photoUrl): ?><div class="mb-2"><img src="<?php echo e($photoUrl); ?>" alt="" style="max-height:80px;border-radius:8px"></div><?php endif; ?>
<input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
<small class="text-muted">Leave empty to keep current</small>
</div>
<div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select">
<option value="active" <?php echo $t['status']==='active'?'selected':''; ?>>Active</option>
<option value="inactive" <?php echo $t['status']==='inactive'?'selected':''; ?>>Inactive</option>
</select></div>
<div class="col-12"><label class="form-label">Bio</label><textarea name="bio" class="form-control" rows="3"><?php echo e($t['bio']); ?></textarea></div>
<div class="col-12"><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update</button> <a href="admin-teachers.php" class="btn btn-outline-secondary">Cancel</a></div>
</div></form></div></div>
<?php require_once 'includes/admin-footer.php'; ?>
