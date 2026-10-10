<?php
require_once __DIR__ . '/includes/admin-auth.php';
$pdo = getDB();

/* ---------- DELETE ---------- */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // Sub-categories තියෙනවා නම් delete කරන්න දෙන්නේ නෑ
    $chk = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
    $chk->execute([$id]);
    if ($chk->fetchColumn() > 0) {
        redirect('admin-categories.php?msg=has_children');
    }

    $row = $pdo->prepare("SELECT image FROM categories WHERE id=?");
    $row->execute([$id]);
    $old = $row->fetchColumn();
    if ($old) {
        $path = __DIR__ . '/../uploads/categories/' . $old;
        if (file_exists($path)) @unlink($path);
    }
    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
    redirect('admin-categories.php?msg=deleted');
}

/* ---------- SAVE ---------- */
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id        = (int)($_POST['id'] ?? 0);
    $parent_id = (int)($_POST['parent_id'] ?? 0);
    $name      = trim($_POST['name'] ?? '');
    $name_si   = trim($_POST['name_si'] ?? '');
    $slug      = trim($_POST['slug'] ?? '');
    $icon      = trim($_POST['icon'] ?? 'fas fa-book');
    $color     = trim($_POST['color'] ?? '#E30613');
    $sort      = (int)($_POST['sort_order'] ?? 0);

    // Validate parent
    $parentSlug = '';
    if ($parent_id) {
        if ($parent_id === $id) {
            $parent_id = 0;
        } else {
            $ps = $pdo->prepare("SELECT slug, parent_id FROM categories WHERE id = ?");
            $ps->execute([$parent_id]);
            $p = $ps->fetch();
            if (!$p || $p['parent_id']) {
                // parent නැත්නම් / parent එකම sub-category එකක් නම් (level 2ක් විතරයි)
                $error = 'Invalid parent category.';
            } else {
                $parentSlug = $p['slug'];
            }
        }
    }

    // Sub-categories තියෙන category එකක් තවත් category එකක් යටතට දාන්න බෑ
    if (!$error && $id && $parent_id) {
        $cc = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
        $cc->execute([$id]);
        if ($cc->fetchColumn() > 0) {
            $error = 'This category has sub-categories, so it cannot be moved under another category.';
        }
    }

    if (!$slug && $name) {
        $slug = slugify($name);
        if ($parentSlug) $slug = $parentSlug . '-' . $slug; // grade-6 එක වෙනම parent වලට clash නොවෙන්න
    }

    $oldImage = null;
    if ($id) {
        $st = $pdo->prepare("SELECT image FROM categories WHERE id=?");
        $st->execute([$id]);
        $oldImage = $st->fetchColumn();
    }

    if (!$error && !$name) {
        $error = 'Name is required.';
    }

    if (!$error) {
        $image = uploadImage('image', 'categories', $oldImage);
        if ($image === false) {
            $error = 'Image upload failed. Use JPG/PNG/WebP under 5MB.';
        } else {
            $pid = $parent_id ?: null;
            if ($id) {
                $pdo->prepare("UPDATE categories SET parent_id=?, name=?, name_si=?, slug=?, icon=?, color=?, sort_order=?, image=? WHERE id=?")
                    ->execute([$pid, $name, $name_si, $slug, $icon, $color, $sort, $image, $id]);
            } else {
                $pdo->prepare("INSERT INTO categories (parent_id, name, name_si, slug, icon, color, sort_order, image) VALUES (?,?,?,?,?,?,?,?)")
                    ->execute([$pid, $name, $name_si, $slug, $icon, $color, $sort, $image]);
            }
            redirect('admin-categories.php?msg=saved');
        }
    }
}

/* ---------- EDIT / PRESELECT PARENT ---------- */
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}
$preParent = (int)($_GET['parent'] ?? 0); // "+ Sub" button එකෙන් එන්නේ

/* ---------- LOAD ALL + BUILD TREE ---------- */
$all = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM courses WHERE category_id = c.id) AS course_count
                    FROM categories c ORDER BY sort_order, name")->fetchAll();

$topLevel = [];
$children = [];
foreach ($all as $c) {
    if (empty($c['parent_id'])) $topLevel[] = $c;
    else $children[$c['parent_id']][] = $c;
}

// Edit කරන category එකට children තියෙනවද?
$editHasChildren = $edit && !empty($children[$edit['id']]);
$selectedParent  = $edit ? (int)($edit['parent_id'] ?? 0) : $preParent;

$msgs = [
    'saved'        => ['success', 'Category saved!'],
    'deleted'      => ['success', 'Category deleted!'],
    'has_children' => ['danger',  'Delete the sub-categories first, then delete this category.'],
];

