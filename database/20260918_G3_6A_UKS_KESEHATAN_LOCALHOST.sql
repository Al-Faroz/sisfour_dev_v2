-- G3.6A UKS / Kesehatan — LOCALHOST
-- Baseline source: main @ 59b22b651ad0d508ea3a29261ef590d4c9506da4
-- IMPORTANT: jalankan localhost setelah source branch G3.6A dipull.
-- Hosting SQL dibuat terpisah setelah localhost PASS + audit dump hosting aktual.

START TRANSACTION;

-- 1) Role Kesehatan sebagai effective role berbasis users.id_pegawai.
ALTER TABLE `users`
  MODIFY `role` ENUM('admin','operator','pimpinan','bk','kesehatan','guru','siswa') NULL;

ALTER TABLE `user_roles`
  MODIFY `role` ENUM('admin','operator','pimpinan','bk','kesehatan','guru','siswa') NOT NULL;

ALTER TABLE `role_permissions`
  MODIFY `role` ENUM('admin','operator','pimpinan','bk','kesehatan','guru','siswa') NOT NULL;

ALTER TABLE `role_menus`
  MODIFY `role` ENUM('admin','operator','pimpinan','bk','kesehatan','guru','siswa') NOT NULL;

-- 2) Master/reference UKS configurable.
CREATE TABLE IF NOT EXISTS `uks_ref_keluhan` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(120) NOT NULL,
  `urutan` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `status_aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `deleted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_uks_ref_keluhan_nama` (`nama`),
  KEY `idx_uks_ref_keluhan_active` (`status_aktif`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `uks_ref_tindakan` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(120) NOT NULL,
  `urutan` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `status_aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `deleted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_uks_ref_tindakan_nama` (`nama`),
  KEY `idx_uks_ref_tindakan_active` (`status_aktif`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `uks_ref_hasil` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(120) NOT NULL,
  `urutan` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `status_aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `deleted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_uks_ref_hasil_nama` (`nama`),
  KEY `idx_uks_ref_hasil_active` (`status_aktif`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `uks_ref_keluhan` (`nama`, `urutan`, `status_aktif`, `created_at`)
SELECT seed.nama, seed.urutan, 1, NOW()
FROM (
  SELECT 'Demam' nama, 10 urutan UNION ALL
  SELECT 'Sakit kepala', 20 UNION ALL
  SELECT 'Sakit perut', 30 UNION ALL
  SELECT 'Mual atau muntah', 40 UNION ALL
  SELECT 'Diare', 50 UNION ALL
  SELECT 'Batuk pilek', 60 UNION ALL
  SELECT 'Sakit gigi', 70 UNION ALL
  SELECT 'Nyeri haid', 80 UNION ALL
  SELECT 'Lemas atau pingsan', 90 UNION ALL
  SELECT 'Mimisan', 100 UNION ALL
  SELECT 'Luka atau lecet', 110 UNION ALL
  SELECT 'Keseleo', 120 UNION ALL
  SELECT 'Sesak napas', 130 UNION ALL
  SELECT 'Gatal atau alergi', 140 UNION ALL
  SELECT 'Sakit mata', 150 UNION ALL
  SELECT 'Cedera olahraga', 160 UNION ALL
  SELECT 'Lainnya', 170
) seed
WHERE NOT EXISTS (
  SELECT 1 FROM `uks_ref_keluhan` r WHERE r.nama = seed.nama
);

INSERT INTO `uks_ref_tindakan` (`nama`, `urutan`, `status_aktif`, `created_at`)
SELECT seed.nama, seed.urutan, 1, NOW()
FROM (
  SELECT 'Istirahat' nama, 10 urutan UNION ALL
  SELECT 'Kompres', 20 UNION ALL
  SELECT 'Minum air hangat atau oralit', 30 UNION ALL
  SELECT 'Perawatan luka', 40 UNION ALL
  SELECT 'Pemberian obat', 50
) seed
WHERE NOT EXISTS (
  SELECT 1 FROM `uks_ref_tindakan` r WHERE r.nama = seed.nama
);

INSERT INTO `uks_ref_hasil` (`nama`, `urutan`, `status_aktif`, `created_at`)
SELECT seed.nama, seed.urutan, 1, NOW()
FROM (
  SELECT 'Kembali ke kelas' nama, 10 urutan UNION ALL
  SELECT 'Istirahat di UKS', 20 UNION ALL
  SELECT 'Dijemput orang tua', 30 UNION ALL
  SELECT 'Dirujuk ke klinik', 40
) seed
WHERE NOT EXISTS (
  SELECT 1 FROM `uks_ref_hasil` r WHERE r.nama = seed.nama
);

-- 3) Data CKG periodik. id_kelas adalah snapshot kelas pada period record.
CREATE TABLE IF NOT EXISTS `uks_ckg` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_tahun` INT UNSIGNED NOT NULL,
  `id_kelas` INT UNSIGNED NOT NULL,
  `id_siswa` INT UNSIGNED NOT NULL,
  `tanggal` DATE NOT NULL,
  `berat_badan` DECIMAL(6,2) NULL,
  `tinggi_badan` DECIMAL(6,2) NULL,
  `status_gizi` VARCHAR(30) NULL,
  `status_tinggi` VARCHAR(30) NULL,
  `lingkar_perut` DECIMAL(6,2) NULL,
  `tekanan_sistol` SMALLINT UNSIGNED NULL,
  `tekanan_diastol` SMALLINT UNSIGNED NULL,
  `gula_darah` DECIMAL(7,2) NULL,
  `kondisi_gigi_mulut` VARCHAR(50) NULL,
  `visus_kanan` VARCHAR(30) NULL,
  `visus_kiri` VARCHAR(30) NULL,
  `buta_warna` ENUM('Ya','Tidak') NULL,
  `hasil_pendengaran` VARCHAR(50) NULL,
  `skrining_talasemia` VARCHAR(80) NULL,
  `skrining_tuberkulosis` VARCHAR(80) NULL,
  `created_by` INT UNSIGNED NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `deleted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_uks_ckg_tahun_tanggal` (`id_tahun`, `tanggal`),
  KEY `idx_uks_ckg_siswa_tanggal` (`id_siswa`, `tanggal`, `deleted_at`),
  KEY `idx_uks_ckg_kelas` (`id_kelas`),
  KEY `idx_uks_ckg_deleted` (`deleted_at`),
  KEY `idx_uks_ckg_created_by` (`created_by`),
  KEY `idx_uks_ckg_updated_by` (`updated_by`),
  CONSTRAINT `fk_uks_ckg_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_uks_ckg_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_uks_ckg_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_uks_ckg_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_uks_ckg_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4) Catatan Harian UKS periodik + tindakan multi-pilih.
CREATE TABLE IF NOT EXISTS `uks_kunjungan` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_tahun` INT UNSIGNED NOT NULL,
  `id_kelas` INT UNSIGNED NOT NULL,
  `id_siswa` INT UNSIGNED NOT NULL,
  `tanggal` DATE NOT NULL,
  `jam_masuk` TIME NOT NULL,
  `id_keluhan` INT UNSIGNED NOT NULL,
  `catatan_keluhan` TEXT NULL,
  `suhu_tubuh` DECIMAL(4,1) NULL,
  `tekanan_darah` VARCHAR(20) NULL,
  `obat_diberikan` VARCHAR(255) NULL,
  `jam_keluar` TIME NULL,
  `id_hasil` INT UNSIGNED NOT NULL,
  `orang_tua_dihubungi` ENUM('Ya','Tidak') NOT NULL DEFAULT 'Tidak',
  `id_petugas_user` INT UNSIGNED NULL,
  `created_by` INT UNSIGNED NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `deleted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_uks_kunjungan_tahun_tanggal` (`id_tahun`, `tanggal`),
  KEY `idx_uks_kunjungan_siswa` (`id_siswa`, `tanggal`),
  KEY `idx_uks_kunjungan_kelas` (`id_kelas`),
  KEY `idx_uks_kunjungan_keluhan` (`id_keluhan`),
  KEY `idx_uks_kunjungan_hasil` (`id_hasil`),
  KEY `idx_uks_kunjungan_deleted` (`deleted_at`),
  KEY `idx_uks_kunjungan_petugas` (`id_petugas_user`),
  CONSTRAINT `fk_uks_kunjungan_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_uks_kunjungan_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_uks_kunjungan_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_uks_kunjungan_keluhan` FOREIGN KEY (`id_keluhan`) REFERENCES `uks_ref_keluhan` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_uks_kunjungan_hasil` FOREIGN KEY (`id_hasil`) REFERENCES `uks_ref_hasil` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_uks_kunjungan_petugas` FOREIGN KEY (`id_petugas_user`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_uks_kunjungan_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_uks_kunjungan_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `uks_kunjungan_tindakan` (
  `id_kunjungan` INT UNSIGNED NOT NULL,
  `id_tindakan` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id_kunjungan`, `id_tindakan`),
  KEY `idx_uks_kt_tindakan` (`id_tindakan`),
  CONSTRAINT `fk_uks_kt_kunjungan` FOREIGN KEY (`id_kunjungan`) REFERENCES `uks_kunjungan` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_uks_kt_tindakan` FOREIGN KEY (`id_tindakan`) REFERENCES `uks_ref_tindakan` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5) Permission keys. IDs tidak di-hardcode.
INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'uks_ckg.view', 'Lihat Data CKG', 'UKS', 'SEMUA,KELAS_DIAMPU,DIRI_SENDIRI'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `permission_key` = 'uks_ckg.view');
INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'uks_ckg.manage', 'Kelola Data CKG', 'UKS', 'SEMUA'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `permission_key` = 'uks_ckg.manage');
INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'uks_ckg.import', 'Import Data CKG', 'UKS', 'SEMUA'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `permission_key` = 'uks_ckg.import');
INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'uks_ckg.export', 'Export Data CKG', 'UKS', 'SEMUA'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `permission_key` = 'uks_ckg.export');
INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'uks_harian.view', 'Lihat Catatan Harian UKS', 'UKS', 'SEMUA,KELAS_DIAMPU,DIRI_SENDIRI'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `permission_key` = 'uks_harian.view');
INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'uks_harian.manage', 'Kelola Catatan Harian UKS', 'UKS', 'SEMUA'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `permission_key` = 'uks_harian.manage');
INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'uks_harian.export', 'Export Catatan Harian UKS', 'UKS', 'SEMUA'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `permission_key` = 'uks_harian.export');
INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'uks_master.manage', 'Kelola Master UKS', 'UKS', 'SEMUA'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `permission_key` = 'uks_master.manage');

