-- SisisFour G3.3.1 — Fondasi BK + Konseling BK
-- Target: HOSTING
-- Database: u473908839_sisfour2026
-- Berdasarkan dump aktual: 2026-09-16 13:26 (MariaDB 11.8.9)
--
-- Gate:
-- 1) Script ini disusun dari dump hosting aktual, bukan salinan patch localhost.
-- 2) Jalankan hanya setelah review/static gate dan approval eksplisit.
-- 3) Kolom legacy ref_pelanggaran.poin TIDAK dihapus/diubah. Aplikasi G3.3.1 tidak lagi
--    menampilkan, mengagregasi, mengekspor, atau memakai poin sebagai business rule.
-- 4) Konseling BK bersifat rahasia. Akses operasional hanya Admin, Operator, dan BK.
-- 5) Pengaturan Form Konseling hanya Admin dan BK.
-- 6) Pimpinan, Guru/Wali, dan Siswa tidak memperoleh permission/menu Konseling.
-- 7) Audit actor direkam melalui created_by -> users.id.
-- 8) Dump hosting menunjukkan akun role BK memakai users.id_pegawai -> pegawai.id;
--    id_guru_bk tetap metadata nullable, bukan identitas utama BK.
-- 9) Pilihan Form Konseling memakai setting_sistem key bk_konseling_form_options;
--    tidak membuat tabel settings baru.
-- 10) Script tidak membaca information_schema dan tidak memakai multi-table DELETE.

CREATE TABLE IF NOT EXISTS `u473908839_sisfour2026`.`konseling_bk` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_tahun` int(10) UNSIGNED NOT NULL,
  `id_kelas` int(10) UNSIGNED NOT NULL,
  `id_siswa` int(10) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `pertemuan_ke` smallint(5) UNSIGNED NOT NULL DEFAULT 1,
  `bentuk_layanan` varchar(50) NOT NULL,
  `cara_hadir` varchar(80) NOT NULL,
  `bidang` enum('Pribadi','Sosial','Belajar','Karier') NOT NULL,
  `topik` varchar(150) NOT NULL,
  `uraian_masalah` text DEFAULT NULL,
  `hasil_kesepakatan` text DEFAULT NULL,
  `rencana_berikutnya` varchar(100) DEFAULT NULL,
  `tanggal_berikutnya` date DEFAULT NULL,
  `status` enum('Proses','Selesai') NOT NULL DEFAULT 'Proses',
  `id_guru_bk` int(10) UNSIGNED DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(10) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_konseling_siswa_tanggal` (`id_siswa`,`tanggal`),
  KEY `idx_konseling_kelas_tanggal` (`id_kelas`,`tanggal`),
  KEY `idx_konseling_status_tanggal` (`status`,`tanggal`),
  KEY `idx_konseling_guru_tanggal` (`id_guru_bk`,`tanggal`),
  KEY `idx_konseling_tahun` (`id_tahun`),
  KEY `idx_konseling_tanggal_berikutnya` (`tanggal_berikutnya`),
  KEY `idx_konseling_created_by` (`created_by`),
  KEY `idx_konseling_updated_by` (`updated_by`),
  CONSTRAINT `fk_konseling_tahun`
    FOREIGN KEY (`id_tahun`) REFERENCES `u473908839_sisfour2026`.`tahun_ajaran` (`id`)
    ON UPDATE CASCADE,
  CONSTRAINT `fk_konseling_kelas`
    FOREIGN KEY (`id_kelas`) REFERENCES `u473908839_sisfour2026`.`kelas` (`id`)
    ON UPDATE CASCADE,
  CONSTRAINT `fk_konseling_siswa`
    FOREIGN KEY (`id_siswa`) REFERENCES `u473908839_sisfour2026`.`siswa` (`id`)
    ON UPDATE CASCADE,
  CONSTRAINT `fk_konseling_guru_bk`
    FOREIGN KEY (`id_guru_bk`) REFERENCES `u473908839_sisfour2026`.`guru` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_konseling_created_by`
    FOREIGN KEY (`created_by`) REFERENCES `u473908839_sisfour2026`.`users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_konseling_updated_by`
    FOREIGN KEY (`updated_by`) REFERENCES `u473908839_sisfour2026`.`users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Permission operasional + settings.
