# Tree Structure — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 14 September 2026

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

## 3. Frontend

```text
assets/
├── css/
│   └── sisfour-ui.css
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

Reusable UI foundation berada di `sisfour-ui.css` dan `assets/js/components/`.

Vendor Sneat/Bootstrap tidak dipatch langsung.

## 4. Layout Views

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

Struktur ini valid walaupun standar global memberi contoh `layouts/partials/`; yang penting responsibility tetap terpisah.

## 5. Upload Public

```text
uploads/foto_siswa/
uploads/foto_guru/
uploads/foto_pegawai/
uploads/settings/branding/
uploads/settings/kartu/
```

Branding runtime direferensikan `setting_sistem`.

## 6. Upload Non-Public

```text
writable/uploads/personalia/
```

Raw document hanya dikirim melalui controller/service yang sah.

## 7. Runtime Writable

```text
writable/cache/
writable/logs/
writable/backups/
writable/debugbar/
writable/uploads/
```

## 8. Canonical Docs

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

`11_UI_UX_GURU_WALAS_SISWA — SisisFour.md` masih dapat ditemukan pada branch sebagai path lama, tetapi bukan entry SSOT utama; role contract canonical adalah `11_UI_UX_ROLE_EXPERIENCE — SisisFour.md`.

## 9. UI Hierarchy

```text
13 Global Sneat
→ 11 SisisFour UI
→ 14 Mobile/Cordova UI
→ 11 Role Experience
```

## 10. Cordova

Project Cordova/APK **belum** menjadi bagian phase G2.

Ketika G4 dimulai, project/package Cordova sebaiknya berada pada boundary terpisah yang jelas dan tidak mencampur vendor Android build artefact ke source Web tanpa aturan.

Detail ada di `16_MOBILE_CORDOVA — SisisFour.md`.

## 11. Phase Boundary

```text
G2 Master Data/lifecycle fixing + stabilization
G3 Mobile role UI/WebView readiness
G4 Cordova APK packaging/integration
```

## 12. Tidak Di-commit

Secara prinsip:

```text
.env
vendor/
build output
runtime cache/log/backup/debugbar
raw personalia upload
credential/token/secret
Cordova signing material
```

## 13. Structural Change Rule

- business rule tetap di Service;
- Controller menangani request/response;
- JS page-specific per module;
- route hanya ditambah bila endpoint nyata diperlukan;
- reusable CSS/JS masuk foundation/component;
- project mobile/APK tidak menduplikasi source business Web;
- docs canonical disinkronkan sebelum phase ditutup.
