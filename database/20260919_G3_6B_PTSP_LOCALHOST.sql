-- G3.6B PTSP — LOCALHOST
-- Baseline source: main @ 90acc7f94fee391a5a7fbad2395e3f16571fe921
-- IMPORTANT: jalankan localhost setelah source branch G3.6B dipull.
-- Hosting SQL dibuat terpisah setelah localhost PASS + fresh hosting dump audit.

-- 1) Role PTSP sebagai effective role berbasis users.id_pegawai.
ALTER TABLE `users`
  MODIFY `role` ENUM('admin','operator','pimpinan','bk','kesehatan','ptsp','guru','siswa') NULL;

ALTER TABLE `user_roles`
  MODIFY `role` ENUM('admin','operator','pimpinan','bk','kesehatan','ptsp','guru','siswa') NOT NULL;

ALTER TABLE `role_permissions`
  MODIFY `role` ENUM('admin','operator','pimpinan','bk','kesehatan','ptsp','guru','siswa') NOT NULL;

ALTER TABLE `role_menus`
  MODIFY `role` ENUM('admin','operator','pimpinan','bk','kesehatan','ptsp','guru','siswa') NOT NULL;

-- 2) Layanan PTSP periodik.
CREATE TABLE IF NOT EXISTS `ptsp_layanan` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_tahun` INT UNSIGNED NOT NULL,
  `nama_lengkap` VARCHAR(150) NOT NULL,
  `kategori_pemohon` VARCHAR(50) NOT NULL,
  `nomor_whatsapp` VARCHAR(25) NOT NULL,
  `jenis_layanan` VARCHAR(120) NOT NULL,
  `tujuan_keterangan` TEXT NOT NULL,
  `status` ENUM('Baru','Diproses','Selesai') NOT NULL DEFAULT 'Baru',
  `created_by` INT UNSIGNED NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ptsp_layanan_tahun_status` (`id_tahun`, `status`),
  KEY `idx_ptsp_layanan_created` (`created_at`),
  KEY `idx_ptsp_layanan_created_by` (`created_by`),
  KEY `idx_ptsp_layanan_updated_by` (`updated_by`),
  CONSTRAINT `fk_ptsp_layanan_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_ptsp_layanan_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_ptsp_layanan_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3) Polling kepuasan periodik. Submission berulang diperbolehkan.
CREATE TABLE IF NOT EXISTS `ptsp_polling` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_tahun` INT UNSIGNED NOT NULL,
  `nama_lengkap` VARCHAR(150) NULL,
  `kategori_responden` VARCHAR(50) NULL,
  `nomor_whatsapp` VARCHAR(25) NULL,
  `tingkat_kepuasan` VARCHAR(50) NOT NULL,
  `score` TINYINT UNSIGNED NOT NULL,
  `masukan_saran` TEXT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ptsp_polling_tahun_score` (`id_tahun`, `score`),
  KEY `idx_ptsp_polling_created` (`created_at`),
  CONSTRAINT `fk_ptsp_polling_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4) Pengaduan anonim periodik + klasifikasi multi-pilih.
