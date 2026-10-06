# Testing, Regression & Release Gate — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 5 Oktober 2026
**Phase aktif:** **G4.0 — Cordova Android Foundation; Environment Preflight PASS; Architecture Lock APPROVED**

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
G3.7   CLOSED / MERGED — PR #16
G3.8   CLOSED / MERGED — PR #17
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
G3.7 runtime source head            00bbef3ee5ba2310a5cecc88c571a6e4a7ead853
G3.7 source                         IMPLEMENTED / Wave 1–7B complete
G3.7 Wave 1 GitHub diff audit       PASS / GitHub read evidence
G3.7 Wave 1 static gate             PASS / user terminal evidence
G3.7 Wave 1 runtime UAT             PASS / user runtime evidence via Wave 8 full regression
G3.7 Wave 2 GitHub diff audit       PASS / GitHub read evidence
G3.7 Wave 2 static gate             PASS / user terminal evidence
G3.7 Wave 2 dashboard runtime UAT   PASS / user runtime evidence via Wave 8 full regression
G3.7 Wave 3 GitHub diff audit       PASS / GitHub read evidence
G3.7 Wave 3 static gate             PASS / user terminal evidence
G3.7 Wave 3 runtime UAT             PASS / user runtime evidence via Wave 8 full regression
G3.7 Wave 4 GitHub diff audit       PASS / GitHub read evidence
G3.7 Wave 4 static gate             PASS / user terminal evidence
G3.7 Wave 4 runtime UAT             PASS / user runtime evidence via Wave 8 full regression
G3.7 Wave 5 GitHub diff audit       PASS / GitHub read evidence
G3.7 Wave 5 static gate             PASS / user terminal evidence
G3.7 Wave 5 runtime UAT             PASS / user runtime evidence via Wave 8 full regression
G3.7 Wave 6 GitHub diff audit       PASS / GitHub read evidence
G3.7 Wave 6 static gate             PASS / user terminal evidence
G3.7 Wave 6 runtime UAT             PASS / user runtime evidence via Wave 8 full regression
G3.7 Wave 7A GitHub diff audit      PASS / GitHub read evidence
G3.7 Wave 7A static gate            PASS / user terminal evidence
G3.7 Wave 7A runtime UAT            PASS / user runtime evidence via Wave 8 full regression
G3.7 Wave 7B GitHub diff audit      PASS / GitHub read evidence
G3.7 Wave 7B static gate            PASS / user terminal evidence
G3.7 Wave 7B runtime UAT            PASS / user runtime evidence via Wave 8 full regression
G3.7 Wave 8 full viewport regression PASS / user runtime evidence
G3.7 Settings Menu mobile           PASS / user runtime evidence — documented matrix exception
G3.7 Kenaikan bulk mobile           PASS / user runtime evidence — documented local-scroll exception
G3.7 Kelulusan bulk mobile          PASS / user runtime evidence — documented local-scroll exception
G3.7 Matrix mobile                  PASS / user runtime evidence — local-scroll exception
G3.7 local viewport/runtime UAT     PASS / user runtime evidence
G3.7 cross-role regression          PASS / user runtime evidence
G3.7 local gate                     PASS ALL
G3.7 hosting deployment             PASS / user evidence
G3.7 hosting runtime smoke          PASS / user runtime evidence
G3.7 production gate                PASS ALL
G3.7 PR #16                         CLOSED / MERGED
G3.7 PR Ready                       PASS / user approval
G3.7 Merge                          PASS / user approval
G3.7 feature head                   7fb760c0945b33c0739e7ef83b0cbeeabfc7b295
G3.7 merge commit                   7a595f21b70d9bfc28272b7f8ba19a2dfd3e60f9
```

## 18. Roadmap

```text
G3.6A  UKS / Kesehatan       CLOSED / MERGED — PR #13
G3.6B  PTSP                  CLOSED / MERGED — PR #14
G3.6C  Executive Viz/Signage CLOSED / MERGED — PR #15
G3.7   Global Mobile Sweep   CLOSED / MERGED — PR #16
G3.8   WebView Readiness      CLOSED / MERGED — PR #17
G4     Cordova APK             G4.1 ACTIVE — THIN-WRAPPER RECOVERY / REBUILD + DEVICE UAT PENDING
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
runtime source head           00bbef3ee5ba2310a5cecc88c571a6e4a7ead853
source implementation         IMPLEMENTED / Wave 1–7B complete
Wave 1 GitHub diff audit      PASS / GitHub read evidence
Wave 1 static gate            PASS / user terminal evidence
Wave 1 runtime UAT            PASS / user runtime evidence via Wave 8 full regression
Wave 2 GitHub diff audit      PASS / GitHub read evidence
Wave 2 static gate            PASS / user terminal evidence
Wave 2 dashboard runtime UAT  PASS / user runtime evidence via Wave 8 full regression
Wave 3 GitHub diff audit      PASS / GitHub read evidence
Wave 3 static gate            PASS / user terminal evidence
Wave 3 runtime UAT            PASS / user runtime evidence via Wave 8 full regression
Wave 4 GitHub diff audit      PASS / GitHub read evidence
Wave 4 static gate            PASS / user terminal evidence
Wave 4 runtime UAT            PASS / user runtime evidence via Wave 8 full regression
Wave 5 GitHub diff audit      PASS / GitHub read evidence
Wave 5 static gate            PASS / user terminal evidence
Wave 5 runtime UAT            PASS / user runtime evidence via Wave 8 full regression
Wave 6 GitHub diff audit      PASS / GitHub read evidence
Wave 6 static gate            PASS / user terminal evidence
Wave 6 runtime UAT            PASS / user runtime evidence via Wave 8 full regression
Wave 7A GitHub diff audit     PASS / GitHub read evidence
Wave 7A static gate           PASS / user terminal evidence
Wave 7A runtime UAT           PASS / user runtime evidence via Wave 8 full regression
Wave 7B GitHub diff audit     PASS / GitHub read evidence
Wave 7B static gate           PASS / user terminal evidence
Wave 7B runtime UAT           PASS / user runtime evidence via Wave 8 full regression
Wave 8 full viewport UAT     PASS / user runtime evidence
Settings Menu mobile          PASS / user runtime evidence — documented matrix exception
Kenaikan bulk mobile          PASS / user runtime evidence — documented local-scroll exception
Kelulusan bulk mobile         PASS / user runtime evidence — documented local-scroll exception
Matrix mobile                 PASS / user runtime evidence — local-scroll exception
local viewport/runtime UAT    PASS / user runtime evidence
cross-role regression         PASS / user runtime evidence
local G3.7 gate               PASS ALL
hosting source deployment     PASS / user evidence
hosting runtime smoke         PASS / user runtime evidence
production gate               PASS ALL
PR #16                        CLOSED / MERGED
PR Ready                      PASS / user approval
Merge                         PASS / user approval
feature head                  7fb760c0945b33c0739e7ef83b0cbeeabfc7b295
merge commit                  7a595f21b70d9bfc28272b7f8ba19a2dfd3e60f9
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
terminal syntax gate       PASS / user terminal evidence
local viewport re-smoke    PASS / user runtime evidence via Wave 8 full regression
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
Wave 2 static terminal gate PASS / user terminal evidence
Wave 2 dashboard runtime    PASS / user runtime evidence via Wave 8 full regression
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
status = ACCEPTED / user runtime evidence via Wave 8 full regression
reason = intrinsically 2D (siswa × tanggal)
rule   = horizontal scroll boleh hanya di matrix container
UAT    = document/body tidak overflow
final exception = documented local-scroll exception
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
Wave 3 static terminal gate PASS / user terminal evidence
Wave 3 runtime UAT          PASS / user runtime evidence via Wave 8 full regression
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
Wave 4 static terminal gate PASS / user terminal evidence
Wave 4 runtime UAT          PASS / user runtime evidence via Wave 8 full regression
```


### Wave 5 — Siswa + Kartu + Profile

Audit sebelum mutation:

```text
Dashboard Siswa       = existing mobile-adaptive / regression-only
Kartu Daftar          = table-only gap
Kartu Preview         = fixed physical canvas with local horizontal scroll
Profile Siswa         = biodata adaptive; class history table-only gap
Profile Guru          = existing responsive profile primitive
Profile Pegawai       = existing responsive profile primitive
Personalia Detail     = existing mobile record-card; edit modals need mobile polish
Kartu PDF/JPG output  = physical renderer / regression-only
Portfolio PDF         = output renderer / regression-only
```

Implemented polish:

```text
Kartu Daftar
- desktop table preserved
- mobile card list mirrors same server rows
- mobile/desktop checkbox controls synchronized by card ID
- selectedIds deduplicated before print request
- Preview/PDF/Reissue touch-friendly
- pagination/filter/generate/reissue/cetak endpoints unchanged

