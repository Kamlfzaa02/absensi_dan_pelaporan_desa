<?php
/**
 * mahasiswa/cetak_logbook.php
 * -----------------------------------------------------
 * Halaman pilihan cetak: Semua logbook / Per bulan /
 * Rentang tanggal. Jika parameter GET 'mode' belum ada,
 * tampilkan form pilihan dulu. Jika sudah ada, langsung
 * tampilkan hasil cetak (memakai print_template.php).
 * -----------------------------------------------------
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole('mahasiswa');

$userId = $_SESSION['user_id'];
$mode = $_GET['mode'] ?? '';

// ----- Tahap 1: belum pilih mode -> tampilkan form pilihan -----
if ($mode === '') {
    $pageTitle = 'Cetak Logbook';
    $currentPage = 'cetak';
    include __DIR__ . '/../includes/header.php';
    ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-wrapper">
        <h4 class="mb-3"><i class="fa-solid fa-print"></i> Cetak Logbook</h4>
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabSemua">Semua Logbook</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabBulan">Per Bulan</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabRentang">Rentang Tanggal</a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tabSemua">
                        <p class="text-muted">Mencetak seluruh logbook yang pernah diisi.</p>
                        <a href="cetak_logbook.php?mode=semua" target="_blank" class="btn btn-primary">
                            <i class="fa-solid fa-print"></i> Cetak Semua
                        </a>
                    </div>
                    <div class="tab-pane fade" id="tabBulan">
                        <form method="GET" action="cetak_logbook.php" target="_blank" class="row g-2">
                            <input type="hidden" name="mode" value="bulan">
                            <div class="col-md-4">
                                <input type="month" name="bulan" class="form-control" value="<?= date('Y-m') ?>" required>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-primary"><i class="fa-solid fa-print"></i> Cetak</button>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tabRentang">
                        <form method="GET" action="cetak_logbook.php" target="_blank" class="row g-2">
                            <input type="hidden" name="mode" value="rentang">
                            <div class="col-md-3">
                                <input type="date" name="tanggal_awal" class="form-control" required>
                            </div>
                            <div class="col-md-1 align-self-center text-center">s/d</div>
                            <div class="col-md-3">
                                <input type="date" name="tanggal_akhir" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-primary"><i class="fa-solid fa-print"></i> Cetak</button>
                            </div>
                        </form>
                    </div>
                </div>
                <small class="text-muted d-block mt-3">
                    Tip: pada dialog cetak browser, pilih "Save as PDF" untuk menyimpan sebagai file PDF.
                </small>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <?php
    exit;
}

// ----- Tahap 2: mode sudah dipilih -> tampilkan halaman cetak -----
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$mahasiswa = $stmtUser->fetch();

$sql = "SELECT * FROM logbook WHERE user_id = ?";
$params = [$userId];

if ($mode === 'bulan') {
    $bulan = $_GET['bulan'] ?? date('Y-m');
    $sql .= " AND DATE_FORMAT(tanggal, '%Y-%m') = ?";
    $params[] = $bulan;
} elseif ($mode === 'rentang') {
    $awal = $_GET['tanggal_awal'] ?? '0000-01-01';
    $akhir = $_GET['tanggal_akhir'] ?? '9999-12-31';
    $sql .= " AND tanggal BETWEEN ? AND ?";
    $params[] = $awal;
    $params[] = $akhir;
}
// mode 'semua' -> tanpa filter tambahan

$sql .= " ORDER BY tanggal ASC, id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logbookData = $stmt->fetchAll();

$semester = 'GASAL 2026/2027';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Logbook - <?= sanitize($mahasiswa['nama']) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/print.css') ?>">
</head>
<body>
<div class="no-print p-3 text-center">
    <button onclick="window.print()" class="btn btn-primary"><i class="fa-solid fa-print"></i> Print / Save PDF</button>
</div>
<?php include __DIR__ . '/../includes/print_template.php'; ?>
</body>
</html>
