# Master Data & Student Lifecycle — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 16 September 2026
**Business baseline:** G2 CLOSED; dependency tambahan G3.2/G3.3.1 telah disinkronkan

> Dokumen ini menyatakan business contract Master Data dan Manajemen Siswa. UI detail mengikuti dokumen UI; authorization final tetap ditentukan Service + permission database.

## 1. Scope Asal G2

```text
F06 Guru
F07 Pegawai
F08 Siswa
F09 Kelas
F10 Tahun Ajaran
F11 Mata Pelajaran
F12 Mapping Wali Kelas
F13 Jadwal Guru
F14 Manajemen Siswa
```

F06–F14 telah PASS/CLOSED pada G2. G3 tidak membuka ulang business rule tersebut; dokumen ini hanya menyerap dependency lintas-modul baru yang sudah disetujui.

## 2. General Integrity Rule

- authorization diperiksa sebelum mutation;
- hard/permanent delete ditolak bila dependency/histori penting ada;
- import besar atomic bila kontrak modul menuntut;
- identifier tidak boleh bentrok lintas entitas relevan;
- history tidak dihapus hanya demi membersihkan current state;
- UI tidak boleh bypass Service;
- selector Tahun Ajaran halaman baca/histori default ke periode aktif, histori tetap selectable bila fungsi mendukung;
- workflow current-state mengikuti periode aktif sesuai Service dan tidak membutuhkan selector periode tambahan.

Dependency lintas-modul yang ditambah setelah G2 juga ikut menjaga delete/history integrity, termasuk `presensi_mengajar_siswa` (G3.2) dan `konseling_bk` (G3.3.1).

## 3. Guru

Business identifier:

```text
NIK wajib 16 digit untuk create/edit/import baru
NIP optional untuk legacy/real data
login identifier = NIP bila tersedia, selain itu NIK
```

NIP/NIK tidak boleh bentrok dengan identifier Guru/Pegawai lain sesuai Service.

Permanent delete diblok bila memiliki dependency akademik/personalia seperti Jadwal, Mapping Wali, Presensi/Jurnal input, BK/Prestasi input, atau history/dokumen personalia.

G3.3.1 menegaskan bahwa actor BK aktual dapat berupa Pegawai, sehingga akun role BK tidak harus mempunyai `users.id_guru`. `konseling_bk.id_guru_bk` nullable/metadata legacy dan FK-nya dapat `SET NULL` sesuai schema.

UI operasional menonjolkan Nama; NIP/NIK sekunder untuk search/verifikasi.

## 4. Pegawai

Tidak ada role `pegawai`.

Akun dihubungkan melalui `users.id_pegawai`, sedangkan role operasional berasal dari User Management.

Akun primary-role BK aktual menggunakan relasi Pegawai ini. Permanent delete Pegawai harus mempertimbangkan account identity dan history/dokumen personalia yang harus dipertahankan.

UI menonjolkan nama + jabatan/context; NIP/NIK sekunder.

## 5. Siswa

```text
NISN unique
NIK divalidasi sesuai Service
status: Aktif / Lulus / Pindah / Keluar
```

Akun managed:

```text
role siswa
id_siswa = siswa.id
username default = NISN
```

Permanent delete diblok bila memiliki dependency yang harus dipertahankan, termasuk:

```text
anggota_kelas
riwayat_siswa
presensi
presensi_mengajar_siswa
kartu_pelajar
catatan_kasus / Tindak Lanjut terkait
konseling_bk
catatan_prestasi
```

UI operasional menonjolkan Nama + kelas/context; NISN/NIK untuk search, pencocokan, detail dan administrasi.

Master Siswa default menampilkan Tahun Ajaran aktif dan status Aktif. Periode lain tetap dapat dipilih untuk histori sesuai scope halaman.

## 6. Kelas

Current membership berada di `anggota_kelas`, history di `riwayat_siswa`.

Permanent delete kelas ditolak bila masih direferensikan membership, Mapping Wali, Jadwal, history, Presensi Siswa, Presensi Mengajar, atau `konseling_bk`.

Master Kelas default menampilkan Tahun Ajaran aktif; histori tetap selectable untuk pembacaan lama.

## 7. Tahun Ajaran / Semester

Format:

```text
YYYY/YYYY
Ganjil | Genap
```

Hanya satu periode operasional aktif.

Master Tahun Ajaran menampilkan seluruh periode karena halaman tersebut mengelola lifecycle periode.

### Ganjil → Genap tahun yang sama

Workflow **Siapkan Genap**:

```text
1 precheck Ganjil aktif
2 buat Genap
3 copy Kelas
4 copy anggota siswa aktif
5 copy Mapping Wali aktif
6 copy Jadwal aktif sebagai baseline
7 tutup history Ganjil aktif
8 buat history Genap aktif
9 nonaktifkan Ganjil
10 aktifkan Genap
11 verifikasi state final
```

Presensi/Jurnal historis tidak disalin. Manual bypass create/update/activate Genap tahun sama saat Ganjil aktif ditolak.

