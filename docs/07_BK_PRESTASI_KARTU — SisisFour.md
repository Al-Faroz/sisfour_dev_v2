# BK, Konseling, Prestasi & Kartu Pelajar — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 18 September 2026
**Baseline Aplikasi:** `main` @ `6bdfc276ae07b6e70065ee7fae9e6ef51c3299ce`
**Development aktif:** `feat/g3-6-siswa-dashboard-20260918` — **G3.6 Dashboard Siswa**

> Dokumen ini menyatakan kontrak BK canonical. Authorization final tetap Route/Filter + Service; View/JavaScript/menu bukan security boundary.

## 1. Ruang Lingkup

```text
Master Pelanggaran
Catatan Pelanggaran Siswa
Tindak Lanjut Pelanggaran
Konseling BK
Tindak Lanjut Konseling BK
Pengaturan Form Konseling
Prestasi Siswa
Kartu Pelajar
Public Verify Kartu
Dashboard/Workflow BK
```

## 2. Aturan Global Periodik BK

Catatan Pelanggaran, Konseling, dan Prestasi adalah tabel periodik/riwayat. Surface listing wajib memiliki filter **Tahun Ajaran**.

Kontrak:

```text
initial filter = Tahun Ajaran aktif
Reset          = kembali ke Tahun Ajaran aktif
historis       = boleh dipilih bila ada di master Tahun Ajaran
export         = mengikuti Tahun Ajaran yang sedang dipilih
create baru    = selalu snapshot Tahun Ajaran aktif dari server
```

Dashboard BK dan Dashboard Siswa adalah current-state dan memakai **Tahun Ajaran aktif** secara server-side tanpa selector historis. Listing/history tetap selectable. Pada Dashboard Siswa, Prestasi dan Catatan Pelanggaran wajib dibatasi identity login + Tahun Ajaran aktif.

Filter Tahun Ajaran tidak dipakai pada tabel global non-periodik seperti Master Pelanggaran, User, Permission, Menu, Setting Sistem, atau Log Activity.

Untuk legacy Catatan Pelanggaran/Prestasi yang sebelumnya tidak mempunyai snapshot Tahun Ajaran, data hanya boleh di-backfill jika periodenya dapat ditentukan tanpa menebak. Record ambigu tetap `NULL` dan harus diaudit, bukan ditempel paksa ke periode aktif.

## 3. Master Pelanggaran — Tanpa Poin

Tabel legacy:

```text
ref_pelanggaran
```

Kategori canonical:

```text
Ringan
Sedang
Berat
```

Permission:

```text
bk_pelanggaran_master.manage
```

Mulai G3.3.1, **poin bukan business rule BK**.

Kontrak:

- UI Master Pelanggaran hanya nama + kategori;
- Catatan Pelanggaran tidak menampilkan poin;
- export tidak membawa poin;
- active dashboard payload tidak membawa poin;
- dashboard tidak membuat ranking poin;
- Top Poin retired;
- `ref_pelanggaran.poin` tetap legacy untuk rollback compatibility;
- create/update aplikasi menulis legacy `0` dan tidak memakainya untuk keputusan bisnis.

## 4. Catatan Pelanggaran Siswa

Tabel fisik legacy:

```text
catatan_kasus
```

Field periodik:

```text
id_tahun -> tahun_ajaran.id
```

Nama UI canonical:

```text
Catatan Pelanggaran Siswa
```

Permission:

```text
bk_kasus.view
bk_kasus.manage
```

Akses bisnis:

| Actor | View | Manage |
|---|---|---|
| Admin | semua | semua |
| Operator | semua | semua |
| Pimpinan | agregat/readonly sesuai permission | tidak |
| BK | semua | semua |
| Wali | kelas Wali sesuai scope | tidak kecuali permission lain diberikan |
| Guru biasa | tidak | tidak |
| Siswa | diri sendiri bila surface mengizinkan | tidak |

