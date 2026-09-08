# Master Data — SisisFour

**Versi Acuan Utama:** v0.5 FINAL BASELINE  
**Tanggal Acuan:** 08 September 2026  
**Baseline Aplikasi:** `main` @ `b85b857e1a38b6eb1fd26ba2d9aa61ae5e679f55`  
**Baseline Database:** `sisfour_dev_v2 (15).sql`

Dokumen ini adalah **acuan utama** SisisFour. Isinya menyatakan kontrak dan kondisi baseline yang berlaku, bukan riwayat perubahan.

---

# 1. Modul

```text
Guru
Pegawai
Siswa
Kelas
Tahun Ajaran
Mata Pelajaran
Mapping Wali Kelas
Jadwal Guru
```

# 2. Hak Akses

| Fitur | Admin | Operator | Pimpinan | BK | Guru/Wali | Siswa |
|---|---|---|---|---|---|---|
| Guru | Full | Full | Readonly | — | — | — |
| Pegawai | Full | Full | Readonly | — | — | — |
| Siswa | Full | Full | Readonly | — | Wali kelas sendiri | — |
| Edit Biodata Siswa | Full | Full | — | — | Wali kelas sendiri | — |
| Mutasi/Kenaikan/Lulus | Full | Full | — | — | — | — |
| Kelas | Full | Full | — | — | — | — |
| Tahun Ajaran | Full | Full | — | — | — | — |
| Mapel | Full | Full | — | — | — | — |
| Mapping Wali | Full | Full | View all | — | Diri | — |
| Jadwal Guru | Full | Full | View all | — | Diri | — |

# 3. Soft Delete

Soft delete:

```text
guru
pegawai
siswa
kelas
tahun_ajaran
mapping_wali_kelas
```

Hard delete:

```text
mata_pelajaran
jadwal_guru
```

# 4. Guru

NIP:

- wajib;
- unique pada tabel Guru;
- tidak boleh bentrok dengan Pegawai.

Create Guru membuat user:

```text
username = NIP
role = guru
id_guru = guru.id
status_aktif = 1
```

Foto:

```text
uploads/foto_guru/
```

Import/export tersedia.

Delete menonaktifkan user terkait. Restore memulihkan lifecycle bila valid.

# 5. Pegawai

Create Pegawai:

```text
username = NIP
role = NULL
id_pegawai = pegawai.id
status_aktif = 1
```

Role operasional kemudian ditentukan melalui Settings User Management.

# 6. Siswa

Identifier:

```text
NIK  = 16 digit numeric string, unique
NISN = unique string
```

Create:

```text
username = NISN
role = siswa
id_siswa = siswa.id
```

Status:

```text
Aktif
Lulus
Pindah
Keluar
```

Foto:

```text
uploads/foto_siswa/
```

Wali tidak boleh mengubah NISN.

# 7. Keanggotaan Kelas

Sumber:

```text
anggota_kelas
```

Constraint:

```text
UNIQUE(id_siswa,id_tahun)
```

Satu siswa hanya satu kelas per tahun.

# 8. Riwayat Siswa

`riwayat_siswa` menyimpan periode membership dan lifecycle.

Histori digunakan untuk laporan dan perpindahan kelas agar kondisi sekarang tidak merusak histori.

# 9. Kelas

Kelas terkait `id_tahun`.

Nama kelas dibentuk dari:

```text
tingkat + rombel
```

Constraint:

```text
UNIQUE(id_tahun,nama_kelas)
```

# 10. Kenaikan

Transaction per batch:

1. validasi anggota asal;
2. tutup histori lama;
3. buat histori aktif tahun tujuan;
4. pindahkan membership;
5. status tetap Aktif.

Mapping Wali dan Jadwal tidak otomatis ikut pindah.

# 11. Kelulusan

Hanya tingkat 9.

Aksi:

- tutup histori;
- status Siswa = Lulus;
- tulis tanggal/keterangan;
- nonaktifkan kartu.

# 12. Mutasi

Status:

```text
Pindah
Keluar
```

Mutasi menutup histori dan menonaktifkan kartu Aktif.

# 13. Tahun Ajaran

Format:

```text
YYYY/YYYY
Ganjil | Genap
```

Hanya satu tahun operasional aktif melalui Service.

Tahun aktif tidak boleh dihapus.

# 14. Mata Pelajaran

```text
kode_mapel
nama_mapel
```

Kode unique, uppercase, max 10. Delete ditolak bila dipakai Jadwal.

# 15. Mapping Wali

Wali bukan role.

Constraint aktif:

```text
1 Guru max 1 kelas per tahun
1 Kelas max 1 Wali per tahun
```

Soft delete = histori.

Reassign/restore harus tetap tunduk unique aktif.

# 16. Jadwal Guru

Sumber input canonical: import Excel.

Kolom bisnis:

```text
Guru
Kelas
Mapel
Tahun
Hari
Jam Mulai
Jam Selesai
Sesi
```

Sesi:

```text
Sesi Awal
Sesi Akhir
Non Sesi
```

Validasi:

- referensi;
- waktu;
- overlap Guru;
- overlap Kelas;
- import atomic.

Jadwal lama dapat menjadi Nonaktif dan tetap dipakai histori.

# 17. User Lifecycle

Soft delete Guru/Pegawai/Siswa menonaktifkan account terkait.

Identifier yang menjadi username harus disinkronkan oleh Service bila berubah secara administratif.

Password tidak otomatis direset hanya karena identifier berubah.

# 18. Checkpoint

- duplicate NIP/NISN/NIK;
- import atomic;
- recycle/restore;
- force delete dependency;
- membership unique;
- histori;
- kenaikan;
- kelulusan;
- mutasi;
- Mapping Wali;
- Jadwal bentrok;
- user lifecycle.
