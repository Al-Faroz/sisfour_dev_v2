# Tree Structure — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 19 September 2026

## 1. Root

```text
sisfour_dev_v2/
├── .htaccess
├── index.php
├── spark
├── composer.json
├── app/
├── assets/
├── database/
├── docs/
├── postman/
├── public/
├── tests/
├── uploads/
├── vendor/
└── writable/
```

Project root adalah Web root; `.htaccess` melindungi internal files/folders dan meneruskan request ke CI4.

## 2. Application

```text
app/
├── Config/
├── Controllers/
├── Database/
├── Filters/
├── Helpers/
├── Models/
├── Services/
├── Support/
└── Views/
```

Flow teknis:

```text
Routes
→ Filters
→ Controller
→ Service
→ Model/Query
→ DB
→ View/JSON/File
```

Flow keputusan global mengikuti `docs/00A_GLOBAL_STANDARD_SISFOUR.md`:

```text
Menu/Fitur
→ Use Case
→ SSOT/Domain
→ Access Boundary
→ Capability
→ Scope
→ Period Context
→ Target Validation
→ Business Invariant
→ Persistence
→ Service Boundary
→ Presentation UI
→ Output Channel/API
→ Audit
→ Testing/Regression
→ Docs Sync
→ Deployment Gate
```

## 3. Route Files

```text
app/Config/Routing.php
app/Config/Routes.php
app/Config/RoutesBKFoundation.php
```

`Routing::$routeFiles` mendaftarkan route utama dan route foundation BK. `autoRoute=false`.

G3.4, G3.5, dan G3.6 tidak menambah route baru. G3.6A menambah route UKS pada `Routes.php`. Route PTSP/public statistics API diimplementasikan pada G3.6B.

## 4. Dashboard/BK Source — G3.3.1 + G3.4 + G3.5 + G3.6

Controller:

```text
app/Controllers/Dashboard.php
app/Controllers/BKKasus.php
app/Controllers/BKKonseling.php
app/Controllers/BKKonselingSettings.php
app/Controllers/BKPelanggaran.php
app/Controllers/BKPrestasi.php
```

Models:

```text
app/Models/BKKasusModel.php
app/Models/BKPrestasiModel.php
app/Models/KonselingBkModel.php
app/Models/KonselingBkFollowUpModel.php
```

Services:

```text
app/Services/BkExportService.php
app/Services/BkKonselingFormSettingsService.php
app/Services/BkWorkflowDashboardService.php
app/Services/PimpinanDashboardService.php
app/Services/SiswaDashboardService.php
app/Services/KonselingBkExportService.php
app/Services/KonselingBkService.php
app/Services/PeriodContextService.php
app/Services/PrestasiService.php
app/Services/RoleAwareDashboardService.php
```

`BkWorkflowDashboardService` adalah G3.4 specialization dari `RoleAwareDashboardService`. `PimpinanDashboardService` adalah G3.5 specialization di atasnya untuk experience Pimpinan. `SiswaDashboardService` adalah G3.6 specialization berikutnya dan hanya meng-override payload experience Siswa. Dengan inheritance ini, behavior BK/Pimpinan phase sebelumnya tetap diwarisi tanpa duplikasi.

Views:

```text
app/Views/dashboard_bk.php
app/Views/dashboard_pimpinan.php
app/Views/dashboard_siswa.php
app/Views/bk/kasus.php
app/Views/bk/konseling.php
app/Views/bk/konseling_settings.php
app/Views/bk/pelanggaran.php
app/Views/bk/prestasi.php
```

Feature JS:

```text
assets/js/bk/kasus.js
assets/js/bk/konseling.js
assets/js/bk/konseling-detail-order.js
assets/js/bk/konseling-settings.js
assets/js/bk/pelanggaran.js
assets/js/bk/prestasi.js
```

Reusable period helper:

```text
assets/js/components/active-year-default.js
```

`PeriodContextService` adalah server-side resolver period untuk surface periodik; helper JS tidak menggantikan validasi server. Dashboard G3.4 BK, G3.5 Pimpinan, dan G3.6 Siswa memakai Tahun Ajaran aktif server-side untuk data periodik dan tidak menambah historical selector.

## 5. Konseling Rework Structure

```text
konseling_bk
   1
   └── N tindak_lanjut_konseling_bk
```

Routes follow-up berada di `RoutesBKFoundation.php`; Model follow-up berada di `KonselingBkFollowUpModel.php`; business rule berada di `KonselingBkService.php`.

Tidak ada Controller/route/model delete workflow untuk parent Konseling maupun Tindak Lanjut Konseling pada kontrak saat ini.

G3.4 hanya membaca workflow tersebut untuk KPI dan jadwal follow-up; tidak menambah mutation baru.

## 6. G3.6A Domain Structure — UKS / Kesehatan

SSOT:

```text
docs/17_UKS_KESEHATAN — SisisFour.md
```

