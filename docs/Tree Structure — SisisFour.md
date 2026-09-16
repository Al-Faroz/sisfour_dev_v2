# Tree Structure — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 16 September 2026

## 1. Root

SisisFour memakai project root sebagai Web root.

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

`.htaccess` root melindungi file/folder internal dan meneruskan request ke CI4.

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

Flow:

```text
Routes
→ Filters
→ Controller
→ Service
→ Model/Query
→ DB
→ View/JSON
```

## 3. Route Files

Route runtime tidak lagi hanya berasal dari `Routes.php`.

```text
app/Config/Routing.php
app/Config/Routes.php
app/Config/RoutesBKFoundation.php
```

`Routing::$routeFiles` mendaftarkan `Routes.php` lalu `RoutesBKFoundation.php`. File tambahan G3.3.1 memuat route Web Konseling/Settings Konseling.

## 4. G3.3.1 BK Source

Source utama yang ditambah/diubah untuk fondasi BK final:

```text
app/Controllers/BKKasus.php
app/Controllers/BKKonseling.php
app/Controllers/BKKonselingSettings.php
app/Controllers/BKPelanggaran.php
app/Controllers/BKPrestasi.php

app/Models/BKKasusModel.php
app/Models/KonselingBkModel.php

app/Services/BkExportService.php
app/Services/BkKonselingFormSettingsService.php
app/Services/KonselingBkExportService.php
app/Services/KonselingBkService.php
app/Services/RoleAwareDashboardService.php

app/Views/bk/kasus.php
app/Views/bk/konseling.php
app/Views/bk/konseling_settings.php
app/Views/bk/pelanggaran.php

assets/js/bk/kasus.js
assets/js/bk/konseling.js
assets/js/bk/konseling-detail-order.js
assets/js/bk/konseling-settings.js
assets/js/bk/pelanggaran.js
assets/js/bk/prestasi.js
```

Dashboard cross-role yang disentuh G3.3.1:

```text
app/Views/dashboard_bk.php
app/Views/dashboard_pimpinan.php
app/Views/dashboard_siswa.php
```

Wali tetap memakai experience/dashboard Guru + context Wali; tidak ada role/view Konseling khusus Wali.

## 5. Frontend

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

Reusable UI foundation berada di global CSS/component. Vendor Sneat/Bootstrap tidak dipatch langsung.

## 6. Layout Views

Project existing menggunakan partial root:

```text
app/Views/main.php
app/Views/_header.php
app/Views/_navbar.php
app/Views/_sidebar.php
app/Views/_footer.php
app/Views/_flash.php
app/Views/_scripts.php
```

Path ini valid walaupun standar global memberi contoh `layouts/partials/`; responsibility lebih penting daripada nama folder.

## 7. SQL / Database Scripts

SQL schema tidak memakai CodeIgniter migration untuk delta G3.2/G3.3.1.

G3.2 final:

```text
database/20260915_G3_2_JURNAL_STUDENT_EXCEPTIONS_LOCALHOST.sql
database/20260915_G3_2_JURNAL_STUDENT_EXCEPTIONS_HOSTING.sql
```

G3.3.1 final:

```text
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_FIX3_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_HOSTING.sql
```

FIX1/FIX2 localhost G3.3.1 adalah patch transisi development dan sudah dikeluarkan dari branch final.

## 8. Upload Public

```text
uploads/foto_siswa/
uploads/foto_guru/
uploads/foto_pegawai/
uploads/settings/branding/
uploads/settings/kartu/
```

Branding runtime direferensikan `setting_sistem`.

## 9. Upload Non-Public

```text
writable/uploads/personalia/
```

Raw document hanya dikirim melalui Controller/Service yang sah.

## 10. Runtime Writable

```text
writable/cache/
writable/logs/
writable/backups/
writable/debugbar/
writable/uploads/
```

## 11. Canonical Docs

Current SSOT set:

```text
00_POLA_PENGERJAAN___SisisFour.md
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
Routes Final — SisisFour.md
Tree Structure — SisisFour.md
```

Tidak ada entry SSOT lama `11_UI_UX_GURU_WALAS_SISWA — SisisFour.md` pada tree current. Role contract canonical adalah `11_UI_UX_ROLE_EXPERIENCE — SisisFour.md`.

## 12. UI Hierarchy

```text
13 Global Sneat
→ 11 SisisFour UI
→ 14 Mobile/Cordova UI
→ 11 Role Experience
```

## 13. Cordova

Project Cordova/APK belum menjadi source phase G3. G4 dimulai setelah G3 Web/mobile stabil. Project/package Cordova harus mempunyai boundary jelas dan tidak mencampur build artifact Android ke source Web tanpa aturan.

## 14. Tidak Di-commit

Secara prinsip:

```text
.env
build output
runtime cache/log/backup/debugbar
raw personalia upload
credential/token/secret
Cordova signing material
```

`vendor/` mengikuti strategy deployment/project; jangan mengubah policy hanya karena artifact lokal.

## 15. Structural Change Rule

- business rule di Service;
- Controller request/response;
- JS page-specific per module;
- route hanya ditambah bila endpoint nyata diperlukan;
- setiap route file tambahan harus didaftarkan di `Routing::$routeFiles`;
- reusable CSS/JS masuk foundation/component;
- SQL delta eksplisit di `database/`;
- project APK tidak menduplikasi source business Web;
- docs canonical disinkronkan sebelum phase merge/closure.

## 16. Current Phase Boundary

```text
G2      CLOSED
G3.1    CLOSED / MERGED
G3.2    CLOSED / MERGED
G3.3    CLOSED / MERGED
G3.3.1  PASS / PENDING MERGE APPROVAL
G3.4    NEXT setelah PR #9 merge
G4      setelah G3 selesai
```