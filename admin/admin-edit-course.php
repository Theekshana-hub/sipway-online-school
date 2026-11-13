<?php
require_once __DIR__ . '/includes/admin-auth.php';
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$id]);
$course = $stmt->fetch();
if (!$course) {
    redirect('admin-courses.php');
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order, name")->fetchAll();
$teachers   = $pdo->query("SELECT * FROM teachers WHERE status = 'active' ORDER BY full_name")->fetchAll();

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

/* ---------- Save Schedule ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_schedule') {

    $years  = $_POST['sch_year']  ?? [];
    $dayArr = $_POST['sch_day']   ?? [];
    $starts = $_POST['sch_start'] ?? [];
    $ends   = $_POST['sch_end']   ?? [];
    $fees   = $_POST['sch_fee']   ?? [];

    $saved = false;
    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM course_year_schedules WHERE course_id = ?")->execute([$id]);

        $ins = $pdo->prepare("
            INSERT INTO course_year_schedules 
            (course_id, class_year, class_day, start_time, end_time, fee, sort_order) 
            VALUES (?,?,?,?,?,?,?)
        ");

        $order = 0;
        foreach ($years as $i => $y) {
            $y  = mb_substr(trim($y), 0, 255);
            $d  = $dayArr[$i] ?? '';
            $d  = in_array($d, $days, true) ? $d : null;

            $st = $starts[$i] ?? '';
            $st = preg_match('/^\d{2}:\d{2}$/', $st) ? $st . ':00' : null;

            $en = $ends[$i] ?? '';
            $en = preg_match('/^\d{2}:\d{2}$/', $en) ? $en . ':00' : null;

            $fe = trim((string)($fees[$i] ?? ''));
            $fe = (is_numeric($fe) && (float)$fe >= 0) ? round((float)$fe, 2) : null;

            // skip completely empty row
            if ($y === '' && !$d && !$st && !$en && $fe === null) continue;

            $ins->execute([
                $id,
                $y !== '' ? $y : null,
                $d,
                $st,
                $en,
                $fe,
                $order++
            ]);
        }

        $pdo->commit();
        $saved = true;
    } catch (Exception $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Course schedule save error: ' . $ex->getMessage());
    }

    if ($saved) {
        redirect('admin-edit-course.php?id=' . $id . '&msg=schedule_saved');
    } else {
        redirect('admin-edit-course.php?id=' . $id . '&msg=schedule_error');
    }
}

/* ---------- Save Main Course ---------- */
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'save_schedule') {

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
        $thumb = uploadImage('thumbnail', 'courses', $course['thumbnail']);
        if ($thumb === false) {
            $error = 'Image upload failed. Use JPG/PNG/WebP under 5MB.';
        } else {
            $pdo->prepare("
                UPDATE courses SET 
                    category_id = ?, teacher_id = ?, title = ?, title_si = ?, slug = ?, 
                    short_desc = ?, description = ?, thumbnail = ?, duration = ?, level = ?, 
                    price = ?, is_featured = ?, is_published = ? 
                WHERE id = ?
            ")->execute([
                $cat, $teacher, $title, $title_si, $slug,
                $short, $desc, $thumb, $duration, $level,
                $price, $featured, $published, $id
            ]);
            redirect('admin-courses.php?msg=updated');
        }
    }
}

/* ---------- Load Schedules ---------- */
$schStmt = $pdo->prepare("SELECT * FROM course_year_schedules WHERE course_id = ? ORDER BY sort_order, id");
$schStmt->execute([$id]);
$schedules = $schStmt->fetchAll();

if (empty($schedules)) {
    $schedules = [['class_year' => '', 'class_day' => '', 'start_time' => '', 'end_time' => '', 'fee' => '']];
}

$page_title = 'Edit Course';
require_once 'includes/admin-header.php';

$imgUrl = imageUrl('courses', $course['thumbnail']);
?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo e($error); ?></div>
<?php endif; ?>

<?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] === 'schedule_saved'): ?>
        <div class="alert alert-success">Schedule saved successfully.</div>
    <?php elseif ($_GET['msg'] === 'schedule_error'): ?>
        <div class="alert alert-danger">Could not save schedule.</div>
    <?php endif; ?>
<?php endif; ?>

