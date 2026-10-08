<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireStudent();
$pdo = getDB();
$stmt = $pdo->prepare("SELECT c.*, cat.name as cat_name, cat.color as cat_color, cat.icon as cat_icon, e.progress, e.enrolled_at FROM enrollments e JOIN courses c ON e.course_id=c.id LEFT JOIN categories cat ON c.category_id=cat.id WHERE e.user_id=? ORDER BY e.enrolled_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$enrolled = $stmt->fetchAll();
$page_title = 'My Learning';
require_once 'includes/header.php';
?>
<section class="page-header"><div class="container position-relative">
<h1 data-aos="fade-up">My Learning</h1>
<p style="opacity:.85" data-aos="fade-up" data-aos-delay="80">Welcome, <?php echo e($_SESSION['full_name']); ?>!</p>
</div></section>
<section class="section"><div class="container">
<div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-up">
<h3 class="mb-0">My Courses (<?php echo count($enrolled); ?>)</h3>
<a href="courses.php" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Browse Courses</a>
</div>
<?php if (empty($enrolled)): ?>
<div class="text-center py-5" data-aos="fade-up"><i class="fas fa-book-open fa-4x text-muted mb-3"></i><h4>No courses yet</h4><p class="text-muted mb-3">Enroll in a course to start learning!</p><a href="courses.php" class="btn btn-primary btn-lg">Explore Courses</a></div>
<?php else: ?>
<div class="row g-4">
<?php foreach ($enrolled as $i => $c): ?>
<div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $i*70; ?>">
<div class="course-card">
<?php $cImg = imageUrl('courses', $c['thumbnail']??null); ?>
<div class="course-thumb" style="<?php echo $cImg ? 'background:url('.e($cImg).') center/cover;' : 'background:linear-gradient(135deg,'.e($c['cat_color']??'#1A1A1A').',#E30613)'; ?>">
<?php if (!$cImg): ?><i class="<?php echo e($c['cat_icon']??'fas fa-book'); ?>"></i><?php endif; ?>
<span class="course-level"><?php echo e($c['level']); ?></span>
</div>
<div class="course-body">
<div class="course-category"><?php echo e($c['cat_name']??''); ?></div>
<h3 class="course-title"><a href="course-details.php?slug=<?php echo e($c['slug']); ?>" style="color:inherit"><?php echo e($c['title']); ?></a></h3>
<div class="mb-2">
<div class="d-flex justify-content-between mb-1"><small class="text-muted">Progress</small><small style="color:#E30613;font-weight:600"><?php echo (int)$c['progress']; ?>%</small></div>
<div class="progress" style="height:7px;border-radius:50px"><div class="progress-bar" style="width:<?php echo (int)$c['progress']; ?>%;background:linear-gradient(90deg,#E30613,#F5A623);border-radius:50px"></div></div>
</div>
<div class="course-meta"><span><i class="far fa-clock"></i> <?php echo e($c['duration']); ?></span>
<a href="course-details.php?slug=<?php echo e($c['slug']); ?>" class="btn btn-primary btn-sm">Continue</a></div>
</div></div></div>
<?php endforeach; ?>
</div><?php endif; ?>
</div></section>
<?php require_once 'includes/footer.php'; ?>
