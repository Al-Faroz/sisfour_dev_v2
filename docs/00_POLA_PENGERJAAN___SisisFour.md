# Pola Pengerjaan — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 19 September 2026
**Development aktif:** G3.8 — Viewport/WebView Readiness / SSOT locked
**Branch aktif:** `feat/g3-8-webview-readiness-20260919`
**Baseline `main`:** setelah merge PR #16 / G3.7 (`7a595f21b70d9bfc28272b7f8ba19a2dfd3e60f9`)
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

Dashboard current-state dapat memakai Tahun Ajaran aktif tanpa selector historis bila seluruh KPI/list memang current-state.

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
14. audit dump hosting bila schema/data migration relevan
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
- SQL hosting dibuat setelah audit dump hosting aktual bila schema berubah.
- Smoke hosting lama tidak membuktikan head baru setelah source/schema berubah.

## 9. Roadmap

```text
G2                         CLOSED / MERGED
G3.1 Mobile foundation     CLOSED / MERGED
G3.2 Guru/Wali Presensi    CLOSED / MERGED
G3.3 Dashboard Guru/Wali   CLOSED / MERGED
G3.3.1 Fondasi BK          CLOSED / MERGED — PR #9
G3.4 Dashboard/Workflow BK CLOSED / MERGED — PR #10
G3.5 Pimpinan              CLOSED / MERGED — PR #11
G3.6 Siswa                 CLOSED / MERGED — PR #12
G3.6A UKS / Kesehatan      CLOSED / MERGED — PR #13
G3.6B PTSP                 CLOSED / MERGED — PR #14
G3.6C Exec Viz / Signage   CLOSED / MERGED — PR #15
G3.7 Global mobile sweep   CLOSED / MERGED — PR #16
G3.8 Viewport/WebView readiness ACTIVE / SSOT LOCKED
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

## 11. G3.3.1 Gate — Closed / Merged

Evidence closure:

```text
17 Sep localhost SQL execution          = PASS / user evidence
17 Sep local runtime UAT                = PASS / user evidence
18 Sep focused UAT Siswa self-only      = PASS / user evidence
18 Sep final static exact head          = PASS / user evidence
18 Sep hosting dump/schema audit        = PASS
18 Sep hosting source latest            = PASS / user evidence
18 Sep focused hosting re-smoke         = PASS / user evidence
PR #9                                   = MERGED
merge commit                            = 27d0f867d1c0ca7636a4a48f6c0b3251538ee7f6
```

Canonical SQL rework tersedia untuk kedua environment:

```text
database/20260917_G3_3_1_BK_PERIOD_YEAR_COUNSELING_FOLLOWUP_LOCALHOST.sql
database/20260917_G3_3_1_BK_PERIOD_YEAR_COUNSELING_FOLLOWUP_HOSTING.sql
```

## 12. G3.4 — Dashboard/Workflow BK

Branch:

```text
feat/g3-4-bk-dashboard-workflow-20260918
```

G3.4 tidak menambah schema, permission, menu, atau route baru. Implementasi memakai foundation G3.3.1 final.

Canonical mapping G3.4:

```text
Menu/Fitur         = Dashboard BK
Use Case           = melihat prioritas kerja BK current-state
SSOT/Domain        = docs/07 + docs/08 + role experience
Access Boundary    = experience BK; source widget tetap permission-aware
Capability         = bk_konseling.view / bk_kasus.view / ews_radar.view / prestasi.view
Scope              = sesuai capability; dashboard BK bukan pembuka akses baru
Period Context     = Tahun Ajaran aktif server-side
Target Validation  = tidak ada mutation baru dari dashboard
Business Invariant = no poin; no Konseling leak; no SLA/overdue baru
Persistence        = read-only agregasi dari tabel existing
Service Boundary   = Dashboard Service
Presentation UI    = KPI 2×2 + quick action + card/list
Output Channel     = Web dashboard + JSON dashboard existing
Audit              = tidak ada mutation baru
Testing/Regression = static + BK runtime + cross-role privacy
Docs Sync          = 00/01/07/08/11 + Tree Structure bila perlu
Deployment Gate    = source-only; hosting smoke setelah approval
```

Dashboard current-state:

```text
KPI              = Konseling Proses / Pelanggaran Bulan Ini / EWS 14 Hari / Prestasi Bulan Ini
Quick Action     = Konseling / Catatan Pelanggaran / EWS / Prestasi
Priority list    = Follow-up Terdekat / Pelanggaran Terbaru / EWS / Prestasi Terbaru
Data limit       = maksimal 5 item per list + Lihat Semua
Period           = hanya Tahun Ajaran aktif
Historical filter= tetap di listing, bukan dashboard
```

Jadwal follow-up memakai `tanggal_berikutnya` entry tindak lanjut terbaru bila histori sudah ada; jika belum ada histori, memakai tanggal berikutnya parent. Record Proses tanpa tanggal berikutnya tetap dihitung sebagai Konseling Proses tetapi tidak dipalsukan menjadi jadwal.

G3.4 tidak membuat SLA, overdue, deadline baru, atau ranking poin.

Closure:

```text
source implementation             = PASS
SSOT sync                         = PASS
local BK runtime/UAT              = PASS / user runtime evidence
cross-role privacy regression     = PASS / user runtime evidence
mobile focused UAT                = PASS / user runtime evidence
final static gate                 = PASS / user terminal evidence
hosting source deployment         = PASS / user evidence
hosting re-smoke                  = PASS / user runtime evidence
PR #10                            = MERGED
merge commit                      = 6f809913eab1032691f130c9df00e95da74b9a17
```

## 13. G3.5 — Dashboard Pimpinan

Branch:

```text
feat/g3-5-pimpinan-dashboard-20260918
```

Canonical mapping G3.5:

```text
Menu/Fitur         = Dashboard Pimpinan
Use Case           = monitoring exception/decision current-state
SSOT/Domain        = docs/05 + docs/06 + docs/08 + role experience
Access Boundary    = experience Pimpinan; source widget tetap permission-aware
Capability         = existing view/report permissions; tidak ada permission baru
Scope              = SEMUA hanya pada capability yang memang dimiliki
Period Context     = Tahun Ajaran aktif server-side
Target Validation  = tidak ada mutation baru dari dashboard
Business Invariant = readonly; no Konseling; Catatan Pelanggaran agregat tanpa poin
Persistence        = read-only agregasi dari tabel existing
Service Boundary   = Dashboard Service
Presentation UI    = KPI 2×2 + quick action + trend/list adaptive
Output Channel     = Web dashboard + JSON dashboard existing
Audit              = tidak ada mutation baru
Testing/Regression = static + Pimpinan runtime + cross-role regression
Docs Sync          = 00/01/08/11 + Tree Structure
Deployment Gate    = source-only; hosting smoke setelah approval
```

Contract G3.5:

```text
KPI              = Kelas Belum Presensi / Jadwal Belum Jurnal / EWS 14 Hari / Catatan Pelanggaran Bulan Ini
Quick Action     = Rekap / Jurnal / EWS / Laporan sesuai permission
Trend            = Presensi 7 hari, Sesi Awal
Recent/top       = EWS max 5 + Prestasi max 5
Period           = current-state Tahun Ajaran aktif
Pelanggaran      = agregat/jumlah, tanpa poin
Konseling        = tidak dibentuk untuk Pimpinan
```

Catatan Pelanggaran Bulan Ini dan Prestasi Terbaru pada dashboard wajib dibatasi `id_tahun = Tahun Ajaran aktif`. Dashboard tidak menambah selector histori; pembacaan historis tetap berada pada listing/laporan periodik.

Closure:

```text
source implementation             = PASS
SSOT sync                         = PASS
local Pimpinan runtime/UAT        = PASS / user runtime evidence
privacy / readonly regression     = PASS / user runtime evidence
mobile focused UAT                = PASS / user runtime evidence
cross-role dashboard regression   = PASS / user runtime evidence
final static gate                 = PASS / user terminal evidence
hosting source deployment         = PASS / user evidence
hosting re-smoke                  = PASS / user runtime evidence
PR #11                            = MERGED
merge commit                      = 6bdfc276ae07b6e70065ee7fae9e6ef51c3299ce
```

## 14. G3.6 — Dashboard Siswa

Branch:

```text
feat/g3-6-siswa-dashboard-20260918
```

Canonical mapping G3.6:

```text
Menu/Fitur         = Dashboard Siswa
Use Case           = self-service readonly current-state
SSOT/Domain        = docs/05 + docs/07 + docs/08 + role experience
Access Boundary    = experience Siswa; source widget tetap permission-aware
Capability         = existing self-view permissions; tidak ada permission baru
Scope              = DIRI_SENDIRI berdasarkan users.id_siswa
Period Context     = Tahun Ajaran aktif server-side untuk data periodik dashboard
Target Validation  = route domain tetap memvalidasi identity/scope target
Business Invariant = readonly; no poin; no Konseling; no cross-student exposure
Persistence        = read-only agregasi dari tabel existing
Service Boundary   = Dashboard Service + service domain existing
Presentation UI    = status hari ini + KPI 2×2 + quick action + card/list
Output Channel     = Web dashboard + JSON dashboard existing
Audit              = tidak ada mutation baru
Testing/Regression = static + Siswa runtime + self-scope/privacy + cross-role
Docs Sync          = 00/01/07/08/11 + Tree Structure
Deployment Gate    = source-only; hosting smoke setelah approval
```

Contract G3.6:

```text
Status Hari Ini  = Presensi Sesi Awal diri sendiri
KPI 2×2          = Hadir / Sakit / Izin / Alpha bulan ini
Quick Action     = Presensi Saya / Kartu / Prestasi / Profil sesuai permission
Recent           = ketidakhadiran max 5 + Lihat Rekap
Kartu            = Kartu Pelajar diri sendiri
Prestasi         = Tahun Ajaran aktif, max 5 + Lihat Semua
Pelanggaran      = Tahun Ajaran aktif, max 5 + Lihat Semua, tanpa poin
Period           = dashboard current-state; tidak ada selector histori
Konseling        = tidak dibentuk untuk Siswa
```

Tidak adanya Tahun Ajaran aktif dibedakan dari data bernilai nol. Dashboard tidak memalsukan KPI 0 ketika Period Context tidak tersedia. Historical Catatan Pelanggaran/Prestasi tetap tersedia pada listing self-only dengan Tahun Ajaran selectable.

Closure G3.6:

```text
source implementation             = PASS
SSOT sync                         = PASS
static gate                       = PASS / user terminal evidence
local runtime/UAT                 = PASS / user runtime evidence
self-scope/privacy regression     = PASS / user runtime evidence
cross-role regression             = PASS / user runtime evidence
hosting source deployment         = PASS / user evidence
hosting re-smoke                  = PASS / user runtime evidence
PR #12                            = MERGED
merge commit                      = 59b22b651ad0d508ea3a29261ef590d4c9506da4
```

Minimum static gate:

```powershell
$phpFiles = git diff --name-only origin/main...HEAD -- '*.php'
foreach ($file in $phpFiles) {
    php -l $file
    if ($LASTEXITCODE -ne 0) { throw "PHP lint failed: $file" }
}