Catatan Pelanggaran berisi kejadian, kategori, tanggal, keterangan, dan histori tindak lanjut. Tidak ada skor/poin.

Modal detail:

```text
Detail Pelanggaran
→ Riwayat Tindak Lanjut
→ Form Tambah/Edit Tindak Lanjut
```

## 5. Tindak Lanjut Pelanggaran — 1:N

Tabel:

```text
tindak_lanjut_kasus
```

Relasi:

```text
catatan_kasus 1:N tindak_lanjut_kasus
```

Satu Catatan Pelanggaran boleh mempunyai lebih dari satu tindak lanjut. Tindak lanjut dibuat/diubah melalui `bk_kasus.manage` dan target divalidasi ulang Service.

Export Catatan Pelanggaran memakai dua sheet:

```text
Pelanggaran
Tindak Lanjut
```

Export mengikuti filter Tahun Ajaran terpilih dan tidak membawa poin.

## 6. Konseling BK — Data Rahasia

Parent table:

```text
konseling_bk
```

Tabel histori follow-up:

```text
tindak_lanjut_konseling_bk
```

Effective role operasional:

```text
admin
operator
bk
```

Permission:

```text
bk_konseling.view
bk_konseling.manage
bk_konseling.export
```

Pimpinan, Guru/Wali, dan Siswa tidak memperoleh permission/menu/detail/widget Konseling.

Audit actor utama:

```text
konseling_bk.created_by -> users.id
tindak_lanjut_konseling_bk.created_by -> users.id
```

Akun role BK aktual memakai `users.id_pegawai -> pegawai.id`; `id_guru_bk` nullable/legacy metadata dan bukan identity audit utama.

## 7. Workflow Konseling

### Tahap 1 — Buat Catatan Awal

Wajib:

```text
Kelas
Siswa
Tanggal
Pertemuan ke-
Bentuk layanan
Cara siswa hadir
Bidang
Topik
```

Create selalu memakai Tahun Ajaran aktif dari server. Kelas dan siswa harus merupakan context aktif pada Tahun Ajaran tersebut.

Setelah simpan:

```text
status = Proses
created_by = user login
id_guru_bk = identity Guru actor bila tersedia, selain itu NULL
```

### Tahap 2 — Hasil Pertemuan Awal

Parent Konseling menyimpan hasil pertemuan awal:

```text
Uraian Masalah
Hasil Pembahasan dan Kesepakatan
Rencana Berikutnya
Tanggal Pertemuan Berikutnya
Status Proses | Selesai
```

Ketentuan:

- metadata Tahap 1 tidak diubah;
- tanggal berikutnya tidak boleh sebelum tanggal Konseling;
- `Selesai` mewajibkan Uraian + Hasil;
- `Proses` boleh belum lengkap.

## 8. Tindak Lanjut Konseling — 1:N

Satu parent Konseling dapat memiliki banyak entry tindak lanjut.

Relasi:

```text
konseling_bk 1:N tindak_lanjut_konseling_bk
```

Setiap entry menyimpan:

```text
tanggal
perkembangan
hasil_kesepakatan
rencana_berikutnya
tanggal_berikutnya
status Proses | Selesai
created_by / created_at
updated_by / updated_at
```

Rules:

- tanggal tindak lanjut tidak boleh sebelum tanggal parent Konseling;
- `perkembangan` wajib;
- tanggal berikutnya tidak boleh sebelum tanggal tindak lanjut;
- status `Selesai` mewajibkan Hasil/Kesepakatan;
- parent `konseling_bk.status` merefleksikan status tindak lanjut terbaru bila histori follow-up sudah ada;
- histori ditampilkan sebelum form Tambah/Edit Tindak Lanjut;
- **tidak ada delete Konseling**;
- **tidak ada delete Tindak Lanjut Konseling**;
- mutation yang tersedia hanya create/update.

Urutan detail canonical:

```text
Identitas Konseling
→ Hasil Pertemuan Awal
→ Riwayat Tindak Lanjut Konseling
→ Form Tambah/Edit Tindak Lanjut
```

## 9. Preservasi Rencana Berikutnya Historis

Pengaturan Form adalah daftar pilihan aktif, tetapi perubahan daftar tidak boleh merusak data yang sudah tersimpan, baik pada parent Konseling maupun entry tindak lanjut.

Kontrak:

```text
record menyimpan Rencana X
→ X dihapus dari Settings
→ record lama dibuka
→ X tetap tampil sebagai "X (tersimpan)"
→ save tanpa mengganti X tetap valid
→ user boleh mengganti ke opsi aktif Y
→ record lain yang tidak pernah menyimpan X tidak boleh memakai X
```

Focused local UAT untuk parent historical-Rencana pada 17 September 2026: **PASS**. Behavior yang sama tetap menjadi invariant follow-up 1:N.

## 10. Pengaturan Isian Form Konseling

Storage:

```text
setting_sistem
setting_key = bk_konseling_form_options
```

Configurable:

```text
Bentuk Layanan
Cara Siswa Hadir
Topik per Bidang
Rencana Berikutnya
```

Fixed:

```text
Bidang = Pribadi | Sosial | Belajar | Karier
Status = Proses | Selesai
```

Permission:

```text
bk_konseling.settings
```

Akses Settings:

```text
Admin = ya
BK = ya
Operator = tidak
Pimpinan = tidak
Guru/Wali = tidak
Siswa = tidak
```

Constraint per group:

```text
minimal 1 pilihan
maksimal 40 pilihan
Bentuk Layanan max 50 karakter
Cara Hadir max 80
Topik max 150
Rencana max 100
deduplikasi case-insensitive
```

## 11. Prestasi Siswa

Tabel:

```text
catatan_prestasi
```

Field periodik:

```text
id_tahun -> tahun_ajaran.id
```

Permission:

```text
prestasi.view
prestasi.manage
```

Tingkat:

```text
Madrasah
Kecamatan
Kabupaten
Provinsi
Nasional
Internasional
```

Create baru selalu snapshot Tahun Ajaran aktif. Listing/export mengikuti filter Tahun Ajaran terpilih.

### G3.6 Dashboard Siswa

Dashboard Siswa hanya menampilkan data self-service actor login. Untuk domain BK pada dashboard:

```text
Prestasi Saya             = id_siswa login + id_tahun aktif, maksimal 5
Catatan Pelanggaran Saya  = id_siswa login + id_tahun aktif, maksimal 5
Lihat Semua               = menuju listing self-only yang tetap menyediakan Tahun Ajaran historical
Poin                       = tidak ditampilkan/dihitung
Konseling                  = tidak dibentuk untuk Siswa
```

Dashboard tidak menambah selector Tahun Ajaran karena merupakan current-state. Bila Tahun Ajaran aktif tidak tersedia, dashboard harus menampilkan state unavailable dan tidak memalsukan nilai 0 sebagai data periodik sah.

Kartu Pelajar tetap non-periodik pada dashboard dan akses preview/download tetap divalidasi server-side terhadap scope `DIRI_SENDIRI`.

## 12. Filter UI BK

Aturan global filter berlaku pada halaman BK.

Jika field filter banyak, desktop **tidak boleh memaksa semua field menjadi satu baris sempit**. Konseling canonical memakai dua baris filter yang seimbang:

```text
Baris 1: Tahun Ajaran | Pencarian | Kelas
Baris 2: Status | Bidang | Dari | Sampai | Reset/Tampilkan
```

Mobile tetap stack dan mengikuti global mobile rule; body/table horizontal overflow dilarang.

## 13. Export Konseling

Permission:

```text
bk_konseling.export
```

Boundary tetap Admin/Operator/BK + permission.

Export mengikuti Tahun Ajaran terpilih dan terdiri dari dua sheet:

```text
Konseling BK    = parent / pertemuan awal
Tindak Lanjut   = histori follow-up 1:N
```

Konseling tidak boleh ikut dashboard/export Pimpinan/Guru/Wali/Siswa hanya karena actor memiliki permission laporan lain.

## 14. Kartu Pelajar

Tabel:

```text
kartu_pelajar
```

Field utama:

```text
id_siswa
nomor_kartu
kode_verifikasi
tanggal_terbit
status_aktif
```

Database menjaga maksimum satu kartu Aktif per siswa. Kartu operasional adalah current-state workflow dan tidak diberi filter Tahun Ajaran hanya demi konsistensi visual palsu.

## 15. SQL G3.3.1 — Closed

Baseline:

```text
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_FIX3_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_HOSTING.sql
```

Rework 17 September 2026:

```text
database/20260917_G3_3_1_BK_PERIOD_YEAR_COUNSELING_FOLLOWUP_LOCALHOST.sql
database/20260917_G3_3_1_BK_PERIOD_YEAR_COUNSELING_FOLLOWUP_HOSTING.sql
```

Rework final:

```text
+ catatan_kasus.id_tahun
+ catatan_prestasi.id_tahun
+ tindak_lanjut_konseling_bk
```

Local + hosting schema/source/re-smoke G3.3.1 telah PASS berdasarkan evidence closure PR #9. G3.4 tidak menambah SQL/schema baru.

## 16. Gate G3.3.1 — Closed / Merged

```text
periodic Tahun Ajaran BK                    PASS
filter desktop 2-row Konseling             PASS
Konseling follow-up 1:N                    PASS
no-delete Konseling/follow-up               PASS
localhost SQL/runtime                       PASS / user evidence
hosting schema/source/re-smoke              PASS / user evidence + dump audit
final static exact head                     PASS / user evidence
PR #9                                       MERGED
merge commit                                27d0f867d1c0ca7636a4a48f6c0b3251538ee7f6
```

## 17. Dashboard/Workflow BK — G3.4

G3.4 memakai foundation G3.3.1 final dan tidak mengubah Access Boundary Konseling.

Dashboard BK adalah current-state Tahun Ajaran aktif:

```text
KPI 2×2
- Konseling Proses
- Pelanggaran Bulan Ini
- EWS Alpha 14 Hari
- Prestasi Bulan Ini

Quick Action
- Konseling BK
- Catatan Pelanggaran
- EWS
- Prestasi

Recent / priority
- Jadwal Follow-up Terdekat
- Catatan Pelanggaran Terbaru
- EWS
- Prestasi Terbaru
```

Semua count/list Catatan Pelanggaran, Konseling, dan Prestasi pada dashboard dibatasi `id_tahun` aktif. Dashboard tidak mempunyai selector Tahun Ajaran; historical selector tetap di listing.

Jadwal Follow-up Terdekat:

```text
jika parent sudah mempunyai histori tindak lanjut
→ pakai tanggal_berikutnya dari entry tindak lanjut terbaru

jika belum mempunyai histori tindak lanjut
→ pakai tanggal_berikutnya parent
```

Parent `Proses` tanpa tanggal berikutnya tetap dihitung pada KPI Konseling Proses tetapi tidak dipalsukan menjadi item jadwal.

G3.4 tidak mendefinisikan SLA, overdue, deadline baru, atau ranking prioritas berdasarkan spekulasi. Recent/top section maksimal 5 item + Lihat Semua.

Authorization dashboard tetap permission-aware; Konseling tidak boleh dibentuk untuk Pimpinan/Guru/Wali/Siswa.

Current gate G3.4:

```text
source implementation                        IMPLEMENTED ON FEATURE BRANCH
SSOT sync                                    IN PROGRESS
static gate                                  PENDING
local runtime/UAT                            PENDING
hosting deployment/re-smoke                  NOT STARTED
PR                                            NOT OPENED
```
