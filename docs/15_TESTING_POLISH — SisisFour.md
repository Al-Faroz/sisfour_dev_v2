# Testing, Regression & Release Gate — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 18 September 2026
**Phase aktif:** G3.6A — **UKS / Kesehatan / source implemented, localhost SQL execution + static/runtime gate pending**

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
- Pemeriksaan CKG Bulan Ini
- Kunjungan UKS Hari Ini
- Kunjungan UKS Bulan Ini
- Rujuk ke Klinik Bulan Ini
- latest max 5
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
G3.6 merge commit                   59b22b651ad0d508ea3a29261ef590d4c9506da4

G3.6A contract                      LOCKED
G3.6A source                        IMPLEMENTED / feature branch
G3.6A docs sync                     IN PROGRESS
G3.6A localhost SQL                 PREPARED
G3.6A localhost SQL execution       PENDING
G3.6A final static gate             PENDING
G3.6A local runtime UAT             PENDING
G3.6A cross-role/historical UAT     PENDING
G3.6A local dump audit              PENDING
G3.6A hosting                       NOT STARTED
```

## 18. Roadmap

```text
G3.6A  UKS / Kesehatan       ACTIVE
G3.6B  PTSP                  NEXT
G3.7   Global Mobile Sweep
G3.8   WebView Readiness
G4     Cordova APK
```

G3.6A mengikuti SSOT `17_UKS_KESEHATAN — SisisFour.md`. PTSP tetap terpisah dan tidak boleh ikut diimplementasikan pada SQL/source G3.6A hanya karena role registry global sudah mengenal target role tersebut.

Setiap deployment/Ready/merge memerlukan approval eksplisit pengguna.
