<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
$slug = $_GET['slug'] ?? '';
if (!$slug) redirect('courses.php');
$pdo = getDB();
$stmt = $pdo->prepare("SELECT c.*, cat.name as cat_name, cat.color as cat_color, t.full_name as teacher_name FROM courses c LEFT JOIN categories cat ON c.category_id=cat.id LEFT JOIN teachers t ON c.teacher_id=t.id WHERE c.slug=? AND c.is_published=1");
$stmt->execute([$slug]);
$course = $stmt->fetch();
if (!$course) redirect('courses.php');
$pdo->prepare("UPDATE courses SET views=views+1 WHERE id=?")->execute([$course['id']]);
$lessons = $pdo->prepare("SELECT * FROM lessons WHERE course_id=? AND is_published=1 ORDER BY sort_order");
$lessons->execute([$course['id']]);
$lessons = $lessons->fetchAll();

$isFree = (float)$course['price'] <= 0;

// ★ Student දැනටමත් enroll වෙලාද (enrollments හෝ paid order එකක්)
$isEnrolled = false;
if (isStudent()) {
    $chk = $pdo->prepare("SELECT 1 FROM enrollments WHERE user_id=? AND course_id=? LIMIT 1");
    $chk->execute([$_SESSION['user_id'], $course['id']]);
    $isEnrolled = (bool)$chk->fetch();
    if (!$isEnrolled) {
        $chk = $pdo->prepare("SELECT 1 FROM course_orders WHERE user_id=? AND course_id=? AND status='paid' LIMIT 1");
        $chk->execute([$_SESSION['user_id'], $course['id']]);
        $isEnrolled = (bool)$chk->fetch();
    }
}

// ★ Enroll: FREE courses වලට විතරයි direct enroll. Paid ඒවා checkout (ගෙවීම) හරහා විතරයි.
if (isset($_GET['enroll']) && isStudent()) {
    if (!$isFree && !$isEnrolled) {
        redirect('checkout.php?slug=' . urlencode($slug));
    }
    if ($isFree && !$isEnrolled) {
        $pdo->prepare("INSERT INTO enrollments (user_id, course_id) VALUES (?,?)")->execute([$_SESSION['user_id'], $course['id']]);
    }
    redirect('student-dashboard.php');
}

$page_title = $course['title'];
require_once 'includes/header.php';
?>
<section class="page-header"><div class="container position-relative">
<div class="row align-items-center">
<div class="col-lg-8" data-aos="fade-up">
<span class="badge mb-2" style="background:<?php echo e($course['cat_color']??'#E30613'); ?>"><?php echo e($course['cat_name']); ?></span>
<h1><?php echo e($course['title']); ?></h1>
<?php if ($course['title_si']): ?><p style="opacity:.85;font-size:1.1rem"><?php echo e($course['title_si']); ?></p><?php endif; ?>
<p style="opacity:.8"><?php echo e($course['short_desc']); ?></p>
<div class="d-flex flex-wrap gap-3 mt-2">
<span><i class="far fa-clock me-1"></i> <?php echo e($course['duration']); ?></span>
<span><i class="fas fa-signal me-1"></i> <?php echo e($course['level']); ?></span>
<span><i class="fas fa-book me-1"></i> <?php echo count($lessons); ?> Lessons</span>
<span><i class="fas fa-user me-1"></i> <?php echo e($course['teacher_name']??'Sipway Faculty'); ?></span>
</div></div>
<div class="col-lg-4 text-lg-end mt-3 mt-lg-0" data-aos="fade-left">
<div class="bg-white text-dark rounded-4 p-4 d-inline-block shadow">
<h3 class="mb-2" style="color:#E30613"><?php echo formatPrice($course['price']); ?></h3>
<?php if (isStudent()): ?>
    <?php if ($isEnrolled): ?>
        <a href="student-dashboard.php" class="btn btn-success btn-lg w-100"><i class="fas fa-check me-1"></i> Enrolled · Go to My Learning</a>
    <?php elseif ($isFree): ?>
        <a href="?slug=<?php echo e($slug); ?>&enroll=1" class="btn btn-primary btn-lg w-100">Enroll Free</a>
    <?php else: ?>
        <a href="checkout.php?slug=<?php echo e($slug); ?>" class="btn btn-primary btn-lg w-100"><i class="fas fa-credit-card me-1"></i> Enroll Now</a>
    <?php endif; ?>
<?php else: ?>
<a href="login.php?redirect=<?php echo urlencode('course-details.php?slug=' . $slug); ?>" class="btn btn-primary btn-lg w-100">Login to Enroll</a>
<?php endif; ?>
</div></div></div></div></section>
<section class="section"><div class="container"><div class="row g-4">
<div class="col-lg-8">
<div class="lesson-content" data-aos="fade-up"><h3 class="mb-3"><i class="fas fa-info-circle me-2" style="color:#E30613"></i>About This Course</h3><?php echo $course['description']; ?></div>
<div class="lesson-content mt-3" data-aos="fade-up"><h3 class="mb-3"><i class="fas fa-list-ul me-2" style="color:#E30613"></i>Curriculum</h3>
<?php if (empty($lessons)): ?><p class="text-muted">Lessons coming soon...</p>
<?php else: ?><div class="list-group list-group-flush">
<?php foreach ($lessons as $idx => $l): ?>
<a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 border-0 border-bottom">
<div class="d-flex align-items-center gap-3">
<span class="badge rounded-circle d-flex align-items-center justify-content-center" style="width:30px;height:30px;background:#E30613"><?php echo $idx+1; ?></span>
<div><strong><?php echo e($l['title']); ?></strong><?php if ($l['is_free']): ?><span class="badge bg-success ms-2">Free</span><?php endif; ?></div>
</div>
<span class="text-muted small"><i class="far fa-clock me-1"></i><?php echo e($l['duration']); ?></span>
</a>
<?php endforeach; ?></div><?php endif; ?>
</div></div>
<div class="col-lg-4"><div class="lesson-sidebar" data-aos="fade-left">
<h5>Course Details</h5>
<ul class="list-unstyled">
<li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Duration</span><strong><?php echo e($course['duration']); ?></strong></li>
<li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Level</span><strong><?php echo e($course['level']); ?></strong></li>
<li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Lessons</span><strong><?php echo count($lessons); ?></strong></li>
<li class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Teacher</span><strong><?php echo e($course['teacher_name']??'Faculty'); ?></strong></li>
<li class="d-flex justify-content-between py-2"><span class="text-muted">Language</span><strong>Sinhala / English</strong></li>
</ul></div></div>
</div></div></section>
<?php require_once 'includes/footer.php'; ?>