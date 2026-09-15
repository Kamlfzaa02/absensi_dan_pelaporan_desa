<?php
/**
 * mahasiswa/riwayat_logbook.php
 * -----------------------------------------------------
 * Menampilkan seluruh logbook milik mahasiswa yang login.
 * Bisa cari berdasarkan tanggal & filter bulan, serta
 * tombol edit/hapus per baris.
 * -----------------------------------------------------
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole('mahasiswa');

$userId = $_SESSION['user_id'];
$tanggal = trim($_GET['tanggal'] ?? '');
$bulan   = trim($_GET['bulan'] ?? '');

$sql = "SELECT * FROM logbook WHERE user_id = ?";
$params = [$userId];

if ($tanggal !== '') {
    $sql .= " AND tanggal = ?";
    $params[] = $tanggal;
}
if ($bulan !== '') {
    $sql .= " AND DATE_FORMAT(tanggal, '%Y-%m') = ?";
    $params[] = $bulan;
}
$sql .= " ORDER BY tanggal DESC, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logbookList = $stmt->fetchAll();

$pageTitle = 'Riwayat Logbook';
$currentPage = 'riwayat';
$flash = getFlash();
include __DIR__ . '/../includes/header.php';
?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="content-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fa-solid fa-clock-rotate-left"></i> Riwayat Logbook</h4>
        <a href="tambah_logbook.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Tambah Logbook</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> alert-auto-hide"><?= sanitize($flash['message']) ?></div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <input type="date" name="tanggal" class="form-control" value="<?= sanitize($tanggal) ?>">
                </div>
                <div class="col-md-3">
                    <input type="month" name="bulan" class="form-control" value="<?= sanitize($bulan) ?>">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100"><i class="fa-solid fa-search"></i> Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="riwayat_logbook.php" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th><th>Hari/Tanggal</th><th>Kegiatan</th><th>Lokasi</th>
                            <th>Sasaran</th><th>Koordinator</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logbookList)): ?>
                            <tr><td colspan="7" class="text-center text-muted">Belum ada logbook.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($logbookList as $i => $l): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= sanitize($l['hari'] . ', ' . date('d-m-Y', strtotime($l['tanggal']))) ?></td>
                            <td><?= sanitize($l['kegiatan']) ?></td>
                            <td><?= sanitize($l['lokasi']) ?></td>
                            <td><?= sanitize($l['sasaran']) ?></td>
                            <td><?= sanitize($l['koordinator']) ?></td>
                            <td>
                                <a href="edit_logbook.php?id=<?= $l['id'] ?>" class="btn btn-sm btn-warning"><i class="fa-solid fa-pen"></i></a>
                                <a href="hapus_logbook.php?id=<?= $l['id'] ?>" class="btn btn-sm btn-danger btn-delete-confirm"><i class="fa-solid fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
