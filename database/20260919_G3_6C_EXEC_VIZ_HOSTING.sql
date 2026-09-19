-- G3.6C Executive Visualization & EWS Signage — HOSTING
-- Prepared from fresh pre-SQL hosting dump: u473908839_sisfour2026 (16).sql
-- Source baseline main: f6f30ceaf070f342d609c322905ee77dc33f3e6f
-- Feature branch: feat/g3-6c-exec-viz-signage-20260919
--
-- Fresh hosting baseline audit:
--   physical_tables=45 (informational; framework/internal tables are environment-sensitive)
--   permissions=66
--   role_permissions=223
--   menus=50
--   role_menus=173
--
-- G3.6C persistence delta:
--   new tables=0
--   +2 permissions
--   +6 role_permissions
--   +1 menu
--   +3 role_menus
--
-- IMPORTANT:
--   1) Jalankan HANYA di hosting setelah approval eksekusi eksplisit user.
--   2) Script ini tidak membawa data UAT localhost.
--   3) Script ini tidak mengubah tabel/data domain BK, UKS, PTSP, Presensi, atau master.
--   4) Physical BASE TABLE count hanya informational; jangan jadikan framework table
--      seperti ci_sessions/migrations sebagai invariant G3.6C.
--   5) Source deployment adalah gate terpisah dan tidak dilakukan oleh SQL ini.

-- 0) Preflight read-only. Semua mutation di bawah schema-qualified sehingga active DB
-- phpMyAdmin tidak menjadi sumber salah-target.
SELECT DATABASE() AS phpmyadmin_active_database;
SELECT 'u473908839_sisfour2026' AS target_database;

SELECT COUNT(*) AS physical_tables_before
FROM information_schema.tables
WHERE table_schema='u473908839_sisfour2026'
  AND table_type='BASE TABLE';

SELECT COUNT(*) AS permissions_before
FROM `u473908839_sisfour2026`.`permissions`;

SELECT COUNT(*) AS role_permissions_before
FROM `u473908839_sisfour2026`.`role_permissions`;

SELECT COUNT(*) AS menus_before
FROM `u473908839_sisfour2026`.`menus`;

SELECT COUNT(*) AS role_menus_before
FROM `u473908839_sisfour2026`.`role_menus`;

SELECT COUNT(*) AS existing_statistik_permissions
FROM `u473908839_sisfour2026`.`permissions`
WHERE `permission_key` IN ('statistik.view','statistik.export_pdf');

SELECT COUNT(*) AS existing_statistik_menu
FROM `u473908839_sisfour2026`.`menus`
WHERE `link`='statistik';

-- 1) Capability Statistik: Admin / Operator / Pimpinan, scope SEMUA.
INSERT INTO `u473908839_sisfour2026`.`permissions`
  (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'statistik.view', 'Lihat Statistik', 'Statistik', 'SEMUA'
WHERE NOT EXISTS (
  SELECT 1
  FROM `u473908839_sisfour2026`.`permissions`
  WHERE `permission_key`='statistik.view'
);

INSERT INTO `u473908839_sisfour2026`.`permissions`
  (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'statistik.export_pdf', 'Export PDF Statistik', 'Statistik', 'SEMUA'
WHERE NOT EXISTS (
  SELECT 1
  FROM `u473908839_sisfour2026`.`permissions`
  WHERE `permission_key`='statistik.export_pdf'
);

INSERT INTO `u473908839_sisfour2026`.`role_permissions`
  (`role`, `id_permission`, `scope`)
SELECT map.role, p.id, 'SEMUA'
FROM (
  SELECT 'admin' role, 'statistik.view' permission_key
  UNION ALL SELECT 'operator','statistik.view'
  UNION ALL SELECT 'pimpinan','statistik.view'
  UNION ALL SELECT 'admin','statistik.export_pdf'
  UNION ALL SELECT 'operator','statistik.export_pdf'
  UNION ALL SELECT 'pimpinan','statistik.export_pdf'
) map
JOIN `u473908839_sisfour2026`.`permissions` p
  ON p.permission_key=map.permission_key
WHERE NOT EXISTS (
  SELECT 1
  FROM `u473908839_sisfour2026`.`role_permissions` rp
  WHERE rp.role=map.role
    AND rp.id_permission=p.id
    AND rp.scope='SEMUA'
);

-- 2) Top-level menu Statistik.
-- Repair defensive untuk legacy id=0 hanya jika baris Statistik seperti itu sudah ada.
SET @statistik_repair_id := (
  SELECT COALESCE(MAX(`id`),0)+1
  FROM `u473908839_sisfour2026`.`menus`
  WHERE `id`<>0
);

UPDATE `u473908839_sisfour2026`.`menus`
SET `id`=@statistik_repair_id
WHERE `id`=0
  AND `nama_menu`='Statistik'
  AND `parent_id` IS NULL
  AND `link`='statistik';

SET @statistik_new_id := (
  SELECT COALESCE(MAX(`id`),0)+1
  FROM `u473908839_sisfour2026`.`menus`
);

INSERT INTO `u473908839_sisfour2026`.`menus`
  (`id`,`nama_menu`,`parent_id`,`urutan`,`icon`,`link`,`created_at`,`updated_at`)
SELECT
  @statistik_new_id,'Statistik',NULL,5,'bx bx-line-chart','statistik',NOW(),NOW()
WHERE NOT EXISTS (
  SELECT 1
  FROM `u473908839_sisfour2026`.`menus`
  WHERE `link`='statistik'
);

INSERT INTO `u473908839_sisfour2026`.`role_menus`
  (`role`,`id_menu`,`tampil`)
SELECT map.role,m.id,1
FROM (
  SELECT 'admin' role
  UNION ALL SELECT 'operator'
  UNION ALL SELECT 'pimpinan'
) map
JOIN `u473908839_sisfour2026`.`menus` m
  ON m.link='statistik'
WHERE NOT EXISTS (
  SELECT 1
  FROM `u473908839_sisfour2026`.`role_menus` rm
  WHERE rm.role=map.role
    AND rm.id_menu=m.id
);

-- 3) Verification read-only.
-- Expected business/RBAC post-SQL:
--   new G3.6C tables=0
--   permissions=68
--   role_permissions=229
--   menus=51
--   role_menus=176
--   statistik_permission_count=2
--   statistik_role_permission_count=6
--   statistik_menu_count=1
--   statistik_role_menu_count=3
-- Physical table count should not be used as a cross-environment invariant.

