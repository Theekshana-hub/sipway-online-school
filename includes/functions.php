<?php
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = strtolower($text);
    return $text ?: 'n-a';
}

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin';
}

function isStudent() {
    return isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'student';
}

function requireAdmin() {
    if (!isAdmin()) {
        redirect(SITE_URL . '/admin-login.php');
    }
}

function requireStudent() {
    if (!isStudent()) {
        redirect(SITE_URL . '/login.php');
    }
}

function formatPrice($price) {
    return 'Rs. ' . number_format($price, 0);
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff/60) . ' min ago';
    if ($diff < 86400) return floor($diff/3600) . ' hours ago';
    return date('M d, Y', $time);
}

/**
 * Upload image file. Returns filename or false on failure.
 * $folder: courses | teachers | categories
 */
function uploadImage($fileInput, $folder = 'courses', $oldFile = null) {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] === UPLOAD_ERR_NO_FILE) {
        return $oldFile; // keep old if no new file
    }
    $file = $_FILES[$fileInput];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed)) {
        return false;
    }
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB max
        return false;
    }
    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        default => 'jpg',
    };
    $name = uniqid($folder . '_', true) . '.' . $ext;
    $dir = __DIR__ . '/../uploads/' . $folder . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $dest = $dir . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return false;
    }
    // delete old file
    if ($oldFile && $oldFile !== 'default-course.jpg' && $oldFile !== 'default-teacher.jpg') {
        $oldPath = $dir . $oldFile;
        if (file_exists($oldPath)) {
            @unlink($oldPath);
        }
    }
    return $name;
}

function imageUrl($folder, $filename, $fallbackIcon = 'fas fa-book') {
    if ($filename && $filename !== 'default-course.jpg' && $filename !== 'default-teacher.jpg') {
        $path = __DIR__ . '/../uploads/' . $folder . '/' . $filename;
        if (file_exists($path)) {
            return SITE_URL . '/uploads/' . $folder . '/' . $filename;
        }
    }
    return null; // use icon fallback
}
