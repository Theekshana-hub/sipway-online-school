<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sipway_online');
define('DB_USER', 'root');
define('DB_PASS', '');
define('SITE_NAME', 'Sipway Online School');
define('SITE_URL', 'http://localhost/sipway-online-school');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch (PDOException $e) {
            die("Database connection failed. Please check config.");
        }
    }
    return $pdo;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
