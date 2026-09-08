# 🛠️ Pola Pengerjaan — SisisFour

**Versi Acuan: v0.5**  
**Tanggal:** 08 September 2026

Dokumen ini menetapkan tata cara pengembangan SisisFour dari fondasi sampai rilis. Dokumen ini bersifat normatif: setiap pengerjaan modul harus mengikuti urutan, pola file, aturan validasi, checkpoint, dan mekanisme integrasi yang dijelaskan di sini.

---

## 1. Prinsip Utama Pengembangan

### 1.1 Fondasi Horizontal, Fitur Vertikal

Pengerjaan dibagi menjadi dua pola:

1. **Fondasi horizontal**
   - konfigurasi framework;
   - database;
   - authentication;
   - RBAC;
   - layout;
   - menu;
   - CSRF;
   - helper/utilitas umum.

2. **Fitur vertikal per modul**
   - Model;
   - Service;
   - Filter bila diperlukan;
   - Controller;
   - View;
   - JavaScript;
   - Route;
   - pengujian modul.

Urutan internal modul:

```text
Model(s)
   ↓
Service(s)
   ↓
Filter (jika diperlukan)
   ↓
Controller(s)
   ↓
View(s)
   ↓
JavaScript
   ↓
Routes
   ↓
TEST / CHECKPOINT
```

Business logic tidak boleh dipindahkan ke View atau JavaScript. Controller harus tipis dan mendelegasikan operasi utama kepada Service.

---

## 2. Aturan Kode Aplikasi

### 2.1 Backend

- Framework: CodeIgniter 4.
- PHP: 8.2+.
- Query database menggunakan Query Builder/Model CI4.
- Raw SQL string concat untuk input user dilarang.
- Operasi yang mengubah beberapa tabel sekaligus wajib menggunakan transaction.
- Service menjadi pusat business rule.
- Model fokus pada representasi tabel, allowed fields, validasi dasar, soft delete, dan helper query lokal.
- Controller fokus pada request/response, delegasi Service, file response, dan rendering View.


### 2.1.1 Database-First Processing untuk Dataset Besar

Untuk dataset yang berpotensi besar, terutama `presensi`, `presensi_mengajar`, `log_activity`, laporan, dan histori operasional, SisisFour menggunakan prinsip **database-first processing**.

Operasi berikut wajib dilakukan oleh MariaDB/MySQL melalui SQL atau Query Builder CI4:

```text
filtering
searching
sorting
JOIN
GROUP BY
COUNT
SUM
MIN / MAX
HAVING
ORDER BY
LIMIT / OFFSET
agregasi periode
rekap status
```

PHP tidak boleh mengambil dataset besar lalu melakukan filtering, grouping, counting, atau agregasi utama dengan `foreach`.

Pola yang benar:

```text
Database
│
├─ WHERE / JOIN
├─ GROUP BY / COUNT / SUM
├─ ORDER BY
└─ LIMIT
      ↓
hasil sudah dipersempit
      ↓
Service PHP
│
├─ business rule
├─ pivot ringan
├─ mapping label
└─ format response
      ↓
View / JSON / XLSX
```

Pola yang dilarang untuk tabel besar:

```text
SELECT semua row
↓
PHP foreach ratusan ribu row
↓
filter / count / group di PHP
```

`Model::findAll()` tanpa pembatas yang jelas tidak boleh digunakan untuk tabel Presensi atau dataset histori besar.

PHP tetap boleh melakukan transformasi ringan setelah dataset dipersempit database, misalnya membentuk Matrix 1 kelas × 1 bulan dari hasil query periode yang sudah terfilter.

Untuk daftar histori besar, pagination/filter harus dilakukan server-side menggunakan `LIMIT/OFFSET` atau strategi pagination lain yang setara.


### 2.2 Frontend

Business JavaScript menggunakan:

