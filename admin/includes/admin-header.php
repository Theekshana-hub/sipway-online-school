<?php
require_once __DIR__ . '/admin-auth.php';
$current = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($page_title) ? e($page_title).' | ' : ''; ?>Admin - Sipway</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
<style>
:root{--sw:250px;--red:#E30613;--red-d:#C00510;--black:#1A1A1A;--orange:#F5A623}
body{font-family:'Inter',sans-serif;background:#f4f5f7}
.sidebar{position:fixed;top:0;left:0;width:var(--sw);height:100vh;background:linear-gradient(180deg,#0D0D0D,#1A1A1A);color:#fff;z-index:1000;overflow-y:auto}
.sidebar-brand{padding:1.3rem;display:flex;align-items:center;gap:10px;border-bottom:1px solid rgba(255,255,255,.08);font-weight:800;font-size:1.2rem;font-family:'Poppins',sans-serif}
.sidebar-brand .bi{width:38px;height:38px;background:linear-gradient(135deg,var(--red),var(--orange));border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem}
.sidebar-brand span span{color:var(--red)}
.sidebar-nav{padding:.8rem 0}
.sidebar-nav .sec{padding:.7rem 1.3rem .3rem;font-size:.68rem;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,.3);font-weight:600}
.sidebar-nav a{display:flex;align-items:center;gap:10px;padding:.7rem 1.3rem;color:rgba(255,255,255,.6);text-decoration:none;font-size:.9rem;border-left:3px solid transparent;transition:.2s}
.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(227,6,19,.15);color:#fff;border-left-color:var(--red)}
.sidebar-nav a i{width:18px;text-align:center}
.main{margin-left:var(--sw);min-height:100vh;padding:1.3rem}
.topbar{background:#fff;border-radius:12px;padding:.9rem 1.3rem;display:flex;justify-content:space-between;align-items:center;box-shadow:0 1px 4px rgba(0,0,0,.04);margin-bottom:1.3rem}
.topbar h5{font-family:'Poppins',sans-serif;font-weight:700;color:var(--black);margin:0;font-size:1.1rem}
.stat-card{background:#fff;border-radius:12px;padding:1.3rem;box-shadow:0 1px 4px rgba(0,0,0,.04);transition:.3s;border:1px solid transparent}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(0,0,0,.07);border-color:rgba(227,6,19,.1)}
.stat-card .icon{width:46px;height:46px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff}
.table-card{background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.04);overflow:hidden;border:1px solid #eee}
.table-card .card-header{background:#fff;border-bottom:1px solid #eee;padding:.9rem 1.3rem;font-weight:600;font-family:'Poppins',sans-serif;color:var(--black)}
.btn-primary{background:linear-gradient(135deg,var(--red),var(--red-d));border:none;border-radius:8px;box-shadow:0 2px 8px rgba(227,6,19,.25)}
.btn-primary:hover{background:linear-gradient(135deg,var(--red-d),#A0040D)}
.btn-outline-primary{border-color:var(--red);color:var(--red)}
.btn-outline-primary:hover{background:var(--red);border-color:var(--red);color:#fff}
.form-control:focus,.form-select:focus{border-color:var(--red);box-shadow:0 0 0 3px rgba(227,6,19,.12)}
.bg-primary{background-color:var(--red)!important}
.text-primary{color:var(--red)!important}
.user-av{width:34px;height:34px;background:linear-gradient(135deg,var(--red),var(--orange));border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.85rem}
@media(max-width:991.98px){.sidebar{transform:translateX(-100%)}.sidebar.show{transform:translateX(0)}.main{margin-left:0}}
</style>
</head>
<body>
<aside class="sidebar" id="sidebar">
<div class="sidebar-brand"><div class="bi"><i class="fas fa-graduation-cap"></i></div><span>SIPWAY<span>Online</span></span></div>
<nav class="sidebar-nav">
<div class="sec">Main</div>
<a href="admin-dashboard.php" class="<?php echo $current=='admin-dashboard'?'active':''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
<div class="sec">Content</div>
<a href="admin-categories.php" class="<?php echo $current=='admin-categories'?'active':''; ?>"><i class="fas fa-folder"></i> Categories</a>
<a href="admin-courses.php" class="<?php echo in_array($current,['admin-courses','admin-add-course','admin-edit-course'])?'active':''; ?>"><i class="fas fa-book"></i> Courses</a>
<a href="admin-teachers.php" class="<?php echo in_array($current,['admin-teachers','admin-add-teacher','admin-edit-teacher'])?'active':''; ?>"><i class="fas fa-chalkboard-teacher"></i> Teachers</a>
<a href="admin-enrollments.php" class="<?php echo $current=='admin-enrollments'?'active':''; ?>"><i class="fas fa-user-graduate"></i> Enrollments</a>
<div class="sec">Users</div>
<a href="admin-students.php" class="<?php echo $current=='admin-students'?'active':''; ?>"><i class="fas fa-users"></i> Students</a>
<a href="admin-messages.php" class="<?php echo $current=='admin-messages'?'active':''; ?>"><i class="fas fa-envelope"></i> Messages</a>
<div class="sec">System</div>
<a href="admin-settings.php" class="<?php echo $current=='admin-settings'?'active':''; ?>"><i class="fas fa-cog"></i> Settings</a>
<a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> View Website</a>
<a href="admin-logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</nav>
</aside>
<div class="main">
<div class="topbar">
<div class="d-flex align-items-center gap-2">
<button class="btn btn-sm d-lg-none" id="sbToggle" style="border:1px solid #ddd"><i class="fas fa-bars"></i></button>
<h5><?php echo $page_title ?? 'Dashboard'; ?></h5>
</div>
<div class="d-flex align-items-center gap-2">
<span class="text-muted small d-none d-md-inline"><?php echo e($_SESSION['full_name']); ?></span>
<div class="user-av"><i class="fas fa-user"></i></div>
</div>
</div>
