# Testing, Regression & Release Gate — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 19 September 2026
**Phase aktif:** G3.7 — **Global Mobile Sweep / SSOT locked; implementation pending**

> Quality gate dibagi per phase agar regression bisnis, mobile UI, schema delta, privacy, hosting, dan Cordova tidak bercampur. Merge/release tetap memerlukan approval eksplisit pengguna.

## 1. Static Gate Umum

```powershell
php -l path\file.php
node --check path\file.js
php spark routes
git diff --check origin/main...HEAD
git status
```

Tidak boleh ada syntax error, route target hilang, file tidak sengaja terhapus, atau whitespace conflict. Perubahan schema harus dilengkapi SQL local + verification sebelum hosting.

## 2. Phase Closed

```text
G2     CLOSED / MERGED — PR #5
G3.1   CLOSED / MERGED — PR #6
G3.2   CLOSED / MERGED — PR #7
G3.3   CLOSED / MERGED — PR #8
G3.3.1 CLOSED / MERGED — PR #9
G3.4   CLOSED / MERGED — PR #10
G3.5   CLOSED / MERGED — PR #11
G3.6   CLOSED / MERGED — PR #12
G3.6A  CLOSED / MERGED — PR #13
G3.6B  CLOSED / MERGED — PR #14
G3.6C  CLOSED / MERGED — PR #15
```

## 3. Global UI/UX Regression

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

Acceptance global:

- no body horizontal overflow;
- no horizontal table scroll role operasional;
- Nama sebagai primary identity;
- filter desktop yang banyak tidak dipaksa menjadi satu baris sempit;
- tabel/list periodik mempunyai Tahun Ajaran default aktif;
- Reset period filter kembali ke Tahun Ajaran aktif;
- export mengikuti period filter;
- class/scope historis mengikuti Tahun Ajaran yang dipilih, bukan Tahun aktif;
- touch target utama 44–48px;
- modal/keyboard nyaman;
- busy guard;
- network failure tidak menjadi sukses palsu;
- no uncaught browser error;
- direct URL tidak menembus RBAC/Service.

## 4. G3.3.1 — Gate Lama yang Tetap Valid

Sudah PASS sebelum rework 17 September:

```text
poin pelanggaran retired
Catatan Pelanggaran + Tindak Lanjut 1:N baseline
Prestasi baseline
Konseling Tahap 1/Tahap 2 baseline
Settings Form Konseling
Admin/Operator/BK operational matrix
Admin/BK Settings
Pimpinan/Guru/Wali/Siswa tanpa Konseling
actor users.id / BK Pegawai
baseline SQL local + hosting
baseline broad hosting smoke
historical Rencana parent focused local UAT
```

Hasil historical Rencana yang sudah terbukti lokal:

```text
opsi lama setelah dihapus Settings tetap tampil di record lama   PASS
record lama dapat disimpan tanpa mengganti opsi lama             PASS
opsi lama dapat diganti ke opsi aktif baru                       PASS
opsi lama tidak muncul pada record lain                          PASS
```

## 5. Rework 17 September 2026

Keputusan baru:

```text
A. Filter banyak desktop boleh/wajib dipecah 2 baris bila padat.
B. Semua tabel periodik/historis memakai filter Tahun Ajaran default aktif.
C. Catatan Pelanggaran dan Prestasi mendapat snapshot id_tahun.
D. Scope kelas historis memakai Tahun Ajaran terpilih.
E. Export Kelas/Tahun membaca membership periode record, bukan kelas aktif saat ini.
F. Konseling mempunyai Tindak Lanjut 1:N.
G. Tidak ada delete Konseling atau Tindak Lanjut Konseling.
H. Semua aturan global UI/UX tetap berlaku.
```

Source target utama:

```text
app/Services/PeriodContextService.php
app/Services/BkScopeService.php
app/Models/BKKasusModel.php
app/Models/BKPrestasiModel.php
app/Models/KonselingBkModel.php
app/Models/KonselingBkFollowUpModel.php
app/Services/BkService.php
app/Services/BkExportService.php
app/Services/PrestasiService.php
app/Services/KonselingBkService.php
app/Services/KonselingBkExportService.php
app/Controllers/BKKonseling.php
app/Config/RoutesBKFoundation.php
app/Views/bk/kasus.php
app/Views/bk/prestasi.php
app/Views/bk/konseling.php
assets/js/bk/kasus.js
assets/js/bk/prestasi.js
assets/js/bk/konseling.js
```

