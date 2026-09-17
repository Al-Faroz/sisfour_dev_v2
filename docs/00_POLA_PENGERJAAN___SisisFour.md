# Pola Pengerjaan — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 17 September 2026  
**Development aktif:** G3.3.1 — Fondasi BK + Konseling (**closure patch / focused local runtime PASS / static + hosting re-smoke pending**)  
**Branch aktif:** `feat/g3-bk-foundation-konseling-20260916`  
**Baseline `main`:** setelah merge PR #8 / G3.3 (`06e4e559c045763096058fc889342da78d973314`)

> Dokumen ini adalah kontrak cara kerja SisisFour saat ini. Ia bukan changelog. Keputusan domain yang lebih rinci tetap berada pada dokumen domain masing-masing.

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
07  BK / Konseling / Prestasi / Kartu
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
Database    -> dump SQL aktual + schema live + SQL delta final di database/
Route       -> Config\Routing::$routeFiles + seluruh route file terdaftar
Auth/RBAC   -> users, user_roles, permissions, role_permissions + Service
Menu        -> menus, role_menus, MenuService
Business    -> Service modul
Persistence -> Model / Query Builder
UI          -> View + application CSS + Vanilla JS
Global UI   -> docs/13_CI4_SNEAT_GLOBAL_LAYOUT_STANDARD.md
Mobile UI   -> docs/14_SISFOUR_MOBILE_CORDOVA_UI_UX_STANDARD.md
Deployment  -> docs/10_DEPLOYMENT_PRODUCTION — SisisFour.md
Release     -> docs/15_TESTING_POLISH — SisisFour.md
```

Schema delta development memakai SQL eksplisit di `database/`, bukan CodeIgniter migration. SQL harus schema-aware, idempotent bila memungkinkan, memiliki verification query, diuji lokal/staging lebih dulu, dan tidak dijalankan ke hosting tanpa audit kondisi aktual + approval eksplisit.

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

```text
1. baca seluruh docs relevan + source + database aktual
2. tentukan invariant/business rule
3. audit schema/permission/menu bila domain berubah
4. Model/Query bila perlu
5. Service
6. Filter bila perlu
7. Controller
8. View
9. JavaScript/CSS
10. Routes hanya bila endpoint nyata diperlukan
11. static gate
12. runtime regression
13. localhost/staging SQL gate bila ada schema delta
14. hosting dump audit sebelum menulis SQL hosting
15. hosting smoke bila deployment disetujui
16. sinkronkan docs canonical
17. PR review / explicit merge approval
```

Perubahan UI-only tidak boleh menyentuh Service/DB bila kebutuhan data dan business rule tidak berubah.

## 7. Aturan Git / Deployment

- Sebelum write GitHub, baca state/blob aktual.
- Perubahan berurutan pada path yang sama harus memakai state terbaru.
- Jangan merge tanpa approval eksplisit pengguna.
- Jangan deploy hanya karena PR mergeable.
- Production DB tidak disentuh dalam regression development.
- Jika SQL hosting diperlukan, audit dump hosting aktual lebih dulu lalu susun delta spesifik environment.
- Setelah source berubah sesudah production smoke, smoke lama tidak otomatis membuktikan head baru; lakukan focused re-smoke sesuai area perubahan.

## 8. Status G2 / G3

```text
G2                         CLOSED / MERGED (PR #5)
G3.1 Mobile foundation     CLOSED / MERGED (PR #6)
G3.2 Guru/Wali Presensi    CLOSED / MERGED (PR #7)
G3.3 Dashboard Guru/Wali   CLOSED / MERGED (PR #8)
G3.3.1 Fondasi BK          CLOSURE PATCH / LOCAL RUNTIME PASS / STATIC+HOSTING PENDING (PR #9)
G3.4 Dashboard/Workflow BK NEXT setelah PR #9 merge
G3.5 Pimpinan              setelah G3.4
G3.6 Siswa                 setelah G3.5
G3.7 Global mobile sweep
G3.8 Viewport/WebView readiness
G4 Cordova APK
```

Merge baseline sebelum PR #9:

```text
G2   375766c07f3856515a71ffdb07f3681c3047ca31
G3.1 d10ced5d70ffc68642067aac44feeb6a91cacd29
G3.2 176e5f764850d030968524af47117f259449064c
G3.3 06e4e559c045763096058fc889342da78d973314
```

## 9. G3.3.1 — Kontrak Final BK

```text
Catatan Kasus -> nama experience Catatan Pelanggaran Siswa
poin pelanggaran dihentikan sebagai business rule
kategori Ringan/Sedang/Berat tetap klasifikasi nonnumeric
ref_pelanggaran.poin tetap legacy untuk rollback; aplikasi menulis 0 dan tidak memakainya
Top Poin retired; route legacy hanya compatibility
Tindak Lanjut Pelanggaran tetap 1:N terpisah
Konseling BK fitur/tabel terpisah dan rahasia
Prestasi fitur positif terpisah
```

### Konseling dua tahap

Tahap 1:

```text
Kelas -> Siswa -> Tanggal -> Pertemuan ke-
Bentuk Layanan -> Cara Hadir -> Bidang -> Topik
status awal = Proses
```

Tahap 2:

```text
Uraian Masalah
Hasil Pembahasan & Kesepakatan
Rencana Berikutnya
Tanggal Pertemuan Berikutnya
Status Proses / Selesai
```

`Selesai` mewajibkan Uraian + Hasil. Tanggal berikutnya tidak boleh sebelum tanggal Konseling. Tidak ada delete Konseling pada G3.3.1.

### Preservasi opsi historis

Perubahan `Pengaturan Form Konseling` berlaku sebagai daftar pilihan aktif, tetapi **tidak boleh merusak nilai Rencana Berikutnya yang sudah tersimpan pada record lama**. Saat record lama dibuka, nilai yang sudah tersimpan tetap harus tampil dan boleh dipertahankan walaupun opsi tersebut kemudian dihapus dari Settings. Nilai legacy dari record lain tidak boleh menjadi bypass untuk memilih opsi yang sudah dinonaktifkan.

Closure patch untuk invariant ini ditemukan saat full docs/source audit 16 September 2026 dan tidak mengubah schema/SQL. Focused local runtime UAT pada 17 September 2026 telah **PASS**: nilai lama tetap tampil sebagai `(tersimpan)`, dapat dipertahankan saat save, dapat diganti ke opsi aktif baru, dan tidak muncul pada record lain. Evidence static gate head terbaru dan focused hosting re-smoke masih wajib sebelum PR #9 Ready/Merge.

### Identity BK

```text
created_by -> users.id
akun role BK aktual memakai users.id_pegawai -> pegawai.id
id_guru_bk nullable/legacy metadata, bukan identity utama
label actor UI/export = Dicatat oleh
```

### Permission / privacy

```text
bk_konseling.view      Admin, Operator, BK
bk_konseling.manage    Admin, Operator, BK
bk_konseling.export    Admin, Operator, BK
bk_konseling.settings  Admin, BK
```

Pimpinan, Guru/Wali, Siswa tidak mendapat menu/detail/widget Konseling. Route filter + Service tetap wajib.

### Pengaturan Form Konseling

Gunakan existing:

```text
setting_sistem.setting_key = bk_konseling_form_options
```

Configurable: Bentuk Layanan, Cara Hadir, Topik per Bidang, Rencana Berikutnya.  
Fixed: Bidang `Pribadi|Sosial|Belajar|Karier`, Status `Proses|Selesai`.

## 10. SQL G3.3.1

Final scripts:

```text
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_FIX3_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_HOSTING.sql
```

FIX1/FIX2 localhost adalah patch transisi dan tidak menjadi bagian branch final.

SQL hosting dibuat setelah dump aktual diaudit. SQL execution + broad hosting smoke telah PASS. Closure patch preservasi Rencana historis **tidak membutuhkan SQL baru**.

## 11. Core Mobile Contract G3

Untuk role operasional prioritas:

```text
body horizontal scroll   = DILARANG
table horizontal scroll  = DILARANG
nested horizontal scroll = DILARANG
```

Identity canonical:

> **Search with Name + Identifier, display primarily by Name.**

Touch target utama 44–48px; compact interactive control sekitar 40px.

## 12. Static Gate Minimum

```powershell
php -l path\file.php
node --check path\file.js
php spark routes
git diff --check
git status --short
```

## 13. Runtime Gate Minimum

Tidak boleh ada 404/500 tak disengaja, 403 palsu, privilege escalation, double mutation, partial transaction, histori putus, uncaught browser error, horizontal overflow, input penting hilang akibat network failure, atau sukses palsu.

Khusus Konseling: tidak boleh ada akses role terlarang, siswa di luar kelas/tahun aktif, status Selesai tanpa field wajib, atau kehilangan nilai historis hanya karena Settings berubah.

## 14. Definition of Done per Sub-phase

```text
source stabil
static gate PASS
SQL/schema gate PASS bila ada schema delta
runtime gate PASS
privacy/RBAC gate PASS bila data sensitif
canonical docs sinkron
PR review selesai
user approval eksplisit
```

G3.3.1 telah memenuhi seluruh gate besar sebelumnya dan focused local runtime UAT closure patch sudah PASS. Yang tersisa sebelum status kembali FINAL PASS adalah evidence static gate head terbaru dan focused hosting re-smoke, lalu approval eksplisit untuk Ready/Merge.