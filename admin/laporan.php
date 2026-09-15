<?php
/**
 * admin/laporan.php
 * -----------------------------------------------------
 * Halaman filter laporan. Admin memilih mahasiswa/kelompok/
 * bulan/rentang tanggal, lalu klik "Cetak" yang membuka
 * admin/cetak.php di tab baru (siap print / save as PDF).
 * -----------------------------------------------------
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole('admin');

$mahasiswaList = $pdo->query("SELECT id, nama, nim, kelompok FROM users WHERE role='mahasiswa' ORDER BY nama ASC")->fetchAll();
$kelompokList = $pdo->query("SELECT DISTINCT kelompok FROM users WHERE role='mahasiswa' AND kelompok IS NOT NULL AND kelompok <> '' ORDER BY kelompok ASC")->fetchAll();

$pageTitle = 'Laporan / Cetak';
$currentPage = 'laporan';
include __DIR__ . '/../includes/header.php';
?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="content-wrapper">
    <h4 class="mb-3"><i class="fa-solid fa-print"></i> Laporan / Cetak Logbook</h4>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="cetak.php" target="_blank">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Filter Berdasarkan</label>
                        <select name="filter_type" id="filter_type" class="form-select" onchange="toggleFilter()">
                            <option value="mahasiswa">Per Mahasiswa</option>
                            <option value="kelompok">Per Kelompok</option>
                            <option value="bulan">Per Bulan</option>
                            <option value="rentang">Rentang Tanggal</option>
                        </select>
                    </div>

                    <div class="col-md-8 filter-box" id="box-mahasiswa">
                        <label class="form-label">Pilih Mahasiswa</label>
                        <select name="user_id" class="form-select">
                            <?php foreach ($mahasiswaList as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= sanitize($m['nama']) ?> (<?= sanitize($m['nim'] ?? '-') ?>) - <?= sanitize($m['kelompok'] ?? '-') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-8 filter-box d-none" id="box-kelompok">
                        <label class="form-label">Pilih Kelompok</label>
                        <select name="kelompok" class="form-select">
                            <?php foreach ($kelompokList as $k): ?>
                                <option value="<?= sanitize($k['kelompok']) ?>"><?= sanitize($k['kelompok']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-8 filter-box d-none" id="box-bulan">
                        <label class="form-label">Pilih Bulan</label>
                        <input type="month" name="bulan" class="form-control" value="<?= date('Y-m') ?>">
                    </div>

                    <div class="col-md-8 filter-box d-none" id="box-rentang">
                        <label class="form-label">Rentang Tanggal</label>
                        <div class="d-flex gap-2">
                            <input type="date" name="tanggal_awal" class="form-control">
                            <span class="align-self-center">s/d</span>
                            <input type="date" name="tanggal_akhir" class="form-control">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-4">
                    <i class="fa-solid fa-print"></i> Cetak / Simpan PDF
                </button>
                <small class="text-muted d-block mt-2">
                    Tip: pada dialog cetak browser, pilih "Save as PDF" untuk menyimpan sebagai file PDF.
                </small>
            </form>
        </div>
    </div>
</div>

<script>
function toggleFilter() {
    const type = document.getElementById('filter_type').value;
    document.querySelectorAll('.filter-box').forEach(el => el.classList.add('d-none'));
    document.getElementById('box-' + type).classList.remove('d-none');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