- Vanilla JavaScript;
- Fetch API;
- Bootstrap 5;
- SweetAlert2;
- DataTables bila dibutuhkan;
- Select2 bila dibutuhkan.

jQuery boleh tetap dimuat sebagai dependency vendor/template, tetapi **logic aplikasi baru tidak boleh bergantung pada jQuery**.

Pola request frontend:

```text
View
  ↓
Vanilla JS
  ↓
Fetch API
  ↓
Route
  ↓
PermissionFilter
  ↓
Controller
  ↓
Service
  ↓
Model / Query Builder
```

### 2.3 CSRF Web

CSRF aktif untuk seluruh route Web.

Semua request mutasi berbasis Fetch:

- POST;
- PUT;
- PATCH;
- DELETE;

wajib membawa CSRF token melalui header global `X-CSRF-TOKEN`.

SisisFour menggunakan wrapper global:

```text
assets/js/csrf-fetch.js
```

yang dimuat sebelum JavaScript modul. Wrapper hanya memodifikasi request mutasi same-origin.

Konfigurasi Web menggunakan token stabil selama lifecycle halaman/session cookie agar request AJAX berurutan tidak menggunakan token yang sudah kadaluarsa setelah satu request.

---

## 3. Struktur Dokumen Acuan

Urutan otoritas spesifikasi:

```text
00_POLA_PENGERJAAN
        ↓
01_MASTERPLAN
        ↓
02_DATABASE
        ↓
03_AUTH_RBAC_MENU
        ↓
04_MASTER_DATA
        ↓
05_PRESENSI
        ↓
06_LAPORAN
        ↓
07_BK_PRESTASI_KARTU
        ↓
08_DASHBOARD_SETTINGS_BACKUP
        ↓
09_PROFILE
        ↓
15_TESTING_POLISH
```

Bila terdapat detail implementasi yang tidak disebut dokumen tingkat atas, gunakan dokumen modul yang paling spesifik.

`Routes.php` adalah kontrak runtime route. Namun route tidak boleh bertentangan dengan dokumen bisnis.

---

## 4. Fase Pengembangan

### Fase 0 — Fondasi

#### 4.1 Setup Project

- CI4 terpasang melalui Composer.
- Struktur `public/` dipindahkan ke root project sesuai pola deployment.
- `.htaccess` melindungi `app/`, `writable/`, `vendor/`, `.env`, dan file sensitif lainnya.
- Timezone `Asia/Jakarta`.
- Base URL sesuai environment.

#### 4.2 Dependency

Composer:

- PhpSpreadsheet;
- Dompdf;
- endroid/qr-code.

Frontend vendor:

- Bootstrap 5;
- SweetAlert2;
- DataTables;
- Select2;
- ApexCharts bila dibutuhkan dashboard;
- library template Sneat.

#### 4.3 Struktur Upload

```text
uploads/
├── .htaccess
├── foto_guru/
├── foto_siswa/
├── branding/
└── kartu_pelajar/
    ├── background_depan/
    └── background_belakang/
```

Upload image wajib:
- tipe yang diizinkan eksplisit;
- size limit;
- re-encode;
- tidak boleh executable;
- nama file tidak berasal langsung dari input user.

#### 4.4 Database

Database dibangun menggunakan SQL murni berdasarkan `02_DATABASE`.

Tidak menggunakan CI4 migration/seeder sebagai sumber kebenaran.

#### 4.5 Authentication dan RBAC

Fondasi Auth harus selesai sebelum modul bisnis.

Komponen minimum:

```text
Models:
- UserModel
- UserRolesModel
- LoginAttemptsModel
- ApiTokensModel

Services:
- AuthService
- PermissionService
- MenuService

Filters:
- AuthFilter
- PermissionFilter
- MaintenanceFilter
```

Checkpoint:
- login berhasil;
- login gagal tercatat;
- lock username setelah 5 kegagalan berturut-turut;
- session database;
- `auth_version`;
- multi-role;
- scope resolver;
- Wali dinamis;
- menu contextual;
- route 403 bila tidak memiliki permission.

