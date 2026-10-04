-- SisisFour G3.10A — BK Group Recording
-- Target: LOCALHOST / sisfour_dev_v2
-- Tanggal: 2026-10-04
-- Jalankan satu kali setelah source G3.10A dipull.

CREATE TABLE IF NOT EXISTS `sisfour_dev_v2`.`catatan_kasus_kelompok` (
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
  CONSTRAINT `fk_ckk_tahun_g310` FOREIGN KEY (`id_tahun`) REFERENCES `sisfour_dev_v2`.`tahun_ajaran` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ckk_pelanggaran_g310` FOREIGN KEY (`id_pelanggaran`) REFERENCES `sisfour_dev_v2`.`ref_pelanggaran` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ckk_created_by_g310` FOREIGN KEY (`created_by`) REFERENCES `sisfour_dev_v2`.`users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ckk_updated_by_g310` FOREIGN KEY (`updated_by`) REFERENCES `sisfour_dev_v2`.`users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `sisfour_dev_v2`.`catatan_kasus`
  ADD COLUMN IF NOT EXISTS `id_kelompok` INT(10) UNSIGNED NULL AFTER `id_tahun`;

ALTER TABLE `sisfour_dev_v2`.`catatan_kasus`
  ADD INDEX IF NOT EXISTS `idx_catatan_kasus_kelompok` (`id_kelompok`);

ALTER TABLE `sisfour_dev_v2`.`catatan_kasus`
  ADD CONSTRAINT `fk_catatan_kasus_kelompok_g310`
  FOREIGN KEY (`id_kelompok`) REFERENCES `sisfour_dev_v2`.`catatan_kasus_kelompok` (`id`)
  ON DELETE RESTRICT ON UPDATE CASCADE;

CREATE TABLE IF NOT EXISTS `sisfour_dev_v2`.`konseling_kelompok` (
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
  CONSTRAINT `fk_kk_tahun_g310` FOREIGN KEY (`id_tahun`) REFERENCES `sisfour_dev_v2`.`tahun_ajaran` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_kk_created_by_g310` FOREIGN KEY (`created_by`) REFERENCES `sisfour_dev_v2`.`users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_kk_updated_by_g310` FOREIGN KEY (`updated_by`) REFERENCES `sisfour_dev_v2`.`users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `sisfour_dev_v2`.`konseling_kelompok_anggota` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_konseling_kelompok` INT(10) UNSIGNED NOT NULL,
  `id_siswa` INT(10) UNSIGNED NOT NULL,
  `id_kelas` INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kka_group_siswa` (`id_konseling_kelompok`, `id_siswa`),
  KEY `idx_kka_siswa` (`id_siswa`),
  KEY `idx_kka_kelas` (`id_kelas`),
  CONSTRAINT `fk_kka_group_g310` FOREIGN KEY (`id_konseling_kelompok`) REFERENCES `sisfour_dev_v2`.`konseling_kelompok` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_kka_siswa_g310` FOREIGN KEY (`id_siswa`) REFERENCES `sisfour_dev_v2`.`siswa` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_kka_kelas_g310` FOREIGN KEY (`id_kelas`) REFERENCES `sisfour_dev_v2`.`kelas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `sisfour_dev_v2`.`tindak_lanjut_konseling_kelompok` (
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
  KEY `idx_tlkk_group_tanggal` (`id_konseling_kelompok`, `tanggal`, `id`),
  KEY `idx_tlkk_status_berikutnya` (`status`, `tanggal_berikutnya`),
  CONSTRAINT `fk_tlkk_group_g310` FOREIGN KEY (`id_konseling_kelompok`) REFERENCES `sisfour_dev_v2`.`konseling_kelompok` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_tlkk_created_by_g310` FOREIGN KEY (`created_by`) REFERENCES `sisfour_dev_v2`.`users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tlkk_updated_by_g310` FOREIGN KEY (`updated_by`) REFERENCES `sisfour_dev_v2`.`users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SHOW CREATE TABLE `sisfour_dev_v2`.`catatan_kasus_kelompok`;
SHOW CREATE TABLE `sisfour_dev_v2`.`catatan_kasus`;
SHOW CREATE TABLE `sisfour_dev_v2`.`konseling_kelompok`;
SHOW CREATE TABLE `sisfour_dev_v2`.`konseling_kelompok_anggota`;
SHOW CREATE TABLE `sisfour_dev_v2`.`tindak_lanjut_konseling_kelompok`;
