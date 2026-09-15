# Pola Pengerjaan — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 15 September 2026  
**Development aktif:** G3.2 — Guru/Wali Presensi & Jurnal  
**Branch aktif:** `feat/g3-guru-wali-presensi-jurnal-20260915`  
**Baseline:** `main` setelah merge PR #6 / G3.1 CLOSED

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

`14` meng-override aturan `11/13` pada mobile/WebView bila lebih ketat, khususnya larangan horizontal-scroll pada tabel operasional role prioritas.

Business rule tetap mengikuti dokumen domain dan Service. View/JavaScript tidak boleh mengubah authorization atau lifecycle secara diam-diam.

## 3. Sumber Kebenaran Teknis

```text
Database    -> dump SQL resmi terbaru + schema live + SQL schema delta yang belum dirilis
Route       -> app/Config/Routes.php
Auth/RBAC   -> users, user_roles, permissions, role_permissions + Service
Menu        -> menus, role_menus, MenuService
Business    -> Service modul
Persistence -> Model / Query Builder
UI          -> View + assets/css/sisfour-ui.css + assets/css/sisfour-mobile.css + Vanilla JS
Global UI   -> docs/13_CI4_SNEAT_GLOBAL_LAYOUT_STANDARD.md
Mobile UI   -> docs/14_SISFOUR_MOBILE_CORDOVA_UI_UX_STANDARD.md
Deployment  -> docs/10_DEPLOYMENT_PRODUCTION — SisisFour.md
Release     -> docs/15_TESTING_POLISH — SisisFour.md
```

Schema delta development disimpan sebagai SQL eksplisit di `database/`, bukan CodeIgniter migration. SQL harus idempotent bila memungkinkan, memiliki query verifikasi, diuji pada localhost/staging copy lebih dulu, dan tidak diaplikasikan ke production tanpa backup + approval deploy eksplisit. `docs/02_DATABASE` baru dinaikkan menjadi baseline schema baru setelah SQL delta lulus UAT dan masuk release yang disetujui.

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

Perubahan UI-only tidak boleh menyentuh Service/DB bila kebutuhan data dan business rule tidak berubah. Bila user menyetujui perluasan domain yang benar-benar memerlukan persistence baru, perubahan schema wajib memakai SQL script eksplisit yang aman/idempotent sesuai kemampuan MySQL/MariaDB, memiliki verification query, diuji pada localhost/staging lebih dulu, dan tidak diaplikasikan ke production tanpa backup + approval deploy eksplisit.

## 7. Aturan Full File dan Git

- Revisi file diserahkan sebagai file utuh, bukan potongan source.
- `Routes.php` dipertahankan bila endpoint baru tidak diperlukan.
- Sebelum write GitHub, baca blob SHA aktual.
- Perubahan berurutan pada path yang sama harus memakai SHA terbaru.
- Jangan merge/deploy sebelum static + runtime gate lulus.
- Production DB tidak disentuh dalam regression development.
- Setiap sub-phase besar memakai branch/PR terpisah agar scope tidak bercampur.

## 8. G2 — CLOSED

G2 resmi selesai dan merged ke `main` melalui PR #5 pada 15 September 2026.

```text
G2.1 Repository Hygiene     PASS
G2.2 Static Gate            PASS
G2.3 Business Regression    PASS
G2.4 Browser Regression     PASS
G2.5 Closure Review         PASS
PR #5                       MERGED
```

Merge commit:

```text
375766c07f3856515a71ffdb07f3681c3047ca31
```

Scope F06–F14 tidak dibuka ulang di G3 tanpa blocker/regression baru yang terverifikasi.

## 9. G3 — Mobile Role UI

Tujuan G3:

```text
Web UI mobile-first untuk role operasional
Cordova/WebView ready
satu source UI CI4/Sneat
no duplicate mobile application UI
```

Role prioritas:

```text
Pimpinan
BK
Guru
Guru + Wali Kelas
Siswa
```

