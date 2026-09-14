# Pola Pengerjaan — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 14 September 2026
**Development aktif:** G2 — Master Data & Student Lifecycle Fixing
**Branch aktif:** `fix/g2-master-data-20260913`
**PR aktif:** #5, belum merge

> Dokumen ini adalah kontrak cara kerja SisisFour saat ini. Ia bukan changelog dan tidak menyimpan narasi revisi lama.

## 1. Kedudukan `docs/`

Folder `docs/` adalah Single Source of Truth untuk business rule, arsitektur, database, authorization, route, UI/UX, testing, deployment, mobile, dan Cordova.

Urutan baca canonical:

```text
00  Pola Pengerjaan
01  Masterplan
02  Database
03  Auth / RBAC / Menu
04  Master Data & Student Lifecycle
05  Presensi
06  Laporan
07  BK / Prestasi / Kartu
08  Dashboard / Settings / Backup / Log
09  Profile / Personalia
10  Deployment Production
11  UI/UX SisisFour
11  UI/UX Role Experience
12  Audit UI/UX Admin
13  CI4 + Sneat Global Layout Standard
14  SisisFour Mobile & Cordova UI/UX Standard
15  Testing / Regression / Release Gate
16  Cordova Packaging & Integration
Routes Final
Tree Structure
```

## 2. Hirarki Standar UI

Jika ada aturan visual yang berbeda, gunakan prioritas:

```text
13 CI4 + Sneat Global
        ↓
11 UI/UX SisisFour
        ↓
14 Mobile & Cordova UI/UX
        ↓
11 Role Experience
        ↓
aturan khusus halaman bila terdokumentasi
```

`14` meng-override aturan `11/13` pada mobile/WebView bila lebih ketat, misalnya larangan horizontal-scroll tabel operasional.

Business rule tetap mengikuti dokumen domain dan Service; dokumen UI tidak boleh mengubah authorization atau lifecycle secara diam-diam.

## 3. Sumber Kebenaran Teknis

```text
Database    -> dump SQL resmi terbaru + schema live bila perlu
Route       -> app/Config/Routes.php
Auth/RBAC   -> users, user_roles, permissions, role_permissions + Service
Menu        -> menus, role_menus, MenuService
Business    -> Service modul
Persistence -> Model / Query Builder
UI          -> View + assets/css/sisfour-ui.css + Vanilla JS
Global UI   -> docs/13_CI4_SNEAT_GLOBAL_LAYOUT_STANDARD.md
Mobile UI   -> docs/14_SISFOUR_MOBILE_CORDOVA_UI_UX_STANDARD.md
Deployment  -> docs/10_DEPLOYMENT_PRODUCTION — SisisFour.md
Release     -> docs/15_TESTING_POLISH — SisisFour.md
```

`PermissionFilter` adalah route gate. Service tetap security/business boundary untuk target data, scope, transaksi, lifecycle, dan side effect.

## 4. Stack Resmi

```text
Framework        CodeIgniter 4
PHP              8.2+
Database         MariaDB / MySQL
Development      XAMPP
UI               Sneat Free v3 + Bootstrap 5.3.x
Font             Public Sans
Business JS      Vanilla JavaScript
HTTP Frontend    Fetch API
Chart            ApexCharts
Spreadsheet      PhpSpreadsheet
PDF              Dompdf
QR               endroid/qr-code
Web Session      DatabaseHandler / ci_sessions
API Auth         JWT + api_tokens
Android target   Apache Cordova / Android WebView
Timezone         Asia/Jakarta
```

jQuery boleh tetap menjadi dependency vendor, tetapi business JavaScript baru tidak bergantung pada jQuery.

## 5. Arsitektur Request

```text
Browser / Cordova WebView / API Client
        ↓
Routes
        ↓
Global Filter / Auth / Permission
        ↓
Controller
        ↓
Service
        ↓
Model / Query Builder
        ↓
MariaDB / MySQL
```

View/JavaScript tidak menjadi authorization boundary.

## 6. Pola Perubahan Source

Urutan normal:

```text
1. baca docs domain + source + database aktual
2. tentukan invariant/business rule
3. Model/Query bila perlu
4. Service
5. Filter bila perlu
6. Controller
7. View
8. JavaScript/CSS
9. Routes hanya bila endpoint nyata diperlukan
10. static gate
11. runtime regression
12. sinkronkan docs canonical
```

Perubahan UI-only tidak boleh menyentuh Service/DB bila kebutuhan datanya tidak berubah.

## 7. Aturan Full File dan Git

- Revisi file diserahkan sebagai file utuh, bukan potongan source.
- `Routes.php` dipertahankan bila endpoint baru tidak diperlukan.
- Sebelum write GitHub, baca blob SHA aktual.
- Perubahan berurutan pada path yang sama harus memakai SHA terbaru.
- Jangan merge/deploy sebelum static + runtime gate lulus.
- Production DB tidak disentuh dalam regression development.

## 8. Phase Aktif — G2

G2 adalah **fixing dan stabilization**, bukan fase redesign mobile penuh.

Scope G2:

```text
F06 Guru
F07 Pegawai
F08 Siswa
F09 Kelas
F10 Tahun Ajaran / semester transition
F11 Mata Pelajaran
F12 Mapping Wali
F13 Jadwal Guru
F14 Manajemen Siswa
Admin UI foundation/stabilization yang sudah masuk branch
Login/branding bugfix yang terkait regression
```

