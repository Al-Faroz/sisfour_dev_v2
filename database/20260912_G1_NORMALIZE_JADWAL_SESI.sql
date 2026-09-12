-- SISFOUR DEV V2 - G1/F13 blocker
-- Normalisasi sesi untuk tahun ajaran aktif saja.
-- Rule: jadwal pertama per kelas/hari = Sesi Awal,
--       jadwal terakhir per kelas/hari = Sesi Akhir,
--       jadwal di antaranya = Non Sesi.
--
-- WAJIB backup database sebelum APPLY.
-- Dump hosting 12 Sep 2026 sebelum normalisasi menghasilkan target:
-- rows_will_change = 303
-- Sesi Awal = 312
-- Sesi Akhir = 312
-- Non Sesi = 629

SET @id_tahun := (
    SELECT id
    FROM tahun_ajaran
    WHERE status_aktif = 1
      AND deleted_at IS NULL
    ORDER BY id DESC
    LIMIT 1
);

SELECT
    @id_tahun AS id_tahun_aktif,
    nama_tahun,
    semester
FROM tahun_ajaran
WHERE id = @id_tahun;

-- PRECHECK: harus 0 rows.
SELECT
    id_kelas,
    hari,
    COUNT(*) AS jumlah_jadwal
FROM jadwal_guru
WHERE id_tahun = @id_tahun
  AND status_jadwal = 'Aktif'
GROUP BY id_kelas, hari
HAVING COUNT(*) < 2;

DROP TEMPORARY TABLE IF EXISTS tmp_jadwal_sesi_target;

-- Explicit charset/collation mengikuti jadwal_guru.sesi pada hosting.
-- Mencegah #1267 Illegal mix of collations pada komparasi sesi.
CREATE TEMPORARY TABLE tmp_jadwal_sesi_target (
    id INT(10) UNSIGNED NOT NULL,
    sesi_baru ENUM('Sesi Awal', 'Sesi Akhir', 'Non Sesi')
        CHARACTER SET utf8mb4
        COLLATE utf8mb4_general_ci NOT NULL,
    PRIMARY KEY (id)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_general_ci;

INSERT INTO tmp_jadwal_sesi_target (id, sesi_baru)
SELECT
    id,
    CASE
        WHEN rn_awal = 1 THEN 'Sesi Awal'
        WHEN rn_akhir = 1 THEN 'Sesi Akhir'
        ELSE 'Non Sesi'
    END AS sesi_baru
FROM (
    SELECT
        id,
        ROW_NUMBER() OVER (
            PARTITION BY id_kelas, hari
            ORDER BY jam_mulai ASC, jam_selesai ASC, id ASC
        ) AS rn_awal,
        ROW_NUMBER() OVER (
            PARTITION BY id_kelas, hari
            ORDER BY jam_selesai DESC, jam_mulai DESC, id DESC
        ) AS rn_akhir
    FROM jadwal_guru
    WHERE id_tahun = @id_tahun
      AND status_jadwal = 'Aktif'
) ranked;

SELECT COUNT(*) AS rows_will_change
FROM jadwal_guru j
JOIN tmp_jadwal_sesi_target t ON t.id = j.id
WHERE j.id_tahun = @id_tahun
  AND j.status_jadwal = 'Aktif'
  AND j.sesi <> t.sesi_baru;

START TRANSACTION;

UPDATE jadwal_guru j
JOIN tmp_jadwal_sesi_target t ON t.id = j.id
SET j.sesi = t.sesi_baru
WHERE j.id_tahun = @id_tahun
  AND j.status_jadwal = 'Aktif'
  AND j.sesi <> t.sesi_baru;

SELECT ROW_COUNT() AS rows_changed;

SELECT
    sesi,
    COUNT(*) AS jumlah
FROM jadwal_guru
WHERE id_tahun = @id_tahun
  AND status_jadwal = 'Aktif'
GROUP BY sesi
ORDER BY FIELD(sesi, 'Sesi Awal', 'Sesi Akhir', 'Non Sesi');

-- Harus 0 rows.
SELECT
    id_kelas,
    hari,
    SUM(sesi = 'Sesi Awal') AS jumlah_sesi_awal,
    SUM(sesi = 'Sesi Akhir') AS jumlah_sesi_akhir,
    COUNT(*) AS jumlah_jadwal
FROM jadwal_guru
WHERE id_tahun = @id_tahun
  AND status_jadwal = 'Aktif'
GROUP BY id_kelas, hari
HAVING SUM(sesi = 'Sesi Awal') <> 1
    OR SUM(sesi = 'Sesi Akhir') <> 1;

COMMIT;

DROP TEMPORARY TABLE IF EXISTS tmp_jadwal_sesi_target;
