# Master Data & Student Lifecycle — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 15 September 2026
**Development Stage:** G2

> Dokumen ini menyatakan business contract Master Data dan Manajemen Siswa. UI detail mengikuti dokumen UI; authorization final tetap ditentukan Service + permission database.

## 1. Scope G2

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

## 2. General Integrity Rule

- authorization diperiksa sebelum mutation;
- hard/permanent delete ditolak bila dependency/histori penting ada;
- import besar atomic bila kontrak modul menuntut;
- identifier tidak boleh bentrok lintas entitas yang relevan;
- history tidak dihapus hanya demi membersihkan current state;
- perubahan UI tidak boleh bypass Service;
- selector Tahun Ajaran pada halaman baca/histori default ke periode aktif, tetapi histori tetap dapat dipilih bila fungsi halaman memang mendukung histori;
- workflow current-state seperti Penempatan/Pindah, Mutasi, Kelulusan, Kenaikan, input Presensi/Jurnal dan Kartu operasional mengikuti periode aktif sesuai Service dan tidak membutuhkan selector periode tambahan.

## 3. Guru

Business identifier:

```text
NIK wajib 16 digit untuk create/edit/import baru
NIP optional untuk legacy/real data
login identifier = NIP bila tersedia, selain itu NIK
```

NIP/NIK tidak boleh bentrok dengan identifier Guru/Pegawai lain sesuai Service.

Permanent delete diblok bila memiliki dependency akademik/personalia seperti Jadwal, Mapping Wali, Presensi/Jurnal input, BK/Prestasi input, atau history/dokumen personalia.

UI operasional menonjolkan **nama lengkap**; NIP/NIK menjadi identifier search/verifikasi.

## 4. Pegawai

Tidak ada role `pegawai`.

Akun dihubungkan melalui `users.id_pegawai`, sedangkan role operasional berasal dari User Management.

Permanent delete diblok bila masih mempunyai history/dokumen personalia yang harus dipertahankan.

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

Permanent delete diblok bila memiliki anggota kelas, riwayat, Presensi, kartu, kasus, atau prestasi.

UI operasional menonjolkan nama + kelas/context; NISN/NIK dipakai untuk search, pencocokan, detail dan administrasi.

Master Siswa default menampilkan Tahun Ajaran aktif dan status siswa Aktif. Pemilihan periode lain tetap diperbolehkan untuk kebutuhan histori sesuai scope halaman.

## 6. Kelas

Current membership berada di `anggota_kelas` dan history di `riwayat_siswa`.

Permanent delete kelas ditolak bila masih direferensikan oleh membership, Mapping Wali, Jadwal, history, Presensi Siswa, atau Presensi Mengajar.

Master Kelas default menampilkan Tahun Ajaran aktif; periode historis tetap dapat dipilih untuk pembacaan data lama.

## 7. Tahun Ajaran / Semester

Format:

```text
YYYY/YYYY
Ganjil | Genap
```

Hanya satu periode operasional aktif.

Master Tahun Ajaran tetap menampilkan seluruh periode karena halaman tersebut adalah surface pengelolaan lifecycle periode, bukan filter operasional biasa.

### Ganjil → Genap tahun yang sama

Gunakan workflow **Siapkan Genap**.

Atomic steps:

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

Presensi/Jurnal **tidak disalin**.

Manual bypass create/update/activate Genap tahun yang sama saat Ganjil aktif ditolak; gunakan workflow Siapkan Genap.

### Genap → Ganjil tahun berikutnya

Gunakan Kenaikan Kelas/Kelulusan, bukan Siapkan Genap.

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

Class harus berasal dari Tahun Ajaran target. Soft delete mempertahankan history. Permanent delete mapping historis dilindungi.

Surface Mapping Wali default ke Tahun Ajaran aktif. Assign Wali juga membuka periode aktif sebagai pilihan awal, sementara histori tetap dapat ditinjau melalui filter/recycle sesuai permission.

Workflow pergantian wali canonical:

```text
Wali lama
→ Nonaktifkan
→ histori dipertahankan
→ Assign Wali baru
```

Tidak diperlukan mutation khusus yang menghapus histori lama.

## 10. Jadwal Guru

Sesi:

```text
Sesi Awal
Non Sesi
Sesi Akhir
```

Import wajib:

- resolve Guru/Kelas/Mapel/Tahun;
- validasi waktu;
- menolak overlap Guru;
- menolak overlap Kelas;
- menjaga topology per kelas/hari;
- transaction/rollback sesuai Service.

Topology aktif per kelas/hari:

```text
minimal 2 row
row pertama = Sesi Awal
row terakhir = Sesi Akhir
row tengah = Non Sesi
```

Delete Jadwal yang sudah direferensikan Jurnal/Presensi Mengajar dilindungi; historical row dapat dipertahankan nonaktif sesuai lifecycle.

