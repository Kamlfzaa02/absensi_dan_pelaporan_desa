<?php
/**
 * includes/sidebar.php
 * Sidebar navigasi — role-based menu
 */
if (!isset($currentPage)) $currentPage = '';
$role = $_SESSION['role'] ?? '';
?>
<aside class="sidebar" id="sidebar">

    <!-- Brand -->
    <div class="sidebar-brand">
        <img src="<?= base_url('assets/images/logo.png') ?>" alt="Logo" class="animate-logo">
        <div class="sidebar-brand-text">
            <strong>KKN 43</strong>
            <span>Desa Taman Sari Mrangen</span>
        </div>
    </div>

    <!-- Menu -->
    <nav class="sidebar-menu">
        <?php if ($role === 'admin'): ?>

            <div class="sidebar-section">Menu Utama</div>

            <div class="nav-item">
                <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>"
                   href="<?= base_url('admin/dashboard.php') ?>">
                    <span class="nav-icon"><i class="fa-solid fa-gauge-high"></i></span>
                    Dashboard
                </a>
            </div>

            <div class="sidebar-section">Manajemen</div>

            <div class="nav-item">
                <a class="nav-link <?= $currentPage === 'mahasiswa' ? 'active' : '' ?>"
                   href="<?= base_url('admin/mahasiswa.php') ?>">
                    <span class="nav-icon"><i class="fa-solid fa-users"></i></span>
                    Mahasiswa
                </a>
            </div>

            <div class="nav-item">
                <a class="nav-link <?= $currentPage === 'absensi' ? 'active' : '' ?>"
                   href="<?= base_url('admin/absensi.php') ?>">
                    <span class="nav-icon"><i class="fa-solid fa-clipboard-user"></i></span>
                    Absensi
                </a>
            </div>

            <div class="nav-item">
                <a class="nav-link <?= $currentPage === 'logbook' ? 'active' : '' ?>"
                   href="<?= base_url('admin/logbook.php') ?>">
                    <span class="nav-icon"><i class="fa-solid fa-book-open"></i></span>
                    Logbook
                </a>
            </div>

            <div class="nav-item">
                <a class="nav-link <?= $currentPage === 'laporan' ? 'active' : '' ?>"
                   href="<?= base_url('admin/laporan.php') ?>">
                    <span class="nav-icon"><i class="fa-solid fa-file-lines"></i></span>
                    Laporan & Cetak
                </a>
            </div>

        <?php elseif ($role === 'mahasiswa'): ?>

            <div class="sidebar-section">Menu Utama</div>

            <div class="nav-item">
                <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>"
                   href="<?= base_url('mahasiswa/dashboard.php') ?>">
                    <span class="nav-icon"><i class="fa-solid fa-gauge-high"></i></span>
                    Dashboard
                </a>
            </div>

            <div class="sidebar-section">Absensi</div>

            <div class="nav-item">
                <a class="nav-link <?= $currentPage === 'absensi' ? 'active' : '' ?>"
                   href="<?= base_url('mahasiswa/absensi.php') ?>">
                    <span class="nav-icon"><i class="fa-solid fa-camera-retro"></i></span>
                    Absen Hari Ini
                </a>
            </div>

            <div class="nav-item">
                <a class="nav-link <?= $currentPage === 'riwayat_absensi' ? 'active' : '' ?>"
                   href="<?= base_url('mahasiswa/riwayat_absensi.php') ?>">
                    <span class="nav-icon"><i class="fa-solid fa-calendar-check"></i></span>
                    Riwayat Absensi
                </a>
            </div>

            <div class="sidebar-section">Logbook</div>

            <div class="nav-item">
                <a class="nav-link <?= $currentPage === 'tambah' ? 'active' : '' ?>"
                   href="<?= base_url('mahasiswa/tambah_logbook.php') ?>">
                    <span class="nav-icon"><i class="fa-solid fa-plus"></i></span>
                    Tambah Logbook
                </a>
            </div>

            <div class="nav-item">
                <a class="nav-link <?= $currentPage === 'riwayat' ? 'active' : '' ?>"
                   href="<?= base_url('mahasiswa/riwayat_logbook.php') ?>">
                    <span class="nav-icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
                    Riwayat Logbook
                </a>
            </div>

            <div class="nav-item">
                <a class="nav-link <?= $currentPage === 'cetak' ? 'active' : '' ?>"
                   href="<?= base_url('mahasiswa/cetak_logbook.php') ?>">
                    <span class="nav-icon"><i class="fa-solid fa-print"></i></span>
                    Cetak Logbook
                </a>
            </div>

            <div class="sidebar-section">Akun</div>

            <div class="nav-item">
                <a class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>"
                   href="<?= base_url('mahasiswa/profile.php') ?>">
                    <span class="nav-icon"><i class="fa-solid fa-id-card"></i></span>
                    Profil Saya
                </a>
            </div>

        <?php endif; ?>
    </nav>

    <!-- Logout -->
    <div class="sidebar-footer">
        <a href="<?= base_url('logout.php') ?>">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            Keluar
        </a>
    </div>

</aside>
<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