SQL local:

```text
database/20260917_G3_3_1_BK_PERIOD_YEAR_COUNSELING_FOLLOWUP_LOCALHOST.sql
```

## 6. Gate SQL Local Rework

Import SQL local hanya setelah source terbaru dipull.

Wajib verifikasi:

```text
catatan_kasus.id_tahun tersedia
catatan_prestasi.id_tahun tersedia
index tahun+tanggal tersedia
tindak_lanjut_konseling_bk tersedia
FK parent Konseling = RESTRICT
FK actor = SET NULL
jumlah legacy id_tahun NULL diketahui, bukan diam-diam diabaikan
```

Record legacy hanya di-backfill jika siswa mempunyai tepat satu Tahun Ajaran pada histori membership. Record ambigu boleh tetap NULL dan harus tercatat pada verification count.

Execution local 17 September sudah dilaporkan user **PASS**. Exact verification count legacy NULL tidak diinventarisir di SSOT karena user hanya melaporkan overall execution PASS.

## 7. UAT Tahun Ajaran — Catatan Pelanggaran

Minimum:

```text
1. buka Catatan Pelanggaran -> Tahun Ajaran aktif terpilih default
2. Reset -> kembali ke Tahun Ajaran aktif
3. pilih Tahun historis -> tabel hanya data period tersebut
4. Kelas pada data/export berasal dari membership periode record, bukan kelas aktif sekarang
5. kembali ke aktif -> data aktif kembali
6. create Catatan baru -> tersimpan dengan id_tahun aktif walau filter sebelumnya historis
7. export -> hanya period yang dipilih + Tahun/Kelas historis benar
8. mobile tidak overflow horizontal
```

### Scope KELAS_DIAMPU historis

Bila tersedia account/scope pengujian:

```text
pilih Tahun historis
→ siswa yang terlihat mengikuti kelas yang diampu pada Tahun historis itu
→ bukan kelas Tahun aktif
→ direct detail record id_tahun NULL harus ditolak untuk KELAS_DIAMPU
```

## 8. UAT Tahun Ajaran — Prestasi

Minimum:

```text
1. default Tahun Ajaran aktif
2. Reset kembali aktif
3. histori period dapat dipilih
4. kelas pada list/export mengikuti membership periode record
5. create baru selalu snapshot periode aktif server
6. export mengikuti period terpilih
7. KELAS_DIAMPU bila digunakan mengikuti Tahun terpilih
8. mobile/table tetap sesuai global no-horizontal-overflow rule
```

## 9. UAT Tahun Ajaran — Konseling

Minimum:

```text
1. default Tahun Ajaran aktif
2. filter desktop tampil 2 baris, tidak dipaksa satu baris
3. ganti Tahun Ajaran -> opsi Kelas pada filter ikut period terpilih
4. Reset -> Tahun aktif + filter lain kosong
5. create Konseling tetap hanya kelas/siswa Tahun aktif
6. listing/export mengikuti Tahun terpilih
7. no horizontal overflow desktop/mobile
```

## 10. UAT Konseling Follow-up 1:N

Buat satu parent Konseling, lengkapi pertemuan awal, lalu:

```text
1. Tambah Tindak Lanjut #1 status Proses                  PASS/FAIL
2. Tambah Tindak Lanjut #2 status Proses                  PASS/FAIL
3. histori menampilkan #1 dan #2 tanpa overwrite          PASS/FAIL
4. Edit #1 hanya mengubah #1                              PASS/FAIL
5. parent status mengikuti entry terbaru                  PASS/FAIL
6. Tambah #3 status Selesai + hasil wajib                 PASS/FAIL
7. parent status menjadi Selesai                          PASS/FAIL
8. tanggal follow-up < tanggal parent ditolak             PASS/FAIL
9. tanggal berikutnya < tanggal follow-up ditolak         PASS/FAIL
10. status Selesai tanpa Hasil/Kesepakatan ditolak        PASS/FAIL
11. tidak ada tombol/route delete parent                  PASS/FAIL
12. tidak ada tombol/route delete follow-up               PASS/FAIL
```

### Historical Rencana pada Follow-up

```text
1. follow-up menyimpan Rencana X
2. X dihapus dari Settings
3. buka follow-up lama -> X (tersimpan)
4. save tanpa mengganti X -> sukses
5. ganti ke opsi aktif Y -> sukses
6. follow-up lain yang tidak pernah menyimpan X tidak boleh memilih X
```

