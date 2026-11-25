<?php
$page_title = 'Home';
require_once 'includes/header.php';
$pdo = getDB();

/* ---------- Featured courses (with optional teacher) ---------- */
function hasColumn(PDO $pdo, string $table, string $column): bool {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $pdo->quote($column));
        return (bool)$stmt->fetch();
    } catch (Exception $e) {
        return false;
    }
}

$teacherColumn = null;
$possibleColumns = ['teacher_id', 'instructor_id', 'lecturer_id', 'teacher', 'user_id'];
foreach ($possibleColumns as $col) {
    if (hasColumn($pdo, 'courses', $col)) {
        $teacherColumn = $col;
        break;
    }
}

$sql = "SELECT c.*, 
               cat.name AS cat_name, cat.icon AS cat_icon, cat.color AS cat_color";
if ($teacherColumn) {
    $sql .= ", t.full_name AS teacher_name, t.photo AS teacher_photo";
} else {
    $sql .= ", NULL AS teacher_name, NULL AS teacher_photo";
}
$sql .= " FROM courses c
          LEFT JOIN categories cat ON c.category_id = cat.id";
if ($teacherColumn) {
    $sql .= " LEFT JOIN teachers t ON c.`$teacherColumn` = t.id";
}
$sql .= " WHERE c.is_featured = 1 AND c.is_published = 1
          ORDER BY c.id
          LIMIT 6";

$featured = $pdo->query($sql)->fetchAll();

/* ---------- Schedules for featured courses ---------- */
$schedulesByCourse = [];
if (!empty($featured)) {
    try {
        $ids = array_map(fn($r) => (int)$r['id'], $featured);
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $ss  = $pdo->prepare("SELECT * FROM course_year_schedules WHERE course_id IN ($ph) ORDER BY sort_order, id");
        $ss->execute($ids);
        foreach ($ss->fetchAll() as $row) {
            $schedulesByCourse[(int)$row['course_id']][] = $row;
        }
    } catch (Exception $ex) {
        $schedulesByCourse = [];
    }
}

function clTime($t) {
    return $t ? date('g:i A', strtotime($t)) : '';
}

/* ---------- Other home data ---------- */
$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order, name")->fetchAll();
$total_courses = $pdo->query("SELECT COUNT(*) FROM courses WHERE is_published=1")->fetchColumn();
$total_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();