Kartu Preview
- page header/action mobile-safe
- physical 1011×638 canvas preserved
- horizontal scroll remains local to .kartu-scroll

Profile Siswa
- readonly contract preserved
- biodata long values wrap-safe
- Riwayat Kelas gets mobile record-card renderer
- desktop table preserved

Profile Guru / Pegawai
- existing responsive layout preserved
- navigation/hero/upload/save actions touch-friendly
- long metadata wrap-safe
- Pegawai page header aligned to canonical primitive

Personalia Detail
- existing desktop tables + mobile record-cards preserved
- 4 edit modals become scrollable fullscreen-sm-down
- mobile document/edit/delete/add actions get touch target
- record title/meta wrap-safe
```

Changed runtime files:

```text
app/Views/kartu/daftar.php
assets/js/kartu/daftar.js
assets/css/kartu-daftar.css
app/Views/kartu/preview.php
app/Views/profile/siswa.php
app/Views/profile/guru.php
app/Views/profile/pegawai.php
app/Views/personalia/detail.php
```

Invariant:

```text
Kartu generate/reissue          = unchanged
PDF/JPG ZIP output              = unchanged
selected-card print semantics   = unchanged
Profile persistence             = unchanged
Personalia persistence          = unchanged
Controller / Service / Model    = unchanged
route / RBAC / scope            = unchanged
DB/schema/SQL                   = NONE
```

Evidence:

```text
Wave 5 implementation       IMPLEMENTED
Wave 5 GitHub diff audit    PASS / GitHub read evidence
Wave 5 static terminal gate PASS / user terminal evidence
Wave 5 runtime UAT          PASS / user runtime evidence via Wave 8 full regression
```


### Wave 6 — Statistik + Remaining Operational Surfaces

Audit sebelum mutation:

```text
Statistik                = responsive grid existing; chart breakpoint/containment gap
UKS Master               = card/list existing; action/modal mobile polish gap
Signage                  = special public display surface / regression-only
Log Activity             = Admin/Operator administrative surface -> Wave 7
Master/Settings/Backup   = Admin/Operator administrative surface -> Wave 7
BK TOP                   = legacy view; no active route found
BK Konseling Settings    = view/controller exist; explicit route registration not found
```

G3.7 tidak menambah atau memperbaiki route orphan/ambiguity. Route/RBAC tetap unchanged.

Implemented polish:

```text
Statistik
- filter/context/card headers wrap-safe
- chart/card containers min-width:0 and max-width containment
- ApexCharts width 100% + parent/window resize
- responsive breakpoints at 576px and 768px
- mobile legend/axis font/label overlap guards
- chart heights compact on narrow screens
- data payload, section order, and PDF export contract unchanged