CREATE TABLE IF NOT EXISTS `ptsp_pengaduan` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_tahun` INT UNSIGNED NOT NULL,
  `judul_laporan` VARCHAR(200) NOT NULL,
  `isi_laporan` TEXT NOT NULL,
  `tanggal_kejadian` DATE NULL,
  `status` ENUM('Masuk','Diverifikasi','Diproses','Selesai') NOT NULL DEFAULT 'Masuk',
  `lampiran_path` VARCHAR(255) NULL,
  `lampiran_nama_asli` VARCHAR(255) NULL,
  `lampiran_mime` VARCHAR(80) NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ptsp_pengaduan_tahun_status` (`id_tahun`, `status`),
  KEY `idx_ptsp_pengaduan_tanggal` (`tanggal_kejadian`),
  KEY `idx_ptsp_pengaduan_created` (`created_at`),
  KEY `idx_ptsp_pengaduan_updated_by` (`updated_by`),
  CONSTRAINT `fk_ptsp_pengaduan_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_ptsp_pengaduan_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ptsp_pengaduan_klasifikasi` (
  `id_pengaduan` INT UNSIGNED NOT NULL,
  `klasifikasi` ENUM('Pengaduan','Aspirasi','Permintaan Informasi') NOT NULL,
  PRIMARY KEY (`id_pengaduan`, `klasifikasi`),
  KEY `idx_ptsp_pengaduan_klasifikasi` (`klasifikasi`),
  CONSTRAINT `fk_ptsp_pk_pengaduan` FOREIGN KEY (`id_pengaduan`) REFERENCES `ptsp_pengaduan` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5) Permission PTSP. Hard delete sengaja capability terpisah.
INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'ptsp_layanan.view', 'Lihat Layanan PTSP', 'PTSP', 'SEMUA'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `permission_key`='ptsp_layanan.view');
INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'ptsp_layanan.manage', 'Kelola Layanan PTSP', 'PTSP', 'SEMUA'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `permission_key`='ptsp_layanan.manage');
INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'ptsp_layanan.export', 'Export Layanan PTSP', 'PTSP', 'SEMUA'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `permission_key`='ptsp_layanan.export');
INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'ptsp_layanan.delete', 'Hard Delete Layanan PTSP', 'PTSP', 'SEMUA'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `permission_key`='ptsp_layanan.delete');

INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'ptsp_polling.view', 'Lihat Polling Kepuasan PTSP', 'PTSP', 'SEMUA'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `permission_key`='ptsp_polling.view');
INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'ptsp_polling.export', 'Export Polling Kepuasan PTSP', 'PTSP', 'SEMUA'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `permission_key`='ptsp_polling.export');
INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'ptsp_polling.delete', 'Hard Delete Polling Kepuasan PTSP', 'PTSP', 'SEMUA'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `permission_key`='ptsp_polling.delete');

INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'ptsp_pengaduan.view', 'Lihat Pengaduan PTSP', 'PTSP', 'SEMUA'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `permission_key`='ptsp_pengaduan.view');
INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'ptsp_pengaduan.manage', 'Kelola Status Pengaduan PTSP', 'PTSP', 'SEMUA'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `permission_key`='ptsp_pengaduan.manage');
INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'ptsp_pengaduan.export', 'Export Pengaduan PTSP', 'PTSP', 'SEMUA'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `permission_key`='ptsp_pengaduan.export');
INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'ptsp_pengaduan.delete', 'Hard Delete Pengaduan PTSP', 'PTSP', 'SEMUA'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `permission_key`='ptsp_pengaduan.delete');

