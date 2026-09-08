# 🧪 Testing & Polish — SisisFour

**Versi Acuan: v0.5**  
**Tanggal:** 08 September 2026

Dokumen ini menetapkan strategi testing, checkpoint, integrasi, regression test, fresh install test, security test, dan kriteria rilis SisisFour.

---

# 1. Prinsip Testing

Testing dilakukan pada empat lapis:

1. syntax/static check;
2. manual module test;
3. integration test;
4. automated test untuk logic kritis.

Tidak boleh melanjutkan modul berikut bila blocker pada checkpoint sebelumnya belum selesai.

---

# 2. Static Check

## 2.1 PHP

Semua file PHP yang dibuat/diubah:

```powershell
php -l path\file.php
```

Expected:

```text
No syntax errors detected
```

## 2.2 JavaScript

```powershell
node --check assets/js/...
```

Expected tidak ada syntax error.

---

# 3. Browser Test

Untuk setiap modul:

- Chrome/Edge desktop;
- hard reload setelah asset berubah;
- console tidak memiliki uncaught error;
- Network tab tidak mempunyai 403/404/500 yang tidak diharapkan;
- modal dapat dibuka/ditutup;
- button disabled/loading state benar;
- error dari server tampil jelas;
- success state tidak menggandakan submit.

---

# 4. CSRF Test

CSRF aktif.

Uji seluruh mutation:

```text
POST
PUT
PATCH
DELETE
```

Expected:
- Fetch otomatis membawa `X-CSRF-TOKEN`;
- mutation sah berhasil;
- request tanpa token ditolak;
- GET tidak membutuhkan token;
- API JWT tidak bergantung CSRF Web.

Smoke test minimal:
- update Guru;
- delete/restore Guru;
- update Pegawai;
- delete/restore Pegawai;
- update Siswa;
- upload foto Siswa;
- mutasi Siswa;
- delete/restore Siswa;
- add/remove anggota Kelas;
- kenaikan;
- kelulusan;
- aktivasi Tahun;
- update Mapel;
- assign/nonaktif/restore Wali;
- import Jadwal;
- delete Jadwal.

---

# 5. Auth Test

## 5.1 Login

- username benar/password benar;
- username benar/password salah;
- username tidak ada;
- user tidak aktif;
- user role NULL;
- user multi-role;
- Admin bootstrap tanpa relasi Guru/Pegawai.

## 5.2 Rate Limit

5 kali gagal:

```text
lock 5 menit
```

Setelah periode lock berakhir, login valid dapat dilakukan.

## 5.3 Auth Version

Login baru:
- `auth_version` naik;
- sesi lama invalid.

## 5.4 Logout

- session dihancurkan;
- halaman privat tidak dapat dibuka melalui Back/refresh tanpa auth valid.

---

# 6. RBAC Test Matrix

Minimal akun:

```text
Admin
Operator
Pimpinan
BK
Guru biasa
Guru Wali
Siswa
```

## 6.1 Admin

Expected:
- full sesuai permission;
- tidak bergantung `id_guru`;
- Data Siswa tampil;
- menu contextual tidak salah menyaring Admin;
- route management dapat diakses.

## 6.2 Operator

Expected:
- full administratif sesuai permission;
- menu yang relevan tampil;
- mutation berhasil.

## 6.3 Pimpinan

Expected:
- readonly pada area operasional;
- tidak ada tombol mutation bila permission tidak ada;
- direct mutation route harus 403;
- Mapping Wali dan Jadwal dapat dilihat semua bila `view_all`.

## 6.4 Guru Biasa

Expected:
- Data Siswa tidak tampil;
- Mapping Wali diri;
- Jadwal diri;
- tidak memperoleh kelas Wali;
- direct route Master Siswa ditolak bila bukan Wali.

## 6.5 Guru Wali

Expected:
- Data Siswa tampil;
- hanya kelas Wali;
- NISN immutable;
- akses Wali hilang jika mapping dinonaktifkan;
- saat mengakses kelas lain, berlaku sebagai Guru biasa.

## 6.6 Siswa

Expected:
- tidak ada menu administrasi;
- hanya data diri;
- tidak dapat mengubah scope melalui parameter URL.

---

# 7. Master Guru

Uji:

- create;
- update;
- NIP duplicate;
- NIP sama Pegawai;
- upload foto;
- file bukan PNG;
- file >2MB;
- import valid;
- import invalid;
- rollback import;
- export;
- delete;
- restore;
- force delete;
- lifecycle user;
- duplicate account prevention.

