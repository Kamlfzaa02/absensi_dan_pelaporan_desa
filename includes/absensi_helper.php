<?php
/**
 * includes/absensi_helper.php
 * -----------------------------------------------------
 * Helper functions untuk fitur Absensi (Auto Table Creation,
 * Base64 Image Processing, File Upload, Geolocation helper,
 * Pengaturan Jam Buka/Tutup Absensi).
 * -----------------------------------------------------
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Memastikan tabel absensi sudah dibuat di database.
 */
function ensureAbsensiTableExists(PDO $pdo): void
{
    static $checked = false;
    if ($checked) return;

    $sql = "CREATE TABLE IF NOT EXISTS `absensi` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `tanggal` DATE NOT NULL,
        `jam_masuk` TIME NULL,
        `jam_pulang` TIME NULL,
        `foto_masuk` VARCHAR(255) NULL,
        `foto_pulang` VARCHAR(255) NULL,
        `latitude_masuk` VARCHAR(50) NULL,
        `longitude_masuk` VARCHAR(50) NULL,
        `lokasi_masuk` TEXT NULL,
        `latitude_pulang` VARCHAR(50) NULL,
        `longitude_pulang` VARCHAR(50) NULL,
        `lokasi_pulang` TEXT NULL,
        `status` ENUM('hadir', 'izin', 'sakit', 'alpa') NOT NULL DEFAULT 'hadir',
        `keterangan` TEXT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_user_tanggal` (`user_id`, `tanggal`),
        CONSTRAINT `fk_absensi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    try {
        $pdo->exec($sql);
        $checked = true;
    } catch (PDOException $e) {
        // Fallback jika FK error (misal engine tabel users bukan InnoDB)
        $sqlFallback = str_replace("CONSTRAINT `fk_absensi_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE", "", $sql);
        $sqlFallback = str_replace(",\n)", "\n)", $sqlFallback);
        try {
            $pdo->exec($sqlFallback);
            $checked = true;
        } catch (PDOException $ex) {
            error_log("Failed to create absensi table: " . $ex->getMessage());
        }
    }
}

/**
 * Menyimpan gambar dari Base64 string (dari kamera canvas)
 * ke direktori uploads/absensi/
 */
function saveBase64Image(string $base64String, string $prefix = 'absensi'): ?string
{
    if (empty($base64String)) {
        return null;
    }

    // Cek format base64
    if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $type)) {
        $data = substr($base64String, strpos($base64String, ',') + 1);
        $type = strtolower($type[1]); // jpg, png, jpeg, etc.
        if ($type === 'jpeg') $type = 'jpg';
    } else {
        return null;
    }

    $data = base64_decode($data);
    if ($data === false) {
        return null;
    }

    $uploadDir = __DIR__ . '/../uploads/absensi/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $type;
    $filePath = $uploadDir . $filename;

    if (file_put_contents($filePath, $data) !== false) {
        return 'uploads/absensi/' . $filename;
    }

    return null;
}

/**
 * Menyimpan gambar dari file upload ($_FILES)
 */
function saveUploadedImage(array $file, string $prefix = 'absensi'): ?string
{
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    $fileType = mime_content_type($file['tmp_name']);
    if (!in_array($fileType, $allowedTypes)) {
        return null;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (empty($ext)) $ext = 'jpg';

    $uploadDir = __DIR__ . '/../uploads/absensi/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filename = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'uploads/absensi/' . $filename;
    }

    return null;
}

/**
 * Ambil data absensi mahasiswa untuk tanggal tertentu (default hari ini)
 */
function getAbsensiHariIni(PDO $pdo, int $userId, ?string $tanggal = null): ?array
{
    ensureAbsensiTableExists($pdo);
    if ($tanggal === null) {
        $tanggal = date('Y-m-d');
    }

    $stmt = $pdo->prepare("SELECT * FROM absensi WHERE user_id = ? AND tanggal = ? LIMIT 1");
    $stmt->execute([$userId, $tanggal]);
    $result = $stmt->fetch();
    return $result ?: null;
}

/**
 * Memastikan tabel absensi_settings sudah ada.
 * Juga menyemai baris default jika masih kosong.
 */
