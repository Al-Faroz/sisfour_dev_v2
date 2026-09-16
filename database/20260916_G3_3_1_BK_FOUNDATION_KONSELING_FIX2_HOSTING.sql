-- SisisFour G3.3.1 FIX2 — Konseling BK schema/access reconciliation
-- Target: HOSTING
-- Database: u473908839_sisfour2026
-- Tanggal: 2026-09-16
--
-- PENTING:
-- - JANGAN dijalankan sebelum dump hosting aktual diperiksa.
-- - Script dibuat schema-qualified agar tidak bergantung pada database aktif phpMyAdmin.
-- - Setelah dump hosting diperiksa, script ini boleh disesuaikan lagi bila state hosting berbeda.
--
-- Sasaran:
-- 1) created_by -> users.id menjadi audit actor utama;
-- 2) id_guru_bk legacy tetap nullable dan tidak menjadi identitas BK;
-- 3) identitas BK dibaca melalui created_by -> users.id_pegawai -> pegawai.id;
-- 4) Admin, Operator, BK memiliki tepat satu permission Konseling per key dengan scope SEMUA;
-- 5) menu Konseling BK dipastikan tampil untuk Admin, Operator, BK.

INSERT INTO `u473908839_sisfour2026`.`permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
VALUES
  ('bk_konseling.view', 'Lihat Konseling BK', 'BK', 'SEMUA'),
  ('bk_konseling.manage', 'Kelola Konseling BK', 'BK', 'SEMUA'),
  ('bk_konseling.export', 'Export Konseling BK', 'BK', 'SEMUA')
ON DUPLICATE KEY UPDATE
  `nama` = VALUES(`nama`),
  `modul` = VALUES(`modul`),
  `scope_didukung` = VALUES(`scope_didukung`);

SET @has_id_guru_bk := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = 'u473908839_sisfour2026'
    AND TABLE_NAME = 'konseling_bk'
    AND COLUMN_NAME = 'id_guru_bk'
);
SET @sql := IF(
  @has_id_guru_bk > 0,
  'ALTER TABLE `u473908839_sisfour2026`.`konseling_bk` MODIFY `id_guru_bk` int(10) UNSIGNED DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_created_by := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = 'u473908839_sisfour2026'
    AND TABLE_NAME = 'konseling_bk'
    AND COLUMN_NAME = 'created_by'
);
SET @sql := IF(
  @has_created_by = 0,
  IF(
    @has_id_guru_bk > 0,
    'ALTER TABLE `u473908839_sisfour2026`.`konseling_bk` ADD COLUMN `created_by` int(10) UNSIGNED DEFAULT NULL AFTER `id_guru_bk`',
    'ALTER TABLE `u473908839_sisfour2026`.`konseling_bk` ADD COLUMN `created_by` int(10) UNSIGNED DEFAULT NULL AFTER `status`'
  ),
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `u473908839_sisfour2026`.`konseling_bk`
SET `created_by` = `updated_by`
WHERE `created_by` IS NULL
  AND `updated_by` IS NOT NULL;

SET @has_created_idx := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = 'u473908839_sisfour2026'
    AND TABLE_NAME = 'konseling_bk'
    AND INDEX_NAME = 'idx_konseling_created_by'
);
SET @sql := IF(
  @has_created_idx = 0,
  'ALTER TABLE `u473908839_sisfour2026`.`konseling_bk` ADD KEY `idx_konseling_created_by` (`created_by`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_created_fk := (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = 'u473908839_sisfour2026'
    AND TABLE_NAME = 'konseling_bk'
    AND CONSTRAINT_NAME = 'fk_konseling_created_by'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);
SET @sql := IF(
  @has_created_fk = 0,
  'ALTER TABLE `u473908839_sisfour2026`.`konseling_bk` ADD CONSTRAINT `fk_konseling_created_by` FOREIGN KEY (`created_by`) REFERENCES `u473908839_sisfour2026`.`users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Normalisasi permission Konseling secara deterministik.
DELETE rp
FROM `u473908839_sisfour2026`.`role_permissions` rp
JOIN `u473908839_sisfour2026`.`permissions` p ON p.`id` = rp.`id_permission`
WHERE rp.`role` IN ('admin', 'operator', 'bk')
  AND p.`permission_key` IN (
    'bk_konseling.view',
    'bk_konseling.manage',
    'bk_konseling.export'
  );

