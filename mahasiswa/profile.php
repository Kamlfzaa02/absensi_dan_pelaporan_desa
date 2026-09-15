<?php
/**
 * mahasiswa/profile.php
 * -----------------------------------------------------
 * Mahasiswa dapat melihat & memperbarui data profil
 * sendiri (kecuali NIM, email, kelompok - dikelola admin
 * agar konsisten dengan data resmi). Password bisa diganti.
 * -----------------------------------------------------
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole('mahasiswa');

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$profil = $stmt->fetch();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi tidak valid, silakan muat ulang halaman.';
    } else {
        $passwordBaru = $_POST['password_baru'] ?? '';
        $passwordKonfirmasi = $_POST['password_konfirmasi'] ?? '';

        if ($passwordBaru !== '') {
            if ($passwordBaru !== $passwordKonfirmasi) {
                $error = 'Konfirmasi password tidak sama.';
            } elseif (strlen($passwordBaru) < 6) {
                $error = 'Password minimal 6 karakter.';
            } else {
                $hash = password_hash($passwordBaru, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->execute([$hash, $userId]);
                setFlash('success', 'Password berhasil diperbarui.');
                header('Location: profile.php');
                exit;
            }
        } else {
            $error = 'Isi password baru untuk mengganti password.';
        }
    }
}

$pageTitle = 'Profil';
$currentPage = 'profile';
$flash = getFlash();
$csrfToken = generateCsrfToken();
include __DIR__ . '/../includes/header.php';
?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="content-wrapper">
    <h4 class="mb-3"><i class="fa-solid fa-id-card"></i> Profil Saya</h4>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> alert-auto-hide"><?= sanitize($flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= sanitize($error) ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-3">Data Diri (dikelola oleh Admin)</h6>
                    <table class="table table-borderless mb-0">
                        <tr><th width="150">NIM</th><td>: <?= sanitize($profil['nim'] ?? '-') ?></td></tr>
                        <tr><th>Nama</th><td>: <?= sanitize($profil['nama']) ?></td></tr>
                        <tr><th>Email</th><td>: <?= sanitize($profil['email']) ?></td></tr>
                        <tr><th>Prodi</th><td>: <?= sanitize($profil['prodi'] ?? '-') ?></td></tr>
                        <tr><th>Kelompok</th><td>: <?= sanitize($profil['kelompok'] ?? '-') ?></td></tr>
                        <tr><th>Desa</th><td>: <?= sanitize($profil['desa'] ?? '-') ?></td></tr>
                        <tr><th>Kecamatan</th><td>: <?= sanitize($profil['kecamatan'] ?? '-') ?></td></tr>
                        <tr><th>Kabupaten</th><td>: <?= sanitize($profil['kabupaten'] ?? '-') ?></td></tr>
                        <tr><th>DPL</th><td>: <?= sanitize($profil['dpl'] ?? '-') ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-3">Ganti Password</h6>
                    <form method="POST" action="profile.php">
                        <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="password_baru" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" name="password_konfirmasi" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-key"></i> Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
