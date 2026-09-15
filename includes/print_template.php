<?php
/**
 * includes/print_template.php
 * -----------------------------------------------------
 * Template cetak logbook resmi KKN (dipakai oleh
 * mahasiswa/cetak_logbook.php dan admin/cetak.php).
 *
 * Variabel yang HARUS disediakan sebelum include:
 *   $mahasiswa   -> array data user (desa, kecamatan, kabupaten, dpl, dst)
 *   $logbookData -> array daftar logbook (No urut otomatis)
 *   $semester    -> string, contoh "GASAL 2026/2027"
 *
 * Setiap 30 baris otomatis pindah halaman baru (page-break),
 * masing-masing halaman punya header tabel sendiri.
 * -----------------------------------------------------
 */
$rowsPerPage = 30;
$chunks = array_chunk($logbookData, $rowsPerPage);
if (empty($chunks)) {
    $chunks = [[]]; // tetap tampilkan 1 halaman kosong jika belum ada data
}
$tanggalCetak = date('d F Y');
?>
<div class="print-area">
<?php foreach ($chunks as $pageIndex => $rows): ?>
    <div class="print-sheet <?= $pageIndex > 0 ? 'page-break' : '' ?>">
        <div class="print-header">
            <h1>REKAM KEGIATAN INDIVIDU MAHASISWA KKN</h1>
            <h2>SEMESTER <?= sanitize($semester) ?></h2>
            <h3>UNIVERSITAS PGRI SEMARANG</h3>
        </div>

        <div class="print-identitas">
            <table>
                <tr><td class="label">DESA/KELURAHAN</td><td class="colon">:</td><td><?= sanitize($mahasiswa['desa'] ?? '-') ?></td></tr>
                <tr><td class="label">KECAMATAN</td><td class="colon">:</td><td><?= sanitize($mahasiswa['kecamatan'] ?? '-') ?></td></tr>
                <tr><td class="label">KAB/KOTA</td><td class="colon">:</td><td><?= sanitize($mahasiswa['kabupaten'] ?? '-') ?></td></tr>
                <tr><td class="label">D P L</td><td class="colon">:</td><td><?= sanitize($mahasiswa['dpl'] ?? '-') ?></td></tr>
            </table>
        </div>

        <table class="print-table">
            <thead>
                <tr>
                    <th width="4%">No</th>
                    <th width="12%">Hari/Tgl</th>
                    <th width="28%">KEGIATAN</th>
                    <th width="16%">LOKASI</th>
                    <th width="16%">SASARAN</th>
                    <th width="24%">NAMA KOORDINATOR KEGIATAN</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = $pageIndex * $rowsPerPage + 1;
                foreach ($rows as $r):
                ?>
                <tr>
                    <td class="center"><?= $no++ ?></td>
                    <td><?= sanitize($r['hari'] . ', ' . date('d-m-Y', strtotime($r['tanggal']))) ?></td>
                    <td><?= sanitize($r['kegiatan']) ?></td>
                    <td><?= sanitize($r['lokasi']) ?></td>
                    <td><?= sanitize($r['sasaran']) ?></td>
                    <td><?= sanitize($r['koordinator']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php
                // Isi baris kosong agar tabel tetap rapi sampai kelipatan tertentu (opsional, dilewati agar cetak ringkas)
                ?>
            </tbody>
        </table>

        <?php if ($pageIndex === count($chunks) - 1): ?>
            <div class="print-date"><?= sanitize($tanggalCetak) ?></div>
            <div class="print-footer">
                <div class="sign-block">
                    <div>Mengetahui</div>
                    <div>DPL,</div>
                    <div class="sign-space"></div>
                    <div>(......................)</div>
                </div>
                <div class="sign-block">
                    <div>&nbsp;</div>
                    <div>Mahasiswa KKN,</div>
                    <div class="sign-space"></div>
                    <div>(......................)</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>
