# 📋 BK, Prestasi & Kartu Pelajar — SisisFour

**Versi Acuan: v0.5**  
**Tanggal:** 08 September 2026

Dokumen ini menetapkan seluruh aturan bisnis, akses, scope, histori, keamanan, dan output untuk:

1. Bimbingan Konseling;
2. Master Pelanggaran;
3. Catatan Kasus;
4. Prestasi Siswa;
5. Top 20 Poin Pelanggaran;
6. Kartu Pelajar Digital;
7. Verifikasi QR.

---

# BAGIAN A — PRINSIP UMUM

## 1. Sumber Identitas Siswa

Seluruh modul siswa menggunakan:

```text
siswa.id
```

Kelas aktif/historis harus di-resolve dari:

```text
anggota_kelas
riwayat_siswa
```

Wali hanya boleh melihat siswa kelas mapping aktif.

---

## 2. Wali Bukan Role

Wali menggunakan role:

```text
guru
```

dengan scope contextual:

```text
KELAS_DIAMPU
```

Tidak boleh ada cabang authorization yang mengandalkan string role `wali_kelas`.

---

# BAGIAN B — MASTER PELANGGARAN

## 3. Tabel

```text
ref_pelanggaran
```

Field:

```text
id
nama_pelanggaran
kategori
poin
```

Kategori:

```text
Ringan
Sedang
Berat
```

---

## 4. Hak Akses Master Pelanggaran

| Role | Akses |
|---|---|
| Admin | Full |
| Operator | Full |
| Pimpinan | Readonly bila diberi view melalui modul BK |
| BK | Full |
| Guru | Tidak |
| Wali | Tidak manage |
| Siswa | Tidak |

Permission manage:

```text
bk_pelanggaran_master.manage
```

Tidak ada permission khusus view master yang terpisah; kebutuhan readonly Pimpinan dapat diberikan melalui halaman BK yang sudah ber-scope readonly.

---

## 5. Aturan Pelanggaran

- nama wajib;
- kategori wajib;
- poin integer >= 0;
- item yang telah dipakai Catatan Kasus tidak boleh dihapus bila FK RESTRICT;
- perubahan poin setelah kasus lama tercatat harus dipertimbangkan.

Untuk menjaga histori poin, implementasi disarankan menyimpan nilai poin snapshot pada Catatan Kasus bila kebutuhan audit sekolah mengharuskannya. Namun schema v0.5 saat ini menghitung dari `ref_pelanggaran.poin`, sehingga perubahan poin referensi akan memengaruhi agregat historis. Jika behavior tersebut tidak diinginkan, schema harus diperluas sebelum implementasi.

---

# BAGIAN C — CATATAN KASUS

## 6. Tabel

```text
catatan_kasus
```

Field:

```text
id_siswa
id_pelanggaran
tanggal
keterangan
id_guru_input
created_at
updated_at
updated_by
```

---

## 7. Hak Akses Catatan Kasus

| Role | Manage | View |
|---|---|---|
| Admin | Semua | Semua |
| Operator | Semua | Semua |
| Pimpinan | Tidak | Semua readonly |
| BK | Semua | Semua |
| Guru biasa | Tidak | Tidak |
| Wali | Tidak | Siswa kelas Wali |
| Siswa | Tidak | Tidak |

Permission:

```text
bk_kasus.manage
bk_kasus.view
```

Scope view:

```text
SEMUA
KELAS_DIAMPU
DIRI_SENDIRI
```

Untuk v0.5, Siswa tidak diberikan UI Catatan Kasus meskipun permission schema mendukung `DIRI_SENDIRI`; role mapping final tidak boleh memberi permission tersebut kepada Siswa kecuali kebijakan sekolah berubah.

---

## 8. Input Kasus

Input minimum:

```text
Siswa
Pelanggaran
Tanggal
Keterangan
```

`id_guru_input`/actor tidak dipercaya dari browser.

Admin/Operator/BK:

```text
actor = user login
```

Jika user mempunyai `id_guru`, simpan sebagai `id_guru_input`.

