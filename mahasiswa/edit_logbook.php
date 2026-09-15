<?php
/**
 * mahasiswa/edit_logbook.php
 * -----------------------------------------------------
 * Form edit logbook milik mahasiswa yang sedang login.
 * Mahasiswa hanya bisa edit logbook miliknya sendiri
 * (dicek lewat WHERE user_id = session).
 * -----------------------------------------------------
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole('mahasiswa');

$id = (int) ($_GET['id'] ?? 0);
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM logbook WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $userId]);
$logbook = $stmt->fetch();

if (!$logbook) {
    setFlash('error', 'Logbook tidak ditemukan.');
    header('Location: riwayat_logbook.php');
    exit;
}

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
            $namaHariIndo = [
                'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => "Jum'at", 'Saturday' => 'Sabtu'
            ];
            $hari = $namaHariIndo[date('l', strtotime($tanggal))] ?? '';

            $stmt = $pdo->prepare("UPDATE logbook SET hari=?, tanggal=?, kegiatan=?, lokasi=?, sasaran=?, koordinator=? WHERE id=? AND user_id=?");
            $stmt->execute([$hari, $tanggal, $kegiatan, $lokasi, $sasaran, $koordinator, $id, $userId]);

            setFlash('success', 'Logbook berhasil diperbarui.');
            header('Location: riwayat_logbook.php');
            exit;
        }
    }
}

$pageTitle = 'Edit Logbook';
$currentPage = 'riwayat';
$csrfToken = generateCsrfToken();
include __DIR__ . '/../includes/header.php';
?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="content-wrapper">
    <h4 class="mb-3"><i class="fa-solid fa-pen"></i> Edit Logbook</h4>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= sanitize($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="edit_logbook.php?id=<?= $logbook['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tanggal *</label>
                        <input type="date" name="tanggal" id="tanggal" class="form-control" required value="<?= sanitize($logbook['tanggal']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Hari</label>
                        <input type="text" id="hari_display" class="form-control" readonly value="<?= sanitize($logbook['hari']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nama Koordinator Kegiatan *</label>
                        <input type="text" name="koordinator" class="form-control" required value="<?= sanitize($logbook['koordinator']) ?>">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Nama Kegiatan *</label>
                        <textarea name="kegiatan" class="form-control" rows="3" required><?= sanitize($logbook['kegiatan']) ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Lokasi *</label>
                        <input type="text" name="lokasi" class="form-control" required value="<?= sanitize($logbook['lokasi']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Sasaran *</label>
                        <input type="text" name="sasaran" class="form-control" required value="<?= sanitize($logbook['sasaran']) ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-4">
                    <i class="fa-solid fa-save"></i> Simpan Perubahan
                </button>
                <a href="riwayat_logbook.php" class="btn btn-secondary mt-4">Batal</a>
            </form>
        </div>
    </div>
</div>

<script>
const namaHari = ['Minggu','Senin','Selasa','Rabu','Kamis',"Jum'at",'Sabtu'];
document.getElementById('tanggal').addEventListener('change', function () {
    const d = new Date(this.value + 'T00:00:00');
    document.getElementById('hari_display').value = namaHari[d.getDay()];
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
