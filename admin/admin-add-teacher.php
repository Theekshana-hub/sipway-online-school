<?php
require_once __DIR__.'/includes/admin-auth.php';
$pdo = getDB();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name']??'');
    $email = trim($_POST['email']??'');
    $phone = trim($_POST['phone']??'');
    $subject = trim($_POST['subject']??'');
    $bio = trim($_POST['bio']??'');
    if (!$name) $error = 'Name required.';
    else {
        $photo = uploadImage('photo', 'teachers');
        if ($photo === false) $error = 'Image upload failed. Use JPG/PNG/WebP under 5MB.';
        else {
            if (!$photo) $photo = 'default-teacher.jpg';
            $pdo->prepare("INSERT INTO teachers (full_name,email,phone,subject,bio,photo) VALUES (?,?,?,?,?,?)")->execute([$name,$email,$phone,$subject,$bio,$photo]);
            redirect('admin-teachers.php?msg=added');
        }
    }
}
$page_title = 'Add Teacher';
require_once 'includes/admin-header.php';
?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
<div class="table-card"><div class="card-header">Add Teacher</div><div class="p-4">
<form method="POST" enctype="multipart/form-data"><div class="row g-3">
<div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Subject</label><input type="text" name="subject" class="form-control" placeholder="e.g. A/L Biology"></div>
<div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Photo</label>
<input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
<small class="text-muted">JPG/PNG/WebP – max 5MB</small></div>
<div class="col-12"><label class="form-label">Bio</label><textarea name="bio" class="form-control" rows="3"></textarea></div>
<div class="col-12"><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Add Teacher</button> <a href="admin-teachers.php" class="btn btn-outline-secondary">Cancel</a></div>
</div></form></div></div>
<?php require_once 'includes/admin-footer.php'; ?>
