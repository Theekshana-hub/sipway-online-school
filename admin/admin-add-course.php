<?php
require_once __DIR__.'/includes/admin-auth.php';
$pdo = getDB();
$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order")->fetchAll();
$teachers = $pdo->query("SELECT * FROM teachers WHERE status='active' ORDER BY full_name")->fetchAll();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title     = trim($_POST['title'] ?? '');
    $title_si  = trim($_POST['title_si'] ?? '');
    $slug      = trim($_POST['slug'] ?? '') ?: slugify($title);
    $cat       = (int)($_POST['category_id'] ?? 0) ?: null;
    $teacher   = (int)($_POST['teacher_id'] ?? 0) ?: null;
    $short     = trim($_POST['short_desc'] ?? '');
    $desc      = $_POST['description'] ?? '';
    $duration  = trim($_POST['duration'] ?? '');
    $level     = $_POST['level'] ?? 'A/L';
    $price     = (float)($_POST['price'] ?? 0);
    $featured  = isset($_POST['is_featured']) ? 1 : 0;
    $published = isset($_POST['is_published']) ? 1 : 0;

    if (!$title) {
        $error = 'Title required.';
    } else {
        // Check if slug already exists
        $check = $pdo->prepare("SELECT id, title FROM courses WHERE slug = ?");
        $check->execute([$slug]);
        $existing = $check->fetch();

        if ($existing) {
            $error = "This slug (<strong>{$slug}</strong>) already exists for course: <strong>{$existing['title']}</strong> (ID: {$existing['id']})";
        } else {
            $thumb = uploadImage('thumbnail', 'courses');

            if ($thumb === false) {
                $error = 'Image upload failed. Use JPG/PNG/WebP under 5MB.';
            } else {
                if (!$thumb) $thumb = 'default-course.jpg';

                $pdo->prepare("INSERT INTO courses 
                    (category_id, teacher_id, title, title_si, slug, short_desc, description, thumbnail, duration, level, price, is_featured, is_published) 
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
                    ->execute([
                        $cat, $teacher, $title, $title_si, $slug, $short, $desc, 
                        $thumb, $duration, $level, $price, $featured, $published
                    ]);

                redirect('admin-courses.php?msg=added');
            }
        }
    }
}

$page_title = 'Add Course';
require_once 'includes/admin-header.php';
?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="table-card">
    <div class="card-header">Add New Course</div>
    <div class="p-4">
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control" required value="<?php echo e($_POST['title'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" placeholder="auto" value="<?php echo e($_POST['slug'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Title (Sinhala)</label>
                    <input type="text" name="title_si" class="form-control" value="<?php echo e($_POST['title_si'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">Select</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $c['id']) ? 'selected' : ''; ?>>
                                <?php echo e($c['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Teacher</label>
                    <select name="teacher_id" class="form-select">
                        <option value="">Select</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?php echo $t['id']; ?>" <?php echo (isset($_POST['teacher_id']) && $_POST['teacher_id'] == $t['id']) ? 'selected' : ''; ?>>
                                <?php echo e($t['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Course Image</label>
                    <input type="file" name="thumbnail" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                    <small class="text-muted">JPG, PNG, WebP – max 5MB</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Duration</label>
                    <input type="text" name="duration" class="form-control" placeholder="1 Year" value="<?php echo e($_POST['duration'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Level</label>
                    <select name="level" class="form-select">
                        <?php foreach (['A/L','Grade 6','Grade 7','Beginner','Intermediate'] as $l): ?>
                            <option value="<?php echo $l; ?>" <?php echo (isset($_POST['level']) && $_POST['level'] === $l) ? 'selected' : ''; ?>>
                                <?php echo $l; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Price (Rs.)</label>
                    <input type="number" name="price" class="form-control" value="<?php echo e($_POST['price'] ?? '0'); ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Short Description</label>
                    <textarea name="short_desc" class="form-control" rows="2"><?php echo e($_POST['short_desc'] ?? ''); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Full Description (HTML ok)</label>
                    <textarea name="description" class="form-control" rows="5"><?php echo e($_POST['description'] ?? ''); ?></textarea>
                </div>
                <div class="col-12">
                    <div class="form-check form-check-inline">
                        <input type="checkbox" name="is_featured" class="form-check-input" id="f" <?php echo isset($_POST['is_featured']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="f">Featured</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input type="checkbox" name="is_published" class="form-check-input" id="p" checked>
                        <label class="form-check-label" for="p">Published</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Create Course
                    </button>
                    <a href="admin-courses.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/admin-footer.php'; ?>