Domain implementation:

```text
UKS
├── Data CKG
├── Data UKS
└── Master UKS
```

Controllers:

```text
app/Controllers/UksCkg.php
app/Controllers/UksHarian.php
app/Controllers/UksMaster.php
```

Models:

```text
app/Models/UksCkgModel.php
app/Models/UksKunjunganModel.php
```

Services:

```text
app/Services/UksScopeService.php
app/Services/UksService.php
app/Services/UksExportService.php
app/Services/KesehatanDashboardService.php
```

Views/JS:

```text
app/Views/dashboard_kesehatan.php
app/Views/uks/ckg.php
app/Views/uks/harian.php
app/Views/uks/master.php
assets/js/uks/ckg.js
assets/js/uks/harian.js
assets/js/uks/master.js
```

Role `kesehatan` memakai identity Pegawai. Final data scope UKS untuk Wali berada di `UksScopeService` dan dievaluasi terhadap Tahun Ajaran terpilih.

## 7. Future Domain Structure — PTSP

SSOT target:

```text
docs/18_PTSP — SisisFour.md
```

Domain target:

```text
PTSP authenticated administration
├── Layanan PTSP
├── Polling Kepuasan
└── Pengaduan

PTSP public landing
├── Public Layanan Form
├── Public Polling Form
└── Public Pengaduan Form

Public API
├── Layanan aggregate statistics
├── Polling aggregate statistics
└── Pengaduan aggregate statistics
```

Role `ptsp` memakai identity Pegawai. Source/schema/menu/route/API diimplementasikan pada branch G3.6B.

## 8. Frontend

```text
assets/
├── css/
│   ├── sisfour-ui.css
│   └── sisfour-mobile.css
├── img/
├── js/
│   ├── components/
│   ├── bk/
│   ├── kartu/
│   ├── master/
│   ├── manajemen_siswa/
│   ├── personalia/
│   ├── presensi/
│   ├── profile/
│   ├── settings/
│   ├── csrf-fetch.js
│   └── main.js
└── vendor/
```

Reusable UI foundation berada di global CSS/component; vendor Sneat/Bootstrap tidak dipatch langsung.

PTSP public landing akan mempunyai presentation khusus, tetapi tetap mengikuti global responsive/loading/error/busy-guard standards.

## 9. Layout Views

```text
app/Views/main.php
app/Views/_header.php
app/Views/_navbar.php
app/Views/_sidebar.php
app/Views/_footer.php
app/Views/_flash.php
app/Views/_scripts.php
```

Responsibility lebih penting daripada nama folder.

## 10. SQL / Database Scripts

G3.2 final:

```text
database/20260915_G3_2_JURNAL_STUDENT_EXCEPTIONS_LOCALHOST.sql
database/20260915_G3_2_JURNAL_STUDENT_EXCEPTIONS_HOSTING.sql
```

G3.3.1 baseline:

```text
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_FIX3_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_HOSTING.sql
```

G3.3.1 rework final:

```text
database/20260917_G3_3_1_BK_PERIOD_YEAR_COUNSELING_FOLLOWUP_LOCALHOST.sql
database/20260917_G3_3_1_BK_PERIOD_YEAR_COUNSELING_FOLLOWUP_HOSTING.sql
```

G3.4, G3.5, dan G3.6 tidak mempunyai SQL/schema delta.

G3.6A localhost:

```text
database/20260918_G3_6A_UKS_KESEHATAN_LOCALHOST.sql
```

SQL tersebut PREPARED / belum dieksekusi. SQL hosting G3.6A belum dibuat. PTSP belum mempunyai SQL/schema delta.

## 11. Upload / Writable

Public upload existing:

```text
uploads/foto_siswa/
uploads/foto_guru/
uploads/foto_pegawai/
uploads/settings/branding/
uploads/settings/kartu/
```

Non-public personalia:

```text
writable/uploads/personalia/
```

Runtime:

```text
writable/cache/
writable/logs/
writable/backups/
writable/debugbar/
writable/uploads/
```

Upload Pengaduan PTSP disimpan private di `WRITEPATH/uploads/ptsp/pengaduan/`; tidak ada direct public file URL.

## 12. Canonical Docs

```text
00_POLA_PENGERJAAN___SisisFour.md
00A_GLOBAL_STANDARD_SISFOUR.md
01_MASTERPLAN — SisisFour.md
02_DATABASE — SisisFour.md
03_AUTH_RBAC_MENU — SisisFour.md
04_MASTER_DATA — SisisFour.md
05_PRESENSI — SisisFour.md
06_LAPORAN — SisisFour.md
07_BK_PRESTASI_KARTU — SisisFour.md
08_DASHBOARD_SETTINGS_BACKUP — SisisFour.md
09_PROFILE — SisisFour.md
10_DEPLOYMENT_PRODUCTION — SisisFour.md
11_UI_UX — SisisFour.md
11_UI_UX_ROLE_EXPERIENCE — SisisFour.md
12_UI_UX_AUDIT_ADMIN — SisisFour.md
13_CI4_SNEAT_GLOBAL_LAYOUT_STANDARD.md
14_SISFOUR_MOBILE_CORDOVA_UI_UX_STANDARD.md
15_TESTING_POLISH — SisisFour.md
16_MOBILE_CORDOVA — SisisFour.md
17_UKS_KESEHATAN — SisisFour.md
18_PTSP — SisisFour.md
Routes Final — SisisFour.md
Tree Structure — SisisFour.md
```

