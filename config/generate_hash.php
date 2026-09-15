<?php
/**
 * config/generate_hash.php
 * -----------------------------------------------------
 * Script bantu (jalankan sekali lewat browser lalu HAPUS)
 * untuk membuat password_hash asli dan mengupdate akun
 * seed di tabel users (admin & mahasiswa contoh).
 * -----------------------------------------------------
 */
require_once __DIR__ . '/database.php';

$password = 'password123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email IN ('admin@upgris.ac.id','budi@student.upgris.ac.id')");
$stmt->execute([$hash]);

echo "Password akun contoh berhasil di-hash ulang.<br>";
echo "Gunakan password: <b>$password</b> untuk login admin@upgris.ac.id maupun budi@student.upgris.ac.id.<br>";
echo "<b>Setelah ini, hapus file config/generate_hash.php demi keamanan.</b>";
