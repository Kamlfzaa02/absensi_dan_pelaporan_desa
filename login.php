<?php
/**
 * login.php
 * -----------------------------------------------------
 * Halaman login untuk Admin & Mahasiswa (satu form,
 * role ditentukan otomatis dari data di database).
 * -----------------------------------------------------
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Sesi tidak valid, silakan muat ulang halaman.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'Email dan password wajib diisi.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] === 'nonaktif') {
                    $error = 'Akun Anda sudah tidak aktif. Hubungi admin.';
                } else {
                    // Regenerasi session id untuk mencegah session fixation
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['nama'] = $user['nama'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];

                    header('Location: index.php');
                    exit;
                }
            } else {
                $error = 'Email atau password salah.';
            }
        }
    }
}

$pageTitle = 'Login';
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Logbook KKN 43 Desa Taman Sari Mrangen</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>

<body class="login-page">

    <!-- Topbar Contacts Bar (mimicking UPGRIS) -->
    <div class="login-topbar d-none d-md-block">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex gap-3">
                <span><i class="fa-solid fa-envelope me-1"></i> kknupgris43tamansari@gmail.com</span>

            </div>
            <div class="d-flex gap-3 align-items-center">
                <span><i class="fa-solid fa-globe me-1"></i> ID</span>
                <a href="#" target="_blank"><i class="fa-brands fa-tiktok"></i></a>
                <a href="#" target="_blank"><i class="fa-brands fa-instagram"></i></a>
                <a href="#" target="_blank"><i class="fa-brands fa-youtube"></i></a>
            </div>
        </div>
    </div>

    <!-- Floating Glassmorphic Navbar (mimicking UPGRIS) -->
    <!-- <header class="w-100">
        <div class="login-navbar">
            <a href="#" class="login-navbar-brand">
                <i class="fa-solid fa-graduation-cap brand-cap"></i> KKN 43 <span>Taman Sari</span>
            </a>
            <div class="login-navbar-menu d-none d-lg-flex">
                <a href="#" class="active">Beranda</a>
                <a href="#">Akademik</a>
                <a href="#">Informasi</a>
                <a href="#">Layanan</a>
                <a href="#">Lain-lain</a>
            </div>
            <div></div> Spacer for flex alignment -->
    <!-- </div>
    </header> -->

    <!-- Main Landing Container -->
    <main class="login-content">
        <div class="container d-flex justify-content-center">
            <div class="login-wrapper">
                <!-- Left side: Hero Text -->
                <div class="login-hero-text d-none d-lg-block">
                    <div class="login-hero-tag">KKN 43 Desa Taman Sari Mrangen</div>
                    <h1 class="login-hero-title"><span>Logbook KKN 43</span><br>Desa Taman Sari Mrangen</h1>
                    <p class="login-hero-desc">
                        Selamat datang di portal Logbook KKN 43 Desa Taman Sari Mrangen. Silakan masuk untuk
                        mendokumentasikan, memantau, dan mengelola seluruh riwayat logbook kegiatan KKN Anda secara
                        terstruktur.
                    </p>
                </div>

                <!-- Right side: Glass Login Card -->
                <div class="card login-card glass-card">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <img src="<?= base_url('assets/images/logo.png') ?>" alt="Logo KKN 43" class="mb-3" style="max-height: 100px; width: auto; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.15));">
                            <h4 class="mt-2 mb-0">Masuk Akun</h4>
                            <small class="text-white-50">Logbook KKN 43 Desa Taman Sari Mrangen</small>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger py-2 mb-3 text-center"
                                style="background-color: rgba(220, 53, 69, 0.2); color: #ffb3b8; border: 1px solid rgba(220, 53, 69, 0.3);">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= sanitize($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="login.php">
                            <input type="hidden" name="csrf_token" value="<?= sanitize($csrfToken) ?>">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="nama@email.com"
                                    required autofocus>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="••••••••"
                                    required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mt-2">
                                <i class="fa-solid fa-right-to-bracket me-1"></i> Login
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Landing Footer -->
    <footer class="login-footer">
        <div class="container">
            <span>&copy; <?= date('Y') ?> Logbook KKN 43 Desa Taman Sari Mrangen. All Rights Reserved.</span>
        </div>
    </footer>

</body>

</html>