-- Helper mapping permission tanpa asumsi ID antar environment.
INSERT INTO `role_permissions` (`role`, `id_permission`, `scope`)
SELECT map.role, p.id, map.scope
FROM (
  SELECT 'kesehatan' role, 'dashboard.view' permission_key, 'SEMUA' scope
  UNION ALL SELECT 'admin','uks_ckg.view','SEMUA'
  UNION ALL SELECT 'operator','uks_ckg.view','SEMUA'
  UNION ALL SELECT 'kesehatan','uks_ckg.view','SEMUA'
  UNION ALL SELECT 'pimpinan','uks_ckg.view','SEMUA'
  UNION ALL SELECT 'guru','uks_ckg.view','KELAS_DIAMPU'
  UNION ALL SELECT 'siswa','uks_ckg.view','DIRI_SENDIRI'
  UNION ALL SELECT 'admin','uks_ckg.manage','SEMUA'
  UNION ALL SELECT 'operator','uks_ckg.manage','SEMUA'
  UNION ALL SELECT 'kesehatan','uks_ckg.manage','SEMUA'
  UNION ALL SELECT 'admin','uks_ckg.import','SEMUA'
  UNION ALL SELECT 'operator','uks_ckg.import','SEMUA'
  UNION ALL SELECT 'kesehatan','uks_ckg.import','SEMUA'
  UNION ALL SELECT 'admin','uks_ckg.export','SEMUA'
  UNION ALL SELECT 'operator','uks_ckg.export','SEMUA'
  UNION ALL SELECT 'kesehatan','uks_ckg.export','SEMUA'
  UNION ALL SELECT 'pimpinan','uks_ckg.export','SEMUA'
  UNION ALL SELECT 'admin','uks_harian.view','SEMUA'
  UNION ALL SELECT 'operator','uks_harian.view','SEMUA'
  UNION ALL SELECT 'kesehatan','uks_harian.view','SEMUA'
  UNION ALL SELECT 'pimpinan','uks_harian.view','SEMUA'
  UNION ALL SELECT 'guru','uks_harian.view','KELAS_DIAMPU'
  UNION ALL SELECT 'siswa','uks_harian.view','DIRI_SENDIRI'
  UNION ALL SELECT 'admin','uks_harian.manage','SEMUA'
  UNION ALL SELECT 'operator','uks_harian.manage','SEMUA'
  UNION ALL SELECT 'kesehatan','uks_harian.manage','SEMUA'
  UNION ALL SELECT 'admin','uks_harian.export','SEMUA'
  UNION ALL SELECT 'operator','uks_harian.export','SEMUA'
  UNION ALL SELECT 'kesehatan','uks_harian.export','SEMUA'
  UNION ALL SELECT 'pimpinan','uks_harian.export','SEMUA'
  UNION ALL SELECT 'admin','uks_master.manage','SEMUA'
  UNION ALL SELECT 'operator','uks_master.manage','SEMUA'
  UNION ALL SELECT 'kesehatan','uks_master.manage','SEMUA'
) map
JOIN `permissions` p ON p.permission_key = map.permission_key
WHERE NOT EXISTS (
  SELECT 1 FROM `role_permissions` rp
  WHERE rp.role = map.role
    AND rp.id_permission = p.id
    AND rp.scope = map.scope
);