/* ---------- Hero background slideshow ---------- */
$heroSlides = [];
$heroDir = __DIR__ . '/assets/images/hero/';
if (is_dir($heroDir)) {
    $heroFiles = glob($heroDir . '*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE);
    if ($heroFiles) {
        natsort($heroFiles);
        foreach ($heroFiles as $hf) {
            $heroSlides[] = 'assets/images/hero/' . rawurlencode(basename($hf));
        }
    }
}
?>

<section class="hero">

    <?php if (!empty($heroSlides)): ?>
    <div class="hero-bg-slider" id="heroBgSlider">
        <?php foreach ($heroSlides as $i => $src): ?>
            <div class="hero-bg-slide<?php echo $i === 0 ? ' active' : ''; ?>" style="background-image: url('<?php echo e($src); ?>');"></div>
        <?php endforeach; ?>
    </div>
    <div class="hero-bg-overlay"></div>
    <?php if (count($heroSlides) > 1): ?>
    <div class="hero-bg-dots" id="heroBgDots">
        <?php foreach ($heroSlides as $i => $src): ?>
            <button type="button" aria-label="Slide <?php echo $i + 1; ?>" class="<?php echo $i === 0 ? 'active' : ''; ?>"></button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7 hero-content">
                <div class="hero-badge">
                    <i class="fas fa-star"></i> Sipway Campus Official Online Platform
                </div>
                <h1>Learn Differently.<br><span>Succeed Differently.</span></h1>
                <p>Empowering Students Through Quality Education, Expert Guidance, and Innovative Learning Experiences. Discover Your Potential and Build a Brighter Future with Sipway Online School, Kurunegala.</p>
                <div class="hero-buttons">
                    <a href="courses.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-book-open me-2"></i>Explore Courses
                    </a>
                    <a href="register.php" class="btn btn-outline-light btn-lg">Get Started Free</a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <h3 class="counter" data-target="<?php echo max(15, (int)$total_courses); ?>">0+</h3>
                        <p>Courses</p>
                    </div>
                    <div class="stat-item">
                        <h3 class="counter" data-target="<?php echo max(500, (int)$total_students); ?>">0+</h3>
                        <p>Students</p>
                    </div>
                    <div class="stat-item">
                        <h3 class="counter" data-target="12">0+</h3>
                        <p>Years</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block text-center" data-aos="fade-left" data-aos-duration="1000">
                <div class="hero-visual-card">
                    <div class="floating-icon fi-1"><i class="fas fa-laptop-code"></i></div>
                    <div class="floating-icon fi-2"><i class="fas fa-flask"></i></div>
                    <div class="floating-icon fi-3"><i class="fas fa-language"></i></div>
                    <div class="hero-center-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Start Learning Today</h3>
                    <p>Interactive lessons • Expert teachers • Certificates</p>
                </div>
            </div>
        </div>
    </div>

    <div class="hero-wave">
        <svg viewBox="0 0 1440 80" preserveAspectRatio="none"><path fill="#f8f9fa" d="M0,40 C360,80 720,0 1440,40 L1440,80 L0,80 Z"></path></svg>
    </div>
</section>

<?php if (!empty($heroSlides) && count($heroSlides) > 1): ?>
<script>
(function () {
    var slides = document.querySelectorAll('#heroBgSlider .hero-bg-slide');
    var dots   = document.querySelectorAll('#heroBgDots button');
    var current = 0, timer = null, DELAY = 5500;

    slides.forEach(function (s) {
        var m = s.style.backgroundImage.match(/url\(["']?(.*?)["']?\)/);
        if (m) { var img = new Image(); img.src = m[1]; }
    });

    function show(n) {
        slides[current].classList.remove('active');
        if (dots[current]) dots[current].classList.remove('active');
        current = (n + slides.length) % slides.length;
        void slides[current].offsetWidth;
        slides[current].classList.add('active');
        if (dots[current]) dots[current].classList.add('active');
    }
    function start() { stop(); timer = setInterval(function () { show(current + 1); }, DELAY); }
    function stop()  { if (timer) clearInterval(timer); }

    dots.forEach(function (d, i) {
        d.addEventListener('click', function () { show(i); start(); });
    });

    document.addEventListener('visibilitychange', function () {
        document.hidden ? stop() : start();
    });

    start();
})();
</script>
<?php endif; ?>

<section class="section bg-light">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="section-badge">CATEGORIES</span>
            <h2>Our Course Categories</h2>
            <p>Choose from A/L streams, languages, grades and more</p>
        </div>
        <div class="row g-3">
            <?php foreach ($categories as $i => $cat): ?>
            <div class="col-lg-3 col-md-4 col-6" data-aos="zoom-in" data-aos-delay="<?php echo $i * 70; ?>">
                <a href="courses.php?cat=<?php echo e($cat['slug']); ?>" class="text-decoration-none">
                    <div class="category-card">
                        <?php $catImg = imageUrl('categories', $cat['image'] ?? null); ?>
                        <?php if ($catImg): ?>
                        <div class="category-icon" style="background:url(<?php echo e($catImg); ?>) center/cover; border:none;"></div>
                        <?php else: ?>
                        <div class="category-icon" style="background: <?php echo e($cat['color']); ?>">
                            <i class="<?php echo e($cat['icon']); ?>"></i>
                        </div>
                        <?php endif; ?>
                        <h4><?php echo e($cat['name']); ?></h4>
                        <p><?php echo e($cat['name_si'] ?? ''); ?></p>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<section class="section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="section-badge">FEATURED</span>
            <h2>Popular Courses</h2>
            <p>Most enrolled programs at Sipway Online School</p>
        </div>
        <div class="row g-4">
            <?php if (empty($featured)): ?>
            <div class="col-12 text-center text-muted py-4">Featured courses will appear here. Add courses from Admin Panel.</div>
            <?php else: foreach ($featured as $i => $c):
                $cImg         = imageUrl('courses', $c['thumbnail'] ?? null);
                $teacherImg   = !empty($c['teacher_photo']) ? imageUrl('teachers', $c['teacher_photo']) : null;
                $teacherName  = $c['teacher_name'] ?? '';
                $classSchedules = $schedulesByCourse[(int)$c['id']] ?? [];
                $maxVisible   = 2; // home page – show only 2 slots
                $visibleSchedules = array_slice($classSchedules, 0, $maxVisible);
                $extraSchedules   = array_slice($classSchedules, $maxVisible);
                $extraCount       = count($extraSchedules);
                $cardId           = 'home-sched-' . (int)$c['id'];
            ?>
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $i * 100; ?>">
                <div class="course-card">
                    <?php if ($cImg): ?>
                        <div class="course-thumb" style="background:url(<?php echo e($cImg); ?>) center/cover;">
                            <?php if ($c['is_featured']): ?><span class="course-badge">Featured</span><?php endif; ?>
                            <span class="course-level"><?php echo e($c['level']); ?></span>
                        </div>
                    <?php else: ?>
                        <div class="course-thumb" style="background:linear-gradient(135deg,<?php echo e($c['cat_color']??'#1A1A1A'); ?>,#E30613)">
                            <i class="<?php echo e($c['cat_icon'] ?? 'fas fa-book'); ?>"></i>
                            <?php if ($c['is_featured']): ?><span class="course-badge">Featured</span><?php endif; ?>
                            <span class="course-level"><?php echo e($c['level']); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="course-body">
                        <div class="course-category"><?php echo e($c['cat_name'] ?? ''); ?></div>
                        
                        <h3 class="course-title">
                            <a href="course-details.php?slug=<?php echo e($c['slug']); ?>" style="color: inherit">
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
                                <span class="course-teacher-fallback"><i class="fas fa-user-tie"></i></span>
                            <?php endif; ?>
                            <span class="course-teacher-name"><?php echo e($teacherName); ?></span>
                        </div>
                        <?php endif; ?>

                        <p class="course-desc"><?php echo e($c['short_desc']); ?></p>

                        <!-- Schedules (time / day / year / fee) -->
                        <?php if (!empty($classSchedules)): ?>
                        <div class="cl-schedule">
                            <?php foreach ($visibleSchedules as $sc):
                                $t = clTime($sc['start_time']);
                                if ($t && !empty($sc['end_time'])) $t .= ' – ' . clTime($sc['end_time']);
                                $slotFee = ($sc['fee'] !== null && $sc['fee'] !== '') ? (float)$sc['fee'] : null;
                            ?>
                                <div class="cl-slot">
                                    <?php if (!empty($sc['class_year'])): ?>
                                        <span class="cl-chip year"><i class="fas fa-graduation-cap"></i> <?php echo e($sc['class_year']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($sc['class_day'])): ?>
                                        <span class="cl-chip"><i class="far fa-calendar"></i> <?php echo e($sc['class_day']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($t): ?>
                                        <span class="cl-chip"><i class="far fa-clock"></i> <?php echo e($t); ?></span>
                                    <?php endif; ?>
                                    <?php if ($slotFee !== null): ?>
                                        <span class="cl-chip fee"><i class="fas fa-tag"></i> <?php echo $slotFee > 0 ? 'Rs. ' . number_format($slotFee) : 'Free'; ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>

                            <?php if ($extraCount > 0): ?>
                                <div class="cl-extra-slots" id="<?php echo $cardId; ?>">
                                    <?php foreach ($extraSchedules as $sc):
                                        $t = clTime($sc['start_time']);
                                        if ($t && !empty($sc['end_time'])) $t .= ' – ' . clTime($sc['end_time']);
                                        $slotFee = ($sc['fee'] !== null && $sc['fee'] !== '') ? (float)$sc['fee'] : null;
                                    ?>
                                        <div class="cl-slot">
                                            <?php if (!empty($sc['class_year'])): ?>
                                                <span class="cl-chip year"><i class="fas fa-graduation-cap"></i> <?php echo e($sc['class_year']); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($sc['class_day'])): ?>
                                                <span class="cl-chip"><i class="far fa-calendar"></i> <?php echo e($sc['class_day']); ?></span>
                                            <?php endif; ?>
                                            <?php if ($t): ?>
                                                <span class="cl-chip"><i class="far fa-clock"></i> <?php echo e($t); ?></span>
                                            <?php endif; ?>
                                            <?php if ($slotFee !== null): ?>
                                                <span class="cl-chip fee"><i class="fas fa-tag"></i> <?php echo $slotFee > 0 ? 'Rs. ' . number_format($slotFee) : 'Free'; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="cl-more-btn"
                                        aria-expanded="false"
                                        aria-controls="<?php echo $cardId; ?>"
                                        data-more="+<?php echo $extraCount; ?> more"
                                        data-less="Show less">
                                    <span class="cl-more-text">+<?php echo $extraCount; ?> more</span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Duration + Price removed as requested -->
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
        <div class="text-center mt-5" data-aos="fade-up">
            <a href="courses.php" class="btn btn-primary btn-lg">
                View All Courses <i class="fas fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>


<section class="section bg-light">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="section-badge">WHY US</span>
            <h2>Why Sipway Online?</h2>
            <p>Everything you need for successful online learning</p>
        </div>
        <div class="row g-4">
            <?php
            $features = [
                ['fas fa-chalkboard-teacher', 'Expert Teachers', 'Learn from experienced A/L and professional teachers'],
                ['fas fa-laptop', 'Flexible Online', 'Study anytime, anywhere at your own pace'],
                ['fas fa-certificate', 'Certificates', 'Recognized certificates on course completion'],
                ['fas fa-headset', 'Student Support', 'Dedicated support for all your queries'],
            ];
            foreach ($features as $i => $f): ?>
            <div class="col-lg-3 col-md-6" data-aos="flip-up" data-aos-delay="<?php echo $i * 100; ?>">
                <div class="feature-card">
                    <div class="feature-icon"><i class="<?php echo $f[0]; ?>"></i></div>
                    <h4><?php echo $f[1]; ?></h4>
                    <p><?php echo $f[2]; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<section class="section">
    <div class="container">
        <div class="section-header" data-aos="fade-up">
            <span class="section-badge">SUBJECTS</span>
            <h2>What We Teach</h2>
            <p>Complete coverage for A/L, Technology, Commerce & more</p>
        </div>
        <div class="row g-3">
            <?php
            $subjects = [
                ['Sinhala', 'සිංහල', 'fas fa-book', '#E30613'],
                ['Biology', 'ජීව විද්‍යාව', 'fas fa-dna', '#0ea5e9'],
                ['Media', 'මාධ්‍ය', 'fas fa-tv', '#8b5cf6'],
                ['SFT', 'තාක්ෂණ විද්‍යා', 'fas fa-atom', '#F5A623'],
                ['ET', 'ඉංජිනේරු තාක්ෂණ', 'fas fa-cogs', '#ef4444'],
                ['BST', 'ජෛව පද්ධති', 'fas fa-leaf', '#10b981'],
                ['ICT', 'ICT', 'fas fa-laptop-code', '#06b6d4'],
                ['Agriculture', 'කෘෂිකර්මය', 'fas fa-seedling', '#84cc16'],
                ['Accounting', 'ගිණුම්කරණය', 'fas fa-calculator', '#f59e0b'],
                ['Economics', 'ආර්ථික විද්‍යා', 'fas fa-chart-line', '#6366f1'],
                ['Business', 'ව්‍යාපාර', 'fas fa-briefcase', '#ec4899'],
                ['Korean', 'කොරියානු', 'fas fa-language', '#F5A623'],
                ['Grade 6-7', '6-7 ශ්‍රේණි', 'fas fa-child', '#14b8a6'],
                ['Astrology', 'ජ්‍යොතිෂය', 'fas fa-star', '#a855f7'],
            ];
            foreach ($subjects as $i => $s): ?>
            <div class="col-lg-2 col-md-3 col-4" data-aos="fade-up" data-aos-delay="<?php echo ($i % 7) * 50; ?>">
                <div class="subject-chip">
                    <div class="subject-icon" style="background: <?php echo $s[3]; ?>20; color: <?php echo $s[3]; ?>">
                        <i class="<?php echo $s[2]; ?>"></i>
                    </div>
                    <strong><?php echo $s[0]; ?></strong>
                    <small><?php echo $s[1]; ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<section class="section cta-section">
    <div class="container position-relative">
        <div class="row align-items-center">
            <div class="col-lg-8" data-aos="fade-right">
                <h2 class="text-white mb-2" style="font-size: 2rem">Ready to Start Learning?</h2>
                <p class="mb-0" style="opacity: 0.85">Join thousands of students building successful careers with Sipway Online School.</p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0" data-aos="fade-left">
                <a href="register.php" class="btn btn-accent btn-lg me-2">Register Now</a>
                <a href="contact.php" class="btn btn-outline-light btn-lg">Contact Us</a>
            </div>
        </div>
    </div>
</section>

<style>
/* ===== Hero visual card ===== */
.hero-visual-card {
    background: rgba(255,255,255,0.07);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 24px;
    padding: 3rem 2rem;
    position: relative;
    overflow: hidden;
}
.hero-center-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #E30613, #F5A623);
    border-radius: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.2rem;
    font-size: 2.8rem;
    color: #fff;
    animation: pulse-glow 3s ease-in-out infinite;
    box-shadow: 0 10px 40px rgba(227,6,19,0.4);
}
@keyframes pulse-glow {
    0%, 100% { box-shadow: 0 10px 40px rgba(227,6,19,0.3); transform: scale(1); }
    50% { box-shadow: 0 15px 50px rgba(227,6,19,0.5); transform: scale(1.05); }
}
.hero-visual-card h3 { color: #fff; margin-bottom: 0.4rem; }
.hero-visual-card p { opacity: 0.75; margin: 0; font-size: 0.95rem; }
.floating-icon {
    position: absolute;
    width: 48px;
    height: 48px;
    background: rgba(255,255,255,0.12);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.2rem;
    animation: floatIcon 4s ease-in-out infinite;
}
.fi-1 { top: 15%; left: 10%; animation-delay: 0s; }
.fi-2 { top: 20%; right: 12%; animation-delay: 1s; }
.fi-3 { bottom: 18%; left: 15%; animation-delay: 2s; }
@keyframes floatIcon {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-12px); }
}
.hero-wave {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    line-height: 0;
    z-index: 3;
}
.hero-wave svg { width: 100%; height: 50px; display: block; }
.hero { padding-bottom: 50px; }

/* ===== Hero background slideshow ===== */
.hero {
    position: relative;
    overflow: hidden;
}
.hero > .container {
    position: relative;
    z-index: 3;
}
.hero-bg-slider {
    position: absolute;
    inset: 0;
    z-index: 0;
}
.hero-bg-slide {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    opacity: 0;
    transform: scale(1);
    transition: opacity 1.6s ease-in-out;
    will-change: opacity, transform;
}
.hero-bg-slide.active {
    opacity: 1;
    animation: heroKenBurns 9s ease-out forwards;
}
@keyframes heroKenBurns {
    from { transform: scale(1); }
    to   { transform: scale(1.12); }
}
.hero-bg-overlay {
    position: absolute;
    inset: 0;
    z-index: 1;
    background:
        linear-gradient(90deg, rgba(8,8,8,0.88) 0%, rgba(8,8,8,0.68) 50%, rgba(8,8,8,0.50) 100%),
        linear-gradient(180deg, rgba(227,6,19,0.10) 0%, rgba(0,0,0,0.35) 100%);
}
.hero-bg-dots {
    position: absolute;
    left: 50%;
    bottom: 62px;
    transform: translateX(-50%);
    z-index: 4;
    display: flex;
    gap: 8px;
}
.hero-bg-dots button {
    width: 9px;
    height: 9px;
    border-radius: 50%;
    border: 0;
    padding: 0;
    background: rgba(255,255,255,0.35);
    cursor: pointer;
    transition: all 0.3s ease;
}
.hero-bg-dots button.active {
    width: 26px;
    border-radius: 10px;
    background: #E30613;
}
@media (prefers-reduced-motion: reduce) {
    .hero-bg-slide.active { animation: none; }
    .hero-bg-slide { transition: none; }
}

/* ===== Subject chips ===== */
.subject-chip {
    background: #fff;
    border-radius: 14px;
    padding: 1.2rem 0.8rem;
    text-align: center;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    border: 1px solid #eee;
    transition: all 0.3s;
    height: 100%;
    cursor: default;
}
.subject-chip:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    border-color: rgba(227,6,19,0.2);
}
.subject-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.7rem;
    font-size: 1.2rem;
}
.subject-chip strong {
    display: block;
    font-size: 0.9rem;
    color: #1A1A1A;
    margin-bottom: 0.15rem;
}
.subject-chip small {
    font-size: 0.75rem;
    color: #6c757d;
}

