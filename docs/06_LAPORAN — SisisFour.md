# 📊 Laporan & Export — SisisFour

**Versi:** 4.0 Final · **Tanggal:** 05 September 2026

Dokumen ini mengatur seluruh mekanisme pelaporan SisisFour: **Matrix Presensi**, **Export Presensi**, dan **Laporan Jurnal Mengajar**. Aturan akses wajib mengikuti RBAC v4.0 dan status Wali Kelas yang bersifat dinamis.

---

## 1. Prinsip Dasar

1. **Presensi resmi** untuk laporan adalah **Sesi Awal (`AWAL`)**.
2. **Sesi Akhir (`AKHIR`)** hanya merupakan data dokumentasi tambahan dan tidak masuk perhitungan Matrix, rekap resmi, atau EWS.
3. Seluruh laporan wajib mengikuti **scope permission** user.
4. **Guru Biasa (non-Wali)** tidak memiliki fitur Matrix maupun laporan/Export Presensi.
5. **Wali Kelas** hanya dapat melihat/menghasilkan laporan untuk **kelas yang sedang menjadi kelas walinya** pada tahun ajaran aktif.
6. **Pimpinan** dapat melihat seluruh laporan secara **read-only**.
7. **Admin dan Operator** memiliki akses administratif penuh.
8. **BK** tidak memiliki akses laporan presensi maupun laporan jurnal.
9. Export tidak boleh tersedia tanpa permission view yang relevan.

---

## 2. Hak Akses Laporan

| Fitur | Admin | Operator | Pimpinan | BK | Guru Biasa | Wali Kelas | Siswa |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Matrix Presensi | Full | Full | Semua, readonly | — | — | Kelas Wali | — |
| Export Presensi Bulanan | Full | Full | Semua, readonly | — | — | Kelas Wali | — |
| Export Presensi Semester | Full | Full | Semua, readonly | — | — | Kelas Wali | — |
| Laporan Jurnal | Semua | Semua | Semua, readonly | — | — | Diri sendiri | — |
| Export Jurnal | Semua | Semua | Semua | — | — | Diri sendiri | — |

### Penjelasan

- **Full/SEMUA:** tidak dibatasi kelas.
- **Kelas Wali:** hanya siswa dan presensi pada kelas yang sedang diwalikan.
- **Diri sendiri:** hanya jurnal milik guru yang sedang login.
- Tanda `—` berarti tidak memiliki akses.

---

# 3. Matrix Presensi

## 3.1 Tujuan

Matrix memberikan gambaran kehadiran siswa per tanggal dalam satu bulan.

### Sumber data

```text
presensi_harian.sesi = 'AWAL'
```

### Status

```text
H = Hadir
S = Sakit
I = Izin
A = Alpha
```

Status `AKHIR` tidak boleh masuk perhitungan Matrix.

## 3.2 Struktur

Kolom utama:

```text
No
NISN
Nama Siswa
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

Setiap tanggal menampilkan status `H/S/I/A`.

Total status dipisahkan menjadi:

```text
H | S | I | A
```

## 3.3 Filter

Untuk halaman Matrix:

- Kelas
- Bulan
- Tahun ajaran aktif otomatis

User tidak boleh memilih kelas di luar scope yang dimilikinya.

### Aturan scope

**Admin/Operator**

- Dapat memilih seluruh kelas.

**Pimpinan**

- Dapat memilih seluruh kelas.
- Seluruh halaman bersifat readonly.

**Wali Kelas**

- Dropdown kelas hanya berisi kelas yang sedang diwalikan.
- Tidak boleh mengakses Matrix kelas lain.

**Guru Biasa**

- Tidak mempunyai akses Matrix.

**BK/Siswa**

- Tidak mempunyai akses Matrix.

## 3.4 Permission

```text
laporan_matrix.view
```

Scope yang sah:

```text
SEMUA
KELAS_DIAMPU
```

`KELAS_TERJADWAL` **tidak digunakan** untuk Matrix.

---

# 4. Export Presensi Bulanan

## 4.1 Tujuan

Menghasilkan rekap presensi seorang/seluruh siswa dalam satu bulan berdasarkan kelas yang dipilih.

## 4.2 Struktur Data

Kolom:

1. No
2. NISN
3. Nama Siswa
4. Kelas
5. Total H
6. Total S
7. Total I
8. Total A
9. Tanggal 1–31

Pada export bulanan, setiap tanggal dapat memiliki:

```text
AW | AK
```

Contoh:

```text
01 Aug
AW = H
AK = H

