<?php
$page_title = 'Home';
require_once 'includes/header.php';
$pdo = getDB();
$featured = $pdo->query("SELECT c.*, cat.name as cat_name, cat.icon as cat_icon, cat.color as cat_color FROM courses c LEFT JOIN categories cat ON c.category_id=cat.id WHERE c.is_featured=1 AND c.is_published=1 ORDER BY c.id LIMIT 6")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order, name")->fetchAll();
$total_courses = $pdo->query("SELECT COUNT(*) FROM courses WHERE is_published=1")->fetchColumn();
$total_students = $pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
?>


<section class="hero">
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
            <?php else: foreach ($featured as $i => $c): ?>
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $i * 100; ?>">
                <div class="course-card">
                    <?php $cImg = imageUrl('courses', $c['thumbnail'] ?? null); ?>
                    <div class="course-thumb" style="<?php echo $cImg ? 'background:url('.e($cImg).') center/cover;' : 'background:linear-gradient(135deg,'.e($c['cat_color']??'#1A1A1A').',#E30613)'; ?>">
                        <?php if (!$cImg): ?><i class="<?php echo e($c['cat_icon'] ?? 'fas fa-book'); ?>"></i><?php endif; ?>
                        <?php if ($c['is_featured']): ?><span class="course-badge">Featured</span><?php endif; ?>
                        <span class="course-level"><?php echo e($c['level']); ?></span>
                    </div>
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
                        <p class="course-desc"><?php echo e($c['short_desc']); ?></p>
                        <div class="course-meta">
                            <span><i class="far fa-clock"></i> <?php echo e($c['duration']); ?></span>
                            <span class="course-price"><?php echo formatPrice($c['price']); ?></span>
                        </div>
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
}
.hero-wave svg { width: 100%; height: 50px; display: block; }
.hero { padding-bottom: 50px; }

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
</style>

<?php require_once 'includes/footer.php'; ?>
