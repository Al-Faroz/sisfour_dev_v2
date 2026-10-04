-- SisisFour G3.10B — Student Document Center
-- FINAL LOCALHOST schema after period-ownership removal.
-- Target: LOCALHOST / sisfour_dev_v2
-- Tanggal: 2026-10-04
-- IMPORTANT: replace the earlier unexecuted G3.10B draft. Run this file once.

CREATE TABLE IF NOT EXISTS `sisfour_dev_v2`.`dokumen_siswa_import_batch` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `judul` VARCHAR(200) NOT NULL,
  `format_file` ENUM('PDF','IMAGE') NOT NULL,
  `source_filename` VARCHAR(255) DEFAULT NULL,
  `total_row` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_valid` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_error` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('COMMITTED','ROLLED_BACK','FAILED') NOT NULL DEFAULT 'COMMITTED',
  `created_by` INT(10) UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `committed_at` DATETIME DEFAULT NULL,
  `rolled_back_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_dsib_status_created` (`status`, `created_at`),
  KEY `idx_dsib_created_by` (`created_by`),
  CONSTRAINT `fk_dsib_created_by_g310`
    FOREIGN KEY (`created_by`)
    REFERENCES `sisfour_dev_v2`.`users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `sisfour_dev_v2`.`dokumen_siswa` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `target_type` ENUM('INDIVIDU','TINGKAT') NOT NULL,
  `id_siswa` INT(10) UNSIGNED DEFAULT NULL,
  `tingkat` ENUM('7','8','9') DEFAULT NULL,
  `judul` VARCHAR(200) NOT NULL,
  `format_file` ENUM('PDF','IMAGE') NOT NULL,
  `link_gdrive` VARCHAR(1000) NOT NULL,
  `status` ENUM('PUBLISHED','ARCHIVED') NOT NULL DEFAULT 'PUBLISHED',
  `id_import_batch` INT(10) UNSIGNED DEFAULT NULL,
  `created_by` INT(10) UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_by` INT(10) UNSIGNED DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ds_status_target` (`status`, `target_type`),
  KEY `idx_ds_target_tingkat` (`target_type`, `tingkat`),
  KEY `idx_ds_siswa_status` (`id_siswa`, `status`),
  KEY `idx_ds_import_batch` (`id_import_batch`),
  CONSTRAINT `fk_ds_siswa_g310`
    FOREIGN KEY (`id_siswa`)
    REFERENCES `sisfour_dev_v2`.`siswa` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ds_import_batch_g310`
    FOREIGN KEY (`id_import_batch`)
    REFERENCES `sisfour_dev_v2`.`dokumen_siswa_import_batch` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ds_created_by_g310`
    FOREIGN KEY (`created_by`)
    REFERENCES `sisfour_dev_v2`.`users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ds_updated_by_g310`
    FOREIGN KEY (`updated_by`)
    REFERENCES `sisfour_dev_v2`.`users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `sisfour_dev_v2`.`dokumen_siswa_access_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_dokumen` INT(10) UNSIGNED NOT NULL,
  `id_user` INT(10) UNSIGNED DEFAULT NULL,
  `id_siswa` INT(10) UNSIGNED DEFAULT NULL,
  `aksi` ENUM('OPEN') NOT NULL DEFAULT 'OPEN',
  `waktu` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_dsal_dokumen_waktu` (`id_dokumen`, `waktu`),
  KEY `idx_dsal_siswa_waktu` (`id_siswa`, `waktu`),
  CONSTRAINT `fk_dsal_dokumen_g310`
    FOREIGN KEY (`id_dokumen`)
    REFERENCES `sisfour_dev_v2`.`dokumen_siswa` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_dsal_user_g310`
    FOREIGN KEY (`id_user`)
    REFERENCES `sisfour_dev_v2`.`users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_dsal_siswa_g310`
    FOREIGN KEY (`id_siswa`)
    REFERENCES `sisfour_dev_v2`.`siswa` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `sisfour_dev_v2`.`dokumen_siswa_delete_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `delete_batch_key` VARCHAR(64) NOT NULL,
  `id_dokumen_asal` INT(10) UNSIGNED NOT NULL,
  `target_type` ENUM('INDIVIDU','TINGKAT') NOT NULL,
  `id_siswa` INT(10) UNSIGNED DEFAULT NULL,
  `tingkat` ENUM('7','8','9') DEFAULT NULL,
  `judul` VARCHAR(200) NOT NULL,
  `format_file` ENUM('PDF','IMAGE') NOT NULL,
  `link_gdrive` VARCHAR(1000) NOT NULL,
  `status_asal` ENUM('PUBLISHED','ARCHIVED') NOT NULL,
  `id_import_batch` INT(10) UNSIGNED DEFAULT NULL,
  `created_by_asal` INT(10) UNSIGNED DEFAULT NULL,
  `created_at_asal` DATETIME DEFAULT NULL,
  `deleted_by` INT(10) UNSIGNED DEFAULT NULL,
  `deleted_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_dsdl_batch` (`delete_batch_key`),
  KEY `idx_dsdl_dokumen_asal` (`id_dokumen_asal`),
  KEY `idx_dsdl_deleted_at` (`deleted_at`),
  KEY `idx_dsdl_deleted_by` (`deleted_by`),
  CONSTRAINT `fk_dsdl_deleted_by_g310`
    FOREIGN KEY (`deleted_by`)
    REFERENCES `sisfour_dev_v2`.`users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sisfour_dev_v2`.`permissions`
  (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'dokumen_siswa.view_self','Lihat Dokumen Saya','Dokumen Siswa','DIRI_SENDIRI'
WHERE NOT EXISTS (
  SELECT 1 FROM `sisfour_dev_v2`.`permissions`
  WHERE `permission_key`='dokumen_siswa.view_self'
);

INSERT INTO `sisfour_dev_v2`.`permissions`
  (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'dokumen_siswa.view_all','Lihat Semua Dokumen Siswa','Dokumen Siswa','SEMUA'
WHERE NOT EXISTS (
  SELECT 1 FROM `sisfour_dev_v2`.`permissions`
  WHERE `permission_key`='dokumen_siswa.view_all'
);

INSERT INTO `sisfour_dev_v2`.`permissions`
  (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'dokumen_siswa.manage','Kelola Dokumen Siswa','Dokumen Siswa','SEMUA'
WHERE NOT EXISTS (
  SELECT 1 FROM `sisfour_dev_v2`.`permissions`
  WHERE `permission_key`='dokumen_siswa.manage'
);

INSERT INTO `sisfour_dev_v2`.`permissions`
  (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'dokumen_siswa.export','Export Dokumen Siswa','Dokumen Siswa','SEMUA'
WHERE NOT EXISTS (
  SELECT 1 FROM `sisfour_dev_v2`.`permissions`
  WHERE `permission_key`='dokumen_siswa.export'
);

INSERT INTO `sisfour_dev_v2`.`permissions`
  (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'dokumen_siswa.hard_delete','Hard Delete Dokumen Siswa','Dokumen Siswa','SEMUA'
WHERE NOT EXISTS (
  SELECT 1 FROM `sisfour_dev_v2`.`permissions`
  WHERE `permission_key`='dokumen_siswa.hard_delete'
);

INSERT INTO `sisfour_dev_v2`.`role_permissions`
  (`role`, `id_permission`, `scope`)
SELECT r.role, p.id, 'SEMUA'
FROM (
  SELECT 'admin' role
  UNION ALL
  SELECT 'operator'
) r
JOIN `sisfour_dev_v2`.`permissions` p
  ON p.permission_key IN (
    'dokumen_siswa.view_all',
    'dokumen_siswa.manage',
    'dokumen_siswa.export',
    'dokumen_siswa.hard_delete'
  )
WHERE NOT EXISTS (
  SELECT 1
  FROM `sisfour_dev_v2`.`role_permissions` rp
  WHERE rp.role=r.role
    AND rp.id_permission=p.id
);

INSERT INTO `sisfour_dev_v2`.`role_permissions`
  (`role`, `id_permission`, `scope`)
SELECT 'siswa', p.id, 'DIRI_SENDIRI'
FROM `sisfour_dev_v2`.`permissions` p
WHERE p.permission_key='dokumen_siswa.view_self'
  AND NOT EXISTS (
    SELECT 1
    FROM `sisfour_dev_v2`.`role_permissions` rp
    WHERE rp.role='siswa'
      AND rp.id_permission=p.id
  );

INSERT INTO `sisfour_dev_v2`.`menus`
  (`nama_menu`, `parent_id`, `urutan`, `icon`, `link`, `created_at`, `updated_at`)
SELECT 'Dokumen Siswa', NULL, 8, 'bx bx-folder', '#', NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `sisfour_dev_v2`.`menus`
  WHERE `nama_menu`='Dokumen Siswa'
    AND `parent_id` IS NULL
);

INSERT INTO `sisfour_dev_v2`.`menus`
  (`nama_menu`, `parent_id`, `urutan`, `icon`, `link`, `created_at`, `updated_at`)
SELECT 'Data Dokumen', p.id, 1, 'bx bx-file', 'dokumen-siswa', NOW(), NOW()
FROM `sisfour_dev_v2`.`menus` p
WHERE p.nama_menu='Dokumen Siswa'
  AND p.parent_id IS NULL
  AND NOT EXISTS (
    SELECT 1 FROM `sisfour_dev_v2`.`menus`
    WHERE `link`='dokumen-siswa'
  );

INSERT INTO `sisfour_dev_v2`.`menus`
  (`nama_menu`, `parent_id`, `urutan`, `icon`, `link`, `created_at`, `updated_at`)
SELECT 'Bulk Import', p.id, 2, 'bx bx-import', 'dokumen-siswa/import', NOW(), NOW()
FROM `sisfour_dev_v2`.`menus` p
WHERE p.nama_menu='Dokumen Siswa'
  AND p.parent_id IS NULL
  AND NOT EXISTS (
    SELECT 1 FROM `sisfour_dev_v2`.`menus`
    WHERE `link`='dokumen-siswa/import'
  );

INSERT INTO `sisfour_dev_v2`.`menus`
  (`nama_menu`, `parent_id`, `urutan`, `icon`, `link`, `created_at`, `updated_at`)
SELECT 'Dokumen Saya', NULL, 8, 'bx bx-file-find', 'dokumen-saya', NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `sisfour_dev_v2`.`menus`
  WHERE `link`='dokumen-saya'
);

INSERT INTO `sisfour_dev_v2`.`role_menus`
  (`role`, `id_menu`, `tampil`)
SELECT r.role, m.id, 1
FROM (
  SELECT 'admin' role
  UNION ALL
  SELECT 'operator'
) r
JOIN `sisfour_dev_v2`.`menus` m
  ON (
    (m.nama_menu='Dokumen Siswa' AND m.parent_id IS NULL)
    OR m.link IN ('dokumen-siswa','dokumen-siswa/import')
  )
WHERE NOT EXISTS (
  SELECT 1
  FROM `sisfour_dev_v2`.`role_menus` rm
  WHERE rm.role=r.role
    AND rm.id_menu=m.id
);

INSERT INTO `sisfour_dev_v2`.`role_menus`
  (`role`, `id_menu`, `tampil`)
SELECT 'siswa', m.id, 1
FROM `sisfour_dev_v2`.`menus` m
WHERE m.link='dokumen-saya'
  AND NOT EXISTS (
    SELECT 1
    FROM `sisfour_dev_v2`.`role_menus` rm
    WHERE rm.role='siswa'
      AND rm.id_menu=m.id
  );

SHOW CREATE TABLE `sisfour_dev_v2`.`dokumen_siswa`;
SHOW CREATE TABLE `sisfour_dev_v2`.`dokumen_siswa_import_batch`;
SHOW CREATE TABLE `sisfour_dev_v2`.`dokumen_siswa_access_log`;
SHOW CREATE TABLE `sisfour_dev_v2`.`dokumen_siswa_delete_log`;

SELECT permission_key
FROM `sisfour_dev_v2`.`permissions`
WHERE permission_key LIKE 'dokumen_siswa.%'
ORDER BY permission_key;

SELECT role, id_permission, scope
FROM `sisfour_dev_v2`.`role_permissions`
WHERE id_permission IN (
  SELECT id
  FROM `sisfour_dev_v2`.`permissions`
  WHERE permission_key LIKE 'dokumen_siswa.%'
)
ORDER BY role, id_permission;

SELECT id, nama_menu, parent_id, link
FROM `sisfour_dev_v2`.`menus`
WHERE link IN ('dokumen-siswa','dokumen-siswa/import','dokumen-saya')
   OR nama_menu='Dokumen Siswa';