UKS Master
- card headers and list rows wrap-safe
- nama/status/action do not force document overflow
- Tambah/Edit/Nonaktifkan compact touch targets
- modal becomes scrollable fullscreen-sm-down
- create/update/delete semantics unchanged
```

Changed runtime files:

```text
app/Views/statistik/index.php
assets/css/statistik.css
assets/js/statistik.js
app/Views/uks/master.php
```

Invariant:

```text
Statistik payload/agregasi       = unchanged
Statistik PDF output             = unchanged
UKS Master persistence           = unchanged
Controller / Service / Model     = unchanged
route / RBAC / scope             = unchanged
DB/schema/SQL                    = NONE
```

Evidence:

```text
Wave 6 implementation       IMPLEMENTED
Wave 6 GitHub diff audit    PASS / GitHub read evidence
Wave 6 static terminal gate PASS / user terminal evidence
Wave 6 runtime UAT          PASS / user runtime evidence via Wave 8 full regression
```


### Wave 7A — Adaptive Admin Surfaces

Scope:

```text
Backup Database
Log Activity
Settings User
Master Kelas
Master Mata Pelajaran
Master Tahun Ajaran
Mapping Wali Kelas
Recycle Bin Guru
Recycle Bin Pegawai
Recycle Bin Siswa
Recycle Bin Kelas
Histori / Recycle Bin Wali Kelas
Recycle Bin Tahun Ajaran
```

Implemented pattern:

```text
desktop table              = preserved
mobile list/card           = added from same runtime dataset
pagination/filter state    = shared with existing renderer where present
mobile action              = existing handler or proxy to existing desktop handler
touch target               = compact/primary mobile primitive
modal                      = fullscreen-sm-down where relevant
business endpoint          = unchanged
```

Wave 7A intentionally excludes:

```text
Master Guru / Pegawai / Siswa
Jadwal Guru
Manajemen Siswa Kelas/Kenaikan/Kelulusan/Mutasi
Settings Menu matrix
Settings Sistem
```

Those remain Wave 7B / documented-exception work.

Changed runtime files: 26 files = 13 View/JS pairs.

Invariant:

```text
CRUD semantics                  = unchanged
restore/force-delete semantics  = unchanged
role/user semantics             = unchanged
pagination/filter authority     = unchanged
Controller / Service / Model    = unchanged
route / RBAC / scope            = unchanged
DB/schema/SQL                   = NONE
```

Evidence:

```text
Wave 7A implementation       IMPLEMENTED
Wave 7A GitHub diff audit    PASS / GitHub read evidence
Wave 7A static terminal gate PASS / user terminal evidence
Wave 7A runtime UAT          PASS / user runtime evidence via Wave 8 full regression
```


### Wave 7B — Heavy CRUD + Documented Exceptions

Implemented adaptive surfaces:

```text
Master Guru
Master Pegawai
Master Siswa
Jadwal Guru
Manajemen Siswa — Atur Kelas
Manajemen Siswa — Mutasi
```

Hybrid surfaces:

```text
Manajemen Siswa — Kenaikan
- outer class/progress surface gets mobile companion view
- bulk student checkbox table remains local horizontal-scroll
- selection/process semantics unchanged

