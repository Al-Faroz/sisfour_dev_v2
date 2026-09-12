# Pola Pengerjaan — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 12 September 2026
**Baseline Aplikasi:** `main` @ `39da4651acd29adcd575677d7a37c058bf32269d`
**Baseline Database:** `sisfour_dev_v2 (33).sql`

> Dokumen ini menyatakan kontrak yang berlaku pada baseline di atas. Dokumen ini **bukan changelog** dan tidak menyimpan narasi fase lama.


## 1. Kedudukan Folder `docs/`

`docs/` adalah **Single Source of Truth (SSOT)** untuk kontrak bisnis, arsitektur, authorization, database, route, release gate, dan deployment SisisFour.

Urutan baca:

```text
00_POLA_PENGERJAAN
01_MASTERPLAN
02_DATABASE
03_AUTH_RBAC_MENU
04_MASTER_DATA
05_PRESENSI
06_LAPORAN
07_BK_PRESTASI_KARTU
08_DASHBOARD_SETTINGS_BACKUP
09_PROFILE
10_DEPLOYMENT_PRODUCTION
15_TESTING_POLISH
16_MOBILE_CORDOVA
Routes Final
Tree Structure
```

Jika dokumen, source, route, dan database berbeda, konflik harus diidentifikasi dan diputuskan eksplisit.

## 2. Sumber Kebenaran Teknis

```text
Database    -> dump SQL resmi terbaru + SHOW CREATE TABLE bila perlu
Route       -> app/Config/Routes.php
Auth/RBAC   -> users, user_roles, permissions, role_permissions
Menu        -> menus, role_menus, MenuService
Business    -> Service modul
Persistence -> Model / Query Builder
UI          -> View + Vanilla JS
Deployment  -> 10_DEPLOYMENT_PRODUCTION
```

`PermissionFilter` hanya route gate. **Service adalah security/business boundary** untuk target data, scope, transaksi, lifecycle, dan side effect.

## 3. Aturan Full File

Setiap revisi atau file baru diserahkan sebagai **file utuh**, bukan snippet/diff.

Urutan pengerjaan:

```text
Model
-> Service
-> Filter bila diperlukan
-> Controller
-> View
-> JavaScript
-> Routes bila benar-benar perlu
-> Static Check
-> Runtime Checkpoint
-> Dokumentasi Canonical
```

`Routes.php` tidak diubah bila tidak ada kebutuhan route nyata.

## 4. Stack Resmi

```text
Framework        CodeIgniter 4
PHP              8.2+
Database         MariaDB / MySQL
Development      XAMPP
UI               Sneat + Bootstrap 5
Business JS      Vanilla JavaScript
HTTP Frontend    Fetch API
Chart            ApexCharts
Spreadsheet      PhpSpreadsheet
PDF              Dompdf
QR               endroid/qr-code
Session Web      DatabaseHandler / ci_sessions
Auth API         JWT + api_tokens
Timezone         Asia/Jakarta
```

jQuery boleh ada sebagai dependency template/vendor, tetapi business JavaScript tidak bergantung pada jQuery.

## 5. Arsitektur Request

```text
Browser / WebView / API Client
        |
        v
Routes
        |
        v
Global Filter (Maintenance/CSRF)
        |
        v
AuthFilter
        |
        v
PermissionFilter
        |
        v
Controller
        |
        v
Service
        |
        v
Model / Query Builder
        |
        v
MariaDB/MySQL
```

## 6. Document Root

SisisFour menggunakan **project root sebagai Web root**, bukan folder `public/`.

```text
sisfour_dev_v2/
├── index.php
├── .htaccess
├── app/
├── assets/
├── public/
├── uploads/
├── vendor/
├── writable/
└── docs/
```

`.htaccess` root wajib melindungi file/folder internal seperti `.env`, `app/`, `vendor/`, `writable/`, `docs/`, `database/`, dan repository metadata.

## 7. Database-First

Untuk dataset besar, filtering/agregasi utama dilakukan database:

```text
WHERE
JOIN
GROUP BY
COUNT / SUM
HAVING
ORDER BY
LIMIT / OFFSET
```

Dilarang memuat seluruh data besar lalu mengagregasi utama di PHP. Pivot ringan diperbolehkan setelah dataset dibatasi.

## 8. Authorization

Effective role:

```text
users.role
UNION
user_roles.role
```

Role resmi:

```text
admin
operator
pimpinan
bk
guru
siswa
```

Tidak ada role `pegawai`. Wali Kelas juga **bukan role**; status Wali di-resolve dinamis dari `mapping_wali_kelas` pada tahun aktif.

Scope:

```text
SEMUA
KELAS_DIAMPU
KELAS_TERJADWAL
DIRI_SENDIRI
TIDAK_ADA
```

## 9. Security Baseline

- Web menggunakan database session.
- CSRF aktif untuk Web.
- Mutation Fetch menggunakan `assets/js/csrf-fetch.js`.
- API `/api/*` memakai Bearer token/JWT dan tidak memakai CSRF Web.
- Actor Web berasal dari session; actor API dari token tervalidasi.
- `auth_version` adalah invalidation token keamanan; **login normal tidak menaikkannya**.
- Output teks di-escape.
- Password/hash/token/cookie/session id tidak ditulis ke log bisnis.
- Upload user divalidasi tipe, ukuran, isi, path, dan nama.
- Dokumen Personalia disimpan non-public di `writable/uploads/personalia/`.

## 10. Static Gate

```powershell
php -l path\file.php
node --check path\file.js
php spark routes
git diff --check
git status --short
```

## 11. Runtime Gate Minimum

Tidak boleh ada:

- 404 route tidak disengaja;
- 500;
- 403 palsu;
- CSRF failure pada mutation sah;
- privilege escalation/IDOR;
- duplicate akibat double-submit/race;
- partial transaction;
- histori putus;
- session/API actor salah;
- uncaught browser error;
- resource internal dapat diakses public.

## 12. Baseline Release

Baseline ini adalah release candidate setelah STEP 06: UI/UX sweep, authorization consistency, RequestContext API, signage, session recovery, pagination, maintenance, Kartu performance, hosting hardening, dan final regression.

Deployment production dilakukan secara **manual upload** ke Hostinger dan dikontrol oleh `10_DEPLOYMENT_PRODUCTION — SisisFour.md`.