INSERT INTO `u473908839_sisfour2026`.`permissions`
  (`permission_key`, `nama`, `modul`, `scope_didukung`)
VALUES
  ('bk_konseling.view', 'Lihat Konseling BK', 'BK', 'SEMUA'),
  ('bk_konseling.manage', 'Kelola Konseling BK', 'BK', 'SEMUA'),
  ('bk_konseling.export', 'Export Konseling BK', 'BK', 'SEMUA'),
  ('bk_konseling.settings', 'Pengaturan Form Konseling BK', 'BK', 'SEMUA')
ON DUPLICATE KEY UPDATE
  `nama` = VALUES(`nama`),
  `modul` = VALUES(`modul`),
  `scope_didukung` = VALUES(`scope_didukung`);

-- Normalisasi seluruh mapping permission Konseling.
DELETE FROM `u473908839_sisfour2026`.`role_permissions`
WHERE `id_permission` IN (
  SELECT p.`id`
  FROM `u473908839_sisfour2026`.`permissions` p
  WHERE p.`permission_key` LIKE 'bk_konseling.%'
);

-- Operasional: Admin + Operator + BK.
INSERT INTO `u473908839_sisfour2026`.`role_permissions`
  (`role`, `id_permission`, `scope`)
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

-- Settings: Admin + BK saja.
INSERT INTO `u473908839_sisfour2026`.`role_permissions`
  (`role`, `id_permission`, `scope`)
SELECT r.`role`, p.`id`, 'SEMUA'
FROM (
  SELECT 'admin' AS `role`
  UNION ALL SELECT 'bk'
) r
JOIN `u473908839_sisfour2026`.`permissions` p
  ON p.`permission_key` = 'bk_konseling.settings';

-- Rename business label; physical legacy table/route tetap dipertahankan.
UPDATE `u473908839_sisfour2026`.`menus`
SET `nama_menu` = 'Catatan Pelanggaran', `updated_at` = NOW()
WHERE `link` = 'bk/kasus';

SET @bk_parent_id := (
  SELECT `parent_id`
  FROM `u473908839_sisfour2026`.`menus`
  WHERE `link` = 'bk/kasus'
  LIMIT 1
);

-- Menu Konseling BK.
SET @konseling_menu_id := (
  SELECT `id`
  FROM `u473908839_sisfour2026`.`menus`
  WHERE `link` = 'bk/konseling'
  LIMIT 1
);
SET @next_menu_id := (
  SELECT COALESCE(MAX(`id`), 0) + 1
  FROM `u473908839_sisfour2026`.`menus`
);
SET @next_menu_order := (
  SELECT COALESCE(MAX(`urutan`), 0) + 1
  FROM `u473908839_sisfour2026`.`menus`
  WHERE `parent_id` = @bk_parent_id
);

INSERT INTO `u473908839_sisfour2026`.`menus`
  (`id`, `nama_menu`, `parent_id`, `urutan`, `icon`, `link`, `created_at`, `updated_at`)
SELECT
  @next_menu_id,
  'Konseling BK',
  @bk_parent_id,
  @next_menu_order,
  'bx bx-chat',
  'bk/konseling',
  NOW(),
  NOW()
WHERE @konseling_menu_id IS NULL
  AND @bk_parent_id IS NOT NULL;

SET @konseling_menu_id := (
  SELECT `id`
  FROM `u473908839_sisfour2026`.`menus`
  WHERE `link` = 'bk/konseling'
  LIMIT 1
);

INSERT INTO `u473908839_sisfour2026`.`role_menus`
  (`role`, `id_menu`, `tampil`)
SELECT r.`role`, @konseling_menu_id, 1
FROM (
  SELECT 'admin' AS `role`
  UNION ALL SELECT 'operator'
  UNION ALL SELECT 'bk'
) r
WHERE @konseling_menu_id IS NOT NULL
ON DUPLICATE KEY UPDATE `tampil` = 1;

DELETE FROM `u473908839_sisfour2026`.`role_menus`
WHERE `role` IN ('pimpinan', 'guru', 'siswa')
  AND `id_menu` = @konseling_menu_id;

