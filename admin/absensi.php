<?php
/**
 * admin/absensi.php
 * -----------------------------------------------------
 * Halaman Monitoring & Rekap Absensi Mahasiswa (Sisi Admin)
 * Fitur: Filter tanggal, cari mahasiswa, statistik harian,
 * preview foto selfie, peta lokasi GPS, dan manual edit status.
 * -----------------------------------------------------
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/absensi_helper.php';

requireRole('admin');

$pageTitle = 'Kelola Absensi Mahasiswa';
$currentPage = 'absensi';

ensureAbsensiTableExists($pdo);
$absensiSettings = getAbsensiSettings($pdo);

// Parameter Filter
$selectedTanggal = isset($_GET['tanggal']) && !empty($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$search = trim($_GET['search'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');

// Process Manual Edit / Update Status by Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Sesi tidak valid, silakan muat ulang halaman.');
        header('Location: absensi.php?tanggal=' . urlencode($selectedTanggal));
        exit;
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'save_absensi_settings') {
        $settingsToSave = [];
        $timeKeys = ['masuk_buka', 'masuk_tutup', 'pulang_buka', 'pulang_tutup'];
        $allowedStatus = ['1', '0'];

        foreach ($timeKeys as $key) {
            $value = trim($_POST[$key] ?? '');
            if (!preg_match('/^\d{2}:\d{2}$/', $value)) {
                setFlash('danger', 'Format jam untuk pengaturan absensi tidak valid.');
                header('Location: absensi.php?tanggal=' . urlencode($selectedTanggal));
                exit;
            }
            $settingsToSave[$key] = $value;
        }

        $statusAbsensi = trim($_POST['absensi_aktif'] ?? '1');
        if (!in_array($statusAbsensi, $allowedStatus, true)) {
            $statusAbsensi = '1';
        }
        $settingsToSave['absensi_aktif'] = $statusAbsensi;

        saveAbsensiSettings($pdo, $settingsToSave);
        $absensiSettings = getAbsensiSettings($pdo);
        setFlash('success', 'Pengaturan jam absensi berhasil disimpan.');
        header('Location: absensi.php?tanggal=' . urlencode($selectedTanggal));
        exit;
    }

    if ($action === 'update_status') {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $newStatus = $_POST['status'] ?? 'hadir';
        $keterangan = trim($_POST['keterangan'] ?? '');

        if ($targetUserId > 0 && in_array($newStatus, ['hadir', 'izin', 'sakit', 'alpa'])) {
            // Cek apakah sudah ada record absensi
            $stmt = $pdo->prepare("SELECT id FROM absensi WHERE user_id = ? AND tanggal = ?");
            $stmt->execute([$targetUserId, $selectedTanggal]);
            $existing = $stmt->fetch();

            if ($existing) {
                $stmt = $pdo->prepare("UPDATE absensi SET status = ?, keterangan = ? WHERE user_id = ? AND tanggal = ?");
                $stmt->execute([$newStatus, $keterangan, $targetUserId, $selectedTanggal]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO absensi (user_id, tanggal, status, keterangan) VALUES (?, ?, ?, ?)");
                $stmt->execute([$targetUserId, $selectedTanggal, $newStatus, $keterangan]);
            }

            setFlash('success', 'Berhasil memperbarui status absensi mahasiswa.');
        } else {
            setFlash('danger', 'Data tidak valid.');
        }

        header('Location: absensi.php?tanggal=' . urlencode($selectedTanggal));
        exit;
    }
}

// Build SQL Query
$sql = "
    SELECT 
        u.id AS user_id, 
        u.nama, 
        u.nim, 
        u.email, 
        u.kelompok,
        a.id AS absensi_id,
        a.tanggal,
        a.jam_masuk,
        a.jam_pulang,
        a.foto_masuk,
        a.foto_pulang,
        a.latitude_masuk,
        a.longitude_masuk,
        a.lokasi_masuk,
        a.latitude_pulang,
        a.longitude_pulang,
        a.lokasi_pulang,
        COALESCE(a.status, 'belum_absen') AS status,
        a.keterangan
    FROM users u
    LEFT JOIN absensi a ON u.id = a.user_id AND a.tanggal = :tanggal
    WHERE u.role = 'mahasiswa' AND u.status = 'aktif'
";

$params = [':tanggal' => $selectedTanggal];

if ($search !== '') {
    $sql .= " AND (u.nama LIKE :search OR u.nim LIKE :search OR u.email LIKE :search OR u.kelompok LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($filterStatus !== '') {
    if ($filterStatus === 'belum_absen') {
        $sql .= " AND a.status IS NULL";
    } else {
        $sql .= " AND a.status = :status";
        $params[':status'] = $filterStatus;
    }
}

$sql .= " ORDER BY u.nama ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listAbsensi = $stmt->fetchAll();

// Count Summary Stats for the selected date
$stmtStats = $pdo->prepare("
    SELECT 
        COUNT(u.id) AS total_mahasiswa,
        SUM(CASE WHEN a.status = 'hadir' THEN 1 ELSE 0 END) AS total_hadir,
        SUM(CASE WHEN a.status IN ('izin', 'sakit') THEN 1 ELSE 0 END) AS total_izin,
        SUM(CASE WHEN a.status = 'alpa' OR a.status IS NULL THEN 1 ELSE 0 END) AS total_belum_absen
    FROM users u
    LEFT JOIN absensi a ON u.id = a.user_id AND a.tanggal = ?
    WHERE u.role = 'mahasiswa' AND u.status = 'aktif'
");
$stmtStats->execute([$selectedTanggal]);
$stats = $stmtStats->fetch();

$csrfToken = generateCsrfToken();
$flash = getFlash();

require_once __DIR__ . '/../includes/header.php';
?>
<!-- Leaflet CSS for Map Viewer -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<main class="content-wrapper">
    <div class="page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <h1><i class="fa-solid fa-clipboard-user"></i> Kelola & Rekap Absensi</h1>
            <p>Pemantauan real-time presensi kehadiran mahasiswa KKN 43 dengan foto selfie dan verifikasi GPS.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= base_url('admin/cetak_absensi.php?mode=tanggal&tanggal=' . urlencode($selectedTanggal)) ?>"
               target="_blank"
               class="btn btn-danger">
                <i class="fa-solid fa-file-pdf me-1"></i> Export PDF Hari Ini
            </a>
            <a href="<?= base_url('admin/cetak_absensi.php') ?>"
               target="_blank"
               class="btn btn-outline-secondary">
                <i class="fa-solid fa-print me-1"></i> Cetak Kustom
            </a>
        </div>
    </div>


    <!-- Breadcrumb -->
    <div class="breadcrumb-bar">
        <a href="<?= base_url('admin/dashboard.php') ?>">Dashboard Admin</a>
        <i class="fa-solid fa-chevron-right"></i>
        <span>Absensi Mahasiswa</span>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show mb-4" role="alert">
            <i class="fa-solid fa-circle-info me-2"></i> <?= sanitize($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Summary Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-md-3">
            <div class="stat-card stat-blue">
                <div class="stat-card-icon">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-value"><?= (int)($stats['total_mahasiswa'] ?? 0) ?></div>
                    <div class="stat-card-label">Total Mahasiswa</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="stat-card stat-green">
                <div class="stat-card-icon">
                    <i class="fa-solid fa-user-check"></i>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-value"><?= (int)($stats['total_hadir'] ?? 0) ?></div>
                    <div class="stat-card-label">Hadir Hari Ini</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="stat-card stat-gold">
                <div class="stat-card-icon">
                    <i class="fa-solid fa-envelope-open-text"></i>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-value"><?= (int)($stats['total_izin'] ?? 0) ?></div>
                    <div class="stat-card-label">Izin / Sakit</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="stat-card stat-purple">
                <div class="stat-card-icon">
                    <i class="fa-solid fa-user-clock"></i>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-value"><?= (int)($stats['total_belum_absen'] ?? 0) ?></div>
                    <div class="stat-card-label">Belum Absen / Alpa</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pengaturan Jam Absensi Card -->
    <div class="card mb-4">
        <div class="card-header-custom">
            <h5><i class="fa-solid fa-clock me-2 text-primary"></i> Pengaturan Jam Absensi</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="absensi.php?tanggal=<?= urlencode($selectedTanggal) ?>" class="row g-3 align-items-end">
                <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                <input type="hidden" name="action" value="save_absensi_settings">

                <div class="col-12 col-md-3">
                    <label class="form-label">Absen Masuk Buka</label>
                    <input type="time" name="masuk_buka" class="form-control" value="<?= sanitize($absensiSettings['masuk_buka']['value'] ?? '06:00') ?>" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Absen Masuk Tutup</label>
                    <input type="time" name="masuk_tutup" class="form-control" value="<?= sanitize($absensiSettings['masuk_tutup']['value'] ?? '09:00') ?>" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Absen Pulang Buka</label>
                    <input type="time" name="pulang_buka" class="form-control" value="<?= sanitize($absensiSettings['pulang_buka']['value'] ?? '15:00') ?>" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">Absen Pulang Tutup</label>
                    <input type="time" name="pulang_tutup" class="form-control" value="<?= sanitize($absensiSettings['pulang_tutup']['value'] ?? '21:00') ?>" required>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">Status Absensi</label>
                    <select name="absensi_aktif" class="form-select">
                        <option value="1" <?= (($absensiSettings['absensi_aktif']['value'] ?? '1') === '1') ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= (($absensiSettings['absensi_aktif']['value'] ?? '1') === '0') ? 'selected' : '' ?>>Ditutup</option>
                    </select>
                </div>

                <div class="col-12 col-md-8 d-flex justify-content-md-end align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="absensi.php" class="row g-3 align-items-end">
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label">Tanggal Presensi</label>
                    <input type="date" name="tanggal" class="form-control" value="<?= sanitize($selectedTanggal) ?>">
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label">Cari Mahasiswa / NIM</label>
                    <input type="text" name="search" class="form-control" placeholder="Nama, NIM, atau Kelompok..." value="<?= sanitize($search) ?>">
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label">Filter Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="hadir" <?= $filterStatus === 'hadir' ? 'selected' : '' ?>>Hadir</option>
                        <option value="izin" <?= $filterStatus === 'izin' ? 'selected' : '' ?>>Izin</option>
                        <option value="sakit" <?= $filterStatus === 'sakit' ? 'selected' : '' ?>>Sakit</option>
                        <option value="alpa" <?= $filterStatus === 'alpa' ? 'selected' : '' ?>>Alpa</option>
                        <option value="belum_absen" <?= $filterStatus === 'belum_absen' ? 'selected' : '' ?>>Belum Absen</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-filter me-1"></i> Terapkan
                    </button>
                    <a href="absensi.php" class="btn btn-outline-secondary" title="Reset Filter">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table Absensi Card -->
    <div class="card">
        <div class="card-header-custom">
            <h5>
                <i class="fa-solid fa-clipboard-check me-2 text-primary"></i>
                Rekap Absensi Tanggal <?= date('d F Y', strtotime($selectedTanggal)) ?>
            </h5>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary"><?= count($listAbsensi) ?> Data Mahasiswa</span>
                <a href="<?= base_url('admin/cetak_absensi.php?mode=tanggal&tanggal=' . urlencode($selectedTanggal)) ?>"
                   target="_blank"
                   class="btn btn-sm btn-danger">
                    <i class="fa-solid fa-file-pdf me-1"></i> Export PDF
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width: 40px;">No</th>
                            <th>Mahasiswa</th>
                            <th>Status</th>
                            <th>Jam Masuk & Foto</th>
                            <th>Jam Pulang & Foto</th>
                            <th>Lokasi GPS</th>
                            <th class="text-end">Aksi Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($listAbsensi)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-folder-open fa-2x mb-2 d-block opacity-50"></i>
                                    Tidak ada data mahasiswa ditemukan untuk filter ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($listAbsensi as $idx => $mhs): ?>
                                <?php
                                $status = $mhs['status'];
                                $badgeClass = 'bg-secondary';
                                $statusText = 'Belum Absen';

                                if ($status === 'hadir') {
                                    $badgeClass = 'bg-success';
                                    $statusText = 'HADIR';
                                } elseif ($status === 'izin') {
                                    $badgeClass = 'bg-warning text-dark';
                                    $statusText = 'IZIN';
                                } elseif ($status === 'sakit') {
                                    $badgeClass = 'bg-warning text-dark';
                                    $statusText = 'SAKIT';
                                } elseif ($status === 'alpa') {
                                    $badgeClass = 'bg-danger';
                                    $statusText = 'ALPA';
                                }
                                ?>
                                <tr>
                                    <td><?= $idx + 1 ?></td>
                                    <td>
                                        <strong class="d-block text-dark"><?= sanitize($mhs['nama']) ?></strong>
                                        <small class="text-muted">NIM: <?= sanitize($mhs['nim'] ?: '-') ?> | <?= sanitize($mhs['kelompok'] ?: 'KKN 43') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?= $badgeClass ?>"><?= $statusText ?></span>
                                    </td>
                                    <td>
                                        <?php if ($status === 'hadir' && $mhs['jam_masuk']): ?>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($mhs['foto_masuk']): ?>
                                                    <img src="<?= base_url($mhs['foto_masuk']) ?>" alt="Foto Masuk" class="img-absensi-thumb btn-view-photo" data-photo="<?= base_url($mhs['foto_masuk']) ?>" data-title="Masuk: <?= sanitize($mhs['nama']) ?> (<?= date('H:i', strtotime($mhs['jam_masuk'])) ?> WIB)">
                                                <?php endif; ?>
                                                <div>
                                                    <strong class="d-block text-dark"><?= date('H:i', strtotime($mhs['jam_masuk'])) ?> WIB</strong>
                                                    <small class="text-muted">Presensi Masuk</small>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($status === 'hadir' && $mhs['jam_pulang']): ?>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($mhs['foto_pulang']): ?>
                                                    <img src="<?= base_url($mhs['foto_pulang']) ?>" alt="Foto Pulang" class="img-absensi-thumb btn-view-photo" data-photo="<?= base_url($mhs['foto_pulang']) ?>" data-title="Pulang: <?= sanitize($mhs['nama']) ?> (<?= date('H:i', strtotime($mhs['jam_pulang'])) ?> WIB)">
                                                <?php endif; ?>
                                                <div>
                                                    <strong class="d-block text-dark"><?= date('H:i', strtotime($mhs['jam_pulang'])) ?> WIB</strong>
                                                    <small class="text-muted">Presensi Pulang</small>
                                                </div>
                                            </div>
                                        <?php elseif ($status === 'hadir'): ?>
                                            <span class="badge bg-warning text-dark small"><i class="fa-solid fa-clock me-1"></i> Belum Pulang</span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($mhs['latitude_masuk']) && !empty($mhs['longitude_masuk'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-view-map"
                                                data-lat="<?= $mhs['latitude_masuk'] ?>"
                                                data-lng="<?= $mhs['longitude_masuk'] ?>"
                                                data-lokasi="<?= sanitize($mhs['lokasi_masuk'] ?: 'Lokasi GPS Absen Masuk') ?>"
                                                data-nama="<?= sanitize($mhs['nama']) ?>">
                                                <i class="fa-solid fa-location-dot text-danger me-1"></i> Cek Peta
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditStatus<?= $mhs['user_id'] ?>">
                                            <i class="fa-solid fa-pen-to-square me-1"></i> Edit Status
                                        </button>

                                        <!-- Modal Manual Edit Status -->
                                        <div class="modal fade text-start" id="modalEditStatus<?= $mhs['user_id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form method="POST" action="absensi.php?tanggal=<?= urlencode($selectedTanggal) ?>">
                                                        <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="user_id" value="<?= $mhs['user_id'] ?>">
                                                        
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"><i class="fa-solid fa-user-gear text-primary me-2"></i> Update Status Absensi</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="mb-3">
                                                                Ubah status presensi untuk <strong><?= sanitize($mhs['nama']) ?></strong> pada tanggal <strong><?= date('d/m/Y', strtotime($selectedTanggal)) ?></strong>.
                                                            </p>
                                                            <div class="mb-3">
                                                                <label class="form-label">Status Presensi</label>
                                                                <select name="status" class="form-select" required>
                                                                    <option value="hadir" <?= $status === 'hadir' ? 'selected' : '' ?>>Hadir</option>
                                                                    <option value="izin" <?= $status === 'izin' ? 'selected' : '' ?>>Izin</option>
                                                                    <option value="sakit" <?= $status === 'sakit' ? 'selected' : '' ?>>Sakit</option>
                                                                    <option value="alpa" <?= $status === 'alpa' ? 'selected' : '' ?>>Alpa</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Catatan / Keterangan Admin</label>
                                                                <textarea name="keterangan" class="form-control" rows="3" placeholder="Alasan perubahan atau catatan khusus..."><?= sanitize($mhs['keterangan'] ?? '') ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Modal Preview Foto Selfie -->
<div class="modal fade" id="modalFotoPreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalFotoTitle">Foto Absensi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-2">
                <img id="imgModalTarget" src="" alt="Foto Absensi" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>

<!-- Modal Map GPS Viewer -->
<div class="modal fade" id="modalMapViewer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalMapTitle"><i class="fa-solid fa-map-location-dot text-danger me-2"></i> Lokasi GPS Absensi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="modalMapAddress" class="small text-muted mb-2"></p>
                <div id="modalMapContainer" class="map-container-lg"></div>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet JS for Map Modal -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Foto Preview Modal Trigger
    const fotoModal = new bootstrap.Modal(document.getElementById('modalFotoPreview'));
    const imgTarget = document.getElementById('imgModalTarget');
    const titleTarget = document.getElementById('modalFotoTitle');

    document.querySelectorAll('.btn-view-photo').forEach(function(img) {
        img.addEventListener('click', function() {
            imgTarget.src = this.getAttribute('data-photo');
            titleTarget.innerText = this.getAttribute('data-title') || 'Foto Absensi';
            fotoModal.show();
        });
    });

    // Map Modal Trigger
    const mapModalElem = document.getElementById('modalMapViewer');
    const mapModal = new bootstrap.Modal(mapModalElem);
    const mapAddress = document.getElementById('modalMapAddress');
    let modalMapObj = null;
    let modalMarkerObj = null;

    document.querySelectorAll('.btn-view-map').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const lat = parseFloat(this.getAttribute('data-lat'));
            const lng = parseFloat(this.getAttribute('data-lng'));
            const lokasi = this.getAttribute('data-lokasi');
            const nama = this.getAttribute('data-nama');

            mapAddress.innerHTML = `<strong>Mahasiswa:</strong> ${nama} <br> <strong>Lokasi:</strong> ${lokasi} (Lat: ${lat}, Lng: ${lng})`;

            mapModal.show();

            mapModalElem.addEventListener('shown.bs.modal', function handler() {
                mapModalElem.removeEventListener('shown.bs.modal', handler);

                if (!modalMapObj) {
                    modalMapObj = L.map('modalMapContainer').setView([lat, lng], 16);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(modalMapObj);
                    modalMarkerObj = L.marker([lat, lng]).addTo(modalMapObj).bindPopup(`<b>${nama}</b><br>${lokasi}`).openPopup();
                } else {
                    modalMapObj.setView([lat, lng], 16);
                    modalMarkerObj.setLatLng([lat, lng]).bindPopup(`<b>${nama}</b><br>${lokasi}`).openPopup();
                }
                modalMapObj.invalidateSize();
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