Jika actor tidak memiliki `id_guru`, implementasi harus menentukan apakah kolom perlu nullable atau memakai actor administratif lain. Karena schema v0.5 `catatan_kasus.id_guru_input` saat ini NOT NULL, sebelum implementasi modul BK wajib memastikan Admin/Operator yang dapat manage mempunyai identitas Guru yang valid, atau schema diperbarui secara eksplisit. Jangan mengisi ID palsu.

---

## 9. View Wali

Wali dapat melihat detail lengkap kasus siswa kelas Wali.

Wali tidak boleh:

- create;
- edit;
- delete;
- mengubah master pelanggaran;
- melihat siswa kelas lain.

Jika mapping Wali dinonaktifkan, akses tersebut hilang.

---

## 10. Historis

Kasus siswa tidak boleh hilang jika siswa:

```text
Pindah
Keluar
Lulus
```

Laporan/detail histori tetap dapat diakses oleh role `SEMUA`.

Scope Wali aktif hanya untuk kelas Wali saat ini, kecuali kebijakan histori kelas lama ditambahkan kemudian.

---

# BAGIAN D — POIN DAN TOP 20

## 11. Perhitungan Poin

Total poin:

```text
SUM(ref_pelanggaran.poin)
berdasarkan catatan_kasus siswa
```

Filter dapat dibatasi:

```text
Tahun
Bulan
Periode
Kelas
```

bila UI membutuhkannya.

---

## 12. EWS Tidak Menggunakan Poin BK

EWS Radar Presensi murni:

```text
Alpha
Sesi Awal
14 hari terakhir
```

Poin pelanggaran tidak boleh digabung menjadi trigger EWS Presensi.

---

## 13. Top 20

Top 20 adalah widget analitik, bukan menu utama.

Isi:

```text
20 siswa dengan akumulasi poin tertinggi
```

Akses:

| Role | Akses |
|---|---|
| Admin | Semua |
| Operator | Semua |
| Pimpinan | Semua readonly |
| BK | Semua |
| Guru | Tidak |
| Wali | Kelas Wali |
| Siswa | Tidak |

Wali tidak boleh menerima Top 20 global lalu difilter frontend.

Query harus sudah scoped di server.

---

# BAGIAN E — PRESTASI

## 14. Tabel

```text
catatan_prestasi
```

Field:

```text
id_siswa
nama_prestasi
tingkat
tanggal
penyelenggara
keterangan
id_guru_input
created_at
```

Schema v0.5 saat ini tidak mempunyai `updated_at`/`updated_by`. Jika fitur edit Prestasi memerlukan audit penuh, perubahan schema harus dilakukan sebelum implementasi, bukan hanya ditambah di Controller.

---

## 15. Hak Akses Prestasi

| Role | Manage | View |
|---|---|---|
| Admin | Semua | Semua |
| Operator | Semua | Semua |
| Pimpinan | Tidak | Semua |
| BK | Semua | Semua |
| Guru | Tidak | Tidak |
| Wali | Tidak | Siswa kelas Wali |
| Siswa | Tidak | Diri |

Permission:

```text
prestasi.manage
prestasi.view
```

Scope:

```text
SEMUA
KELAS_DIAMPU
DIRI_SENDIRI
```

---

## 16. Tingkat Prestasi

Nilai `tingkat` dapat berupa:

```text
Madrasah
Kecamatan
Kabupaten
Provinsi
Nasional
Internasional
```

atau istilah lain sesuai kebijakan madrasah.

Karena schema menggunakan VARCHAR, validasi pilihan dapat dikelola di UI/Service tanpa mengubah tabel.

---

## 17. Bukti Prestasi

Upload bukti tidak diwajibkan pada v0.5.

Jika di masa depan ditambahkan:

- storage harus terpisah;
- file type dibatasi;
- tidak boleh executable;
- authorization download wajib.

---

# BAGIAN F — KARTU PELAJAR DIGITAL

## 18. Tabel

```text
kartu_pelajar
```

Field:

```text
id
id_siswa
nomor_kartu
kode_verifikasi
tanggal_terbit
status_aktif
```

