-- SisisFour G3.10A — BK Group Recording
-- Target: HOSTING / u473908839_sisfour2026
-- Baseline audit: dump hosting 2026-10-04 09:05
-- Tanggal penyusunan: 2026-10-04
-- IMPORTANT:
-- 1) File ini MENYIAPKAN delta schema hosting; jangan dieksekusi tanpa approval eksplisit.
-- 2) Jalankan G3.10A sebelum G3.10B.
-- 3) Designed to be safe on the audited hosting baseline and safe to resume after partial execution.

USE `u473908839_sisfour2026`;

CREATE TABLE IF NOT EXISTS `u473908839_sisfour2026`.`catatan_kasus_kelompok` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_tahun` INT(10) UNSIGNED NOT NULL,
  `tanggal` DATE NOT NULL,
  `id_pelanggaran` INT(10) UNSIGNED NOT NULL,
  `keterangan` TEXT DEFAULT NULL,
  `created_by` INT(10) UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_by` INT(10) UNSIGNED DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ckk_tahun_tanggal` (`id_tahun`, `tanggal`),
  KEY `idx_ckk_pelanggaran` (`id_pelanggaran`),
  KEY `idx_ckk_created_by` (`created_by`),
  CONSTRAINT `fk_ckk_tahun_g310`
    FOREIGN KEY (`id_tahun`)
    REFERENCES `u473908839_sisfour2026`.`tahun_ajaran` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ckk_pelanggaran_g310`
    FOREIGN KEY (`id_pelanggaran`)
    REFERENCES `u473908839_sisfour2026`.`ref_pelanggaran` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ckk_created_by_g310`
    FOREIGN KEY (`created_by`)
    REFERENCES `u473908839_sisfour2026`.`users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ckk_updated_by_g310`
    FOREIGN KEY (`updated_by`)
    REFERENCES `u473908839_sisfour2026`.`users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `u473908839_sisfour2026`.`catatan_kasus`
  ADD COLUMN IF NOT EXISTS `id_kelompok`
  INT(10) UNSIGNED NULL AFTER `id_tahun`;

ALTER TABLE `u473908839_sisfour2026`.`catatan_kasus`
  ADD INDEX IF NOT EXISTS `idx_catatan_kasus_kelompok`
  (`id_kelompok`);

SET @g310a_fk_kasus_group := (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA='u473908839_sisfour2026'
    AND TABLE_NAME='catatan_kasus'
    AND CONSTRAINT_NAME='fk_catatan_kasus_kelompok_g310'
    AND CONSTRAINT_TYPE='FOREIGN KEY'
);

SET @g310a_sql := IF(
  @g310a_fk_kasus_group > 0,
  'SELECT ''fk_catatan_kasus_kelompok_g310 already exists''',
  'ALTER TABLE `u473908839_sisfour2026`.`catatan_kasus`
     ADD CONSTRAINT `fk_catatan_kasus_kelompok_g310`
     FOREIGN KEY (`id_kelompok`)
     REFERENCES `u473908839_sisfour2026`.`catatan_kasus_kelompok` (`id`)
     ON DELETE RESTRICT ON UPDATE CASCADE'
);

PREPARE g310a_stmt FROM @g310a_sql;
EXECUTE g310a_stmt;
DEALLOCATE PREPARE g310a_stmt;