SELECT COUNT(*) AS physical_tables_after
FROM information_schema.tables
WHERE table_schema='u473908839_sisfour2026'
  AND table_type='BASE TABLE';

SELECT COUNT(*) AS total_permissions
FROM `u473908839_sisfour2026`.`permissions`;

SELECT COUNT(*) AS total_role_permissions
FROM `u473908839_sisfour2026`.`role_permissions`;

SELECT COUNT(*) AS total_menus
FROM `u473908839_sisfour2026`.`menus`;

SELECT COUNT(*) AS total_role_menus
FROM `u473908839_sisfour2026`.`role_menus`;

SELECT p.permission_key, p.modul, p.scope_didukung, rp.role, rp.scope
FROM `u473908839_sisfour2026`.`permissions` p
LEFT JOIN `u473908839_sisfour2026`.`role_permissions` rp
  ON rp.id_permission=p.id
WHERE p.permission_key IN ('statistik.view','statistik.export_pdf')
ORDER BY p.permission_key,rp.role;

SELECT m.id,m.nama_menu,m.parent_id,m.urutan,m.icon,m.link,rm.role,rm.tampil
FROM `u473908839_sisfour2026`.`menus` m
LEFT JOIN `u473908839_sisfour2026`.`role_menus` rm
  ON rm.id_menu=m.id
WHERE m.link='statistik'
ORDER BY rm.role;

SELECT COUNT(*) AS statistik_permission_count
FROM `u473908839_sisfour2026`.`permissions`
WHERE permission_key IN ('statistik.view','statistik.export_pdf');

SELECT COUNT(*) AS statistik_role_permission_count
FROM `u473908839_sisfour2026`.`role_permissions` rp
JOIN `u473908839_sisfour2026`.`permissions` p
  ON p.id=rp.id_permission
WHERE p.permission_key IN ('statistik.view','statistik.export_pdf');

SELECT COUNT(*) AS statistik_menu_count
FROM `u473908839_sisfour2026`.`menus`
WHERE link='statistik';

SELECT COUNT(*) AS statistik_role_menu_count
FROM `u473908839_sisfour2026`.`role_menus` rm
JOIN `u473908839_sisfour2026`.`menus` m
  ON m.id=rm.id_menu
WHERE m.link='statistik';

-- Expected 0 rows: Statistik permission tidak boleh diberikan ke role lain.
SELECT rp.role, p.permission_key, rp.scope
FROM `u473908839_sisfour2026`.`role_permissions` rp
JOIN `u473908839_sisfour2026`.`permissions` p
  ON p.id=rp.id_permission
WHERE p.permission_key IN ('statistik.view','statistik.export_pdf')
  AND rp.role NOT IN ('admin','operator','pimpinan');

-- Expected 0 rows: menu Statistik tidak boleh dipetakan ke role lain.
SELECT rm.role, m.link, rm.tampil
FROM `u473908839_sisfour2026`.`role_menus` rm
JOIN `u473908839_sisfour2026`.`menus` m
  ON m.id=rm.id_menu
WHERE m.link='statistik'
  AND rm.role NOT IN ('admin','operator','pimpinan');
