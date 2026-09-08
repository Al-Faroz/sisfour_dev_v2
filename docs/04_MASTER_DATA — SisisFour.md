# 📋 Master Data — SisisFour

**Versi Acuan: v0.5**  
**Tanggal:** 08 September 2026

Dokumen ini menetapkan seluruh aturan bisnis dan integrasi Master Data SisisFour.

Master Data terdiri dari:

1. Guru;
2. Pegawai;
3. Siswa;
4. Kelas;
5. Tahun Ajaran;
6. Mata Pelajaran;
7. Mapping Wali Kelas;
8. Jadwal Guru.

---

# 1. Hak Akses

| Fitur | Admin | Operator | Pimpinan | BK | Guru | Wali | Siswa |
|---|---|---|---|---|---|---|---|
| Guru | Full | Full | Readonly | — | — | — | — |
| Pegawai | Full | Full | Readonly | — | — | — | — |
| Siswa | Full | Full | Readonly semua | — | — | Kelas Wali | Diri readonly |
| Edit Biodata Siswa | Full | Full | — | — | — | Kelas Wali | — |
| Ubah NISN | Full | Full | — | — | — | Tidak | — |
| Mutasi | Full | Full | — | — | — | — | — |
| Kenaikan | Full | Full | — | — | — | — | — |
| Kelulusan | Full | Full | — | — | — | — | — |
| Kelas | Full | Full | — | — | — | — | — |
| Tahun Ajaran | Full | Full | — | — | — | — | — |
| Mata Pelajaran | Full | Full | — | — | — | — | — |
| Mapping Wali | Full | Full | View all | — | Diri | Diri | — |
| Jadwal Guru | Full | Full | View all | — | Diri | Diri | — |
| Import/Export | Full | Full | — | — | — | — | — |

Wali adalah konteks Guru, bukan role.

---

# 2. Soft Delete

Soft delete:

```text
guru
pegawai
siswa
kelas
tahun_ajaran
mapping_wali_kelas
```

Recycle Bin hanya untuk Admin/Operator.

Hard delete:

```text
mata_pelajaran
jadwal_guru
```

Force delete soft-delete entity harus mempertimbangkan FK dan dependency.

---

# 3. Guru

## 3.1 Field

- NIP;
- nama;
- jenis kelamin;
- tempat lahir;
- tanggal lahir;
- alamat;
- nomor telepon;
- email;
- status kepegawaian;
- foto.

## 3.2 NIP

NIP:
- wajib;
- unik pada Guru;
- tidak boleh sama dengan Pegawai.

Validasi lintas tabel dilakukan Service.

## 3.3 Auto User

Create Guru:

```text
username = NIP
password = hash(NIP)
role = guru
id_guru = guru.id
status_aktif = 1
```

User harus dimasukkan ke struktur role sesuai AuthService.

## 3.4 Foto

- PNG;
- max 2 MB;
- rasio 3:4;
- crop;
- re-encode;
- simpan `uploads/foto_guru/`.

## 3.5 Import

Template minimum:

```text
NIP
NAMA LENGKAP & GELAR
JENIS KELAMIN (L/P)
```

Import wajib:
- exact header;
- validasi NIP;
- validasi lintas Pegawai;
- stop-on-error;
- atomic transaction;
- auto-create user untuk seluruh row valid.

## 3.6 Export

Export mengikuti filter aktif dan hanya data yang diizinkan scope.

## 3.7 Delete

Soft delete Guru:
- tandai `deleted_at`;
- user terkait dinonaktifkan;
- dependency FK dapat mencegah force delete.

Restore:
- `deleted_at = NULL`;
- user terkait aktif kembali bila valid.

Force delete:
- hanya dari Recycle Bin;
- ditolak bila FK masih mereferensikan Guru.

---

# 4. Pegawai

## 4.1 Field

- NIP;
- nama;
- jenis kelamin;
- tempat/tanggal lahir;
- alamat;
- telepon;
- email;
- jabatan.

## 4.2 Auto User

Create Pegawai:

```text
username = NIP
password = hash(NIP)
role = NULL
id_pegawai = pegawai.id
status_aktif = 1
```

Admin dapat kemudian menentukan role.

## 4.3 NIP

Tidak boleh sama dengan Guru.

## 4.4 Import

Template:

```text
NIP
NAMA LENGKAP
JENIS KELAMIN (L/P)
JABATAN
```

Import:
- exact header;
- stop-on-error;
- atomic;
- duplicate lintas Guru/Pegawai ditolak.

## 4.5 Delete/Restore

Soft delete Pegawai menonaktifkan user terkait.

Restore mengaktifkan kembali user bila relasi masih valid.

---

# 5. Siswa

## 5.1 Identifier

NIK:
- tepat 16 digit;
- numeric string;
- unique.

NISN:
- unique;
- basis username.

## 5.2 Field Biodata

Field utama:
- NIK;
- NISN;
- nama;
- jenis kelamin;
- tempat/tanggal lahir;
- alamat;
- telepon;
- kebutuhan khusus;
- disabilitas;
- KIP/PIP;
- nama ayah;
- nama ibu;
- nama wali;
- foto;
- status aktif;
- data mutasi.

## 5.3 User

Create Siswa:

```text
username = NISN
password = hash(NISN)
role = siswa
id_siswa = siswa.id
```

Jika Admin mengganti NISN:
- username ikut disinkronkan;
- password tidak otomatis di-reset;
- `auth_version` dapat dinaikkan agar sesi lama invalid.

Wali tidak boleh mengganti NISN meskipun mengirim request secara manual.

## 5.4 Biodata Wali

Wali boleh mengedit:
- nama;
- jenis kelamin;
- tempat/tanggal lahir;
- alamat;
- field biodata terkait;
- foto.

Hanya untuk siswa kelas Wali.

Wali tidak boleh:
- mengubah NISN;
- melakukan mutasi;
- kelulusan;
- kenaikan kelas;
- import;
- export Master Siswa.

## 5.5 Foto

```text
PNG
max 2 MB
crop 3:4
re-encode
uploads/foto_siswa/
```

## 5.6 Status

```text
Aktif
Lulus
Pindah
Keluar
```

Pindah/Keluar/Lulus:
- `tanggal_mutasi` wajib;
- `keterangan_mutasi` diisi sesuai aksi;
- histori aktif ditutup;
- histori final dicatat;
- kartu pelajar menjadi Nonaktif.

## 5.7 Import

Template:

```text
NIK
NISN
NAMA LENGKAP
JENIS KELAMIN
TEMPAT LAHIR
TANGGAL LAHIR
ALAMAT
```

Header harus sama.

Import atomic.

## 5.8 Filter

- Nama;
- NIK;
- NISN;
- Kelas;
- Status Aktif.

Export mengikuti filter dan scope.

## 5.9 Delete

Soft delete:
- siswa tidak tampil di Master aktif;
- user dinonaktifkan.

Restore:
- siswa aktif kembali sebagai record;
- user terkait diaktifkan/dibuat kembali sesuai kondisi.

Force delete:
- harus tunduk FK;
- histori/presensi/kartu dapat menyebabkan penolakan.

---

# 6. Keanggotaan Kelas

Penempatan siswa ke kelas tidak dilakukan di form Master Siswa.

Sumber hubungan:

```text
anggota_kelas
```

Aturan:

```text
UNIQUE(id_siswa, id_tahun)
```

Satu siswa hanya satu kelas pada tahun yang sama.

Saat ditambahkan ke kelas:
- insert `anggota_kelas`;
- buat `riwayat_siswa` Aktif bila belum ada;
- jangan membuat histori aktif ganda.

Saat koreksi keluar dari kelas:
- hapus relasi `anggota_kelas` sesuai Service;
- tutup histori aktif tahun tersebut;
- status siswa dapat tetap Aktif bila hanya koreksi administrasi.

---

# 7. Kelas

## 7.1 Struktur

```text
tingkat: 7 / 8 / 9
rombel : A / B / C / ...
nama_kelas = tingkat-rombel
```

Contoh:

```text
7 + A = 7-A
```

`nama_kelas` tidak diinput bebas.

## 7.2 Tahun

Setiap Kelas terkait satu `id_tahun`.

Kelas pada tahun berbeda adalah entity berbeda.

## 7.3 Filter

- Tingkat;
- Tahun Ajaran.

## 7.4 Anggota

Master Kelas menyediakan pengelolaan anggota.

Candidate siswa:
- status Aktif;
- belum punya kelas di tahun tersebut;
- atau sudah menjadi anggota kelas yang sedang dibuka.

