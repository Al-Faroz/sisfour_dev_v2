# 📊 Laporan & Export — SisisFour

**Versi Acuan: v0.5**  
**Tanggal:** 08 September 2026

Dokumen ini menetapkan seluruh aturan bisnis, scope, filter, struktur data, export, histori, dan audit untuk modul **Matrix Presensi**, **Export Presensi**, dan **Laporan Jurnal Mengajar**.

Dokumen ini harus dibaca bersama:

```text
02_DATABASE
03_AUTH_RBAC_MENU
04_MASTER_DATA
05_PRESENSI
```

Semua laporan wajib membaca data dari sumber operasional final dan tidak boleh membuat perhitungan yang berbeda dari aturan Presensi.

---

# BAGIAN A — PRINSIP UMUM

## 1. Sumber Presensi Resmi

Perhitungan resmi Presensi Siswa hanya menggunakan:

```text
presensi.sesi = 'Sesi Awal'
```

Status:

```text
Hadir
Sakit
Izin
Alpha
```

`Sesi Akhir` adalah dokumentasi tambahan dan:

- tidak masuk Matrix resmi;
- tidak masuk rekap H/S/I/A resmi;
- tidak masuk EWS;
- tidak memengaruhi total ketidakhadiran semester.

---

## 2. Sumber Data Historis

Laporan tidak boleh hanya mengandalkan kondisi Master Data saat ini.

Data historis dapat berasal dari:

```text
presensi
presensi_mengajar
riwayat_siswa
jadwal_guru
tahun_ajaran
kelas
```

Snapshot yang tersimpan pada Presensi/Jurnal harus diprioritaskan untuk identitas historis bila diperlukan.

Contoh:

```text
nama_siswa_snapshot
nama_guru_snapshot
```

---

## 3. Scope

Semua laporan wajib memakai `AuthService::resolveScope()`.

Scope relevan:

```text
SEMUA
KELAS_DIAMPU
DIRI_SENDIRI
TIDAK_ADA
```

`KELAS_TERJADWAL` tidak digunakan untuk Matrix dan Export Presensi.

Guru biasa tidak memperoleh Matrix/Export Presensi hanya karena pernah mengajar kelas tersebut.

---

# BAGIAN B — HAK AKSES

## 4. Matriks Akses

| Fitur | Admin | Operator | Pimpinan | BK | Guru | Wali | Siswa |
|---|---|---|---|---|---|---|---|
| Matrix Presensi | Semua | Semua | Semua readonly | — | — | Kelas Wali | — |
| Export Bulanan | Semua | Semua | Semua sesuai permission | — | — | Kelas Wali | — |
| Export Semester | Semua | Semua | Semua sesuai permission | — | — | Kelas Wali | — |
| Laporan Jurnal | Semua | Semua | Semua readonly | — | Diri | Diri | — |
| Export Jurnal | Semua | Semua | Semua sesuai permission | — | Diri | Diri | — |

---

## 5. Permission

### Matrix

```text
laporan_matrix.view
```

Scope:

```text
SEMUA
KELAS_DIAMPU
```

### Export Presensi

```text
laporan_export.generate
```

Scope:

```text
SEMUA
KELAS_DIAMPU
```

User juga harus mempunyai hak view terhadap data yang diekspor.

### Laporan Jurnal

```text
laporan_jurnal.view
```

Scope:

```text
SEMUA
DIRI_SENDIRI
```

### Export Jurnal

```text
laporan_jurnal.export
```

Scope:

```text
SEMUA
DIRI_SENDIRI
```

---

# BAGIAN C — MATRIX PRESENSI

## 6. Tujuan

Matrix menampilkan status Presensi resmi siswa per tanggal dalam satu bulan.

Sumber:

```text
presensi
WHERE sesi = 'Sesi Awal'
```

---

## 7. Struktur Matrix

Kolom:

```text
No
NISN
Nama Siswa
Kelas
H
S
I
A
01
02
03
...
31
```

Isi tanggal:

```text
H
S
I
A
-
```

Mapping:

```text
H = Hadir
S = Sakit
I = Izin
A = Alpha
- = tidak ada record / bukan hari efektif / belum menjadi anggota
```

Tanda `-` tidak boleh otomatis dianggap Alpha.

---

## 8. Membership Historis

Matrix harus menghormati periode keanggotaan siswa.

Jika siswa masuk kelas di tengah semester:

- tanggal sebelum `riwayat_siswa.tanggal_mulai` tidak dihitung Alpha;
- siswa tetap dapat muncul di Matrix periode setelah masuk;
- laporan historis lama tetap dapat menampilkan siswa walaupun status saat ini Lulus/Pindah/Keluar.

