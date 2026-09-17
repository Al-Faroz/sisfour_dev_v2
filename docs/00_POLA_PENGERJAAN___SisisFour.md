# Pola Pengerjaan — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 17 September 2026  
**Development aktif:** G3.3.1 — Fondasi BK + Konseling (**periodic Tahun Ajaran + follow-up Konseling 1:N rework / local gate pending**)  
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
aturan khusus halaman
```

Aturan yang bersifat global harus diterapkan konsisten pada feature yang sedang disentuh. Aturan domain tidak boleh diubah oleh View/JavaScript.

## 3. Sumber Kebenaran Teknis

```text
Database    -> dump SQL aktual + schema live + SQL delta final di database/
Route       -> Config\Routing::$routeFiles + seluruh route file terdaftar
Auth/RBAC   -> users, user_roles, permissions, role_permissions + Service
Menu        -> menus, role_menus, MenuService
Business    -> Service modul
Persistence -> Model / Query Builder
UI          -> View + application CSS + Vanilla JS
Global UI   -> docs/11_UI_UX — SisisFour.md + docs/13_CI4_SNEAT_GLOBAL_LAYOUT_STANDARD.md
Mobile UI   -> docs/14_SISFOUR_MOBILE_CORDOVA_UI_UX_STANDARD.md
Deployment  -> docs/10_DEPLOYMENT_PRODUCTION — SisisFour.md
Release     -> docs/15_TESTING_POLISH — SisisFour.md
```

`PermissionFilter` adalah route gate. Service tetap security/business boundary untuk target data, scope, transaksi, lifecycle, dan side effect.

## 4. Pola Perubahan Source

```text
1. baca docs canonical + source + database aktual
2. kunci invariant/business rule
3. audit schema/permission/menu bila domain berubah
4. sinkronkan SSOT keputusan baru
5. Model/Query
6. Service
7. Controller/Routes
8. View/JS/CSS
9. SQL localhost bila schema berubah
10. static gate
11. runtime/UAT localhost
12. audit dump hosting sebelum SQL hosting
13. hosting execution/smoke hanya dengan approval
14. final docs/PR sync
15. PR Ready / merge hanya dengan approval eksplisit
```

## 5. Aturan Git / Deployment

- Sebelum write GitHub, baca state/blob aktual.
- Jangan merge tanpa approval eksplisit pengguna.
- Jangan deploy hanya karena PR mergeable.
- Production DB tidak disentuh dalam regression development.
- SQL hosting dibuat setelah audit dump hosting aktual; jangan copy buta localhost.
- Setelah source/schema berubah sesudah smoke production, smoke lama tidak membuktikan head baru.

## 6. Global UI/UX yang Wajib Dijaga

### Filter padat

Desktop tidak boleh memaksa terlalu banyak filter menjadi satu baris sempit. Jika filter padat, pecah menjadi dua baris yang seimbang.

Canonical Konseling:

```text
Baris 1: Tahun Ajaran | Pencarian | Kelas
Baris 2: Status | Bidang | Dari | Sampai | Reset/Tampilkan
```

### Tahun Ajaran untuk tabel periodik

Keputusan 17 September 2026:

> Semua tabel/list periodik atau historis yang memang mempunyai dimensi Tahun Ajaran wajib menyediakan filter Tahun Ajaran.

```text
initial = Tahun Ajaran aktif
Reset   = Tahun Ajaran aktif
history = selectable bila domain mendukung
export  = mengikuti Tahun Ajaran terpilih
create  = Service snapshot Tahun Ajaran aktif
```

Jangan menambahkan filter Tahun Ajaran palsu pada tabel global/non-periodik seperti User, Permission, Menu, Setting, Log, Master Pelanggaran.

### Mobile/WebView

```text
body horizontal scroll   = DILARANG
table horizontal scroll  = DILARANG untuk role operasional
nested horizontal scroll = DILARANG
```

Name-first, SearchableSelect, busy guard, loading/empty/error, project confirmation, safe-area, dan touch target tetap global.

## 7. Status G2 / G3

```text
G2                         CLOSED / MERGED (PR #5)
G3.1 Mobile foundation     CLOSED / MERGED (PR #6)
G3.2 Guru/Wali Presensi    CLOSED / MERGED (PR #7)
G3.3 Dashboard Guru/Wali   CLOSED / MERGED (PR #8)
G3.3.1 Fondasi BK          REWORK / LOCAL GATE PENDING (PR #9)
G3.4 Dashboard/Workflow BK NEXT setelah PR #9 merge
G3.5 Pimpinan              setelah G3.4
G3.6 Siswa                 setelah G3.5
G3.7 Global mobile sweep
G3.8 Viewport/WebView readiness
G4 Cordova APK
```

## 8. G3.3.1 — Kontrak BK Final Target

```text
Catatan Kasus -> Catatan Pelanggaran Siswa
poin pelanggaran retired
kategori Ringan/Sedang/Berat nonnumeric
ref_pelanggaran.poin legacy only; aplikasi menulis 0
Tindak Lanjut Pelanggaran 1:N
Konseling BK rahasia
Prestasi terpisah
Catatan Pelanggaran/Konseling/Prestasi = tabel periodik berfilter Tahun Ajaran
```

### Konseling parent

Tahap 1:

```text
Kelas -> Siswa -> Tanggal -> Pertemuan ke-
Bentuk Layanan -> Cara Hadir -> Bidang -> Topik
status awal = Proses
```

Tahap 2 parent:

```text
Uraian Masalah
Hasil Pembahasan & Kesepakatan
Rencana Berikutnya
Tanggal Pertemuan Berikutnya
Status Proses / Selesai
```

### Tindak Lanjut Konseling 1:N

Keputusan 17 September 2026 mengganti kontrak lama yang hanya mempunyai satu rencana lanjutan.

```text
konseling_bk 1:N tindak_lanjut_konseling_bk
```

Setiap tindak lanjut memiliki tanggal, perkembangan, hasil/kesepakatan, rencana, tanggal berikutnya, status, dan actor audit.

Canonical detail:

```text
Identitas
→ Hasil Pertemuan Awal
→ Riwayat Tindak Lanjut
→ Form Tambah/Edit Tindak Lanjut
```

**Tidak ada delete parent Konseling dan tidak ada delete Tindak Lanjut Konseling.**

### Historical Rencana

Nilai Rencana yang sudah tersimpan tetap boleh ditampilkan/dipertahankan sebagai `(tersimpan)` walaupun dihapus dari Settings; record lain tidak boleh memakai opsi nonaktif tersebut.

Focused local UAT parent historical-Rencana sebelumnya PASS. Behavior yang sama wajib diuji ulang pada follow-up 1:N.

## 9. Permission / Privacy

```text
bk_konseling.view      Admin, Operator, BK
bk_konseling.manage    Admin, Operator, BK
bk_konseling.export    Admin, Operator, BK
bk_konseling.settings  Admin, BK
```

Pimpinan/Guru/Wali/Siswa tidak mendapat menu/detail/widget Konseling. Route filter + Service tetap wajib.

## 10. SQL G3.3.1

Baseline yang sudah lulus local+hosting:

```text
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_FIX3_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_HOSTING.sql
```

Rework localhost 17 September:

```text
database/20260917_G3_3_1_BK_PERIOD_YEAR_COUNSELING_FOLLOWUP_LOCALHOST.sql
```

Delta:

```text
catatan_kasus.id_tahun
catatan_prestasi.id_tahun
tindak_lanjut_konseling_bk
```

Belum ada SQL hosting untuk rework ini.

## 11. Static / Runtime Gate

Minimum final-head gate:

```powershell
php -l <changed php>
node --check <changed js>
php spark routes
git diff --check origin/main...HEAD
git status
```

Runtime wajib memeriksa Tahun Ajaran default/reset/history/export, filter Konseling 2 baris, follow-up Konseling multiple entry, historical Rencana, no-delete, RBAC/privacy, dan no horizontal overflow.

## 12. Definition of Done G3.3.1 Rework

```text
SSOT sinkron
source stabil
localhost SQL PASS
local runtime UAT PASS
final static gate PASS
hosting dump audit ulang
hosting delta SQL PASS
focused hosting smoke PASS
PR review selesai
approval Ready eksplisit
approval Merge eksplisit
```

PR #9 tetap Draft sampai semua gate baru ini selesai. G3.4 belum dimulai.