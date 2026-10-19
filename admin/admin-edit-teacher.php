<?php
require_once __DIR__.'/includes/admin-auth.php';
$pdo = getDB();
$id = (int)($_GET['id']??0);
$stmt = $pdo->prepare("SELECT * FROM teachers WHERE id=?");
$stmt->execute([$id]);
$t = $stmt->fetch();
if (!$t) redirect('admin-teachers.php');
$error = '';

// ===== Categories tree (parent + sub) =====
$allCats = $pdo->query("SELECT id, name, parent_id FROM categories ORDER BY sort_order, name")->fetchAll();
$topCats = [];
$subCats = [];
foreach ($allCats as $ct) {
    if (empty($ct['parent_id'])) $topCats[] = $ct;
    else $subCats[$ct['parent_id']][] = $ct;
}
$validIds = array_map('intval', array_column($allCats, 'id'));

// Teacher ge dan thiyena categories
$cs = $pdo->prepare("SELECT category_id FROM teacher_categories WHERE teacher_id=?");
$cs->execute([$id]);
$currentCats = array_map('intval', $cs->fetchAll(PDO::FETCH_COLUMN));
$selectedCats = $currentCats;

$currentSubjects = array_values(array_filter(array_map('trim', explode(',', (string)($t['subject'] ?? '')))));
$postedSubjects = $currentSubjects ?: [''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['full_name']??'');
    $email  = trim($_POST['email']??'');
    $phone  = trim($_POST['phone']??'');
    $bio    = trim($_POST['bio']??'');
    $status = $_POST['status']??'active';

    // Selected categories (godak) - valid ewa withrak ganna
    $rawCats = $_POST['category_ids'] ?? [];
    if (!is_array($rawCats)) $rawCats = [$rawCats];
    $selectedCats = array_values(array_unique(array_filter(
        array_map('intval', $rawCats),
        function ($v) use ($validIds) { return in_array($v, $validIds, true); }
    )));

    $rawSubjects = $_POST['subject'] ?? [];
    if (!is_array($rawSubjects)) $rawSubjects = [$rawSubjects];

    $subjectList = [];
    foreach ($rawSubjects as $s) {
        $s = trim(str_replace(',', ' ', (string)$s));
        $s = preg_replace('/\s+/', ' ', $s);
        if ($s !== '' && !in_array(mb_strtolower($s), array_map('mb_strtolower', $subjectList), true)) {
            $subjectList[] = $s;
        }
    }
    $subject = implode(', ', $subjectList);
    $postedSubjects = $subjectList ?: [''];

    if (!$name) $error = 'Name required.';
    else {
        $photo = uploadImage('photo', 'teachers', $t['photo']);
        if ($photo === false) $error = 'Image upload failed. Use JPG/PNG/WebP under 5MB.';
        else {
            $pdo->beginTransaction();
            try {
                $pdo->prepare("UPDATE teachers SET full_name=?,email=?,phone=?,subject=?,bio=?,photo=?,status=? WHERE id=?")
                    ->execute([$name,$email,$phone,$subject,$bio,$photo,$status,$id]);

                // Category links aluthen set karanawa
                $pdo->prepare("DELETE FROM teacher_categories WHERE teacher_id=?")->execute([$id]);
                if ($selectedCats) {
                    $ins = $pdo->prepare("INSERT IGNORE INTO teacher_categories (teacher_id, category_id) VALUES (?, ?)");
                    foreach ($selectedCats as $cid) {
                        $ins->execute([$id, $cid]);
                    }
                }
                $pdo->commit();
            } catch (Exception $ex) {
                $pdo->rollBack();
                $error = 'Save failed: ' . $ex->getMessage();
            }

            if (!$error) redirect('admin-teachers.php?msg=updated');
        }
    }
}
$page_title = 'Edit Teacher';
require_once 'includes/admin-header.php';
$photoUrl = imageUrl('teachers', $t['photo']);
?>
<style>
.cat-box { max-height: 220px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 8px; padding: 10px 14px; background: #fff; }
.cat-parent { font-weight: 600; }
.cat-sub { padding-left: 24px; }
</style>

<?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
<div class="table-card"><div class="card-header">Edit: <?php echo e($t['full_name']); ?></div><div class="p-4">
<form method="POST" enctype="multipart/form-data"><div class="row g-3">

<div class="col-md-6">
    <label class="form-label">Full Name *</label>
    <input type="text" name="full_name" class="form-control" required
           value="<?php echo e($_SERVER['REQUEST_METHOD']==='POST' ? ($_POST['full_name'] ?? '') : $t['full_name']); ?>">
</div>

<div class="col-md-6">
    <label class="form-label">Categories <small class="text-muted">(godak select karanna puluwan)</small></label>
    <div class="cat-box">
        <?php if (!$topCats): ?>
            <small class="text-muted">No categories found.</small>
        <?php endif; ?>
        <?php foreach ($topCats as $cat): ?>
            <div class="form-check cat-parent">
                <input class="form-check-input" type="checkbox" name="category_ids[]"
                       id="cat<?php echo (int)$cat['id']; ?>" value="<?php echo (int)$cat['id']; ?>"
                       <?php echo in_array((int)$cat['id'], $selectedCats, true) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="cat<?php echo (int)$cat['id']; ?>">
                    <?php echo e($cat['name']); ?>
                </label>
            </div>
            <?php foreach ($subCats[$cat['id']] ?? [] as $sub): ?>
                <div class="form-check cat-sub">
                    <input class="form-check-input" type="checkbox" name="category_ids[]"
                           id="cat<?php echo (int)$sub['id']; ?>" value="<?php echo (int)$sub['id']; ?>"
                           <?php echo in_array((int)$sub['id'], $selectedCats, true) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="cat<?php echo (int)$sub['id']; ?>">
                        <?php echo e($sub['name']); ?>
                    </label>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
</div>

<div class="col-md-6">
    <label class="form-label">Subjects</label>
    <div id="subjectList">
        <?php foreach ($postedSubjects as $sv): ?>
        <div class="input-group mb-2 subject-row">
            <input type="text" name="subject[]" class="form-control"
                   placeholder="e.g. A/L Biology"
                   value="<?php echo e($sv); ?>">
            <button type="button" class="btn btn-outline-danger btn-remove-subject" title="Remove">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php endforeach; ?>
    </div>
    <button type="button" id="addSubjectBtn" class="btn btn-sm btn-outline-primary">
        <i class="fas fa-plus me-1"></i> Add another subject
    </button>
    <div><small class="text-muted">You can add as many subjects as you need.</small></div>
</div>

<div class="col-md-6">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control"
           value="<?php echo e($_SERVER['REQUEST_METHOD']==='POST' ? ($_POST['email'] ?? '') : $t['email']); ?>">
</div>
<div class="col-md-6">
    <label class="form-label">Phone</label>
    <input type="tel" name="phone" class="form-control"
           value="<?php echo e($_SERVER['REQUEST_METHOD']==='POST' ? ($_POST['phone'] ?? '') : $t['phone']); ?>">
</div>

<div class="col-md-6">
<label class="form-label">Photo</label>
<?php if ($photoUrl): ?><div class="mb-2"><img src="<?php echo e($photoUrl); ?>" alt="" style="max-height:80px;border-radius:8px"></div><?php endif; ?>
<input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
<small class="text-muted">Leave empty to keep current</small>
</div>

<div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select">
<?php $curStatus = $_SERVER['REQUEST_METHOD']==='POST' ? ($_POST['status'] ?? 'active') : $t['status']; ?>
<option value="active" <?php echo $curStatus==='active'?'selected':''; ?>>Active</option>
<option value="inactive" <?php echo $curStatus==='inactive'?'selected':''; ?>>Inactive</option>
</select></div>

<div class="col-12"><label class="form-label">Bio</label>
<textarea name="bio" class="form-control" rows="3"><?php echo e($_SERVER['REQUEST_METHOD']==='POST' ? ($_POST['bio'] ?? '') : $t['bio']); ?></textarea></div>

<div class="col-12"><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update</button> <a href="admin-teachers.php" class="btn btn-outline-secondary">Cancel</a></div>
</div></form></div></div>

<script>
(function () {
    var list = document.getElementById('subjectList');
    var addBtn = document.getElementById('addSubjectBtn');

    function makeRow() {
        var div = document.createElement('div');
        div.className = 'input-group mb-2 subject-row';
        div.innerHTML =
            '<input type="text" name="subject[]" class="form-control" placeholder="e.g. A/L Biology">' +
            '<button type="button" class="btn btn-outline-danger btn-remove-subject" title="Remove">' +
            '<i class="fas fa-times"></i></button>';
        return div;
    }

    addBtn.addEventListener('click', function () {
        var row = makeRow();
        list.appendChild(row);
        row.querySelector('input').focus();
    });

    list.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-remove-subject');
        if (!btn) return;
        var rows = list.querySelectorAll('.subject-row');
        var row = btn.closest('.subject-row');
        if (rows.length > 1) row.remove();
        else row.querySelector('input').value = '';
    });

    list.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && e.target.name === 'subject[]') {
            e.preventDefault();
            addBtn.click();
        }
    });
})();
</script>
<?php require_once 'includes/admin-footer.php'; ?>