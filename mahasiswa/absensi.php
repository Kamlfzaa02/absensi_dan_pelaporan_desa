<?php
/**
 * mahasiswa/absensi.php
 * -----------------------------------------------------
 * Halaman Absensi Harian Mahasiswa
 * Menggunakan Foto Muka (Camera Snapshot HTML5) +
 * GPS Geolocation & Peta Interaktif (Leaflet.js)
 * -----------------------------------------------------
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/absensi_helper.php';

requireRole('mahasiswa');

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');
$pageTitle = 'Absensi Harian Mahasiswa';
$currentPage = 'absensi';

ensureAbsensiTableExists($pdo);
$absensiHariIni = getAbsensiHariIni($pdo, $userId, $today);

// Load pengaturan jam absensi dari admin
$absensiSettings = getAbsensiSettings($pdo);
$jamMasukBuka    = $absensiSettings['masuk_buka']['value']   ?? '06:00';
$jamMasukTutup   = $absensiSettings['masuk_tutup']['value']  ?? '09:00';
$jamPulangBuka   = $absensiSettings['pulang_buka']['value']  ?? '15:00';
$jamPulangTutup  = $absensiSettings['pulang_tutup']['value'] ?? '21:00';
$absensiAktif    = ($absensiSettings['absensi_aktif']['value'] ?? '1') === '1';

$error = '';
$success = '';

// Process POST form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Sesi tidak valid, silakan muat ulang halaman.');
        header('Location: absensi.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'absen_masuk') {
        // Validasi jam absensi masuk
        $validasiMasuk = validateJamAbsensi('masuk', $absensiSettings);
        if (!$validasiMasuk['ok']) {
            setFlash('danger', $validasiMasuk['message']);
            header('Location: absensi.php');
            exit;
        }

        $fotoBase64 = $_POST['foto_base64'] ?? '';
        $lat = trim($_POST['latitude'] ?? '');
        $lng = trim($_POST['longitude'] ?? '');
        $lokasi = trim($_POST['lokasi'] ?? '');

        $fotoPath = null;
        if (!empty($fotoBase64)) {
            $fotoPath = saveBase64Image($fotoBase64, 'masuk_' . $userId);
        } elseif (isset($_FILES['foto_file'])) {
            $fotoPath = saveUploadedImage($_FILES['foto_file'], 'masuk_' . $userId);
        }

        if (!$fotoPath) {
            setFlash('danger', 'Gagal menyimak foto. Pastikan Anda mengizinkan akses kamera atau mengunggah foto selfie yang valid.');
            header('Location: absensi.php');
            exit;
        }

        $jamMasuk = date('H:i:s');

        if ($absensiHariIni) {
            // Update jika record sudah dibuat (misal tadinya alpa)
            $stmt = $pdo->prepare("UPDATE absensi SET jam_masuk = ?, foto_masuk = ?, latitude_masuk = ?, longitude_masuk = ?, lokasi_masuk = ?, status = 'hadir' WHERE user_id = ? AND tanggal = ?");
            $stmt->execute([$jamMasuk, $fotoPath, $lat, $lng, $lokasi, $userId, $today]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO absensi (user_id, tanggal, jam_masuk, foto_masuk, latitude_masuk, longitude_masuk, lokasi_masuk, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'hadir')");
            $stmt->execute([$userId, $today, $jamMasuk, $fotoPath, $lat, $lng, $lokasi]);
        }

        setFlash('success', 'Berhasil Absen Masuk pada jam ' . date('H:i') . ' WIB.');
        header('Location: absensi.php');
        exit;
    }

    if ($action === 'absen_pulang') {
        if (!$absensiHariIni || $absensiHariIni['status'] !== 'hadir') {
            setFlash('danger', 'Anda harus Absen Masuk terlebih dahulu.');
            header('Location: absensi.php');
            exit;
        }

        // Validasi jam absensi pulang
        $validasiPulang = validateJamAbsensi('pulang', $absensiSettings);
        if (!$validasiPulang['ok']) {
            setFlash('danger', $validasiPulang['message']);
            header('Location: absensi.php');
            exit;
        }

        $fotoBase64 = $_POST['foto_base64'] ?? '';
        $lat = trim($_POST['latitude'] ?? '');
        $lng = trim($_POST['longitude'] ?? '');
        $lokasi = trim($_POST['lokasi'] ?? '');

        $fotoPath = null;
        if (!empty($fotoBase64)) {
            $fotoPath = saveBase64Image($fotoBase64, 'pulang_' . $userId);
        } elseif (isset($_FILES['foto_file'])) {
            $fotoPath = saveUploadedImage($_FILES['foto_file'], 'pulang_' . $userId);
        }

        if (!$fotoPath) {
            setFlash('danger', 'Gagal menyimak foto pulang. Silakan coba lagi.');
            header('Location: absensi.php');
            exit;
        }

        $jamPulang = date('H:i:s');
        $stmt = $pdo->prepare("UPDATE absensi SET jam_pulang = ?, foto_pulang = ?, latitude_pulang = ?, longitude_pulang = ?, lokasi_pulang = ? WHERE user_id = ? AND tanggal = ?");
        $stmt->execute([$jamPulang, $fotoPath, $lat, $lng, $lokasi, $userId, $today]);

        setFlash('success', 'Berhasil Absen Pulang pada jam ' . date('H:i') . ' WIB.');
        header('Location: absensi.php');
        exit;
    }

    if ($action === 'absen_izin') {
        $statusIzin = $_POST['status_izin'] ?? 'izin';
        if (!in_array($statusIzin, ['izin', 'sakit'])) {
            $statusIzin = 'izin';
        }
        $keterangan = trim($_POST['keterangan'] ?? '');

        if ($keterangan === '') {
            setFlash('danger', 'Keterangan izin/sakit wajib diisi.');
            header('Location: absensi.php');
            exit;
        }

        if ($absensiHariIni) {
            $stmt = $pdo->prepare("UPDATE absensi SET status = ?, keterangan = ? WHERE user_id = ? AND tanggal = ?");
            $stmt->execute([$statusIzin, $keterangan, $userId, $today]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO absensi (user_id, tanggal, status, keterangan) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $today, $statusIzin, $keterangan]);
        }

        setFlash('success', 'Keterangan ' . ucfirst($statusIzin) . ' berhasil dikirim.');
        header('Location: absensi.php');
        exit;
    }
}

$csrfToken = generateCsrfToken();
$flash = getFlash();

require_once __DIR__ . '/../includes/header.php';
?>
<!-- Leaflet CSS for Maps -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<main class="content-wrapper">
    <div class="page-header">
        <h1><i class="fa-solid fa-camera-retro"></i> Absensi Harian</h1>
        <p>Presensi kehadiran KKN 43 dengan foto selfie dan verifikasi lokasi GPS realtime.</p>
    </div>

    <!-- Breadcrumb -->
    <div class="breadcrumb-bar">
        <a href="<?= base_url('mahasiswa/dashboard.php') ?>">Dashboard</a>
        <i class="fa-solid fa-chevron-right"></i>
        <span>Absensi</span>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-info me-2"></i> <?= sanitize($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Overview Status Absensi Hari Ini -->
    <div class="row g-4 mb-4">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="stat-card stat-blue">
                <div class="stat-card-icon">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-value" style="font-size: 1.1rem; font-weight: 700;">
                        <?= date('d F Y') ?>
                    </div>
                    <div class="stat-card-label">Hari / Tanggal Ini</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="stat-card stat-teal">
                <div class="stat-card-icon">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-value" style="font-size: 1.1rem; font-weight: 700;">
                        <?php if ($absensiHariIni && $absensiHariIni['jam_masuk']): ?>
                            <?= date('H:i', strtotime($absensiHariIni['jam_masuk'])) ?> WIB
                        <?php else: ?>
                            --:-- WIB
                        <?php endif; ?>
                    </div>
                    <div class="stat-card-label">Jam Masuk</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="stat-card stat-purple">
                <div class="stat-card-icon">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </div>
                <div class="stat-card-body">
                    <div class="stat-card-value" style="font-size: 1.1rem; font-weight: 700;">
                        <?php if ($absensiHariIni && $absensiHariIni['jam_pulang']): ?>
                            <?= date('H:i', strtotime($absensiHariIni['jam_pulang'])) ?> WIB
                        <?php else: ?>
                            --:-- WIB
                        <?php endif; ?>
                    </div>
                    <div class="stat-card-label">Jam Pulang</div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$absensiHariIni || ($absensiHariIni['status'] === 'hadir' && !$absensiHariIni['jam_pulang'])): ?>
        <!-- Form Absensi Kamera & GPS (Untuk Absen Masuk atau Absen Pulang) -->
        <?php $isAbsenMasuk = !$absensiHariIni || !$absensiHariIni['jam_masuk']; ?>

        <?php if (!$absensiAktif): ?>
            <!-- Absensi Dinonaktifkan Admin -->
            <div class="card mb-4 border-0" style="background: linear-gradient(135deg, #7f1d1d, #dc2626); color:#fff;">
                <div class="card-body p-4 text-center">
                    <i class="fa-solid fa-ban fa-3x mb-3 opacity-75"></i>
                    <h4 class="fw-bold mb-2">Absensi Sedang Ditutup</h4>
                    <p class="mb-0 opacity-75">Absensi saat ini dinonaktifkan oleh admin. Silakan hubungi DPL atau panitia KKN untuk informasi lebih lanjut.</p>
                </div>
            </div>
        <?php else: ?>

        <!-- Info Banner Jam Absensi -->
        <div class="card mb-4 border-0" style="background: linear-gradient(135deg, #0f2342, #1e5aa8); color:#fff;">
            <div class="card-body p-3 d-flex flex-wrap gap-3 align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2 rounded-circle" style="background:rgba(255,255,255,.15); font-size:1.3rem; width:46px; height:46px; display:flex; align-items:center; justify-content:center;">
                        <i class="fa-solid fa-clock-rotate-left text-warning"></i>
                    </div>
                    <div>
                        <small class="d-block" style="color:rgba(255,255,255,.6); font-size:.72rem; text-transform:uppercase; letter-spacing:.8px;">Jadwal Absensi Hari Ini</small>
                        <span class="fw-bold" style="font-size:.95rem;">
                            <i class="fa-solid fa-right-to-bracket me-1 text-success"></i> Masuk: <?= $jamMasukBuka ?> – <?= $jamMasukTutup ?> WIB
                            &nbsp;|&nbsp;
                            <i class="fa-solid fa-right-from-bracket me-1 text-warning"></i> Pulang: <?= $jamPulangBuka ?> – <?= $jamPulangTutup ?> WIB
                        </span>
                    </div>
                </div>
                <?php
                $jamNow = date('H:i');
                $jamAbsenStatus = '';
                if ($isAbsenMasuk) {
                    if ($jamNow < $jamMasukBuka) {
                        $jamAbsenStatus = '<span class="badge" style="background:rgba(255,193,7,.2); color:#ffc107; padding:7px 14px; font-size:.8rem;"><i class="fa-solid fa-hourglass-start me-1"></i> Absen Masuk Belum Buka</span>';
                    } elseif ($jamNow > $jamMasukTutup) {
                        $jamAbsenStatus = '<span class="badge" style="background:rgba(220,38,38,.25); color:#fca5a5; padding:7px 14px; font-size:.8rem;"><i class="fa-solid fa-xmark me-1"></i> Absen Masuk Ditutup</span>';
                    } else {
                        $jamAbsenStatus = '<span class="badge" style="background:rgba(21,128,61,.25); color:#86efac; padding:7px 14px; font-size:.8rem;"><i class="fa-solid fa-circle-check me-1"></i> Absen Masuk Terbuka</span>';
                    }
                } else {
                    if ($jamNow < $jamPulangBuka) {
                        $jamAbsenStatus = '<span class="badge" style="background:rgba(255,193,7,.2); color:#ffc107; padding:7px 14px; font-size:.8rem;"><i class="fa-solid fa-hourglass-start me-1"></i> Absen Pulang Belum Buka</span>';
                    } elseif ($jamNow > $jamPulangTutup) {
                        $jamAbsenStatus = '<span class="badge" style="background:rgba(220,38,38,.25); color:#fca5a5; padding:7px 14px; font-size:.8rem;"><i class="fa-solid fa-xmark me-1"></i> Absen Pulang Ditutup</span>';
                    } else {
                        $jamAbsenStatus = '<span class="badge" style="background:rgba(21,128,61,.25); color:#86efac; padding:7px 14px; font-size:.8rem;"><i class="fa-solid fa-circle-check me-1"></i> Absen Pulang Terbuka</span>';
                    }
                }
                echo $jamAbsenStatus;
                ?>
            </div>
        </div>

        <div class="row g-4">
            <!-- Col Left: Camera & Form Submission -->
            <div class="col-12 col-lg-7">
                <div class="card h-100">
                    <div class="card-header-custom">
                        <h5>
                            <i class="fa-solid fa-camera text-primary me-2"></i>
                            <?= $isAbsenMasuk ? 'Ambil Foto Muka (Absen Masuk)' : 'Ambil Foto Muka (Absen Pulang)' ?>
                        </h5>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalIzin">
                            <i class="fa-solid fa-envelope-open-text me-1"></i> Form Izin / Sakit
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Camera Stream Box -->
                        <div class="camera-box mb-3">
                            <video id="webcam" class="camera-video" autoplay playsinline></video>
                            <img id="photo-preview" class="camera-preview-img d-none" alt="Preview Foto Selfie">
                            <div class="camera-overlay">
                                <span class="camera-status-badge" id="camera-status">
                                    <i class="fa-solid fa-video me-1"></i> Kamera Aktif
                                </span>
                            </div>
                        </div>

                        <!-- Canvas tersembunyi untuk snapshot -->
                        <canvas id="canvas-snapshot" style="display:none;"></canvas>

                        <!-- Camera Action Buttons -->
                        <div class="d-flex justify-content-center gap-2 mb-4">
                            <button type="button" class="btn btn-primary" id="btn-take-photo">
                                <i class="fa-solid fa-camera me-1"></i> Ambil Foto Snapshot
                            </button>
                            <button type="button" class="btn btn-outline-secondary d-none" id="btn-retake-photo">
                                <i class="fa-solid fa-arrows-rotate me-1"></i> Foto Ulang
                            </button>
                        </div>

                        <!-- Fallback Input File jika Kamera Bermasalah -->
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="fa-solid fa-circle-question me-1"></i> Jika kamera tidak dapat diakses di perangkat Anda, Anda dapat memilih foto selfie dari galeri/file.
                            <input type="file" id="foto_file_input" name="foto_file" class="form-control form-control-sm mt-2" accept="image/*" capture="user">
                        </div>

                        <!-- Form Submit Payload -->
                        <form method="POST" action="absensi.php" id="form-absensi" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                            <input type="hidden" name="action" value="<?= $isAbsenMasuk ? 'absen_masuk' : 'absen_pulang' ?>">
                            <input type="hidden" name="foto_base64" id="foto_base64_input" value="">
                            <input type="hidden" name="latitude" id="latitude_input" value="">
                            <input type="hidden" name="longitude" id="longitude_input" value="">
                            <input type="hidden" name="lokasi" id="lokasi_input" value="">

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg" id="btn-submit-absensi">
                                    <i class="fa-solid fa-check-circle me-1"></i>
                                    <?= $isAbsenMasuk ? 'Kirim Absen Masuk Sekarang' : 'Kirim Absen Pulang Sekarang' ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Col Right: Location GPS Info & Leaflet Map -->
            <div class="col-12 col-lg-5">
                <div class="card h-100">
                    <div class="card-header-custom">
                        <h5><i class="fa-solid fa-location-dot text-danger me-2"></i> Verifikasi Lokasi GPS</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-refresh-gps">
                            <i class="fa-solid fa-location-crosshairs me-1"></i> Refresh GPS
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- GPS Status Info -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between p-3 rounded" style="background: var(--bg); border: 1px solid var(--border);">
                                <div>
                                    <small class="text-muted d-block uppercase" style="font-size: .7rem; font-weight: 700;">Status GPS</small>
                                    <strong id="gps-status-text" class="text-primary" style="font-size: .9rem;">
                                        <i class="fa-solid fa-spinner fa-spin me-1"></i> Mengambil koordinat lokasi...
                                    </strong>
                                </div>
                                <span class="badge bg-info" id="gps-accuracy-badge">GPS</span>
                            </div>
                        </div>

                        <!-- Coordinate Details -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="p-2 border rounded text-center">
                                    <small class="text-muted d-block" style="font-size: .7rem;">LATITUDE</small>
                                    <strong id="lat-display" class="font-monospace" style="font-size: .85rem;">-</strong>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 border rounded text-center">
                                    <small class="text-muted d-block" style="font-size: .7rem;">LONGITUDE</small>
                                    <strong id="lng-display" class="font-monospace" style="font-size: .85rem;">-</strong>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat / Lokasi Terdeteksi</label>
                            <textarea id="address-display" class="form-control form-control-sm" rows="2" readonly placeholder="Mencari alamat lokasi..."></textarea>
                        </div>

                        <!-- Map Preview Container -->
                        <div id="map-preview" class="map-container mb-2"></div>
                        <small class="text-muted d-block text-center" style="font-size: .75rem;">
                            <i class="fa-solid fa-circle-info me-1"></i> Titik pin di atas menunjukkan koordinat lokasi presisi Anda.
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    <?php elseif ($absensiHariIni['status'] === 'izin' || $absensiHariIni['status'] === 'sakit'): ?>
        <!-- Tampilan jika Status Izin / Sakit -->
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="mb-3">
                    <span class="badge bg-warning p-3 rounded-circle" style="font-size: 2rem;">
                        <i class="fa-solid fa-envelope-open-text"></i>
                    </span>
                </div>
                <h4 class="fw-bold text-dark mb-2">Status Hari Ini: <?= strtoupper($absensiHariIni['status']) ?></h4>
                <p class="text-muted mb-4" style="max-width: 500px; margin: 0 auto;">
                    Anda telah mengirimkan surat keterangan <strong><?= sanitize($absensiHariIni['status']) ?></strong> untuk hari ini.
                </p>
                <div class="p-3 border rounded text-start mx-auto" style="max-width: 500px; background: var(--bg);">
                    <small class="text-muted d-block uppercase fw-bold" style="font-size: .7rem;">KETERANGAN / ALASAN:</small>
                    <div class="mt-1 text-dark"><?= nl2br(sanitize($absensiHariIni['keterangan'] ?? '-')) ?></div>
                </div>
                <a href="<?= base_url('mahasiswa/riwayat_absensi.php') ?>" class="btn btn-outline-primary mt-4">
                    <i class="fa-solid fa-list-check me-1"></i> Lihat Riwayat Absensi
                </a>
            </div>
        </div>

    <?php else: ?>
        <!-- Tampilan jika Sudah Absen Masuk & Absen Pulang (Lengkap Hari Ini) -->
        <div class="card mb-4">
            <div class="card-body text-center py-4">
                <div class="mb-2">
                    <span class="badge bg-success p-3 rounded-circle" style="font-size: 2rem;">
                        <i class="fa-solid fa-circle-check"></i>
                    </span>
                </div>
                <h4 class="fw-bold text-dark mb-1">Absensi Hari Ini Selesai!</h4>
                <p class="text-muted mb-0">Terima kasih, Anda telah menyelesaikan Absen Masuk & Absen Pulang untuk hari ini.</p>
            </div>
        </div>

        <div class="row g-4">
            <!-- Data Absen Masuk -->
            <div class="col-12 col-md-6">
                <div class="card h-100">
                    <div class="card-header-custom">
                        <h5><i class="fa-solid fa-right-to-bracket text-success me-2"></i> Absen Masuk</h5>
                        <span class="badge bg-success"><?= date('H:i', strtotime($absensiHariIni['jam_masuk'])) ?> WIB</span>
                    </div>
                    <div class="card-body">
                        <?php if ($absensiHariIni['foto_masuk']): ?>
                            <div class="text-center mb-3">
                                <img src="<?= base_url($absensiHariIni['foto_masuk']) ?>" alt="Foto Masuk" class="img-fluid rounded border" style="max-height: 220px; object-fit: cover;">
                            </div>
                        <?php endif; ?>
                        <p class="small text-muted mb-1">
                            <i class="fa-solid fa-location-dot me-1 text-danger"></i> <strong>Lokasi:</strong> <?= sanitize($absensiHariIni['lokasi_masuk'] ?: 'Lokasi terrekam') ?>
                        </p>
                        <?php if ($absensiHariIni['latitude_masuk'] && $absensiHariIni['longitude_masuk']): ?>
                            <div id="map-masuk" class="map-container mt-2"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Data Absen Pulang -->
            <div class="col-12 col-md-6">
                <div class="card h-100">
                    <div class="card-header-custom">
                        <h5><i class="fa-solid fa-arrow-right-from-bracket text-purple me-2"></i> Absen Pulang</h5>
                        <span class="badge bg-purple"><?= date('H:i', strtotime($absensiHariIni['jam_pulang'])) ?> WIB</span>
                    </div>
                    <div class="card-body">
                        <?php if ($absensiHariIni['foto_pulang']): ?>
                            <div class="text-center mb-3">
                                <img src="<?= base_url($absensiHariIni['foto_pulang']) ?>" alt="Foto Pulang" class="img-fluid rounded border" style="max-height: 220px; object-fit: cover;">
                            </div>
                        <?php endif; ?>
                        <p class="small text-muted mb-1">
                            <i class="fa-solid fa-location-dot me-1 text-danger"></i> <strong>Lokasi:</strong> <?= sanitize($absensiHariIni['lokasi_pulang'] ?: 'Lokasi terrekam') ?>
                        </p>
                        <?php if ($absensiHariIni['latitude_pulang'] && $absensiHariIni['longitude_pulang']): ?>
                            <div id="map-pulang" class="map-container mt-2"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</main>

<!-- Modal Input Form Izin / Sakit -->
<div class="modal fade" id="modalIzin" tabindex="-1" aria-labelledby="modalIzinLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="absensi.php">
                <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                <input type="hidden" name="action" value="absen_izin">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalIzinLabel"><i class="fa-solid fa-envelope-open-text text-primary me-2"></i> Form Izin / Sakit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Jenis Permohonan</label>
                        <select name="status_izin" class="form-select" required>
                            <option value="izin">Izin (Ada Keperluan / Acara)</option>
                            <option value="sakit">Sakit (Kondisi Kesehatan)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan / Alasan Lengkap</label>
                        <textarea name="keterangan" class="form-control" rows="4" required placeholder="Jelaskan alasan izin / sakit Anda..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane me-1"></i> Kirim Surat Izin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Leaflet JS for Map -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const video = document.getElementById('webcam');
    const photoPreview = document.getElementById('photo-preview');
    const canvas = document.getElementById('canvas-snapshot');
    const btnTakePhoto = document.getElementById('btn-take-photo');
    const btnRetakePhoto = document.getElementById('btn-retake-photo');
    const fotoBase64Input = document.getElementById('foto_base64_input');
    const fotoFileInput = document.getElementById('foto_file_input');
    const cameraStatus = document.getElementById('camera-status');
    const btnSubmit = document.getElementById('btn-submit-absensi');

    let stream = null;

    // Start Webcam Stream
    if (video) {
        navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'user',
                width: { ideal: 640 },
                height: { ideal: 480 }
            }
        }).then(function(s) {
            stream = s;
            video.srcObject = stream;
        }).catch(function(err) {
            console.warn('Gagal akses kamera:', err);
            if (cameraStatus) {
                cameraStatus.className = 'camera-status-badge bg-danger';
                cameraStatus.innerHTML = '<i class="fa-solid fa-video-slash me-1"></i> Kamera Tidak Aktif';
            }
        });
    }

    // Take Photo Snapshot
    if (btnTakePhoto) {
        btnTakePhoto.addEventListener('click', function() {
            if (!video || !video.videoWidth) {
                alert('Stream kamera belum siap. Gunakan opsi unggah foto file jika kamera tidak tampil.');
                return;
            }
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
            fotoBase64Input.value = dataUrl;

            // Display preview
            photoPreview.src = dataUrl;
            photoPreview.classList.remove('d-none');
            video.classList.add('d-none');

            btnTakePhoto.classList.add('d-none');
            btnRetakePhoto.classList.remove('d-none');
        });
    }

    // Retake Photo
    if (btnRetakePhoto) {
        btnRetakePhoto.addEventListener('click', function() {
            fotoBase64Input.value = '';
            photoPreview.classList.add('d-none');
            video.classList.remove('d-none');

            btnRetakePhoto.classList.add('d-none');
            btnTakePhoto.classList.remove('d-none');
        });
    }

    // File input preview fallback
    if (fotoFileInput) {
        fotoFileInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    photoPreview.src = evt.target.result;
                    photoPreview.classList.remove('d-none');
                    if (video) video.classList.add('d-none');
                    fotoBase64Input.value = ''; // Mengutamakan file input
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    }

    // GPS Geolocation Handler
    let map = null;
    let marker = null;

    const latInput = document.getElementById('latitude_input');
    const lngInput = document.getElementById('longitude_input');
    const lokasiInput = document.getElementById('lokasi_input');
    const latDisplay = document.getElementById('lat-display');
    const lngDisplay = document.getElementById('lng-display');
    const addressDisplay = document.getElementById('address-display');
    const gpsStatusText = document.getElementById('gps-status-text');
    const gpsAccuracyBadge = document.getElementById('gps-accuracy-badge');

    function initMap(lat, lng) {
        const mapElem = document.getElementById('map-preview');
        if (!mapElem) return;

        if (!map) {
            map = L.map('map-preview').setView([lat, lng], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(map);

            marker = L.marker([lat, lng]).addTo(map)
                .bindPopup('Lokasi Presensi Anda').openPopup();
        } else {
            map.setView([lat, lng], 16);
            marker.setLatLng([lat, lng]);
        }
    }

    function getGPSLocation() {
        if (!navigator.geolocation) {
            if (gpsStatusText) gpsStatusText.innerHTML = '<span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> Geolocation tidak didukung browser ini.</span>';
            return;
        }

        if (gpsStatusText) {
            gpsStatusText.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Mengambil lokasi GPS...';
        }

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude.toFixed(6);
                const lng = position.coords.longitude.toFixed(6);
                const accuracy = Math.round(position.coords.accuracy);

                if (latInput) latInput.value = lat;
                if (lngInput) lngInput.value = lng;
                if (latDisplay) latDisplay.innerText = lat;
                if (lngDisplay) lngDisplay.innerText = lng;

                if (gpsStatusText) {
                    gpsStatusText.innerHTML = '<span class="text-success"><i class="fa-solid fa-location-dot me-1"></i> Lokasi GPS Terdeteksi</span>';
                }

                if (gpsAccuracyBadge) {
                    gpsAccuracyBadge.innerText = 'Akurasi: ±' + accuracy + 'm';
                    gpsAccuracyBadge.className = accuracy <= 50 ? 'badge bg-success' : 'badge bg-warning';
                }

                initMap(lat, lng);

                // Reverse Geocoding via Nominatim OpenStreetMap
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.display_name) {
                            if (addressDisplay) addressDisplay.value = data.display_name;
                            if (lokasiInput) lokasiInput.value = data.display_name;
                        } else {
                            const simpleLoc = `Lat: ${lat}, Lng: ${lng}`;
                            if (addressDisplay) addressDisplay.value = simpleLoc;
                            if (lokasiInput) lokasiInput.value = simpleLoc;
                        }
                    })
                    .catch(() => {
                        const simpleLoc = `Lat: ${lat}, Lng: ${lng}`;
                        if (addressDisplay) addressDisplay.value = simpleLoc;
                        if (lokasiInput) lokasiInput.value = simpleLoc;
                    });
            },
            function(error) {
                console.warn('Geolocation Error:', error);
                let errDesc = 'Gagal mengambil GPS.';
                if (error.code === error.PERMISSION_DENIED) errDesc = 'Izin lokasi ditolak pengguna.';
                else if (error.code === error.POSITION_UNAVAILABLE) errDesc = 'Sinyal lokasi tidak tersedia.';
                else if (error.code === error.TIMEOUT) errDesc = 'Waktu pengambilan lokasi habis.';

                if (gpsStatusText) {
                    gpsStatusText.innerHTML = `<span class="text-danger"><i class="fa-solid fa-circle-exclamation me-1"></i> ${errDesc}</span>`;
                }
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    }

    // Trigger GPS initially
    getGPSLocation();

    const btnRefreshGps = document.getElementById('btn-refresh-gps');
    if (btnRefreshGps) {
        btnRefreshGps.addEventListener('click', getGPSLocation);
    }

    // Render maps for completed attendance views (if present)
    const mapMasukContainer = document.getElementById('map-masuk');
    <?php if ($absensiHariIni && $absensiHariIni['latitude_masuk'] && $absensiHariIni['longitude_masuk']): ?>
        if (mapMasukContainer) {
            const m1 = L.map('map-masuk').setView([<?= $absensiHariIni['latitude_masuk'] ?>, <?= $absensiHariIni['longitude_masuk'] ?>], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(m1);
            L.marker([<?= $absensiHariIni['latitude_masuk'] ?>, <?= $absensiHariIni['longitude_masuk'] ?>]).addTo(m1).bindPopup('Lokasi Masuk').openPopup();
        }
    <?php endif; ?>

    const mapPulangContainer = document.getElementById('map-pulang');
    <?php if ($absensiHariIni && $absensiHariIni['latitude_pulang'] && $absensiHariIni['longitude_pulang']): ?>
        if (mapPulangContainer) {
            const m2 = L.map('map-pulang').setView([<?= $absensiHariIni['latitude_pulang'] ?>, <?= $absensiHariIni['longitude_pulang'] ?>], 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(m2);
            L.marker([<?= $absensiHariIni['latitude_pulang'] ?>, <?= $absensiHariIni['longitude_pulang'] ?>]).addTo(m2).bindPopup('Lokasi Pulang').openPopup();
        }
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