Focused local runtime UAT rework telah dilaporkan user **PASS** untuk Tahun Ajaran periodik, export periodik, Konseling follow-up 1:N, historical Rencana tindak lanjut, no-delete/RBAC, dan responsive. Detail individual checklist tetap menjadi regression checklist untuk final/hosting smoke; jangan mengubah PASS user menjadi klaim CI/static.

## 11. Export Konseling Rework

XLSX wajib mempunyai:

```text
Sheet 1 = Konseling BK parent/pertemuan awal
Sheet 2 = Tindak Lanjut 1:N
```

Export mengikuti filter Tahun Ajaran dan privacy boundary Admin/Operator/BK.

## 12. Privacy / RBAC Regression

```text
view/manage/export -> effective role Admin/Operator/BK + permission
settings           -> effective role Admin/BK + permission
```

Pimpinan/Guru/Wali/Siswa/Kesehatan/PTSP tidak boleh memperoleh menu/detail/widget/direct access Konseling.

## 13. No-delete Contract Konseling

Dilarang pada rework ini:

```text
DELETE route parent Konseling
DELETE route tindak_lanjut_konseling_bk
tombol Hapus Konseling
tombol Hapus Tindak Lanjut Konseling
cascade delete histori follow-up dari aplikasi
```

Edit tetap diperbolehkan sesuai permission dan harus tercatat pada audit actor/update fields.

## 14. Static Gate Setelah Local UAT

Jalankan pada **head final setelah semua source/docs selesai**:

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

Jangan klaim PASS tanpa output user/CI.

## 15. G3.6A — UKS / Kesehatan Gate

### SQL localhost

Jalankan hanya pada localhost setelah branch exact ditarik:

```text
database/20260918_G3_6A_UKS_KESEHATAN_LOCALHOST.sql
```

Verification minimum:

```text
role enum users/user_roles/role_permissions/role_menus memuat kesehatan
6 tabel uks_* tersedia
8 permission UKS tersedia
role_permissions sesuai matrix
menu UKS/Data CKG/Data UKS/Master UKS tersedia
role_menus sesuai Access Boundary
```

### Runtime / business UAT

```text
Role Kesehatan
- identity wajib Pegawai
- dashboard Kesehatan tampil
- KPI current-state Tahun aktif benar
- quick action permission-aware
- CKG/Harian/Import/Master bekerja

CKG
- default Tahun aktif; history selectable
- create selalu snapshot Tahun aktif
- update mempertahankan period record
- NISN wajib pada import
- nama tidak menjadi fallback key
- duplicate aktif siswa+tanggal -> UPDATE
- CKG soft-deleted lama -> import INSERT record aktif baru
- CKG soft delete hilang dari listing/export normal
- Pimpinan readonly + export
- Wali readonly kelas wali pada period terpilih
- former Wali dapat memilih period lama tetapi tidak melihat siswa di period tanpa mapping
- Siswa hanya DIRI_SENDIRI
- Guru non-Wali/BK/PTSP DENY

Catatan Harian UKS
- satu kunjungan = satu parent
- tindakan multi-pilih tersimpan
- petugas = actor login
- update mempertahankan Tahun record
- soft delete hilang dari listing/export normal
- opsi master inactive yang tersimpan tetap tampil/preservable pada edit histori

Master UKS
- Keluhan/Tindakan/Hasil configurable
- deactivate menghilangkan opsi dari record baru
- edit dapat reactivate opsi lama
- histori referensi tetap terbaca

Dashboard Kesehatan
- Kunjungan UKS Hari Ini
- Kunjungan UKS Bulan Ini
- Rujuk ke Klinik Bulan Ini
- Pemeriksaan CKG Bulan Ini
- primary action: Tambah Data Kunjungan
- quick action: Data UKS / Data CKG / Import CKG / Master UKS
- latest Kunjungan max 5 lalu CKG max 5
- no medical score/SLA/overdue/risk label baru

Regression
- Admin/Operator/Pimpinan/BK/Guru/Guru+Wali/Siswa tetap normal
- Konseling tetap tidak pernah terekspos ke Kesehatan
- mobile viewport global no horizontal body overflow
```

### Evidence labels

```text
SQL execution/verifikasi terminal = PASS / user terminal evidence
runtime UAT                       = PASS / user runtime evidence
dump audit                        = PASS / read-only dump audit
```

Jangan menyebut user terminal evidence sebagai CI.