INSERT INTO `role_permissions` (`role`, `id_permission`, `scope`)
SELECT map.role, p.id, 'SEMUA'
FROM (
  SELECT 'ptsp' role, 'dashboard.view' permission_key
  UNION ALL SELECT 'admin','ptsp_layanan.view'
  UNION ALL SELECT 'operator','ptsp_layanan.view'
  UNION ALL SELECT 'ptsp','ptsp_layanan.view'
  UNION ALL SELECT 'pimpinan','ptsp_layanan.view'
  UNION ALL SELECT 'admin','ptsp_layanan.manage'
  UNION ALL SELECT 'operator','ptsp_layanan.manage'
  UNION ALL SELECT 'ptsp','ptsp_layanan.manage'
  UNION ALL SELECT 'admin','ptsp_layanan.export'
  UNION ALL SELECT 'operator','ptsp_layanan.export'
  UNION ALL SELECT 'ptsp','ptsp_layanan.export'
  UNION ALL SELECT 'pimpinan','ptsp_layanan.export'
  UNION ALL SELECT 'admin','ptsp_layanan.delete'
  UNION ALL SELECT 'operator','ptsp_layanan.delete'
  UNION ALL SELECT 'ptsp','ptsp_layanan.delete'
  UNION ALL SELECT 'admin','ptsp_polling.view'
  UNION ALL SELECT 'operator','ptsp_polling.view'
  UNION ALL SELECT 'ptsp','ptsp_polling.view'
  UNION ALL SELECT 'pimpinan','ptsp_polling.view'
  UNION ALL SELECT 'admin','ptsp_polling.export'
  UNION ALL SELECT 'operator','ptsp_polling.export'
  UNION ALL SELECT 'ptsp','ptsp_polling.export'
  UNION ALL SELECT 'pimpinan','ptsp_polling.export'
  UNION ALL SELECT 'admin','ptsp_polling.delete'
  UNION ALL SELECT 'operator','ptsp_polling.delete'
  UNION ALL SELECT 'ptsp','ptsp_polling.delete'
  UNION ALL SELECT 'admin','ptsp_pengaduan.view'
  UNION ALL SELECT 'operator','ptsp_pengaduan.view'
  UNION ALL SELECT 'ptsp','ptsp_pengaduan.view'
  UNION ALL SELECT 'pimpinan','ptsp_pengaduan.view'
  UNION ALL SELECT 'admin','ptsp_pengaduan.manage'
  UNION ALL SELECT 'operator','ptsp_pengaduan.manage'
  UNION ALL SELECT 'ptsp','ptsp_pengaduan.manage'
  UNION ALL SELECT 'admin','ptsp_pengaduan.export'
  UNION ALL SELECT 'operator','ptsp_pengaduan.export'
  UNION ALL SELECT 'ptsp','ptsp_pengaduan.export'
  UNION ALL SELECT 'pimpinan','ptsp_pengaduan.export'
  UNION ALL SELECT 'admin','ptsp_pengaduan.delete'
  UNION ALL SELECT 'operator','ptsp_pengaduan.delete'
  UNION ALL SELECT 'ptsp','ptsp_pengaduan.delete'
) map
JOIN `permissions` p ON p.permission_key = map.permission_key
WHERE NOT EXISTS (
  SELECT 1 FROM `role_permissions` rp
  WHERE rp.role=map.role AND rp.id_permission=p.id AND rp.scope='SEMUA'
);

-- 6) Menu internal PTSP. Public landing tidak menjadi menu authenticated.
SET @ptsp_repair_parent_id := (
  SELECT COALESCE(MAX(`id`),0)+1 FROM `menus` WHERE `id`<>0
);
UPDATE `menus`
SET `id`=@ptsp_repair_parent_id
WHERE `id`=0 AND `nama_menu`='PTSP' AND `parent_id` IS NULL AND `link`='#';

SET @ptsp_new_id := (SELECT COALESCE(MAX(`id`),0)+1 FROM `menus`);
INSERT INTO `menus` (`id`,`nama_menu`,`parent_id`,`urutan`,`icon`,`link`,`created_at`,`updated_at`)
SELECT @ptsp_new_id,'PTSP',NULL,8,'bx bx-building-house','#',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `nama_menu`='PTSP' AND `parent_id` IS NULL);

SET @ptsp_parent_id := (
  SELECT `id` FROM `menus`
  WHERE `nama_menu`='PTSP' AND `parent_id` IS NULL
  ORDER BY `id` ASC LIMIT 1
);

SET @ptsp_new_id := (SELECT COALESCE(MAX(`id`),0)+1 FROM `menus`);
INSERT INTO `menus` (`id`,`nama_menu`,`parent_id`,`urutan`,`icon`,`link`,`created_at`,`updated_at`)
SELECT @ptsp_new_id,'Layanan PTSP',@ptsp_parent_id,1,NULL,'ptsp/layanan',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `link`='ptsp/layanan');

