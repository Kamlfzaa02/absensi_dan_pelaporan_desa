<?php
/**
 * includes/form_mahasiswa_fields.php
 * -----------------------------------------------------
 * Partial berisi field-field form mahasiswa, dipakai
 * bersama oleh modal Tambah & modal Edit (admin/mahasiswa.php)
 * agar tidak duplikasi kode. Variabel $m (array data lama,
 * kosong jika mode tambah) harus tersedia sebelum include.
 * -----------------------------------------------------
 */
?>
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">NIM</label>
        <input type="text" name="nim" class="form-control" value="<?= sanitize($m['nim'] ?? '') ?>">
    </div>
    <div class="col-md-8">
        <label class="form-label">Nama Lengkap *</label>
        <input type="text" name="nama" class="form-control" required value="<?= sanitize($m['nama'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label">Email *</label>
        <input type="email" name="email" class="form-control" required value="<?= sanitize($m['email'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label">Password <?= empty($m) ? '*' : '(kosongkan jika tidak diubah)' ?></label>
        <input type="password" name="password" class="form-control" <?= empty($m) ? 'required' : '' ?>>
    </div>
    <div class="col-md-6">
        <label class="form-label">Program Studi</label>
        <input type="text" name="prodi" class="form-control" value="<?= sanitize($m['prodi'] ?? '') ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label">Kelompok KKN</label>
        <input type="text" name="kelompok" class="form-control" value="<?= sanitize($m['kelompok'] ?? '') ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Desa/Kelurahan</label>
        <input type="text" name="desa" class="form-control" value="<?= sanitize($m['desa'] ?? '') ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Kecamatan</label>
        <input type="text" name="kecamatan" class="form-control" value="<?= sanitize($m['kecamatan'] ?? '') ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Kabupaten/Kota</label>
        <input type="text" name="kabupaten" class="form-control" value="<?= sanitize($m['kabupaten'] ?? '') ?>">
    </div>
    <div class="col-md-8">
        <label class="form-label">Nama DPL</label>
        <input type="text" name="dpl" class="form-control" value="<?= sanitize($m['dpl'] ?? '') ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Status Aktif</label>
        <select name="status" class="form-select">
            <option value="aktif" <?= (($m['status'] ?? 'aktif') === 'aktif') ? 'selected' : '' ?>>Aktif</option>
            <option value="nonaktif" <?= (($m['status'] ?? '') === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
        </select>
    </div>
</div>
