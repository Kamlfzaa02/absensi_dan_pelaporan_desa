<?php
/**
 * mahasiswa/hapus_logbook.php
 * -----------------------------------------------------
 * Menghapus logbook milik mahasiswa yang login saja.
 * Dipanggil lewat link dari riwayat_logbook.php
 * (?id=...). Query WHERE user_id mencegah mahasiswa
 * menghapus logbook milik orang lain (IDOR protection).
 * -----------------------------------------------------
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole('mahasiswa');

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("DELETE FROM logbook WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);

setFlash('success', 'Logbook berhasil dihapus.');
header('Location: riwayat_logbook.php');
exit;
