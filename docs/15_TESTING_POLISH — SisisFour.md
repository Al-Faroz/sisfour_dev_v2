# Testing, Regression & Release Gate — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 17 September 2026  
**Phase aktif:** G3.3.1 rework — **periodic Tahun Ajaran + Konseling follow-up 1:N / local gate pending**

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
opsi lama dapat diganti ke opsi aktif baru                        PASS
opsi lama tidak muncul pada record lain                           PASS
```

## 5. Rework 17 September 2026

Keputusan baru:

```text
A. Filter banyak desktop boleh/wajib dipecah 2 baris bila padat.
B. Semua tabel periodik/historis memakai filter Tahun Ajaran default aktif.
C. Catatan Pelanggaran dan Prestasi mendapat snapshot id_tahun.
D. Konseling mempunyai Tindak Lanjut 1:N.
E. Tidak ada delete Konseling atau Tindak Lanjut Konseling.
F. Semua aturan global UI/UX tetap berlaku.
```

Source target utama:

```text
app/Services/PeriodContextService.php
app/Models/BKKasusModel.php
app/Models/BKPrestasiModel.php
app/Models/KonselingBkModel.php
app/Models/KonselingBkFollowUpModel.php
app/Services/BkService.php
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

## 7. UAT Tahun Ajaran — Catatan Pelanggaran

Minimum:

```text
1. buka Catatan Pelanggaran -> Tahun Ajaran aktif terpilih default
2. Reset -> kembali ke Tahun Ajaran aktif
3. pilih Tahun historis -> tabel hanya data period tersebut
4. kembali ke aktif -> data aktif kembali
5. create Catatan baru -> tersimpan dengan id_tahun aktif walau filter sebelumnya historis
6. export -> hanya period yang dipilih
7. mobile tidak overflow horizontal
```

## 8. UAT Tahun Ajaran — Prestasi

Minimum:

```text
1. default Tahun Ajaran aktif
2. Reset kembali aktif
3. histori period dapat dipilih
4. create baru selalu snapshot periode aktif server
5. export mengikuti period terpilih
6. mobile/table tetap sesuai global no-horizontal-overflow rule
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

Pimpinan/Guru/Wali/Siswa tidak boleh memperoleh menu/detail/widget/direct access Konseling.

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

## 15. Hosting Gate

Baseline hosting PASS **tidak membuktikan rework 17 September**.

Setelah localhost PASS:

```text
1. audit dump/schema hosting aktual lagi
2. buat delta hosting khusus state aktual
3. review SQL hosting
4. execution hanya dengan approval eksplisit user
5. focused hosting UAT period filter + follow-up 1:N
6. final docs sync
7. PR Ready hanya dengan approval user
8. merge hanya dengan approval merge terpisah
```

## 16. Current Status

```text
Broad G3.3.1 baseline                     PASS
Historical Rencana parent local UAT        PASS
17 Sep source implementation               IMPLEMENTED
17 Sep docs canonical sync                 IN PROGRESS / branch
17 Sep localhost delta SQL                 PREPARED
17 Sep localhost SQL execution             PENDING
17 Sep local runtime UAT                   PENDING
17 Sep final static gate                   PENDING
17 Sep hosting dump audit/delta/re-smoke   NOT STARTED
PR #9                                      DRAFT / BELUM MERGE
G3.4                                       BELUM DIMULAI
```

## 17. G3.4 dan Seterusnya

G3.4 Dashboard/Workflow BK baru dimulai setelah rework G3.3.1 PASS dan PR #9 merged. G3.5 Pimpinan, G3.6 Siswa, G3.7 global mobile sweep, G3.8 WebView readiness, lalu G4 Cordova.

Setiap merge/release memerlukan approval eksplisit pengguna.