Manajemen Siswa — Kelulusan
- source classes + alumni history get mobile companion views
- bulk student checkbox table remains local horizontal-scroll
- restore/process semantics unchanged
```

Documented matrix exception:

```text
Settings Menu
- true 2D menu × role matrix
- no card conversion
- horizontal scroll restricted to table-responsive container
- matrix container has region label + keyboard focus
- document/body overflow remains forbidden
```

Settings Sistem remains regression-only because it is form/card based and did not require a presentation rewrite.

Changed runtime files:

```text
app/Views/master/guru.php
assets/js/master/guru.js
app/Views/master/pegawai.php
assets/js/master/pegawai.js
app/Views/master/siswa.php
assets/js/master/siswa.js
app/Views/master/jadwal_guru.php
assets/js/master/jadwal-guru.js
app/Views/manajemen_siswa/kelas.php
assets/js/manajemen_siswa/kelas.js
app/Views/manajemen_siswa/kenaikan.php
assets/js/manajemen_siswa/kenaikan.js
app/Views/manajemen_siswa/kelulusan.php
assets/js/manajemen_siswa/kelulusan.js
app/Views/manajemen_siswa/mutasi.php
assets/js/manajemen_siswa/mutasi.js
app/Views/settings/menu.php
```

Invariant:

```text
Guru/Pegawai/Siswa CRUD            = unchanged
Jadwal CRUD/import                 = unchanged
Atur Kelas semantics               = unchanged
Kenaikan/Kelulusan bulk semantics  = unchanged
Mutasi/restore semantics           = unchanged
Menu-role mapping semantics        = unchanged
Controller / Service / Model       = unchanged
route / RBAC / scope               = unchanged
DB/schema/SQL                      = NONE
```

Evidence:

```text
Wave 7B implementation       IMPLEMENTED
Wave 7B GitHub diff audit    PASS / GitHub read evidence
Wave 7B JS parse-only check  PASS / GitHub read evidence
Wave 7B static terminal gate PASS / user terminal evidence
Wave 7B runtime UAT          PASS / user runtime evidence via Wave 8 full regression
```

Exception acceptance telah dipenuhi melalui Wave 8 full viewport regression: body/document horizontal overflow tetap false; scroll hanya lokal pada surface exception.


### Wave 8 — Full Viewport Regression Closure

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

Evidence closure:

```text
runtime source head                 00bbef3ee5ba2310a5cecc88c571a6e4a7ead853
Wave 8 full viewport regression     PASS / user runtime evidence
local viewport/runtime UAT          PASS / user runtime evidence
cross-role regression               PASS / user runtime evidence
Settings Menu matrix exception      PASS / user runtime evidence
Kenaikan bulk local-scroll          PASS / user runtime evidence
Kelulusan bulk local-scroll         PASS / user runtime evidence
Matrix Presensi local-scroll        PASS / user runtime evidence
desktop/tablet regression           PASS / user runtime evidence
body/document horizontal overflow   PASS / user runtime evidence
local G3.7 gate                     PASS ALL
```

G3.7 CLOSED / MERGED melalui PR #16. Hosting source deployment dan hosting runtime smoke telah PASS sebelum merge; merge commit = `7a595f21b70d9bfc28272b7f8ba19a2dfd3e60f9`.


## 23. G3.8 — Viewport/WebView Readiness Gate

Baseline:

```text
main   = 7a595f21b70d9bfc28272b7f8ba19a2dfd3e60f9
branch = feat/g3-8-webview-readiness-20260919
```

Scope G3.8 adalah source-Web readiness. Tidak ada project/plugin Cordova, native bridge, APK, signing, atau schema change pada phase ini.

Static/read audit minimum:

```text
viewport meta + viewport-fit pada shell/login/public standalone relevan
safe-area token tersedia
visualViewport integration tidak regression
csrf-fetch session-expiry recovery tetap aktif
tidak ada Cordova/native dependency prematur
php -l seluruh PHP changed bila ada source mutation
node --check seluruh JS changed bila ada source mutation
php spark routes
git diff --check origin/main...HEAD
git status
```

Runtime readiness minimum:

```text
360×800
390×844
412×915
landscape / short-height representative viewport
768×1024
1024×768
1366×768

