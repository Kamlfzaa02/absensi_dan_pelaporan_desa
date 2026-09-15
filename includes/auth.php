<?php
/**
 * includes/auth.php
 * -----------------------------------------------------
 * Helper untuk session, proteksi halaman (login guard),
 * dan fungsi keamanan sederhana (CSRF, sanitasi input).
 * File ini WAJIB di-include paling atas di setiap halaman
 * yang butuh login (admin & mahasiswa).
 * -----------------------------------------------------
 */

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah user sudah login.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Paksa user login, kalau belum -> redirect ke login.php
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

/**
 * Paksa role tertentu. Contoh: requireRole('admin');
 * Jika role tidak sesuai -> tolak akses.
 */
function requireRole(string $role): void
{
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        header('Location: ' . base_url('login.php?error=akses_ditolak'));
        exit;
    }
}

/**
 * Sanitasi input teks sederhana (mencegah XSS saat ditampilkan).
 */
function sanitize(string $data): string
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token dan simpan di session.
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validasi CSRF token yang dikirim lewat form.
 */
function validateCsrfToken(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && $token !== null && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Helper untuk menampilkan pesan flash (sukses/error) sekali tampil.
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
