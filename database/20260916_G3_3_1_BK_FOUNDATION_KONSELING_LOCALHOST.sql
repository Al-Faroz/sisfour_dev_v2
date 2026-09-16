-- SisisFour G3.3.1 — Fondasi BK + Konseling BK
-- Target: LOCALHOST
-- Database: sisfour_dev_v2
-- Tanggal: 2026-09-16
--
-- Catatan:
-- 1) Kolom legacy ref_pelanggaran.poin TIDAK dihapus pada tahap ini agar rollback aman.
--    Aplikasi G3.3.1 tidak lagi menampilkan/mengagregasi poin.
-- 2) Konseling BK bersifat rahasia. Akses diberikan kepada Admin, Operator, dan BK
--    melalui permission khusus; Service tetap menjadi business/security boundary.
-- 3) Workflow Konseling BK: create Tahap 1 -> update Tahap 2.
-- 4) Pencatat selalu direkam melalui created_by -> users.id. id_guru_bk hanya metadata
--    optional bila akun memang terhubung ke identitas Guru.

USE `sisfour_dev_v2`;

CREATE TABLE IF NOT EXISTS `konseling_bk` (
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
  CONSTRAINT `fk_konseling_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_konseling_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_konseling_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_konseling_guru_bk` FOREIGN KEY (`id_guru_bk`) REFERENCES `guru` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_konseling_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_konseling_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
VALUES
  ('bk_konseling.view', 'Lihat Konseling BK', 'BK', 'SEMUA'),
  ('bk_konseling.manage', 'Kelola Konseling BK', 'BK', 'SEMUA'),
  ('bk_konseling.export', 'Export Konseling BK', 'BK', 'SEMUA')
ON DUPLICATE KEY UPDATE
  `nama` = VALUES(`nama`),
  `modul` = VALUES(`modul`),
  `scope_didukung` = VALUES(`scope_didukung`);

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

UPDATE `menus`
SET `nama_menu` = 'Catatan Pelanggaran', `updated_at` = NOW()
WHERE `link` = 'bk/kasus';

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

INSERT IGNORE INTO `role_menus` (`role`, `id_menu`, `tampil`)
SELECT r.`role`, @konseling_menu_id, 1
FROM (
  SELECT 'admin' AS `role`
  UNION ALL SELECT 'operator'
  UNION ALL SELECT 'bk'
) r
WHERE @konseling_menu_id IS NOT NULL;

SELECT `id`, `permission_key`, `nama`, `scope_didukung`
FROM `permissions`
WHERE `permission_key` LIKE 'bk_konseling.%'
ORDER BY `permission_key`;

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