### Genap → Ganjil tahun berikutnya

Gunakan Kenaikan/Kelulusan, bukan Siapkan Genap.

## 8. Mata Pelajaran

```text
kode_mapel unique
nama_mapel
```

Delete ditolak bila ada dependency Jadwal.

## 9. Mapping Wali Kelas

Wali bukan role.

```text
1 Guru maksimal 1 kelas aktif per tahun
1 Kelas maksimal 1 Wali aktif per tahun
```

Class harus berasal dari Tahun Ajaran target. Soft delete mempertahankan history.

Pergantian wali:

```text
Wali lama
→ Nonaktifkan
→ histori dipertahankan
→ Assign Wali baru
```

## 10. Jadwal Guru

Sesi:

```text
Sesi Awal
Non Sesi
Sesi Akhir
```

Import wajib resolve Guru/Kelas/Mapel/Tahun, validasi waktu, menolak overlap Guru/Kelas, menjaga topology, dan transaction/rollback.

Topology aktif per kelas/hari:

```text
minimal 2 row
row pertama = Sesi Awal
row terakhir = Sesi Akhir
row tengah = Non Sesi
```

Delete Jadwal yang sudah direferensikan Presensi Mengajar/Jurnal dilindungi; historical row dapat dipertahankan nonaktif.

## 11. Penempatan / Pindah Kelas

Hanya bekerja pada siswa/periode valid. Current membership dan history harus konsisten. Mutation mengikuti Tahun Ajaran aktif dan tidak memakai selector historis bebas.

## 12. Kenaikan Kelas

Kenaikan = promotion antar Tahun Ajaran, bukan Ganjil→Genap tahun sama.

```text
source = periode aktif semester Genap
target = semester Ganjil tahun berikutnya
kelas 7 → 8
kelas 8 → 9
kelas 9 → Kelulusan
```

Guard:

- source periode aktif valid;
- source/target tidak tahun ajaran sama;
- target tepat tahun akademik berikutnya;
- source Genap, target Ganjil;
- kelas 9 tidak diproses sebagai kenaikan biasa;
- anti-double-process;
- transaction menjaga history/membership/status.

Status regression G2: **PASS**.

## 13. Mutasi

Terminal:

```text
Pindah
Keluar
```

Transactional: validasi siswa current, tutup history aktif, catat terminal history, update status/tanggal/keterangan, lepaskan membership current bila perlu, nonaktifkan kartu Aktif, pertahankan history lama.

## 14. Kelulusan

Hanya siswa Aktif tingkat akhir yang valid. Transaction menutup history, mencatat Lulus, update status, melepas membership, menonaktifkan kartu. Account tidak otomatis dinonaktifkan tanpa policy eksplisit.

## 15. Restore Lifecycle Terminal

Restore untuk:

```text
Pindah
Keluar
Lulus
```

Sah hanya bila terminal event terbaru, status current masih sama, exact periode sumber masih aktif, source class/history valid, dan tidak membuat duplicate membership/history Aktif.

Restore transactional mempertahankan terminal history, memulihkan membership, membuka history Aktif baru, mengembalikan status Aktif, membersihkan field terminal sesuai Service, dan dapat mengaktifkan kembali kartu jika memenuhi kontrak.

Status regression G2: **PASS**.

## 16. Pagination & Export

Dataset besar memakai bounded query/pagination. Identifier NIP/NISN/kode diperlakukan sebagai text bila diperlukan agar spreadsheet tidak kehilangan digit.

## 17. Default Tahun Ajaran — Surface Contract

```text
Master Siswa          → default periode aktif
Master Kelas          → default periode aktif
Mapping Wali          → default periode aktif
Assign Wali           → default periode aktif
Master Jadwal Guru    → default periode aktif
Import Jadwal Guru    → default periode aktif
Laporan Jurnal        → default periode aktif
Matrix Presensi       → default periode aktif
Export Presensi       → default periode aktif
```

Workflow current-state memakai periode aktif langsung:

```text
Penempatan/Pindah
Mutasi
Kelulusan
Kenaikan
Presensi operasional
Jurnal operasional
Kartu Pelajar operasional
Konseling Tahap 1 (kelas/siswa Tahun Ajaran aktif)
```

Tidak perlu helper/alert yang hanya menjelaskan default periode aktif.

## 18. Cross-domain Integrity Setelah G3.3.1

Konseling Tahap 1 selalu memvalidasi:

```text
kelas ∈ Tahun Ajaran aktif
siswa ∈ anggota_kelas target
siswa.status_aktif = Aktif
```

Ini menggunakan current membership yang sudah menjadi kontrak Master Data; client selector tidak dapat menembus membership tersebut.

## 19. Gate

```text
G2 F06–F14 regression          PASS / CLOSED
G3.2 Jurnal child dependency   PASS / MERGED
G3.3.1 Konseling dependency    PASS local + hosting
```

Perubahan dependency G3 tidak mengubah lifecycle G2; ia hanya menambah relasi histori yang harus dipertahankan.