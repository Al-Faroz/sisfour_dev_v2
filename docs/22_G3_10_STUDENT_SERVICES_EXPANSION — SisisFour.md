# G3.10 — Student Services Expansion — SisisFour

**Status:** CLOSURE-READY / RUNTIME UAT PASS / FINAL EXACT-HEAD REGRESSION PASS
**Branch:** `feat/g3-10-student-services-expansion-20261004`
**Base:** exact head G3.9 `96c22ab0bd9e2bc18d15a2a1a5592b0aace16bb1`
**Dependency:** PR #18 G3.9 remains Draft; G3.10 is stacked and must not change PR #18.
**G4/Cordova:** NOT STARTED.

## 1. Scope

G3.10 terdiri dari dua subfase:

```text
G3.10A — BK Group Recording
├─ Catatan Pelanggaran Kelompok
├─ Konseling Kelompok
└─ XLSX export group-aware

G3.10B — Student Document Center
├─ Dokumen Individu
├─ Dokumen Per Tingkat
├─ link Google Drive manual
├─ bulk import Excel Dokumen Individu
└─ metadata/audit export

G3.10C — BK UI & Export Polish
├─ canonical BK page-action hierarchy
├─ canonical Bootstrap/Sneat button hierarchy dari docs/11 + docs/13
├─ export scope: filter aktif / seluruh Tahun Ajaran
├─ export mode: Ringkas / Lengkap-Audit
└─ human-readable Pelanggaran + Konseling workbook
```

Tidak ada Google Drive API, service account, Drive credential, server-side upload, atau file proxy pada phase ini.

## 2. G3.10A — Catatan Pelanggaran Kelompok

Existing `catatan_kasus` tetap canonical **one row per student**.

Pencatatan kelompok memakai parent event:

```text
catatan_kasus_kelompok
        ↓ 1:N
catatan_kasus
        ↓ 1:N
tindak_lanjut_kasus
```

Hard rules:

1. satu kejadian kelompok menyimpan Tahun Ajaran aktif, tanggal, pelanggaran, keterangan, actor;
2. anggota dipilih multi-siswa;
3. commit bersifat transaction all-or-nothing;
4. setiap anggota menghasilkan satu `catatan_kasus` individual dengan `id_kelompok` sama;
5. history/dashboard/self-service existing tetap membaca per-siswa record;
6. record lama/individu mempunyai `id_kelompok = NULL`;
7. group create tidak mengubah permission existing `bk_kasus.manage`;
8. target siswa wajib aktif dan valid pada Tahun Ajaran aktif;
9. follow-up tetap tersimpan per `catatan_kasus`; bulk/fan-out follow-up kelompok boleh membuat child per anggota dalam satu transaction;
10. no point/scoring tetap berlaku.

Export Catatan Pelanggaran mengikuti kontrak G3.10C:

```text
RINGKAS
1. Ringkasan
2. Rekap Pelanggaran
3. Tindak Lanjut

LENGKAP / AUDIT
1. Ringkasan
2. Data Pelanggaran
3. Riwayat Tindak Lanjut
4. Kejadian Kelompok
```

Mode Ringkas tidak membawa NISN, ID Catatan/ID Kelompok, timestamp audit, atau sheet
Kejadian Kelompok terpisah. Mode Lengkap/Audit mempertahankan metadata tersebut untuk
tracing.

## 3. G3.10A — Konseling Kelompok

Konseling Kelompok **tidak** dibuat dengan menduplikasi `konseling_bk`.

Persistence terpisah:

```text
konseling_kelompok
        ↓ 1:N
konseling_kelompok_anggota

konseling_kelompok
        ↓ 1:N
tindak_lanjut_konseling_kelompok
```

Parent menyimpan:

```text
id_tahun
tanggal
pertemuan_ke
bentuk_layanan
cara_hadir
bidang
topik
uraian_masalah
hasil_kesepakatan
rencana_berikutnya
tanggal_berikutnya
status
created_by / updated_by
```

Anggota menyimpan snapshot:

```text
id_konseling_kelompok
id_siswa
id_kelas
```

Rules:

1. create hanya Tahun Ajaran aktif;
2. semua anggota wajib mempunyai membership valid pada exact `id_tahun`;
3. anggota boleh lintas kelas dalam periode yang sama;
4. privacy sama dengan Konseling Individu;
5. Admin/Operator/BK sesuai existing `bk_konseling.*`;
6. Pimpinan/Guru/Wali/Siswa/Kesehatan/PTSP tidak mendapat detail/member record;
7. no-delete contract parent/follow-up tetap berlaku;
8. parent status mengikuti tindak lanjut kelompok terbaru bila ada.

Export Konseling mengikuti kontrak G3.10C:

```text
RINGKAS
1. Ringkasan
2. Rekap Konseling
3. Tindak Lanjut

LENGKAP / AUDIT
1. Ringkasan
2. Data Konseling
3. Detail Konseling
4. Riwayat Tindak Lanjut
5. Anggota Kelompok
```

Mode Ringkas menggabungkan Individu/Kelompok sebagai laporan operasional tanpa ID/NISN
audit dan tanpa Hasil/Kesepakatan sensitif. Mode Lengkap/Audit mempertahankan detail
tracing, anggota, actor, dan narasi sensitif. Konseling Kelompok tidak di-flatten menjadi
duplikasi parent per siswa pada mode Lengkap/Audit.

## 4. XLSX Hardening

Semua cell text user-controlled pada export XLSX harus ditulis sebagai **explicit string** agar value seperti `=...`, `+...`, `-...`, atau `@...` tidak dieksekusi sebagai formula spreadsheet.

Per-sheet row guard diterapkan sebelum workbook dibangun. Default maximum operational row = 50.000 per sheet.

Temporary export file tetap dibersihkan setelah response.

## 5. G3.10B — Student Document Center

Google Drive adalah **external/manual storage**.

Canonical flow:

```text
MANUAL
Upload file ke Google Drive
→ Copy Link
→ input metadata + link ke SisFour

BULK INDIVIDU
Upload file-file ke Google Drive
→ copy link masing-masing
→ Download/isi Template Excel
→ NISN + LINK GOOGLE DRIVE
→ upload Excel ke SisFour
→ validate
→ preview
→ commit
```

SisFour tidak melakukan API call ke Google Drive.

## 6. File Format

Format file yang didukung hanya:

```text
PDF
IMAGE
```

`IMAGE` secara operasional berarti JPG/JPEG/PNG.

Tidak didukung:

```text
DOC/DOCX
XLS/XLSX
PPT/PPTX
ZIP
Google Docs/Sheets/Slides native
format lain
```

Karena tidak ada Google Drive API, `format_file` adalah metadata yang dinyatakan operator. SisFour memvalidasi URL Google Drive, bukan MIME remote file.

## 7. Period Contract Dokumen

Dokumen Siswa **bukan transaksi akademik semester** dan tidak menyimpan `id_tahun`.

Canonical rule:

```text
Dokumen INDIVIDU
→ melekat ke id_siswa
→ tetap tersedia lintas semester/tahun sampai ARCHIVED/HARD DELETE

Dokumen TINGKAT
→ melekat ke tingkat 7/8/9
→ eligibility siswa dihitung dari membership pada PERIODE AKTIF
→ periode aktif hanya resolver tingkat current, bukan atribut dokumen
```

Jika tidak ada Tahun Ajaran aktif:

```text
INDIVIDU → tetap tersedia
TINGKAT  → default deny / tidak ditampilkan
```

Tidak ada historical period selector pada `Dokumen Saya`.

## 8. Target Dokumen

```text
INDIVIDU
TINGKAT
```

### INDIVIDU

```text
id_siswa NOT NULL
tingkat  NULL
```

Resolver manual/bulk:

```text
NISN
→ siswa.id
```

Membership akademik tidak menjadi syarat ownership Dokumen Individu. Siswa historis
(Lulus/Pindah/Keluar) tetap dapat mempunyai Dokumen Individu selama master siswa
masih valid/tidak terhapus.