-- 6) Menu UKS. IDs tidak di-hardcode.
INSERT INTO `menus` (`nama_menu`, `parent_id`, `urutan`, `icon`, `link`, `created_at`, `updated_at`)
SELECT 'UKS', NULL, 7, 'bx bx-plus-medical', '#', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `nama_menu` = 'UKS' AND `parent_id` IS NULL);

SET @uks_parent_id := (
  SELECT `id` FROM `menus`
  WHERE `nama_menu` = 'UKS' AND `parent_id` IS NULL
  ORDER BY `id` ASC LIMIT 1
);

INSERT INTO `menus` (`nama_menu`, `parent_id`, `urutan`, `icon`, `link`, `created_at`, `updated_at`)
SELECT 'Data CKG', @uks_parent_id, 1, NULL, 'uks/ckg', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `link` = 'uks/ckg');

INSERT INTO `menus` (`nama_menu`, `parent_id`, `urutan`, `icon`, `link`, `created_at`, `updated_at`)
SELECT 'Data UKS', @uks_parent_id, 2, NULL, 'uks/harian', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `link` = 'uks/harian');

INSERT INTO `menus` (`nama_menu`, `parent_id`, `urutan`, `icon`, `link`, `created_at`, `updated_at`)
SELECT 'Master UKS', @uks_parent_id, 3, NULL, 'uks/master', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `link` = 'uks/master');

