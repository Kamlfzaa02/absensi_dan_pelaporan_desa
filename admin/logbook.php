<?php
/**
 * admin/logbook.php
 * -----------------------------------------------------
 * Admin dapat melihat seluruh logbook mahasiswa,
 * mencari berdasarkan nama/kelompok/tanggal, melihat
 * detail (modal), dan menghapus logbook.
 * -----------------------------------------------------
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole('admin');

// ----- Hapus logbook -----
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM logbook WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'Logbook berhasil dihapus.');
    header('Location: logbook.php');
    exit;
}

// ----- Filter pencarian -----
$nama    = trim($_GET['nama'] ?? '');
$kelompok = trim($_GET['kelompok'] ?? '');
$tanggal = trim($_GET['tanggal'] ?? '');

$sql = "SELECT l.*, u.nama AS nama_mahasiswa, u.kelompok, u.nim
        FROM logbook l JOIN users u ON l.user_id = u.id
        WHERE 1=1";
$params = [];

if ($nama !== '') {
    $sql .= " AND u.nama LIKE ?";
    $params[] = "%$nama%";
}
if ($kelompok !== '') {
    $sql .= " AND u.kelompok LIKE ?";
    $params[] = "%$kelompok%";
}
if ($tanggal !== '') {
    $sql .= " AND l.tanggal = ?";
    $params[] = $tanggal;
}
$sql .= " ORDER BY l.tanggal DESC, l.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logbookList = $stmt->fetchAll();

$pageTitle = 'Kelola Logbook';
$currentPage = 'logbook';
$flash = getFlash();
include __DIR__ . '/../includes/header.php';
?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="content-wrapper">
    <h4 class="mb-3"><i class="fa-solid fa-book-open"></i> Kelola Logbook</h4>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> alert-auto-hide"><?= sanitize($flash['message']) ?></div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <input type="text" name="nama" class="form-control" placeholder="Nama mahasiswa" value="<?= sanitize($nama) ?>">
                </div>
                <div class="col-md-3">
                    <input type="text" name="kelompok" class="form-control" placeholder="Kelompok" value="<?= sanitize($kelompok) ?>">
                </div>
                <div class="col-md-3">
                    <input type="date" name="tanggal" class="form-control" value="<?= sanitize($tanggal) ?>">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100"><i class="fa-solid fa-search"></i> Cari</button>
                </div>
                <div class="col-md-1">
                    <a href="logbook.php" class="btn btn-outline-secondary w-100" title="Reset"><i class="fa-solid fa-rotate"></i></a>
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
                            <th>#</th><th>Tanggal</th><th>Mahasiswa</th><th>Kelompok</th>
                            <th>Kegiatan</th><th>Lokasi</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logbookList)): ?>
                            <tr><td colspan="7" class="text-center text-muted">Belum ada data logbook.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($logbookList as $i => $l): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= sanitize($l['hari'] . ', ' . date('d-m-Y', strtotime($l['tanggal']))) ?></td>
                            <td><?= sanitize($l['nama_mahasiswa']) ?></td>
                            <td><?= sanitize($l['kelompok'] ?? '-') ?></td>
                            <td><?= sanitize(mb_strimwidth($l['kegiatan'], 0, 40, '...')) ?></td>
                            <td><?= sanitize($l['lokasi']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#detail<?= $l['id'] ?>">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <a href="logbook.php?action=delete&id=<?= $l['id'] ?>" class="btn btn-sm btn-danger btn-delete-confirm">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Detail Logbook -->
    <?php foreach ($logbookList as $l): ?>
    <div class="modal fade" id="detail<?= $l['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Logbook</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-borderless mb-0">
                        <tr><th width="150">Mahasiswa</th><td>: <?= sanitize($l['nama_mahasiswa']) ?> (<?= sanitize($l['nim'] ?? '-') ?>)</td></tr>
                        <tr><th>Kelompok</th><td>: <?= sanitize($l['kelompok'] ?? '-') ?></td></tr>
                        <tr><th>Hari/Tanggal</th><td>: <?= sanitize($l['hari'] . ', ' . date('d-m-Y', strtotime($l['tanggal']))) ?></td></tr>
                        <tr><th>Kegiatan</th><td>: <?= sanitize($l['kegiatan']) ?></td></tr>
                        <tr><th>Lokasi</th><td>: <?= sanitize($l['lokasi']) ?></td></tr>
                        <tr><th>Sasaran</th><td>: <?= sanitize($l['sasaran']) ?></td></tr>
                        <tr><th>Koordinator</th><td>: <?= sanitize($l['koordinator']) ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
