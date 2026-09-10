-- =====================================================================
-- SisisFour Phase 3.2 - VERIFY ONLY
-- Baseline aplikasi : main @ c05466738012ea2da852fa3e878b6bbb897d6607
-- Baseline database : sisfour_dev_v2 (29).sql / MariaDB 10.4.x
-- Tanggal            : 09 September 2026
-- =====================================================================
--
-- Script ini READ-ONLY terhadap data/schema.
-- Tidak ada INSERT/UPDATE/DELETE/ALTER/TRUNCATE/DROP.
-- Tujuan: memastikan live DB masih memiliki kontrak Phase 3.1 yang tidak
-- selalu terlihat pada export phpMyAdmin 5.2.1.
-- =====================================================================

USE `sisfour_dev_v2`;

SELECT DATABASE() AS `database_aktif`, VERSION() AS `versi_mariadb`;

-- 1. Session schema runtime.
SHOW CREATE TABLE `ci_sessions`;

-- 2. Daftar CHECK constraint Personalia menurut metadata MariaDB.
SELECT
    `TABLE_NAME`,
    `CONSTRAINT_NAME`,
    `CONSTRAINT_TYPE`
FROM `information_schema`.`TABLE_CONSTRAINTS`
WHERE `CONSTRAINT_SCHEMA` = DATABASE()
  AND `CONSTRAINT_TYPE` = 'CHECK'
  AND `CONSTRAINT_NAME` IN (
      'chk_rp_owner',
      'chk_rpen_owner',
      'chk_rpen_periode',
      'chk_rpk_owner',
      'chk_dp_owner'
  )
ORDER BY `TABLE_NAME`, `CONSTRAINT_NAME`;

-- Expected: 5 rows.

-- 3. Data precheck. Semua jumlah_invalid wajib 0.
SELECT 'riwayat_pendidikan_owner_invalid' AS `check_name`, COUNT(*) AS `jumlah_invalid`
FROM `riwayat_pendidikan`
WHERE NOT (
    (`id_guru` IS NOT NULL AND `id_pegawai` IS NULL)
    OR
    (`id_guru` IS NULL AND `id_pegawai` IS NOT NULL)
)
UNION ALL
SELECT 'riwayat_penugasan_owner_invalid', COUNT(*)
FROM `riwayat_penugasan`
WHERE NOT (
    (`id_guru` IS NOT NULL AND `id_pegawai` IS NULL)
    OR
    (`id_guru` IS NULL AND `id_pegawai` IS NOT NULL)
)
UNION ALL
SELECT 'riwayat_penugasan_periode_invalid', COUNT(*)
FROM `riwayat_penugasan`
WHERE `tanggal_selesai` IS NOT NULL
  AND `tanggal_selesai` < `tanggal_mulai`
UNION ALL
SELECT 'riwayat_pangkat_owner_invalid', COUNT(*)
FROM `riwayat_pangkat`
WHERE NOT (
    (`id_guru` IS NOT NULL AND `id_pegawai` IS NULL)
    OR
    (`id_guru` IS NULL AND `id_pegawai` IS NOT NULL)
)
UNION ALL
SELECT 'dokumen_personalia_owner_invalid', COUNT(*)
FROM `dokumen_personalia`
WHERE NOT (
    (`id_guru` IS NOT NULL AND `id_pegawai` IS NULL)
    OR
    (`id_guru` IS NULL AND `id_pegawai` IS NOT NULL)
);

-- 4. SHOW CREATE adalah verifikasi final karena menampilkan definisi live.
SHOW CREATE TABLE `riwayat_pendidikan`;
SHOW CREATE TABLE `riwayat_penugasan`;
SHOW CREATE TABLE `riwayat_pangkat`;
SHOW CREATE TABLE `dokumen_personalia`;

-- Expected:
-- ci_sessions.timestamp = datetime NOT NULL DEFAULT current_timestamp()
-- chk_rp_owner
-- chk_rpen_owner
-- chk_rpen_periode
-- chk_rpk_owner
-- chk_dp_owner