---

### Fase 1 — Modul Master Data

Urutan implementasi wajib:

```text
Guru
↓
Pegawai
↓
Siswa
↓
Kelas
↓
Tahun Ajaran
↓
Mata Pelajaran
↓
Mapping Wali Kelas
↓
Jadwal Guru
```

#### 5. Guru

Komponen:

```text
Model     : GuruModel
Service   : GuruService
Controller: MasterGuru
View      : master/guru.php
Recycle   : master/guru_recycle.php
JS        : assets/js/master/guru.js
            assets/js/master/guru-recycle.js
```

Checkpoint:
- CRUD;
- NIP unik lintas Guru/Pegawai;
- user otomatis;
- foto;
- import/export;
- soft delete;
- restore;
- force delete;
- user terkait aktif/nonaktif sesuai lifecycle Guru.

#### 6. Pegawai

Checkpoint:
- CRUD;
- user otomatis;
- `users.role` boleh NULL;
- NIP unik lintas Guru/Pegawai;
- import/export atomic;
- soft delete/restore/force delete.

#### 7. Siswa

Checkpoint:
- NIK 16 digit;
- NISN unik;
- user siswa otomatis;
- Wali hanya kelas diampu;
- NISN immutable bagi Wali;
- foto;
- import/export;
- mutasi;
- soft delete/restore/force delete;
- kartu nonaktif saat Lulus/Pindah/Keluar;
- histori siswa konsisten.

#### 8. Kelas

Checkpoint:
- nama kelas otomatis `tingkat-rombel`;
- satu siswa satu kelas per tahun;
- kelola anggota kelas;
- kenaikan kelas;
- kelulusan tingkat 9;
- riwayat siswa;
- dependency sebelum delete;
- recycle/restore.

#### 9. Tahun Ajaran

Checkpoint:
- semester Ganjil/Genap;
- hanya satu record aktif;
- aktivasi menonaktifkan record sebelumnya;
- tahun aktif tidak dapat dihapus;
- dependency diperiksa;
- restore kembali Nonaktif.

#### 10. Mata Pelajaran

Checkpoint:
- kode unik;
- uppercase;
- hard delete;
- delete ditolak bila dipakai Jadwal Guru.

#### 11. Mapping Wali Kelas

Checkpoint:
- Wali bukan role;
- satu Guru maksimal satu Wali aktif per tahun;
- satu Kelas maksimal satu Wali aktif per tahun;
- soft delete sebagai histori;
- restore/reassign menggunakan row histori lama;
- scope DIRI_SENDIRI untuk Guru;
- readonly semua untuk Pimpinan;
- full untuk Admin/Operator.

#### 12. Jadwal Guru

Checkpoint:
- hanya import Excel;
- tidak ada form input manual;
- validasi Guru/Kelas/Mapel/hari/jam/sesi;
- bentrok Guru;
- bentrok Kelas;
- tidak ada team teaching;
- import atomic;
- jadwal aktif lama menjadi Nonaktif;
- hasil import baru menjadi Aktif;
- export mengikuti filter;
- Guru hanya melihat jadwal diri sendiri.

---

### Fase 2 — Finalisasi Integrasi Master Data

Sebelum Presensi dikerjakan, seluruh Master Data wajib melalui integrasi lintas modul.

Area uji:

```text
Guru ↔ Users/RBAC
Pegawai ↔ Users/RBAC
Siswa ↔ Users/RBAC
Siswa ↔ Anggota Kelas
Siswa ↔ Riwayat Siswa
Kelas ↔ Tahun Ajaran
Guru ↔ Mapping Wali
Guru ↔ Jadwal
Kelas ↔ Jadwal
Mapel ↔ Jadwal
Tahun Ajaran ↔ seluruh data operasional
```