---

# 8. Master Pegawai

Uji:

- create;
- `users.role = NULL`;
- role dapat diberikan kemudian;
- NIP lintas Guru;
- import atomic;
- update;
- delete;
- restore;
- force delete;
- lifecycle user.

---

# 9. Master Siswa

Uji:

- NIK tepat 16 digit;
- NIK kurang/lebih;
- NIK non-numeric;
- NIK duplicate;
- NISN duplicate;
- create auto user;
- update Admin;
- ganti NISN Admin dan sinkron username;
- password tidak reset otomatis saat ganti NISN;
- update Wali;
- malicious Wali NISN request;
- Wali mengakses siswa kelas lain;
- photo valid/invalid;
- filter;
- export mengikuti filter;
- import atomic;
- mutasi Pindah;
- mutasi Keluar;
- kartu Nonaktif;
- histori;
- delete;
- restore;
- force delete dependency.

---

# 10. Master Kelas

Uji:

- nama kelas auto;
- duplicate tahun/nama;
- tambah anggota;
- duplicate membership;
- siswa sudah di kelas lain pada tahun sama;
- remove anggota;
- kenaikan;
- partial checklist;
- target tahun salah;
- target kelas tidak ada;
- histori lama ditutup;
- histori baru dibuat;
- kelulusan kelas 9;
- larangan kelulusan kelas 7/8;
- kartu Lulus Nonaktif;
- dependency delete;
- recycle;
- restore.

---

# 11. Tahun Ajaran

Uji:

- format `YYYY/YYYY`;
- format salah;
- tahun kedua bukan +1;
- semester;
- create Nonaktif;
- aktivasi pertama;
- aktivasi kedua;
- hanya satu aktif;
- active delete ditolak;
- dependency delete ditolak;
- restore Nonaktif;
- duplicate tahun/semester.

---

# 12. Mata Pelajaran

Uji:
- kode uppercase;
- unique;
- karakter invalid;
- edit;
- hard delete belum dipakai;
- delete sudah dipakai Jadwal ditolak.

---

# 13. Mapping Wali

## 13.1 Constraint Aktif

- Guru A → 7-A berhasil;
- Guru A → 7-B tahun sama ditolak;
- Guru B → 7-A tahun sama ditolak.

## 13.2 Soft Delete

Nonaktifkan Guru A/7-A.

Expected:

```text
deleted_at terisi
```

## 13.3 Reassign

Assign Guru A → 7-B tahun sama.

Expected:
- row lama direstore;
- `id_kelas` menjadi 7-B;
- tidak membuat duplicate row aktif.

## 13.4 Restore Langsung

Restore histori:
- berhasil bila Guru dan kelas historis kosong;
- gagal bila Guru sudah Wali aktif;
- gagal bila kelas historis sudah punya Wali.

## 13.5 Scope

Guru hanya melihat dirinya.

Pimpinan melihat semua readonly.

Admin/Operator full.

---

# 14. Jadwal Guru

## 14.1 Template

Header exact:

```text
NIP_GURU
NAMA_KELAS
KODE_MAPEL
HARI
JAM_MULAI
JAM_SELESAI
SESI
```

## 14.2 Reference Error

- NIP tidak ada;
- Kelas tidak ada;
- Mapel tidak ada;
- Tahun nonaktif.

Semua harus membatalkan import.

## 14.3 Time Error

- jam mulai kosong;
- jam selesai kosong;
- jam mulai >= jam selesai;
- sesi tidak valid;
- hari tidak valid.

## 14.4 Overlap

Case:
1. Guru sama, overlap → reject.
2. Guru sama, tidak overlap → accept.
3. Kelas sama, overlap → reject.
4. Kelas sama, tidak overlap → accept.
5. Guru/kelas beda, overlap → accept.

## 14.5 Replacement

Import set A.

Import set B.

Expected:
- A menjadi Nonaktif;
- B Aktif;
- A tidak dihapus.

## 14.6 Scope

- Admin semua;
- Operator semua;
- Pimpinan semua readonly;
- Guru diri.

---

# 15. Fresh Install Database Test

Fresh install dilakukan di database kosong.

Urutan:

