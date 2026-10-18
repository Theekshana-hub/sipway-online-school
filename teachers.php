<?php
$page_title = 'Teachers';
require_once 'includes/header.php';
$pdo = getDB();

$cat_slug = $_GET['cat'] ?? '';
$search   = trim($_GET['q'] ?? '');

// ===== Categories tree =====
$allCats = $pdo->query("SELECT * FROM categories ORDER BY sort_order, name")->fetchAll();
$topCats = [];
$subCats = [];
$activeParentSlug = '';

foreach ($allCats as $ct) {
    if (empty($ct['parent_id'])) {
        $topCats[] = $ct;
    } else {
        $subCats[$ct['parent_id']][] = $ct;
    }
}

// Active sub-category එකක් නම් parent එක open කරන්න
foreach ($topCats as $t) {
    foreach ($subCats[$t['id']] ?? [] as $s) {
        if ($s['slug'] === $cat_slug) {
            $activeParentSlug = $t['slug'];
            break 2;
        }
    }
}

// ===== Teachers filter =====
// Text eka normalize karanawa: trim, lowercase, extra spaces ain karanawa, dash -> space
$norm = function ($s) {
    $s = mb_strtolower(trim((string)$s), 'UTF-8');
    $s = str_replace(['-', '_'], ' ', $s);
    return preg_replace('/\s+/u', ' ', $s);
};

// Selected category (+ parent nam sub categories) walata match keys hadanawa
$matchKeys = [];
if ($cat_slug) {
    foreach ($allCats as $ct) {
        if ($ct['slug'] === $cat_slug) {
            $matchKeys[] = $norm($ct['name']);
            $matchKeys[] = $norm($ct['slug']);

            if (empty($ct['parent_id'])) {
                foreach ($subCats[$ct['id']] ?? [] as $sub) {
                    $matchKeys[] = $norm($sub['name']);
                    $matchKeys[] = $norm($sub['slug']);
                }
            }
            break;
        }
    }
}

// Search eka SQL eken, category eka PHP eken filter karanawa
$sql = "SELECT * FROM teachers WHERE status = 'active'";
$params = [];

if ($search !== '') {
    $sql .= " AND (full_name LIKE ? OR bio LIKE ? OR subject LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY full_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$teachers = $stmt->fetchAll();

if ($cat_slug && $matchKeys) {
    $teachers = array_values(array_filter($teachers, function ($t) use ($norm, $matchKeys) {
        foreach (explode(',', (string)($t['subject'] ?? '')) as $s) {
            if (in_array($norm($s), $matchKeys, true)) {
                return true;
            }
        }
        return false;
    }));
}
?>

<style>
.teacher-card {
    background: #fff;
    border-radius: 18px;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
    box-shadow: 0 6px 24px rgba(0,0,0,.08);
    transition: transform .3s ease, box-shadow .3s ease;
}
.teacher-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 14px 36px rgba(227,6,19,.18);
}

