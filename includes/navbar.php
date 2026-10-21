<nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="<?php echo SITE_URL; ?>/index.php">
      <img src="<?php echo SITE_URL; ?>/logo.png" alt="SIPWAY Logo" class="brand-logo me-2" style="height: 40px; width: auto;">
      <span class="brand-text">SIPWAY<span>online school</span></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <li class="nav-item">
          <a class="nav-link <?php echo $current_page=='index'?'active':''; ?>" href="<?php echo SITE_URL; ?>/index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $current_page=='courses'||$current_page=='course-details'?'active':''; ?>" href="<?php echo SITE_URL; ?>/courses.php">Classes</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $current_page=='teachers'?'active':''; ?>" href="<?php echo SITE_URL; ?>/teachers.php">Teachers</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $current_page=='about'?'active':''; ?>" href="<?php echo SITE_URL; ?>/about.php">About</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $current_page=='contact'?'active':''; ?>" href="<?php echo SITE_URL; ?>/contact.php">Contact</a>
        </li>
        <?php if (isLoggedIn()): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
              <i class="fas fa-user-circle me-1"></i>
              <?php echo e($_SESSION['full_name'] ?? 'Account'); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <?php if (isStudent()): ?>
                <li>
                  <a class="dropdown-item" href="<?php echo SITE_URL; ?>/student-dashboard.php">
                    <i class="fas fa-book-open me-2"></i>My Learning
                  </a>
                </li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php">
                  <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
              </li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item ms-lg-2">
            <a class="btn btn-outline-primary btn-sm px-3" href="<?php echo SITE_URL; ?>/login.php">Login</a>
          </li>
          <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
            <a class="btn btn-primary btn-sm px-3" href="<?php echo SITE_URL; ?>/register.php">Register</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
