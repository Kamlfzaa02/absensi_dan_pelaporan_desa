<?php
/**
 * includes/header.php
 * -----------------------------------------------------
 * Bagian <head> HTML: meta, title, CSS (Bootstrap 5,
 * Font Awesome, style.css custom). Variabel $pageTitle
 * bisa di-set di halaman pemanggil sebelum include ini.
 * -----------------------------------------------------
 */
if (!isset($pageTitle)) {
    $pageTitle = 'Logbook KKN 43 Desa Taman Sari Mrangen';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> | Logbook KKN 43 Desa Taman Sari Mrangen</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body>
