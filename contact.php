<?php
$page_title = 'Contact';
require_once 'includes/header.php';
$pdo = getDB();
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if (!$name || !$email || !$message) $error = 'Please fill required fields.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Invalid email.';
    else {
        $pdo->prepare("INSERT INTO contact_messages (name,email,phone,subject,message) VALUES (?,?,?,?,?)")->execute([$name,$email,$phone,$subject,$message]);
        $success = 'Message sent successfully! We will contact you soon.';
    }
}
?>
<section class="page-header"><div class="container position-relative"><h1 data-aos="fade-up">Contact Us</h1>
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="index.php">Home</a></li><li class="breadcrumb-item active">Contact</li></ol></nav></div></section>
<section class="section"><div class="container"><div class="row g-5">
<div class="col-lg-5" data-aos="fade-right">
<h2 class="mb-3">Get In Touch</h2>
<p class="text-muted mb-4">Questions about courses? Want to enroll? Reach out to us.</p>
<?php
$contacts = [
    ['fas fa-map-marker-alt','Address','Rajapihilla Road, Sarvodaya Mawatha, Kurunegala'],
    ['fas fa-phone','Phone','+94 71 813 0000'],
    ['fas fa-envelope','Email','sipwaycampus@gmail.com'],
    ['fas fa-clock','Hours','Mon - Sat: 8.00 AM - 10.00 PM'],
];
foreach ($contacts as $c): ?>
<div class="d-flex gap-3 mb-3">
<div class="feature-icon" style="width:45px;height:45px;font-size:1.1rem;margin:0;flex-shrink:0"><i class="<?php echo $c[0]; ?>"></i></div>
<div><h6 class="mb-0"><?php echo $c[1]; ?></h6><p class="text-muted mb-0 small"><?php echo $c[2]; ?></p></div>
</div>
<?php endforeach; ?>
</div>
<div class="col-lg-7" data-aos="fade-left"><div class="lesson-content">
<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
<form method="POST">
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" name="name" class="form-control" required value="<?php echo e($_POST['name']??''); ?>"></div>
<div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required value="<?php echo e($_POST['email']??''); ?>"></div>
<div class="col-md-6"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control" value="<?php echo e($_POST['phone']??''); ?>"></div>
<div class="col-md-6"><label class="form-label">Subject</label><input type="text" name="subject" class="form-control" value="<?php echo e($_POST['subject']??''); ?>"></div>
<div class="col-12"><label class="form-label">Message *</label><textarea name="message" class="form-control" rows="4" required><?php echo e($_POST['message']??''); ?></textarea></div>
<div class="col-12"><button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-paper-plane me-2"></i>Send Message</button></div>
</div></form></div></div>
</div></div></section>
<?php require_once 'includes/footer.php'; ?>