## 7.5 Delete

Kelas tidak dapat dihapus bila masih digunakan secara aktif oleh dependency penting seperti:
- anggota;
- wali aktif;
- jadwal aktif.

Soft delete digunakan untuk Kelas.

---

# 8. Kenaikan Kelas

Alur:

```text
Pilih kelas asal
↓
Ambil seluruh siswa Aktif
↓
Checklist default semua
↓
Admin uncheck pengecualian
↓
Pilih kelas tujuan
↓
Pilih tahun tujuan
↓
Transaction
```

Per siswa terpilih:

1. validasi anggota kelas asal;
2. validasi status Aktif;
3. tutup riwayat aktif lama;
4. buat riwayat Aktif tahun baru;
5. tambah/update anggota kelas tujuan;
6. status tetap Aktif;
7. mutation fields dibersihkan bila relevan.

Wali dan Jadwal tidak ikut pindah.

Kelas tujuan harus sudah dibuat sebelumnya.

---

# 9. Kelulusan

Kelulusan hanya untuk kelas tingkat 9.

Per siswa:
- validasi anggota;
- tutup histori aktif;
- insert histori `Lulus`;
- `status_aktif = Lulus`;
- isi tanggal/keterangan;
- kartu Nonaktif.

Semua dalam transaction.

Siswa yang tidak dicentang tidak diproses.

---

# 10. Mutasi Siswa

Status mutasi:

```text
Pindah
Keluar
```

Alur:
- validasi siswa Aktif;
- temukan konteks kelas/tahun;
- tutup histori Aktif;
- tulis histori final;
- ubah status siswa;
- isi tanggal/keterangan;
- nonaktifkan kartu.

---

# 11. Tahun Ajaran

## 11.1 Format

```text
YYYY/YYYY
```

Contoh:

```text
2026/2027
```

Tahun kedua harus +1 dari tahun pertama.

Semester:

```text
Ganjil
Genap
```

## 11.2 Status Aktif

Hanya satu record boleh aktif.

Create baru default:

```text
Nonaktif
```

Aktivasi:

```text
BEGIN
nonaktifkan seluruh record aktif lain
aktifkan target
COMMIT
```

## 11.3 Delete

Record aktif tidak dapat dihapus.

Record yang masih direferensikan data operasional tidak dapat dihapus.

Restore selalu kembali Nonaktif.

## 11.4 Dependency

Dependency dapat mencakup:
- kelas;
- anggota kelas;
- mapping wali;
- jadwal;
- histori siswa;
- presensi;
- jurnal.

---

# 12. Mata Pelajaran

Field:

```text
nama_mapel
kode_mapel
```

Kode:
- uppercase;
- unique;
- maksimal 10;
- huruf/angka/underscore/minus.

Tidak menggunakan soft delete.

Delete ditolak bila sudah dipakai Jadwal Guru.

---

# 13. Mapping Wali Kelas

## 13.1 Aturan

```text
1 Guru maksimal 1 Wali aktif per tahun
1 Kelas maksimal 1 Wali aktif per tahun
```

Database menggunakan generated unique columns agar histori soft-delete tidak mengunci assign berikutnya.

## 13.2 Assign

Dropdown Tahun dipilih terlebih dahulu.

Guru yang ditampilkan:
- Guru aktif;
- belum menjadi Wali aktif pada tahun tersebut.

Kelas yang ditampilkan:
- kelas tahun tersebut;
- belum mempunyai Wali aktif.

## 13.3 Reassign

Jika Guru pernah memiliki row mapping pada tahun sama tetapi sudah soft-deleted:

```text
RESTORE row lama
+
update id_kelas
```

Bukan INSERT row baru.

## 13.4 Nonaktifkan

```text
deleted_at = timestamp
```

Row tetap menjadi histori.

## 13.5 Restore

Restore langsung hanya bila:
- Guru belum Wali aktif;
- kelas historis belum punya Wali aktif;
- Guru/Kelas/Tahun masih valid.

## 13.6 Scope

Admin/Operator:
```text
SEMUA
```

Pimpinan:
```text
view all readonly
```

Guru:
```text
DIRI_SENDIRI
```

---

# 14. Jadwal Guru

## 14.1 Input

Tidak ada form create/edit manual.

Sumber input:

```text
Import Excel
```

Template:

```text
NIP_GURU
NAMA_KELAS
KODE_MAPEL
HARI
JAM_MULAI
JAM_SELESAI
SESI
```

## 14.2 Hari

```text
Senin
Selasa
Rabu
Kamis
Jumat
Sabtu
Minggu
```

## 14.3 Sesi

```text
Sesi Awal
Sesi Akhir
Non Sesi
```

Sesi adalah label administratif. Jam aktual tetap `jam_mulai/jam_selesai`.

## 14.4 Validasi Referensi

Setiap row harus resolve:

```text
NIP → Guru
Nama Kelas + Tahun → Kelas
Kode Mapel → Mata Pelajaran
```

## 14.5 Validasi Waktu

```text
jam_mulai < jam_selesai
```

## 14.6 Bentrok

Overlap bila:

```text
mulaiA < selesaiB
AND
mulaiB < selesaiA
```

Ditolak bila:
- Guru sama + hari sama + overlap;
- Kelas sama + hari sama + overlap.

Tidak ada team teaching.

## 14.7 Atomic Import

Semua row divalidasi lebih dulu.

Satu error:

```text
seluruh import batal
```

## 14.8 Replacement Set

Import ke Tahun Ajaran aktif:

```text
jadwal Aktif lama → Nonaktif
hasil import → Aktif
```

Jadwal lama tidak dihapus.

Tujuannya mempertahankan histori dan referensi Presensi/Jurnal lama.

## 14.9 Filter

- Guru;
- Kelas;
- Tahun Ajaran;
- Hari;
- Status.

Dropdown kelas dapat dipersempit berdasarkan Guru.

## 14.10 Export

Admin/Operator.

Export mengikuti seluruh filter aktif.

Kolom minimal:
- NIP;
- Guru;
- Kelas;
- Kode Mapel;
- Mapel;
- Hari;
- Jam;
- Sesi;
- Status;
- Tahun;
- Semester.

---

# 15. Integrasi Master Data

## 15.1 Siswa → Kelas

Presensi dan laporan tidak boleh menebak kelas dari siswa. Gunakan `anggota_kelas` per `id_tahun`.

## 15.2 Wali → Kelas

Gunakan mapping aktif.

## 15.3 Guru → Kelas Terjadwal

Gunakan `jadwal_guru` aktif.

## 15.4 Tahun Aktif

Semua operasi operasional harus menggunakan Tahun Ajaran aktif atau `id_tahun` eksplisit.

## 15.5 Mata Pelajaran → Jadwal

Mapel yang sudah digunakan tidak boleh dihapus karena akan memutus referensi Jadwal.

---

# 16. Import dan Export Umum

Aturan:
- file XLSX/XLS sesuai modul;
- header exact;
- identifier dibaca sebagai string;
- tidak ada partial success;
- transaction;
- error menyebut baris;
- export mengikuti filter/scope.

---

# 17. CSRF

Semua mutation Fetch Web dilindungi global CSRF.

Module JS tidak perlu membuat mekanisme token sendiri selama wrapper global aktif.

---

# 18. Log Activity

Aksi penting:
- create/update/delete/restore;
- mutasi;
- kenaikan;
- kelulusan;
- aktivasi Tahun;
- mapping;
- import Jadwal;

dicatat ke `log_activity`.

---

# 19. Error Handling

Business error harus jelas.

Contoh:
- duplicate NIP;
- duplicate NIK;
- duplicate NISN;
- siswa sudah punya kelas;
- Wali duplicate;
- kelas sudah punya Wali;
- Tahun aktif tidak boleh dihapus;
- mapel dipakai Jadwal;
- Jadwal overlap.

---

# 20. Checkpoint Master Data

Master Data lulus bila:
- Guru CRUD;
- Pegawai CRUD;
- Siswa CRUD;
- Kelas CRUD;
- Tahun CRUD;
- Mapel CRUD;
- Wali mapping;
- Jadwal import;
- semua recycle;
- NIP lintas tabel;
- NIK 16 digit;
- role Pegawai NULL;
- NISN Wali immutable;
- satu siswa satu kelas/tahun;
- histori siswa konsisten;
- satu Tahun aktif;
- Mapping Wali unique aktif;
- Jadwal tidak overlap;
- import rollback;
- RBAC semua role benar;
- CSRF semua mutation berhasil.