02 Aug
AW = A
AK = -
```

### Aturan perhitungan

Total:

```text
H/S/I/A = hanya dari AWAL
```

`AKHIR` hanya informasi tambahan.

## 4.3 Permission

```text
laporan_export.generate
```

Scope:

```text
SEMUA
KELAS_DIAMPU
```

Export Wali Kelas wajib dibatasi ke kelas walinya.

### Aturan penting

Permission export tidak boleh dipakai sendirian. Sebelum menghasilkan file, aplikasi wajib memastikan user juga memiliki akses view terhadap laporan yang diekspor.

---

# 5. Export Presensi Semester

## 5.1 Struktur

Rekap satu semester dengan perhitungan:

```text
Total H | S | I | A
```

serta rincian per bulan:

```text
Juli
H | S | I | A

Agustus
H | S | I | A

...
```

Seluruh angka dihitung dari:

```text
sesi = AWAL
```

Data `AKHIR` tidak ditampilkan dalam laporan semester.

## 5.2 Semester

| Semester | Periode |
| --- | --- |
| Ganjil | Juli–Desember |
| Genap | Januari–Juni |

Periode mengikuti konfigurasi Tahun Ajaran aktif.

---

# 6. Laporan Jurnal Mengajar

## 6.1 Tujuan

Menampilkan histori Presensi Mengajar/jurnal guru.

Kolom:

```text
Tanggal
Hari
Jam
Guru
Kelas
Mapel
Status
Materi
```

## 6.2 Hak Akses

### Admin/Operator

Dapat:

- memilih guru,
- memilih kelas berdasarkan guru,
- melihat seluruh jurnal,
- melakukan export.

Scope:

```text
SEMUA
```

### Pimpinan

Dapat melihat seluruh jurnal secara readonly.

Tidak dapat:

- mengedit jurnal,
- merevisi jurnal.

### Guru Biasa

Dapat melihat jurnal **diri sendiri** sesuai data yang telah tersimpan dan permission laporan jurnal yang diberikan.

Tidak dapat melihat jurnal guru lain.

### Wali Kelas

Status Wali Kelas **tidak mengubah scope jurnal menjadi KELAS_DIAMPU**.

Wali tetap melihat jurnalnya sendiri sebagai guru:

```text
DIRI_SENDIRI
```

Kelas yang muncul adalah kelas yang memang diajar oleh guru tersebut.

### BK/Siswa

Tidak memiliki akses laporan jurnal.

---

# 7. Export Jurnal

Export mengikuti filter Laporan Jurnal.

Permission:

```text
laporan_jurnal.export
```

Aturan:

- Guru hanya dapat export jurnal sendiri.
- Wali tetap hanya export jurnal sendiri.
- Pimpinan dapat export seluruh jurnal.
- Admin/Operator dapat export seluruh jurnal.
- Guru tidak dapat menggunakan export untuk membaca jurnal guru lain.

---

# 8. Identitas Siswa pada Laporan

Laporan menggunakan:

```text
NISN
Nama Siswa
Kelas
```

NIK tidak perlu ditampilkan karena merupakan data identitas sensitif dan bukan kebutuhan utama laporan presensi.

---

# 9. Data Historis

Siswa yang sudah tidak aktif tetap dapat muncul pada laporan historis apabila mempunyai data presensi pada periode yang sedang ditarik.

Jangan memfilter laporan historis hanya dengan:

```text
is_active = 1
```

Relasi harus tetap mempertahankan histori presensi.

---

# 10. Export dan Audit

Setiap export wajib mencatat:

```text
id_user
permission
jenis_laporan
filter
waktu
```

Audit harus masuk ke mekanisme log aktivitas SisisFour.

---

# 11. Dual Output

Halaman laporan mendukung:

```text
HTML
JSON
```

melalui parameter:

```text
?format=json
```

Business logic harus berada di Service, bukan duplikasi di Controller.

---

# 12. Catatan Developer

1. Jangan menggunakan `KELAS_TERJADWAL` untuk Matrix.
2. Jangan memberikan menu laporan kepada Guru Biasa.
3. Jangan memberikan export tanpa view.
4. Scope Wali selalu berasal dari mapping Wali Kelas aktif.
5. Pimpinan selalu readonly.
6. Semua query laporan wajib menerapkan scope sebelum data dikembalikan.
7. `AWAL` adalah satu-satunya sumber perhitungan resmi H/S/I/A.
8. Export wajib mengikuti filter yang aktif.
9. Endpoint JSON harus menerapkan authorization yang sama dengan halaman HTML.

---

© 2026 SisisFour · MTsN 4 Jombang · Laporan & Export Final
