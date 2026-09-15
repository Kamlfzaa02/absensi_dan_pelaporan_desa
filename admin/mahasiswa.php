<?php
/**
 * admin/mahasiswa.php
 * -----------------------------------------------------
 * CRUD Mahasiswa (dalam satu file menggunakan modal Bootstrap).
 * Aksi ditentukan lewat parameter GET ?action= (untuk delete)
 * dan POST hidden field 'form_action' untuk tambah/edit.
 * -----------------------------------------------------
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole('admin');

// ----- Proses Tambah / Edit (POST) -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('error', 'Sesi tidak valid, silakan coba lagi.');
        header('Location: mahasiswa.php');
        exit;
    }

    $formAction = $_POST['form_action'] ?? '';
    $nim        = trim($_POST['nim'] ?? '');
    $nama       = trim($_POST['nama'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $prodi      = trim($_POST['prodi'] ?? '');
    $kelompok   = trim($_POST['kelompok'] ?? '');
    $desa       = trim($_POST['desa'] ?? '');
    $kecamatan  = trim($_POST['kecamatan'] ?? '');
    $kabupaten  = trim($_POST['kabupaten'] ?? '');
    $dpl        = trim($_POST['dpl'] ?? '');
    $status     = $_POST['status'] ?? 'aktif';

    if ($nama === '' || $email === '') {
        setFlash('error', 'Nama dan Email wajib diisi.');
        header('Location: mahasiswa.php');
        exit;
    }

    if ($formAction === 'tambah') {
        $password = $_POST['password'] ?? '';
        if ($password === '') {
            setFlash('error', 'Password wajib diisi untuk mahasiswa baru.');
            header('Location: mahasiswa.php');
            exit;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Cek email duplikat
        $cek = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $cek->execute([$email]);
        if ($cek->fetch()) {
            setFlash('error', 'Email sudah digunakan.');
            header('Location: mahasiswa.php');
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO users (nim, nama, email, password, prodi, kelompok, desa, kecamatan, kabupaten, dpl, role, status)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'mahasiswa', ?)");
        $stmt->execute([$nim, $nama, $email, $hash, $prodi, $kelompok, $desa, $kecamatan, $kabupaten, $dpl, $status]);
        setFlash('success', 'Data mahasiswa berhasil ditambahkan.');
    } elseif ($formAction === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);

        // Jika password diisi, update juga passwordnya
        $password = $_POST['password'] ?? '';
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET nim=?, nama=?, email=?, password=?, prodi=?, kelompok=?, desa=?, kecamatan=?, kabupaten=?, dpl=?, status=? WHERE id=? AND role='mahasiswa'");
            $stmt->execute([$nim, $nama, $email, $hash, $prodi, $kelompok, $desa, $kecamatan, $kabupaten, $dpl, $status, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET nim=?, nama=?, email=?, prodi=?, kelompok=?, desa=?, kecamatan=?, kabupaten=?, dpl=?, status=? WHERE id=? AND role='mahasiswa'");
            $stmt->execute([$nim, $nama, $email, $prodi, $kelompok, $desa, $kecamatan, $kabupaten, $dpl, $status, $id]);
        }
        setFlash('success', 'Data mahasiswa berhasil diperbarui.');
    }

    header('Location: mahasiswa.php');
    exit;
}

// ----- Proses Hapus (GET ?action=delete&id=) -----
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'mahasiswa'");
    $stmt->execute([$id]);
    setFlash('success', 'Data mahasiswa berhasil dihapus.');
    header('Location: mahasiswa.php');
    exit;
}

// ----- Ambil data untuk ditampilkan -----
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role='mahasiswa' AND (nama LIKE ? OR nim LIKE ? OR kelompok LIKE ?) ORDER BY nama ASC");
    $like = "%$search%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM users WHERE role='mahasiswa' ORDER BY nama ASC");
}
$mahasiswaList = $stmt->fetchAll();

$pageTitle = 'Kelola Mahasiswa';
$currentPage = 'mahasiswa';
$flash = getFlash();
$csrfToken = generateCsrfToken();
include __DIR__ . '/../includes/header.php';
?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="content-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fa-solid fa-users"></i> Kelola Mahasiswa</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="fa-solid fa-plus"></i> Tambah Mahasiswa
        </button>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> alert-auto-hide"><?= sanitize($flash['message']) ?></div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Cari nama / NIM / kelompok..." value="<?= sanitize($search) ?>">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100"><i class="fa-solid fa-search"></i> Cari</button>
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
                            <th>#</th><th>NIM</th><th>Nama</th><th>Email</th><th>Prodi</th>
                            <th>Kelompok</th><th>Desa</th><th>DPL</th><th>Status</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mahasiswaList)): ?>
                            <tr><td colspan="10" class="text-center text-muted">Belum ada data mahasiswa.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($mahasiswaList as $i => $m): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= sanitize($m['nim'] ?? '-') ?></td>
                            <td><?= sanitize($m['nama']) ?></td>
                            <td><?= sanitize($m['email']) ?></td>
                            <td><?= sanitize($m['prodi'] ?? '-') ?></td>
                            <td><?= sanitize($m['kelompok'] ?? '-') ?></td>
                            <td><?= sanitize($m['desa'] ?? '-') ?></td>
                            <td><?= sanitize($m['dpl'] ?? '-') ?></td>
                            <td>
                                <span class="badge bg-<?= $m['status'] === 'aktif' ? 'success' : 'secondary' ?>">
                                    <?= sanitize(ucfirst($m['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $m['id'] ?>">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <a href="mahasiswa.php?action=delete&id=<?= $m['id'] ?>" class="btn btn-sm btn-danger btn-delete-confirm">
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
</div>

<!-- Modal Edit per baris -->
<?php foreach ($mahasiswaList as $m): ?>
<div class="modal fade" id="modalEdit<?= $m['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="mahasiswa.php">
                <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                <input type="hidden" name="form_action" value="edit">
                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Mahasiswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php include __DIR__ . '/../includes/form_mahasiswa_fields.php'; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="mahasiswa.php">
                <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                <input type="hidden" name="form_action" value="tambah">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Mahasiswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php $m = []; include __DIR__ . '/../includes/form_mahasiswa_fields.php'; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