login/logout/redirect normal
session expiry pada Fetch kembali ke login; HTML login tidak dirender sebagai JSON/table/modal
keyboard open/close tidak menutup active input
fullscreen/scrollable modal tetap usable dengan keyboard
SearchableSelect tetap terlihat/usable saat keyboard terbuka
safe-area tidak menutup header/footer/action
body/document horizontal overflow = false
network failure tidak menjadi sukses palsu
busy guard / anti double-submit tidak regression
browser file input dapat dipakai pada surface yang memilikinya
browser PDF/XLSX/export/preview tetap reachable
internal navigation tetap same-origin
external-link inventory tidak memerlukan native assumption
RBAC/scope/period/privacy unchanged
expected DENY tetap DENY
no uncaught browser error
```

Native-only G4 gate yang belum boleh diklaim G3.8:

```text
deviceready
Android Back
native geolocation permission
native download/open/share
external browser/app intent
status bar / edge-to-edge native configuration
signed APK
multi-device real APK regression
```

Closure gate:

```text
G3.8 SSOT lock                PASS / user approval
branch                        feat/g3-8-webview-readiness-20260919
baseline main                 7a595f21b70d9bfc28272b7f8ba19a2dfd3e60f9
read-only readiness audit     PASS / GitHub read evidence
viewport-fit foundation       PRESENT / GitHub read evidence
safe-area foundation          PRESENT / GitHub read evidence
visualViewport foundation     PRESENT / GitHub read evidence
session-expiry Fetch recovery PRESENT / GitHub read evidence
Cordova project/plugin        ABSENT / CORRECT FOR G3.8
source implementation         IMPLEMENTED / Wave 1A+1B
runtime source head            d6640d0e11fe48f9e47266756b7da6cfc029bcec
source diff audit              PASS / GitHub read evidence
static gate                    PASS / user terminal evidence
runtime readiness UAT          PASS / user runtime evidence
local G3.8 readiness           PASS
hosting deployment            NOT AUTHORIZED / NOT EXECUTED
feature head                  52143aad25b2d273ee585bcc318ebdedcb3ccfa4
PR #17                        CLOSED / MERGED
PR Ready                      PASS / user approval
Merge                         PASS / user approval
merge commit                  2a22d4d8a4d8fce9ec1dd27504b9ef77c357dec9
main after merge              2a22d4d8a4d8fce9ec1dd27504b9ef77c357dec9
G4/Cordova                    NOT STARTED
```


## G3.9 — Dashboard Experience V2 Test Gate

G3.9 menambah regression untuk composition tanpa mengurangi gate G3.7/G3.8.

### Static / Unit

Minimal:

```text
Primary Role berasal dari users.role
effective permission tetap users.role UNION user_roles.role
secondary role tidak mengganti Metric/EWS/Data owner
Operator/Pimpinan secondary tidak menghasilkan action section
eligible operational secondary menghasilkan action catalog permission-aware
Wali tetap context Guru
Admin/Siswa exclusive
whitelist double/triple ditegakkan service-side
Role 2/3 ordering deterministik
valid staff identity = id_guru OR id_pegawai
tidak ada dual Guru+Pegawai person identity
```

Unit test lama yang mengunci:

```text
admin > operator > pimpinan > bk > ...
```

sebagai pemilih Dashboard harus diganti dengan test Primary Role G3.9. Static
experience priority lama tidak boleh tetap diam-diam menentukan Home.

### Guru/Wali State

Wajib test:

```text
not_applicable
not_started
available
submitted
wali_available
ended
```

Expected presentation:

```text
submitted      → completed/green
ended          → grey disabled
wali_available → tetap actionable
```

Wali tidak mendapat Jurnal privilege.

### Visual / Responsive

Minimal viewport:

```text
360×800
390×844
412×915
768×1024
1024×768
1366×768
```

Check:

```text
action vs metric terbaca jelas
semua enabled dashboard action memakai functional colored gradient
enabled dashboard action memakai shared action shadow
metric/status/context/work-surface tetap flat tanpa action shadow
disabled/expired action flat grey tanpa gradient/shadow
metric tidak tampak clickable
focus visible
touch target aman
no body/document horizontal overflow
no horizontal operational table scroll
desktop/tablet tidak regression
WebView readiness G3.8 tidak regression
```

### Security

```text
no new permission leak
scope/period tetap
Konseling privacy tetap
Siswa self-scope tetap
UKS/PTSP/BK authorization tetap server-side
invalid role combination ditolak server
```

G3.9 belum boleh dinyatakan CLOSED hanya dari screenshot; composition, permission,
identity, viewport, dan negative-path test wajib ikut lulus.

### G3.9E — Regression & Closure Current Gate

Static/source audit:

```text
Primary Role owns Dashboard                    PASS / source audit
secondary action only, no secondary payload   PASS / source audit
Pimpinan/Guru/Wali/Siswa no Konseling payload PASS / source audit
Dashboard legacy quick-action patterns         PASS / source audit
enabled Dashboard action gradient contract     PASS / source audit
multi-role Sidebar role provenance             PASS / source audit
Sidebar incremental leaf dedupe                PASS / unit/source audit
Profile identity routing                       PASS / source audit
Profile Guru/Siswa role_menus visibility       PASS / source audit
single-role Sidebar path preserved             PASS / source audit
```

Focused user runtime evidence:

```text
Guru + Kesehatan             PASS
BK + Kesehatan + PTSP        PASS
Guru + Wali + Kesehatan      PASS
Guru + PTSP                  PASS
functional gradient UI       PASS
multi-role Sidebar grouping  PASS
```

Technical gate pada exact head:

```text
PHP lint changed PHP files       PASS / user local runtime evidence
node --check changed JS          PASS / user local runtime evidence
FunctionalConsistencyTest       PASS — 22 tests / 59 assertions
php spark routes                 PASS / user local runtime evidence
git diff --check                 PASS
clean working tree               PASS
code coverage driver warning     NON-BLOCKING
```

Final runtime gate yang masih wajib sebelum G3.9 CLOSED / PR Ready:

```text
single-role smoke:
Admin / Operator / Pimpinan / BK / Kesehatan / PTSP / Guru / Guru+Wali / Siswa