### G2.0 — SSOT Sync

Selesaikan dokumen canonical sebelum coding berikutnya:

- hierarki UI;
- standar Sneat global;
- role experience;
- Mobile & Cordova UI/UX;
- testing gate;
- urutan G2/G3/G4.

### G2.1 — Repository Hygiene

Sebelum regression final:

- audit diff terhadap `main`;
- kembalikan perubahan eksperimental yang tidak lagi diperlukan;
- khusus `JadwalGuruService.php`, pastikan tidak membawa rewrite/relaksasi import Genap yang sudah tidak dibutuhkan workflow final;
- pastikan `Routes.php` hanya berubah bila memang disengaja; target G2 saat ini tetap tidak memerlukannya;
- `git diff --check`.

### G2.2 — Static Gate

```text
PHP lint semua PHP yang berubah
node --check semua JS yang berubah
php spark routes
git diff --check
git status
```

### G2.3 — Business Regression F06–F14

Urutan:

```text
F06 Guru
F07 Pegawai
F08 Siswa
F09 Kelas
F10 Tahun Ajaran
F11 Mapel
F12 Mapping Wali
F13 Jadwal Guru
F14 Manajemen Siswa
```

F10 `Siapkan Genap` yang sudah lulus regression tidak perlu menjalankan destructive transition berulang bila Service terkait tidak berubah; lakukan targeted verification sesuai kebutuhan.

### G2.4 — Browser Regression G2

Uji hanya UI yang sudah menjadi bagian G2:

- Admin desktop/laptop/mobile smoke;
- login show/hide password;
- favicon/icon branding upload;
- page title/navbar;
- pagination/filter/export yang diubah;
- Profile Guru mobile tabs;
- tidak ada console/runtime error.

**Jangan** melakukan redesign besar tabel role operasional pada tahap ini.

### G2.5 — G2 Closure

- sinkronkan docs domain terhadap state final;
- review PR #5;
- pastikan tidak ada test data/temporary file ikut commit;
- user memberi approval eksplisit;
- merge hanya setelah approval.

## 9. Phase Berikutnya — G3 Mobile Role UI

G3 dimulai dari `main` setelah G2 selesai/merge.

Tujuan:

```text
Web UI mobile-first
Cordova/WebView ready
satu source UI CI4/Sneat
no duplicate mobile application UI
```

Urutan G3:

```text
G3.1 Mobile foundation
G3.2 Guru/Wali — Presensi & Jurnal
G3.3 Dashboard Guru/Wali
G3.4 BK workflow + Dashboard BK
G3.5 Pimpinan monitoring
G3.6 Siswa self-service
G3.7 global mobile sweep
G3.8 viewport/WebView regression
```

G3 mengikuti `14_SISFOUR_MOBILE_CORDOVA_UI_UX_STANDARD.md`.

## 10. Phase Setelahnya — G4 Cordova APK

Cordova packaging tidak dikerjakan di G2.

G4 dimulai setelah Web/mobile G3 stabil:

```text
G4.1 Cordova architecture spike
G4.2 Android project/config
G4.3 session/auth strategy verification
G4.4 back button / keyboard / safe-area
G4.5 geolocation permission
G4.6 network/offline state
G4.7 file download/share/external links
G4.8 device regression
G4.9 signed APK/release distribution
```

Detail teknis ada di `16_MOBILE_CORDOVA — SisisFour.md`.

## 11. Aturan Anti-Tabrakan Antar Phase

- G2 tidak menerima redesign mobile besar kecuali blocker regression.
- G3 tidak mengubah business rule F06–F14 tanpa issue/scope baru.
- G4 tidak menduplikasi halaman CI4 menjadi SPA kedua kecuali keputusan arsitektur baru dibuat eksplisit.
- Cordova bridge/plugin tidak ditanam ke business Service.
- CSS mobile reusable masuk foundation, bukan patch per halaman.
- Semua phase memakai branch terpisah dan regression gate sendiri.

## 12. Database-First

Filtering/agregasi dataset besar dilakukan database:

```text
WHERE / JOIN / GROUP BY / HAVING / ORDER BY / LIMIT / OFFSET
```

Dilarang load seluruh dataset besar lalu melakukan agregasi utama di PHP/JS.

## 13. Security Baseline

- CSRF aktif untuk Web.
- Mutation Fetch memakai helper CSRF project.
- API memakai Bearer/JWT dan bukan CSRF Web.
- Output user/data di-escape.
- Upload divalidasi tipe, ukuran, isi dan path.
- Dokumen personalia tetap non-public.
- Cordova tidak boleh memindahkan authorization ke client.
- Credential/token tidak ditulis ke log.

## 14. Static Gate Minimum

```powershell
php -l path\file.php
node --check path\file.js
php spark routes
git diff --check
git status --short
```

## 15. Runtime Gate Minimum

Tidak boleh ada:

- 404/500 tak disengaja;
- 403 palsu;
- CSRF failure pada mutation sah;
- IDOR/privilege escalation;
- double mutation;
- partial transaction;
- histori putus;
- uncaught browser error;
- horizontal body overflow pada viewport wajib;
- data akademik dinyatakan sukses sebelum server mengonfirmasi.

## 16. Definition of Done per Phase

Sebuah phase baru boleh dimulai jika phase sebelumnya:

```text
source stabil
static gate PASS
runtime gate PASS
canonical docs sinkron
PR review selesai
user approval eksplisit
```
