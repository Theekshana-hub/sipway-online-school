<?php
$page_title = 'Courses';
require_once 'includes/header.php';
$pdo = getDB();
if (session_status() === PHP_SESSION_NONE) session_start();

$cat_slug = $_GET['cat'] ?? '';
$search   = trim($_GET['q'] ?? '');

// ===== Column තියෙනවද කියලා check කරන function =====
function hasColumn(PDO $pdo, string $table, string $column): bool {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $pdo->quote($column));
        return (bool)$stmt->fetch();
    } catch (Exception $e) {
        return false;
    }
}

// Teacher column එක හොයාගන්න
$teacherColumn = null;
$possibleColumns = ['teacher_id', 'instructor_id', 'lecturer_id', 'teacher', 'user_id'];
foreach ($possibleColumns as $col) {
    if (hasColumn($pdo, 'courses', $col)) {
        $teacherColumn = $col;
        break;
    }
}

// ===== Courses query =====
$sql = "SELECT c.*, 
               cat.name AS cat_name, cat.slug AS cat_slug, cat.icon AS cat_icon, cat.color AS cat_color,
               par.name AS parent_name";
               
if ($teacherColumn) {
    $sql .= ", t.full_name AS teacher_name, t.photo AS teacher_photo";
} else {
    $sql .= ", NULL AS teacher_name, NULL AS teacher_photo";
}

$sql .= " FROM courses c
          LEFT JOIN categories cat ON c.category_id = cat.id
          LEFT JOIN categories par ON cat.parent_id = par.id";

if ($teacherColumn) {
    $sql .= " LEFT JOIN teachers t ON c.`$teacherColumn` = t.id";
}

$sql .= " WHERE c.is_published = 1";
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

// Paid courses
$paidCourseIds = [];
if (!empty($_SESSION['user_id'])) {
    $ps = $pdo->prepare("SELECT course_id FROM course_orders WHERE user_id = ? AND status = 'paid'");
    $ps->execute([$_SESSION['user_id']]);
    $paidCourseIds = array_map('intval', $ps->fetchAll(PDO::FETCH_COLUMN));
}

// Categories tree
$allCats = $pdo->query("SELECT * FROM categories ORDER BY sort_order, name")->fetchAll();
$topCats = [];
$subCats = [];
$activeParentSlug = '';
foreach ($allCats as $ct) {
    if (empty($ct['parent_id'])) $topCats[] = $ct;
    else $subCats[$ct['parent_id']][] = $ct;
}
foreach ($topCats as $t) {
    foreach ($subCats[$t['id']] ?? [] as $s) {
        if ($s['slug'] === $cat_slug) $activeParentSlug = $t['slug'];
    }
}
?>
<style>
.cg-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(260px,1fr));
    gap:24px;
}
.cg-grid .cg-item{ min-width:0; }
.cg-grid .course-card{
    height:100%;
    display:flex;
    flex-direction:column;
    overflow:hidden;
    border-radius:16px;
    background:#fff;
    box-shadow:0 4px 20px rgba(0,0,0,.07);
    transition:transform .25s, box-shadow .25s;
}
.cg-grid .course-card:hover{
    transform:translateY(-6px);
    box-shadow:0 14px 36px rgba(0,0,0,.13);
}
.cg-grid .course-thumb.cg-thumb{
    position:relative;
    width:100%;
    height:180px;
    overflow:hidden;
    background:#1c1e24;
}
.cg-thumb .cg-img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
    transition:transform .5s;
}
.cg-grid .course-card:hover .cg-img{
    transform:scale(1.06);
}
.cg-thumb .course-badge{ 
    z-index:2; 
    position:absolute;
    top:12px; left:12px;
    background:#E30613;
    color:#fff;
    font-size:12px;
    font-weight:700;
    padding:5px 12px;
    border-radius:999px;
}
.cg-grid .course-body{
    flex:1;
    display:flex;
    flex-direction:column;
    padding:18px;
}
.course-category{
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    max-width:100%;
    font-size:13px;
    color:#E30613;
    font-weight:600;
    margin-bottom:6px;
}
.course-title{
    font-size:17px;
    font-weight:700;
    margin:0 0 4px;
    line-height:1.35;
}
.course-desc{
    font-size:14px;
    color:#666;
    margin-bottom:12px;
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
}