.teacher-photo {
    position: relative;
    width: 100%;
    aspect-ratio: 4 / 5;
    overflow: hidden;
    background: #111;
    display: flex;
    align-items: center;
    justify-content: center;
}
.teacher-photo::before {
    content: '';
    position: absolute;
    inset: -20px;
    background: var(--photo) center/cover no-repeat;
    filter: blur(22px) brightness(.7);
    transform: scale(1.1);
}
/* FIX: img eka absolute karala height 0 wena eka nawathwala */
.teacher-photo img {
    position: absolute;
    inset: 0;
    z-index: 1;
    width: 100%;
    height: 100%;
    object-fit: contain;
    transition: transform .4s ease;
}
.teacher-card:hover .teacher-photo img {
    transform: scale(1.03);
}
.teacher-photo::after {
    content: '';
    position: absolute;
    left: 0; right: 0; bottom: 0;
    height: 4px;
    background: linear-gradient(90deg, #1A1A1A, #E30613);
    z-index: 2;
}

.teacher-photo.no-photo {
    background: linear-gradient(135deg, #1A1A1A, #E30613);
}
.teacher-photo.no-photo::before { display: none; }
.teacher-photo.no-photo i {
    position: relative;
    z-index: 1;
    font-size: 4rem;
    color: #fff;
    opacity: .9;
}

.teacher-body {
    padding: 22px 20px 26px;
    text-align: center;
    flex: 1;
}
.teacher-body h4 {
    color: var(--black, #1A1A1A);
    font-weight: 700;
    margin-bottom: 10px;
}

.teacher-grades {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 6px;
    margin-bottom: 14px;
}
.teacher-grade {
    display: inline-block;
    color: #E30613;
    font-weight: 600;
    font-size: .85rem;
    background: rgba(227,6,19,.08);
    padding: 4px 14px;
    border-radius: 50px;
}

.teacher-bio {
    color: #666;
    font-size: .93rem;
    line-height: 1.6;
    margin: 0;
}
</style>

<section class="page-header">
    <div class="container position-relative">
        <h1 data-aos="fade-up">Our Teachers</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Teachers</li>
            </ol>
        </nav>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="row g-4">

            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="lesson-sidebar" data-aos="fade-right">
                    <h5><i class="fas fa-filter me-2"></i>Categories</h5>
                    <ul class="lesson-list">
                        <li>
                            <a href="teachers.php" class="<?php echo !$cat_slug ? 'active' : ''; ?>">
                                <i class="fas fa-th"></i> All Teachers
                            </a>
                        </li>

                        <?php foreach ($topCats as $cat):
                            $subs   = $subCats[$cat['id']] ?? [];
                            $isOpen = ($cat_slug === $cat['slug'] || $activeParentSlug === $cat['slug']);
                        ?>
                            <li>
                                <a href="teachers.php?cat=<?php echo e($cat['slug']); ?>"
                                   class="<?php echo $cat_slug === $cat['slug'] ? 'active' : ''; ?>">
                                    <i class="<?php echo e($cat['icon']); ?>"></i>
                                    <?php echo e($cat['name']); ?>
                                    <?php if ($subs): ?>
                                        <i class="fas fa-chevron-<?php echo $isOpen ? 'up' : 'down'; ?> float-end small mt-1"></i>
                                    <?php endif; ?>
                                </a>
                            </li>

                            <?php if ($subs && $isOpen): ?>
                                <?php foreach ($subs as $sub): ?>
                                    <li style="padding-left:18px">
                                        <a href="teachers.php?cat=<?php echo e($sub['slug']); ?>"
                                           class="<?php echo $cat_slug === $sub['slug'] ? 'active' : ''; ?>"
                                           style="font-size:.92em">
                                            <i class="fas fa-angle-right"></i> <?php echo e($sub['name']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>

                    <h5 class="mt-3"><i class="fas fa-search me-2"></i>Search</h5>
                    <form method="GET" class="mt-2">
                        <?php if ($cat_slug): ?>
                            <input type="hidden" name="cat" value="<?php echo e($cat_slug); ?>">
                        <?php endif; ?>
                        <div class="input-group">
                            <input type="text" name="q" class="form-control form-control-sm"
                                   placeholder="Search..."
                                   value="<?php echo e($search); ?>">
                            <button class="btn btn-primary btn-sm" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Teachers Grid -->
            <div class="col-lg-9">
                <p class="text-muted mb-3" data-aos="fade-up">
                    <?php echo count($teachers); ?> teacher(s) found
                </p>

                <?php if (empty($teachers)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-user-tie fa-3x text-muted mb-3"></i>
                        <h4>No teachers found</h4>
                        <a href="teachers.php" class="btn btn-primary">View All Teachers</a>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($teachers as $i => $t): ?>
                            <?php
                                $tImg     = imageUrl('teachers', $t['photo'] ?? null);
                                $subjects = array_filter(array_map('trim', explode(',', (string)($t['subject'] ?? ''))));
                            ?>
                            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo ($i % 3) * 80; ?>">
                                <div class="teacher-card">

                                    <?php if ($tImg): ?>
                                        <div class="teacher-photo" style="--photo:url('<?php echo e($tImg); ?>')">
                                            <img src="<?php echo e($tImg); ?>"
                                                 alt="<?php echo e($t['full_name']); ?>"
                                                 loading="lazy"
                                                 onerror="this.style.display='none'; this.parentElement.classList.add('no-photo'); this.parentElement.innerHTML='<i class=\'fas fa-user-tie\'></i>';">
                                        </div>
                                    <?php else: ?>
                                        <div class="teacher-photo no-photo">
                                            <i class="fas fa-user-tie"></i>
                                        </div>
                                    <?php endif; ?>

                                    <div class="teacher-body">
                                        <h4><?php echo e($t['full_name']); ?></h4>

                                        <?php if ($subjects): ?>
                                            <div class="teacher-grades">
                                                <?php foreach ($subjects as $sub): ?>
                                                    <span class="teacher-grade"><?php echo e($sub); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <p class="teacher-bio"><?php echo e($t['bio']); ?></p>
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