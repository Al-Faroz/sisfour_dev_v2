-- SisisFour G3.3.1 FIX3 — Pengaturan Form Konseling
-- Target: LOCALHOST
-- Database: sisfour_dev_v2
-- Tanggal: 2026-09-16
--
-- Tidak membuat tabel baru.
-- Pilihan Form Konseling disimpan pada setting_sistem dengan key:
--   bk_konseling_form_options
-- Row setting akan dibuat aplikasi saat pengaturan pertama kali disimpan.
--
-- Akses:
--   admin = boleh mengatur
--   bk    = boleh mengatur
--   operator = tetap boleh memakai Konseling, tetapi tidak mengatur pilihan form

INSERT INTO `sisfour_dev_v2`.`permissions`
    (`permission_key`, `nama`, `modul`, `scope_didukung`)
VALUES
    ('bk_konseling.settings', 'Pengaturan Form Konseling BK', 'BK', 'SEMUA')
ON DUPLICATE KEY UPDATE
    `nama` = VALUES(`nama`),
    `modul` = VALUES(`modul`),
    `scope_didukung` = VALUES(`scope_didukung`);

SET @settings_permission_id := (
    SELECT `id`
    FROM `sisfour_dev_v2`.`permissions`
    WHERE `permission_key` = 'bk_konseling.settings'
    LIMIT 1
);

-- Normalisasi permission: hanya Admin dan BK.
DELETE FROM `sisfour_dev_v2`.`role_permissions`
WHERE `id_permission` = @settings_permission_id;

INSERT INTO `sisfour_dev_v2`.`role_permissions`
    (`role`, `id_permission`, `scope`)
SELECT r.`role`, @settings_permission_id, 'SEMUA'
FROM (
    SELECT 'admin' AS `role`
    UNION ALL
    SELECT 'bk'
) r
WHERE @settings_permission_id IS NOT NULL;

-- Parent mengikuti kelompok BK & Prestasi yang sama dengan Konseling BK.
SET @bk_parent_id := (
    SELECT `parent_id`
    FROM `sisfour_dev_v2`.`menus`
    WHERE `link` = 'bk/konseling'
    LIMIT 1
);

SET @settings_menu_id := (
    SELECT `id`
    FROM `sisfour_dev_v2`.`menus`
    WHERE `link` = 'bk/konseling/settings'
    LIMIT 1
);

SET @next_menu_id := (
    SELECT COALESCE(MAX(`id`), 0) + 1
    FROM `sisfour_dev_v2`.`menus`
);

-- Pakai urutan terakhir dalam parent untuk menghindari benturan UNIQUE/order existing.
SET @next_menu_order := (
    SELECT COALESCE(MAX(`urutan`), 0) + 1
    FROM `sisfour_dev_v2`.`menus`
    WHERE (`parent_id` = @bk_parent_id OR (`parent_id` IS NULL AND @bk_parent_id IS NULL))
);

INSERT INTO `sisfour_dev_v2`.`menus`
    (`id`, `nama_menu`, `parent_id`, `urutan`, `icon`, `link`, `created_at`, `updated_at`)
SELECT
    @next_menu_id,
    'Pengaturan Form Konseling',
    @bk_parent_id,
    @next_menu_order,
    'bx bx-slider-alt',
    'bk/konseling/settings',
    NOW(),
    NOW()
WHERE @settings_menu_id IS NULL;

SET @settings_menu_id := (
    SELECT `id`
    FROM `sisfour_dev_v2`.`menus`
    WHERE `link` = 'bk/konseling/settings'
    LIMIT 1
);

UPDATE `sisfour_dev_v2`.`menus`
SET
    `nama_menu` = 'Pengaturan Form Konseling',
    `parent_id` = @bk_parent_id,
    `icon` = 'bx bx-slider-alt',
    `updated_at` = NOW()
WHERE `id` = @settings_menu_id;

-- Admin dan BK tampil.
INSERT INTO `sisfour_dev_v2`.`role_menus` (`role`, `id_menu`, `tampil`)
SELECT r.`role`, @settings_menu_id, 1
FROM (
    SELECT 'admin' AS `role`
    UNION ALL
    SELECT 'bk'
) r
WHERE @settings_menu_id IS NOT NULL
ON DUPLICATE KEY UPDATE `tampil` = 1;

-- Operator tidak diberi menu setting meskipun tetap memiliki akses operasional Konseling.
DELETE FROM `sisfour_dev_v2`.`role_menus`
WHERE `role` = 'operator'
  AND `id_menu` = @settings_menu_id;

-- Verifikasi permission.
SELECT rp.`role`, p.`permission_key`, rp.`scope`
FROM `sisfour_dev_v2`.`role_permissions` rp
JOIN `sisfour_dev_v2`.`permissions` p ON p.`id` = rp.`id_permission`
WHERE p.`permission_key` = 'bk_konseling.settings'
ORDER BY rp.`role`;

-- Verifikasi menu.
SELECT rm.`role`, m.`nama_menu`, m.`link`, rm.`tampil`
FROM `sisfour_dev_v2`.`role_menus` rm
JOIN `sisfour_dev_v2`.`menus` m ON m.`id` = rm.`id_menu`
WHERE m.`link` = 'bk/konseling/settings'
ORDER BY rm.`role`;

-- Verifikasi tabel setting_sistem tersedia; tidak ada CREATE TABLE pada FIX3.
SELECT COUNT(*) AS `setting_sistem_tersedia`
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'sisfour_dev_v2'
  AND TABLE_NAME = 'setting_sistem';
