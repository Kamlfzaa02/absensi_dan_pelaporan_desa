<?php
/**
 * mahasiswa/dashboard.php
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/absensi_helper.php';
requireRole('mahasiswa');

$userId = $_SESSION['user_id'];

ensureAbsensiTableExists($pdo);
$absensiHariIni = getAbsensiHariIni($pdo, $userId);

$totalLogbook = $pdo->prepare("SELECT COUNT(*) c FROM logbook WHERE user_id = ?");
$totalLogbook->execute([$userId]);
$totalLogbook = $totalLogbook->fetch()['c'];

// Logbook bulan ini
$bulanIni = $pdo->prepare("SELECT COUNT(*) c FROM logbook WHERE user_id = ? AND MONTH(tanggal)=MONTH(CURDATE()) AND YEAR(tanggal)=YEAR(CURDATE())");
$bulanIni->execute([$userId]);
$logbookBulanIni = $bulanIni->fetch()['c'];

// 5 logbook terakhir
$stmt = $pdo->prepare("SELECT * FROM logbook WHERE user_id = ? ORDER BY tanggal DESC, id DESC LIMIT 5");
$stmt->execute([$userId]);
$riwayat = $stmt->fetchAll();

$logbookTerakhir = $riwayat[0] ?? null;

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
$flash = getFlash();
include __DIR__ . '/../includes/header.php';
?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="content-wrapper">

    <!-- Page Header -->
    <div class="page-header">
        <h1> Dashboard</h1>
        <p>Selamat datang, <strong><?= sanitize($_SESSION['nama'] ?? '') ?></strong> - KKN 43 Desa Taman Sari Mrangen
        </p>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> alert-auto-hide mb-4">
            <?= sanitize($flash['message']) ?>
        </div>
    <?php endif; ?>

    <!-- Banner Widget Absensi Hari Ini -->
    <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #0f2342 0%, #1e5aa8 100%); color: #fff;">
        <div class="card-body p-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 rounded-circle" style="background: rgba(255,255,255,0.15); font-size: 1.8rem; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-camera-retro text-warning"></i>
                </div>
                <div>
                    <h5 class="mb-1 text-white fw-bold">Absensi KKN Hari Ini (<?= date('d M Y') ?>)</h5>
                    <p class="mb-0 text-white-50 small">
                        Status Presensi: 
                        <?php if (!$absensiHariIni): ?>
                            <span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i> Belum Absen</span>
                        <?php elseif ($absensiHariIni['status'] === 'hadir' && !$absensiHariIni['jam_pulang']): ?>
                            <span class="badge bg-info"><i class="fa-solid fa-check me-1"></i> Sudah Absen Masuk (<?= date('H:i', strtotime($absensiHariIni['jam_masuk'])) ?> WIB)</span>
                        <?php elseif ($absensiHariIni['status'] === 'hadir' && $absensiHariIni['jam_pulang']): ?>
                            <span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i> Absensi Lengkap Hari Ini</span>
                        <?php else: ?>
                            <span class="badge bg-warning"><i class="fa-solid fa-envelope-open-text me-1"></i> Status: <?= strtoupper($absensiHariIni['status']) ?></span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div>
                <a href="<?= base_url('mahasiswa/absensi.php') ?>" class="btn btn-warning text-dark fw-bold px-4 py-2" style="border-radius: 20px;">
                    <i class="fa-solid fa-location-arrow me-1"></i> Buka Absensi <i class="fa-solid fa-chevron-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-4">
            <div class="stat-card stat-blue">
                <div class="stat-card-icon">
                    <i class="fa-solid fa-book-open"></i>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-value"><?= $totalLogbook ?></div>
                    <div class="stat-card-label">Total Logbook</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-4">
            <div class="stat-card stat-teal">
                <div class="stat-card-icon">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-value"><?= $logbookBulanIni ?></div>
                    <div class="stat-card-label">Logbook Bulan Ini</div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-4">
            <div class="stat-card stat-gold">
                <div class="stat-card-icon">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-value"><?= date('d') ?></div>
                    <div class="stat-card-label"><?= date('F Y') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logbook terakhir & Quick Actions -->
    <div class="row g-3">

        <!-- Riwayat Singkat -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header-custom">
                    <h5><i class="fa-solid fa-clock-rotate-left"></i> Logbook Terakhir</h5>
                    <a href="<?= base_url('mahasiswa/riwayat_logbook.php') ?>" class="btn btn-sm btn-outline-primary">
                        Lihat Semua <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Kegiatan</th>
                                    <th>Lokasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($riwayat)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">Belum ada logbook yang diisi.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <?php foreach ($riwayat as $l): ?>
                                    <tr>
                                        <td class="text-muted" style="white-space:nowrap;">
                                            <?= sanitize($l['hari'] . ', ' . date('d M Y', strtotime($l['tanggal']))) ?>
                                        </td>
                                        <td><?= sanitize(mb_strimwidth($l['kegiatan'], 0, 50, '…')) ?></td>
                                        <td><?= sanitize($l['lokasi']) ?></td>
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
                    <h5> Aksi Cepat</h5>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    <a href="<?= base_url('mahasiswa/absensi.php') ?>" class="quick-action-btn">
                        <div class="qa-icon" style="background:rgba(21,128,61,.1);color:var(--green);">
                            <i class="fa-solid fa-camera-retro"></i>
                        </div>
                        <span>Absen Hari Ini</span>
                        <i class="fa-solid fa-chevron-right ms-auto"
                            style="font-size:.65rem;color:var(--text-light);"></i>
                    </a>
                    <a href="<?= base_url('mahasiswa/tambah_logbook.php') ?>" class="quick-action-btn">
                        <div class="qa-icon" style="background:rgba(59,130,246,.1);color:var(--blue-light);">
                            <i class="fa-solid fa-plus"></i>
                        </div>
                        <span>Tambah Logbook Baru</span>
                        <i class="fa-solid fa-chevron-right ms-auto"
                            style="font-size:.65rem;color:var(--text-light);"></i>
                    </a>
                    <a href="<?= base_url('mahasiswa/riwayat_logbook.php') ?>" class="quick-action-btn">
                        <div class="qa-icon" style="background:rgba(13,148,136,.1);color:var(--teal);">
                            <i class="fa-solid fa-list-ul"></i>
                        </div>
                        <span>Lihat Semua Riwayat</span>
                        <i class="fa-solid fa-chevron-right ms-auto"
                            style="font-size:.65rem;color:var(--text-light);"></i>
                    </a>
                    <a href="<?= base_url('mahasiswa/cetak_logbook.php') ?>" class="quick-action-btn">
                        <div class="qa-icon" style="background:rgba(124,58,237,.1);color:var(--purple);">
                            <i class="fa-solid fa-print"></i>
                        </div>
                        <span>Cetak Logbook</span>
                        <i class="fa-solid fa-chevron-right ms-auto"
                            style="font-size:.65rem;color:var(--text-light);"></i>
                    </a>
                    <a href="<?= base_url('mahasiswa/profile.php') ?>" class="quick-action-btn">
                        <div class="qa-icon" style="background:rgba(217,119,6,.1);color:var(--gold);">
                            <i class="fa-solid fa-id-card"></i>
                        </div>
                        <span>Lihat Profil</span>
                        <i class="fa-solid fa-chevron-right ms-auto"
                            style="font-size:.65rem;color:var(--text-light);"></i>
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>