/* Teacher mini info */
.course-teacher {
    display:flex;
    align-items:center;
    gap:10px;
    margin:8px 0 12px;
    padding:8px 10px;
    background:#f8f9fa;
    border-radius:10px;
}
.course-teacher img {
    width:36px;
    height:36px;
    border-radius:50%;
    object-fit:cover;
    border:2px solid #e5e7eb;
    flex-shrink:0;
}
.course-teacher-fallback {
    width:36px;
    height:36px;
    border-radius:50%;
    background:#fee2e2;
    color:#E30613;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:15px;
    flex-shrink:0;
}
.course-teacher-name {
    font-size:13.5px;
    font-weight:600;
    color:#222;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.course-meta{
    display:flex;
    justify-content:space-between;
    align-items:center;
    font-size:14px;
    color:#555;
    border-top:1px dashed #eee;
    padding-top:12px;
    margin-top:auto;
}
.course-price{
    font-weight:700;
    color:#111;
}
.cg-actions{ display:flex; gap:8px; margin-top:14px; }
.cg-actions .btn{ flex:1; font-size:.85rem; padding:.5rem .5rem; border-radius:8px; }

@media (max-width:575px){
    .cg-grid{ grid-template-columns:1fr; }
}
</style>

<section class="page-header">
    <div class="container position-relative">
        <h1 data-aos="fade-up">Our Classes</h1>
        <nav aria-label="breadcrumb" data-aos="fade-up" data-aos-delay="80">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Courses</li>
            </ol>
        </nav>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3">
                <div class="lesson-sidebar" data-aos="fade-right">
                    <h5><i class="fas fa-filter me-2"></i>Categories</h5>
                    <ul class="lesson-list">
                        <li>
                            <a href="courses.php" class="<?php echo !$cat_slug ? 'active' : ''; ?>">
                                <i class="fas fa-th"></i> All Classes
                            </a>
                        </li>
                        <?php foreach ($topCats as $cat):
                            $subs = $subCats[$cat['id']] ?? [];
                            $isOpen = ($cat_slug === $cat['slug'] || $activeParentSlug === $cat['slug']);
                        ?>
                        <li>
                            <a href="courses.php?cat=<?php echo e($cat['slug']); ?>" 
                               class="<?php echo $cat_slug === $cat['slug'] ? 'active' : ''; ?>">
                                <i class="<?php echo e($cat['icon']); ?>"></i> 
                                <?php echo e($cat['name']); ?>
                                <?php if ($subs): ?>
                                    <i class="fas fa-chevron-<?php echo $isOpen ? 'up' : 'down'; ?> float-end small mt-1"></i>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php if ($subs && $isOpen): foreach ($subs as $sub): ?>
                            <li style="padding-left:18px">
                                <a href="courses.php?cat=<?php echo e($sub['slug']); ?>" 
                                   class="<?php echo $cat_slug === $sub['slug'] ? 'active' : ''; ?>" 
                                   style="font-size:.92em">
                                    <i class="fas fa-angle-right"></i> <?php echo e($sub['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; endif; ?>
                        <?php endforeach; ?>
                    </ul>

                    <h5 class="mt-3"><i class="fas fa-search me-2"></i>Search</h5>
                    <form method="GET" class="mt-2">
                        <?php if ($cat_slug): ?>
                            <input type="hidden" name="cat" value="<?php echo e($cat_slug); ?>">
                        <?php endif; ?>
                        <div class="input-group">
                            <input type="text" name="q" class="form-control form-control-sm" 
                                   placeholder="Search..." value="<?php echo e($search); ?>">
                            <button class="btn btn-primary btn-sm" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-9">
                <p class="text-muted mb-3" data-aos="fade-up"><?php echo count($courses); ?> classe(s) found</p>

                <?php if (empty($courses)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                        <h4>No classes found</h4>
                        <a href="courses.php" class="btn btn-primary">View All</a>
                    </div>
                <?php else: ?>
                    <div class="cg-grid">
                        <?php foreach ($courses as $i => $c):
                            $cImg        = imageUrl('courses', $c['thumbnail'] ?? null);
                            $teacherImg  = !empty($c['teacher_photo']) ? imageUrl('teachers', $c['teacher_photo']) : null;
                            $teacherName = $c['teacher_name'] ?? '';
                            $isPaid      = in_array((int)$c['id'], $paidCourseIds, true);
                        ?>
                        <div class="cg-item" data-aos="fade-up" data-aos-delay="<?php echo ($i % 3) * 70; ?>">
                            <div class="course-card">
                                <?php if ($cImg): ?>
                                    <div class="course-thumb cg-thumb">
                                        <img class="cg-img" src="<?php echo e($cImg); ?>" alt="<?php echo e($c['title']); ?>" loading="lazy">
                                        <?php if ($c['is_featured']): ?>
                                            <span class="course-badge">Featured</span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="course-thumb cg-thumb" 
                                         style="background:linear-gradient(135deg,<?php echo e($c['cat_color'] ?? '#1A1A1A'); ?>,#E30613);display:flex;align-items:center;justify-content:center;">
                                        <i class="<?php echo e($c['cat_icon'] ?? 'fas fa-book'); ?>" style="font-size:48px;color:rgba(255,255,255,.85);"></i>
                                        <?php if ($c['is_featured']): ?>
                                            <span class="course-badge">Featured</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="course-body">
                                    <div class="course-category"><?php echo e($c['cat_name'] ?? ''); ?></div>
                                    
                                    <h3 class="course-title">
                                        <a href="course-details.php?slug=<?php echo e($c['slug']); ?>" style="color:inherit">
                                            <?php echo e($c['title']); ?>
                                        </a>
                                    </h3>
                                    
                                    <?php if (!empty($c['title_si'])): ?>
                                        <p class="small text-muted mb-1"><?php echo e($c['title_si']); ?></p>
                                    <?php endif; ?>

                                    <!-- Teacher -->
                                    <?php if ($teacherName): ?>
                                        <div class="course-teacher">
                                            <?php if ($teacherImg): ?>
                                                <img src="<?php echo e($teacherImg); ?>" alt="<?php echo e($teacherName); ?>" loading="lazy">
                                            <?php else: ?>
                                                <span class="course-teacher-fallback">
                                                    <i class="fas fa-user-tie"></i>
                                                </span>
                                            <?php endif; ?>
                                            <span class="course-teacher-name"><?php echo e($teacherName); ?></span>
                                        </div>
                                    <?php endif; ?>

                                    <p class="course-desc"><?php echo e($c['short_desc']); ?></p>
                                    
                                    <div class="course-meta">
                                        <span><i class="far fa-clock"></i> <?php echo e($c['duration']); ?></span>
                                        <span class="course-price"><?php echo formatPrice($c['price']); ?></span>
                                    </div>

                                    <div class="cg-actions">
                                        <a href="course-details.php?slug=<?php echo e($c['slug']); ?>" class="btn btn-outline-secondary">Details</a>
                                        <?php if ($isPaid): ?>
                                            <span class="btn btn-success disabled"><i class="fas fa-check me-1"></i>Enrolled</span>
                                        <?php else: ?>
                                            <a href="checkout.php?slug=<?php echo e($c['slug']); ?>" class="btn btn-primary">
                                                <i class="fas fa-credit-card me-1"></i>
                                                <?php echo ((float)$c['price'] > 0) ? 'Enroll Now' : 'Join Free'; ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>