-- Menu Pengaturan Form Konseling.
SET @settings_menu_id := (
  SELECT `id`
  FROM `u473908839_sisfour2026`.`menus`
  WHERE `link` = 'bk/konseling/settings'
  LIMIT 1
);
SET @next_menu_id := (
  SELECT COALESCE(MAX(`id`), 0) + 1
  FROM `u473908839_sisfour2026`.`menus`
);
SET @next_menu_order := (
  SELECT COALESCE(MAX(`urutan`), 0) + 1
  FROM `u473908839_sisfour2026`.`menus`
  WHERE `parent_id` = @bk_parent_id
);

INSERT INTO `u473908839_sisfour2026`.`menus`
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
WHERE @settings_menu_id IS NULL
  AND @bk_parent_id IS NOT NULL;

SET @settings_menu_id := (
  SELECT `id`
  FROM `u473908839_sisfour2026`.`menus`
  WHERE `link` = 'bk/konseling/settings'
  LIMIT 1
);

INSERT INTO `u473908839_sisfour2026`.`role_menus`
  (`role`, `id_menu`, `tampil`)
SELECT r.`role`, @settings_menu_id, 1
FROM (
  SELECT 'admin' AS `role`
  UNION ALL SELECT 'bk'
) r
WHERE @settings_menu_id IS NOT NULL
ON DUPLICATE KEY UPDATE `tampil` = 1;

DELETE FROM `u473908839_sisfour2026`.`role_menus`
WHERE `role` IN ('operator', 'pimpinan', 'guru', 'siswa')
  AND `id_menu` = @settings_menu_id;

-- Verification gate.
SELECT 'u473908839_sisfour2026' AS `database_target`;

SELECT `id`, `permission_key`, `nama`, `scope_didukung`
FROM `u473908839_sisfour2026`.`permissions`
WHERE `permission_key` LIKE 'bk_konseling.%'
ORDER BY `permission_key`;

SELECT rp.`role`, p.`permission_key`, rp.`scope`
FROM `u473908839_sisfour2026`.`role_permissions` rp
JOIN `u473908839_sisfour2026`.`permissions` p
  ON p.`id` = rp.`id_permission`
WHERE p.`permission_key` LIKE 'bk_konseling.%'
ORDER BY rp.`role`, p.`permission_key`;

SELECT rm.`role`, m.`nama_menu`, m.`link`, rm.`tampil`
FROM `u473908839_sisfour2026`.`role_menus` rm
JOIN `u473908839_sisfour2026`.`menus` m
  ON m.`id` = rm.`id_menu`
WHERE m.`link` IN ('bk/konseling', 'bk/konseling/settings')
ORDER BY m.`link`, rm.`role`;

SELECT COUNT(*) AS `forbidden_permission_rows`
FROM `u473908839_sisfour2026`.`role_permissions` rp
JOIN `u473908839_sisfour2026`.`permissions` p
  ON p.`id` = rp.`id_permission`
WHERE rp.`role` IN ('pimpinan', 'guru', 'siswa')
  AND p.`permission_key` LIKE 'bk_konseling.%';

SELECT COUNT(*) AS `forbidden_menu_rows`
FROM `u473908839_sisfour2026`.`role_menus` rm
JOIN `u473908839_sisfour2026`.`menus` m
  ON m.`id` = rm.`id_menu`
WHERE rm.`role` IN ('pimpinan', 'guru', 'siswa')
  AND m.`link` IN ('bk/konseling', 'bk/konseling/settings')
  AND rm.`tampil` = 1;

SELECT `id`, `nama_menu`, `parent_id`, `urutan`, `link`
FROM `u473908839_sisfour2026`.`menus`
WHERE `link` IN ('bk/kasus', 'bk/konseling', 'bk/konseling/settings')
ORDER BY `parent_id`, `urutan`, `id`;

SELECT u.`id`, u.`username`, u.`role`, u.`id_guru`, u.`id_pegawai`,
       p.`nama` AS `nama_pegawai`
FROM `u473908839_sisfour2026`.`users` u
LEFT JOIN `u473908839_sisfour2026`.`pegawai` p
  ON p.`id` = u.`id_pegawai`
WHERE u.`role` = 'bk'
ORDER BY u.`username`;

SHOW CREATE TABLE `u473908839_sisfour2026`.`konseling_bk`;