function ensureAbsensiSettingsTableExists(PDO $pdo): void
{
    static $checkedSettings = false;
    if ($checkedSettings) return;

    $pdo->exec("CREATE TABLE IF NOT EXISTS `absensi_settings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `key_name` VARCHAR(60) NOT NULL UNIQUE,
        `value` VARCHAR(10) NOT NULL,
        `label` VARCHAR(100) NOT NULL,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Semai nilai default jika tabel baru saja dibuat (kosong)
    $count = $pdo->query("SELECT COUNT(*) FROM absensi_settings")->fetchColumn();
    if ((int)$count === 0) {
        $defaults = [
            ['masuk_buka',   '06:00', 'Jam Buka Absen Masuk'],
            ['masuk_tutup',  '09:00', 'Jam Tutup Absen Masuk'],
            ['pulang_buka',  '15:00', 'Jam Buka Absen Pulang'],
            ['pulang_tutup', '21:00', 'Jam Tutup Absen Pulang'],
            ['absensi_aktif', '1',    'Status Absensi Aktif (1=aktif, 0=nonaktif)'],
        ];
        $ins = $pdo->prepare("INSERT INTO absensi_settings (key_name, value, label) VALUES (?, ?, ?)");
        foreach ($defaults as $row) {
            $ins->execute($row);
        }
    }
    $checkedSettings = true;
}

/**
 * Ambil semua pengaturan absensi sebagai asosiatif key => value.
 */
function getAbsensiSettings(PDO $pdo): array
{
    ensureAbsensiSettingsTableExists($pdo);
    $rows = $pdo->query("SELECT key_name, value, label FROM absensi_settings ORDER BY id ASC")->fetchAll();
    $result = [];
    foreach ($rows as $r) {
        $result[$r['key_name']] = ['value' => $r['value'], 'label' => $r['label']];
    }
    return $result;
}

/**
 * Simpan pengaturan absensi dari admin.
 */
function saveAbsensiSettings(PDO $pdo, array $settings): void
{
    ensureAbsensiSettingsTableExists($pdo);

    $stmt = $pdo->prepare("UPDATE absensi_settings SET value = ? WHERE key_name = ?");
    foreach ($settings as $key => $value) {
        $stmt->execute([(string)$value, $key]);
    }
}

/**
 * Validasi apakah jam sekarang masih dalam rentang yang diperbolehkan.
 * Mengembalikan array ['ok' => bool, 'message' => string].
 *
 * @param string $tipe  'masuk' | 'pulang'
 * @param array  $settings  Hasil getAbsensiSettings()
 */
function validateJamAbsensi(string $tipe, array $settings): array
{
    // Jika absensi dinonaktifkan admin
    if (isset($settings['absensi_aktif']) && $settings['absensi_aktif']['value'] === '0') {
        return ['ok' => false, 'message' => 'Absensi sedang dinonaktifkan oleh admin.'];
    }

    $keyBuka  = $tipe . '_buka';
    $keyTutup = $tipe . '_tutup';

    if (!isset($settings[$keyBuka], $settings[$keyTutup])) {
        return ['ok' => true, 'message' => ''];  // jika setting tidak ditemukan, loloskan
    }

    $jamBuka  = $settings[$keyBuka]['value'];   // format "HH:MM"
    $jamTutup = $settings[$keyTutup]['value'];  // format "HH:MM"
    $jamNow   = date('H:i');

    if ($jamNow < $jamBuka) {
        return [
            'ok' => false,
            'message' => 'Absen ' . ($tipe === 'masuk' ? 'Masuk' : 'Pulang') .
                         ' belum dibuka. Dibuka mulai jam ' . $jamBuka . ' WIB.'
        ];
    }

    if ($jamNow > $jamTutup) {
        return [
            'ok' => false,
            'message' => 'Waktu Absen ' . ($tipe === 'masuk' ? 'Masuk' : 'Pulang') .
                         ' sudah ditutup pada jam ' . $jamTutup . ' WIB.'
        ];
    }

    return ['ok' => true, 'message' => ''];
}

