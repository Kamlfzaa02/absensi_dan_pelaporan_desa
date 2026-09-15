-- =====================================================
-- Table: absensi
-- KKN Logbook - Fitur Absensi Foto Muka & GPS Geolocation
-- =====================================================

CREATE TABLE IF NOT EXISTS `absensi` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