$jsFiles = git diff --name-only origin/main...HEAD -- '*.js'
foreach ($file in $jsFiles) {
    node --check $file
    if ($LASTEXITCODE -ne 0) { throw "JS check failed: $file" }
}

php spark routes
if ($LASTEXITCODE -ne 0) { throw "Route check failed" }

git diff --check origin/main...HEAD
if ($LASTEXITCODE -ne 0) { throw "git diff --check failed" }

git status
```

Jangan klaim static/runtime PASS tanpa sumber evidencenya. `PASS / user evidence` tidak boleh diubah menjadi klaim CI/static.

## 15. G3.6A — UKS / Kesehatan

Branch:

```text
feat/g3-6a-uks-kesehatan-20260918
baseline main = 59b22b651ad0d508ea3a29261ef590d4c9506da4
```

Contract locked:

```text
Role Kesehatan identity = users.id_pegawai
Dashboard priority = admin > operator > pimpinan > bk > kesehatan > guru > siswa
PTSP priority = OPEN sampai G3.6B

Import CKG:
- stable key siswa = NISN wajib
- nama bukan fallback key
- duplicate active siswa+tanggal = UPDATE
- CKG tombstone lama = INSERT aktif baru, no auto-restore

Master configurable:
- Keluhan
- Tindakan
- Hasil Kunjungan
- deactivate/reactivate; historical reference tetap stabil