multi-role smoke:
Guru+Kesehatan
Guru+PTSP
Guru+Wali+PTSP
BK+Kesehatan+PTSP
BK+Operator+Kesehatan

negative/edge:
no active period
identity unavailable
permission partially removed
Menu & Role item disabled
expired-session logout recovery
active Sidebar parent/open state

viewport:
360×800
390×844
412×915
768×1024
1024×768
1366×768
```


## G3.10 — Student Services Expansion Gate

### G3.10A BK Group Recording

```text
Pelanggaran kelompok:
- create 2+ siswa all-or-nothing
- invalid satu siswa membatalkan seluruh batch
- setiap child mempunyai id_kelompok yang sama
- individual record tetap id_kelompok NULL
- self/history/dashboard masih per siswa
- follow-up individual tetap bekerja
- group fan-out tidak membuat partial child

Konseling kelompok:
- parent + anggota + follow-up persistence terpisah
- member exact membership pada Tahun aktif
- lintas kelas periode yang sama diperbolehkan
- no-delete parent/follow-up
- status parent mengikuti follow-up terbaru
- non Admin/Operator/BK direct access DENY
- export tidak flatten confidential parent per siswa

XLSX:
- group-aware sheets
- exact period filter
- user text explicit string
- max 50.000 row per sheet
```

### G3.10B Student Document Center

```text
Admin/Operator manual INDIVIDU create tanpa period ownership
Admin/Operator manual TINGKAT create tanpa stored id_tahun
Siswa INDIVIDU tetap visible tanpa Tahun Ajaran aktif
TINGKAT eligibility memakai current membership pada Tahun Ajaran aktif
No active period → TINGKAT tidak tampil
INDIVIDU cross-student direct open DENY
non Google Drive URL DENY
format only PDF / IMAGE
Lulus/Pindah/Keluar tetap dapat Dokumen Individu

Bulk:
- context hanya Judul + Format
- template DATA_DOKUMEN + PETUNJUK
- PETUNJUK memuat clickable canonical Spreadsheet Helper URL
- template filter semua siswa / tingkat / kelas memakai periode aktif hanya untuk roster
- authoritative columns NISN + LINK GOOGLE DRIVE
- duplicate NISN row in file DENY
- unknown NISN DENY
- duplicate DB id_siswa+normalized judul DENY
- any blocking error => no commit
- valid preview => one transaction commit
- id_import_batch recorded
- rollback metadata only, never Drive file

Hard delete:
- selected IDs only
- permission dokumen_siswa.hard_delete
- every target revalidated server-side
- deletion snapshot created
- access logs dependent deleted
- dokumen_siswa rows hard-deleted transactionally
- Google Drive never mutated/deleted

Export:
- no Tahun Ajaran/Semester ownership columns
- current class optional display only
- Admin/Operator only
- access log sheet included

