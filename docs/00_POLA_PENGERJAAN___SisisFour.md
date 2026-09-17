# Pola Pengerjaan — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 18 September 2026
**Development aktif:** G3.3.1 — Fondasi BK + Konseling (**periodic Tahun Ajaran + follow-up Konseling 1:N rework**)
**Branch aktif:** `feat/g3-bk-foundation-konseling-20260916`
**Baseline `main`:** setelah merge PR #8 / G3.3 (`06e4e559c045763096058fc889342da78d973314`)
**Role registry canonical:** `admin`, `operator`, `pimpinan`, `bk`, `guru`, `siswa`, `kesehatan`, `ptsp`; Wali Kelas tetap context Guru.

> Dokumen ini adalah kontrak cara kerja SisisFour saat ini. Ia bukan changelog. `00A_GLOBAL_STANDARD_SISFOUR.md` adalah companion wajib sebelum coding/review fitur apa pun. Detail domain tetap berada pada dokumen domain masing-masing.

## 1. Kedudukan `docs/`

Folder `docs/` adalah Single Source of Truth untuk business rule, arsitektur, database, authorization, route, UI/UX, testing, deployment, mobile, dan Cordova.

Urutan baca canonical:

```text
00   Pola Pengerjaan
00A  Global Standard SisisFour
01   Masterplan
02   Database
03   Auth / RBAC / Menu
04   Master Data & Student Lifecycle
05   Presensi
06   Laporan
07   BK / Konseling / Prestasi / Kartu
08   Dashboard / Settings / Backup / Log
09   Profile / Personalia
10   Deployment Production
11   UI/UX SisisFour
11   UI/UX Role Experience
12   Audit UI/UX Admin
13   CI4 + Sneat Global Layout Standard
14   SisisFour Mobile & Cordova UI/UX Standard
15   Testing / Regression / Release Gate
16   Cordova Packaging & Integration
17   UKS / Kesehatan
18   PTSP
Routes Final
Tree Structure
```

Setiap fitur wajib dipetakan melalui:

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
→ Output Channel
→ Audit
→ Testing/Regression
→ Docs Sync
→ Deployment Gate
```

Jangan mulai keputusan business/security dari tombol UI, tabel, query, atau struktur database.

## 2. Sumber Kebenaran Teknis

```text
Global Map  -> docs/00A_GLOBAL_STANDARD_SISFOUR.md
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

`PermissionFilter` adalah route gate. Service tetap authoritative security/business boundary untuk target data, scope, period, transaksi, lifecycle, dan side effect.

## 3. Role Registry

Role resmi:

```text
admin
operator
pimpinan
bk
guru
siswa
kesehatan
ptsp
```

`Wali Kelas` adalah context Guru, bukan role baru.

Identity:

```text
Guru       -> users.id_guru
Siswa      -> users.id_siswa
BK         -> users.id_pegawai
Kesehatan  -> users.id_pegawai
PTSP       -> users.id_pegawai
```

Multi-role diperbolehkan. Effective role tetap mengikuti `users.role UNION user_roles.role`, kemudian permission + domain Access Boundary + capability + scope.

### Default deny

Role/context yang tidak disebut sebagai bagian Access Boundary suatu domain **tidak mendapat akses** sampai ada keputusan SSOT eksplisit.

### Makna Full Access

`Full Access` selalu **domain-specific**. Full Access UKS tidak memberi akses PTSP/BK/domain lain; Full Access PTSP tidak memberi akses UKS/BK/domain lain.

Full Access tidak otomatis berarti hard-delete/cancel/settings/approval. Destructive capability ditetapkan eksplisit per domain.

## 4. Domain Baseline — UKS / Kesehatan

SSOT rinci: `docs/17_UKS_KESEHATAN — SisisFour.md`.

Menu:

```text
UKS
├── Data CKG
└── Data UKS / Catatan Harian UKS
```

Access:

```text
Admin       = Full Access UKS / scope SEMUA
Operator    = Full Access UKS / scope SEMUA
Kesehatan   = Full Access UKS / scope SEMUA
Pimpinan    = ReadOnly SEMUA + Export
Guru + Wali = ReadOnly hanya kelas wali
Siswa       = ReadOnly seluruh data kesehatan dirinya sendiri
Guru non-Wali / BK / PTSP / role lain = DEFAULT DENY
```

Kesehatan = Pegawai; multi-role = YA.

Domain periodik Tahun Ajaran:

```text
Read/History  = Tahun Ajaran filter
Create        = Tahun Ajaran aktif server-side
Update        = tetap period record
Export        = Tahun terpilih + scope
```

