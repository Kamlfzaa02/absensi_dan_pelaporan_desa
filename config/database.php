<?php
/**
 * config/database.php
 * -----------------------------------------------------
 * File koneksi database menggunakan PDO.
 * Semua file lain akan meng-include file ini untuk
 * mendapatkan objek koneksi $pdo.
 * -----------------------------------------------------
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'kkn_logbook');
define('DB_USER', 'root');
define('DB_PASS', '');

// Base URL helper otomatis menyesuaikan environment (localhost / subfolder / domain root)
if (!defined('BASE_URL')) {
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $projRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $basePath = '';
    if (!empty($docRoot) && strpos($projRoot, $docRoot) === 0) {
        $basePath = substr($projRoot, strlen($docRoot));
    }
    $basePath = rtrim(str_replace('\\', '/', $basePath), '/');
    define('BASE_URL', $basePath);
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        return BASE_URL . ($path !== '' ? '/' . $path : '');
    }
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
