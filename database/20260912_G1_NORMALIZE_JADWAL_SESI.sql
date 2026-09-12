-- SISFOUR DEV V2 - G1/F13 blocker
-- PRECHECK ONLY. Execute the UPDATE block manually after reviewing its output.
-- Active year only; inactive/historical schedules are not touched.

SET @id_tahun := (
    SELECT id FROM tahun_ajaran
    WHERE status_aktif = 1 AND deleted_at IS NULL
    ORDER BY id DESC LIMIT 1
);

-- Must return 0 rows before applying normalization.
SELECT id_kelas, hari, COUNT(*) AS jumlah_jadwal
FROM jadwal_guru
WHERE id_tahun = @id_tahun AND status_jadwal = 'Aktif'
GROUP BY id_kelas, hari
HAVING COUNT(*) < 2;

-- Preview target classification. First schedule = Sesi Awal,
-- last schedule = Sesi Akhir, middle schedules = Non Sesi.
SELECT j.id, j.id_guru, j.id_kelas, j.hari, j.jam_mulai, j.jam_selesai,
       j.sesi AS sesi_lama, target.sesi_baru
FROM jadwal_guru j
JOIN (
    SELECT id,
           CASE
               WHEN rn_awal = 1 THEN 'Sesi Awal'
               WHEN rn_akhir = 1 THEN 'Sesi Akhir'
               ELSE 'Non Sesi'
           END AS sesi_baru
    FROM (
        SELECT id,
               ROW_NUMBER() OVER (
                   PARTITION BY id_kelas, hari
                   ORDER BY jam_mulai ASC, jam_selesai ASC, id ASC
               ) AS rn_awal,
               ROW_NUMBER() OVER (
                   PARTITION BY id_kelas, hari
                   ORDER BY jam_selesai DESC, jam_mulai DESC, id DESC
               ) AS rn_akhir
        FROM jadwal_guru
        WHERE id_tahun = @id_tahun AND status_jadwal = 'Aktif'
    ) ranked
) target ON target.id = j.id
WHERE j.sesi <> target.sesi_baru
ORDER BY FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'),
         j.id_kelas, j.jam_mulai, j.id;

-- APPLY (run after backup + PRECHECK)
--
-- START TRANSACTION;
-- UPDATE jadwal_guru j
-- JOIN (
--     SELECT id,
--            CASE
--                WHEN rn_awal = 1 THEN 'Sesi Awal'
--                WHEN rn_akhir = 1 THEN 'Sesi Akhir'
--                ELSE 'Non Sesi'
--            END AS sesi_baru
--     FROM (
--         SELECT id,
--                ROW_NUMBER() OVER (
--                    PARTITION BY id_kelas, hari
--                    ORDER BY jam_mulai ASC, jam_selesai ASC, id ASC
--                ) AS rn_awal,
--                ROW_NUMBER() OVER (
--                    PARTITION BY id_kelas, hari
--                    ORDER BY jam_selesai DESC, jam_mulai DESC, id DESC
--                ) AS rn_akhir
--         FROM jadwal_guru
--         WHERE id_tahun = @id_tahun AND status_jadwal = 'Aktif'
--     ) ranked
-- ) target ON target.id = j.id
-- SET j.sesi = target.sesi_baru
-- WHERE j.id_tahun = @id_tahun
--   AND j.status_jadwal = 'Aktif'
--   AND j.sesi <> target.sesi_baru;
--
-- SELECT ROW_COUNT() AS rows_changed;
--
-- SELECT sesi, COUNT(*) AS jumlah
-- FROM jadwal_guru
-- WHERE id_tahun = @id_tahun AND status_jadwal = 'Aktif'
-- GROUP BY sesi;
--
-- Expected from hosting dump 12 Sep 2026:
-- rows_changed = 303
-- Sesi Awal = 312
-- Sesi Akhir = 312
-- Non Sesi = 629
--
-- COMMIT;
-- Use ROLLBACK instead of COMMIT if verification differs.
