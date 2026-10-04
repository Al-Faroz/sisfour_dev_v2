# G3.10 — Student Services Expansion — SisisFour

**Status:** LOCKED / IMPLEMENTATION ACTIVE  
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

Export Catatan Pelanggaran:

```text
Sheet 1 Pelanggaran
  one row per student
  + Mode
  + ID Kelompok
  + Jumlah Anggota

Sheet 2 Kelompok Pelanggaran
  one row per group event

Sheet 3 Tindak Lanjut
  one row per student follow-up
  + ID Kelompok
```

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

Export Konseling:

```text
1. Konseling Individu
2. Tindak Lanjut Individu
3. Konseling Kelompok
4. Anggota Kelompok
5. Tindak Lanjut Kelompok
```

Konseling Kelompok tidak di-flatten menjadi duplikasi parent per siswa.

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

## 7. Period Context Dokumen

Setiap dokumen wajib terikat ke exact `tahun_ajaran.id`.

Pada SisFour, satu row `tahun_ajaran` adalah:

```text
nama_tahun + semester
```

Maka:

```text
2026/2027 — Ganjil ≠ 2026/2027 — Genap
```

Rules:

1. manual input default ke periode aktif tetapi Admin/Operator boleh memilih historical period;
2. bulk import = satu batch untuk satu exact `id_tahun`;
3. student historical membership diverifikasi memakai `anggota_kelas.id_tahun`;
4. jangan memakai kelas/tingkat siswa saat ini untuk dokumen historical;
5. siswa Lulus/Pindah/Keluar tetap boleh menerima historical document bila membership periode tersebut valid;
6. listing Siswa default periode aktif, history selectable.

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

Manual/bulk resolve:

```text
NISN
→ siswa.id
→ anggota_kelas WHERE id_tahun = selected
→ kelas historical
```

### TINGKAT

```text
id_siswa NULL
tingkat  7 / 8 / 9
```

Eligibility siswa:

```text
users.id_siswa
→ anggota_kelas exact id_tahun
→ kelas.tingkat = dokumen.tingkat
```

Satu Dokumen Tingkat adalah satu link bersama; tidak dibuat N row per siswa.

## 9. Manual Dokumen

Field minimum:

```text
Judul
Target INDIVIDU / TINGKAT
Tahun Ajaran + Semester
Format PDF / IMAGE
Link Google Drive
Siswa (bila INDIVIDU)
Tingkat (bila TINGKAT)
Status PUBLISHED / ARCHIVED
```

Link wajib HTTPS dan host allowlist:

```text
drive.google.com
docs.google.com
```

SisFour hanya menentukan siapa yang boleh melihat/membuka link. Sharing policy file di Google Drive tetap tanggung jawab operator.

## 10. Bulk Import Dokumen Individu

Bulk hanya untuk target INDIVIDU pada scope awal.

Import context:

```text
Judul Dokumen
Exact Tahun Ajaran + Semester
Format PDF / IMAGE
Status = PUBLISHED
Optional template filter = Semua / Tingkat / Kelas
```

Template canonical:

```text
NISN
NAMA SISWA     (verification/display)
KELAS          (verification/display)
LINK GOOGLE DRIVE
```

Authoritative input:

```text
NISN + LINK GOOGLE DRIVE
```

Nama/Kelas tidak menjadi resolver.

Template dapat dibuat oleh SisFour dari membership periode terpilih agar operator hanya perlu mengisi link.

Import pipeline:

```text
XLSX
→ validate header
→ normalize NISN/link
→ duplicate-in-file check
→ resolve siswa
→ resolve exact historical membership
→ validate GDrive URL
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

Business duplicate awal:

```text
id_siswa + id_tahun + normalized judul
```

Duplicate diblok dan tampil pada preview.

Update/replace bulk **bukan scope awal**. Manual edit link existing tetap boleh sesuai permission dan tercatat audit.

## 12. Import Batch / Rollback

Tabel audit:

```text
dokumen_siswa_import_batch
```

Menyimpan:

```text
judul
id_tahun
format_file
source_filename
total_row
total_valid
total_error
status
created_by
created_at
committed_at
```

`dokumen_siswa.id_import_batch` mengikat hasil import.

Rollback batch hanya membatalkan/arsip metadata hasil batch SisFour dan **tidak pernah menghapus file Google Drive**.

## 13. Access Boundary Dokumen Siswa

```text
Admin     = view_all + manage + export
Operator  = view_all + manage + export
Siswa     = view_self
role lain = DEFAULT DENY
```

Permission baru:

```text
dokumen_siswa.view_self
dokumen_siswa.view_all
dokumen_siswa.manage
dokumen_siswa.export
```

Siswa tidak boleh memilih `id_siswa` target dari request. Identity selalu berasal dari `users.id_siswa`.

## 14. Siswa — Dokumen Saya

Dataset:

```text
dokumen INDIVIDU dengan id_siswa login
UNION
dokumen TINGKAT yang cocok dengan membership login pada exact id_tahun
```

Default = periode aktif. History = selectable.

Open flow:

```text
GET dokumen-saya/buka/{id}
→ auth
→ permission view_self
→ resolve users.id_siswa
→ validate INDIVIDU/TINGKAT eligibility
→ write access log
→ redirect ke link Google Drive
```

Raw link tidak menjadi authorization boundary.

## 15. Audit

```text
dokumen_siswa_access_log
id_dokumen
id_user
id_siswa
aksi = OPEN
waktu
```

Manage/create/import/edit/export juga dicatat ke `log_activity`.

## 16. Export Dokumen

Operational XLSX hanya metadata:

```text
ID
Judul
Target
Tahun Ajaran
Semester
Tingkat
NISN
Nama Siswa
Kelas
Format
Status
Created By
Created At
```

Credential tidak ada. Link Google Drive dapat disertakan hanya pada export Admin/Operator karena memang merupakan metadata operasional domain; tidak pernah diekspor ke role lain.

Access-log export dapat menjadi sheet kedua bila `dokumen_siswa.export`.

## 17. UI / Menu

Admin/Operator:

```text
Dokumen Siswa
├─ Data Dokumen
└─ Bulk Import
```

Siswa:

```text
Dokumen Saya
```

Surface wajib mobile-safe dan mengikuti G3.7/G3.8/G3.9 UI contract.

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
manual individual document
manual grade-level document
historical period targeting
student self-only dataset
bulk template generation
bulk strict preview
bulk commit
duplicate rejection
invalid NISN rejection
invalid period membership rejection
invalid non-GDrive URL rejection
PDF/IMAGE metadata only
open/access audit
metadata export
mobile/WebView regression
```

## 20. Out of Scope

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
