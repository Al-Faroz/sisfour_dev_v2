# 📋 BK, Prestasi & Kartu Pelajar — SisisFour

**Versi:** 4.0 Final · **Tanggal:** 05 September 2026

Dokumen ini mengatur modul **Bimbingan Konseling (BK)**, **Prestasi Siswa**, dan **Kartu Pelajar Digital** beserta aturan akses final RBAC v4.0.

---

# Bagian 1 — Bimbingan Konseling

## 1.1 Master Pelanggaran

Tabel referensi:

```text
ref_pelanggaran
```

Fungsi:

- tambah,
- lihat,
- ubah,
- hapus.

Hak akses:

| Role | Akses |
| --- | --- |
| Admin | Full |
| Operator | Full |
| Pimpinan | Readonly |
| BK | Full |
| Guru Biasa | Tidak ada |
| Wali Kelas | Tidak dapat mengelola master |
| Siswa | Tidak ada |

---

## 1.2 Catatan Kasus

Catatan kasus menyimpan histori masalah siswa.

Informasi utama:

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

Poin pelanggaran dihitung berdasarkan data pelanggaran.

Poin BK **tidak menentukan EWS Radar**.

EWS murni berdasarkan:

```text
Alpha
Sesi Awal
14 hari terakhir
```

---

## 1.3 Hak Akses Catatan Kasus

| Role | Input/Edit | View |
| --- | --- | --- |
| Admin | Full | Semua |
| Operator | Full | Semua |
| Pimpinan | Tidak | Semua readonly |
| BK | Full | Semua |
| Guru Biasa | Tidak | Tidak |
| Wali Kelas | Tidak | Detail siswa kelas wali, readonly |
| Siswa | Tidak | Tidak |

### Wali Kelas

Wali Kelas boleh melihat **seluruh detail** catatan kasus siswa yang berada pada kelas walinya.

Wali tidak boleh:

- menambah kasus,
- mengubah kasus,
- menghapus kasus,
- mengubah master pelanggaran.

---

# Bagian 2 — Prestasi Siswa

## 2.1 Tujuan

Mencatat prestasi siswa pada tingkat sekolah maupun luar sekolah.

Contoh field:

```text
id_siswa
nama_prestasi
tingkat
tanggal
penyelenggara
keterangan
id_guru_input
created_at
updated_at
updated_by
```

Upload bukti tidak diwajibkan dalam desain saat ini.

## 2.2 Hak Akses

| Role | Manage | View |
| --- | --- | --- |
| Admin | Full | Semua |
| Operator | Full | Semua |
| Pimpinan | Tidak | Semua |
| BK | Full | Semua |
| Guru Biasa | Tidak | Tidak |
| Wali Kelas | Tidak | Siswa kelas wali |
| Siswa | Tidak | Diri sendiri |

### Catatan

Walaupun fitur Prestasi secara fungsional merupakan bagian dari modul BK, **Admin dan Operator tetap memiliki kewenangan administratif penuh**.

---

# Bagian 3 — Top 20 Poin Pelanggaran

Top 20 adalah widget analitik, bukan menu utama.

Isi:

```text
20 siswa dengan akumulasi poin pelanggaran tertinggi
```

Akses:

| Role | Akses |
| --- | --- |
| Admin | Ya |
| Operator | Ya |
| Pimpinan | Ya |
| BK | Ya |
| Guru Biasa | Tidak |
| Wali Kelas | Ya, sesuai scope yang diizinkan |
| Siswa | Tidak |

Untuk Wali Kelas, data siswa harus dibatasi ke kelas walinya.

Top 20 tidak boleh membuka data siswa di luar scope.

---

# Bagian 4 — Kartu Pelajar Digital

## 4.1 Fungsi

Fitur meliputi:

- daftar kartu,
- generate kartu,
- cetak,
- preview,
- download,
- reissue,
- verifikasi QR.

## 4.2 Hak Akses

### View / Cetak

| Role | Scope |
| --- | --- |
| Admin | Semua |
| Operator | Semua |
| Pimpinan | Semua |
| BK | Tidak |
| Guru Biasa | Tidak |
| Wali Kelas | Kelas wali |
| Siswa | Diri sendiri |

### Manage / Terbitkan

| Role | Scope |
| --- | --- |
| Admin | Semua |
| Operator | Semua |
| Pimpinan | Semua |
| BK | Tidak |
| Guru Biasa | Tidak |
| Wali Kelas | Tidak |
| Siswa | Tidak |

**Wali Kelas hanya mempunyai kewenangan mencetak kartu**, bukan menerbitkan/reissue/mengelola kartu.

---

# 5. Generate dan Reissue

## Generate

Generate membuat kartu yang belum tersedia.

Dapat dilakukan:

```text
Admin
Operator
Pimpinan
```

## Reissue

Reissue hanya membuat ulang output kartu.

Nilai berikut tidak berubah:

```text
nomor kartu
kode verifikasi
identitas siswa
```

---

# 6. Cetak Massal

Cetak massal dapat dilakukan:

```text
Admin
Operator
Pimpinan
Wali Kelas
```

Scope:

- Admin/Operator/Pimpinan → semua kelas.
- Wali → hanya kelas walinya.

Wali dapat melakukan:

- cetak individual,
- cetak massal kelas.

---

# 7. Format Kartu

Ukuran kanvas:

```text
1011 × 638 px
```

Field:

```text
nama
NIK
kelas
jenis_kelamin
tahun_ajaran
TTL
alamat
foto
QR
kode_kartu
```

NIK pada kartu fisik boleh tercetak penuh untuk kebutuhan administrasi.

---

# 8. Verifikasi QR

Endpoint publik:

```text
/kartu/verify/{kode_verifikasi}
```

Tidak membutuhkan login.

Halaman publik hanya menampilkan data minimum.

NIK wajib dimasking.

Contoh:

```text
351012xxxxxx1234
```

Tidak boleh menampilkan NIK penuh pada halaman verifikasi publik.

---

# 9. Nonaktif Otomatis

Jika status siswa menjadi:

```text
Lulus
Pindah
Keluar
```

maka kartu otomatis:

```text
Nonaktif
```

---

# 10. Foto

Foto siswa:

- PNG,
- maksimal 2MB,
- crop 3:4,
- re-encode,
- metadata berbahaya harus dibuang.

Jika tidak ada foto, area foto dikosongkan.

---

# 11. Audit

Aksi berikut wajib tercatat:

```text
generate
reissue
cetak
perubahan status
```

Catatan minimal:

```text
id_user
waktu
aksi
id_siswa / id_kartu
```

---

# 12. Prinsip Keamanan

1. Wali tidak boleh mengakses kartu di luar kelas walinya.
2. Siswa tidak boleh melihat kartu siswa lain.
3. Permission `kartu_pelajar.manage` tidak berarti otomatis memberi akses semua data jika scope bersifat kelas.
4. Endpoint harus tetap melakukan validasi scope walaupun UI menyembunyikan tombol.
5. Verifikasi QR publik tidak boleh membocorkan data identitas sensitif.

---

© 2026 SisisFour · MTsN 4 Jombang · BK, Prestasi & Kartu Final