<!-- ================= Main Course Form ================= -->
<div class="table-card mb-4">
    <div class="card-header">Edit: <?php echo e($course['title']); ?></div>
    <div class="p-4">
        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control" required value="<?php echo e($course['title']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control" value="<?php echo e($course['slug']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Title (Sinhala)</label>
                    <input type="text" name="title_si" class="form-control" value="<?php echo e($course['title_si']); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">Select</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $course['category_id'] == $c['id'] ? 'selected' : ''; ?>>
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
                            <option value="<?php echo $t['id']; ?>" <?php echo $course['teacher_id'] == $t['id'] ? 'selected' : ''; ?>>
                                <?php echo e($t['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Course Image</label>
                    <?php if ($imgUrl): ?>
                        <div class="mb-2">
                            <img src="<?php echo e($imgUrl); ?>" alt="" style="max-height:80px;border-radius:8px">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="thumbnail" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif">
                    <small class="text-muted">Leave empty to keep current image</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Duration</label>
                    <input type="text" name="duration" class="form-control" value="<?php echo e($course['duration']); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Level</label>
                    <select name="level" class="form-select">
                        <?php foreach (['A/L', 'Grade 6', 'Grade 7', 'Beginner', 'Intermediate'] as $l): ?>
                            <option value="<?php echo $l; ?>" <?php echo $course['level'] === $l ? 'selected' : ''; ?>>
                                <?php echo $l; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Price (Rs.)</label>
                    <input type="number" name="price" class="form-control" step="0.01" value="<?php echo e($course['price']); ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Short Description</label>
                    <textarea name="short_desc" class="form-control" rows="2"><?php echo e($course['short_desc']); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Full Description</label>
                    <textarea name="description" class="form-control" rows="5"><?php echo e($course['description']); ?></textarea>
                </div>
                <div class="col-12">
                    <div class="form-check form-check-inline">
                        <input type="checkbox" name="is_featured" class="form-check-input" id="f" <?php echo $course['is_featured'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="f">Featured</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input type="checkbox" name="is_published" class="form-check-input" id="p" <?php echo $course['is_published'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="p">Published</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Update Course
                    </button>
                    <a href="admin-courses.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ================= Schedule Section ================= -->
<div class="table-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Course Schedule (Year / Day / Time / Fee)</span>
        <button type="button" class="btn btn-sm btn-outline-primary" id="addSchRow">
            + Add Year / Day / Time / Fee
        </button>
    </div>
    <div class="p-4">
        <p class="text-muted small mb-3">
            You can add multiple years, days, times and fees. Use ✕ to remove a row. 
            Click <strong>Save Schedule</strong> after making changes.
        </p>

        <form method="POST" action="admin-edit-course.php?id=<?php echo $id; ?>">
            <input type="hidden" name="action" value="save_schedule">

            <div id="schRows">
                <?php foreach ($schedules as $sc): ?>
                    <div class="row g-2 align-items-end mb-2 sch-row">
                        <div class="col-md-3">
                            <label class="form-label small">Class Year</label>
                            <input type="text" name="sch_year[]" class="form-control" maxlength="255"
                                   placeholder="e.g. 2027 A/L" 
                                   value="<?php echo e($sc['class_year'] ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Class Day</label>
                            <select name="sch_day[]" class="form-select">
                                <option value="">—</option>
                                <?php foreach ($days as $d): ?>
                                    <option value="<?php echo $d; ?>" <?php echo ($sc['class_day'] ?? '') === $d ? 'selected' : ''; ?>>
                                        <?php echo $d; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Start Time</label>
                            <input type="time" name="sch_start[]" class="form-control"
                                   value="<?php echo !empty($sc['start_time']) ? e(substr($sc['start_time'], 0, 5)) : ''; ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">End Time</label>
                            <input type="time" name="sch_end[]" class="form-control"
                                   value="<?php echo !empty($sc['end_time']) ? e(substr($sc['end_time'], 0, 5)) : ''; ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Fee (Rs.)</label>
                            <input type="number" name="sch_fee[]" class="form-control" step="0.01" min="0" 
                                   placeholder="e.g. 3000"
                                   value="<?php echo ($sc['fee'] ?? '') !== '' && $sc['fee'] !== null ? e($sc['fee']) : ''; ?>">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-danger w-100 sch-remove" title="Remove">✕</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Template for new rows -->
            <template id="schTpl">
                <div class="row g-2 align-items-end mb-2 sch-row">
                    <div class="col-md-3">
                        <label class="form-label small">Class Year</label>
                        <input type="text" name="sch_year[]" class="form-control" maxlength="255" placeholder="e.g. 2027 A/L">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Class Day</label>
                        <select name="sch_day[]" class="form-select">
                            <option value="">—</option>
                            <?php foreach ($days as $d): ?>
                                <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Start Time</label>
                        <input type="time" name="sch_start[]" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">End Time</label>
                        <input type="time" name="sch_end[]" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Fee (Rs.)</label>
                        <input type="number" name="sch_fee[]" class="form-control" step="0.01" min="0" placeholder="e.g. 3000">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-danger w-100 sch-remove" title="Remove">✕</button>
                    </div>
                </div>
            </template>

            <button type="submit" class="btn btn-primary mt-3">
                <i class="fas fa-save me-1"></i> Save Schedule
            </button>
        </form>
    </div>
</div>

<script>
(function () {
    var wrap = document.getElementById('schRows');
    var tpl  = document.getElementById('schTpl');

    // Add new row
    document.getElementById('addSchRow').addEventListener('click', function () {
        wrap.appendChild(tpl.content.cloneNode(true));
    });

    // Remove row
    wrap.addEventListener('click', function (e) {
        var btn = e.target.closest('.sch-remove');
        if (!btn) return;

        var rows = wrap.querySelectorAll('.sch-row');
        var row  = btn.closest('.sch-row');

        if (rows.length === 1) {
            // Clear the last remaining row instead of removing it
            row.querySelectorAll('input').forEach(function (i) { i.value = ''; });
            row.querySelectorAll('select').forEach(function (s) { s.value = ''; });
        } else {
            row.remove();
        }
    });
})();
</script>

<?php require_once 'includes/admin-footer.php'; ?>