UI / Dashboard:
- Data Dokumen desktop table memakai hierarchy Dokumen / Penerima / Status / Pencatat / Aksi
- mobile Data Dokumen tetap adaptive list dari dataset yang sama
- Data Dokumen mempunyai canonical pager + page size 25/50/100
- filter mempertahankan page size dan reset offset ke halaman awal
- hard-delete selection tetap current-page scoped
- Dashboard Siswa menampilkan Dokumen Saya hanya bila dokumen_siswa.view_self tersedia
- Dokumen Saya berada pada Self-Service Access, bukan operational Primary Action Surface
- action Dokumen Saya menuju dokumen-saya dan memakai Cyan action family
```

Runtime/UAT evidence 2026-10-05:

```text
Manual Input Dokumen                  PASS
Import Massal                         PASS
Dokumen Saya Role Siswa               PASS
Data Dokumen table + pager            PASS
Dashboard Siswa → Dokumen Saya        PASS
Cross-student direct open DENY        PASS
Invalid Drive URL rejection           PASS
Duplicate document rejection          PASS
Hard delete + delete snapshot         PASS
Google Drive untouched                PASS
Access audit OPEN                     PASS
Metadata + access-log export          PASS
Session stability after hardening     PASS
Mobile 360px / responsive smoke       PASS
```

Static gate remains:

```text
php -l changed PHP
node --check changed JS
php spark routes
git diff --check
unit tests
working tree clean
```

Final executable-head regression evidence 2026-10-05:

```text
Executable local/remote head           a0b7ec6
PHP lint changed closure surfaces      PASS
JavaScript syntax                      PASS
Routes                                 PASS
FunctionalConsistencyTest              PASS — 26/26, 75 assertions
Code coverage driver                   WARNING ONLY
git diff --check                       PASS
working tree                           CLEAN / synced with origin
```

Regression dijalankan pada executable head `a0b7ec6`. Commit setelah head tersebut hanya
closure documentation/status sync dan tidak mengubah PHP/JS/runtime behavior.

Dengan runtime/UAT dan final executable-head regression sama-sama PASS, G3.10 berada pada
status **CLOSURE-READY**.

CLOSURE-READY bukan approval untuk Ready/merge/deploy. PR #19 tetap Draft sampai ada
approval eksplisit user.

## G4.0 — Environment & Architecture Lock Gate

Canonical baseline:

```text
main / origin-main                de3efc5119f811d30f4c1759e20d244106ebd899
feature branch                    feat/g4-cordova-android-20261005
G3.9 PR #18                       CLOSED / MERGED
G3.10 PR #19                      CLOSED / MERGED
```

Environment preflight evidence 2026-10-05:

```text
JDK 17.0.20.1                     PASS
Node 24.19.0                      PASS
npm 11.17.0                       PASS
Cordova CLI 13.0.0                PASS
ANDROID_HOME                      PASS
Android CLI                       PASS
ADB / Platform Tools              PASS
Android Platform API 36           PASS
Build Tools 36.0.0                PASS
Command-line Tools                PASS
system Gradle 8.14.4              PASS
working tree / canonical main     PASS
```

Architecture gate:

```text
local Cordova shell               LOCKED
controlled InAppBrowser           LOCKED
production HTTPS only             LOCKED
Web session + CSRF unchanged      LOCKED
server authorization boundary     LOCKED
narrow native bridge              LOCKED
no offline mutation replay        LOCKED
Android Back                      PENDING G4.1 APK proof
authenticated native download     PENDING G4.1 APK proof
geolocation permission/runtime    PENDING G4.1 APK proof
external intent routing           PENDING G4.1 APK proof
```

G4.1 may start only from this exact lock. G4.1 acceptance must prove `deviceready`, session/login/logout/redirect continuity, Back contract, location allow/deny, download/open/share, external link routing, network failure behavior, and no authorization/privacy widening before broader APK work continues.


## G4.1C — Authenticated Download Regression Gate

Source/static:

```text
node --check mobile/cordova/www/js/shell.js
plugin.xml well-formed
plugin/package JSON valid
cordova prepare android
cordova requirements android
cordova build android
git diff --check origin/main...HEAD
git status
```

Real-device minimum after rebuild:

```text
login via APK
GET export Catatan Pelanggaran
GET export Konseling BK
GET export Prestasi
GET export UKS/PTSP/Dokumen Siswa
Laporan Presensi/Jurnal
Backup download
Kartu Pelajar single-file download
file appears in Downloads
correct filename + extension + MIME
download notification completes
RBAC denial remains denial
session expiry does not produce a false-success file
external/non-SisFour download URL is not bridged
```

Separate POST-download regression:

```text
Statistik PDF POST/client payload
Kartu Pelajar export JPG ZIP POST
other POST attachment surfaces discovered by regression
```

Gate status:

```text
G4.1B runtime UAT                PASS / user evidence
G4.1C source                     IMPLEMENTED
G4.1C static/build               PENDING / user terminal evidence
G4.1C GET download runtime UAT   PENDING
G4.1C GET download regression    IMPLEMENTED / REBUILD UAT PENDING
G4.1D Dashboard helper           IMPLEMENTED / REBUILD UAT PENDING
G4.1D native geolocation         IMPLEMENTED / REBUILD UAT PENDING
G4.1E upload/import chooser      PASS / USER DEVICE UAT
G4.1F POST output bridge         PENDING GENERIC SOLUTION
G4.1F Android Back adapter       IMPLEMENTED / REBUILD UAT PENDING
G4.1G branding candidate         REMEDIATED / REBUILD UAT PENDING
G4.2 version/signing procedure  PREPARED / KEY + SIGNED BUILD PENDING
```


## G4.1D/G4.1E/G4.1G — Device Batch Gate

Navigation:

```text
Dashboard floating helper visible on internal non-dashboard page
helper opens authenticated /dashboard
helper absent on login/root/dashboard
helper does not cover sticky save/action controls
Android Back regression remains valid
```

Native location:

```text
no permission prompt at startup
no permission prompt at dashboard load
Presensi/Jurnal user action triggers location when required
allow -> location accepted
deny -> clear failure / no false success
non-geofence actor/action -> no unnecessary location request
```

Upload/import:

```text
Android document picker opens
cancel returns safely
valid XLSX/file returns to form
invalid MIME/size remains server-rejected
import/upload authorization unchanged
```

Branding:

```text
launcher icon is SisFour/MTsN 4 Jombang, not Cordova
adaptive mask visually acceptable on real launcher
native splash shows official identity
no visible Cordova placeholder between splash and remote Web
local loading shell remains responsive
reduced-motion preference disables cosmetic animation
no startup delay that feels materially worse
```

Current branding source provenance is recorded in `mobile/cordova/resources/branding/README.md`. If adaptive mask crops the official mark/text excessively, create a safe-padded derived foreground while retaining the official master untouched.


## G4.1F — POST Output + Android Back Gate

POST output real-device:

```text
Statistik PDF -> native POST -> PDF in Downloads
Statistik PDF keeps selected filters
Statistik PDF visual payload does not crash/blank app
Kartu selected front PDF -> Downloads
Kartu selected back PDF -> Downloads
Kartu class front/back PDF -> Downloads
Kartu class JPG ZIP -> Downloads
expired session -> no HTML file saved
forbidden actor -> no false-success file
CSRF failure -> no false-success file
generated filename/extension correct
```

Android Back real-device:

```text
modal open -> Back closes modal, stays page
dropdown open -> Back closes dropdown
mobile sidebar open -> Back closes sidebar
dirty Presensi/Jurnal/form -> confirmation before leave
detail/history -> Back returns one logical page
dashboard first Back -> exit hint
dashboard second Back <=1.8s -> exits app
dashboard second Back after timeout -> stays app and rearms
no accidental close while file/location operation is active
```

Static/source evidence:

```text
shell.js parse PASS
native plugin JS parse PASS
JSON manifests PASS
native POST endpoint allowlist PASS
redirect-to-login guard PASS
attachment response guard PASS
Gradle build PENDING
device UAT PENDING
```


## G4.2 — Release Signing Gate

Preparation source:

```text
widget/application id = id.sch.mtsn4jombang.sisfour
versionName           = 1.0.0
versionCode           = 10000
real build-release.json ignored
keystore extensions ignored
release APK/AAB scripts available
release procedure documented
```

Promotion is blocked until G4.1 exact-head device UAT passes.

Release gate:

```text
release keystore created outside repo
independent encrypted backup exists
alias/password custody recorded securely
certificate SHA-256 fingerprint recorded
signed APK build PASS
apksigner verify PASS
clean install PASS
next-version signed update PASS
same package id PASS
same certificate PASS
versionCode monotonic PASS
multi-device smoke PASS
final artifact checksum recorded
keystore/password absent from Git diff PASS
```


## G4 Device UAT Regression — After 6 October Remediation

Next APK must prove:

```text
Upload/import still PASS