Master Jadwal Guru dan import Jadwal default ke Tahun Ajaran aktif. Filter historis tetap tersedia untuk kebutuhan baca/export bila scope mengizinkan.

## 11. Penempatan / Pindah Kelas

Hanya bekerja pada siswa/periode yang valid sesuai Service. Current membership dan history harus tetap konsisten.

Workflow ini mengikuti Tahun Ajaran aktif dan tidak menggunakan selector periode historis untuk mutation current-state.

## 12. Kenaikan Kelas

Kenaikan adalah promotion antar Tahun Ajaran, bukan transisi Ganjil→Genap tahun yang sama.

Kontrak promotion normal:

```text
source = periode aktif semester Genap
target = semester Ganjil tahun ajaran berikutnya
kelas 7 → 8
kelas 8 → 9
```

Guard wajib:

- source harus periode aktif yang valid;
- source dan target tidak boleh tahun ajaran yang sama;
- target harus tepat tahun akademik berikutnya;
- semester source harus Genap dan target harus Ganjil;
- level 9 tidak boleh diproses sebagai kenaikan biasa dan harus melalui Kelulusan;
- membership/history tidak boleh menjadi duplikat akibat double process;
- transaction menjaga source history tertutup, target history Aktif terbuka, membership target terbentuk, dan status siswa tetap Aktif.

Status regression G2 per 15 September 2026: **PASS**, termasuk happy-path kenaikan, guard, partial promotion, anti-double-process, membership integrity, history integrity, dan progress per kelas.

## 13. Mutasi

Terminal state:

```text
Pindah
Keluar
```

Workflow transactional:

- hanya siswa Aktif/current yang valid;
- tutup history aktif;
- buat/update terminal history sesuai kontrak;
- update status/tanggal/keterangan;
- lepaskan current membership periode aktif bila perlu;
- nonaktifkan kartu Aktif;
- history periode lama tetap dipertahankan.

## 14. Kelulusan

Hanya siswa Aktif tingkat akhir yang memenuhi context periode.

Workflow transactional:

- tutup history aktif;
- catat terminal Lulus;
- update status/tanggal/keterangan;
- lepaskan current membership periode aktif;
- nonaktifkan kartu Aktif;
- akun tidak otomatis dimatikan kecuali policy alumni/login diputuskan eksplisit.

## 15. Restore Lifecycle Terminal

Restore tersedia untuk terminal event:

```text
Pindah
Keluar
Lulus
```

Restore hanya sah bila:

- terminal event tersebut adalah lifecycle terminal terbaru siswa;
- status siswa saat ini masih sama dengan terminal event yang akan direstore;
- row `tahun_ajaran` exact milik terminal history tersebut masih merupakan periode yang aktif saat ini;
- source class/history masih valid;
- tidak ada membership aktif atau history Aktif terbuka yang akan menimbulkan duplikasi.

Restore transactional:

```text
terminal history lama tetap dipertahankan
→ membership ke kelas terakhir dipulihkan
→ history Aktif baru dibuka
→ status siswa kembali Aktif
→ field mutasi terminal dibersihkan sesuai Service
→ kartu terakhir dapat diaktifkan kembali bila memenuhi kontrak
```

Jika periode sumber terminal sudah nonaktif, restore ditolak. Restore bukan penghapusan histori terminal.

Status regression G2: **PASS**.

## 16. Pagination & Export

Dataset besar memakai bounded query/pagination. UI Admin mengikuti paginator canonical.

Export identifier seperti NIP/NISN/kode diperlakukan sebagai text bila diperlukan agar spreadsheet tidak kehilangan digit/format.

## 17. Default Tahun Ajaran — Surface Contract

Untuk menjaga tampilan operasional konsisten dan tetap mendukung histori:

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

Tidak perlu menampilkan helper/alert seperti “Default menampilkan Tahun Ajaran yang sedang aktif.” Pemilihan default harus terlihat langsung dari control yang terpilih.

Workflow current-state memakai periode aktif langsung dari business context dan tidak perlu selector tambahan:

```text
Penempatan/Pindah
Mutasi
Kelulusan
Kenaikan
Presensi operasional
Jurnal operasional
Kartu Pelajar operasional
```

## 18. G2 Gate

Sebelum G2 closed:

```text
repository hygiene
static lint
F06–F14 regression
UI smoke untuk halaman yang disentuh
canonical docs sync
PR review
explicit approval
```

F14 Manajemen Siswa telah dinyatakan **PASS**. Ini tidak otomatis berarti seluruh G2 closed; browser regression, final docs/checklist, PR review, dan approval merge tetap mengikuti gate phase.

Redesign mobile role bukan bagian scope dokumen implementasi G2; targetnya G3.