### TINGKAT

```text
id_siswa NULL
tingkat  7 / 8 / 9
```

Eligibility pada sisi Siswa:

```text
users.id_siswa
→ Tahun Ajaran aktif
→ anggota_kelas exact periode aktif
→ kelas.tingkat = dokumen.tingkat
```

Satu Dokumen Tingkat adalah satu link bersama; tidak dibuat N row per siswa.

## 9. Manual Dokumen

Field minimum:

```text
Judul
Target INDIVIDU / TINGKAT
Format PDF / IMAGE
Link Google Drive
Siswa (bila INDIVIDU)
Tingkat (bila TINGKAT)
Status PUBLISHED / ARCHIVED
```

Link Dokumen wajib HTTPS dan host allowlist:

```text
drive.google.com
```

URL `docs.google.com` hanya digunakan untuk Spreadsheet Helper pada template dan tidak
diterima sebagai link Dokumen. SisFour hanya menentukan siapa yang boleh melihat/membuka link. Sharing policy file
di Google Drive tetap tanggung jawab operator.

## 10. Bulk Import Dokumen Individu

Bulk hanya untuk target INDIVIDU pada scope awal.

Import context:

```text
Judul Dokumen
Format PDF / IMAGE
Status = PUBLISHED
```

Tidak ada Tahun Ajaran/Semester pada context import.

Template canonical mempunyai dua sheet:

```text
Sheet 1: DATA_DOKUMEN
NISN
NAMA SISWA      (verification/display)
KELAS           (verification/display current)
LINK GOOGLE DRIVE

Sheet 2: PETUNJUK
petunjuk operasional
hyperlink Spreadsheet Helper
```

Authoritative import input:

```text
NISN + LINK GOOGLE DRIVE
```

Nama/Kelas tidak menjadi resolver.

Template dapat dibuat berdasarkan:

```text
Semua Siswa
Tingkat 7 / 8 / 9
Kelas tertentu
```

Filter Tingkat/Kelas memakai Tahun Ajaran aktif **hanya untuk menghasilkan daftar
siswa current**. Period tersebut tidak pernah disimpan ke dokumen hasil import.

Canonical Spreadsheet Helper:

```text
https://docs.google.com/spreadsheets/d/16CKvPVbkxZk6zW9dTeN35ivk3Qi_bIzIXKQWCFsnywQ/edit?usp=sharing
```

Helper adalah alat bantu operator untuk memasukkan Folder ID Google Drive dan
menghasilkan:

```text
Nama File
ID File
URL Penampil
```

SisFour tidak memanggil helper/API tersebut dan tetap dapat beroperasi bila helper
tidak digunakan.

Import pipeline:

```text
XLSX
→ validate header
→ normalize NISN/link
→ duplicate-in-file check
→ resolve siswa by NISN
→ validate Google Drive URL
→ duplicate DB check
→ PREVIEW
→ COMMIT all-or-nothing
```

Tidak ada partial commit bila ada blocking error.

## 11. Duplicate Rule

Default import mode:

```text
CREATE_ONLY
```

Business duplicate Published:

```text
INDIVIDU → id_siswa + normalized judul
TINGKAT  → tingkat + normalized judul
```

Duplicate diblok dan tampil pada preview.

Update/replace bulk **bukan scope awal**. Manual edit link existing tetap boleh
sesuai permission dan tercatat audit.

## 12. Import Batch / Rollback

Tabel audit:

```text
dokumen_siswa_import_batch
```

Menyimpan:

```text
judul
format_file
source_filename
total_row
total_valid
total_error
status
created_by
created_at
committed_at
rolled_back_at
```

`dokumen_siswa.id_import_batch` mengikat hasil import.

Rollback batch mengarsip metadata hasil batch SisFour dan **tidak pernah menghapus
file Google Drive**.

## 13. Bulk Hard Delete

Admin/Operator dapat memilih 1..N dokumen dari Data Dokumen dan melakukan hard delete.

Canonical flow:

```text
selected document IDs
→ permission dokumen_siswa.hard_delete
→ server revalidates every ID
→ snapshot metadata ke dokumen_siswa_delete_log
→ hapus dokumen_siswa_access_log terkait
→ hard DELETE dokumen_siswa
→ commit transaction
```

Google Drive tidak disentuh.

Hard delete **tidak sama dengan Archive** dan tidak dapat dibatalkan dari SisFour.

Scope awal hanya:

```text
checkbox row
Select All pada halaman/table result saat ini
Bulk Hard Delete selected IDs
```

Tidak ada `Delete All Filtered Results` pada scope awal.

Deletion audit minimum:

```text
id_dokumen_asal
target_type
id_siswa
tingkat
judul
format_file
link_gdrive
id_import_batch
deleted_by
deleted_at
delete_batch_key
```

## 14. Access Boundary Dokumen Siswa

```text
Admin     = view_all + manage + export + hard_delete
Operator  = view_all + manage + export + hard_delete
Siswa     = view_self
role lain = DEFAULT DENY
```

Permission:

```text
dokumen_siswa.view_self
dokumen_siswa.view_all
dokumen_siswa.manage
dokumen_siswa.export
dokumen_siswa.hard_delete
```

Siswa tidak boleh memilih `id_siswa` target dari request. Identity selalu berasal
dari `users.id_siswa`.

## 15. Siswa — Dokumen Saya

Dataset:

```text
dokumen INDIVIDU Published dengan id_siswa login
UNION
dokumen TINGKAT Published yang cocok dengan tingkat current siswa
pada Tahun Ajaran aktif
```

Tidak ada filter/history Tahun Ajaran pada Dokumen Saya.

Open flow:

```text
GET dokumen-saya/buka/{id}
→ auth
→ permission view_self
→ resolve users.id_siswa
→ validate INDIVIDU/TINGKAT eligibility
→ revalidate Google Drive URL
→ write access log
→ redirect ke link Google Drive
```

Raw link tidak menjadi authorization boundary.

### 15.1 Dashboard Siswa access

Jika actor Siswa mempunyai permission `dokumen_siswa.view_self` dan identity siswa valid,
Dashboard Siswa menampilkan action `Dokumen Saya` pada blok `Akses Saya`.

```text
Surface     = Self-Service Access
Label       = Dokumen Saya
Route       = dokumen-saya
Permission  = dokumen_siswa.view_self
Family      = Cyan
```

Action ini tidak membentuk operational Primary Action Surface baru dan tidak memperluas
scope. Dataset tetap diselesaikan oleh `DokumenSiswaService::selfPage()` dengan
`DIRI_SENDIRI`.

### 15.2 Data Dokumen manager table

Halaman `dokumen-siswa` untuk Admin/Operator memakai presentation contract:

```text
Desktop columns
Dokumen | Penerima | Status | Pencatat | Aksi

Dokumen
→ Judul + Format + sumber Manual/Import

Penerima
→ INDIVIDU: Nama + NISN + Kelas saat ini bila tersedia
→ TINGKAT: Tingkat + label dokumen bersama

Pencatat
→ actor + created_at
```

Service tetap authoritative untuk filter/paging. UI memakai `limit/offset` yang sudah
tersedia dengan canonical pager 25/50/100. Mobile memakai adaptive list dari row yang sama,
bukan horizontal operational table.

Hard-delete tetap hanya selected IDs pada current page/result dan tidak berubah menjadi
delete-all filtered.

## 16. Audit

```text
dokumen_siswa_access_log
id_dokumen
id_user
id_siswa
aksi = OPEN
waktu

dokumen_siswa_delete_log
snapshot metadata hard delete
deleted_by
deleted_at
delete_batch_key
```

Manage/create/import/edit/export/hard-delete juga dicatat ke `log_activity`.

## 17. Export Dokumen

Operational XLSX:

```text
Sheet 1: Dokumen Siswa
ID
Judul
Target
Tingkat
NISN
Nama Siswa
Kelas Saat Ini (display only)
Format
Status
Link Google Drive
Import Batch
Created By
Created At

Sheet 2: Riwayat Akses
ID Dokumen
Judul
NISN
Nama Siswa
Aksi
Waktu
Username
```

Tidak ada Tahun Ajaran/Semester sebagai atribut dokumen.

`Kelas Saat Ini` hanya display yang dihitung dari periode aktif pada saat export dan
bukan ownership/period field dokumen.

## 18. SQL / Deployment

G3.10 memerlukan schema delta.

Urutan:

```text
1. source + LOCALHOST SQL
2. user pull
3. import SQL localhost
4. verification
5. static/unit gate
6. runtime UAT
7. post-UAT localhost dump audit bila diperlukan
8. fresh hosting dump audit
9. hosting SQL disusun dari state aktual
10. hosting execution hanya dengan approval eksplisit
```

Jangan membuat/mengeksekusi hosting SQL dari asumsi baseline.

Status 2026-10-04 setelah localhost + hosting verification dan final static gate:
- G3.10A localhost schema = **PASS**.
- G3.10B localhost schema/RBAC/menu = **PASS**.
- fresh hosting dump `u473908839_sisfour2026` tanggal 2026-10-04 09:05 telah diaudit sebagai baseline sebelum hosting update.
- `menus.id` hosting pada baseline tersebut legacy non-AUTO_INCREMENT; MAX(id) = 125, sehingga provisioning menu G3.10B memakai explicit positive ID.
- hosting SQL G3.10A = **PASS**.
- hosting SQL G3.10B = **PASS**.
- PHP lint = **PASS**.
- JavaScript syntax check = **PASS**.
- PHPUnit `FunctionalConsistencyTest` = **PASS (26/26 tests, 75 assertions)** pada exact closure head; warning code coverage driver tidak dianggap test failure.
- route verification G3.10A/G3.10B = **PASS**.
- `git diff --check` = **PASS**.
- branch/remote sync pada closure checkpoint = **PASS**.
- runtime/UAT G3.10B manual input = **PASS / user evidence 2026-10-05**.
- runtime/UAT G3.10B bulk import = **PASS / user evidence 2026-10-05**.
- runtime/UAT G3.10B Dokumen Saya pada role Siswa = **PASS / user evidence 2026-10-05**.
- Data Dokumen table/pager polish = **PASS / user evidence 2026-10-05**.
- Dashboard Siswa → Dokumen Saya access = **PASS / user evidence 2026-10-05**.
- cross-student protection = **PASS / user evidence 2026-10-05**.
- invalid Google Drive URL rejection = **PASS / source + unit + user evidence**.
- duplicate document protection = **PASS / user evidence 2026-10-05**.
- hard delete + delete snapshot + Drive untouched = **PASS / user evidence 2026-10-05**.
- access audit OPEN = **PASS / user evidence 2026-10-05**.
- metadata + access-log export = **PASS / user evidence 2026-10-05**.
- session stability after `sisfour_v2_session` hardening = **PASS / user evidence 2026-10-05**.
- mobile 360px Data Dokumen / Dashboard Siswa / Dokumen Saya = **PASS / user evidence 2026-10-05**.
- runtime/UAT acceptance keseluruhan = **PASS**.
- final exact-head PHP lint = **PASS**.
- final exact-head JavaScript syntax = **PASS**.
- final exact-head routes verification = **PASS**.
- final exact-head PHPUnit = **PASS (26/26 tests, 75 assertions)**.
- final exact-head `git diff --check` = **PASS**.
- final exact-head working tree = **CLEAN / branch synced with origin**.
- final exact-head regression keseluruhan = **PASS**.
- G3.10 = **CLOSURE-READY**.
- PR #19 tetap **DRAFT**; Ready/Merge/Deploy tidak dilakukan tanpa approval eksplisit.

## 19. Acceptance

G3.10A:

```text
group violation create transaction-safe
child history remains per student
group counseling separate persistence
counseling privacy unchanged
group-aware export correct
no-delete counseling unchanged
```

G3.10B:

```text
manual individual document without period ownership
manual grade-level document without stored period
student individual self-only dataset across periods
grade eligibility from active-period current membership
no active period: individual stays visible; grade defaults deny
bulk template generation with DATA_DOKUMEN + PETUNJUK
Spreadsheet Helper clickable hyperlink
bulk strict preview
bulk commit
duplicate rejection by student+title
invalid NISN rejection
invalid non-GDrive URL rejection
PDF/IMAGE metadata only
bulk hard delete selected IDs
deletion audit snapshot retained
Google Drive never deleted/mutated
open/access audit
metadata + access export
Data Dokumen table hierarchy + pager
Dashboard Siswa → Dokumen Saya self-service action
mobile/WebView regression
```

## 20. G3.10C — BK UI & Export Polish

G3.10C adalah polish checkpoint sebelum closure G3.10. Tidak menambah schema database
dan tidak mengubah privacy/RBAC canonical BK.

### 20.1 Canonical BK page actions

Catatan Pelanggaran dan Konseling BK memakai hierarchy action yang sama:

```text
[Kelompok] [Export XLSX] [Tambah Baru]
```

Page-header actions mengikuti hierarchy global docs/11 + docs/13:

```text
Related / Kelompok → btn-outline-primary
Export             → btn-outline-success
Primary Create     → btn-primary
```

Visual `sisfour-action--*` dari G3.9 tetap scoped untuk Dashboard/action surface dan
tidak dipakai sebagai pengganti button hierarchy form/module operasional. Filter
Reset/Tampilkan, row action, Cancel/Save modal, dan destructive action tetap mengikuti
hierarchy Bootstrap/Sneat canonical.

### 20.2 Export UX

Export Catatan Pelanggaran dan Konseling BK dibuka melalui modal dengan dua dimensi:

```text
Cakupan
├─ Sesuai filter saat ini
└─ Seluruh Tahun Ajaran terpilih

Mode
├─ Ringkas
└─ Lengkap / Audit
```

Mode Ringkas ditujukan untuk penggunaan operasional sehari-hari dan meminimalkan internal
ID pada sheet utama. Mode Lengkap/Audit menambahkan informasi teknis/audit yang diperlukan.

### 20.3 Workbook Catatan Pelanggaran

Mode Ringkas adalah laporan operasional yang sengaja berbeda dari dataset audit:

```text
1. Ringkasan
2. Rekap Pelanggaran
3. Tindak Lanjut
```

`Rekap Pelanggaran` hanya membawa informasi yang perlu dibaca sehari-hari:
Tanggal, Nama Siswa, Kelas, Pelanggaran, Kategori, Jenis Kejadian, Keterangan,
dan Jumlah Tindak Lanjut. NISN, ID internal, timestamp audit, serta sheet kejadian
kelompok terpisah tidak dimasukkan.

Mode Lengkap/Audit:

```text
1. Ringkasan
2. Data Pelanggaran
3. Riwayat Tindak Lanjut
4. Kejadian Kelompok
```

Mode ini membawa NISN, ID Catatan/ID Kelompok, timestamp, actor tindak lanjut, dan
dataset kejadian kelompok untuk kebutuhan tracing.

Ringkasan pada kedua mode menyimpan Tahun Ajaran, cakupan, mode, timestamp export,
filter yang digunakan, serta metrik jumlah catatan/siswa/kejadian kelompok/tindak lanjut.

### 20.4 Workbook Konseling BK

Mode Ringkas adalah laporan operasional dan hanya mempunyai tiga sheet:

```text
1. Ringkasan
2. Rekap Konseling
3. Tindak Lanjut
```

`Rekap Konseling` menyatukan Individu/Kelompok melalui kolom `Jenis Konseling` dan
hanya membawa Tanggal, Siswa/Kelompok, Kelas, Layanan, Bidang, Topik, Status,
Rencana Berikutnya, dan Tanggal Berikutnya. Ringkas tidak membawa NISN, ID Referensi,
sheet Anggota Kelompok terpisah, actor audit, Uraian Masalah, atau Hasil/Kesepakatan.