Status:

```text
Aktif
Nonaktif
```

---

## 19. Fungsi

Modul Kartu meliputi:

- daftar kartu;
- generate;
- preview;
- cetak;
- download;
- cetak massal;
- reissue output;
- verifikasi QR;
- status aktif/nonaktif.

---

# BAGIAN G — HAK AKSES KARTU

## 20. View

| Role | Scope |
|---|---|
| Admin | Semua |
| Operator | Semua |
| Pimpinan | Semua |
| BK | Tidak |
| Guru biasa | Tidak |
| Wali | Kelas Wali |
| Siswa | Diri |

Permission:

```text
kartu_pelajar.view
```

Scope:

```text
SEMUA
KELAS_DIAMPU
DIRI_SENDIRI
```

---

## 21. Manage

| Role | Scope |
|---|---|
| Admin | Semua |
| Operator | Semua |
| Pimpinan | Semua |
| BK | Tidak |
| Guru | Tidak |
| Wali | Tidak |
| Siswa | Tidak |

Permission:

```text
kartu_pelajar.manage
```

Scope didukung:

```text
SEMUA
KELAS_DIAMPU
```

Pada kebijakan v0.5:
- Admin, Operator, dan Pimpinan memiliki `kartu_pelajar.manage` dengan scope `SEMUA`;
- Wali tidak diberikan manage;
- Wali hanya menggunakan hak view/cetak untuk kelas Wali.

---

# BAGIAN H — GENERATE

## 22. Generate Kartu

Generate dilakukan bila siswa belum mempunyai kartu.

Server membuat:

```text
nomor_kartu
kode_verifikasi
tanggal_terbit
status_aktif = Aktif
```

Nomor dan kode harus unik.

Jangan menggunakan nilai berurutan mudah ditebak untuk `kode_verifikasi`.

Gunakan random token yang cukup kuat.

---

## 23. Idempotensi

Jika kartu sudah ada:

```text
Generate
```

tidak boleh membuat duplicate baru secara diam-diam.

UI harus menawarkan:

```text
Preview
Cetak
Reissue
```

---

# BAGIAN I — REISSUE

## 24. Definisi

Reissue adalah membuat ulang hasil visual/cetak, bukan membuat identitas kartu baru.

Tetap:

```text
nomor_kartu
kode_verifikasi
id_siswa
tanggal_terbit
```

Tidak berubah kecuali ada proses administratif penerbitan ulang yang secara eksplisit didefinisikan kemudian.

---

# BAGIAN J — CETAK

## 25. Cetak Individual

Admin/Operator/Pimpinan:

```text
semua sesuai scope
```

Wali:

```text
kelas Wali
```

Siswa:

```text
download/preview diri
```

---

## 26. Cetak Massal

Boleh:

```text
Admin
Operator
Pimpinan
Wali
```

Scope Wali:

```text
hanya kelas Wali
```

Request massal wajib memvalidasi seluruh ID siswa di server.

Jangan mempercayai checkbox client sebagai authorization.

---

# BAGIAN K — FORMAT KARTU

## 27. Kanvas

Ukuran:

```text
1011 × 638 px
```

Field:

```text
Nama
NIK
Kelas
Jenis Kelamin
Tahun Ajaran
Tempat/Tanggal Lahir
Alamat
Foto
QR
Kode/Nomor Kartu
```

NIK pada kartu fisik boleh tercetak penuh untuk kebutuhan administrasi sekolah.

---

## 28. Background

Setting:

```text
background_kta_depan
background_kta_belakang
```

Asset harus berada pada storage yang diizinkan Settings.

---

## 29. Foto

Sumber:

```text
siswa.foto
```

Aturan foto mengikuti Master Siswa:

- PNG;
- max 2 MB;
- crop 3:4;
- re-encode.

Jika tidak ada foto:

```text
placeholder / area kosong
```

Tidak gagal generate seluruh kartu.

---

# BAGIAN L — QR VERIFICATION

## 30. Endpoint Publik

