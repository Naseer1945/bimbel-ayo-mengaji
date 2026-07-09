-- ============================================================
-- database.sql — Bimbel Ayo Mengaji (Fullstack WebApp)
-- Skema lengkap: 7 tabel + 1 view + index + seed data.
-- Import via phpMyAdmin atau: mysql -u root < database.sql
-- Charset: utf8mb4 agar emoji & teks Arab aman.
-- ============================================================

CREATE DATABASE IF NOT EXISTS `bimbel_ayo_mengaji`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `bimbel_ayo_mengaji`;

-- Bersihkan tabel lama (urutan menghormati foreign key)
DROP VIEW  IF EXISTS `view_dashboard_stats`;
DROP TABLE IF EXISTS `notifikasi`;
DROP TABLE IF EXISTS `promosi`;
DROP TABLE IF EXISTS `log_aktivitas`;
DROP TABLE IF EXISTS `tugas_pengajar`;
DROP TABLE IF EXISTS `assets_galeri`;
DROP TABLE IF EXISTS `entitas`;
DROP TABLE IF EXISTS `clients`;
DROP TABLE IF EXISTS `pengajar`;
DROP TABLE IF EXISTS `pengaturan`;
DROP TABLE IF EXISTS `users`;

-- ------------------------------------------------------------
-- TABEL 1: users (Master akun semua role)
-- ------------------------------------------------------------
CREATE TABLE `users` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `username`      VARCHAR(50)  UNIQUE NOT NULL,
    `email`         VARCHAR(100) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role`          ENUM('client','manager','pengajar','super_admin') NOT NULL DEFAULT 'client',
    `nama_lengkap`  VARCHAR(100) NOT NULL,
    `no_hp`         VARCHAR(20),
    `alamat`        TEXT,
    `foto_profil`   VARCHAR(255) DEFAULT 'assets/default-avatar.png',
    `status_aktif`  BOOLEAN DEFAULT TRUE,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABEL 2: pengajar (Data detail pengajar)
-- ------------------------------------------------------------
CREATE TABLE `pengajar` (
    `id`             INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`        INT NOT NULL,
    `nama_pengajar`  VARCHAR(100) NOT NULL,
    `pendidikan`     TEXT,
    `pengalaman`     TEXT,
    `foto_pengajar`  VARCHAR(255) DEFAULT 'assets/default-pengajar.png',
    `bio`            TEXT,
    `status_aktif`   BOOLEAN DEFAULT TRUE,
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABEL 3: clients (Data customer)
-- ------------------------------------------------------------
CREATE TABLE `clients` (
    `id`                  INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`             INT NOT NULL,
    `nama_client`         VARCHAR(100) NOT NULL,
    `no_hp`               VARCHAR(20),
    `alamat`              TEXT,
    `jumlah_entitas`      INT DEFAULT 1,
    `status_pendaftaran`  ENUM('pending','approved','rejected') DEFAULT 'pending',
    `catatan_manager`     TEXT,
    `created_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABEL 4: entitas (Santri/anak yang didaftarkan)
-- ------------------------------------------------------------
CREATE TABLE `entitas` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `client_id`       INT NOT NULL,
    `nama_entitas`    VARCHAR(100) NOT NULL,
    `usia`            INT,
    `jenis_kelamin`   ENUM('L','P'),
    `level_saat_ini`  VARCHAR(50) DEFAULT 'Pemula',
    `status_belajar`  ENUM('baru','aktif','lulus','nonaktif') DEFAULT 'baru',
    `pengajar_id`     INT,
    `jadwal_hari`     VARCHAR(50),
    `jadwal_jam`      TIME,
    `catatan_progress` TEXT,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`client_id`)   REFERENCES `clients`(`id`)  ON DELETE CASCADE,
    FOREIGN KEY (`pengajar_id`) REFERENCES `pengajar`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABEL 5: assets_galeri (Foto yang bisa diubah manager/admin)
-- ------------------------------------------------------------
CREATE TABLE `assets_galeri` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `nama_file`     VARCHAR(255) NOT NULL,
    `path_file`     VARCHAR(255) NOT NULL,
    `kategori`      ENUM('hero','galeri','dokumentasi','pengajar') NOT NULL,
    `urutan_tampil` INT DEFAULT 0,
    `status_aktif`  BOOLEAN DEFAULT TRUE,
    `diupload_oleh` INT,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`diupload_oleh`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABEL 6: tugas_pengajar (Arahan dari manager)