Mode Lengkap/Audit mempunyai lima sheet:

```text
1. Ringkasan
2. Data Konseling
3. Detail Konseling
4. Riwayat Tindak Lanjut
5. Anggota Kelompok
```

Mode Lengkap/Audit membawa Tahun Ajaran, jumlah anggota, pertemuan ke-, cara hadir,
actor, NISN/ID pada sheet audit terkait, Uraian Masalah, Hasil/Kesepakatan,
Perkembangan, serta membership kelompok untuk kebutuhan tracing.

Workbook tetap mempertahankan:
- explicit-string protection untuk text user-controlled;
- freeze header;
- AutoFilter;
- row guard;
- temporary-file cleanup;
- permission export existing;
- privacy Konseling existing.

### 20.5 Current implementation gate

Status setelah source G3.10C diimplementasikan:

```text
BK page layout/action contract      IMPLEMENTED / GLOBAL SSOT ALIGNED
Export modal scope/mode             IMPLEMENTED / NATIVE SNEAT SCROLLABLE
Ringkas vs Lengkap differentiation   IMPLEMENTED / RUNTIME RECHECK PENDING
Pelanggaran workbook redesign       IMPLEMENTED
Konseling workbook redesign         IMPLEMENTED
DB/schema change                    NONE
Regression PHP/JS/unit              PENDING LOCAL RERUN
Workbook runtime verification       PENDING
Desktop/mobile/WebView UAT          PENDING
Web session isolation/auth sync      IMPLEMENTED / RUNTIME RECHECK PENDING
Ready/Merge/Deploy                  NOT AUTHORIZED
```

PASS static/unit sebelum G3.10C adalah evidence untuk checkpoint source sebelumnya dan
harus dijalankan ulang terhadap exact HEAD G3.10C sebelum phase dapat ditutup.

### 20.6 Sneat modal correction

Runtime UAT menemukan tombol close modal Export tampak keluar dari sudut modal. Audit
vendor menunjukkan ini berasal dari treatment native Sneat untuk modal biasa
(`.modal .btn-close` memakai translate + absolute positioning).

Canonical correction **bukan** CSS per-feature. Modal Export Catatan Pelanggaran dan
Konseling BK memakai:

```text
modal-dialog
+ modal-dialog-centered
+ modal-dialog-scrollable
```

Dengan demikian behavior close dan short-viewport scrolling mengikuti Sneat native.
Custom `sisfour-export-modal` dan override close per-feature dihapus.

Button page-header juga dikembalikan ke docs/11:

```text
Related/Group  = btn-outline-primary
Export         = btn-outline-success
Primary Create = btn-primary
```

G3.9 `sisfour-action--*` tetap khusus Dashboard/action surface.

### 20.7 Web session stability hardening

Selama G3.10C UAT ditemukan laporan session Admin kadang tampak logout sendiri saat
navigasi/back. Source audit menemukan dua hardening point:

1. cookie session default `ci_session` terlalu generik untuk localhost/multi-app;
2. self-update pada Settings User menaikkan `auth_version` tetapi sebelumnya tidak
   menyinkronkan current session.

Patch canonical:

```text
Session cookie   → sisfour_v2_session
Self user update → refresh current actor session setelah commit
Self reset       → refresh current actor session setelah credential reset
Other sessions   → tetap invalid bila auth_version lama
AuthFilter       → log alasan invalidasi tanpa credential/session secret
```

Perubahan nama cookie menyebabkan **one-time re-login** setelah source baru pertama kali
dipakai. Hal tersebut expected dan bukan regression logout acak.

Jika logout masih muncul setelah login ulang dengan cookie baru, lakukan diagnosis dari
log `AuthFilter` dan schema `ci_sessions`; jangan menurunkan validasi auth_version.

## 21. Out of Scope

```text
Google Drive API
automatic Drive upload
automatic MIME verification
Drive credential/service account
server file proxy
bulk ZIP download
Office/native Google document formats
public document access
student upload
Guru/Wali document management
```
