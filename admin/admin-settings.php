<?php
require_once __DIR__.'/includes/admin-auth.php';
$pdo = getDB();
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $k=>$v) {
        if ($k==='submit') continue;
        $pdo->prepare("INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?")->execute([$k,$v,$v]);
    }
    $success = 'Settings saved!';
}
$settings = [];
foreach ($pdo->query("SELECT * FROM settings") as $r) $settings[$r['setting_key']] = $r['setting_value'];
$page_title = 'Settings';
require_once 'includes/admin-header.php';
?>
<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
<div class="table-card"><div class="card-header">Site Settings</div><div class="p-4">
<form method="POST"><div class="row g-3">
<div class="col-md-6"><label class="form-label">Site Title</label><input type="text" name="site_title" class="form-control" value="<?php echo e($settings['site_title']??''); ?>"></div>
<div class="col-md-6"><label class="form-label">Tagline</label><input type="text" name="site_tagline" class="form-control" value="<?php echo e($settings['site_tagline']??''); ?>"></div>
<div class="col-md-6"><label class="form-label">Contact Email</label><input type="email" name="contact_email" class="form-control" value="<?php echo e($settings['contact_email']??''); ?>"></div>
<div class="col-md-6"><label class="form-label">Contact Phone</label><input type="text" name="contact_phone" class="form-control" value="<?php echo e($settings['contact_phone']??''); ?>"></div>
<div class="col-12"><label class="form-label">Address</label><input type="text" name="contact_address" class="form-control" value="<?php echo e($settings['contact_address']??''); ?>"></div>
<div class="col-md-6"><label class="form-label">Facebook URL</label><input type="url" name="facebook" class="form-control" value="<?php echo e($settings['facebook']??''); ?>"></div>
<div class="col-12"><button type="submit" name="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Settings</button></div>
</div></form></div></div>
<?php require_once 'includes/admin-footer.php'; ?>
