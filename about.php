<?php $page_title = 'About Us'; require_once 'includes/header.php'; ?>
<section class="page-header"><div class="container position-relative"><h1 data-aos="fade-up">About Sipway Online School</h1>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="index.php">Home</a></li><li class="breadcrumb-item active">About</li></ol></nav></div></section>
<section class="section"><div class="container"><div class="row align-items-center g-5">
<div class="col-lg-6" data-aos="fade-right">
<span class="section-badge">WHO WE ARE</span>
<h2 class="mb-3">Sipway Professional Development & Training Campus</h2>
<p class="lead">Sipway Online School brings quality education online — A/L classes, Technology, Commerce, Languages and more.</p>
<p>We are committed to delivering practical, career-oriented education. Our online platform makes learning accessible to students across Sri Lanka.</p>
<div class="row g-3 mt-2">
<?php foreach (['A/L All Streams','Expert Teachers','Flexible Online','Certificates'] as $item): ?>
<div class="col-6"><div class="d-flex align-items-center gap-2"><i class="fas fa-check-circle" style="color:#E30613"></i><span><?php echo $item; ?></span></div></div>
<?php endforeach; ?>
</div></div>
<div class="col-lg-6" data-aos="fade-left">
<div style="background:linear-gradient(135deg,#1A1A1A,#E30613);height:320px;border-radius:20px;display:flex;align-items:center;justify-content:center">
<i class="fas fa-graduation-cap" style="font-size:7rem;color:rgba(255,255,255,.25)"></i>
</div></div></div></div></section>
<section class="section bg-light"><div class="container"><div class="row g-4">
<div class="col-md-6" data-aos="fade-up"><div class="lesson-content h-100"><h3><i class="fas fa-eye me-2" style="color:#E30613"></i>Our Vision</h3><p>To be a nationally recognised professional higher education institution known for inclusiveness, innovation and exceptional student success.</p></div></div>
<div class="col-md-6" data-aos="fade-up" data-aos-delay="100"><div class="lesson-content h-100"><h3><i class="fas fa-bullseye me-2" style="color:#E30613"></i>Our Mission</h3><p>We offer high-quality, industry-aligned education that empowers learners to achieve academic excellence and lifelong success.</p></div></div>
</div></div></section>
<?php require_once 'includes/footer.php'; ?>