INSERT INTO `u473908839_sisfour2026`.`role_permissions` (`role`, `id_permission`, `scope`)
SELECT r.`role`, p.`id`, 'SEMUA'
FROM (
  SELECT 'admin' AS `role`
  UNION ALL SELECT 'operator'
  UNION ALL SELECT 'bk'
) r
JOIN `u473908839_sisfour2026`.`permissions` p
  ON p.`permission_key` IN (
    'bk_konseling.view',
    'bk_konseling.manage',
    'bk_konseling.export'
  );

UPDATE `u473908839_sisfour2026`.`menus`
SET `nama_menu` = 'Catatan Pelanggaran', `updated_at` = NOW()
WHERE `link` = 'bk/kasus';

SET @bk_parent_id := (
  SELECT `parent_id`
  FROM `u473908839_sisfour2026`.`menus`
  WHERE `link` = 'bk/kasus'
  LIMIT 1
);
SET @konseling_menu_id := (
  SELECT `id`
  FROM `u473908839_sisfour2026`.`menus`
  WHERE `link` = 'bk/konseling'
  LIMIT 1
);
SET @next_menu_id := (SELECT COALESCE(MAX(`id`), 0) + 1 FROM `u473908839_sisfour2026`.`menus`);
SET @next_menu_order := (
  SELECT COALESCE(MAX(`urutan`), 0) + 1
  FROM `u473908839_sisfour2026`.`menus`
  WHERE (`parent_id` = @bk_parent_id OR (`parent_id` IS NULL AND @bk_parent_id IS NULL))
);

INSERT INTO `u473908839_sisfour2026`.`menus` (`id`, `nama_menu`, `parent_id`, `urutan`, `icon`, `link`, `created_at`, `updated_at`)
SELECT @next_menu_id, 'Konseling BK', @bk_parent_id, @next_menu_order, 'bx bx-chat', 'bk/konseling', NOW(), NOW()
WHERE @konseling_menu_id IS NULL;

SET @konseling_menu_id := (
  SELECT `id`
  FROM `u473908839_sisfour2026`.`menus`
  WHERE `link` = 'bk/konseling'
  LIMIT 1
);

INSERT INTO `u473908839_sisfour2026`.`role_menus` (`role`, `id_menu`, `tampil`)
SELECT r.`role`, @konseling_menu_id, 1
FROM (
  SELECT 'admin' AS `role`
  UNION ALL SELECT 'operator'
  UNION ALL SELECT 'bk'
) r
WHERE @konseling_menu_id IS NOT NULL
ON DUPLICATE KEY UPDATE `tampil` = 1;

-- Verifikasi schema-qualified.
SELECT 'u473908839_sisfour2026' AS `database_target`;

SELECT c.COLUMN_NAME, c.IS_NULLABLE, c.COLUMN_TYPE
FROM information_schema.COLUMNS c
WHERE c.TABLE_SCHEMA = 'u473908839_sisfour2026'
  AND c.TABLE_NAME = 'konseling_bk'
  AND c.COLUMN_NAME IN ('id_guru_bk', 'created_by', 'updated_by')
ORDER BY c.ORDINAL_POSITION;

SELECT rp.`role`, p.`permission_key`, rp.`scope`
FROM `u473908839_sisfour2026`.`role_permissions` rp
JOIN `u473908839_sisfour2026`.`permissions` p ON p.`id` = rp.`id_permission`
WHERE p.`permission_key` LIKE 'bk_konseling.%'
ORDER BY rp.`role`, p.`permission_key`;

SELECT rm.`role`, m.`nama_menu`, m.`link`, rm.`tampil`
FROM `u473908839_sisfour2026`.`role_menus` rm
JOIN `u473908839_sisfour2026`.`menus` m ON m.`id` = rm.`id_menu`
WHERE m.`link` = 'bk/konseling'
ORDER BY rm.`role`;

SELECT u.`id`, u.`username`, u.`role`, u.`id_guru`, u.`id_pegawai`, p.`nama` AS `nama_pegawai`
FROM `u473908839_sisfour2026`.`users` u
LEFT JOIN `u473908839_sisfour2026`.`pegawai` p ON p.`id` = u.`id_pegawai`
WHERE u.`role` = 'bk'
ORDER BY u.`username`;

SELECT COUNT(*) AS `bk_tanpa_id_pegawai`
FROM `u473908839_sisfour2026`.`users`
WHERE `role` = 'bk'
  AND (`id_pegawai` IS NULL OR `id_pegawai` = 0);

SHOW CREATE TABLE `u473908839_sisfour2026`.`konseling_bk`;
