<?php
require_once __DIR__ . '/includes/admin-auth.php';
$pdo = getDB();

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
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

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $name_si = trim($_POST['name_si'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $icon = trim($_POST['icon'] ?? 'fas fa-book');
    $color = trim($_POST['color'] ?? '#E30613');
    $sort = (int)($_POST['sort_order'] ?? 0);
    if (!$slug && $name) $slug = slugify($name);

    $oldImage = null;
    if ($id) {
        $st = $pdo->prepare("SELECT image FROM categories WHERE id=?");
        $st->execute([$id]);
        $oldImage = $st->fetchColumn();
    }

    if (!$name) {
        $error = 'Name is required.';
    } else {
        $image = uploadImage('image', 'categories', $oldImage);
        if ($image === false) $error = 'Image upload failed. Use JPG/PNG/WebP under 5MB.';
        else {
            if ($id) {
                $pdo->prepare("UPDATE categories SET name=?, name_si=?, slug=?, icon=?, color=?, sort_order=?, image=? WHERE id=?")
                    ->execute([$name, $name_si, $slug, $icon, $color, $sort, $image, $id]);
            } else {
                $pdo->prepare("INSERT INTO categories (name, name_si, slug, icon, color, sort_order, image) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$name, $name_si, $slug, $icon, $color, $sort, $image]);
            }
            redirect('admin-categories.php?msg=saved');
        }
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$categories = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM courses WHERE category_id = c.id) as course_count FROM categories c ORDER BY sort_order, name")->fetchAll();
$page_title = 'Categories';
require_once 'includes/admin-header.php';
?>
<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success alert-dismissible fade show">Category saved!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="table-card">
            <div class="card-header"><?php echo $edit ? 'Edit Category' : 'Add Category'; ?></div>
            <div class="p-3">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $edit['id'] ?? 0; ?>">
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
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><?php echo $edit ? 'Update' : 'Add'; ?> Category</button>
                    <?php if ($edit): ?><a href="admin-categories.php" class="btn btn-outline-secondary w-100 mt-2">Cancel</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="table-card">
            <div class="card-header">All Categories (<?php echo count($categories); ?>)</div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Image</th><th>Name</th><th>Sinhala</th><th>Courses</th><th>Order</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($categories as $c):
                            $cimg = imageUrl('categories', $c['image'] ?? null);
                        ?>
                        <tr>
                            <td>
                                <?php if ($cimg): ?>
                                <img src="<?php echo e($cimg); ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:8px">
                                <?php else: ?>
                                <span class="d-inline-flex align-items-center justify-content-center rounded" style="width:36px;height:36px;background:<?php echo e($c['color']); ?>;color:#fff">
                                    <i class="<?php echo e($c['icon']); ?>"></i>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo e($c['name']); ?></strong><br><small class="text-muted"><?php echo e($c['slug']); ?></small></td>
                            <td><?php echo e($c['name_si'] ?: '-'); ?></td>
                            <td><span class="badge bg-info"><?php echo $c['course_count']; ?></span></td>
                            <td><?php echo $c['sort_order']; ?></td>
                            <td>
                                <a href="?edit=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                <a href="?delete=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($categories)): ?><tr><td colspan="6" class="text-center text-muted py-3">No categories</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/admin-footer.php'; ?>
