<?php
/**
 * mahasiswa/riwayat_absensi.php
 * -----------------------------------------------------
 * Halaman Riwayat Absensi Mahasiswa
 * Menampilkan daftar kehadiran harian, foto selfie,
 * jam masuk/pulang, dan titik koordinat GPS.
 * -----------------------------------------------------
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/absensi_helper.php';

requireRole('mahasiswa');

$userId = $_SESSION['user_id'];
$pageTitle = 'Riwayat Absensi Saya';
$currentPage = 'riwayat_absensi';

ensureAbsensiTableExists($pdo);

// Filter Bulan & Tahun
$selectedBulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$selectedTahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

// Query data absensi mahasiswa
$stmt = $pdo->prepare("
    SELECT * FROM absensi 
    WHERE user_id = ? 
      AND MONTH(tanggal) = ? 
      AND YEAR(tanggal) = ? 
    ORDER BY tanggal DESC
");
$stmt->execute([$userId, $selectedBulan, $selectedTahun]);
$absensiList = $stmt->fetchAll();

// Array Nama Bulan
$namaBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

require_once __DIR__ . '/../includes/header.php';
?>
<!-- Leaflet CSS for Map Modal -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

<main class="content-wrapper">
    <div class="page-header">
        <h1><i class="fa-solid fa-calendar-check"></i> Riwayat Absensi Saya</h1>
        <p>Rekap riwayat kehadiran harian, jam presensi, foto selfie, dan verifikasi lokasi GPS.</p>
    </div>

    <!-- Breadcrumb -->
    <div class="breadcrumb-bar">
        <a href="<?= base_url('mahasiswa/dashboard.php') ?>">Dashboard</a>
        <i class="fa-solid fa-chevron-right"></i>
        <span>Riwayat Absensi</span>
    </div>

    <!-- Filter Card -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="riwayat_absensi.php" class="row g-3 align-items-end">
                <div class="col-12 col-sm-5 col-md-4">
                    <label class="form-label">Pilih Bulan</label>
                    <select name="bulan" class="form-select">
                        <?php foreach ($namaBulan as $num => $name): ?>
                            <option value="<?= $num ?>" <?= $num === $selectedBulan ? 'selected' : '' ?>>
                                <?= $name ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-sm-4 col-md-3">
                    <label class="form-label">Pilih Tahun</label>
                    <select name="tahun" class="form-select">
                        <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                            <option value="<?= $y ?>" <?= $y === $selectedTahun ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-12 col-sm-3 col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-filter me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card">
        <div class="card-header-custom">
            <h5>
                <i class="fa-solid fa-list-ul me-2"></i>
                Data Presensi (<?= $namaBulan[$selectedBulan] ?> <?= $selectedTahun ?>)
            </h5>
            <span class="badge bg-primary"><?= count($absensiList) ?> Hari Terrekam</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Absen Masuk</th>
                            <th>Absen Pulang</th>
                            <th>Lokasi GPS</th>
                            <th class="text-end">Aksi / Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($absensiList)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                    Belum ada data absensi untuk bulan <?= $namaBulan[$selectedBulan] ?> <?= $selectedTahun ?>.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($absensiList as $index => $row): ?>
                                <?php
                                $statusBadgeClass = 'bg-secondary';
                                if ($row['status'] === 'hadir') $statusBadgeClass = 'bg-success';
                                elseif (in_array($row['status'], ['izin', 'sakit'])) $statusBadgeClass = 'bg-warning';
                                elseif ($row['status'] === 'alpa') $statusBadgeClass = 'bg-danger';
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <strong><?= date('d/m/Y', strtotime($row['tanggal'])) ?></strong>
                                        <small class="d-block text-muted"><?= date('l', strtotime($row['tanggal'])) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?= $statusBadgeClass ?>">
                                            <?= strtoupper($row['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] === 'hadir' && $row['jam_masuk']): ?>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($row['foto_masuk']): ?>
                                                    <img src="<?= base_url($row['foto_masuk']) ?>" alt="Foto Masuk" class="img-absensi-thumb btn-view-photo" data-photo="<?= base_url($row['foto_masuk']) ?>" data-title="Foto Absen Masuk - <?= date('d/m/Y', strtotime($row['tanggal'])) ?>">
                                                <?php endif; ?>
                                                <div>
                                                    <strong class="d-block"><?= date('H:i', strtotime($row['jam_masuk'])) ?> WIB</strong>
                                                    <small class="text-muted">Masuk</small>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] === 'hadir' && $row['jam_pulang']): ?>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($row['foto_pulang']): ?>
                                                    <img src="<?= base_url($row['foto_pulang']) ?>" alt="Foto Pulang" class="img-absensi-thumb btn-view-photo" data-photo="<?= base_url($row['foto_pulang']) ?>" data-title="Foto Absen Pulang - <?= date('d/m/Y', strtotime($row['tanggal'])) ?>">
                                                <?php endif; ?>
                                                <div>
                                                    <strong class="d-block"><?= date('H:i', strtotime($row['jam_pulang'])) ?> WIB</strong>
                                                    <small class="text-muted">Pulang</small>
                                                </div>
                                            </div>
                                        <?php elseif ($row['status'] === 'hadir'): ?>
                                            <span class="badge bg-warning text-dark"><i class="fa-solid fa-clock me-1"></i> Belum Pulang</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['lokasi_masuk']) || !empty($row['latitude_masuk'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-view-map" 
                                                data-lat="<?= $row['latitude_masuk'] ?>" 
                                                data-lng="<?= $row['longitude_masuk'] ?>" 
                                                data-lokasi="<?= sanitize($row['lokasi_masuk'] ?: 'Titik GPS Presensi') ?>"
                                                data-tanggal="<?= date('d/m/Y', strtotime($row['tanggal'])) ?>">
                                                <i class="fa-solid fa-location-dot text-danger me-1"></i> Lihat Peta
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if (in_array($row['status'], ['izin', 'sakit'])): ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalKeterangan<?= $row['id'] ?>">
                                                <i class="fa-solid fa-circle-info me-1"></i> Ket.
                                            </button>

                                            <!-- Modal Detail Keterangan Izin/Sakit -->
                                            <div class="modal fade text-start" id="modalKeterangan<?= $row['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Keterangan <?= ucfirst($row['status']) ?> (<?= date('d/m/Y', strtotime($row['tanggal'])) ?>)</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p><?= nl2br(sanitize($row['keterangan'] ?: 'Tidak ada keterangan tambahan.')) ?></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark">Lengkap</span>
                                        <?php endif; ?>
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
            const tgl = this.getAttribute('data-tanggal');

            mapAddress.innerHTML = `<strong>Tanggal ${tgl}:</strong> ${lokasi} (Lat: ${lat}, Lng: ${lng})`;

            mapModal.show();

            mapModalElem.addEventListener('shown.bs.modal', function handler() {
                mapModalElem.removeEventListener('shown.bs.modal', handler);

                if (!modalMapObj) {
                    modalMapObj = L.map('modalMapContainer').setView([lat, lng], 16);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(modalMapObj);
                    modalMarkerObj = L.marker([lat, lng]).addTo(modalMapObj).bindPopup(lokasi).openPopup();
                } else {
                    modalMapObj.setView([lat, lng], 16);
                    modalMarkerObj.setLatLng([lat, lng]).bindPopup(lokasi).openPopup();
                }
                modalMapObj.invalidateSize();
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