Admin/Operator tetap responsive, tetapi matrix administrasi berat boleh memiliki exception terdokumentasi.

Urutan:

```text
G3.1 Mobile foundation                  CLOSED / MERGED
G3.2 Guru/Wali — Presensi & Jurnal      ACTIVE
G3.3 Dashboard Guru/Wali
G3.4 BK workflow + Dashboard BK
G3.5 Pimpinan monitoring
G3.6 Siswa self-service
G3.7 global mobile sweep
G3.8 viewport/WebView readiness regression
```

### G3.1 — CLOSED

G3.1 lulus static/browser smoke dan merged melalui PR #6.

Merge commit:

```text
d10ced5d70ffc68642067aac44feeb6a91cacd29
```

Foundation canonical yang sekarang tersedia:

```text
role-aware shell classes
safe-area tokens
mobile spacing/density tokens
44px primary / 40px compact touch target
adaptive operational table primitive
name-first primary/meta cell
mobile form/filter/action primitive
sticky action primitive
2×2 KPI primitive
compact pagination/empty state
navbar/sidebar/footer mobile-safe baseline
body/layout overflow baseline
```

G3.1 tidak menambah project/plugin Cordova.

## 10. G3.2 — Guru/Wali Presensi & Jurnal

G3.2 mempertahankan authorization/scope Presensi dan Jurnal existing, sekaligus memuat **satu perluasan domain Jurnal yang disetujui user**: catatan Jurnal dan exception siswa Sakit/Izin/Alpha per pembelajaran. Perluasan ini tidak membuka ulang F06–F14 dan tidak mengubah makna Presensi Siswa resmi.

### Presensi Siswa

Target UI:

```text
Siswa              Status
Ahmad Fulan        [H] [S] [I] [A]
```

Kontrak:

- Nama siswa menjadi identitas visual utama.
- NISN bukan kolom rutin pada mobile; tetap tersedia pada desktop/audit bila dibutuhkan.
- Status H/S/I/A memiliki target sentuh minimum 40px dan label aksesibel.
- Tidak ada horizontal table scroll pada role operasional mobile.
- Guru Terjadwal/Wali tetap mengikuti scope Service.
- Geofence/time-window tetap server-authoritative.
- Satu submit kelas tetap atomic.
- Busy guard mencegah double submit.
- Network failure tidak menghapus perubahan status yang belum tersimpan.
- Success hanya setelah server mengonfirmasi.

### Presensi Mengajar / Jurnal

Kontrak existing tetap:

- Guru operasional tidak dipaksa memilih identitas dirinya sendiri bila hanya satu pilihan valid.
- Jadwal dapat di-auto-load bila hanya satu Jadwal valid.
- Wali tidak mendapat hak Jurnal hanya karena context Wali; hak tetap berdasarkan Jadwal Guru.
- Geofence/time-window/duplicate/revision tetap Service-authoritative.

Perluasan Jurnal G3.2:

```text
presensi_mengajar.catatan                 = optional
presensi_mengajar_siswa                  = child exception pembelajaran
status child                              = Sakit / Izin / Alpha
```

Rule wajib:

- child siswa hanya berasal dari roster kelas Jurnal pada tanggal Jurnal;
- Nama primary, NISN secondary search/disambiguation;
- satu siswa maksimal satu row child per Jurnal;
- child hanya boleh ada ketika status Guru `Hadir`;
- parent + exact child list disimpan/revisi dalam satu transaction;
- child **tidak pernah** menulis/mengubah tabel `presensi`;
- child tidak masuk Rekap/EWS/Signage Presensi resmi;
- laporan utama tetap `1 row = 1 Jurnal`, child hanya aggregate count dan detail lazy-load;
- query listing tidak boleh N+1.

Schema delta canonical G3.2:

```text
database/20260915_G3_2_JURNAL_STUDENT_EXCEPTIONS_LOCALHOST.sql
database/20260915_G3_2_JURNAL_STUDENT_EXCEPTIONS_HOSTING.sql
```

