# Sistem Logbook KKN - Universitas PGRI Semarang

Aplikasi berbasis PHP Native (tanpa framework) + MySQL (PDO) + Bootstrap 5.

## Cara Instalasi (XAMPP)

1. Copy folder `kkn-logbook` ke dalam `htdocs` (Windows: `C:\xampp\htdocs\kkn-logbook`).
2. Jalankan Apache & MySQL lewat XAMPP Control Panel.
3. Buka phpMyAdmin (`http://localhost/phpmyadmin`), buat database baru (atau langsung import `database.sql`, database `kkn_logbook` akan dibuat otomatis).
4. Cek/edit `config/database.php` jika username/password MySQL Anda berbeda dari default XAMPP (`root` tanpa password).
5. Buka `http://localhost/kkn-logbook/config/generate_hash.php` di browser SATU KALI untuk membuat password hash asli pada 2 akun contoh (admin & mahasiswa), lalu **hapus file tersebut** setelah selesai.
6. Buka `http://localhost/kkn-logbook/` di browser.

## Akun Contoh (setelah menjalankan generate_hash.php)

| Role      | Email                        | Password    |
|-----------|-------------------------------|-------------|
| Admin     | admin@upgris.ac.id            | password123 |
| Mahasiswa | budi@student.upgris.ac.id     | password123 |

## Struktur Folder

```
kkn-logbook/
├── assets/            -> CSS, JS, gambar
│   ├── css/style.css      (tema utama: biru, putih, abu muda)
│   └── css/print.css      (khusus tampilan cetak logbook resmi)
├── config/
│   ├── database.php       (koneksi PDO)
│   └── generate_hash.php  (bantu buat password hash awal - hapus setelah dipakai)
├── includes/           -> Komponen bersama (header, navbar, sidebar, footer, auth, form partial, print template)
├── admin/              -> Semua halaman khusus admin
├── mahasiswa/           -> Semua halaman khusus mahasiswa
├── database.sql         -> Struktur database + data awal
├── index.php, login.php, logout.php
```

## Fitur Utama

**Admin**: Dashboard statistik, CRUD Mahasiswa, Kelola Logbook (lihat/cari/detail/hapus semua logbook), Laporan & Cetak (filter per mahasiswa/kelompok/bulan/rentang tanggal, output print/PDF).

**Mahasiswa**: Dashboard ringkas, Tambah Logbook harian, Riwayat Logbook (cari/edit/hapus/filter), Cetak Logbook (semua/per bulan/rentang tanggal) sesuai format resmi KKN, Profil & ganti password.

## Keamanan yang Diterapkan
- Password di-hash dengan `password_hash()` / `password_verify()`.
- Semua query database memakai PDO Prepared Statement (mencegah SQL Injection).
- Semua output ditampilkan lewat `htmlspecialchars()` (mencegah XSS).
- CSRF token sederhana pada setiap form POST.
- Proteksi role (`requireRole()`), setiap halaman admin/mahasiswa memvalidasi sesi & peran.
- Query logbook mahasiswa selalu difilter `WHERE user_id = session` agar mahasiswa tidak bisa mengedit/menghapus data milik mahasiswa lain (anti-IDOR).
- `session_regenerate_id()` setelah login untuk mencegah session fixation.

## Cetak Logbook (PDF)
Aplikasi ini TIDAK memakai library PDF pihak ketiga. Fitur "cetak PDF" memanfaatkan fitur bawaan browser **Print > Save as PDF**, dengan CSS khusus (`print.css`) yang meniru tampilan dokumen resmi KKN, termasuk otomatis pindah halaman setiap 30 baris kegiatan.

## Catatan Pengembangan Lanjutan
- Semua field wajib divalidasi juga di sisi client (HTML5 `required`) DAN server (PHP), sesuai praktik keamanan berlapis.
- Untuk produksi sesungguhnya, sebaiknya set `session.cookie_httponly` dan `session.cookie_secure` (jika HTTPS) di `php.ini`, serta pindahkan kredensial database ke file `.env` di luar web root.
