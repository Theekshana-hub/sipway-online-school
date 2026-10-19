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

foreach ($allCats as $ct) {
    if (empty($ct['parent_id'])) {
        $topCats[] = $ct;
    } else {
        $subCats[$ct['parent_id']][] = $ct;
    }
}

// ===== Selected category (+ parent nam sub categories ekathu karanawa) =====
$filterIds = [];
foreach ($allCats as $ct) {
    if ($cat_slug !== '' && $ct['slug'] === $cat_slug) {
        $filterIds[] = (int)$ct['id'];
        if (empty($ct['parent_id'])) {
            foreach ($subCats[$ct['id']] ?? [] as $sub) {
                $filterIds[] = (int)$sub['id'];
            }
        }
        break;
    }
}

// ===== Teachers query =====
$sql = "SELECT t.*, c.name AS category_name
        FROM teachers t
        LEFT JOIN categories c ON c.id = t.category_id
        WHERE t.status = 'active'";
$params = [];

if ($filterIds) {
    $placeholders = implode(',', array_fill(0, count($filterIds), '?'));
    $sql .= " AND t.category_id IN ($placeholders)";
    $params = array_merge($params, $filterIds);
}

if ($search !== '') {
    $sql .= " AND (t.full_name LIKE ? OR t.bio LIKE ? OR t.subject LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY t.full_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$teachers = $stmt->fetchAll();
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

.teacher-cat-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    z-index: 3;
    background: rgba(26,26,26,.85);
    color: #fff;
    font-size: .75rem;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 50px;
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
                    <h5><i class="fas fa-filter me-2"></i>Filter</h5>

                    <form method="GET" id="filterForm" class="mt-2">
                        <label class="form-label small mb-1">Category</label>
                        <select name="cat" class="form-select form-select-sm mb-3"
                                onchange="document.getElementById('filterForm').submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($topCats as $cat):
                                $subs = $subCats[$cat['id']] ?? [];
                            ?>
                                <?php if ($subs): ?>
                                    <optgroup label="<?php echo e($cat['name']); ?>">
                                        <option value="<?php echo e($cat['slug']); ?>"
                                            <?php echo $cat_slug === $cat['slug'] ? 'selected' : ''; ?>>
                                            All <?php echo e($cat['name']); ?>
                                        </option>
                                        <?php foreach ($subs as $sub): ?>
                                            <option value="<?php echo e($sub['slug']); ?>"
                                                <?php echo $cat_slug === $sub['slug'] ? 'selected' : ''; ?>>
                                                <?php echo e($sub['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php else: ?>
                                    <option value="<?php echo e($cat['slug']); ?>"
                                        <?php echo $cat_slug === $cat['slug'] ? 'selected' : ''; ?>>
                                        <?php echo e($cat['name']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>

                        <label class="form-label small mb-1">Search</label>
                        <div class="input-group">
                            <input type="text" name="q" class="form-control form-control-sm"
                                   placeholder="Search..."
                                   value="<?php echo e($search); ?>">
                            <button class="btn btn-primary btn-sm" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>

                        <?php if ($cat_slug || $search !== ''): ?>
                            <a href="teachers.php" class="btn btn-outline-secondary btn-sm w-100 mt-3">
                                <i class="fas fa-times me-1"></i> Clear Filters
                            </a>
                        <?php endif; ?>
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
                                            <?php if (!empty($t['category_name'])): ?>
                                                <span class="teacher-cat-badge"><?php echo e($t['category_name']); ?></span>
                                            <?php endif; ?>
                                            <img src="<?php echo e($tImg); ?>"
                                                 alt="<?php echo e($t['full_name']); ?>"
                                                 loading="lazy"
                                                 onerror="this.style.display='none'; this.parentElement.classList.add('no-photo'); this.parentElement.innerHTML='<i class=\'fas fa-user-tie\'></i>';">
                                        </div>
                                    <?php else: ?>
                                        <div class="teacher-photo no-photo">
                                            <?php if (!empty($t['category_name'])): ?>
                                                <span class="teacher-cat-badge"><?php echo e($t['category_name']); ?></span>
                                            <?php endif; ?>
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