-- SisisFour G3.3.1 closure/rework
-- Target: LOCALHOST / sisfour_dev_v2
-- Tanggal: 2026-09-17
--
-- Tujuan:
-- 1) snapshot Tahun Ajaran pada Catatan Pelanggaran dan Prestasi;
-- 2) histori Tindak Lanjut Konseling 1:N;
-- 3) tidak menghapus/memindah data existing;
-- 4) tidak memakai information_schema;
-- 5) tidak membuat SQL hosting pada tahap ini.
--
-- CATATAN RERUN:
-- Kolom/index memakai IF NOT EXISTS. FK bernama di bawah adalah delta satu kali;
-- jangan rerun seluruh file setelah berhasil tanpa audit SHOW CREATE TABLE.

-- -----------------------------------------------------------------------------
-- A. Snapshot Tahun Ajaran untuk tabel periodik BK
-- -----------------------------------------------------------------------------
ALTER TABLE `sisfour_dev_v2`.`catatan_kasus`
  ADD COLUMN IF NOT EXISTS `id_tahun` INT(10) UNSIGNED NULL AFTER `id`;

ALTER TABLE `sisfour_dev_v2`.`catatan_kasus`
  ADD INDEX IF NOT EXISTS `idx_catatan_kasus_tahun_tanggal` (`id_tahun`, `tanggal`);

ALTER TABLE `sisfour_dev_v2`.`catatan_kasus`
  ADD CONSTRAINT `fk_catatan_kasus_tahun_g331`
  FOREIGN KEY (`id_tahun`)
  REFERENCES `sisfour_dev_v2`.`tahun_ajaran` (`id`)
  ON DELETE RESTRICT ON UPDATE CASCADE;

ALTER TABLE `sisfour_dev_v2`.`catatan_prestasi`
  ADD COLUMN IF NOT EXISTS `id_tahun` INT(10) UNSIGNED NULL AFTER `id`;

ALTER TABLE `sisfour_dev_v2`.`catatan_prestasi`
  ADD INDEX IF NOT EXISTS `idx_catatan_prestasi_tahun_tanggal` (`id_tahun`, `tanggal`);

ALTER TABLE `sisfour_dev_v2`.`catatan_prestasi`
  ADD CONSTRAINT `fk_catatan_prestasi_tahun_g331`
  FOREIGN KEY (`id_tahun`)
  REFERENCES `sisfour_dev_v2`.`tahun_ajaran` (`id`)
  ON DELETE RESTRICT ON UPDATE CASCADE;

-- Backfill hanya bila histori membership siswa menunjuk tepat satu Tahun Ajaran.
-- Record ambigu tetap NULL; script tidak menebak periode dari tanggal karena master
-- tahun_ajaran tidak menyimpan rentang tanggal mulai/selesai.
UPDATE `sisfour_dev_v2`.`catatan_kasus` ck
SET ck.`id_tahun` = (
  SELECT MIN(ak.`id_tahun`)
  FROM `sisfour_dev_v2`.`anggota_kelas` ak
  WHERE ak.`id_siswa` = ck.`id_siswa`
)
WHERE ck.`id_tahun` IS NULL
  AND 1 = (
    SELECT COUNT(DISTINCT ak2.`id_tahun`)
    FROM `sisfour_dev_v2`.`anggota_kelas` ak2
    WHERE ak2.`id_siswa` = ck.`id_siswa`
  );

UPDATE `sisfour_dev_v2`.`catatan_prestasi` cp
SET cp.`id_tahun` = (
  SELECT MIN(ak.`id_tahun`)
  FROM `sisfour_dev_v2`.`anggota_kelas` ak
  WHERE ak.`id_siswa` = cp.`id_siswa`
)
WHERE cp.`id_tahun` IS NULL
  AND 1 = (
    SELECT COUNT(DISTINCT ak2.`id_tahun`)
    FROM `sisfour_dev_v2`.`anggota_kelas` ak2
    WHERE ak2.`id_siswa` = cp.`id_siswa`
  );

-- -----------------------------------------------------------------------------
-- B. Tindak Lanjut Konseling 1:N
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sisfour_dev_v2`.`tindak_lanjut_konseling_bk` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_konseling` INT(10) UNSIGNED NOT NULL,
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
  KEY `idx_tlk_konseling_tanggal` (`id_konseling`, `tanggal`, `id`),
  KEY `idx_tlk_status_berikutnya` (`status`, `tanggal_berikutnya`),
  KEY `idx_tlk_created_by` (`created_by`),
  KEY `idx_tlk_updated_by` (`updated_by`),
  CONSTRAINT `fk_tlk_konseling`
    FOREIGN KEY (`id_konseling`)
    REFERENCES `sisfour_dev_v2`.`konseling_bk` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_tlk_created_by`
    FOREIGN KEY (`created_by`)
    REFERENCES `sisfour_dev_v2`.`users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tlk_updated_by`
    FOREIGN KEY (`updated_by`)
    REFERENCES `sisfour_dev_v2`.`users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- C. Verification
-- -----------------------------------------------------------------------------
SHOW CREATE TABLE `sisfour_dev_v2`.`catatan_kasus`;
SHOW CREATE TABLE `sisfour_dev_v2`.`catatan_prestasi`;
SHOW CREATE TABLE `sisfour_dev_v2`.`tindak_lanjut_konseling_bk`;

SELECT COUNT(*) AS `catatan_kasus_tahun_belum_terpetakan`
FROM `sisfour_dev_v2`.`catatan_kasus`
WHERE `id_tahun` IS NULL;

SELECT COUNT(*) AS `prestasi_tahun_belum_terpetakan`
FROM `sisfour_dev_v2`.`catatan_prestasi`
WHERE `id_tahun` IS NULL;

SELECT COUNT(*) AS `jumlah_tindak_lanjut_konseling`
FROM `sisfour_dev_v2`.`tindak_lanjut_konseling_bk`;