## 16. Hosting Gate

G3.6 hosting PASS **tidak membuktikan G3.6A**.

Urutan setelah localhost PASS:

```text
1. final static gate pada exact head
2. local runtime + cross-role + historical scope UAT
3. buat dump localhost setelah PASS dan audit schema/data delta
4. minta dump hosting aktual dan audit read-only
5. susun SQL hosting khusus state aktual
6. review SQL hosting
7. execution hosting hanya dengan approval eksplisit user
8. deploy source hanya dengan approval eksplisit user
9. focused hosting re-smoke
10. PR Ready hanya dengan approval user
11. merge hanya dengan approval merge terpisah
```

## 17. Current Status

```text
G3.3.1                              CLOSED / MERGED — PR #9
G3.4                                CLOSED / MERGED — PR #10
G3.5                                CLOSED / MERGED — PR #11
G3.6                                CLOSED / MERGED — PR #12
G3.6A                               CLOSED / MERGED — PR #13
G3.6B                               CLOSED / MERGED — PR #14
G3.6C                               CLOSED / MERGED — PR #15
G3.6C merge commit                  f82a0299c8989da6c1026d84861f3e95d786f7dd
Kartu JPG ZIP add-on                CLOSED / MERGED — PR #15

G3.7 contract                       LOCKED / user approval
G3.7 branch                         feat/g3-7-global-mobile-sweep-20260919
G3.7 source                         IN PROGRESS / Wave 1 + Wave 2 + Wave 3 + Wave 4 implemented
G3.7 Wave 1 GitHub diff audit       PASS / GitHub read evidence
G3.7 Wave 1 static gate             PASS / user terminal evidence
G3.7 Wave 1 runtime UAT             PARTIAL / overflow @720px PASS
G3.7 Wave 2 GitHub diff audit       PASS / GitHub read evidence
G3.7 Wave 2 static gate             PENDING
G3.7 Wave 2 dashboard runtime UAT   PENDING
G3.7 Wave 3 GitHub diff audit       PASS / GitHub read evidence
G3.7 Wave 3 static gate             PENDING
G3.7 Wave 3 runtime UAT             PENDING
G3.7 Wave 4 GitHub diff audit       PASS / GitHub read evidence
G3.7 Wave 4 static gate             PENDING
G3.7 Wave 4 runtime UAT             PENDING
G3.7 Matrix mobile                  CANDIDATE EXCEPTION / UAT REQUIRED
G3.7 local viewport/runtime UAT     PARTIAL
G3.7 cross-role regression          PENDING
G3.7 hosting deployment             NOT AUTHORIZED
PR Ready                            NOT AUTHORIZED
Merge                               NOT AUTHORIZED
```

## 18. Roadmap

```text
G3.6A  UKS / Kesehatan       CLOSED / MERGED — PR #13
G3.6B  PTSP                  CLOSED / MERGED — PR #14
G3.6C  Executive Viz/Signage CLOSED / MERGED — PR #15
G3.7   Global Mobile Sweep   ACTIVE
G3.8   WebView Readiness
G4     Cordova APK
```

G3.6A mengikuti SSOT `17_UKS_KESEHATAN — SisisFour.md`. PTSP tetap terpisah dan tidak boleh ikut diimplementasikan pada SQL/source G3.6A hanya karena role registry global sudah mengenal target role tersebut.

Setiap deployment/Ready/merge memerlukan approval eksplisit pengguna.


## 19. G3.6B — PTSP Gate

Static minimum:

```text
php -l seluruh PHP changed G3.6B
node --check assets/js/ptsp/*.js
php spark routes
git diff --check origin/main...HEAD
git status
```

Runtime minimum:

```text
Public landing /ptsp tampil sebagai kios 3 tombol besar
urutan kios = Layanan PTSP -> Pengaduan -> Polling Kepuasan
setiap tombol membuka halaman form tersendiri
Public Layanan submit tanpa login + CSRF valid
receipt thermal media 80 mm tanpa nomor tiket/antrian/tracking
Auto Print OFF + Auto PDF OFF -> tombol cetak manual tersedia, tidak ada dialog/download otomatis
Auto Print OFF + Auto PDF ON -> PDF bukti thermal 80mm otomatis terunduh
Auto Print ON -> dialog print otomatis muncul dan Auto PDF tidak dijalankan
PDF filename tidak memuat PII/public record ID
PDF receipt tidak memuat ticket/antrian/tracking/public record ID
PDF receipt normal 80mm muat 1 halaman
jarak antarbaris compact dan footer tidak terdorong ke halaman kedua
Public Polling submit berulang
Public Pengaduan anonim + optional PDF/PNG/JPG/JPEG <= 5 MB
attachment tidak dapat dibuka sebagai public URL
Admin/Operator/PTSP full domain
Pimpinan readonly + export, no mutation/hard-delete
BK/Kesehatan/Guru/Wali/Siswa internal PTSP DENY
Layanan Baru -> Diproses -> Selesai
Pengaduan Masuk -> Diverifikasi -> Diproses/Selesai
hard delete Admin/Operator/PTSP only
XLSX mengikuti Tahun/filter
public stats 3 endpoint aggregate-only
public stats tidak mengeluarkan PII/raw record id
cross-origin GET public stats bekerja tanpa credentials
Settings Sistem menampilkan 3 card API PTSP
Copy URL bekerja
Copy Script API bekerja dan script memuat endpoint aktual
dashboard PTSP current-state sesuai locked KPI/action
mobile no horizontal body overflow
Konseling tetap confidential
```

Gate saat ini:

```text
G3.6B contract                LOCKED
G3.6B source                  IMPLEMENTED / feature branch
G3.6B localhost SQL           PREPARED
G3.6B static gate             RE-RUN PENDING after kiosk/auto-print refinement
G3.6B initial public UAT       PASS / user runtime evidence on prior head
G3.6B kiosk/auto-print re-smoke PASS / user runtime evidence on prior head
G3.6B PDF auto-download re-smoke PASS / user runtime evidence on prior head
G3.6B compact receipt re-smoke     PASS / user runtime evidence
G3.6B remaining runtime UAT         PASS ALL / user runtime evidence
G3.6B API settings card re-smoke    PASS / user runtime evidence
G3.6B post-SQL local dump            PASS / read-only dump audit
G3.6B fresh hosting dump             PASS / read-only dump audit
G3.6B hosting SQL                    PREPARED / static audited
G3.6B hosting SQL execution          PASS / user evidence
G3.6B post-SQL hosting dump audit    PASS / read-only dump audit
G3.6B hosting source deployment      PASS / user evidence
G3.6B focused hosting runtime smoke  PASS / user runtime evidence
PR Ready                            NOT AUTHORIZED
Merge                               NOT AUTHORIZED
```

## 20. G3.6C — Executive Visualization Gate

Static minimum:

```text
php -l seluruh PHP changed G3.6C
node --check assets/js/signage.js
node --check assets/js/statistik.js
php spark routes
git diff --check origin/main...HEAD
git status
```

Runtime Signage:

```text
/signage tetap public
header compact
summary H/S/I/A jumlah + persentase
coverage kelas tampil
3 panel stabil sesuai TemplateSIGNAGE
EWS internal rotation Sakit -> Izin -> Alpha
ranking EWS = Sesi Awal / 14 hari / max20
Kelas Belum Presensi auto-page
Jadwal Belum Jurnal = selesai+15 menit dan belum ada presensi_mengajar
rotasi 15 detik
refresh fetch 5 menit
tidak ada PII ekstra
```

Runtime Statistik:

```text
Admin/Operator/Pimpinan ALLOW
BK/Kesehatan/PTSP/Guru/Wali/Siswa DENY
Tahun Ajaran wajib
all/bulan ini/30 hari/custom
filter tingkat/kelas
Executive + Komposisi + Presensi + EWS + Pembelajaran
Pelanggaran tanpa poin
Prestasi
Konseling aggregate confidential: total/status/bidang/tren
Konseling tidak memuat nama siswa/topik/catatan/Guru BK/follow-up/jadwal individual
Konseling mengabaikan filter Tingkat/Kelas
UKS aggregate only
PTSP aggregate only
Mobilitas Siswa
ApexCharts lokal
Export PDF mengikuti filter
Export PDF section/card mengikuti urutan halaman Statistik
Export PDF memakai PNG hasil render ApexCharts halaman ketika JS tersedia
Export PDF fallback tetap authoritative bila PNG client tidak tersedia
Export PDF tidak memiliki duplicate axis label / black SVG artifact
Export PDF memakai explicit page sections; tidak ada orphan heading
Export PDF tercatat di log_activity
mobile no horizontal body overflow
```

Database expected setelah local SQL:

```text
G3.6C new tables    0
physical tables     informational / environment-sensitive
permissions         68
role_permissions    229
menus               51
role_menus          176
```