Jika siswa keluar dari kelas:

- tanggal setelah `tanggal_selesai` tidak boleh dianggap Alpha untuk kelas lama.

---

## 9. Hari Efektif

Matrix hanya menampilkan status yang benar-benar ada pada tabel Presensi.

Dokumen ini tidak mendefinisikan tabel kalender akademik terpisah.

Karena itu:

- hari tanpa record tidak otomatis dinilai Alpha;
- penentuan hari efektif tidak boleh ditebak hanya dari Senin–Minggu;
- jika kelak dibuat Kalender Akademik, laporan dapat menggunakannya sebagai sumber tambahan.

---

## 10. Filter Matrix

Filter wajib:

```text
Kelas
Bulan
Tahun Ajaran / Semester
```

Default:

```text
tahun_ajaran.status_aktif = 1
```

Admin/Operator/Pimpinan dapat memilih kelas sesuai seluruh scope.

Wali:

```text
dropdown kelas = kelas Wali aktif
```

Direct URL dengan kelas lain harus ditolak.

---

## 11. Query Matrix

Service sebaiknya memisahkan:

```text
resolveScope()
resolveKelas()
resolveMembership()
ambilPresensiBulanan()
buildMatrix()
```

Tidak boleh membuat query per siswa × tanggal yang menghasilkan N+1 besar.

Lebih baik mengambil dataset satu periode lalu membentuk Matrix di Service.

---

# BAGIAN D — EXPORT PRESENSI BULANAN

## 12. Tujuan

Menghasilkan file rekap Presensi satu kelas dalam satu bulan.

---

## 13. Format

Kolom dasar:

```text
No
NISN
Nama Siswa
Kelas
Total Hadir
Total Sakit
Total Izin
Total Alpha
Tanggal 01
Tanggal 02
...
Tanggal 31
```

Untuk export bulanan, tiap tanggal boleh menampilkan dua informasi:

```text
AW = Sesi Awal
AK = Sesi Akhir
```

Contoh:

```text
01
AW: H
AK: H
```

atau:

```text
02
AW: A
AK: -
```

Total H/S/I/A tetap hanya menggunakan Sesi Awal.

---

## 14. Format File

Format minimum:

```text
XLSX
```

PDF dapat ditambahkan bila dibutuhkan, tetapi tidak menggantikan XLSX sebagai export data utama.

Nama file disarankan:

```text
presensi_bulanan_{kelas}_{tahun}_{bulan}.xlsx
```

---

## 15. Metadata Header

File dapat memuat:

```text
Nama Madrasah
Tahun Ajaran
Semester
Kelas
Bulan
Tanggal Generate
```

Data generator/user tidak wajib ditampilkan di file, tetapi tetap dicatat di audit.

---

# BAGIAN E — EXPORT PRESENSI SEMESTER

## 16. Periode Semester

```text
Ganjil → Juli–Desember
Genap  → Januari–Juni
```

Acuan semester berasal dari:

```text
tahun_ajaran.semester
```

---

## 17. Struktur

Per siswa:

```text
NISN
Nama
Kelas
Total H
Total S
Total I
Total A
```

Lalu rincian per bulan:

```text
Juli    H S I A
Agustus H S I A
...
```

Hanya `Sesi Awal`.

---

## 18. Siswa Historis Semester

Siswa yang:

```text
Pindah
Keluar
Lulus
```

tetap dapat muncul bila mempunyai Presensi pada semester tersebut.

Jangan memfilter semester hanya berdasarkan:

```text
siswa.status_aktif = 'Aktif'
```

---

# BAGIAN F — LAPORAN JURNAL MENGAJAR

## 19. Sumber

```text
presensi_mengajar
JOIN jadwal_guru
JOIN kelas
JOIN mata_pelajaran
```

Snapshot Guru tetap dipakai untuk histori.

---

## 20. Kolom

```text
Tanggal
Hari
Jam
NIP
Guru
Kelas
Kode Mapel
Mata Pelajaran
Sesi
Status
Materi
Tahun Ajaran
Semester
```

Status:

```text
Hadir
Izin
Sakit
```

---

## 21. Filter

Admin/Operator/Pimpinan:

```text
Guru
Kelas
Tahun Ajaran
Hari
Status
Tanggal Awal
Tanggal Akhir
```

Guru/Wali:

```text
Guru = diri sendiri, fixed
Kelas = hanya kelas yang pernah/masih diajar sesuai dataset
Tahun
Hari
Status
Tanggal
```

---

## 22. Scope Jurnal

Wali tidak mendapatkan `KELAS_DIAMPU` untuk Laporan Jurnal.

Wali tetap:

```text
DIRI_SENDIRI
```