CREATE TABLE IF NOT EXISTS `u473908839_sisfour2026`.`konseling_kelompok` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_tahun` INT(10) UNSIGNED NOT NULL,
  `tanggal` DATE NOT NULL,
  `pertemuan_ke` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `bentuk_layanan` VARCHAR(50) NOT NULL,
  `cara_hadir` VARCHAR(80) NOT NULL,
  `bidang` ENUM('Pribadi','Sosial','Belajar','Karier') NOT NULL,
  `topik` VARCHAR(150) NOT NULL,
  `uraian_masalah` TEXT DEFAULT NULL,
  `hasil_kesepakatan` TEXT DEFAULT NULL,
  `rencana_berikutnya` VARCHAR(100) DEFAULT NULL,
  `tanggal_berikutnya` DATE DEFAULT NULL,
  `status` ENUM('Proses','Selesai') NOT NULL DEFAULT 'Proses',
  `created_by` INT(10) UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_by` INT(10) UNSIGNED DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_kk_tahun_tanggal` (`id_tahun`, `tanggal`),
  KEY `idx_kk_status_berikutnya` (`status`, `tanggal_berikutnya`),
  CONSTRAINT `fk_kk_tahun_g310`
    FOREIGN KEY (`id_tahun`)
    REFERENCES `u473908839_sisfour2026`.`tahun_ajaran` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_kk_created_by_g310`
    FOREIGN KEY (`created_by`)
    REFERENCES `u473908839_sisfour2026`.`users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_kk_updated_by_g310`
    FOREIGN KEY (`updated_by`)
    REFERENCES `u473908839_sisfour2026`.`users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `u473908839_sisfour2026`.`konseling_kelompok_anggota` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_konseling_kelompok` INT(10) UNSIGNED NOT NULL,
  `id_siswa` INT(10) UNSIGNED NOT NULL,
  `id_kelas` INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kka_group_siswa`
    (`id_konseling_kelompok`, `id_siswa`),
  KEY `idx_kka_siswa` (`id_siswa`),
  KEY `idx_kka_kelas` (`id_kelas`),
  CONSTRAINT `fk_kka_group_g310`
    FOREIGN KEY (`id_konseling_kelompok`)
    REFERENCES `u473908839_sisfour2026`.`konseling_kelompok` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_kka_siswa_g310`
    FOREIGN KEY (`id_siswa`)
    REFERENCES `u473908839_sisfour2026`.`siswa` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_kka_kelas_g310`
    FOREIGN KEY (`id_kelas`)
    REFERENCES `u473908839_sisfour2026`.`kelas` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `u473908839_sisfour2026`.`tindak_lanjut_konseling_kelompok` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_konseling_kelompok` INT(10) UNSIGNED NOT NULL,
  `tanggal` DATE NOT NULL,
  `perkembangan` TEXT NOT NULL,
  `hasil_kesepakatan` TEXT DEFAULT NULL,
  `rencana_berikutnya` VARCHAR(100) DEFAULT NULL,
  `tanggal_berikutnya` DATE DEFAULT NULL,
  `status` ENUM('Proses','Selesai') NOT NULL DEFAULT 'Proses',
  `created_by` INT(10) UNSIGNED DEFAULT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_by` INT(10) UNSIGNED DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tlkk_group_tanggal`
    (`id_konseling_kelompok`, `tanggal`, `id`),
  KEY `idx_tlkk_status_berikutnya`
    (`status`, `tanggal_berikutnya`),
  CONSTRAINT `fk_tlkk_group_g310`
    FOREIGN KEY (`id_konseling_kelompok`)
    REFERENCES `u473908839_sisfour2026`.`konseling_kelompok` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_tlkk_created_by_g310`
    FOREIGN KEY (`created_by`)
    REFERENCES `u473908839_sisfour2026`.`users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tlkk_updated_by_g310`
    FOREIGN KEY (`updated_by`)
    REFERENCES `u473908839_sisfour2026`.`users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Verification
SHOW CREATE TABLE `u473908839_sisfour2026`.`catatan_kasus_kelompok`;
SHOW CREATE TABLE `u473908839_sisfour2026`.`catatan_kasus`;
SHOW CREATE TABLE `u473908839_sisfour2026`.`konseling_kelompok`;
SHOW CREATE TABLE `u473908839_sisfour2026`.`konseling_kelompok_anggota`;
SHOW CREATE TABLE `u473908839_sisfour2026`.`tindak_lanjut_konseling_kelompok`;

SELECT
  TABLE_NAME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA='u473908839_sisfour2026'
  AND TABLE_NAME IN (
    'catatan_kasus_kelompok',
    'konseling_kelompok',
    'konseling_kelompok_anggota',
    'tindak_lanjut_konseling_kelompok'
  )
ORDER BY TABLE_NAME;