$page_title = 'Categories';
require_once 'includes/admin-header.php';
?>
<?php if (isset($_GET['msg'], $msgs[$_GET['msg']])): [$mType, $mText] = $msgs[$_GET['msg']]; ?>
<div class="alert alert-<?php echo $mType; ?> alert-dismissible fade show"><?php echo e($mText); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="table-card">
            <div class="card-header"><?php echo $edit ? 'Edit Category' : 'Add Category'; ?></div>
            <div class="p-3">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $edit['id'] ?? 0; ?>">

                    <div class="mb-3">
                        <label class="form-label">Parent Category</label>
                        <select name="parent_id" class="form-select" <?php echo $editHasChildren ? 'disabled' : ''; ?>>
                            <option value="0">— None (Main Category) —</option>
                            <?php foreach ($topLevel as $t):
                                if ($edit && $t['id'] == $edit['id']) continue; ?>
                            <option value="<?php echo $t['id']; ?>" <?php echo $selectedParent == $t['id'] ? 'selected' : ''; ?>><?php echo e($t['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">
                            <?php echo $editHasChildren ? 'This category has sub-categories, so it stays a main category.' : 'Sub-category එකක් හදනවා නම් (උදා: Grade 6) main category එක තෝරන්න.'; ?>
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Name (English) *</label>
                        <input type="text" name="name" class="form-control" required value="<?php echo e($edit['name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Name (Sinhala)</label>
                        <input type="text" name="name_si" class="form-control" value="<?php echo e($edit['name_si'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" value="<?php echo e($edit['slug'] ?? ''); ?>" placeholder="auto">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Icon (Font Awesome)</label>
                        <input type="text" name="icon" class="form-control" value="<?php echo e($edit['icon'] ?? 'fas fa-book'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Color</label>
                        <input type="color" name="color" class="form-control form-control-color w-100" value="<?php echo e($edit['color'] ?? '#E30613'); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category Image</label>
                        <?php
                        $catImg = $edit ? imageUrl('categories', $edit['image'] ?? null) : null;
                        if ($catImg): ?>
                        <div class="mb-2"><img src="<?php echo e($catImg); ?>" alt="" style="max-height:70px;border-radius:8px"></div>
                        <?php endif; ?>
                        <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                        <small class="text-muted">JPG/PNG/WebP – max 5MB</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control" value="<?php echo e($edit['sort_order'] ?? 0); ?>">
                        <small class="text-muted">Grade 6=6, Grade 7=7 වගේ දාන්න, එතකොට පිළිවෙලට පෙනෙයි.</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><?php echo $edit ? 'Update' : 'Add'; ?> Category</button>
                    <?php if ($edit || $preParent): ?><a href="admin-categories.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="table-card">
            <div class="card-header">All Categories (<?php echo count($all); ?>)</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Image</th><th>Name</th><th>Sinhala</th><th>Courses</th><th>Order</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php
                        // Row එකක් print කරන function එක (parent & sub දෙකටම)
                        function renderCatRow($c, $isSub, $totalCourses, $subCount) {
                            $cimg = imageUrl('categories', $c['image'] ?? null);
                            ?>
                            <tr <?php echo $isSub ? 'class="table-light"' : ''; ?>>
                                <td>
                                    <?php if ($cimg): ?>
                                    <img src="<?php echo e($cimg); ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:8px;<?php echo $isSub ? 'margin-left:24px' : ''; ?>">
                                    <?php else: ?>
                                    <span class="d-inline-flex align-items-center justify-content-center rounded" style="width:36px;height:36px;background:<?php echo e($c['color']); ?>;color:#fff;<?php echo $isSub ? 'margin-left:24px' : ''; ?>">
                                        <i class="<?php echo e($c['icon']); ?>"></i>
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($isSub): ?><i class="fas fa-level-up-alt fa-rotate-90 text-muted me-1"></i><?php endif; ?>
                                    <strong><?php echo e($c['name']); ?></strong>
                                    <?php if (!$isSub && $subCount): ?><span class="badge bg-secondary ms-1"><?php echo $subCount; ?> sub</span><?php endif; ?>
                                    <br><small class="text-muted"><?php echo e($c['slug']); ?></small>
                                </td>
                                <td><?php echo e($c['name_si'] ?: '-'); ?></td>
                                <td><span class="badge bg-info"><?php echo $totalCourses; ?></span></td>
                                <td><?php echo $c['sort_order']; ?></td>
                                <td>
                                    <?php if (!$isSub): ?>
                                    <a href="?parent=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-success" title="Add sub-category"><i class="fas fa-plus"></i> Sub</a>
                                    <?php endif; ?>
                                    <a href="?edit=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                    <a href="?delete=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php
                        }

                        foreach ($topLevel as $t):
                            $subs  = $children[$t['id']] ?? [];
                            $total = (int)$t['course_count'];
                            foreach ($subs as $s) $total += (int)$s['course_count'];
                            renderCatRow($t, false, $total, count($subs));
                            foreach ($subs as $s) renderCatRow($s, true, (int)$s['course_count'], 0);
                        endforeach;
                        ?>
                        <?php if (empty($all)): ?><tr><td colspan="6" class="text-center text-muted py-3">No categories</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/admin-footer.php'; ?>