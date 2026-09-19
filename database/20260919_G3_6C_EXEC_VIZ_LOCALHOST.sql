-- G3.6C Executive Visualization & EWS Signage — LOCALHOST
-- Baseline source: main @ f6f30ceaf070f342d609c322905ee77dc33f3e6f
-- Baseline DB post-G3.6B: 45 tables / 66 permissions / 223 role_permissions / 50 menus / 173 role_menus.
-- IMPORTANT:
--   1) Jalankan hanya di localhost setelah source branch G3.6C dipull.
--   2) Tidak ada tabel baru dan tidak ada perubahan enum role.
--   3) Hosting SQL dibuat terpisah setelah local SQL/UAT/post-SQL dump PASS + fresh hosting dump audit.

-- 0) Lock explicit localhost database context.
USE `sisfour_dev_v2`;
SELECT DATABASE() AS active_database;

-- 1) Capability Statistik terpisah. Read-only untuk Admin / Operator / Pimpinan.
INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'statistik.view', 'Lihat Statistik', 'Statistik', 'SEMUA'
WHERE NOT EXISTS (
  SELECT 1 FROM `permissions` WHERE `permission_key`='statistik.view'
);

INSERT INTO `permissions` (`permission_key`, `nama`, `modul`, `scope_didukung`)
SELECT 'statistik.export_pdf', 'Export PDF Statistik', 'Statistik', 'SEMUA'
WHERE NOT EXISTS (
  SELECT 1 FROM `permissions` WHERE `permission_key`='statistik.export_pdf'
);

INSERT INTO `role_permissions` (`role`, `id_permission`, `scope`)
SELECT map.role, p.id, 'SEMUA'
FROM (
  SELECT 'admin' role, 'statistik.view' permission_key
  UNION ALL SELECT 'operator','statistik.view'
  UNION ALL SELECT 'pimpinan','statistik.view'
  UNION ALL SELECT 'admin','statistik.export_pdf'
  UNION ALL SELECT 'operator','statistik.export_pdf'
  UNION ALL SELECT 'pimpinan','statistik.export_pdf'
) map
JOIN `permissions` p ON p.permission_key=map.permission_key
WHERE NOT EXISTS (
  SELECT 1
  FROM `role_permissions` rp
  WHERE rp.role=map.role
    AND rp.id_permission=p.id
    AND rp.scope='SEMUA'
);

-- 2) Top-level menu Statistik. Urutan=5 agar muncul setelah Laporan tanpa menggeser menu existing.
SET @statistik_repair_id := (
  SELECT COALESCE(MAX(`id`),0)+1 FROM `menus` WHERE `id`<>0
);
UPDATE `menus`
SET `id`=@statistik_repair_id
WHERE `id`=0
  AND `nama_menu`='Statistik'
  AND `parent_id` IS NULL
  AND `link`='statistik';

SET @statistik_new_id := (SELECT COALESCE(MAX(`id`),0)+1 FROM `menus`);
INSERT INTO `menus`
  (`id`,`nama_menu`,`parent_id`,`urutan`,`icon`,`link`,`created_at`,`updated_at`)
SELECT
  @statistik_new_id,'Statistik',NULL,5,'bx bx-line-chart','statistik',NOW(),NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `menus` WHERE `link`='statistik'
);

INSERT INTO `role_menus` (`role`,`id_menu`,`tampil`)
SELECT map.role,m.id,1
FROM (
  SELECT 'admin' role
  UNION ALL SELECT 'operator'
  UNION ALL SELECT 'pimpinan'
) map
JOIN `menus` m ON m.link='statistik'
WHERE NOT EXISTS (
  SELECT 1
  FROM `role_menus` rm
  WHERE rm.role=map.role AND rm.id_menu=m.id
);

-- Verification read-only.
SELECT COUNT(*) AS total_tables
FROM information_schema.tables
WHERE table_schema=DATABASE() AND table_type='BASE TABLE';

SELECT COUNT(*) AS total_permissions FROM permissions;
SELECT COUNT(*) AS total_role_permissions FROM role_permissions;
SELECT COUNT(*) AS total_menus FROM menus;
SELECT COUNT(*) AS total_role_menus FROM role_menus;

SELECT p.permission_key, p.modul, p.scope_didukung, rp.role, rp.scope
FROM permissions p
LEFT JOIN role_permissions rp ON rp.id_permission=p.id
WHERE p.permission_key IN ('statistik.view','statistik.export_pdf')
ORDER BY p.permission_key,rp.role;

SELECT m.id,m.nama_menu,m.parent_id,m.urutan,m.icon,m.link,rm.role,rm.tampil
FROM menus m
LEFT JOIN role_menus rm ON rm.id_menu=m.id
WHERE m.link='statistik'
ORDER BY rm.role;

SELECT COUNT(*) AS statistik_permission_count
FROM permissions
WHERE permission_key IN ('statistik.view','statistik.export_pdf');

SELECT COUNT(*) AS statistik_role_permission_count
FROM role_permissions rp
JOIN permissions p ON p.id=rp.id_permission
WHERE p.permission_key IN ('statistik.view','statistik.export_pdf');

SELECT COUNT(*) AS statistik_menu_count
FROM menus
WHERE link='statistik';

SELECT COUNT(*) AS statistik_role_menu_count
FROM role_menus rm
JOIN menus m ON m.id=rm.id_menu
WHERE m.link='statistik';

-- Expected post-SQL:
-- tables=45
-- permissions=68
-- role_permissions=229
-- menus=51
-- role_menus=176
-- statistik_permission_count=2
-- statistik_role_permission_count=6
-- statistik_menu_count=1
-- statistik_role_menu_count=3
