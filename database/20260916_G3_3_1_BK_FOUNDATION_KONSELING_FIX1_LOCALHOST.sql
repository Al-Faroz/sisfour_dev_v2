-- SisisFour G3.3.1 FIX1 — Konseling BK actor/access correction
-- Target: LOCALHOST
-- Database: sisfour_dev_v2
-- Jalankan SETELAH 20260916_G3_3_1_BK_FOUNDATION_KONSELING_LOCALHOST.sql
-- Tanggal: 2026-09-16
--
-- Fix:
-- 1) Konseling tidak lagi mensyaratkan users.id_guru.
-- 2) Actor pencatat disimpan melalui users.id pada kolom created_by.
-- 3) Admin, Operator, dan BK mendapat akses Konseling sesuai permission SEMUA.

USE `sisfour_dev_v2`;

-- Identity Guru menjadi metadata optional; audit actor utama adalah created_by -> users.id.
ALTER TABLE `konseling_bk`
  MODIFY `id_guru_bk` int(10) UNSIGNED DEFAULT NULL;

SET @has_created_by := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'konseling_bk'
    AND COLUMN_NAME = 'created_by'
);
SET @sql := IF(
  @has_created_by = 0,
  'ALTER TABLE `konseling_bk` ADD COLUMN `created_by` int(10) UNSIGNED DEFAULT NULL AFTER `id_guru_bk`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Bila sudah ada data, gunakan actor update terakhir sebagai fallback creator.
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

-- Permission tetap menjadi route gate, Service tetap menjadi business/security boundary.
INSERT IGNORE INTO `role_permissions` (`role`, `id_permission`, `scope`)
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
  );

SET @konseling_menu_id := (
  SELECT `id`
  FROM `menus`
  WHERE `link` = 'bk/konseling'
  LIMIT 1
);

INSERT IGNORE INTO `role_menus` (`role`, `id_menu`, `tampil`)
SELECT r.`role`, @konseling_menu_id, 1
FROM (
  SELECT 'admin' AS `role`
  UNION ALL SELECT 'operator'
  UNION ALL SELECT 'bk'
) r
WHERE @konseling_menu_id IS NOT NULL;

-- Verifikasi.
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

SHOW CREATE TABLE `konseling_bk`;