GET export:
- Catatan Pelanggaran XLSX has .xlsx filename
- Konseling BK XLSX has .xlsx filename
- no export.bin fallback
- file opens successfully

Branding:
- clean install launcher shows SisFour / MTsN 4 Jombang icon
- no Apache Cordova icon
- splash logo remains fully inside safe zone
- no double/overlapping logo during native -> shell transition
- startup remains fast

Dashboard:
- green home control visible immediately after authenticated login
- control visible in module pages
- tap control opens /dashboard
- control does not block zoom/sticky actions

Geolocation:
- Admin/Operator no unnecessary location prompt
- Guru/required geofence flow prompts Android permission on first use
- allow -> coordinates accepted
- deny -> no false-success save

Autofill:
- username/password fields recognized by configured Android Autofill service
- tapping username/password surfaces saved credentials when manager has them
- no SisFour-owned plaintext credential storage
```


## G4 Thin-Wrapper Recovery Regression

Sebelum build APK berikutnya:

```text
shell.js parse PASS
plugin JS parse PASS
package/package-lock JSON PASS
no endpoint bisnis Statistik/Kartu in Cordova shell/native PASS
no remote login form mutation from Cordova PASS
Dashboard only one injected implementation PASS
Dashboard excluded from login/root/dashboard PASS
Jurnal load result includes server-derived geofence_required PASS
Jurnal JS requests location only when geofence_required=true PASS
shell CSS syntax clean PASS
POST output remains explicitly PENDING, not false-PASS
```

Device geolocation matrix:

```text
geofencing OFF + Presensi Siswa Guru       -> no location prompt
geofencing OFF + Jurnal Guru Hadir         -> no location prompt
geofencing ON  + Presensi Guru Terjadwal   -> location required
geofencing ON  + Jurnal Guru Hadir         -> location required
Jurnal Izin/Sakit                          -> no location prompt
scope SEMUA                                -> no location prompt
deny permission                            -> no false success
outside radius                             -> server rejection
startup/login/dashboard                    -> no location prompt
```
