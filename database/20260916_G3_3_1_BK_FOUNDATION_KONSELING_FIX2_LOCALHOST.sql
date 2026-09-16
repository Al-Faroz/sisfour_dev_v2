-- SisisFour G3.3.1 FIX2 — Konseling BK schema/access reconciliation
-- Target: LOCALHOST
-- Database: sisfour_dev_v2
-- Tanggal: 2026-09-16
--
-- Jalankan untuk database yang sudah sempat memakai draft awal G3.3.1.
-- Script ini idempotent dan mencakup kembali koreksi FIX1:
-- 1) created_by -> users.id sebagai audit actor utama;
-- 2) id_guru_bk legacy dibuat nullable bila masih ada;
-- 3) Admin, Operator, BK mendapat permission Konseling scope SEMUA;
-- 4) menu Konseling BK dipastikan tampil untuk Admin, Operator, BK;
-- 5) BK yang merupakan Pegawai dibaca melalui users.id_pegawai, bukan dipaksa sebagai Guru.

USE `sisfour_dev_v2`;

-- Pastikan permission tersedia.
INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
VALUES
  ('bk_konseling.view', 'Lihat Konseling BK', 'BK', 'SEMUA'),
  ('bk_konseling.manage', 'Kelola Konseling BK', 'BK', 'SEMUA'),
  ('bk_konseling.export', 'Export Konseling BK', 'BK', 'SEMUA')
ON DUPLICATE KEY UPDATE
  `nama` = VALUES(`nama`),
  `modul` = VALUES(`modul`),
  `scope_didukung` = VALUES(`scope_didukung`);

-- id_guru_bk adalah kolom legacy optional bila draft awal sudah membuatnya.
SET @has_id_guru_bk := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'konseling_bk'
    AND COLUMN_NAME = 'id_guru_bk'
);
SET @sql := IF(
  @has_id_guru_bk > 0,
  'ALTER TABLE `konseling_bk` MODIFY `id_guru_bk` int(10) UNSIGNED DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Audit actor utama: created_by -> users.id.
SET @has_created_by := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'konseling_bk'
    AND COLUMN_NAME = 'created_by'
);
SET @sql := IF(
  @has_created_by = 0,
  IF(
    @has_id_guru_bk > 0,
    'ALTER TABLE `konseling_bk` ADD COLUMN `created_by` int(10) UNSIGNED DEFAULT NULL AFTER `id_guru_bk`',
    'ALTER TABLE `konseling_bk` ADD COLUMN `created_by` int(10) UNSIGNED DEFAULT NULL AFTER `status`'
  ),
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `konseling_bk`
SET `created_by` = `updated_by`
WHERE `created_by` IS NULL
  AND `updated_by` IS NOT NULL;

SET @has_created_idx := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'konseling_bk'
    AND INDEX_NAME = 'idx_konseling_created_by'
);
SET @sql := IF(
  @has_created_idx = 0,
  'ALTER TABLE `konseling_bk` ADD KEY `idx_konseling_created_by` (`created_by`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_created_fk := (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'konseling_bk'
    AND CONSTRAINT_NAME = 'fk_konseling_created_by'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);
SET @sql := IF(
  @has_created_fk = 0,
  'ALTER TABLE `konseling_bk` ADD CONSTRAINT `fk_konseling_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Permission: upsert, bukan INSERT IGNORE, agar scope lama ikut dikoreksi.
INSERT INTO `role_permissions` (`role`, `id_permission`, `scope`)
SELECT r.`role`, p.`id`, 'SEMUA'
FROM (
  SELECT 'admin' AS `role`
  UNION ALL SELECT 'operator'
  UNION ALL SELECT 'bk'
) r
JOIN `permissions` p
  ON p.`permission_key` IN (
    'bk_konseling.view',
    'bk_konseling.manage',
    'bk_konseling.export'
  )
ON DUPLICATE KEY UPDATE `scope` = 'SEMUA';

-- Pastikan menu Konseling BK ada.
SET @bk_parent_id := (
  SELECT `parent_id`
  FROM `menus`
  WHERE `link` = 'bk/kasus'
  LIMIT 1
);
SET @konseling_menu_id := (
  SELECT `id`
  FROM `menus`
  WHERE `link` = 'bk/konseling'
  LIMIT 1
);
SET @next_menu_id := (SELECT COALESCE(MAX(`id`), 0) + 1 FROM `menus`);
SET @next_menu_order := (
  SELECT COALESCE(MAX(`urutan`), 0) + 1
  FROM `menus`
  WHERE (`parent_id` = @bk_parent_id OR (`parent_id` IS NULL AND @bk_parent_id IS NULL))
);

INSERT INTO `menus` (`id`, `nama_menu`, `parent_id`, `urutan`, `icon`, `link`, `created_at`, `updated_at`)
SELECT @next_menu_id, 'Konseling BK', @bk_parent_id, @next_menu_order, 'bx bx-chat', 'bk/konseling', NOW(), NOW()
WHERE @konseling_menu_id IS NULL;

SET @konseling_menu_id := (
  SELECT `id`
  FROM `menus`
  WHERE `link` = 'bk/konseling'
  LIMIT 1
);

INSERT INTO `role_menus` (`role`, `id_menu`, `tampil`)
SELECT r.`role`, @konseling_menu_id, 1
FROM (
  SELECT 'admin' AS `role`
  UNION ALL SELECT 'operator'
  UNION ALL SELECT 'bk'
) r
WHERE @konseling_menu_id IS NOT NULL
ON DUPLICATE KEY UPDATE `tampil` = 1;

-- Verifikasi schema, permission, menu, dan identity BK.
SELECT c.COLUMN_NAME, c.IS_NULLABLE, c.COLUMN_TYPE
FROM information_schema.COLUMNS c
WHERE c.TABLE_SCHEMA = DATABASE()
  AND c.TABLE_NAME = 'konseling_bk'
  AND c.COLUMN_NAME IN ('id_guru_bk', 'created_by', 'updated_by')
ORDER BY c.ORDINAL_POSITION;

SELECT rp.`role`, p.`permission_key`, rp.`scope`
FROM `role_permissions` rp
JOIN `permissions` p ON p.`id` = rp.`id_permission`
WHERE p.`permission_key` LIKE 'bk_konseling.%'
ORDER BY rp.`role`, p.`permission_key`;

SELECT rm.`role`, m.`nama_menu`, m.`link`, rm.`tampil`
FROM `role_menus` rm
JOIN `menus` m ON m.`id` = rm.`id_menu`
WHERE m.`link` = 'bk/konseling'
ORDER BY rm.`role`;

SELECT u.`id`, u.`username`, u.`role`, u.`id_guru`, u.`id_pegawai`, p.`nama` AS `nama_pegawai`
FROM `users` u
LEFT JOIN `pegawai` p ON p.`id` = u.`id_pegawai`
WHERE u.`role` = 'bk'
ORDER BY u.`username`;