1. create database;
2. jalankan struktur dari `02_DATABASE`;
3. seed RBAC/menu berdasarkan `03_AUTH_RBAC_MENU`;
4. buat password hash Admin bootstrap;
5. login bootstrap;
6. buat Tahun;
7. buat Guru;
8. buat Siswa;
9. buat Kelas;
10. mapping;
11. import Jadwal.

Checklist:
- permission = 43;
- `users.role` nullable;
- generated column Mapping ada;
- unique mapping aktif bekerja;
- FK aktif;
- no schema drift;
- seluruh Model dapat query tanpa unknown column.

---

# 16. Presensi Siswa Test

Setelah modul dibuat:

## 16.1 Guru

- sesuai jadwal;
- kelas bukan jadwal;
- jadwal Nonaktif;
- Non Sesi;
- time-window;
- geofence;
- GPS ditolak;
- di luar radius;
- tidak bisa melihat saved;
- tidak bisa revise.

## 16.2 Wali

- kelas Wali;
- kelas non-Wali;
- bebas time-window;
- bebas geofence untuk kelas Wali;
- bisa melihat saved;
- bisa revise.

## 16.3 Admin/Operator

- semua kelas;
- input;
- revise;
- tanpa geofence;
- tanpa time-window administratif.

## 16.4 Pimpinan

- view;
- input ditolak;
- revise ditolak.

## 16.5 Bulk

- seluruh siswa valid;
- satu siswa asing;
- siswa deleted;
- siswa status nonaktif;
- duplicate request;
- rollback total.

## 16.6 Sesi

- Awal dihitung;
- Akhir tersimpan tetapi tidak dihitung;
- Non Sesi ditolak.

## 16.7 Snapshot

Ubah nama siswa setelah Presensi.

Expected:
- snapshot lama tetap menunjukkan nama saat record dibuat.

---

# 17. Jurnal Test

- semua Jadwal aktif;
- Non Sesi muncul;
- materi kosong ditolak;
- whitespace-only ditolak;
- Guru memalsukan `id_guru` ditolak;
- Admin atas nama berhasil;
- status Hadir geofence;
- Izin/Sakit tanpa geofence;
- duplicate jadwal/tanggal;
- revisi Guru ditolak;
- revisi Admin/Operator berhasil;
- Jadwal Nonaktif tidak dapat dipakai input baru;
- snapshot Guru tetap historis.

---

# 18. Geofencing Automated Test

Case:

```text
inside radius
outside radius
exact boundary
invalid latitude
invalid longitude
null coordinate
disabled global
```

Haversine output diuji dalam meter.

---

# 19. Scope Automated Test

Case minimum:

1. Admin `master_guru.manage` → SEMUA.
2. Guru `jadwal_guru.view` → DIRI_SENDIRI.
3. Guru non-Wali contextual Wali → TIDAK_ADA.
4. Wali `master_siswa.view` → KELAS_DIAMPU.
5. Siswa profile → DIRI_SENDIRI.
6. user tanpa permission → TIDAK_ADA.
7. multi-role Guru+Operator → SEMUA bila Operator memiliki SEMUA.
8. Admin tanpa `id_guru` tetap memperoleh SEMUA.
9. mapping Wali soft-deleted tidak menghasilkan KELAS_DIAMPU.

---

# 20. Jadwal Bentrok Automated Test

Test unit `JadwalGuruService::validateBentrok()`:

- boundary jam bersentuhan (`09:00-10:00` dan `10:00-11:00`) tidak overlap;
- nested overlap;
- partial overlap;
- same start;
- same end;
- hari berbeda tidak overlap;
- Guru berbeda/Kelas sama tetap bentrok kelas;
- Guru sama/Kelas berbeda tetap bentrok Guru.

---

# 21. Security Test

- SQL injection payload;
- invalid ID;
- tamper hidden input;
- direct URL;
- direct DELETE;
- forged `id_guru`;
- forged `id_siswa`;
- Wali forged siswa kelas lain;
- CSRF missing;
- invalid MIME upload;
- oversized file;
- executable upload;
- XSS payload pada nama/alamat/keterangan;
- path traversal filename;
- duplicate rapid submit.

Output HTML harus di-escape.

---

# 22. Transaction Test

Transaction penting:

- import Guru;
- import Pegawai;
- import Siswa;
- create entity + user;
- kenaikan kelas;
- kelulusan;
- mutasi;
- mapping restore/reassign;
- import Jadwal;
- bulk Presensi.

Sisipkan satu error di tengah dan pastikan rollback total.

