-- SisisFour G3.2 - Jurnal Student Exceptions
-- FIX v2: target database eksplisit, tidak bergantung database aktif phpMyAdmin
-- Target: HOSTING / production
-- Database: u473908839_sisfour2026
-- Tanggal: 2026-09-15
--
-- WAJIB: backup database hosting sebelum eksekusi.

USE `u473908839_sisfour2026`;

-- 1) Tambahkan catatan jika belum ada.
ALTER TABLE `presensi_mengajar`
    ADD COLUMN IF NOT EXISTS `catatan` TEXT NULL AFTER `materi`;

-- 2) Buat tabel child exception siswa per Jurnal jika belum ada.
CREATE TABLE IF NOT EXISTS `presensi_mengajar_siswa` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_presensi_mengajar` int(10) UNSIGNED NOT NULL,
    `id_siswa` int(10) UNSIGNED NOT NULL,
    `nama_siswa_snapshot` varchar(150) NOT NULL,
    `nisn_snapshot` varchar(20) DEFAULT NULL,
    `status` enum('Sakit','Izin','Alpha') NOT NULL,
    `created_at` datetime DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pm_siswa_parent_siswa` (`id_presensi_mengajar`,`id_siswa`),
    KEY `idx_pm_siswa_parent` (`id_presensi_mengajar`),
    KEY `idx_pm_siswa_siswa` (`id_siswa`),
    KEY `idx_pm_siswa_status` (`status`),
    CONSTRAINT `fk_pm_siswa_parent`
        FOREIGN KEY (`id_presensi_mengajar`)
        REFERENCES `presensi_mengajar` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_pm_siswa_siswa`
        FOREIGN KEY (`id_siswa`)
        REFERENCES `siswa` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;

-- 3) Verifikasi.
SELECT DATABASE() AS `database_aktif`;

SHOW COLUMNS FROM `presensi_mengajar` LIKE 'catatan';

SHOW CREATE TABLE `presensi_mengajar_siswa`;

SELECT
    'PASS jika database_aktif = u473908839_sisfour2026, kolom catatan tampil dan tabel child tampil'
    AS `sisfour_g32_hosting`;