Observed audit:

```text
localhost post-UAT physical tables  46
hosting pre-G3.6C physical tables   45
local-only framework table          migrations
ci_sessions                          present on both
```

Framework/internal table count tidak menjadi invariant phase; yang dikunci adalah G3.6C tidak membuat tabel baru dan delta RBAC/menu di atas.

Gate:

```text
G3.6C source                     IMPLEMENTED / feature branch
G3.6C localhost SQL              PASS / user evidence
G3.6C GitHub structural audit    PASS / GitHub read evidence
G3.6C static terminal gate       PASS / user terminal evidence @ pre-parity-fix SHA
G3.6C focused static re-check    PASS / user terminal evidence
G3.6C local runtime UAT          PASS / user runtime evidence
G3.6C counseling aggregate       PASS / user runtime evidence
G3.6C PDF PNG parity re-smoke    PASS / user runtime evidence
G3.6C post-SQL dump audit        PASS / read-only dump audit
G3.6C table-count reconciliation PASS / user evidence
G3.6C fresh hosting pre-SQL audit PASS / read-only dump audit
G3.6C hosting SQL execution      PASS / user evidence
G3.6C post-SQL hosting dump      PASS / read-only dump audit
G3.6C hosting source deployment  PASS / user evidence
G3.6C hosting runtime smoke      PASS / user runtime evidence
G3.6C production gate            PASS ALL
PR #15                           CLOSED / MERGED
PR Ready                         PASS / user approval
Merge                            PASS / user approval
merge commit                     f82a0299c8989da6c1026d84861f3e95d786f7dd
```


## 21. Kartu Pelajar — JPG ZIP Front Per Kelas

Contract add-on:

```text
Admin only
no new permission
reuse kartu_pelajar.manage route gate
server effective-role Admin check
same dataset as existing Cetak Depan Per Kelas PDF
front only
1 siswa = 1 JPG
1 kelas = 1 ZIP
max 200 kartu
no DB/schema delta
```

Static gate:

```text
php -l app/Controllers/KartuPelajar.php
php -l app/Services/KartuPelajarService.php
php -l app/Services/KartuPrintService.php
php -l app/Services/KartuRenderService.php
php -l app/Views/kartu/daftar.php
node --check assets/js/kartu/daftar.js
php spark routes
git diff --check origin/main...HEAD
```

Runtime minimum:

```text
Admin melihat tombol Unduh JPG Depan Per Kelas (.ZIP)
Operator dan role lain tidak melihat tombol
direct POST /kartu/export-jpg-zip oleh non-Admin = DENY
pilih kelas wajib
dataset siswa/kartu sama dengan Cetak Depan Per Kelas PDF
hanya kartu Aktif
urutan nama konsisten
ZIP berisi 1 JPG per siswa
JPG hanya sisi depan
JPG memuat background/foto/QR/nomor/nama/NISN/kelas/JK/tahun/TTL/alamat
QR pada JPG dapat dipindai
layout JPG setara front card existing
maksimum 200 kartu
PDF existing front/back tetap normal
mobile tidak overflow
```

Gate add-on:

```text
Contract                          LOCKED / user decision
Source                            IMPLEMENTED / feature branch
DB / permission delta             NONE
Static gate                       PASS / user evidence
Local runtime UAT                 PASS / user runtime evidence @ pre-UI-polish
UI visual/mobile re-smoke         PASS / user runtime evidence
Hosting redeploy add-on           PASS / user evidence
Hosting runtime smoke             PASS / user runtime evidence
Add-on gate                       PASS ALL
PR #15                            CLOSED / MERGED
PR Ready                          PASS / user approval
Merge                             PASS / user approval
merge commit                      f82a0299c8989da6c1026d84861f3e95d786f7dd
```


## 22. G3.7 — Global Mobile Sweep Gate

G3.7 tidak mempunyai SQL/schema/RBAC delta.

Static minimum:

```text
php -l seluruh PHP changed G3.7
node --check seluruh JS changed G3.7
php spark routes
git diff --check origin/main...HEAD
git status
```

Viewport minimum:

```text
360×800
375×812
390×844
412×915
768×1024
1024×768
1366×768
```

Role/runtime matrix minimum:

```text
Admin
Operator
Pimpinan
BK
Guru
Guru + Wali
Siswa
Kesehatan
PTSP
Public PTSP
```

Acceptance:

