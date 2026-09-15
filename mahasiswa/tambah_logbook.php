<?php
/**
 * mahasiswa/tambah_logbook.php
 * -----------------------------------------------------
 * Form input logbook harian. Satu kali input = satu kegiatan.
 * Hari otomatis terisi berdasarkan tanggal yang dipilih (JS),
 * namun tetap divalidasi ulang di server.
 * -----------------------------------------------------
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole('mahasiswa');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi tidak valid, silakan muat ulang halaman.';
    } else {
        $tanggal     = $_POST['tanggal'] ?? '';
        $kegiatan    = trim($_POST['kegiatan'] ?? '');
        $lokasi      = trim($_POST['lokasi'] ?? '');
        $sasaran     = trim($_POST['sasaran'] ?? '');
        $koordinator = trim($_POST['koordinator'] ?? '');

        if ($tanggal === '' || $kegiatan === '' || $lokasi === '' || $sasaran === '' || $koordinator === '') {
            $error = 'Semua field wajib diisi.';
        } else {
            // Hitung nama hari dari tanggal (server-side, tidak percaya input JS)
            $namaHariIndo = [
                'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => "Jum'at", 'Saturday' => 'Sabtu'
            ];
            $hari = $namaHariIndo[date('l', strtotime($tanggal))] ?? '';

            $stmt = $pdo->prepare("INSERT INTO logbook (user_id, hari, tanggal, kegiatan, lokasi, sasaran, koordinator)
                                    VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $hari, $tanggal, $kegiatan, $lokasi, $sasaran, $koordinator]);

            setFlash('success', 'Logbook berhasil disimpan.');
            header('Location: riwayat_logbook.php');
            exit;
        }
    }
}

$pageTitle = 'Tambah Logbook';
$currentPage = 'tambah';
$csrfToken = generateCsrfToken();
include __DIR__ . '/../includes/header.php';
?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="content-wrapper">
    <h4 class="mb-3"><i class="fa-solid fa-plus"></i> Tambah Logbook</h4>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= sanitize($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="tambah_logbook.php">
                <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tanggal *</label>
                        <input type="date" name="tanggal" id="tanggal" class="form-control" required value="<?= sanitize($_POST['tanggal'] ?? date('Y-m-d')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Hari</label>
                        <input type="text" id="hari_display" class="form-control" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nama Koordinator Kegiatan *</label>
                        <input type="text" name="koordinator" class="form-control" required value="<?= sanitize($_POST['koordinator'] ?? '') ?>">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Nama Kegiatan *</label>
                        <textarea name="kegiatan" class="form-control" rows="3" required><?= sanitize($_POST['kegiatan'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Lokasi *</label>
                        <input type="text" name="lokasi" class="form-control" required value="<?= sanitize($_POST['lokasi'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sasaran *</label>
                        <input type="text" name="sasaran" class="form-control" required value="<?= sanitize($_POST['sasaran'] ?? '') ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-4">
                    <i class="fa-solid fa-save"></i> Simpan Logbook
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// Menampilkan nama hari otomatis berdasarkan tanggal yang dipilih (hanya tampilan, server tetap menghitung ulang)
const namaHari = ['Minggu','Senin','Selasa','Rabu','Kamis',"Jum'at",'Sabtu'];
function updateHari() {
    const tgl = document.getElementById('tanggal').value;
    if (tgl) {
        const d = new Date(tgl + 'T00:00:00');
        document.getElementById('hari_display').value = namaHari[d.getDay()];
    }
}
document.getElementById('tanggal').addEventListener('change', updateHari);
updateHari();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
