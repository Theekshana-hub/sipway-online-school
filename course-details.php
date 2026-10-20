<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
$slug = $_GET['slug'] ?? '';
if (!$slug) redirect('courses.php');
$pdo = getDB();
$stmt = $pdo->prepare("SELECT c.*, cat.name as cat_name, cat.color as cat_color
                       FROM courses c
                       LEFT JOIN categories cat ON c.category_id=cat.id
                       WHERE c.slug=? AND c.is_published=1");
$stmt->execute([$slug]);
$course = $stmt->fetch();
if (!$course) redirect('courses.php');
$pdo->prepare("UPDATE courses SET views=views+1 WHERE id=?")->execute([$course['id']]);

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

// =====================================================
// ★ Teacher match logic
//   0) "(Sinhala Medium)" / "English Medium" wage medium words ain karanawa
//   1) subject eke words okkoma course (title + category) eke thiyenna one
//   2) subject ekata level (6, 7, A/L, O/L) thiyenawanam course ekatath samana level ekak thiyenna one
//   3) subject ekata level NATHNAM -> teacher ta course eke category eka tick karala thiyenna one
// =====================================================
$stopWords = ['grade', 'grades', 'class', 'classes', 'and', 'the', 'for', 'level', 'medium'];

$parseText = function ($s) use ($stopWords) {
    $s = mb_strtolower(trim((string)$s), 'UTF-8');

    $s = preg_replace('/\(?\s*(?:in\s+)?(?:sinhala|english|tamil)\s+medium\s*\)?/u', ' ', $s);
    $s = preg_replace('/\(?\s*medium\s*[:\-]?\s*(?:sinhala|english|tamil)\s*\)?/u', ' ', $s);

    $s = preg_replace_callback('/(\d+)\s*(?:-|–|to)\s*(\d+)/u', function ($m) {
        $a = (int)$m[1]; $b = (int)$m[2];
        if ($b >= $a && ($b - $a) <= 13) return implode(' ', range($a, $b));
        return $m[1] . ' ' . $m[2];
    }, $s);

    $s = preg_replace('/[^\p{L}\p{N}\p{M}]+/u', ' ', $s);
    $s = ' ' . trim($s) . ' ';
    $s = preg_replace('/ a l /u', ' al ', $s);
    $s = preg_replace('/ o l /u', ' ol ', $s);
    $s = trim(preg_replace('/\s+/u', ' ', $s));

    $words = [];
    $levels = [];
    if ($s !== '') {
        foreach (array_unique(explode(' ', $s)) as $tk) {
            if (in_array($tk, $stopWords, true)) continue;
            if (ctype_digit($tk) || $tk === 'al' || $tk === 'ol') $levels[] = $tk;
            else $words[] = $tk;
        }
    }
    return [$words, $levels];
};

list($cw1, $cl1) = $parseText($course['title'] ?? '');
list($cw2, $cl2) = $parseText($course['cat_name'] ?? '');
list($cw3, $cl3) = $parseText($course['level'] ?? '');
$courseWords  = array_values(array_unique(array_merge($cw1, $cw2)));
$courseLevels = array_values(array_unique(array_merge($cl1, $cl2, $cl3)));

$teacherCats = [];
foreach ($pdo->query("SELECT teacher_id, category_id FROM teacher_categories")->fetchAll() as $row) {
    $teacherCats[(int)$row['teacher_id']][] = (int)$row['category_id'];
}
$courseCatId = (int)($course['category_id'] ?? 0);

$teacherRows = $pdo->query("SELECT * FROM teachers WHERE status = 'active' ORDER BY full_name")->fetchAll();

$courseTeachers = [];
foreach ($teacherRows as $tr) {
    $tid = (int)$tr['id'];
    $linkedCats = $teacherCats[$tid] ?? [];
    if (!empty($tr['category_id'])) $linkedCats[] = (int)$tr['category_id'];
    $isLinked = $courseCatId > 0 && in_array($courseCatId, $linkedCats, true);

    $matchedSubjects = [];
    foreach (explode(',', (string)($tr['subject'] ?? '')) as $s) {
        $orig = trim($s);
        if ($orig === '') continue;

        list($subWords, $subLevels) = $parseText($orig);
        if (!$subWords) continue;

        if (array_diff($subWords, $courseWords)) continue;

        if ($subLevels) {
            if (!array_intersect($subLevels, $courseLevels)) continue;
        } else {
            if (!$isLinked) continue;
        }

        $matchedSubjects[] = $orig;
    }
    if ($matchedSubjects) {
        $tr['matched_subjects'] = implode(', ', $matchedSubjects);
        $tr['img'] = imageUrl('teachers', $tr['photo'] ?? null);
        $courseTeachers[] = $tr;
    }
}
$teacherNames = array_map(function ($tt) { return $tt['full_name']; }, $courseTeachers);

// ★ Course image
$courseImg = imageUrl('courses', $course['thumbnail'] ?? null);

$page_title = $course['title'];
require_once 'includes/header.php';
?>
<style>
/* ===== Hero ===== */
.cd-hero{
    position:relative;
    background:linear-gradient(135deg,#111 0%,#1A1A1A 55%,#3a0a0e 100%);
    color:#fff;
    padding:120px 0 70px;
    overflow:hidden;
}
.cd-hero::before{
    content:'';
    position:absolute;
    right:-120px; top:-120px;
    width:420px; height:420px;
    border-radius:50%;
    background:radial-gradient(circle,rgba(227,6,19,.35),transparent 70%);
}
.cd-hero .container{ position:relative; z-index:1; }
.cd-breadcrumb{
    font-size:.88rem;
    opacity:.75;
    margin-bottom:14px;
}
.cd-breadcrumb a{ color:#fff; text-decoration:none; }
.cd-breadcrumb a:hover{ text-decoration:underline; }
.cd-cat-badge{
    display:inline-block;
    padding:6px 16px;
    border-radius:50px;
    font-size:.8rem;
    font-weight:600;
    letter-spacing:.3px;
    margin-bottom:14px;
    color:#fff;
}
.cd-hero h1{
    font-weight:800;
    font-size:clamp(1.8rem,4vw,2.8rem);
    line-height:1.2;
    margin-bottom:8px;
}
.cd-hero .cd-sub-si{ font-size:1.1rem; opacity:.85; margin-bottom:6px; }
.cd-hero .cd-short{ opacity:.8; max-width:640px; }

.cd-chips{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-top:22px;
}
.cd-chip{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:rgba(255,255,255,.1);
    border:1px solid rgba(255,255,255,.15);
    backdrop-filter:blur(4px);
    padding:8px 16px;
    border-radius:50px;
    font-size:.9rem;
}
.cd-chip i{ color:#ff5a63; }
.cd-avatars{ display:inline-flex; }
.cd-avatar{
    width:26px; height:26px;
    border-radius:50%;
    object-fit:cover;
    border:2px solid rgba(255,255,255,.85);
    margin-left:-8px;
    background:#333;
}
.cd-avatars .cd-avatar:first-child{ margin-left:0; }

/* ===== Price / enroll card ===== */
.cd-buy{
    background:#fff;
    color:#1A1A1A;
    border-radius:22px;
    padding:26px 26px 24px;
    box-shadow:0 20px 50px rgba(0,0,0,.35);
    text-align:center;
}
.cd-buy small{
    display:block;
    color:#888;
    text-transform:uppercase;
    letter-spacing:.6px;
    font-size:.72rem;
    margin-bottom:2px;
}
.cd-price{
    font-size:2.1rem;
    font-weight:800;
    color:#E30613;
    line-height:1.2;
    margin-bottom:14px;
}
.cd-buy .btn{ border-radius:50px; padding:.8rem 1rem; font-weight:700; }

/* ===== Body ===== */
.cd-body{ padding:50px 0 70px; background:#f7f7f9; }

.cd-image{
    position:relative;
    width:100%;
    aspect-ratio:16 / 9;
    border-radius:20px;
    overflow:hidden;
    background:#111;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 10px 34px rgba(0,0,0,.12);
    margin-bottom:24px;
}
.cd-image .cd-bg{
    position:absolute;
    inset:-20px;
    background-size:cover;
    background-position:center;
    filter:blur(20px) brightness(.8);
    transform:scale(1.1);
}
.cd-image img{
    position:relative;
    z-index:1;
    width:100%;
    height:100%;
    object-fit:contain;
    display:block;
}
.cd-image::after{
    content:'';
    position:absolute;
    left:0; right:0; bottom:0;
    height:4px;
    background:linear-gradient(90deg,#1A1A1A,#E30613);
    z-index:2;
}

.cd-card{
    background:#fff;
    border-radius:20px;
    padding:30px 32px;
    box-shadow:0 6px 24px rgba(0,0,0,.06);
}
.cd-card-title{
    display:flex;
    align-items:center;
    gap:12px;
    font-weight:800;
    font-size:1.35rem;
    margin-bottom:18px;
    color:#1A1A1A;
}
.cd-card-title .cd-ico{
    width:40px; height:40px;
    border-radius:12px;
    background:rgba(227,6,19,.1);
    color:#E30613;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:1rem;
}
.cd-desc{ color:#555; line-height:1.8; }
.cd-desc img{ max-width:100%; height:auto; border-radius:12px; }
.cd-empty{ color:#999; font-style:italic; margin:0; }

/* ===== Sidebar ===== */
.cd-side{ position:sticky; top:96px; }
.cd-detail-list{ list-style:none; padding:0; margin:0; }
.cd-detail-list li{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:13px 0;
    border-bottom:1px dashed #e8e8ec;
}
.cd-detail-list li:last-child{ border-bottom:0; }
.cd-detail-list .cd-k{
    display:flex;
    align-items:center;
    gap:10px;
    color:#777;
}
.cd-detail-list .cd-k i{ color:#E30613; width:18px; text-align:center; }
.cd-detail-list strong{ color:#1A1A1A; text-align:right; }

.cd-teachers-title{
    margin-top:22px;
    padding-top:20px;
    border-top:1px solid #eee;
    font-size:.78rem;
    color:#888;
    text-transform:uppercase;
    letter-spacing:.6px;
    font-weight:600;
}
.cd-teacher{
    display:flex;
    align-items:center;
    gap:14px;
    margin-top:14px;
    padding:10px;
    border-radius:14px;
    background:#fafafb;
    transition:background .2s;
}
.cd-teacher:hover{ background:#f3f3f6; }
.cd-teacher-photo{
    width:58px; height:58px;
    border-radius:50%;
    overflow:hidden;
    flex-shrink:0;
    background:linear-gradient(135deg,#1A1A1A,#E30613);
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
    font-size:1.4rem;
    border:3px solid #fff;
    box-shadow:0 2px 10px rgba(0,0,0,.15);
}
.cd-teacher-photo img{ width:100%; height:100%; object-fit:cover; display:block; }
.cd-teacher-info strong{
    display:block;
    color:#1A1A1A;
    font-size:1rem;
    line-height:1.3;
}
.cd-teacher-info small{
    display:inline-block;
    margin-top:3px;
    color:#E30613;
    background:rgba(227,6,19,.08);
    font-size:.75rem;
    font-weight:600;
    padding:2px 10px;
    border-radius:50px;
}

@media (max-width:991px){
    .cd-hero{ padding:100px 0 50px; }
    .cd-side{ position:static; }
    .cd-card{ padding:24px 20px; }
}
</style>

<!-- ===== Hero ===== -->
<section class="cd-hero">
<div class="container">
<div class="row align-items-center g-4">
    <div class="col-lg-8" data-aos="fade-up">
        <div class="cd-breadcrumb">
            <a href="index.php">Home</a> &nbsp;/&nbsp; <a href="courses.php">Classes</a> &nbsp;/&nbsp; <?php echo e($course['title']); ?>
        </div>
        <span class="cd-cat-badge" style="background:<?php echo e($course['cat_color'] ?? '#E30613'); ?>"><?php echo e($course['cat_name']); ?></span>
        <h1><?php echo e($course['title']); ?></h1>
        <?php if (!empty($course['title_si'])): ?><div class="cd-sub-si"><?php echo e($course['title_si']); ?></div><?php endif; ?>
        <?php if (!empty($course['short_desc'])): ?><p class="cd-short"><?php echo e($course['short_desc']); ?></p><?php endif; ?>

        <div class="cd-chips">
            <span class="cd-chip"><i class="far fa-clock"></i><?php echo e($course['duration']); ?></span>
            <span class="cd-chip"><i class="fas fa-signal"></i><?php echo e($course['level']); ?></span>
            <span class="cd-chip">
                <?php if ($courseTeachers): ?>
                    <span class="cd-avatars">
                        <?php foreach (array_slice($courseTeachers, 0, 4) as $tt): ?>
                            <?php if (!empty($tt['img'])): ?>
                                <img class="cd-avatar" src="<?php echo e($tt['img']); ?>" alt="<?php echo e($tt['full_name']); ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </span>
                    <?php echo e(implode(', ', $teacherNames)); ?>
                <?php else: ?>
                    <i class="fas fa-user"></i>Sipway Faculty
                <?php endif; ?>
            </span>
        </div>
    </div>

    <div class="col-lg-4" data-aos="fade-left">
        <div class="cd-buy">
            <small>Course Fee</small>
            <div class="cd-price"><?php echo formatPrice($course['price']); ?></div>
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
        </div>
    </div>
</div>
</div>
</section>

<!-- ===== Body ===== -->
<section class="cd-body">
<div class="container">
<div class="row g-4">

    <!-- Main -->
    <div class="col-lg-8">
        <?php if ($courseImg): ?>
        <div class="cd-image" data-aos="fade-up">
            <div class="cd-bg" style="background-image:url('<?php echo e($courseImg); ?>')"></div>
            <img src="<?php echo e($courseImg); ?>" alt="<?php echo e($course['title']); ?>">
        </div>
        <?php endif; ?>

        <div class="cd-card" data-aos="fade-up">
            <div class="cd-card-title">
                <span class="cd-ico"><i class="fas fa-info"></i></span>
                About This Course
            </div>
            <div class="cd-desc">
                <?php if (trim(strip_tags((string)$course['description'])) !== ''): ?>
                    <?php echo $course['description']; ?>
                <?php else: ?>
                    <p class="cd-empty">Course details coming soon.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <div class="cd-card cd-side" data-aos="fade-left">
            <div class="cd-card-title" style="font-size:1.15rem;margin-bottom:6px">
                <span class="cd-ico"><i class="fas fa-list-ul"></i></span>
                Course Details
            </div>

            <ul class="cd-detail-list">
                <li>
                    <span class="cd-k"><i class="far fa-clock"></i> Duration</span>
                    <strong><?php echo e($course['duration']); ?></strong>
                </li>
                <li>
                    <span class="cd-k"><i class="fas fa-signal"></i> Level</span>
                    <strong><?php echo e($course['level']); ?></strong>
                </li>
                <li>
                    <span class="cd-k"><i class="fas fa-language"></i> Language</span>
                    <strong>Sinhala</strong>
                </li>
            </ul>

            <!-- ★ Teachers -->
            <div class="cd-teachers-title"><?php echo count($courseTeachers) > 1 ? 'Teachers' : 'Teacher'; ?></div>
            <?php if ($courseTeachers): ?>
                <?php foreach ($courseTeachers as $tt): ?>
                <div class="cd-teacher">
                    <div class="cd-teacher-photo">
                        <?php if (!empty($tt['img'])): ?>
                            <img src="<?php echo e($tt['img']); ?>" alt="<?php echo e($tt['full_name']); ?>"
                                 onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'fas fa-user-tie\'></i>';">
                        <?php else: ?>
                            <i class="fas fa-user-tie"></i>
                        <?php endif; ?>
                    </div>
                    <div class="cd-teacher-info">
                        <strong><?php echo e($tt['full_name']); ?></strong>
                        <small><?php echo e($tt['matched_subjects']); ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="cd-teacher">
                    <div class="cd-teacher-photo"><i class="fas fa-user-tie"></i></div>
                    <div class="cd-teacher-info"><strong>Sipway Faculty</strong></div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>
</section>
<?php require_once 'includes/footer.php'; ?>