CKG:

```text
multiple pemeriksaan per siswa per Tahun Ajaran
data field mengikuti workbook source
import Excel
siswa + tanggal duplikat -> UPDATE existing
stable import identity tidak boleh hanya nama; key final dikunci saat implementasi
```

Catatan Harian UKS:

```text
1 kunjungan = 1 parent record
khusus siswa
Hasil = disposition kunjungan
edit diperbolehkan Admin/Operator/Kesehatan
soft delete Admin/Operator/Kesehatan
alasan delete tidak wajib
```

Pimpinan dapat melihat detail per siswa dan export XLSX. Siswa hanya view, tanpa export.

## 5. Domain Baseline — PTSP

SSOT rinci: `docs/18_PTSP — SisisFour.md`.

Menu/internal domain:

```text
PTSP
├── Layanan PTSP
├── Polling Kepuasan
└── Pengaduan
```

PTSP mempunyai public landing page tersendiri dengan ketiga form public tanpa login.

Access internal:

```text
Admin     = Full Access PTSP / SEMUA
Operator  = Full Access PTSP / SEMUA
PTSP      = Full Access PTSP / SEMUA
Pimpinan  = ReadOnly SEMUA + Export
role lain = DEFAULT DENY internal administration
```

PTSP = Pegawai; multi-role = YA.

Semua submission PTSP snapshot Tahun Ajaran aktif server-side; history/export/statistik mengikuti context period yang sah.

### Layanan PTSP

```text
workflow = Baru -> Diproses -> Selesai
status mutation = Admin/Operator/PTSP
no follow-up 1:N
no SLA
```

Catatan workbook lama tentang `tiket antrian` digantikan keputusan terbaru:

```text
setelah submit -> dapat dicetak printer thermal
fungsi cetak    -> bukti sudah mengisi
NO nomor tiket
NO nomor antrean
NO tracking code
```

### Polling

```text
public
semua pihak boleh isi
submission berulang boleh
label kepuasan tetap disimpan
score statistik 5..1 ikut disimpan
```

### Pengaduan

```text
public
1 form anonim
internal/eksternal tidak dibedakan
status = Masuk -> Diverifikasi -> Diproses/Selesai
lampiran image/PDF opsional
no public tracking code
```

### Hard Delete PTSP

Layanan/Polling/Pengaduan dapat di-hard-delete oleh:

```text
Admin
Operator
PTSP
```

Pimpinan tetap ReadOnly.

### Public Statistics API

PTSP wajib mempunyai API statistik aggregate-only untuk masing-masing form agar dapat dipasang di WordPress/portal madrasah:

```text
Layanan statistics
Polling statistics
Pengaduan statistics
```

API public tidak boleh mengeluarkan PII/raw record seperti nama, WhatsApp, judul/isi pengaduan, atau lampiran. Target route dan output rinci berada pada docs/18.

## 6. Global UI/UX

Hirarki:

```text
13 CI4 + Sneat Global
→ 11 UI/UX SisisFour
→ 14 Mobile & Cordova UI/UX
→ 11 Role Experience
→ aturan khusus halaman/domain
```

Global rule:

```text
Name-first identity
SearchableSelect untuk entity besar
loading / empty / error state
busy guard
project confirmation untuk destructive action
no unwanted body horizontal overflow
no horizontal table scroll role operasional
safe-area + touch target mobile
```

Filter desktop yang padat boleh/harus dipecah menjadi dua atau lebih baris yang seimbang.

### Tahun Ajaran periodik

```text
Read / History           = Tahun Ajaran filter
Create Parent Baru       = Tahun Ajaran aktif
Update Existing Record   = tetap Tahun Ajaran record
Create Child / Follow-up = mengikuti Tahun Ajaran parent
Export                   = mengikuti period yang sedang dibaca
Reset filter             = Tahun Ajaran aktif
```

Jangan menambah Tahun Ajaran palsu ke tabel non-periodik.

## 7. Pola Perubahan Source

```text
1. baca 00 + 00A + docs domain + source + database aktual
2. petakan access/capability/scope/period/target/invariant
3. kunci business rule yang belum jelas
4. audit schema/permission/menu
5. sinkronkan SSOT
6. Model/Query
7. Service
8. Controller/Routes
9. View/JS/CSS
10. SQL localhost bila schema berubah
11. static gate
12. runtime/UAT localhost
13. cross-role + period + output-channel regression
14. audit dump hosting
15. hosting execution/smoke hanya dengan approval
16. final docs/PR sync
17. PR Ready / merge hanya dengan approval eksplisit
```