Tidak ada CodeIgniter migration untuk delta G3.2 ini. SQL localhost dipakai untuk development/UAT dan aman dijalankan ulang. SQL hosting hanya dijalankan setelah backup, G3.2 PASS/merge/release disetujui, dan ada approval deploy eksplisit.

## 11. Core Mobile Contract G3

Untuk role operasional prioritas:

```text
body horizontal scroll   = DILARANG
table horizontal scroll  = DILARANG
nested horizontal scroll = DILARANG
```

Gunakan prioritas informasi, metadata, hidden secondary columns, detail/modal/offcanvas—bukan `min-width` besar atau font ekstrem kecil.

Identity canonical:

> **Search with Name + Identifier, display primarily by Name.**

Nama adalah identitas visual utama. NISN/NIP/NIK sekunder untuk search, verification, disambiguation, audit, import/export, dan integrasi.

Touch target utama mobile: 44–48px; compact interactive control minimum sekitar 40px.

## 12. Phase Setelahnya — G4 Cordova APK

G4 dimulai setelah G3 Web/mobile stabil.

```text
G4.1 Cordova architecture spike
G4.2 Android project/config
G4.3 session/auth strategy verification
G4.4 Android Back
G4.5 safe-area/status bar/keyboard
G4.6 geolocation permission
G4.7 network/offline state
G4.8 file/download/share/external links
G4.9 real-device regression
G4.10 signed APK/distribution
```

Cordova wrapper tidak otomatis mengganti Web session auth dengan JWT.

## 13. Aturan Anti-Tabrakan Antar Phase

- G3 tidak mengubah business rule F06–F14 tanpa issue/scope baru.
- G3.2 hanya memiliki schema/business extension Jurnal yang tercatat eksplisit pada `docs/05_PRESENSI` dan SQL schema delta di `database/`.
- G3 tidak menambahkan project/plugin Cordova.
- G4 tidak menduplikasi halaman CI4 menjadi SPA kedua kecuali keputusan arsitektur baru dibuat eksplisit.
- Cordova bridge/plugin tidak ditanam ke business Service.
- CSS mobile reusable masuk foundation; adaptasi khusus halaman tetap scoped.
- Page-specific exception harus terdokumentasi.

## 14. Database-First

Filtering/agregasi dataset besar dilakukan database:

```text
WHERE / JOIN / GROUP BY / HAVING / ORDER BY / LIMIT / OFFSET
```

Dilarang load seluruh dataset besar lalu melakukan agregasi utama di PHP/JS.

## 15. Security Baseline

- CSRF aktif untuk Web.
- Mutation Fetch memakai helper CSRF project.
- API memakai Bearer/JWT dan bukan CSRF Web.
- Output user/data di-escape.
- Upload divalidasi tipe, ukuran, isi dan path.
- Dokumen personalia tetap non-public.
- Cordova tidak boleh memindahkan authorization ke client.
- Credential/token tidak ditulis ke log.

## 16. Static Gate Minimum

```powershell
php -l path\file.php
node --check path\file.js
php spark routes
git diff --check
git status --short
```

Branch dengan schema delta wajib menjalankan SQL localhost/staging copy yang sesuai dan memverifikasi schema hasilnya sebelum runtime UAT.

## 17. Runtime Gate Minimum

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
- horizontal table scroll pada role operasional mobile;
- input penting hilang hanya karena network failure;
- data akademik dinyatakan sukses sebelum server mengonfirmasi.

Untuk Jurnal G3.2 juga tidak boleh ada child siswa di luar roster, child ketika Guru Izin/Sakit, duplikasi child, perubahan tabel `presensi` akibat child Jurnal, atau laporan yang menggandakan row parent.

## 18. Definition of Done per Sub-phase

Sub-phase baru boleh ditutup/merge jika:

```text
source stabil
static gate PASS
SQL/schema gate PASS bila ada schema delta
runtime gate PASS
canonical docs sinkron
PR review selesai
user approval eksplisit
```
