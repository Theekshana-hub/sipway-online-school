<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isAdmin()) redirect('admin/admin-dashboard.php');
// Clear student session if any
if (isLoggedIn() && !isAdmin()) { session_destroy(); session_start(); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$email || !$password) $error = 'Enter email and password.';
    else {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? AND role='admin' AND status='active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = 'admin';
            $_SESSION['email'] = $user['email'];
            redirect('admin/admin-dashboard.php');
        }
        $error = 'Invalid admin credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login | Sipway Online School</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--red:#E30613;--orange:#F5A623;--black:#1A1A1A}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Poppins',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0D0D0D,#1A1A1A 50%,#2A0A0A);position:relative;overflow:hidden}
body::before{content:'';position:absolute;top:-25%;right:-15%;width:550px;height:550px;background:radial-gradient(circle,rgba(227,6,19,.2) 0%,transparent 70%);animation:f 8s ease-in-out infinite}
body::after{content:'';position:absolute;bottom:-15%;left:-10%;width:400px;height:400px;background:radial-gradient(circle,rgba(245,166,35,.1) 0%,transparent 70%);animation:f 10s ease-in-out infinite reverse}
@keyframes f{0%,100%{transform:translateY(0)}50%{transform:translateY(-20px)}}
.card{background:#fff;border-radius:20px;padding:2.3rem;width:100%;max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,.4);position:relative;z-index:2;animation:up .5s ease}
@keyframes up{from{opacity:0;transform:translateY(25px)}to{opacity:1;transform:translateY(0)}}
.logo{text-align:center;margin-bottom:1.2rem}
.logo-icon{width:65px;height:65px;background:linear-gradient(135deg,var(--red),var(--orange));border-radius:16px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:1.8rem;margin-bottom:.8rem;box-shadow:0 8px 25px rgba(227,6,19,.3)}
.logo h1{font-size:1.4rem;font-weight:800;color:var(--black)}.logo h1 span{color:var(--red)}
.logo p{color:#6c757d;font-size:.85rem}
.badge-admin{display:inline-block;background:rgba(227,6,19,.1);color:var(--red);padding:.25rem .9rem;border-radius:50px;font-size:.78rem;font-weight:600;margin-bottom:1.2rem}
.form-label{font-weight:600;font-size:.88rem;color:#343a40}
.form-control{border-radius:10px;padding:.7rem 1rem;border:1.5px solid #dee2e6}
.form-control:focus{border-color:var(--red);box-shadow:0 0 0 3px rgba(227,6,19,.12)}
.btn-login{background:linear-gradient(135deg,var(--red),#C00510);color:#fff;border:none;border-radius:50px;padding:.75rem;font-weight:600;width:100%;transition:.3s;box-shadow:0 4px 15px rgba(227,6,19,.3)}
.btn-login:hover{transform:translateY(-2px);box-shadow:0 6px 25px rgba(227,6,19,.4);color:#fff}
.alert{border-radius:10px;font-size:.88rem;border:none;background:rgba(227,6,19,.1);color:var(--red)}
.back{text-align:center;margin-top:1.2rem}.back a{color:#6c757d;font-size:.88rem;text-decoration:none}.back a:hover{color:var(--red)}
</style>
</head>
<body>
<div class="card">
<div class="logo">
<div class="logo-icon"><i class="fas fa-graduation-cap"></i></div>
<h1>SIP<span>WAY</span></h1>
<p>Online School</p>
<div class="badge-admin"><i class="fas fa-shield-alt me-1"></i> Admin Panel</div>
</div>
<?php if ($error): ?><div class="alert mb-3"><i class="fas fa-exclamation-circle me-1"></i> <?php echo $error; ?></div><?php endif; ?>
<form method="POST">
<div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required placeholder="admin@sipwaycampus.lk" value="<?php echo htmlspecialchars($_POST['email']??''); ?>"></div>
<div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required placeholder="••••••••"></div>
<button type="submit" class="btn-login"><i class="fas fa-sign-in-alt me-2"></i>Login to Dashboard</button>
</form>
<div class="back"><a href="index.php"><i class="fas fa-arrow-left me-1"></i> Back to Website</a></div>
</div>
</body>
</html>
