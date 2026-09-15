<?php
/**
 * admin/cetak.php
 * -----------------------------------------------------
 * Menghasilkan halaman cetak logbook sesuai filter dari
 * admin/laporan.php. Untuk filter "kelompok" & "bulan"/"rentang"
 * tanpa pilih mahasiswa spesifik, akan mencetak SEMUA mahasiswa
 * yang cocok, masing-masing dengan halamannya sendiri.
 * -----------------------------------------------------
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole('admin');

$filterType = $_GET['filter_type'] ?? 'mahasiswa';
$semester = 'GASAL 2026/2027';

// Tentukan daftar mahasiswa yang akan dicetak
if ($filterType === 'mahasiswa') {
    $userId = (int) ($_GET['user_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role='mahasiswa'");
    $stmt->execute([$userId]);
    $mahasiswaTarget = $stmt->fetchAll();
} elseif ($filterType === 'kelompok') {
    $kelompok = trim($_GET['kelompok'] ?? '');
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role='mahasiswa' AND kelompok = ? ORDER BY nama ASC");
    $stmt->execute([$kelompok]);
    $mahasiswaTarget = $stmt->fetchAll();
} else {
    // bulan / rentang -> cetak semua mahasiswa yang punya logbook di rentang tsb
    $mahasiswaTarget = $pdo->query("SELECT * FROM users WHERE role='mahasiswa' ORDER BY nama ASC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Logbook</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/print.css') ?>">
</head>
<body>
<div class="no-print p-3 text-center">
    <button onclick="window.print()" class="btn btn-primary"><i class="fa-solid fa-print"></i> Print / Save PDF</button>
</div>
<?php foreach ($mahasiswaTarget as $mIndex => $mahasiswa): ?>
    <?php
    // Bangun query logbook sesuai filter
    $sql = "SELECT * FROM logbook WHERE user_id = ?";
    $params = [$mahasiswa['id']];

    if ($filterType === 'bulan') {
        $bulan = $_GET['bulan'] ?? date('Y-m');
        $sql .= " AND DATE_FORMAT(tanggal, '%Y-%m') = ?";
        $params[] = $bulan;
    } elseif ($filterType === 'rentang') {
        $awal = $_GET['tanggal_awal'] ?? '0000-01-01';
        $akhir = $_GET['tanggal_akhir'] ?? '9999-12-31';
        $sql .= " AND tanggal BETWEEN ? AND ?";
        $params[] = $awal;
        $params[] = $akhir;
    }
    $sql .= " ORDER BY tanggal ASC, id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logbookData = $stmt->fetchAll();

    // Lewati mahasiswa tanpa logbook saat cetak massal per kelompok/bulan/rentang
    if ($filterType !== 'mahasiswa' && empty($logbookData)) {
        continue;
    }

    if ($mIndex > 0) {
        echo '<div class="page-break"></div>';
    }

    include __DIR__ . '/../includes/print_template.php';
    ?>
<?php endforeach; ?>
</body>
</html>
