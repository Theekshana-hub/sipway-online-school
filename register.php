<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
if (isLoggedIn()) redirect('student-dashboard.php');
$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if (!$name || !$email || !$password) $error = 'Fill all required fields.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Invalid email.';
    elseif (strlen($password) < 6) $error = 'Password min 6 characters.';
    elseif ($password !== $confirm) $error = 'Passwords do not match.';
    else {
        $pdo = getDB();
        $check = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $check->execute([$email]);
        if ($check->fetch()) $error = 'Email already registered.';
        else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (full_name,email,password,phone,role) VALUES (?,?,?,?,'student')")->execute([$name,$email,$hash,$phone]);
            $success = 'Registration successful! You can now login.';
        }
    }
}
$page_title = 'Register';
require_once 'includes/header.php';
?>
<section class="auth-page"><div class="container">
<div class="auth-card" data-aos="zoom-in" style="max-width:480px">
<div class="text-center mb-2"><span class="brand-icon" style="width:55px;height:55px;font-size:1.4rem;display:inline-flex"><i class="fas fa-graduation-cap"></i></span></div>
<h2>Create Account</h2>
<p class="subtitle">Join Sipway Online School</p>
<?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?> <a href="login.php">Login</a></div><?php endif; ?>
<form method="POST">
<div class="mb-3"><label class="form-label">Full Name *</label><input type="text" name="full_name" class="form-control" required value="<?php echo e($_POST['full_name']??''); ?>"></div>
<div class="mb-3"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required value="<?php echo e($_POST['email']??''); ?>"></div>
<div class="mb-3"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control" value="<?php echo e($_POST['phone']??''); ?>"></div>
<div class="row g-2 mb-3">
<div class="col-6"><label class="form-label">Password *</label><input type="password" name="password" class="form-control" required minlength="6"></div>
<div class="col-6"><label class="form-label">Confirm *</label><input type="password" name="confirm" class="form-control" required></div>
</div>
<button type="submit" class="btn btn-primary w-100 btn-lg">Register</button>
</form>
<p class="text-center mt-3 mb-0">Have account? <a href="login.php" style="color:#E30613;font-weight:600">Login</a></p>
</div></div></section>
<?php require_once 'includes/footer.php'; ?>
