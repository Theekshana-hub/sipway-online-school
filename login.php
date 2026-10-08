<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
if (isLoggedIn()) {
    if (isAdmin()) redirect('admin/admin-dashboard.php');
    redirect('student-dashboard.php');
}
$error = '';
$redirect = $_GET['redirect'] ?? 'student-dashboard.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$email || !$password) $error = 'Enter email and password.';
    else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? AND role='student' AND status='active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            redirect($redirect);
        }
        $error = 'Invalid email or password.';
    }
}
$page_title = 'Student Login';
require_once 'includes/header.php';
?>
<section class="auth-page"><div class="container">
<div class="auth-card" data-aos="zoom-in">
<div class="text-center mb-2"><span class="brand-icon" style="width:55px;height:55px;font-size:1.4rem;display:inline-flex"><i class="fas fa-graduation-cap"></i></span></div>
<h2>Student Login</h2>
<p class="subtitle">Access your courses & learning dashboard</p>
<?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
<form method="POST">
<div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required value="<?php echo e($_POST['email']??''); ?>"></div>
<div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
<button type="submit" class="btn btn-primary w-100 btn-lg">Login</button>
</form>
<p class="text-center mt-3 mb-0">No account? <a href="register.php" style="color:#E30613;font-weight:600">Register</a></p>
</div></div></section>
<?php require_once 'includes/footer.php'; ?>