```text
no body/document horizontal overflow
no table horizontal scroll role operasional
mobile primary information tetap lengkap secara fungsional
action tidak clipped
touch target utama nyaman
filter mobile stack/compact
modal/form keyboard-safe
pager usable
chart/card/list tidak keluar viewport
desktop/tablet regression PASS
RBAC/scope/period/privacy unchanged
expected DENY tetap DENY
no uncaught browser error
```

Heavy administrative matrix Admin/Operator dapat menjadi exception hanya jika dua dimensi tidak dapat direduksi tanpa kehilangan fungsi; setiap exception wajib dicatat eksplisit pada UAT.

Current gate:

```text
SSOT lock                     PASS / user approval
source implementation         IN PROGRESS / Wave 1 + Wave 2 + Wave 3 + Wave 4 implemented
Wave 1 GitHub diff audit      PASS / GitHub read evidence
Wave 1 static gate            PASS / user terminal evidence
Wave 1 runtime UAT            PARTIAL / overflow @720px PASS
Wave 2 GitHub diff audit      PASS / GitHub read evidence
Wave 2 static gate            PENDING
Wave 2 dashboard runtime UAT  PENDING
Wave 3 GitHub diff audit      PASS / GitHub read evidence
Wave 3 static gate            PENDING
Wave 3 runtime UAT            PENDING
Wave 4 GitHub diff audit      PASS / GitHub read evidence
Wave 4 static gate            PENDING
Wave 4 runtime UAT            PENDING
Matrix mobile                 CANDIDATE EXCEPTION / UAT REQUIRED
local viewport/runtime UAT    PARTIAL
cross-role regression         PENDING
hosting source deployment     NOT AUTHORIZED
hosting runtime smoke         PENDING
PR Ready                      NOT AUTHORIZED
Merge                         NOT AUTHORIZED
```


### Wave 1 — Global Foundation

Implemented scope:

```text
navbar/sidebar accessibility + role fallback
visualViewport height sync for keyboard-safe modal sizing
horizontal overflow diagnostic helper (manual/UAT only)
global min-width/max-width responsive guards
reusable mobile card/wrap primitives
compact mobile pagination without horizontal pager strip
searchable-select mobile size/viewport guards
modal footer touch targets + responsive width guard
```

Changed runtime files:

```text
app/Views/_navbar.php
app/Views/_sidebar.php
assets/js/main.js
assets/js/components/pagination.js
assets/css/searchable-select.css
assets/css/sisfour-mobile.css
assets/css/sisfour-modal.css
```

Evidence:

```text
implementation            IMPLEMENTED
GitHub diff audit          PASS / GitHub read evidence
automated GitHub CI        NONE / no workflow runs on exact head
terminal syntax gate       PENDING
local viewport re-smoke    PENDING
```

Manual overflow diagnostic untuk UAT dapat dipanggil di browser console:

```js
SisfourLayoutDiagnostics.horizontalOverflowReport()
```

Helper tersebut read-only dan tidak mengubah layout/business state.


### Wave 2 — Dashboard Seluruh Role

Audit seluruh dashboard dilakukan sebelum mutation.

```text
Pimpinan   = existing mobile-adaptive / regression-only
Guru       = existing mobile-adaptive / regression-only
Guru+Wali  = existing mobile-adaptive / regression-only
Siswa      = existing mobile-adaptive / regression-only

Admin      = mobile Tren + Aktivitas list; desktop table preserved
Operator   = canonical page header + mobile Tren/Aktivitas list; desktop table preserved
BK         = KPI 2×2 canonical + header/list wrap safety
Kesehatan  = KPI 2×2 + primary/quick action + recent-list mobile safety
PTSP       = KPI 2×2 + quick action + recent-list mobile safety
```

Changed runtime files:

```text
app/Views/dashboard_admin.php
app/Views/dashboard_operator.php
app/Views/dashboard_bk.php
app/Views/dashboard_kesehatan.php
app/Views/dashboard_ptsp.php
```

Invariant:

```text
Controller / Service / Model = unchanged
route / RBAC / scope         = unchanged
dashboard payload            = unchanged
KPI/query/business meaning   = unchanged
DB/schema/SQL                = NONE
```

Evidence:

```text
Wave 2 implementation       IMPLEMENTED
Wave 2 GitHub diff audit    PASS / GitHub read evidence
Wave 2 static terminal gate PENDING
Wave 2 dashboard runtime    PENDING
```