karena Jurnal adalah catatan aktivitas mengajar Guru.

---

## 23. Jadwal Nonaktif

Jurnal historis yang merujuk Jadwal Nonaktif tetap ditampilkan.

Jangan memfilter laporan historis hanya:

```text
jadwal_guru.status_jadwal = 'Aktif'
```

Status Jadwal aktif hanya relevan untuk input baru, bukan histori.

---

# BAGIAN G — EXPORT JURNAL

## 24. Aturan

Export harus memakai filter yang sama dengan halaman laporan.

Guru/Wali:

```text
hanya jurnal dirinya
```

Pimpinan/Admin/Operator:

```text
SEMUA sesuai permission
```

---

## 25. Format

XLSX minimum.

Nama file:

```text
laporan_jurnal_{periode}.xlsx
```

Kolom sama dengan tampilan atau subset yang tetap mencukupi audit.

---

# BAGIAN H — IDENTITAS DAN PRIVASI

## 26. Identitas Siswa

Laporan Presensi menggunakan:

```text
NISN
Nama Siswa
Kelas
```

NIK tidak ditampilkan karena bukan kebutuhan laporan Presensi.

---

## 27. Data Sensitif

Jangan export:

- password;
- password hash;
- token;
- NIK bila tidak dibutuhkan;
- data keluarga siswa;
- alamat lengkap;
- data BK.

---

# BAGIAN I — AUDIT

## 28. Log Export

Setiap export penting wajib dicatat ke:

```text
log_activity
```

Kolom schema:

```text
id_user
aksi
modul
keterangan
waktu
```

Contoh:

```text
aksi = EXPORT
modul = Laporan Presensi
keterangan = Export bulanan kelas 7-A Agustus 2026
```

Filter dapat ditulis ringkas di `keterangan`.

Tidak membuat schema log kedua.

---

# BAGIAN J — DUAL OUTPUT

## 29. HTML dan JSON

Halaman laporan dapat mendukung:

```text
HTML
JSON
```

Contoh:

```text
?format=json
```

Authorization HTML dan JSON harus identik.

JSON tidak boleh mengirim data di luar scope.

---

# BAGIAN K — SERVICE

## 30. Service yang Disarankan

```text
LaporanPresensiService
LaporanJurnalService
ExportService
```

Tanggung jawab:

### `LaporanPresensiService`
- Matrix;
- rekap bulanan;
- rekap semester;
- membership historis;
- scope.

### `LaporanJurnalService`
- daftar jurnal;
- filter;
- scope diri/semua;
- histori Jadwal.

### `ExportService`
- format XLSX/PDF;
- filename;
- metadata;
- audit export.

---

# BAGIAN L — PERFORMANCE

## 31. Query

Gunakan index:

```text
presensi(id_tahun, tanggal, sesi, status)
presensi(id_siswa, tanggal)
presensi_mengajar(id_guru, tanggal)
```

Hindari:

```text
query satu siswa per loop
query satu tanggal per loop
```

Untuk Matrix, ambil seluruh periode dalam query terkontrol.

---

# BAGIAN M — ERROR RULE

## 32. Server Harus Menolak

- kelas di luar scope;
- tahun tidak valid;
- permission export tanpa view;
- Guru biasa mencoba Matrix;
- Wali mencoba kelas bukan Wali;
- Guru export jurnal Guru lain;
- tanggal awal > tanggal akhir;
- format bulan tidak valid.

---

# BAGIAN N — CHECKPOINT

## 33. Matrix

- hanya Sesi Awal;
- total benar;
- membership historis benar;
- scope Wali benar;
- Pimpinan readonly;
- Guru biasa tidak akses;
- tidak ada N+1 berat.

## 34. Export Bulanan

- AW masuk total;
- AK hanya informasi;
- filter sama;
- scope sama;
- XLSX valid;
- audit tercatat.

## 35. Export Semester

- hanya AW;
- periode semester benar;
- siswa historis tetap muncul;
- total bulanan = total semester.

## 36. Jurnal

- histori Jadwal Nonaktif tetap terlihat;
- Guru hanya diri;
- Wali hanya diri;
- Pimpinan/Admin/Operator semua;
- export mengikuti filter.

---

# 37. Kriteria Selesai

Modul Laporan dinyatakan selesai bila:

1. seluruh angka bersumber dari data Presensi final;
2. Sesi Akhir tidak masuk perhitungan resmi;
3. scope RBAC dijaga Service;
4. export sama dengan filter layar;
5. histori tidak hilang karena status Master berubah;
6. tidak ada kebocoran NIK/BK;
7. log export tercatat;
8. HTML dan JSON konsisten;
9. Matrix dan semester lulus regression test.
