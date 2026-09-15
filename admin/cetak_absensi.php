<?php
/**
 * admin/cetak_absensi.php
 * -----------------------------------------------------
 * Halaman Cetak / Export PDF Rekap Absensi Mahasiswa KKN.
 * Menggunakan metode browser Print > Save as PDF (tanpa library PHP eksternal),
 * konsisten dengan cara cetak logbook di project ini.
 * -----------------------------------------------------
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/absensi_helper.php';
requireRole('admin');

ensureAbsensiTableExists($pdo);

// Parameter filter dari URL
$filterMode = $_GET['mode'] ?? 'tanggal';  // 'tanggal' | 'bulan' | 'rentang'
$selectedTanggal  = $_GET['tanggal']  ?? date('Y-m-d');
$selectedBulan    = $_GET['bulan']    ?? date('Y-m');
$tanggalAwal      = $_GET['tgl_awal'] ?? date('Y-m-01');
$tanggalAkhir     = $_GET['tgl_akhir'] ?? date('Y-m-d');
$filterUserId     = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// --- Build Query ---
$sql = "
    SELECT 
        u.id AS user_id,
        u.nama,
        u.nim,
        u.kelompok,
        u.prodi,
        COALESCE(a.tanggal, DATE(:tgl_ref)) AS tanggal,
        a.jam_masuk,
        a.jam_pulang,
        COALESCE(a.status, 'alpa') AS status,
        a.keterangan,
        a.lokasi_masuk,
        a.lokasi_pulang,
        a.foto_masuk,
        a.foto_pulang
    FROM users u
";

$namaBulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
    '04' => 'April',   '05' => 'Mei',       '06' => 'Juni',
    '07' => 'Juli',    '08' => 'Agustus',   '09' => 'September',
    '10' => 'Oktober', '11' => 'November',  '12' => 'Desember'
];

// ——— Mode: Per Tanggal (1 hari) ———
if ($filterMode === 'tanggal') {
    $displayTitle  = 'Rekap Absensi Tanggal ' . date('d F Y', strtotime($selectedTanggal));
    $displaySub    = '';

    $sql .= "LEFT JOIN absensi a ON u.id = a.user_id AND a.tanggal = :tgl
             WHERE u.role = 'mahasiswa' AND u.status = 'aktif'";

    if ($filterUserId > 0) {
        $sql .= " AND u.id = :uid";
    }
    $sql .= " ORDER BY u.nama ASC";

    $params = [':tgl' => $selectedTanggal, ':tgl_ref' => $selectedTanggal];
    if ($filterUserId > 0) $params[':uid'] = $filterUserId;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Build tabel: 1 tabel dengan banyak mahasiswa sebagai baris
    $isMultiRow = true;
    $isMultiDate = false;

// ——— Mode: Per Bulan ———
} elseif ($filterMode === 'bulan') {
    $bulanNum = date('m', strtotime($selectedBulan . '-01'));
    $tahunNum = date('Y', strtotime($selectedBulan . '-01'));
    $displayTitle = 'Rekap Absensi Bulan ' . ($namaBulan[$bulanNum] ?? $bulanNum) . ' ' . $tahunNum;
    $displaySub   = '';

    $sql = "
        SELECT 
            a.tanggal,
            u.id AS user_id,
            u.nama,
            u.nim,
            u.kelompok,
            COALESCE(a.status, 'alpa') AS status,
            a.jam_masuk,
            a.jam_pulang,
            a.keterangan,
            a.lokasi_masuk,
            a.foto_masuk,
            a.foto_pulang
        FROM absensi a
        JOIN users u ON u.id = a.user_id
        WHERE u.role = 'mahasiswa'
          AND DATE_FORMAT(a.tanggal, '%Y-%m') = :bulan
    ";

    $params = [':bulan' => $selectedBulan];

    if ($filterUserId > 0) {
        $sql .= " AND u.id = :uid";
        $params[':uid'] = $filterUserId;
    }
    $sql .= " ORDER BY a.tanggal ASC, u.nama ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $isMultiRow  = false;
    $isMultiDate = true;

// ——— Mode: Rentang Tanggal ———
} else {
    $displayTitle = 'Rekap Absensi Periode ' . date('d F Y', strtotime($tanggalAwal)) . ' s.d. ' . date('d F Y', strtotime($tanggalAkhir));
    $displaySub   = '';

    $sql = "
        SELECT 
            a.tanggal,
            u.id AS user_id,
            u.nama,
            u.nim,
            u.kelompok,
            COALESCE(a.status, 'alpa') AS status,
            a.jam_masuk,
            a.jam_pulang,
            a.keterangan,
            a.lokasi_masuk,
            a.foto_masuk,
            a.foto_pulang
        FROM absensi a
        JOIN users u ON u.id = a.user_id
        WHERE u.role = 'mahasiswa'
          AND a.tanggal BETWEEN :awal AND :akhir
    ";

    $params = [':awal' => $tanggalAwal, ':akhir' => $tanggalAkhir];

    if ($filterUserId > 0) {
        $sql .= " AND u.id = :uid";
        $params[':uid'] = $filterUserId;
    }
    $sql .= " ORDER BY a.tanggal ASC, u.nama ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $isMultiRow  = false;
    $isMultiDate = true;
}

// Hitung Statistik Ringkasan
$totalHadir = 0;
$totalIzin  = 0;
$totalSakit = 0;
$totalAlpa  = 0;
foreach ($rows as $r) {
    if ($r['status'] === 'hadir')       $totalHadir++;
    elseif ($r['status'] === 'izin')    $totalIzin++;
    elseif ($r['status'] === 'sakit')   $totalSakit++;
    else                                $totalAlpa++;
}
$totalRows = count($rows);

// Ambil info lembaga
$namaLembaga = 'Universitas PGRI Semarang';
$namaKKN = 'KKN 43 Desa Taman Sari Mrangen';

// Daftar semua mahasiswa untuk dropdown filter
$listMhs = $pdo->query("SELECT id, nama, nim FROM users WHERE role='mahasiswa' AND status='aktif' ORDER BY nama ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Rekap Absensi | KKN 43</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* =============================================
           SCREEN STYLES (before print)
           ============================================= */
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Outfit', 'Segoe UI', Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            margin: 0;
            font-size: 14px;
        }

        /* ---- Kontrol Bar (tidak tercetak) ---- */
        .no-print {
            background: #0f2342;
            padding: 16px 28px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 12px rgba(0,0,0,.18);
        }
        .no-print h6 {
            color: rgba(255,255,255,.8);
            font-size: 0.8rem;
            font-weight: 600;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .no-print strong { color: #fff; font-size: 1rem; }
        .no-print .controls { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }

        /* ---- Filter Bar ---- */
        .filter-bar {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 14px 28px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: flex-end;
        }
        .filter-bar label { font-size: .75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .4px; display: block; margin-bottom: 4px; }
        .filter-bar input, .filter-bar select {
            font-family: inherit;
            font-size: .85rem;
            border: 1px solid #e2e8f0;
            border-radius: 7px;
            padding: 7px 11px;
            background: #f8fafc;
            color: #1e293b;
            outline: none;
        }
        .filter-bar input:focus, .filter-bar select:focus { border-color: #3b82f6; }

        /* ---- Tombol ---- */
        .btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 18px; border-radius: 8px; font-family: inherit;
            font-size: .875rem; font-weight: 600; cursor: pointer;
            border: none; transition: all .2s;
        }
        .btn-primary { background: #1e5aa8; color: #fff; }
        .btn-primary:hover { background: #1a4a8a; }
        .btn-success { background: #15803d; color: #fff; }
        .btn-success:hover { background: #166534; }
        .btn-outline { background: transparent; color: rgba(255,255,255,.8); border: 1px solid rgba(255,255,255,.25); }
        .btn-outline:hover { background: rgba(255,255,255,.1); color: #fff; }
        .btn-sm { padding: 6px 12px; font-size: .8rem; }

        /* ---- Halaman Cetak (kertas) ---- */
        .print-page {
            background: #fff;
            margin: 18px auto;
            max-width: 760px;
            padding: 24px 28px;
            box-shadow: 0 3px 18px rgba(0,0,0,.08);
            border-radius: 8px;
        }

        /* ---- Header Dokumen ---- */
        .doc-header {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            border-bottom: 2px solid #0f2342;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .doc-header img { width: 70px; height: 70px; object-fit: contain; }
        .doc-header-text { flex: 1; }
        .doc-header-text .lembaga {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #64748b;
            font-weight: 600;
        }
        .doc-header-text h1 {
            font-size: 1.1rem;
            font-weight: 800;
            color: #0f2342;
            margin: 4px 0 2px;
            line-height: 1.3;
        }
        .doc-header-text .sub-title {
            font-size: .8rem;
            color: #1e5aa8;
            font-weight: 600;
        }
        .doc-stamp {
            text-align: right;
            font-size: .72rem;
            color: #94a3b8;
            line-height: 1.6;
        }

        /* ---- Ringkasan Statistik ---- */
        .stats-row {
            display: flex;
            gap: 10px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }
        .stat-box {
            flex: 1; min-width: 76px;
            border-radius: 8px;
            padding: 8px 10px;
            text-align: center;
        }
        .stat-box .val { font-size: 1.15rem; font-weight: 800; line-height: 1; }
        .stat-box .lbl { font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; margin-top: 3px; }
        .stat-total { background: #eff6ff; color: #1e5aa8; }
        .stat-hadir { background: #f0fdf4; color: #15803d; }
        .stat-izin  { background: #fefce8; color: #854d0e; }
        .stat-sakit { background: #fff7ed; color: #9a3412; }
        .stat-alpa  { background: #fef2f2; color: #dc2626; }

        /* ---- Tabel Absensi ---- */
        .abs-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .75rem;
        }
        .abs-table th {
            background: #0f2342;
            color: #fff;
            font-size: .68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            padding: 9px 10px;
            text-align: left;
            white-space: nowrap;
        }
        .abs-table th:first-child { border-radius: 0; }
        .abs-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: #1e293b;
        }
        .abs-table tbody tr:nth-child(even) td { background: #f8fafc; }
        .abs-table tbody tr:hover td { background: #eff6ff; }
        .abs-table .no-data td {
            text-align: center;
            color: #94a3b8;
            padding: 24px;
            font-style: italic;
        }

        /* Status Badges */
        .badge {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: .67rem;
            font-weight: 700;
            letter-spacing: .3px;
            text-transform: uppercase;
        }
        .badge-hadir  { background: #dcfce7; color: #166534; }
        .badge-izin   { background: #fef9c3; color: #854d0e; }
        .badge-sakit  { background: #ffedd5; color: #9a3412; }
        .badge-alpa   { background: #fee2e2; color: #991b1b; }

        /* Foto Thumbnail */
        .foto-thumb {
            width: 30px;
            height: 30px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid #e2e8f0;
            display: block;
        }

        .note-footer {
            margin-top: 14px;
            font-size: .7rem;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            text-align: center;
        }

        /* =============================================
           PRINT STYLES
           ============================================= */
        @media print {
            @page {
                size: A4 landscape;
                margin: 15mm 14mm;
            }
            body {
                background: #fff;
                font-size: 11px;
            }
            .no-print, .filter-bar { display: none !important; }
            .print-page {
                margin: 0;
                max-width: 100%;
                padding: 4mm 6mm;
                box-shadow: none;
                border-radius: 0;
            }
            .abs-table { font-size: .72rem; }
            .abs-table th { font-size: .62rem; padding: 5px 6px; }
            .abs-table td { padding: 5px 6px; }
            .stats-row { gap: 6px; margin-bottom: 10px; }
            .stat-box { padding: 6px 8px; }
            .stat-box .val { font-size: 1rem; }
            .foto-thumb { width: 28px; height: 28px; }
        }
    </style>
</head>
<body>

<!-- Kontrol Bar (tidak tercetak) -->
<div class="no-print">
    <div>
        <h6>Preview Cetak Rekap Absensi</h6>
        <strong>KKN 43 Desa Taman Sari Mrangen</strong>
    </div>
    <div class="controls">
        <a href="absensi.php" class="btn btn-outline">
            ← Kembali ke Absensi
        </a>
        <button onclick="window.print()" class="btn btn-success">
            🖨️ Print / Simpan PDF
        </button>
    </div>
</div>

<!-- Filter Bar (tidak tercetak) -->
<div class="filter-bar no-print">
    <form method="GET" action="cetak_absensi.php" style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end; width:100%;">

        <div>
            <label>Mode Cetak</label>
            <select name="mode" onchange="this.form.submit()" style="min-width:140px;">
                <option value="tanggal"  <?= $filterMode === 'tanggal'  ? 'selected' : '' ?>>Per Tanggal</option>
                <option value="bulan"    <?= $filterMode === 'bulan'    ? 'selected' : '' ?>>Per Bulan</option>
                <option value="rentang"  <?= $filterMode === 'rentang'  ? 'selected' : '' ?>>Rentang Tanggal</option>
            </select>
        </div>

        <?php if ($filterMode === 'tanggal'): ?>
            <div>
                <label>Pilih Tanggal</label>
                <input type="date" name="tanggal" value="<?= htmlspecialchars($selectedTanggal) ?>">
            </div>
        <?php elseif ($filterMode === 'bulan'): ?>
            <div>
                <label>Pilih Bulan</label>
                <input type="month" name="bulan" value="<?= htmlspecialchars($selectedBulan) ?>">
            </div>
        <?php else: ?>
            <div>
                <label>Tanggal Awal</label>
                <input type="date" name="tgl_awal" value="<?= htmlspecialchars($tanggalAwal) ?>">
            </div>
            <div>
                <label>Tanggal Akhir</label>
                <input type="date" name="tgl_akhir" value="<?= htmlspecialchars($tanggalAkhir) ?>">
            </div>
        <?php endif; ?>

        <div>
            <label>Mahasiswa (Opsional)</label>
            <select name="user_id" style="min-width:180px;">
                <option value="0">Semua Mahasiswa</option>
                <?php foreach ($listMhs as $mhs): ?>
                    <option value="<?= $mhs['id'] ?>" <?= $filterUserId === (int)$mhs['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($mhs['nama']) ?> (<?= htmlspecialchars($mhs['nim'] ?: '-') ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <button type="submit" class="btn btn-primary btn-sm">🔍 Terapkan Filter</button>
        </div>
    </form>
</div>

<!-- ===================================================
     HALAMAN YANG AKAN DICETAK
     =================================================== -->
<div class="print-page">

    <!-- Header Dokumen Resmi -->
    <div class="doc-header">
        <img src="<?= base_url('assets/images/logo.png') ?>" alt="Logo KKN" onerror="this.style.display='none'">
        <div class="doc-header-text">
            <div class="lembaga"><?= $namaLembaga ?></div>
            <h1>REKAP ABSENSI KEHADIRAN</h1>
            <div class="sub-title"><?= $namaKKN ?></div>
        </div>
        <div class="doc-stamp">
            <strong><?= $displayTitle ?></strong><br>
            Dicetak: <?= date('d/m/Y H:i') ?> WIB<br>
            Total Rekap: <strong><?= $totalRows ?> baris data</strong>
        </div>
    </div>

    <!-- Ringkasan Statistik -->
    <div class="stats-row">
        <div class="stat-box stat-total">
            <div class="val"><?= $totalRows ?></div>
            <div class="lbl">Total</div>
        </div>
        <div class="stat-box stat-hadir">
            <div class="val"><?= $totalHadir ?></div>
            <div class="lbl">Hadir</div>
        </div>
        <div class="stat-box stat-izin">
            <div class="val"><?= $totalIzin ?></div>
            <div class="lbl">Izin</div>
        </div>
        <div class="stat-box stat-sakit">
            <div class="val"><?= $totalSakit ?></div>
            <div class="lbl">Sakit</div>
        </div>
        <div class="stat-box stat-alpa">
            <div class="val"><?= $totalAlpa ?></div>
            <div class="lbl">Alpa</div>
        </div>
    </div>

    <!-- Tabel Rekap Absensi -->
    <table class="abs-table">
        <thead>
            <tr>
                <th style="width:36px;">#</th>
                <?php if ($isMultiDate): ?>
                    <th>Tanggal</th>
                <?php endif; ?>
                <th>Nama</th>
                <th>NIM</th>
                <th>Kelompok</th>
                <th>Status</th>
                <th>Masuk</th>
                <th>Pulang</th>
                <th>Lokasi</th>
                <th>Foto</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr class="no-data">
                    <td colspan="<?= $isMultiDate ? '11' : '11' ?>">
                        Tidak ada data absensi yang ditemukan untuk filter yang dipilih.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $i => $r): ?>
                    <tr>
                        <td style="color:#94a3b8; font-weight:600;"><?= $i + 1 ?></td>
                        <?php if ($isMultiDate): ?>
                            <td style="white-space:nowrap; font-weight:600;">
                                <?= date('d/m/Y', strtotime($r['tanggal'])) ?><br>
                                <small style="color:#94a3b8; font-size:.65rem;"><?= date('l', strtotime($r['tanggal'])) ?></small>
                            </td>
                        <?php endif; ?>
                        <td style="font-weight:600;"><?= htmlspecialchars($r['nama']) ?></td>
                        <td style="font-size:.78rem; color:#64748b;"><?= htmlspecialchars($r['nim'] ?: '-') ?></td>
                        <td style="font-size:.78rem;"><?= htmlspecialchars($r['kelompok'] ?: '-') ?></td>
                        <td>
                            <?php
                            $s = $r['status'];
                            $badgeMap = [
                                'hadir'  => 'badge-hadir',
                                'izin'   => 'badge-izin',
                                'sakit'  => 'badge-sakit',
                                'alpa'   => 'badge-alpa',
                            ];
                            $cls = $badgeMap[$s] ?? 'badge-alpa';
                            ?>
                            <span class="badge <?= $cls ?>"><?= strtoupper($s) ?></span>
                        </td>
                        <td style="white-space:nowrap;">
                            <?= $r['jam_masuk'] ? date('H:i', strtotime($r['jam_masuk'])) . ' WIB' : '<span style="color:#94a3b8;">-</span>' ?>
                        </td>
                        <td style="white-space:nowrap;">
                            <?= $r['jam_pulang'] ? date('H:i', strtotime($r['jam_pulang'])) . ' WIB' : '<span style="color:#94a3b8;">-</span>' ?>
                        </td>
                        <td style="font-size:.72rem; max-width:180px;">
                            <?php $lokasiText = $r['lokasi_masuk'] ?? null; ?>
                            <?= $lokasiText ? htmlspecialchars(mb_strimwidth($lokasiText, 0, 70, '…')) : '<span style="color:#94a3b8;">-</span>' ?>
                        </td>
                        <td style="text-align:center;">
                            <?php if (!empty($r['foto_masuk'])): ?>
                                <img src="<?= base_url($r['foto_masuk']) ?>" alt="Foto" class="foto-thumb" onerror="this.style.display='none'">
                            <?php else: ?>
                                <span style="color:#94a3b8;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:.72rem; max-width:140px; color:#64748b;">
                            <?= !empty($r['keterangan']) ? htmlspecialchars(mb_strimwidth($r['keterangan'], 0, 45, '…')) : '-' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="note-footer">
        Dokumen ini digenerate secara otomatis oleh Sistem Logbook <?= $namaKKN ?> · <?= date('d F Y H:i') ?> WIB
    </div>

</div>

<!-- Script: Auto print jika ada parameter ?autoprint=1 -->
<script>
    if (window.location.search.includes('autoprint=1')) {
        window.addEventListener('load', () => setTimeout(() => window.print(), 500));
    }
</script>

</body>
</html>
