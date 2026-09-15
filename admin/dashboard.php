<?php
/**
 * admin/dashboard.php
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/absensi_helper.php';
requireRole('admin');

ensureAbsensiTableExists($pdo);

$totalMahasiswa  = $pdo->query("SELECT COUNT(*) c FROM users WHERE role='mahasiswa'")->fetch()['c'];
$totalLogbook    = $pdo->query("SELECT COUNT(*) c FROM logbook")->fetch()['c'];
$logbookHariIni  = $pdo->query("SELECT COUNT(*) c FROM logbook WHERE tanggal = CURDATE()")->fetch()['c'];
$logbookBulanIni = $pdo->query("SELECT COUNT(*) c FROM logbook WHERE MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE())")->fetch()['c'];

// Stats Absensi Hari Ini
$todayAbsensiStats = $pdo->query("
    SELECT 
        SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) AS total_hadir,
        SUM(CASE WHEN a.status IN ('izin', 'sakit') THEN 1 ELSE 0 END) AS total_izin,
        SUM(CASE WHEN a.status = 'alpa' OR a.status IS NULL THEN 1 ELSE 0 END) AS total_belum
    FROM users u
    LEFT JOIN absensi a ON u.id = a.user_id AND a.tanggal = CURDATE()
    WHERE u.role = 'mahasiswa' AND u.status = 'aktif'
")->fetch();

// 5 logbook terbaru
$recentLogs = $pdo->query("
    SELECT l.*, u.nama AS nama_mhs, u.kelompok
    FROM logbook l JOIN users u ON l.user_id = u.id
    ORDER BY l.tanggal DESC, l.id DESC
    LIMIT 5
")->fetchAll();

$pageTitle   = 'Dashboard Admin';
$currentPage = 'dashboard';
$flash       = getFlash();
include __DIR__ . '/../includes/header.php';
?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="content-wrapper">

    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fa-solid fa-gauge-high"></i> Dashboard Admin</h1>
        <p>Ringkasan keseluruhan aktivitas KKN 43 Desa Taman Sari Mrangen</p>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> alert-auto-hide mb-4">
            <?= sanitize($flash['message']) ?>
        </div>
    <?php endif; ?>

    <!-- Banner Absensi Hari Ini -->
    <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #0f2342 0%, #1e5aa8 100%); color: #fff;">
        <div class="card-body p-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 rounded-circle" style="background: rgba(255,255,255,0.15); font-size: 1.8rem; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-clipboard-user text-warning"></i>
                </div>
                <div>
                    <h5 class="mb-1 text-white fw-bold">Monitoring Absensi Mahasiswa Hari Ini (<?= date('d M Y') ?>)</h5>
                    <p class="mb-0 text-white-50 small">
                        Hadir: <strong class="text-success fw-bold me-2"><?= (int)($todayAbsensiStats['total_hadir'] ?? 0) ?></strong>
                        | Izin/Sakit: <strong class="text-warning fw-bold me-2"><?= (int)($todayAbsensiStats['total_izin'] ?? 0) ?></strong>
                        | Belum Absen: <strong class="text-white fw-bold"><?= (int)($todayAbsensiStats['total_belum'] ?? 0) ?></strong>
                    </p>
                </div>
            </div>
            <div>
                <a href="<?= base_url('admin/absensi.php') ?>" class="btn btn-warning text-dark fw-bold px-4 py-2" style="border-radius: 20px;">
                    <i class="fa-solid fa-list-check me-1"></i> Kelola Absensi <i class="fa-solid fa-chevron-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card stat-blue">
                <div class="stat-card-icon">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-value"><?= $totalMahasiswa ?></div>
                    <div class="stat-card-label">Total Mahasiswa</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card stat-teal">
                <div class="stat-card-icon">
                    <i class="fa-solid fa-book-open"></i>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-value"><?= $totalLogbook ?></div>
                    <div class="stat-card-label">Total Logbook</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card stat-gold">
                <div class="stat-card-icon">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-value"><?= $logbookHariIni ?></div>
                    <div class="stat-card-label">Logbook Hari Ini</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card stat-purple">
                <div class="stat-card-icon">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-value"><?= $logbookBulanIni ?></div>
                    <div class="stat-card-label">Logbook Bulan Ini</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Logbook + Quick Actions -->
    <div class="row g-3">

        <!-- Recent Activity -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header-custom">
                    <h5><i class="fa-solid fa-clock-rotate-left"></i> Logbook Terbaru</h5>
                    <a href="<?= base_url('admin/logbook.php') ?>" class="btn btn-sm btn-outline-primary">
                        Lihat Semua <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Mahasiswa</th>
                                    <th>Tanggal</th>
                                    <th>Kegiatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentLogs)): ?>
                                    <tr><td colspan="3" class="text-center text-muted py-4">Belum ada data logbook.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($recentLogs as $l): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="navbar-user-avatar" style="width:30px;height:30px;font-size:.75rem;">
                                                    <?= mb_strtoupper(mb_substr($l['nama_mhs'], 0, 1)) ?>
                                                </div>
                                                <span><?= sanitize($l['nama_mhs']) ?></span>
                                            </div>
                                        </td>
                                        <td class="text-muted" style="white-space:nowrap;">
                                            <?= sanitize($l['hari'] . ', ' . date('d M Y', strtotime($l['tanggal']))) ?>
                                        </td>
                                        <td><?= sanitize(mb_strimwidth($l['kegiatan'], 0, 48, '…')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header-custom">
                    <h5><i class="fa-solid fa-bolt"></i> Aksi Cepat</h5>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    <a href="<?= base_url('admin/mahasiswa.php') ?>" class="quick-action-btn">
                        <div class="qa-icon" style="background:rgba(59,130,246,.1);color:var(--blue-light);">
                            <i class="fa-solid fa-user-plus"></i>
                        </div>
                        <span>Tambah Mahasiswa</span>
                        <i class="fa-solid fa-chevron-right ms-auto" style="font-size:.65rem;color:var(--text-light);"></i>
                    </a>
                    <a href="<?= base_url('admin/absensi.php') ?>" class="quick-action-btn">
                        <div class="qa-icon" style="background:rgba(21,128,61,.1);color:var(--green);">
                            <i class="fa-solid fa-clipboard-user"></i>
                        </div>
                        <span>Kelola Absensi</span>
                        <i class="fa-solid fa-chevron-right ms-auto" style="font-size:.65rem;color:var(--text-light);"></i>
                    </a>
                    <a href="<?= base_url('admin/logbook.php') ?>" class="quick-action-btn">
                        <div class="qa-icon" style="background:rgba(13,148,136,.1);color:var(--teal);">
                            <i class="fa-solid fa-book-open"></i>
                        </div>
                        <span>Kelola Logbook</span>
                        <i class="fa-solid fa-chevron-right ms-auto" style="font-size:.65rem;color:var(--text-light);"></i>
                    </a>
                    <a href="<?= base_url('admin/laporan.php') ?>" class="quick-action-btn">
                        <div class="qa-icon" style="background:rgba(124,58,237,.1);color:var(--purple);">
                            <i class="fa-solid fa-file-pdf"></i>
                        </div>
                        <span>Cetak Laporan</span>
                        <i class="fa-solid fa-chevron-right ms-auto" style="font-size:.65rem;color:var(--text-light);"></i>
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