-- ------------------------------------------------------------
CREATE TABLE `tugas_pengajar` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `pengajar_id`  INT NOT NULL,
    `entitas_id`   INT,
    `manager_id`   INT NOT NULL,
    `judul_tugas`  VARCHAR(255) NOT NULL,
    `deskripsi`    TEXT,
    `status`       ENUM('assigned','in_progress','completed') DEFAULT 'assigned',
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`pengajar_id`) REFERENCES `pengajar`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`entitas_id`)  REFERENCES `entitas`(`id`)  ON DELETE SET NULL,
    FOREIGN KEY (`manager_id`)  REFERENCES `users`(`id`)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABEL 7: log_aktivitas (Audit trail)
-- ------------------------------------------------------------
CREATE TABLE `log_aktivitas` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`         INT,
    `role`            VARCHAR(20),
    `jenis_aktivitas` VARCHAR(50) NOT NULL,
    `detail`          TEXT,
    `ip_address`      VARCHAR(45),
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TABEL TAMBAHAN: pengaturan (settings sistem key-value)
-- Dipakai oleh admin/settings.php (nama web, kontak WA, dll)
-- ------------------------------------------------------------
CREATE TABLE `pengaturan` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `nama_key`   VARCHAR(50) UNIQUE NOT NULL,
    `nilai`      TEXT,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- INDEX
-- ------------------------------------------------------------
CREATE INDEX `idx_users_role`      ON `users`(`role`);
CREATE INDEX `idx_users_status`    ON `users`(`status_aktif`);
CREATE INDEX `idx_clients_status`  ON `clients`(`status_pendaftaran`);
CREATE INDEX `idx_entitas_status`  ON `entitas`(`status_belajar`);
CREATE INDEX `idx_entitas_pengajar`ON `entitas`(`pengajar_id`);
CREATE INDEX `idx_galeri_kategori` ON `assets_galeri`(`kategori`);
CREATE INDEX `idx_log_user`        ON `log_aktivitas`(`user_id`);

-- ------------------------------------------------------------
-- VIEW: view_dashboard_stats
-- ------------------------------------------------------------
CREATE VIEW `view_dashboard_stats` AS
SELECT
    (SELECT COUNT(*) FROM users    WHERE role='client')              AS total_client,
    (SELECT COUNT(*) FROM pengajar WHERE status_aktif=1)             AS total_pengajar,
    (SELECT COUNT(*) FROM entitas  WHERE status_belajar='aktif')     AS total_santri_aktif,
    (SELECT COUNT(*) FROM entitas  WHERE status_belajar='baru')      AS total_santri_baru;

-- ============================================================
-- SEED DATA
-- Password di-hash dengan password_hash() PHP (BCRYPT).
--   superadmin / admin123
--   manager1   / manager123
--   aldira     / pengajar123
-- ============================================================

-- Hash BCRYPT valid (dihasilkan via password_hash PHP) untuk:
--   superadmin -> admin123 | manager1 -> manager123 | aldira -> pengajar123
INSERT INTO `users` (username, email, password_hash, role, nama_lengkap, no_hp) VALUES
('superadmin', 'superadmin@ayomengaji.id', '$2y$10$G6kQSrmVPO6QyoDz4Q./r.CLQjjuozwULL0WsBvIUUnG3RS70XIO2', 'super_admin', 'Super Administrator', '081234567890'),
('manager1',   'manager@ayomengaji.id',    '$2y$10$nmCuAeT0nA6/Z.ukTupBT.bu3SjnCPpkl9P52IvW1txyuwDQdU.ue', 'manager',     'Manager Utama',       '081234567891'),
('aldira',     'aldira@ayomengaji.id',     '$2y$10$KAG4H.6yZezfIsotW0gXOuOQ8ryw4udpK67LeeXp4bSqFYNjGR4dm', 'pengajar',    'Al Dira Achmad Arrazib', '087750275958');

-- 2) Detail pengajar Al Dira (user_id=3)
INSERT INTO `pengajar` (user_id, nama_pengajar, pendidikan, pengalaman, foto_pengajar, bio) VALUES
(3, 'Al Dira Achmad Arrazib',
 'MA Persatuan Islam 04 Cianjur (2021-2024); IAI Al-Azhary Cianjur - Prodi Hukum Ekonomi Syari''ah',
 'Guru Tahfidz Pondok Pesantren Persatuan Islam 04 Cianjur; Program Keguruan Khidmat Jam''iyyah 2024, Banten; Pengajar Al-Qur''an Masjid Al-Muslimun Cimenteng Girang',
 'assets/WhatsApp Image 2026-06-12 at 10.20.51.jpeg',
 'Pengajar dan penggagas Les Ayo Mengaji yang memiliki perhatian tinggi terhadap pendidikan Al-Qur''an yang mudah dipahami, interaktif, dan menarik bagi generasi muda.');

-- 3) Assets galeri awal (diupload_oleh = superadmin id=1)
INSERT INTO `assets_galeri` (nama_file, path_file, kategori, urutan_tampil, diupload_oleh) VALUES
('Dokumentasi Belajar 1', 'assets/WhatsApp Image 2026-06-12 at 10.34.10.jpeg', 'dokumentasi', 1, 1),
('Dokumentasi Belajar 2', 'assets/WhatsApp Image 2026-06-12 at 10.34.48.jpeg', 'dokumentasi', 2, 1),
('Foto Profil Pengajar',  'assets/WhatsApp Image 2026-06-12 at 10.20.51.jpeg', 'pengajar',    1, 1);

-- 4) Pengaturan default sistem
INSERT INTO `pengaturan` (nama_key, nilai) VALUES
('nama_web',     'Bimbel Ayo Mengaji'),
('kontak_wa',    '087750275958'),
('email_admin',  'aldira@ayomengaji.id'),
('deskripsi',    'Belajar Al-Qur''an untuk anak & remaja (6-17 th) dengan guru privat yang datang ke rumah di Cianjur.'),
('domain_verif', 'bimbelayomengaji.my.id');

-- ============================================================
-- SELESAI. Login default:
--   superadmin / admin123   (Super Admin)
--   manager1   / manager123 (Manager)
--   aldira     / pengajar123 (Pengajar)
-- ============================================================

-- ============================================================
-- MIGRASI v2 — Promosi, Notifikasi, Link YouTube, Toggle Fitur
-- (aman dijalankan ulang: IF NOT EXISTS / INSERT IGNORE)
-- ============================================================

-- Tabel promosi (dikelola super admin, tampil di beranda)
CREATE TABLE IF NOT EXISTS `promosi` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `judul`           VARCHAR(255) NOT NULL,
    `deskripsi`       TEXT,
    `label`           VARCHAR(50) DEFAULT 'PROMO',
    `tanggal_mulai`   DATE,
    `tanggal_selesai` DATE,
    `status_aktif`    BOOLEAN DEFAULT TRUE,
    `dibuat_oleh`     INT,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`dibuat_oleh`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel notifikasi (reminder per akun, semua role)
CREATE TABLE IF NOT EXISTS `notifikasi` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT NOT NULL,
    `pesan`      TEXT NOT NULL,
    `link`       VARCHAR(255),
    `is_read`    BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Sintaks kompatibel MySQL murni (IF NOT EXISTS pada index/kolom hanya ada di MariaDB)
CREATE INDEX `idx_notif_user` ON `notifikasi`(`user_id`, `is_read`);

-- Urutan tampil pengajar (rotasi oleh manager)
ALTER TABLE `pengajar` ADD COLUMN `urutan_tampil` INT DEFAULT 0;

-- Link YouTube (dikelola manager) + toggle fitur (dikelola super admin)
INSERT IGNORE INTO `pengaturan` (nama_key, nilai) VALUES
('yt_channel',      'https://www.youtube.com/@BimbelAyoMengaji'),
('yt_keunggulan_1', 'https://youtube.com/shorts/qQifrwz-zXk'),
('yt_keunggulan_2', 'https://youtube.com/shorts/aSxvskk0Chc'),
('yt_keunggulan_3', 'https://youtube.com/shorts/llCvhN8hLGE'),
('yt_keunggulan_4', 'https://youtube.com/shorts/aDJwpwoOX0o'),
('yt_keunggulan_5', 'https://youtube.com/shorts/ZXgA9YI17hw'),
('yt_nilai_1', ''), ('yt_nilai_2', ''), ('yt_nilai_3', ''), ('yt_nilai_4', ''),
('fitur_youtube', '1'),
('fitur_promosi', '1');