SET @ptsp_new_id := (SELECT COALESCE(MAX(`id`),0)+1 FROM `menus`);
INSERT INTO `menus` (`id`,`nama_menu`,`parent_id`,`urutan`,`icon`,`link`,`created_at`,`updated_at`)
SELECT @ptsp_new_id,'Polling Kepuasan',@ptsp_parent_id,2,NULL,'ptsp/polling',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `link`='ptsp/polling');

SET @ptsp_new_id := (SELECT COALESCE(MAX(`id`),0)+1 FROM `menus`);
INSERT INTO `menus` (`id`,`nama_menu`,`parent_id`,`urutan`,`icon`,`link`,`created_at`,`updated_at`)
SELECT @ptsp_new_id,'Pengaduan',@ptsp_parent_id,3,NULL,'ptsp/pengaduan',NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `link`='ptsp/pengaduan');

INSERT INTO `role_menus` (`role`,`id_menu`,`tampil`)
SELECT map.role,m.id,1
FROM (
  SELECT 'ptsp' role,'Dashboard' menu_name,'dashboard' link
  UNION ALL SELECT 'admin','PTSP','#'
  UNION ALL SELECT 'operator','PTSP','#'
  UNION ALL SELECT 'ptsp','PTSP','#'
  UNION ALL SELECT 'pimpinan','PTSP','#'
  UNION ALL SELECT 'admin','Layanan PTSP','ptsp/layanan'
  UNION ALL SELECT 'operator','Layanan PTSP','ptsp/layanan'
  UNION ALL SELECT 'ptsp','Layanan PTSP','ptsp/layanan'
  UNION ALL SELECT 'pimpinan','Layanan PTSP','ptsp/layanan'
  UNION ALL SELECT 'admin','Polling Kepuasan','ptsp/polling'
  UNION ALL SELECT 'operator','Polling Kepuasan','ptsp/polling'
  UNION ALL SELECT 'ptsp','Polling Kepuasan','ptsp/polling'
  UNION ALL SELECT 'pimpinan','Polling Kepuasan','ptsp/polling'
  UNION ALL SELECT 'admin','Pengaduan','ptsp/pengaduan'
  UNION ALL SELECT 'operator','Pengaduan','ptsp/pengaduan'
  UNION ALL SELECT 'ptsp','Pengaduan','ptsp/pengaduan'
  UNION ALL SELECT 'pimpinan','Pengaduan','ptsp/pengaduan'
) map
JOIN `menus` m ON m.nama_menu=map.menu_name AND m.link=map.link
WHERE NOT EXISTS (
  SELECT 1 FROM `role_menus` rm WHERE rm.role=map.role AND rm.id_menu=m.id
);

-- Verification read-only.
SHOW COLUMNS FROM `users` LIKE 'role';
SHOW COLUMNS FROM `user_roles` LIKE 'role';
SHOW COLUMNS FROM `role_permissions` LIKE 'role';
SHOW COLUMNS FROM `role_menus` LIKE 'role';

SHOW TABLES LIKE 'ptsp_%';

SELECT p.permission_key, rp.role, rp.scope
FROM role_permissions rp
JOIN permissions p ON p.id=rp.id_permission
WHERE p.permission_key LIKE 'ptsp_%'
   OR (p.permission_key='dashboard.view' AND rp.role='ptsp')
ORDER BY p.permission_key,rp.role;

SELECT m.nama_menu,m.link,rm.role,rm.tampil
FROM role_menus rm
JOIN menus m ON m.id=rm.id_menu
WHERE m.link IN ('dashboard','ptsp/layanan','ptsp/polling','ptsp/pengaduan')
   OR m.nama_menu='PTSP'
ORDER BY m.nama_menu,rm.role;

SELECT COUNT(*) AS ptsp_layanan_rows FROM ptsp_layanan;
SELECT COUNT(*) AS ptsp_polling_rows FROM ptsp_polling;
SELECT COUNT(*) AS ptsp_pengaduan_rows FROM ptsp_pengaduan;