/* ===== Teacher + Schedule (same style as courses.php) ===== */
.course-teacher {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 8px 0 10px;
    padding: 8px 10px;
    background: #f8f9fa;
    border-radius: 10px;
}
.course-teacher img {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e5e7eb;
    flex-shrink: 0;
}
.course-teacher-fallback {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #fee2e2;
    color: #E30613;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    flex-shrink: 0;
}
.course-teacher-name {
    font-size: 13px;
    font-weight: 600;
    color: #222;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.cl-schedule { display: flex; flex-direction: column; gap: 6px; margin: 8px 0 6px; }
.cl-slot {
    display: flex; flex-wrap: wrap; align-items: center; gap: 5px;
    background: #f6f7f9; border: 1px solid #e9eaee; border-radius: 10px;
    padding: 6px 8px;
}
.cl-chip {
    display: inline-flex; align-items: center; gap: 4px;
    background: #fff; color: #16181d; border-radius: 7px;
    padding: 3px 7px; font-size: 11.5px; font-weight: 600;
    border: 1px solid #e9eaee; line-height: 1.3;
}
.cl-chip i { color: #E30613; font-size: 10px; }
.cl-chip.year { background: #E30613; color: #fff; border-color: #E30613; }
.cl-chip.year i { color: #fff; }
.cl-chip.fee { background: #ecfdf3; color: #15803d; border-color: #bbf7d0; margin-left: auto; font-weight: 700; }
.cl-chip.fee i { color: #15803d; }

.cl-extra-slots { display: none; flex-direction: column; gap: 6px; }
.cl-extra-slots.open { display: flex; animation: clDrop .22s ease; }
@keyframes clDrop { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }

.cl-more-btn {
    display: inline-flex; align-items: center; gap: 6px;
    margin-top: 4px; padding: 4px 2px;
    background: none; border: none; color: #E30613;
    font-size: 12.5px; font-weight: 600; cursor: pointer;
    font-family: inherit;
}
.cl-more-btn:hover { text-decoration: underline; }
.cl-more-btn i { font-size: 11px; transition: transform .2s; }
.cl-more-btn[aria-expanded="true"] i { transform: rotate(180deg); }
</style>

<script>
(function () {
    document.querySelectorAll('.cl-more-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var targetId = btn.getAttribute('aria-controls');
            var box = document.getElementById(targetId);
            if (!box) return;

            var isOpen = btn.getAttribute('aria-expanded') === 'true';
            var textEl = btn.querySelector('.cl-more-text');

            if (isOpen) {
                box.classList.remove('open');
                btn.setAttribute('aria-expanded', 'false');
                if (textEl) textEl.textContent = btn.getAttribute('data-more');
            } else {
                box.classList.add('open');
                btn.setAttribute('aria-expanded', 'true');
                if (textEl) textEl.textContent = btn.getAttribute('data-less');
            }
        });
    });
})();
</script>

<?php require_once 'includes/footer.php'; ?>