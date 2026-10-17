<?php
$page_title = 'Teachers';
require_once 'includes/header.php';
$pdo = getDB();
$teachers = $pdo->query("SELECT * FROM teachers WHERE status='active' ORDER BY full_name")->fetchAll();

// Teachers ge subjects walin dropdown eka hadanawa (duplicate nathuwa)
$allSubjects = [];
foreach ($teachers as $k => $t) {
    $subs = array_filter(array_map('trim', explode(',', (string)($t['subject'] ?? ''))));
    $keys = [];
    foreach ($subs as $s) {
        $key = mb_strtolower($s);
        $allSubjects[$key] = $s;
        $keys[] = $key;
    }
    $teachers[$k]['_subs'] = $subs;
    $teachers[$k]['_keys'] = $keys;
}
asort($allSubjects, SORT_NATURAL | SORT_FLAG_CASE);
?>

<style>
/* ===== Filter bar ===== */
.teacher-filter {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: #fff;
    border-radius: 14px;
    padding: 14px 18px;
    margin-bottom: 28px;
    box-shadow: 0 4px 16px rgba(0,0,0,.06);
}
.teacher-filter label {
    font-weight: 600;
    color: #1A1A1A;
    margin: 0 10px 0 0;
}
.teacher-filter .filter-left {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
}
.teacher-filter select {
    min-width: 240px;
    border: 2px solid #eee;
    border-radius: 50px;
    padding: 8px 18px;
    font-size: .95rem;
    outline: none;
    background: #fff;
    cursor: pointer;
    transition: border-color .2s;
}
.teacher-filter select:focus,
.teacher-filter select:hover { border-color: #E30613; }
.teacher-count { color: #666; font-size: .92rem; }

/* ===== Teacher card ===== */
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

/* Photo: full width, full image visible (no crop), podi height ekak */
.teacher-photo {
    position: relative;
    width: 100%;
    height: 240px;              /* ← size eka wenas karanna ona nam meka wenas karanna */
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
    filter: blur(20px) brightness(.7);
    transform: scale(1.1);
}
.teacher-photo img {
    position: relative;
    z-index: 1;
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    object-fit: contain;        /* full image eka penenawa */
    transition: transform .4s ease;
}
.teacher-card:hover .teacher-photo img { transform: scale(1.03); }
.teacher-photo::after {
    content: '';
    position: absolute;
    left: 0; right: 0; bottom: 0;
    height: 4px;
    background: linear-gradient(90deg, #1A1A1A, #E30613);
    z-index: 2;
}

/* Photo nathi unama */
.teacher-photo.no-photo { background: linear-gradient(135deg, #1A1A1A, #E30613); }
.teacher-photo.no-photo::before { display: none; }
.teacher-photo.no-photo i {
    position: relative;
    z-index: 1;
    font-size: 3.5rem;
    color: #fff;
    opacity: .9;
}

.teacher-body {
    padding: 18px 16px 24px;
    text-align: center;
    flex: 1;
}
.teacher-body h4 {
    color: var(--black, #1A1A1A);
    font-weight: 700;
    font-size: 1.1rem;
    margin-bottom: 10px;
}

.teacher-subjects {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 6px;
    margin-bottom: 12px;
}
.teacher-subject {
    display: inline-block;
    color: #E30613;
    font-weight: 600;
    font-size: .8rem;
    background: rgba(227,6,19,.08);
    padding: 3px 12px;
    border-radius: 50px;
}
.teacher-bio {
    color: #666;
    font-size: .88rem;
    line-height: 1.55;
    margin: 0;
}

.teacher-item.hide { display: none; }

@media (max-width: 575px) {
    .teacher-filter select { min-width: 100%; }
    .teacher-filter .filter-left { width: 100%; }
    .teacher-photo { height: 190px; }
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

        <!-- Dropdown filter -->
        <?php if (!empty($teachers)): ?>
        <div class="teacher-filter" data-aos="fade-up">
            <div class="filter-left">
                <label for="subjectFilter"><i class="fas fa-filter me-2"></i>Filter by Class</label>
                <select id="subjectFilter">
                    <option value="">All Classes</option>
                    <?php foreach ($allSubjects as $key => $name): ?>
                        <option value="<?php echo e($key); ?>"><?php echo e($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="teacher-count"><span id="teacherCount"><?php echo count($teachers); ?></span> teacher(s) found</div>
        </div>
        <?php endif; ?>

        <div class="row g-4" id="teacherGrid">
            <?php foreach ($teachers as $i => $t): ?>
                <?php
                    $tImg = imageUrl('teachers', $t['photo'] ?? null);
                    $subjects = $t['_subs'];
                    $dataSubs = '|' . implode('|', $t['_keys']) . '|';
                ?>
                <div class="col-xl-3 col-lg-4 col-md-6 col-6 teacher-item"
                     data-subjects="<?php echo e($dataSubs); ?>"
                     data-aos="fade-up" data-aos-delay="<?php echo ($i % 4) * 80; ?>">
                    <div class="teacher-card">

                        <?php if ($tImg): ?>
                            <div class="teacher-photo" style="--photo:url('<?php echo e($tImg); ?>')">
                                <img src="<?php echo e($tImg); ?>"
                                     alt="<?php echo e($t['full_name']); ?>"
                                     loading="lazy">
                            </div>
                        <?php else: ?>
                            <div class="teacher-photo no-photo">
                                <i class="fas fa-user-tie"></i>
                            </div>
                        <?php endif; ?>

                        <div class="teacher-body">
                            <h4><?php echo e($t['full_name']); ?></h4>

                            <?php if ($subjects): ?>
                                <div class="teacher-subjects">
                                    <?php foreach ($subjects as $sub): ?>
                                        <span class="teacher-subject"><?php echo e($sub); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <p class="teacher-bio"><?php echo e($t['bio']); ?></p>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($teachers)): ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted">Teachers will be listed soon.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Filter karama kenek hari nathnam -->
        <div id="noTeachers" class="text-center py-5" style="display:none">
            <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
            <h5>No teachers found for this class</h5>
            <button type="button" class="btn btn-primary mt-2" id="resetFilter">Show All Teachers</button>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var select  = document.getElementById('subjectFilter');
    if (!select) return;
    var items   = document.querySelectorAll('.teacher-item');
    var counter = document.getElementById('teacherCount');
    var empty   = document.getElementById('noTeachers');
    var reset   = document.getElementById('resetFilter');

    function applyFilter() {
        var val = select.value;
        var shown = 0;
        items.forEach(function (el) {
            var match = !val || el.dataset.subjects.indexOf('|' + val + '|') !== -1;
            el.classList.toggle('hide', !match);
            if (match) shown++;
        });
        counter.textContent = shown;
        empty.style.display = shown === 0 ? 'block' : 'none';
    }

    select.addEventListener('change', applyFilter);
    reset.addEventListener('click', function () {
        select.value = '';
        applyFilter();
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>