INSERT INTO `role_menus` (`role`, `id_menu`, `tampil`)
SELECT map.role, m.id, 1
FROM (
  SELECT 'admin' role, 'Dashboard' menu_name, 'dashboard' link
  UNION ALL SELECT 'operator','UKS','#'
  UNION ALL SELECT 'admin','UKS','#'
  UNION ALL SELECT 'kesehatan','Dashboard','dashboard'
  UNION ALL SELECT 'kesehatan','UKS','#'
  UNION ALL SELECT 'pimpinan','UKS','#'
  UNION ALL SELECT 'guru','UKS','#'
  UNION ALL SELECT 'siswa','UKS','#'
  UNION ALL SELECT 'admin','Data CKG','uks/ckg'
  UNION ALL SELECT 'operator','Data CKG','uks/ckg'
  UNION ALL SELECT 'kesehatan','Data CKG','uks/ckg'
  UNION ALL SELECT 'pimpinan','Data CKG','uks/ckg'
  UNION ALL SELECT 'guru','Data CKG','uks/ckg'
  UNION ALL SELECT 'siswa','Data CKG','uks/ckg'
  UNION ALL SELECT 'admin','Data UKS','uks/harian'
  UNION ALL SELECT 'operator','Data UKS','uks/harian'
  UNION ALL SELECT 'kesehatan','Data UKS','uks/harian'
  UNION ALL SELECT 'pimpinan','Data UKS','uks/harian'
  UNION ALL SELECT 'guru','Data UKS','uks/harian'
  UNION ALL SELECT 'siswa','Data UKS','uks/harian'
  UNION ALL SELECT 'admin','Master UKS','uks/master'
  UNION ALL SELECT 'operator','Master UKS','uks/master'
  UNION ALL SELECT 'kesehatan','Master UKS','uks/master'
) map
JOIN `menus` m
  ON m.nama_menu = map.menu_name
 AND m.link = map.link
WHERE NOT EXISTS (
  SELECT 1 FROM `role_menus` rm
  WHERE rm.role = map.role AND rm.id_menu = m.id
);

COMMIT;

-- Verification (read-only)
SELECT COLUMN_TYPE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('users','user_roles','role_permissions','role_menus')
  AND COLUMN_NAME = 'role'
ORDER BY TABLE_NAME;

SELECT TABLE_NAME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME LIKE 'uks_%'
ORDER BY TABLE_NAME;

SELECT p.permission_key, rp.role, rp.scope
FROM role_permissions rp
JOIN permissions p ON p.id = rp.id_permission
WHERE p.permission_key LIKE 'uks_%' OR (p.permission_key = 'dashboard.view' AND rp.role = 'kesehatan')
ORDER BY p.permission_key, rp.role, rp.scope;

SELECT m.nama_menu, m.link, rm.role, rm.tampil
FROM role_menus rm
JOIN menus m ON m.id = rm.id_menu
WHERE m.link IN ('dashboard','uks/ckg','uks/harian','uks/master')
   OR m.nama_menu = 'UKS'
ORDER BY m.nama_menu, rm.role;