```text
/kartu/verify/{kode_verifikasi}
```

Tidak membutuhkan login.

Endpoint ini hanya melakukan read minimum.

---

## 31. Data Publik

Boleh menampilkan:

```text
Nama
Kelas
Status kartu
Nama madrasah
Nomor/kode kartu seperlunya
Foto bila kebijakan sekolah mengizinkan
```

NIK wajib masking.

Contoh:

```text
351012xxxxxx1234
```

Tidak boleh menampilkan:

- NIK penuh;
- alamat lengkap;
- nama orang tua;
- nomor telepon;
- data BK;
- riwayat Presensi;
- password/token.

---

## 32. Kartu Tidak Valid

Jika:

```text
kode tidak ada
status Nonaktif
siswa deleted
```

halaman harus memberi status yang aman seperti:

```text
Kartu tidak aktif / tidak valid
```

Jangan menjelaskan alasan internal terlalu detail.

---

# BAGIAN M — NONAKTIF OTOMATIS

## 33. Trigger

Saat siswa menjadi:

```text
Lulus
Pindah
Keluar
```

semua kartu aktif siswa:

```text
status_aktif = Nonaktif
```

Aksi dilakukan dalam transaction yang sama dengan perubahan status siswa bila memungkinkan.

---

# BAGIAN N — SERVICE

## 34. Service yang Disarankan

```text
BkService
PrestasiService
KartuPelajarService
KartuRenderService
```

### `BkService`
- master pelanggaran;
- catatan kasus;
- scope Wali;
- total poin;
- Top 20.

### `PrestasiService`
- manage;
- view scope;
- histori.

### `KartuPelajarService`
- generate;
- status;
- scope;
- verify token.

### `KartuRenderService`
- template;
- foto;
- QR;
- render output;
- mass print.

---

# BAGIAN O — AUDIT

## 35. Log

Aksi penting dicatat di:

```text
log_activity
```

Contoh:

```text
CREATE BK
UPDATE BK
DELETE BK
CREATE PRESTASI
GENERATE KARTU
REISSUE KARTU
CETAK KARTU
CETAK MASSAL
NONAKTIF KARTU
```

Schema log tetap:

```text
id_user
aksi
modul
keterangan
waktu
```

---

# BAGIAN P — SECURITY

## 36. IDOR

Dilarang:

```text
/kartu/view?id_siswa=123
```

tanpa verifikasi scope.

Siswa selalu menggunakan:

```text
session('id_siswa')
```

Wali selalu diverifikasi terhadap mapping kelas aktif.

---

## 37. QR Token

`kode_verifikasi`:

- random;
- unique;
- tidak berasal dari NISN/NIK;
- tidak boleh dapat ditebak dengan increment sederhana.

---

## 38. Output Escape

Nama/keterangan/penyelenggara harus di-escape pada HTML.

Rendering kartu juga harus memperlakukan teks sebagai data, bukan markup.

---

# BAGIAN Q — CHECKPOINT

## 39. BK

- CRUD master;
- FK delete;
- kasus;
- scope Wali;
- Pimpinan readonly;
- Top 20;
- EWS tidak memakai poin.

## 40. Prestasi

- manage Admin/Operator/BK;
- view Pimpinan;
- view Wali kelas;
- view Siswa diri;
- no upload wajib.

## 41. Kartu

- generate idempotent;
- nomor unique;
- token random unique;
- preview;
- cetak;
- mass print;
- Wali kelas only;
- Siswa diri;
- Pimpinan sesuai permission;
- status siswa menonaktifkan kartu;
- QR public masking;
- audit.

---

# 42. Kriteria Selesai

Modul dinyatakan selesai bila:

1. tidak ada role Wali khusus;
2. scope Wali selalu server-side;
3. BK dan Prestasi tidak bocor antar siswa;
4. EWS tetap terpisah dari poin BK;
5. Kartu tidak duplicate;
6. QR publik tidak membocorkan data sensitif;
7. status siswa sinkron dengan kartu;
8. audit tercatat;
9. seluruh direct route terlindungi permission/scope.
