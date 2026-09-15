-- SisisFour G3.2 - Jurnal Student Exceptions
-- Target: LOCALHOST / development / UAT
-- Tanggal: 2026-09-15
--
-- Tujuan:
-- 1. Menambah presensi_mengajar.catatan bila belum ada.
-- 2. Membuat presensi_mengajar_siswa bila belum ada.
-- 3. Aman dijalankan ulang tanpa menghapus data UAT yang sudah ada.
--
-- Script ini menggantikan mekanisme CodeIgniter migration untuk schema G3.2.
-- Jalankan pada database SisisFour yang dipilih di phpMyAdmin / MySQL CLI.
-- Tidak menyentuh tabel presensi siswa resmi.

SET @sisfour_db := DATABASE();

-- ---------------------------------------------------------------------------
-- A. Parent Jurnal: catatan optional
-- ---------------------------------------------------------------------------
SET @sisfour_sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `presensi_mengajar` ADD COLUMN `catatan` TEXT NULL AFTER `materi`',
        'SELECT ''OK - presensi_mengajar.catatan sudah tersedia'' AS `sisfour_g32`'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @sisfour_db
      AND TABLE_NAME = 'presensi_mengajar'
      AND COLUMN_NAME = 'catatan'
);

PREPARE sisfour_stmt FROM @sisfour_sql;
EXECUTE sisfour_stmt;
DEALLOCATE PREPARE sisfour_stmt;

-- ---------------------------------------------------------------------------
-- B. Child exception siswa per Jurnal
-- ---------------------------------------------------------------------------
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
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_pm_siswa_siswa`
        FOREIGN KEY (`id_siswa`)
        REFERENCES `siswa` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------------
-- C. Verification - hasil yang diharapkan:
--    catatan = 1 row
--    presensi_mengajar_siswa = 1 row
--    unique/index/FK tampil pada SHOW CREATE TABLE
-- ---------------------------------------------------------------------------
SELECT
    TABLE_SCHEMA,
    TABLE_NAME,
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'presensi_mengajar'
  AND COLUMN_NAME = 'catatan';

SELECT
    TABLE_SCHEMA,
    TABLE_NAME,
    ENGINE,
    TABLE_COLLATION
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'presensi_mengajar_siswa';

SHOW CREATE TABLE `presensi_mengajar_siswa`;

SELECT
    'PASS bila catatan dan presensi_mengajar_siswa tersedia; data existing dipertahankan.' AS `sisfour_g32_localhost`;
