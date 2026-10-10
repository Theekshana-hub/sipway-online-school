<?php
$page_title = 'Courses';
require_once 'includes/header.php';
$pdo = getDB();
$cat_slug = $_GET['cat'] ?? '';
$search = trim($_GET['q'] ?? '');

// cat slug එක parent එකක් නම් ඒකේ sub-categories වල courses ත් එනවා
$sql = "SELECT c.*, cat.name AS cat_name, cat.slug AS cat_slug, cat.icon AS cat_icon, cat.color AS cat_color,
               par.name AS parent_name
        FROM courses c
        LEFT JOIN categories cat ON c.category_id = cat.id
        LEFT JOIN categories par ON cat.parent_id = par.id
        WHERE c.is_published = 1";
$params = [];
if ($cat_slug) {
    $sql .= " AND (cat.slug = ? OR par.slug = ?)";
    $params[] = $cat_slug;
    $params[] = $cat_slug;
}
if ($search) {
    $sql .= " AND (c.title LIKE ? OR c.short_desc LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY c.is_featured DESC, c.title";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll();

// Categories tree
$allCats = $pdo->query("SELECT * FROM categories ORDER BY sort_order, name")->fetchAll();
$topCats = [];
$subCats = [];
$activeParentSlug = '';
foreach ($allCats as $ct) {
    if (empty($ct['parent_id'])) $topCats[] = $ct;
    else $subCats[$ct['parent_id']][] = $ct;
}
// Active එක sub-category එකක් නම් එහි parent එක open කරන්න
foreach ($topCats as $t) {
    foreach ($subCats[$t['id']] ?? [] as $s) {
        if ($s['slug'] === $cat_slug) $activeParentSlug = $t['slug'];
    }
}
?>
<section class="page-header"><div class="container position-relative"><h1 data-aos="fade-up">Our Classes</h1>
<nav aria-label="breadcrumb" data-aos="fade-up" data-aos-delay="80"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="index.php">Home</a></li><li class="breadcrumb-item active">Courses</li></ol></nav></div></section>
<section class="section"><div class="container"><div class="row g-4">
<div class="col-lg-3"><div class="lesson-sidebar" data-aos="fade-right">
<h5><i class="fas fa-filter me-2"></i>Categories</h5>
<ul class="lesson-list">
<li><a href="courses.php" class="<?php echo !$cat_slug?'active':''; ?>"><i class="fas fa-th"></i> All Classes</a></li>
<?php foreach ($topCats as $cat):
    $subs = $subCats[$cat['id']] ?? [];
    $isOpen = ($cat_slug === $cat['slug'] || $activeParentSlug === $cat['slug']);
?>
<li><a href="courses.php?cat=<?php echo e($cat['slug']); ?>" class="<?php echo $cat_slug===$cat['slug']?'active':''; ?>"><i class="<?php echo e($cat['icon']); ?>"></i> <?php echo e($cat['name']); ?><?php if ($subs): ?> <i class="fas fa-chevron-<?php echo $isOpen ? 'up' : 'down'; ?> float-end small mt-1"></i><?php endif; ?></a></li>
<?php if ($subs && $isOpen): foreach ($subs as $sub): ?>
<li style="padding-left:18px"><a href="courses.php?cat=<?php echo e($sub['slug']); ?>" class="<?php echo $cat_slug===$sub['slug']?'active':''; ?>" style="font-size:.92em"><i class="fas fa-angle-right"></i> <?php echo e($sub['name']); ?></a></li>
<?php endforeach; endif; ?>
<?php endforeach; ?>
</ul>
<h5 class="mt-3"><i class="fas fa-search me-2"></i>Search</h5>
<form method="GET" class="mt-2">
<?php if ($cat_slug): ?><input type="hidden" name="cat" value="<?php echo e($cat_slug); ?>"><?php endif; ?>
<div class="input-group"><input type="text" name="q" class="form-control form-control-sm" placeholder="Search..." value="<?php echo e($search); ?>"><button class="btn btn-primary btn-sm" type="submit"><i class="fas fa-search"></i></button></div>
</form></div></div>
<div class="col-lg-9">
<p class="text-muted mb-3" data-aos="fade-up"><?php echo count($courses); ?> classe(s) found</p>
<div class="row g-4">
<?php if (empty($courses)): ?>
<div class="col-12 text-center py-5"><i class="fas fa-book-open fa-3x text-muted mb-3"></i><h4>No classes found</h4><a href="courses.php" class="btn btn-primary">View All</a></div>
<?php else: foreach ($courses as $i => $c): ?>
<div class="col-md-6" data-aos="fade-up" data-aos-delay="<?php echo ($i%4)*70; ?>">
<div class="course-card">
<?php $cImg = imageUrl('courses', $c['thumbnail']??null); ?>
<div class="course-thumb" style="<?php echo $cImg ? 'background:url('.e($cImg).') center/cover;' : 'background:linear-gradient(135deg,'.e($c['cat_color']??'#1A1A1A').',#E30613)'; ?>">
<?php if (!$cImg): ?><i class="<?php echo e($c['cat_icon']??'fas fa-book'); ?>"></i><?php endif; ?>
<?php if ($c['is_featured']): ?><span class="course-badge">Featured</span><?php endif; ?>
<span class="course-level"><?php echo e($c['level']); ?></span>
</div>
<div class="course-body">
<div class="course-category"><?php echo e(($c['parent_name'] ? $c['parent_name'].' › ' : '') . ($c['cat_name']??'')); ?></div>
<h3 class="course-title"><a href="course-details.php?slug=<?php echo e($c['slug']); ?>" style="color:inherit"><?php echo e($c['title']); ?></a></h3>
<?php if ($c['title_si']): ?><p class="small text-muted mb-1"><?php echo e($c['title_si']); ?></p><?php endif; ?>
<p class="course-desc"><?php echo e($c['short_desc']); ?></p>
<div class="course-meta"><span><i class="far fa-clock"></i> <?php echo e($c['duration']); ?></span><span class="course-price"><?php echo formatPrice($c['price']); ?></span></div>
</div></div></div>
<?php endforeach; endif; ?>
</div></div></div></div></section>
<?php require_once 'includes/footer.php'; ?>