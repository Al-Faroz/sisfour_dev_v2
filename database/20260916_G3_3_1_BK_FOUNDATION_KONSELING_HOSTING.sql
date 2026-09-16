-- SisisFour G3.3.1 — Fondasi BK + Konseling BK
-- Target: HOSTING
-- Database: u473908839_sisfour2026
-- Tanggal: 2026-09-16
--
-- PENTING:
-- - JANGAN dijalankan sebelum dump hosting aktual diperiksa.
-- - Setelah dump hosting diperiksa, sesuaikan bila struktur hosting berbeda.
--
-- Catatan:
-- 1) Kolom legacy ref_pelanggaran.poin TIDAK dihapus pada tahap ini agar rollback aman.
-- 2) Konseling BK bersifat rahasia. Akses diberikan kepada Admin, Operator, dan BK
--    melalui permission khusus; Service tetap menjadi business/security boundary.
-- 3) Workflow Konseling BK: create Tahap 1 -> update Tahap 2.
-- 4) Audit actor selalu direkam melalui created_by -> users.id.
-- 5) Identitas BK aplikasi dibaca melalui users.id_pegawai -> pegawai.id.
--    id_guru_bk hanya metadata legacy nullable dan bukan identitas utama BK.
-- 6) Semua tabel aplikasi memakai schema eksplisit agar aman dari context phpMyAdmin.

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
  CONSTRAINT `fk_konseling_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `u473908839_sisfour2026`.`tahun_ajaran` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_konseling_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `u473908839_sisfour2026`.`kelas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_konseling_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `u473908839_sisfour2026`.`siswa` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_konseling_guru_bk` FOREIGN KEY (`id_guru_bk`) REFERENCES `u473908839_sisfour2026`.`guru` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_konseling_created_by` FOREIGN KEY (`created_by`) REFERENCES `u473908839_sisfour2026`.`users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_konseling_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `u473908839_sisfour2026`.`users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `u473908839_sisfour2026`.`permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
VALUES
  ('bk_konseling.view', 'Lihat Konseling BK', 'BK', 'SEMUA'),
  ('bk_konseling.manage', 'Kelola Konseling BK', 'BK', 'SEMUA'),
  ('bk_konseling.export', 'Export Konseling BK', 'BK', 'SEMUA')
ON DUPLICATE KEY UPDATE
  `nama` = VALUES(`nama`),
  `modul` = VALUES(`modul`),
  `scope_didukung` = VALUES(`scope_didukung`);

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

SELECT 'u473908839_sisfour2026' AS `database_target`;

SELECT `id`, `permission_key`, `nama`, `scope_didukung`
FROM `u473908839_sisfour2026`.`permissions`
WHERE `permission_key` LIKE 'bk_konseling.%'
ORDER BY `permission_key`;

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

SHOW CREATE TABLE `u473908839_sisfour2026`.`konseling_bk`;