### Wave 3 — BK + Presensi + Laporan Guru/Wali

Audit sebelum mutation:

```text
BK Kasus                  = existing mobile list / regression-only
BK Konseling              = existing mobile list / regression-only
BK Prestasi               = existing mobile list / regression-only
Presensi Siswa Input      = existing mobile table/status grid / regression-only
Presensi Mengajar         = existing mobile form/status grid / regression-only
Presensi Mengajar Laporan = existing mobile list / regression-only
Laporan Jurnal            = existing mobile list + fullscreen detail / regression-only
```

Implemented gap:

```text
BK Master Pelanggaran
- desktop table preserved
- mobile list mirrors same server rows
- Edit/Hapus operate against same row identity
- pagination state shared

EWS Presensi Siswa
- desktop table preserved
- mobile list shows siswa + total Alpha
- same in-memory rows + same pagination

Rekap Presensi Siswa
- desktop table preserved
- mobile list shows siswa + tanggal/sesi + status
- self-view and scoped-view use same server result
- same server-side pagination/filter state
```

Changed runtime files:

```text
app/Views/bk/pelanggaran.php
assets/js/bk/pelanggaran.js
app/Views/presensi/siswa_ews.php
assets/js/presensi/siswa-ews.js
app/Views/presensi/siswa_rekap.php
assets/js/presensi/siswa-rekap.js
```

Matrix Presensi:

```text
status = CANDIDATE EXCEPTION
reason = intrinsically 2D (siswa × tanggal)
rule   = horizontal scroll boleh hanya di matrix container
UAT    = document/body tetap tidak overflow
final exception = belum dikunci sampai runtime verification
```

Invariant:

```text
Controller / Service / Model = unchanged
route / RBAC / scope         = unchanged
filter/pagination semantics  = unchanged
DB/schema/SQL                = NONE
```

Evidence:

```text
Wave 3 implementation       IMPLEMENTED
Wave 3 GitHub diff audit    PASS / GitHub read evidence
Wave 3 static terminal gate PENDING
Wave 3 runtime UAT          PENDING
```


### Wave 4 — UKS + PTSP

Audit sebelum mutation:

```text
UKS CKG          = existing mobile list + desktop table
UKS Harian       = existing mobile list + desktop table
PTSP Layanan     = existing mobile list + desktop table
PTSP Pengaduan   = existing mobile list + desktop table
PTSP Polling     = existing mobile list + desktop table
PTSP Public      = existing responsive public CSS / regression-only
```

Implemented polish:

```text
UKS CKG
- page/filter/export actions mendapat touch target
- modal data + import fullscreen-sm-down / scrollable
- mobile identity/status row wrap-safe
- Edit/Hapus mobile compact touch target

UKS Harian
- page/filter/export actions mendapat touch target
- modal catatan fullscreen-sm-down
- mobile identity/keluhan/tindakan/hasil/petugas wrap-safe
- Edit/Hapus mobile compact touch target

PTSP Layanan
- page/filter/export actions mendapat touch target
- card header wrap-safe
- modal Tambah Internal scrollable + fullscreen-sm-down
- mobile pemohon/layanan/status/petugas wrap-safe
- mutation actions + pager compact touch target

PTSP Pengaduan
- filter/export/header mobile polish
- judul/klasifikasi/isi/status/lampiran wrap-safe
- status/delete/lampiran action touch target
- pager wrap-safe

PTSP Polling
- filter/export/header mobile polish
- responden/kategori/score/kepuasan/masukan wrap-safe
- delete + pager compact touch target
```

Changed runtime files:

```text
app/Views/uks/ckg.php
app/Views/uks/harian.php
assets/js/uks/ckg.js
assets/js/uks/harian.js
app/Views/ptsp/layanan.php
app/Views/ptsp/pengaduan.php
app/Views/ptsp/polling.php
assets/js/ptsp/layanan.js
assets/js/ptsp/pengaduan.js
assets/js/ptsp/polling.js
```

Invariant:

```text
Controller / Service / Model = unchanged
route / permission / scope   = unchanged
UKS / PTSP workflow          = unchanged
filter/pagination semantics  = unchanged
Public PTSP contract         = unchanged
DB/schema/SQL                = NONE
```

Evidence:

```text
Wave 4 implementation       IMPLEMENTED
Wave 4 GitHub diff audit    PASS / GitHub read evidence
Wave 4 static terminal gate PENDING
Wave 4 runtime UAT          PENDING
```
