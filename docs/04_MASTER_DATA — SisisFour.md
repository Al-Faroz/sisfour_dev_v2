# Master Data — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 12 September 2026
**Baseline Aplikasi:** `main` @ `39da4651acd29adcd575677d7a37c058bf32269d`
**Baseline Database:** `sisfour_dev_v2 (33).sql`

> Dokumen ini menyatakan kontrak yang berlaku pada baseline di atas. Dokumen ini **bukan changelog** dan tidak menyimpan narasi fase lama.


## 1. Modul

```text
Guru
Pegawai
Siswa
Kelas
Tahun Ajaran
Mata Pelajaran
Mapping Wali Kelas
Jadwal Guru
Manajemen Siswa
Personalia Guru/Pegawai
```

## 2. Hak Akses Ringkas

| Fitur | Admin | Operator | Pimpinan | BK | Guru/Wali | Siswa |
|---|---|---|---|---|---|---|
| Guru | Manage | Manage | View | - | - | - |
| Pegawai | Manage | Manage | View | - | - | - |
| Siswa | Manage | Manage | View | sesuai modul BK | Wali: kelas sendiri | diri melalui Profile |
| Kelas | Manage | Manage | - | - | - | - |
| Tahun Ajaran | Manage | Manage | - | - | - | - |
| Mapel | Manage | Manage | - | - | - | - |
| Mapping Wali | Manage | Manage | View all | - | Diri/context | - |
| Jadwal | Manage | Manage | View all | - | Diri | - |
| Kenaikan/Mutasi/Lulus | Manage | Manage | - | - | - | - |

Detail final mengikuti permission database dan Service.

## 3. Guru

Schema mempertahankan NIP/NIK nullable untuk legacy, tetapi Service business rule:

- NIK wajib 16 digit pada create/edit/import baru;
- NIP optional;
- NIP/NIK tidak boleh bentrok antar Guru/Pegawai;
- identifier login = NIP jika tersedia, selain itu NIK.

Akun Guru managed:

```text
role primary = guru
id_guru = guru.id
username = identifier
```

Foto:

```text
uploads/foto_guru/
```

## 4. Pegawai

Tidak ada role `pegawai`.

Akun mengikat:

```text
users.id_pegawai = pegawai.id
```

Role operasional diberikan melalui User Management.

Foto:

```text
uploads/foto_pegawai/
```

## 5. Siswa

```text
NISN unique
NIK 16 digit sesuai business validation
```

Akun:

```text
username default = NISN
role = siswa
id_siswa = siswa.id
```

Status lifecycle:

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

Wali kelas tidak boleh mengubah NISN.

## 6. Kelas dan Histori

Current membership:

```text
anggota_kelas
```

Histori:

```text
riwayat_siswa
```

Satu siswa hanya satu kelas per tahun. Histori wajib dipertahankan saat pindah/naik/mutasi.

## 7. Manajemen Siswa

```text
Penempatan/Pindah Kelas
Kenaikan Kelas
Mutasi
Kelulusan
```

### Kenaikan

- tutup histori lama;
- buat histori tahun tujuan;
- update membership;
- status tetap Aktif.

### Mutasi

```text
Pindah
Keluar
```

Menutup histori dan menonaktifkan kartu Aktif.

### Kelulusan

Menutup histori, status Lulus, dan menonaktifkan kartu Aktif.

## 8. Tahun Ajaran

```text
YYYY/YYYY
Ganjil | Genap
```

Hanya satu operasional aktif. Tahun aktif tidak boleh dihapus.

## 9. Mata Pelajaran

```text
kode_mapel
nama_mapel
```

Kode unique. Delete ditolak bila dependency Jadwal ada.

## 10. Mapping Wali

Wali bukan role.

```text
1 Guru max 1 kelas per tahun
1 Kelas max 1 Wali per tahun
```

Soft delete mempertahankan histori.

## 11. Jadwal Guru

Sumber bulk utama: import Excel.

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

Import harus resolve referensi, validasi waktu, menolak overlap Guru/Kelas, dan atomic.

## 12. Pagination

Dataset besar memakai server-side pagination.

Page-size umum:

```text
25
50
100
```

Jangan kembali ke load-all pada Siswa/Jadwal/manajemen besar tanpa alasan terukur.

## 13. User Lifecycle

Soft delete Guru/Pegawai/Siswa menonaktifkan account terkait.

Perubahan identifier managed disinkronkan ke user melalui Service. Perubahan state keamanan yang menginvalidasi token/session menaikkan `auth_version`.