CSRF smoke test wajib dilakukan untuk semua POST/PUT/PATCH/DELETE.

Master Data hanya dinyatakan selesai bila:
- tidak ada 403 CSRF palsu;
- tidak ada route 404;
- RBAC benar;
- scope benar;
- tidak ada duplicate user;
- tidak ada duplicate anggota kelas per tahun;
- tidak ada duplicate mapping aktif;
- transaction rollback benar;
- histori tidak putus.

---

### Fase 3 — Presensi

Presensi baru boleh mulai setelah Master Data stabil.

#### 13. Presensi Siswa

Komponen utama:

```text
PresensiModel
PresensiService
GeofencingService
PresensiSiswa
Views Presensi Siswa
JS Presensi Siswa
```

Checkpoint:
- Sesi Awal;
- Sesi Akhir;
- default Hadir;
- input bulk per kelas;
- scope Guru sesuai jadwal;
- scope Wali sesuai mapping;
- revisi;
- time-window;
- geofencing;
- snapshot identitas;
- atomic save;
- EWS.

#### 14. Presensi Mengajar

Checkpoint:
- semua sesi jadwal, termasuk Non Sesi;
- status Hadir/Izin/Sakit;
- materi wajib;
- `id_guru` tidak dapat dipalsukan oleh Guru;
- Admin/Operator boleh atas nama;
- revisi hanya Admin/Operator;
- geofencing Hadir Guru;
- time-window.

---

### Fase 4 — Modul Lanjutan

Urutan:

```text
Laporan
↓
BK & Prestasi
↓
Dashboard
↓
Kartu Pelajar
↓
Profile
↓
Settings
↓
Backup & Log
```

Setiap modul mengikuti urutan internal Model → Service → Filter → Controller → View → JS → Route → Test.

---

### Fase 5 — Automated Test

Automated test minimum:
1. `AuthService::resolveScope()`;
2. `JadwalGuruService::validateBentrok()`;
3. `GeofencingService`.

Disarankan menambah test transaction untuk:
- import Siswa;
- kenaikan kelas;
- mapping Wali;
- import Jadwal;
- bulk Presensi.

---

### Fase 6 — Mobile

Mobile Cordova hanya dimulai setelah Web stabil.

Mobile menggunakan API terpisah dengan JWT.

CSRF Web tidak digunakan sebagai autentikasi API mobile.

---

### Fase 7 — Testing & Polish

Acuan: `15_TESTING_POLISH`.

Kriteria akhir:
- semua modul lulus checkpoint;
- no fatal error;
- log bersih;
- fresh install dapat dilakukan dari docs;
- database fresh install konsisten;
- RBAC seluruh role teruji;
- geofencing teruji;
- backup berhasil;
- export sesuai filter;
- semua route protected;
- dokumentasi deployment lengkap.

---

## 15. Aturan Git

Sebelum push:

```powershell
git status
git add -A
git commit -m "pesan perubahan"
git push origin main
```

Tidak boleh commit:

```text
.env
uploads/*
writable/logs/*
writable/backups/*
file credential
file temporary export/import
```

Setelah push, `git status` harus menunjukkan working tree clean.

---

## 16. Aturan Penyerahan File

Untuk setiap file aplikasi yang diperbaiki:
- berikan file utuh;
- jangan hanya snippet;
- jangan menghilangkan route/modul lama;
- PHP wajib `php -l`;
- JavaScript wajib `node --check`;
- bila beberapa file, paketkan ZIP.

---

## 17. Definisi Selesai

Sebuah modul dinyatakan selesai hanya jika:
1. business rule terdokumentasi;
2. schema mendukung;
3. permission tersedia;
4. route tersedia;
5. Service menjalankan rule;
6. UI hanya menampilkan aksi yang diizinkan;
7. direct route tetap dijaga Filter/Service;
8. mutation terlindungi CSRF;
9. error handling jelas;
10. checkpoint manual lulus.
