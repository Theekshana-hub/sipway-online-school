<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
if (!isAdmin()) {
    header('Location: ' . SITE_URL . '/admin-login.php');
    exit;
}