---

# 23. Performance Test

Target:
- Master Data kecil: client-side DataTables diperbolehkan;
- transaksi besar: query terfilter;
- index dipakai untuk `id_tahun`, tanggal, kelas, siswa, Guru;
- export tidak memuat data di luar filter;
- query EWS dibatasi window tanggal;
- halaman Presensi satu kelas tidak melakukan N+1 query per siswa.

---

# 24. Upload Test

Foto:
- PNG valid;
- JPG ditolak bila hanya PNG diizinkan;
- >2 MB ditolak;
- malformed PNG ditolak;
- hasil crop 3:4;
- metadata tidak dipertahankan;
- URL file dapat diakses sesuai konfigurasi;
- file lama dibersihkan bila lifecycle mengharuskan.

---

# 25. Import Test

Setiap import:

- header benar;
- header salah;
- row kosong;
- identifier leading zero;
- duplicate internal file;
- duplicate database;
- error row awal;
- error row tengah;
- error row akhir;
- transaction rollback;
- pesan error menyebut baris.

---

# 26. Export Test

Set filter.

Export.

Expected:

```text
dataset export = dataset setelah filter + scope
```

Tidak boleh export seluruh database jika layar hanya menampilkan subset.

---

# 27. Log Test

Periksa:

```text
id_user
aksi
modul
keterangan
waktu
```

Pastikan mutation penting tercatat.

Log tidak boleh menyimpan password/token.

---

# 28. Error Handling

Expected:

- validation → 422;
- forbidden → 403;
- unauthenticated → 401/redirect sesuai Web;
- not found → 404;
- conflict business → 409/422;
- internal → log + safe message.

Production tidak boleh menampilkan stack trace sensitif.

---

# 29. Deployment Smoke Test

- HTTPS;
- `.env production`;
- database;
- writable permission;
- upload permission;
- CSRF;
- session;
- login;
- export;
- upload;
- geolocation;
- QR;
- PDF.

---

# 30. Browser Console

Final release:

```text
0 uncaught JavaScript errors
0 missing asset 404 kritis
0 mixed-content error
```

Warning vendor yang tidak memengaruhi fungsi tetap harus dievaluasi.

---

# 31. Server Log

`writable/logs` tidak boleh berisi fatal error dari alur normal.

Error yang memang diuji harus dapat dibedakan dari error aplikasi tidak terduga.

---

# 32. README Fresh Install

README harus menjelaskan:

```text
clone
composer install
.env
database import
permission folder
login bootstrap
production deployment
HTTPS
extension PHP
```

Fresh install README harus diuji oleh orang yang tidak bergantung pada database existing.

---

# 33. Git Hygiene

Tidak commit:

```text
.env
uploads content
writable logs
backup
credential
export temp
```

Sebelum release:

```powershell
git status
```

harus bersih.

---

# 34. Regression Test Setelah Perubahan Auth

Setiap perubahan pada:

```text
AuthService
PermissionService
MenuService
PermissionFilter
role_permissions
role_menus
```

wajib mengulang:
- Admin;
- Operator;
- Pimpinan;
- Guru;
- Wali;
- Siswa.

Karena perubahan Auth dapat memengaruhi seluruh modul.

---

# 35. Regression Test Setelah Perubahan Database

Setiap perubahan schema wajib:
- fresh install test;
- existing database compatibility assessment;
- Model allowedFields check;
- Service column name check;
- FK check;
- import/export check.

---

# 36. Kriteria Tahap Master Data Lulus

- Auth/RBAC lulus;
- seluruh 8 Master Data lulus;
- CSRF lulus;
- database fresh install lulus;
- integration matrix lulus;
- tidak ada blocker.

---

# 37. Kriteria Tahap Presensi Lulus

- Presensi Siswa lulus seluruh role;
- Jurnal lulus;
- time-window lulus;
- geofence lulus;
- snapshot lulus;
- rollback lulus;
- laporan dasar dapat membaca data;
- EWS membaca Sesi Awal saja.

---

# 38. Kriteria Rilis

Sistem dapat dirilis bila:

1. semua checkpoint selesai;
2. fresh install berhasil;
3. security test dasar lulus;
4. no blocker;
5. no critical schema drift;
6. RBAC semua role sesuai;
7. transaction critical lulus;
8. production smoke test lulus;
9. dokumentasi lengkap;
10. backup dapat dibuat dan dipulihkan.
