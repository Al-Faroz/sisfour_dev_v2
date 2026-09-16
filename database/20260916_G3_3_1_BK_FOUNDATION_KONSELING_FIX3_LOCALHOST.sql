-- SisisFour G3.3.1 FIX3 — Pengaturan Form Konseling + role boundary hardening
-- Target: LOCALHOST
-- Database: sisfour_dev_v2
-- Tanggal: 2026-09-16
--
-- Tidak membuat tabel baru.
-- Pilihan Form Konseling disimpan pada setting_sistem dengan key:
--   bk_konseling_form_options
-- Row setting akan dibuat aplikasi saat pengaturan pertama kali disimpan.
--
-- Akses operasional Konseling:
--   admin, operator, bk
-- Pengaturan Form Konseling:
--   admin, bk
-- Explicit deny/cleanup:
--   pimpinan, guru/Wali, siswa tidak memiliki permission/menu Konseling.
--
-- Catatan kompatibilitas localhost:
-- - Script ini TIDAK membaca information_schema.
-- - Cleanup memakai subquery sederhana, bukan multi-table DELETE ... JOIN.
-- - Aman dijalankan ulang bila import sebelumnya berhenti di tengah.

-- -----------------------------------------------------------------------------
-- 1. Pastikan permission Settings tersedia.
-- -----------------------------------------------------------------------------
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

-- Pengaturan hanya Admin + BK.
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

-- -----------------------------------------------------------------------------
-- 2. Enforce boundary operasional Konseling.
--    Hapus mapping accidental/legacy untuk role yang tidak boleh melihat data
--    rahasia. Wali memakai role Guru + context, jadi tercakup oleh 'guru'.
-- -----------------------------------------------------------------------------
DELETE FROM `sisfour_dev_v2`.`role_permissions`
WHERE `role` IN ('pimpinan', 'guru', 'siswa')
  AND `id_permission` IN (
      SELECT p.`id`
      FROM `sisfour_dev_v2`.`permissions` p
      WHERE p.`permission_key` IN (
          'bk_konseling.view',
          'bk_konseling.manage',
          'bk_konseling.export',
          'bk_konseling.settings'
      )
  );

-- -----------------------------------------------------------------------------
-- 3. Parent/menu Pengaturan Form Konseling.
-- -----------------------------------------------------------------------------
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

-- Admin dan BK melihat menu Settings.
INSERT INTO `sisfour_dev_v2`.`role_menus` (`role`, `id_menu`, `tampil`)
SELECT r.`role`, @settings_menu_id, 1
FROM (
    SELECT 'admin' AS `role`
    UNION ALL
    SELECT 'bk'
) r
WHERE @settings_menu_id IS NOT NULL
ON DUPLICATE KEY UPDATE `tampil` = 1;

-- Operator tidak mengatur form; Pimpinan/Guru/Siswa tidak melihat Settings.
DELETE FROM `sisfour_dev_v2`.`role_menus`
WHERE `role` IN ('operator', 'pimpinan', 'guru', 'siswa')
  AND `id_menu` = @settings_menu_id;

-- Pastikan menu operasional Konseling juga tidak tampil pada role terlarang.
DELETE FROM `sisfour_dev_v2`.`role_menus`
WHERE `role` IN ('pimpinan', 'guru', 'siswa')
  AND `id_menu` IN (
      SELECT m.`id`
      FROM `sisfour_dev_v2`.`menus` m
      WHERE m.`link` = 'bk/konseling'
  );

-- -----------------------------------------------------------------------------
-- 4. Verifikasi.
-- -----------------------------------------------------------------------------
SELECT rp.`role`, p.`permission_key`, rp.`scope`
FROM `sisfour_dev_v2`.`role_permissions` rp
JOIN `sisfour_dev_v2`.`permissions` p ON p.`id` = rp.`id_permission`
WHERE p.`permission_key` LIKE 'bk_konseling.%'
ORDER BY rp.`role`, p.`permission_key`;

SELECT rm.`role`, m.`nama_menu`, m.`link`, rm.`tampil`
FROM `sisfour_dev_v2`.`role_menus` rm
JOIN `sisfour_dev_v2`.`menus` m ON m.`id` = rm.`id_menu`
WHERE m.`link` IN ('bk/konseling', 'bk/konseling/settings')
ORDER BY m.`link`, rm.`role`;

SELECT COUNT(*) AS `forbidden_permission_rows`
FROM `sisfour_dev_v2`.`role_permissions` rp
JOIN `sisfour_dev_v2`.`permissions` p ON p.`id` = rp.`id_permission`
WHERE rp.`role` IN ('pimpinan', 'guru', 'siswa')
  AND p.`permission_key` LIKE 'bk_konseling.%';

SELECT COUNT(*) AS `forbidden_menu_rows`
FROM `sisfour_dev_v2`.`role_menus` rm
JOIN `sisfour_dev_v2`.`menus` m ON m.`id` = rm.`id_menu`
WHERE rm.`role` IN ('pimpinan', 'guru', 'siswa')
  AND m.`link` IN ('bk/konseling', 'bk/konseling/settings')
  AND rm.`tampil` = 1;

-- Verifikasi tabel existing tanpa information_schema.
SHOW TABLES FROM `sisfour_dev_v2` LIKE 'setting_sistem';