Jika mapping business/security/data integrity belum jelas, jangan menebak dan jangan lanjut coding.

## 8. Aturan Git / Deployment

- Sebelum write GitHub, baca state/blob aktual.
- Jangan merge tanpa approval eksplisit pengguna.
- Jangan deploy hanya karena PR mergeable.
- Production DB tidak disentuh dalam regression development.
- SQL hosting dibuat setelah audit dump hosting aktual.
- Smoke hosting lama tidak membuktikan head baru setelah source/schema berubah.

## 9. Roadmap

```text
G2                         CLOSED / MERGED
G3.1 Mobile foundation     CLOSED / MERGED
G3.2 Guru/Wali Presensi    CLOSED / MERGED
G3.3 Dashboard Guru/Wali   CLOSED / MERGED
G3.3.1 Fondasi BK          LOCAL+HOSTING SCHEMA PASS / HOSTING SOURCE RE-SMOKE PENDING / PR #9 DRAFT
G3.4 Dashboard/Workflow BK setelah PR #9 merge
G3.5 Pimpinan              setelah G3.4
G3.6 Siswa                 setelah G3.5
G3.6A UKS / Kesehatan      setelah G3.6
G3.6B PTSP                 setelah G3.6A
G3.7 Global mobile sweep
G3.8 Viewport/WebView readiness
G4 Cordova APK
```

UKS ditempatkan setelah Pimpinan+Siswa agar scope lintas-role stabil. PTSP setelah UKS karena menambah public landing, public submission, thermal print, dan public statistics API.

## 10. G3.3.1 — Kontrak BK yang Tetap Berlaku

```text
Catatan Kasus -> Catatan Pelanggaran Siswa
poin pelanggaran retired
kategori Ringan/Sedang/Berat nonnumeric
Tindak Lanjut Pelanggaran 1:N
Konseling BK rahasia
Prestasi terpisah
Catatan Pelanggaran/Konseling/Prestasi = periodik Tahun Ajaran
Konseling parent + follow-up 1:N
tidak ada delete parent/follow-up Konseling pada contract sekarang
```

Access Boundary Konseling:

```text
Admin / Operator / BK = domain access sesuai permission
Pimpinan / Guru / Wali / Siswa / Kesehatan / PTSP = TIDAK memiliki akses Konseling
```

Capability:

```text
bk_konseling.view      Admin, Operator, BK
bk_konseling.manage    Admin, Operator, BK
bk_konseling.export    Admin, Operator, BK
bk_konseling.settings  Admin, BK
```

Siswa dengan effective scope `DIRI_SENDIRI` pada Catatan Pelanggaran/Prestasi memakai experience sederhana: Tahun Ajaran tetap sebagai Period Context, filter operasional lain tidak dirender, dan daftar langsung dibatasi data diri oleh Service.

## 11. G3.3.1 Gate

Evidence terbaru:

```text
17 Sep localhost SQL execution          = PASS / user evidence
17 Sep local runtime UAT                = PASS / user evidence
18 Sep focused UAT Siswa self-only      = PASS / user evidence
18 Sep final static gate @ c6f690fa     = PASS / user terminal evidence
18 Sep hosting dump audit               = PASS / read-only evidence
18 Sep hosting rework SQL execution     = PASS / user evidence
18 Sep hosting post-SQL schema audit    = PASS
18 Sep hosting source latest            = PENDING
18 Sep focused hosting re-smoke         = PENDING
PR #9                                   = DRAFT / BELUM MERGE
```

Canonical SQL rework tersedia untuk kedua environment:

```text
database/20260917_G3_3_1_BK_PERIOD_YEAR_COUNSELING_FOLLOWUP_LOCALHOST.sql
database/20260917_G3_3_1_BK_PERIOD_YEAR_COUNSELING_FOLLOWUP_HOSTING.sql
```

Minimum final-head static gate:

```powershell
php -l <changed php>
node --check <changed js>
php spark routes
git diff --check origin/main...HEAD
git status
```

Jangan klaim static/runtime PASS tanpa sumber evidencenya. `PASS / user evidence` tidak boleh diubah menjadi klaim CI/static.

Definition of Done rework:

```text
SSOT sinkron
source stabil
localhost SQL PASS
local runtime UAT PASS
final static gate PASS
hosting dump audit ulang
hosting delta SQL PASS
hosting source latest terpasang
focused hosting smoke PASS
PR review selesai
approval Ready eksplisit
approval Merge eksplisit
```

PR #9 tetap Draft sampai gate selesai dan approval eksplisit diberikan.