`00` + `00A` adalah entry point wajib sebelum dokumen domain.

## 13. UI Hierarchy

```text
13 Global Sneat
→ 11 SisisFour UI
→ 14 Mobile/Cordova UI
→ 11 Role Experience
→ domain-specific public surface bila ada
```

## 14. Structural Change Rule

- baca `00` + `00A` sebelum feature/domain work;
- kunci Access Boundary sebelum Capability;
- kunci Capability sebelum Scope/Period/Target;
- business rule di Service;
- Controller request/response;
- JS page-specific per module;
- public API aggregate tidak boleh bypass privacy/access rules;
- route hanya ditambah bila endpoint nyata diperlukan;
- setiap route file tambahan didaftarkan di `Routing::$routeFiles`;
- reusable CSS/JS masuk foundation/component;
- SQL delta eksplisit di `database/`;
- period context server-side tidak digantikan helper UI;
- output channel listing/detail/dashboard/export/API harus konsisten terhadap contract yang sama;
- docs canonical disinkronkan sebelum phase merge/closure.

## 15. Current / Planned Phase Boundary

```text
G2      CLOSED
G3.1    CLOSED / MERGED
G3.2    CLOSED / MERGED
G3.3    CLOSED / MERGED
G3.3.1  CLOSED / MERGED — PR #9
G3.4    CLOSED / MERGED — PR #10
G3.5    CLOSED / MERGED — PR #11
G3.6    CLOSED / MERGED — PR #12
G3.6A   CLOSED / MERGED — PR #13
G3.6B   CLOSED / MERGED — PR #14
G3.6C   CLOSED / MERGED — PR #15
G3.7    ACTIVE — Global Mobile Sweep
G3.8    Viewport/WebView Readiness
G4      Cordova APK
```


### G3.6B concrete source

```text
app/Controllers/PtspPublic.php
app/Controllers/PtspPublicStats.php
app/Controllers/PtspLayanan.php
app/Controllers/PtspPolling.php
app/Controllers/PtspPengaduan.php

app/Models/PtspLayananModel.php
app/Models/PtspPollingModel.php
app/Models/PtspPengaduanModel.php

app/Services/PtspService.php
app/Services/PtspExportService.php
app/Services/PtspDashboardService.php

app/Views/dashboard_ptsp.php
app/Views/ptsp/public.php
app/Views/ptsp/layanan.php
app/Views/ptsp/polling.php
app/Views/ptsp/pengaduan.php

assets/js/ptsp/public.js
assets/js/ptsp/layanan.js
assets/js/ptsp/polling.js
assets/js/ptsp/pengaduan.js
assets/css/ptsp-public.css

database/20260919_G3_6B_PTSP_LOCALHOST.sql
```

## G3.6C Additions

```text
app/Controllers/Statistik.php
app/Services/StatistikService.php
app/Services/StatistikPdfService.php
app/Views/statistik/index.php
app/Views/statistik/pdf.php
assets/js/statistik.js
assets/css/statistik.css
database/20260919_G3_6C_EXEC_VIZ_LOCALHOST.sql
docs/19_G3_6C_EXEC_VIZ_SIGNAGE — SisisFour.md
```

Existing Signage files are refined in-place:

```text
app/Services/SignageService.php
app/Views/signage/index.php
assets/js/signage.js
assets/css/signage.css
```


## G3.7 Sweep Boundary

G3.7 bekerja terutama pada source presentation existing, bukan membuat domain baru.

Target struktur utama:

```text
app/Views/main.php
app/Views/_navbar.php
app/Views/_sidebar.php
app/Views/dashboard_*.php
app/Views/bk/*
app/Views/presensi/*
app/Views/laporan/*
app/Views/uks/*
app/Views/ptsp/*
app/Views/kartu/*
app/Views/statistik/*
app/Views/profile/*
app/Views/personalia/*
app/Views/manajemen_siswa/*
app/Views/master/*

assets/css/sisfour-ui.css
assets/css/sisfour-mobile.css
assets/css/sisfour-modal.css
module CSS existing bila perlu
assets/js/* page-specific existing
```

Tidak ada folder domain, migration, SQL, permission, atau menu baru yang diwajibkan oleh contract G3.7. Perubahan reusable masuk foundation global; adaptation unik tetap page/module-specific.