Dashboard Kesehatan:
- current-state Tahun Ajaran aktif
- KPI 2×2 CKG/Kunjungan/Rujuk Klinik
- Quick Action CKG/Data UKS/Import/Master
- recent max 5
- no medical scoring/SLA/overdue/risk label
```

Canonical implementation order G3.6A:

```text
SSOT lock
-> localhost SQL prepared
-> source/backend/RBAC/UI
-> docs sync
-> Draft PR
-> user static gate
-> user localhost SQL execution + verification
-> local runtime/cross-role/historical UAT
-> local dump audit
-> fresh hosting dump audit
-> hosting delta SQL
-> explicit hosting approval
-> hosting smoke
-> explicit Ready
-> explicit merge
```

Historical implementation-start gate (G3.6A; superseded by closure in `docs/17_UKS_KESEHATAN — SisisFour.md`):

```text
contract                         LOCKED
source                           IMPLEMENTED / feature branch
localhost SQL                    PREPARED / NOT EXECUTED
docs sync                        IMPLEMENTED
static gate                      PENDING
localhost SQL execution          PENDING
local runtime/UAT                PENDING
cross-role/historical regression PENDING
local dump audit                 PENDING
hosting                          NOT STARTED
```

## 16. G3.6C — Executive Visualization & EWS Signage

Canonical order untuk G3.6C mengikuti pola global:

```text
SSOT lock
-> localhost SQL
-> source/backend/RBAC/UI
-> static gate
-> local SQL + runtime UAT
-> post-SQL local dump audit
-> fresh hosting dump audit
-> hosting delta SQL prepared
-> explicit hosting SQL approval
-> hosting source deployment approval
-> hosting smoke
-> explicit Ready
-> explicit merge
```

Current gate:

```text
contract                         LOCKED
source                           IMPLEMENTED / feature branch
local SQL execution              PASS / user evidence
focused static re-check          PASS / user terminal evidence
local runtime/UAT                PASS / user runtime evidence
post-SQL local dump audit        PASS / read-only dump audit
fresh hosting pre-SQL audit      PASS / read-only dump audit
hosting SQL execution            PASS / user evidence
post-SQL hosting dump audit      PASS / read-only dump audit
hosting source deployment        PASS / user evidence
hosting runtime smoke            PASS / user runtime evidence
Kartu JPG ZIP add-on             PASS ALL
production gate                  PASS ALL
PR #15                          CLOSED / MERGED
PR Ready                         PASS / user approval
Merge                            PASS / user approval
merge commit                     f82a0299c8989da6c1026d84861f3e95d786f7dd
```


## 17. G3.7 — Global Mobile Sweep

G3.7 adalah phase presentation/regression lintas aplikasi setelah seluruh domain utama sampai G3.6C masuk `main`.

Contract:

```text
Use Case           = menyamakan usability mobile/responsive seluruh surface aktif
Access Boundary    = TIDAK BERUBAH
Capability / Scope = TIDAK BERUBAH
Period Context     = TIDAK BERUBAH
Business Invariant = TIDAK BERUBAH
Persistence        = NONE
DB / schema / SQL  = NONE
Route / menu       = tidak berubah kecuali kebutuhan nyata terpisah ditemukan
Service boundary   = tidak diubah hanya untuk presentation
Presentation UI    = responsive/adaptive sesuai docs/11/13/14
Output channel     = Web desktop + mobile browser; WebView-specific behavior ditahan untuk G3.8
```

Prioritas:

```text
mobile-first = Pimpinan / BK / Guru / Guru+Wali / Siswa
operasional  = Kesehatan / PTSP
responsive   = Admin / Operator
exception    = matrix administratif dua dimensi yang tidak dapat direduksi tanpa kehilangan fungsi
```

Acceptance global:

```text
no body horizontal overflow
no horizontal table scroll untuk role operasional
360px usable
action tidak clipped
touch target nyaman
filter mobile stack/compact
modal + keyboard usable
long content wrap aman
dashboard/list/card adaptif
desktop tidak regression
```

Viewport wajib:

```text
360×800
375×812
390×844
412×915
768×1024
1024×768
1366×768
```

Urutan pengerjaan:

```text
Wave 1  shell/global primitives
Wave 2  dashboard seluruh role
Wave 3  BK + Presensi + Laporan Guru/Wali
Wave 4  UKS + PTSP
Wave 5  Siswa + Kartu + Profile
Wave 6  Statistik + remaining operational surfaces
Wave 7  Admin/Operator heavy CRUD + documented exceptions
Wave 8  full viewport regression
```

Current gate:

```text
SSOT lock                        PASS / user approval
branch                           feat/g3-7-global-mobile-sweep-20260919
baseline main                    f82a0299c8989da6c1026d84861f3e95d786f7dd
runtime source head              00bbef3ee5ba2310a5cecc88c571a6e4a7ead853
source implementation            IMPLEMENTED / Wave 1–7B complete
Wave 1 GitHub diff audit         PASS / GitHub read evidence
Wave 1 static gate               PASS / user terminal evidence
Wave 1 runtime UAT               PASS / user runtime evidence via Wave 8 full regression
Wave 2 GitHub diff audit         PASS / GitHub read evidence
Wave 2 static gate               PASS / user terminal evidence
Wave 2 dashboard runtime UAT     PASS / user runtime evidence via Wave 8 full regression
Wave 3 GitHub diff audit         PASS / GitHub read evidence
Wave 3 static gate               PASS / user terminal evidence
Wave 3 runtime UAT               PASS / user runtime evidence via Wave 8 full regression
Wave 4 GitHub diff audit         PASS / GitHub read evidence
Wave 4 static gate               PASS / user terminal evidence
Wave 4 runtime UAT               PASS / user runtime evidence via Wave 8 full regression
Wave 5 GitHub diff audit         PASS / GitHub read evidence
Wave 5 static gate               PASS / user terminal evidence
Wave 5 runtime UAT               PASS / user runtime evidence via Wave 8 full regression
Wave 6 GitHub diff audit         PASS / GitHub read evidence
Wave 6 static gate               PASS / user terminal evidence
Wave 6 runtime UAT               PASS / user runtime evidence via Wave 8 full regression
Wave 7A GitHub diff audit        PASS / GitHub read evidence
Wave 7A static gate              PASS / user terminal evidence
Wave 7A runtime UAT              PASS / user runtime evidence via Wave 8 full regression
Wave 7B GitHub diff audit        PASS / GitHub read evidence
Wave 7B static gate              PASS / user terminal evidence
Wave 7B runtime UAT              PASS / user runtime evidence via Wave 8 full regression
Wave 8 full viewport regression  PASS / user runtime evidence
Settings Menu mobile             PASS / user runtime evidence — documented matrix exception
Kenaikan bulk mobile             PASS / user runtime evidence — documented local-scroll exception
Kelulusan bulk mobile            PASS / user runtime evidence — documented local-scroll exception
Matrix Presensi mobile           PASS / user runtime evidence — local-scroll exception
local viewport/runtime UAT       PASS / user runtime evidence
cross-role regression            PASS / user runtime evidence
local G3.7 gate                  PASS ALL
hosting source deployment        PASS / user evidence
hosting runtime smoke            PASS / user runtime evidence
production gate                  PASS ALL
PR #16                           CLOSED / MERGED
PR Ready                         PASS / user approval
Merge                            PASS / user approval
feature head                     7fb760c0945b33c0739e7ef83b0cbeeabfc7b295
merge commit                     7a595f21b70d9bfc28272b7f8ba19a2dfd3e60f9
```


## 18. G3.8 — Viewport/WebView Readiness

Baseline:

```text
main   = 7a595f21b70d9bfc28272b7f8ba19a2dfd3e60f9
branch = feat/g3-8-webview-readiness-20260919
G3.7  = CLOSED / MERGED — PR #16
```

G3.8 adalah readiness phase pada source Web sebelum project/plugin Cordova dibuat di G4.

Contract locked:

```text
Use Case           = memastikan Web SisisFour siap dibungkus WebView tanpa mengubah domain bisnis
Access Boundary    = TIDAK BERUBAH
Capability / Scope = TIDAK BERUBAH
Period Context     = TIDAK BERUBAH
Business Invariant = TIDAK BERUBAH
Persistence        = NONE
DB / schema / SQL  = NONE
Route / menu       = unchanged kecuali kebutuhan nyata terpisah mendapat approval
Service boundary   = authoritative server boundary tetap
Presentation UI    = Web/WebView-compatible readiness pada source existing
Output channel     = browser desktop/mobile + WebView-compatible Web source
Cordova project    = OUT OF SCOPE / G4
plugin/native API  = OUT OF SCOPE / G4
APK/signing        = OUT OF SCOPE / G4
```

Readiness focus:

```text
viewport-fit + safe-area consistency
short-height / landscape viewport
visualViewport + soft keyboard
modal/SearchableSelect keyboard containment
same-origin Fetch session-expiry recovery
network failure tidak menjadi sukses palsu
browser file input / download / export audit
internal vs external navigation audit
login/logout/redirect continuity
no Cordova/native dependency pada source Web
G3.7 responsive/RBAC/privacy regression tetap intact
```

Boundary G4 yang tidak boleh dimajukan ke G3.8:

```text
Cordova project/config
deviceready
native Android Back handling
native geolocation permission
native download/open/share bridge
external-browser/app intent bridge
status-bar/edge-to-edge native config
keystore/signing/distribution
real APK multi-device gate
```

Gate awal:

```text
SSOT lock                  PASS / user approval
branch                     feat/g3-8-webview-readiness-20260919
baseline main              7a595f21b70d9bfc28272b7f8ba19a2dfd3e60f9
read-only readiness audit  PASS / GitHub read evidence
source implementation      IMPLEMENTED / Wave 1A+1B
runtime source head         d6640d0e11fe48f9e47266756b7da6cfc029bcec
source diff audit           PASS / GitHub read evidence
static gate                 PASS / user terminal evidence
runtime readiness UAT       PASS / user runtime evidence
local G3.8 readiness        PASS
hosting deployment         NOT AUTHORIZED
PR Ready                   NOT AUTHORIZED
Merge                      NOT AUTHORIZED
```
