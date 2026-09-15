<?php
/**
 * index.php
 * -----------------------------------------------------
 * Halaman index. Mengarahkan user sesuai status login & role.
 * -----------------------------------------------------
 */
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: mahasiswa/dashboard.php');
    }
} else {
    header('Location: login.php');
}
exit;
