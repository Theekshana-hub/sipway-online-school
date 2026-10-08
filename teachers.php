<?php
$page_title = 'Teachers';
require_once 'includes/header.php';
$pdo = getDB();
$teachers = $pdo->query("SELECT * FROM teachers WHERE status='active' ORDER BY full_name")->fetchAll();
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

/* Photo area: full image visible, no cropping */
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
    position: relative;
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

/* Fallback when no photo */
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
    margin-bottom: 6px;
}
.teacher-subject {
    display: inline-block;
    color: #E30613;
    font-weight: 600;
    font-size: .9rem;
    background: rgba(227,6,19,.08);
    padding: 4px 14px;
    border-radius: 50px;
    margin-bottom: 12px;
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
            <?php foreach ($teachers as $i => $t): ?>
                <?php $tImg = imageUrl('teachers', $t['photo'] ?? null); ?>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $i * 80; ?>">
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
                            <span class="teacher-subject"><?php echo e($t['subject']); ?></span>
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
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>