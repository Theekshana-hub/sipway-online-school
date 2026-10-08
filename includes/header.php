<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? e($page_title) . ' | ' : ''; ?>Sipway Online School</title>
    <meta name="description" content="Sipway Online School - A/L Classes, Technology, Commerce, Korean Language, Grade 6-7 & Astrology courses online.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>
<div id="preloader"><div class="loader"><div class="loader-circle"></div><div class="loader-text">SIPWAY</div></div></div>
<?php require_once __DIR__ . '/navbar.php'; ?>
<div class="nav-spacer"></div>
