<?php
/**
 * includes/navbar.php
 * Navbar atas — menampilkan nama user, tanggal, dan dropdown logout.
 */
$namaUser = $_SESSION['nama'] ?? 'User';
$roleUser = $_SESSION['role'] ?? '';
$initial  = mb_strtoupper(mb_substr($namaUser, 0, 1));
$hariIni  = date('d M Y');
?>
<nav class="navbar-top" id="navbarTop">

    <!-- Mobile toggle -->
    <button class="navbar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
        <i class="fa-solid fa-bars"></i>
    </button>

    <!-- Page title / breadcrumb -->
    <div class="navbar-title">
        <strong>Logbook KKN 43</strong>
        <span class="d-none d-md-inline"> — Desa Taman Sari Mrangen</span>
    </div>

    <!-- Date -->
    <div class="navbar-date d-none d-sm-flex">
        <i class="fa-regular fa-calendar"></i>
        <?= $hariIni ?>
    </div>

    <!-- User dropdown -->
    <div class="dropdown">
        <button class="navbar-user" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="navbar-user-avatar"><?= htmlspecialchars($initial) ?></div>
            <div class="navbar-user-info d-none d-md-block">
                <div class="navbar-user-name"><?= htmlspecialchars($namaUser) ?></div>
                <div class="navbar-user-role"><?= htmlspecialchars($roleUser) ?></div>
            </div>
            <i class="fa-solid fa-chevron-down" style="font-size:.65rem;color:var(--text-light);margin-left:2px;"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius:12px;min-width:170px;padding:8px;">
            <?php if ($roleUser === 'mahasiswa'): ?>
            <li>
                <a class="dropdown-item rounded-2 py-2" href="<?= base_url('mahasiswa/profile.php') ?>">
                    <i class="fa-regular fa-id-card me-2 text-muted"></i> Profil Saya
                </a>
            </li>
            <li><hr class="dropdown-divider my-1"></li>
            <?php endif; ?>
            <li>
                <a class="dropdown-item rounded-2 py-2 text-danger" href="<?= base_url('logout.php') ?>">
                    <i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Keluar
                </a>
            </li>
        </ul>
    </div>

</nav>
