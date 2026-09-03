# 🚦 Routes Final — SisisFour

**Versi:** 3.0 Final · **Tanggal:** 27 Agustus 2026

Dokumen ini berisi seluruh daftar **route** yang digunakan di aplikasi, lengkap dengan metode HTTP, grup, filter autentikasi, dan permission yang dibutuhkan. Semua route mengikuti konvensi **RESTful** dan mendukung **dual-output** (HTML + JSON) melalui parameter `?format=json`.

**✅ Fitur Lengkap:**

- **42 permission** final dengan scope yang sudah disesuaikan.
- **API Profile lengkap:** `PUT /api/profile/guru` dan `POST /api/profile/guru/foto`.
- **API Version:** `GET /api/version` untuk mekanisme update APK.
- **Filter:** `auth` (web session), `auth:api` (JWT), `permission` (RBAC).

162

Total Routes

54

Master Data

22

API Mobile

1

Public

* * *

## 1. Konvensi Route

- **Filter Autentikasi:**
  
  - auth → Session-based (web).
  - auth:api → JWT-based (mobile).
  - Tanpa Auth → Public (verifikasi QR).
- **Permission Filter:** Semua route dalam grup `master/`, `presensi/`, `laporan/`, `bk/`, `kartu/`, `settings/`, `backup/`, dan `log/` dilindungi oleh **PermissionFilter** yang mengecek `role_permissions` + `scope`.
- **Dual-Output:** Endpoint dengan parameter `?format=json` atau suffix `/json` mengembalikan response dalam format JSON.

* * *

## 2. Route Group: Autentikasi (Web)

Tanpa Auth

| Method     | Route          | Controller::Method | Deskripsi                        |
|------------|----------------|--------------------|----------------------------------|
| GET        | `/`            | `Auth::login`      | Redirect ke halaman login        |
| GET / POST | `/auth/login`  | `Auth::login`      | Halaman login &amp; proses login |
| GET        | `/auth/logout` | `Auth::logout`     | Logout (hapus session)           |

* * *

## 3. Route Group: Autentikasi API (Mobile)

Tanpa Auth (kecuali `/me` dan `/logout`)

| Method | Route               | Controller::Method | Filter                      | Deskripsi                                     |
|--------|---------------------|--------------------|-----------------------------|-----------------------------------------------|
| POST   | `/api/auth/login`   | `Auth::apiLogin`   | None                        | Login via JWT (return access + refresh token) |
| POST   | `/api/auth/logout`  | `Auth::apiLogout`  | auth:api                    | Logout (revoke token)                         |
| GET    | `/api/auth/me`      | `Auth::apiMe`      | auth:api                    | Ambil data user dari JWT                      |
| POST   | `/api/auth/refresh` | `Auth::apiRefresh` | None (dengan refresh token) | Refresh access token                          |

* * *

## 4. Route Group: Public

Tanpa Auth — Verifikasi QR Kartu Pelajar

| Method | Route                      | Controller::Method        | Deskripsi                                                    |
|--------|----------------------------|---------------------------|--------------------------------------------------------------|
| GET    | `/kartu/verify/(:segment)` | `KartuPelajar::verify/$1` | Halaman publik verifikasi QR (NIK masking: 351012xxxxxx1234) |

* * *

## 5. Route Group: Protected Web

auth + permission — Semua route di bawah ini membutuhkan session aktif dan permission yang sesuai.

### 5.1 Dashboard

| Method | Route             | Controller::Method | Permission       | Deskripsi                     |
|--------|-------------------|--------------------|------------------|-------------------------------|
| GET    | `/dashboard`      | `Dashboard::index` | `dashboard.view` | Halaman dashboard sesuai role |
| GET    | `/dashboard/data` | `Dashboard::data`  | `dashboard.view` | Data widget dashboard (JSON)  |

### 5.2 Master Data (54 Route)

**Prefix:** `/master`

#### 5.2.1 Guru

| Method | Route                            | Controller::Method             | Permission                                   | Deskripsi                              |
|--------|----------------------------------|--------------------------------|----------------------------------------------|----------------------------------------|
| GET    | `/master/guru`                   | `MasterGuru::index`            | `master_guru.manage` atau `master_guru.view` | Halaman daftar guru                    |
| GET    | `/master/guru/json`              | `MasterGuru::index`            | `master_guru.manage` atau `master_guru.view` | Data guru (JSON) untuk DataTables      |
| GET    | `/master/guru/template`          | `MasterGuru::downloadTemplate` | `master_guru.manage`                         | Download template import Excel         |
| POST   | `/master/guru/create`            | `MasterGuru::create`           | `master_guru.manage`                         | Tambah guru baru                       |
| POST   | `/master/guru/import`            | `MasterGuru::import`           | `master_guru.manage`                         | Import Excel guru (atomic transaction) |
| GET    | `/master/guru/export`            | `MasterGuru::export`           | `master_guru.manage`                         | Export Excel guru                      |
| PUT    | `/master/guru/update/(:segment)` | `MasterGuru::update/$1`        | `master_guru.manage`                         | Update data guru                       |
| DELETE | `/master/guru/delete/(:segment)` | `MasterGuru::delete/$1`        | `master_guru.manage`                         | Soft delete guru                       |

#### 5.2.2 Pegawai

| Method | Route                               | Controller::Method                | Permission                                         | Deskripsi                |
|--------|-------------------------------------|-----------------------------------|----------------------------------------------------|--------------------------|
| GET    | `/master/pegawai`                   | `MasterPegawai::index`            | `master_pegawai.manage` atau `master_pegawai.view` | Halaman daftar pegawai   |
| GET    | `/master/pegawai/json`              | `MasterPegawai::index`            | `master_pegawai.manage` atau `master_pegawai.view` | Data pegawai (JSON)      |
| GET    | `/master/pegawai/template`          | `MasterPegawai::downloadTemplate` | `master_pegawai.manage`                            | Download template import |
| POST   | `/master/pegawai/create`            | `MasterPegawai::create`           | `master_pegawai.manage`                            | Tambah pegawai baru      |
| POST   | `/master/pegawai/import`            | `MasterPegawai::import`           | `master_pegawai.manage`                            | Import Excel pegawai     |
| GET    | `/master/pegawai/export`            | `MasterPegawai::export`           | `master_pegawai.manage`                            | Export Excel pegawai     |
| PUT    | `/master/pegawai/update/(:segment)` | `MasterPegawai::update/$1`        | `master_pegawai.manage`                            | Update pegawai           |
| DELETE | `/master/pegawai/delete/(:segment)` | `MasterPegawai::delete/$1`        | `master_pegawai.manage`                            | Soft delete pegawai      |

#### 5.2.3 Siswa

| Method | Route                                  | Controller::Method              | Permission                                             | Deskripsi                         |
|--------|----------------------------------------|---------------------------------|--------------------------------------------------------|-----------------------------------|
| GET    | `/master/siswa`                        | `MasterSiswa::index`            | `master_siswa.view`                                    | Halaman daftar siswa              |
| GET    | `/master/siswa/json`                   | `MasterSiswa::index`            | `master_siswa.view`                                    | Data siswa (JSON)                 |
| GET    | `/master/siswa/template`               | `MasterSiswa::downloadTemplate` | `master_siswa.import_export`                           | Download template import          |
| POST   | `/master/siswa/create`                 | `MasterSiswa::create`           | `master_siswa.manage`                                  | Tambah siswa baru                 |
| POST   | `/master/siswa/import`                 | `MasterSiswa::import`           | `master_siswa.import_export`                           | Import Excel siswa                |
| GET    | `/master/siswa/export`                 | `MasterSiswa::export`           | `master_siswa.import_export`                           | Export Excel siswa                |
| PUT    | `/master/siswa/update/(:segment)`      | `MasterSiswa::update/$1`        | `master_siswa.edit_biodata` (KELAS\_DIAMPU untuk Wali) | Update biodata siswa              |
| DELETE | `/master/siswa/delete/(:segment)`      | `MasterSiswa::delete/$1`        | `master_siswa.manage`                                  | Soft delete siswa                 |
| POST   | `/master/siswa/mutasi`                 | `MasterSiswa::mutasi`           | `master_siswa.manage`                                  | Mutasi siswa (Pindah/Keluar)      |
| POST   | `/master/siswa/upload-foto/(:segment)` | `MasterSiswa::uploadFoto/$1`    | `master_siswa.edit_biodata` (KELAS\_DIAMPU untuk Wali) | Upload foto siswa (PNG, crop 3:4) |

#### 5.2.4 Kelas

| Method | Route                             | Controller::Method       | Permission            | Deskripsi                                 |
|--------|-----------------------------------|--------------------------|-----------------------|-------------------------------------------|
| GET    | `/master/kelas`                   | `MasterKelas::index`     | `master_kelas.manage` | Halaman daftar kelas                      |
| GET    | `/master/kelas/json`              | `MasterKelas::index`     | `master_kelas.manage` | Data kelas (JSON)                         |
| POST   | `/master/kelas/create`            | `MasterKelas::create`    | `master_kelas.manage` | Tambah kelas baru                         |
| PUT    | `/master/kelas/update/(:segment)` | `MasterKelas::update/$1` | `master_kelas.manage` | Update kelas                              |
| DELETE | `/master/kelas/delete/(:segment)` | `MasterKelas::delete/$1` | `master_kelas.manage` | Soft delete kelas                         |
| POST   | `/master/kelas/naik/(:segment)`   | `MasterKelas::naik/$1`   | `master_kelas.manage` | Kenaikan kelas (checklist, default semua) |
| POST   | `/master/kelas/lulus/(:segment)`  | `MasterKelas::lulus/$1`  | `master_kelas.manage` | Kelulusan kelas 9                         |

#### 5.2.5 Tahun Ajaran

| Method | Route                               | Controller::Method               | Permission                   | Deskripsi                                     |
|--------|-------------------------------------|----------------------------------|------------------------------|-----------------------------------------------|
| GET    | `/master/tahun`                     | `MasterTahunAjaran::index`       | `master_tahun_ajaran.manage` | Halaman daftar tahun ajaran                   |
| GET    | `/master/tahun/json`                | `MasterTahunAjaran::index`       | `master_tahun_ajaran.manage` | Data tahun ajaran (JSON)                      |
| POST   | `/master/tahun/create`              | `MasterTahunAjaran::create`      | `master_tahun_ajaran.manage` | Tambah tahun ajaran baru                      |
| POST   | `/master/tahun/aktifkan/(:segment)` | `MasterTahunAjaran::aktifkan/$1` | `master_tahun_ajaran.manage` | Aktifkan tahun ajaran (nonaktifkan yang lain) |
| DELETE | `/master/tahun/delete/(:segment)`   | `MasterTahunAjaran::delete/$1`   | `master_tahun_ajaran.manage` | Soft delete tahun ajaran                      |

#### 5.2.6 Mata Pelajaran

| Method | Route                             | Controller::Method       | Permission            | Deskripsi            |
|--------|-----------------------------------|--------------------------|-----------------------|----------------------|
| GET    | `/master/mapel`                   | `MasterMapel::index`     | `master_mapel.manage` | Halaman daftar mapel |
| GET    | `/master/mapel/json`              | `MasterMapel::index`     | `master_mapel.manage` | Data mapel (JSON)    |
| POST   | `/master/mapel/create`            | `MasterMapel::create`    | `master_mapel.manage` | Tambah mapel baru    |
| PUT    | `/master/mapel/update/(:segment)` | `MasterMapel::update/$1` | `master_mapel.manage` | Update mapel         |
| DELETE | `/master/mapel/delete/(:segment)` | `MasterMapel::delete/$1` | `master_mapel.manage` | Hard delete mapel    |

#### 5.2.7 Mapping Wali Kelas

| Method | Route                                  | Controller::Method            | Permission                                     | Deskripsi                                    |
|--------|----------------------------------------|-------------------------------|------------------------------------------------|----------------------------------------------|
| GET    | `/master/wali-kelas`                   | `MappingWaliKelas::index`     | `mapping_wali.view` atau `mapping_wali.manage` | Halaman mapping wali kelas                   |
| GET    | `/master/wali-kelas/json`              | `MappingWaliKelas::index`     | `mapping_wali.view` atau `mapping_wali.manage` | Data mapping (JSON)                          |
| POST   | `/master/wali-kelas/assign`            | `MappingWaliKelas::assign`    | `mapping_wali.manage`                          | Assign wali kelas (dengan mekanisme restore) |
| DELETE | `/master/wali-kelas/delete/(:segment)` | `MappingWaliKelas::delete/$1` | `mapping_wali.manage`                          | Nonaktifkan mapping (soft delete timestamp)  |

#### 5.2.8 Jadwal Guru

| Method | Route                              | Controller::Method             | Permission                                                               | Deskripsi                                               |
|--------|------------------------------------|--------------------------------|--------------------------------------------------------------------------|---------------------------------------------------------|
| GET    | `/master/jadwal`                   | `JadwalGuru::index`            | `jadwal_guru.view` atau `jadwal_guru.view_all` atau `jadwal_guru.manage` | Halaman daftar jadwal                                   |
| GET    | `/master/jadwal/json`              | `JadwalGuru::index`            | `jadwal_guru.view` atau `jadwal_guru.view_all` atau `jadwal_guru.manage` | Data jadwal (JSON)                                      |
| GET    | `/master/jadwal/template`          | `JadwalGuru::downloadTemplate` | `jadwal_guru.manage`                                                     | Download template import                                |
| POST   | `/master/jadwal/import`            | `JadwalGuru::import`           | `jadwal_guru.manage`                                                     | Import Excel jadwal (atomic transaction, Query Builder) |
| DELETE | `/master/jadwal/delete/(:segment)` | `JadwalGuru::delete/$1`        | `jadwal_guru.manage`                                                     | Hapus jadwal (hard delete)                              |

### 5.3 Presensi

**Prefix:** `/presensi`

#### 5.3.1 Presensi Siswa

| Method | Route                                   | Controller::Method         | Permission              | Deskripsi                                           |
|--------|-----------------------------------------|----------------------------|-------------------------|-----------------------------------------------------|
| GET    | `/presensi/siswa`                       | `PresensiSiswa::index`     | `presensi_siswa.input`  | Halaman pilih kelas                                 |
| GET    | `/presensi/siswa/input/(:segment)`      | `PresensiSiswa::input/$1`  | `presensi_siswa.input`  | Halaman input presensi (tombol solid default Hadir) |
| GET    | `/presensi/siswa/input/(:segment)/json` | `PresensiSiswa::input/$1`  | `presensi_siswa.input`  | Data form input (JSON)                              |
| POST   | `/presensi/siswa/save`                  | `PresensiSiswa::save`      | `presensi_siswa.input`  | Simpan presensi                                     |
| PUT    | `/presensi/siswa/revisi/(:segment)`     | `PresensiSiswa::revisi/$1` | `presensi_siswa.revisi` | Revisi presensi (Wali/Admin/Operator)               |
| GET    | `/presensi/siswa/ews`                   | `PresensiSiswa::ews`       | `ews_radar.view`        | Halaman EWS Radar (widget-only)                     |
| GET    | `/presensi/siswa/ews/json`              | `PresensiSiswa::ews`       | `ews_radar.view`        | Data EWS Radar (JSON)                               |
| GET    | `/presensi/siswa/rekap/(:segment)`      | `PresensiSiswa::rekap/$1`  | `presensi_siswa.view`   | Rekap presensi siswa                                |
| GET    | `/presensi/siswa/rekap/(:segment)/json` | `PresensiSiswa::rekap/$1`  | `presensi_siswa.view`   | Data rekap (JSON)                                   |

#### 5.3.2 Presensi Mengajar

| Method | Route                                      | Controller::Method           | Permission                | Deskripsi                                                    |
|--------|--------------------------------------------|------------------------------|---------------------------|--------------------------------------------------------------|
| GET    | `/presensi/mengajar`                       | `PresensiMengajar::index`    | `presensi_mengajar.input` | Halaman pilih jadwal                                         |
| GET    | `/presensi/mengajar/input/(:segment)`      | `PresensiMengajar::input/$1` | `presensi_mengajar.input` | Halaman input jurnal (status Hadir/Izin/Sakit, materi wajib) |
| GET    | `/presensi/mengajar/input/(:segment)/json` | `PresensiMengajar::input/$1` | `presensi_mengajar.input` | Data form jurnal (JSON)                                      |
| POST   | `/presensi/mengajar/save`                  | `PresensiMengajar::save`     | `presensi_mengajar.input` | Simpan jurnal (validasi id\_guru sendiri)                    |
| GET    | `/presensi/mengajar/laporan`               | `PresensiMengajar::laporan`  | `presensi_mengajar.view`  | Halaman laporan jurnal                                       |
| GET    | `/presensi/mengajar/laporan/json`          | `PresensiMengajar::laporan`  | `presensi_mengajar.view`  | Data laporan jurnal (JSON)                                   |

### 5.4 Laporan &amp; Export

**Prefix:** `/laporan`

| Method | Route                           | Controller::Method        | Permission                | Deskripsi                                             |
|--------|---------------------------------|---------------------------|---------------------------|-------------------------------------------------------|
| GET    | `/laporan/presensi/matrix`      | `LaporanPresensi::matrix` | `laporan_matrix.view`     | Matrix presensi (Total H\|S\|I\|A)                    |
| GET    | `/laporan/presensi/matrix/json` | `LaporanPresensi::matrix` | `laporan_matrix.view`     | Data matrix (JSON)                                    |
| GET    | `/laporan/presensi/export`      | `LaporanPresensi::export` | `laporan_export.generate` | Halaman export (Per Bulan/Semester, Total H\|S\|I\|A) |
| GET    | `/laporan/presensi/export/json` | `LaporanPresensi::export` | `laporan_export.generate` | Data export (JSON)                                    |
| GET    | `/laporan/jurnal`               | `LaporanJurnal::index`    | `laporan_jurnal.view`     | Laporan jurnal (filter dinamis Guru→Kelas)            |
| GET    | `/laporan/jurnal/json`          | `LaporanJurnal::index`    | `laporan_jurnal.view`     | Data laporan jurnal (JSON)                            |
| GET    | `/laporan/jurnal/export`        | `LaporanJurnal::export`   | `laporan_jurnal.export`   | Export Excel laporan jurnal                           |

### 5.5 BK &amp; Prestasi

**Prefix:** `/bk`

| Method | Route                               | Controller::Method         | Permission                     | Deskripsi                             |
|--------|-------------------------------------|----------------------------|--------------------------------|---------------------------------------|
| GET    | `/bk/kasus`                         | `BKKasus::index`           | `bk_kasus.view`                | Halaman daftar catatan kasus          |
| GET    | `/bk/kasus/json`                    | `BKKasus::index`           | `bk_kasus.view`                | Data kasus (JSON)                     |
| POST   | `/bk/kasus/create`                  | `BKKasus::create`          | `bk_kasus.manage`              | Tambah catatan kasus                  |
| GET    | `/bk/kasus/top`                     | `BKKasus::top`             | `bk_kasus.view`                | Top 20 poin pelanggaran (widget-only) |
| GET    | `/bk/kasus/top/json`                | `BKKasus::top`             | `bk_kasus.view`                | Data Top 20 (JSON)                    |
| GET    | `/bk/kasus/export`                  | `BKKasus::export`          | `bk_kasus.manage`              | Export Excel catatan kasus            |
| GET    | `/bk/pelanggaran`                   | `BKPelanggaran::index`     | `bk_pelanggaran_master.manage` | Halaman master pelanggaran            |
| GET    | `/bk/pelanggaran/json`              | `BKPelanggaran::index`     | `bk_pelanggaran_master.manage` | Data pelanggaran (JSON)               |
| POST   | `/bk/pelanggaran/create`            | `BKPelanggaran::create`    | `bk_pelanggaran_master.manage` | Tambah pelanggaran                    |
| PUT    | `/bk/pelanggaran/update/(:segment)` | `BKPelanggaran::update/$1` | `bk_pelanggaran_master.manage` | Update pelanggaran                    |
| DELETE | `/bk/pelanggaran/delete/(:segment)` | `BKPelanggaran::delete/$1` | `bk_pelanggaran_master.manage` | Hapus pelanggaran                     |
| GET    | `/bk/prestasi`                      | `BKPrestasi::index`        | `prestasi.view`                | Halaman daftar prestasi               |
| GET    | `/bk/prestasi/json`                 | `BKPrestasi::index`        | `prestasi.view`                | Data prestasi (JSON)                  |
| POST   | `/bk/prestasi/create`               | `BKPrestasi::create`       | `prestasi.manage`              | Tambah prestasi                       |
| GET    | `/bk/prestasi/export`               | `BKPrestasi::export`       | `prestasi.manage`              | Export Excel prestasi                 |

### 5.6 Kartu Pelajar

**Prefix:** `/kartu`

| Method | Route                            | Controller::Method          | Permission             | Deskripsi                                           |
|--------|----------------------------------|-----------------------------|------------------------|-----------------------------------------------------|
| GET    | `/kartu/daftar`                  | `KartuPelajar::daftar`      | `kartu_pelajar.view`   | Halaman daftar kartu                                |
| GET    | `/kartu/daftar/json`             | `KartuPelajar::daftar`      | `kartu_pelajar.view`   | Data kartu (JSON)                                   |
| POST   | `/kartu/generate`                | `KartuPelajar::generate`    | `kartu_pelajar.manage` | Bulk generate kartu (semua siswa aktif)             |
| GET    | `/kartu/cetak/(:segment)`        | `KartuPelajar::cetak/$1`    | `kartu_pelajar.manage` | Cetak massal PDF (per kelas, 2×5 per A4)            |
| GET    | `/kartu/preview/(:segment)`      | `KartuPelajar::preview/$1`  | `kartu_pelajar.view`   | Preview kartu (depan &amp; belakang)                |
| GET    | `/kartu/preview/(:segment)/json` | `KartuPelajar::preview/$1`  | `kartu_pelajar.view`   | Data preview (JSON)                                 |
| GET    | `/kartu/download/(:segment)`     | `KartuPelajar::download/$1` | `kartu_pelajar.view`   | Download PDF kartu                                  |
| POST   | `/kartu/reissue/(:segment)`      | `KartuPelajar::reissue/$1`  | `kartu_pelajar.manage` | Reissue (generate ulang PDF, tanpa ubah nomor/kode) |

### 5.7 Profile

**Prefix:** `/profile`

| Method | Route                       | Controller::Method        | Permission           | Deskripsi                                              |
|--------|-----------------------------|---------------------------|----------------------|--------------------------------------------------------|
| GET    | `/profile/guru`             | `ProfileGuru::index`      | `profile_guru.view`  | Halaman profile guru                                   |
| GET    | `/profile/guru/json`        | `ProfileGuru::index`      | `profile_guru.view`  | Data profile guru (JSON)                               |
| PUT    | `/profile/guru/update`      | `ProfileGuru::update`     | `profile_guru.edit`  | Update data diri guru (NIP readonly, sisanya editable) |
| POST   | `/profile/guru/upload-foto` | `ProfileGuru::uploadFoto` | `profile_guru.edit`  | Upload foto profile guru (PNG, re-encode)              |
| GET    | `/profile/siswa`            | `ProfileSiswa::index`     | `profile_siswa.view` | Halaman profile siswa (readonly)                       |
| GET    | `/profile/siswa/json`       | `ProfileSiswa::index`     | `profile_siswa.view` | Data profile siswa (JSON)                              |

### 5.8 Settings

**Prefix:** `/settings` — Hanya Admin

| Method | Route                                    | Controller::Method                    | Permission               | Deskripsi                                  |
|--------|------------------------------------------|---------------------------------------|--------------------------|--------------------------------------------|
| GET    | `/settings/user`                         | `SettingsUser::index`                 | `settings_user.manage`   | Halaman manajemen user                     |
| GET    | `/settings/user/json`                    | `SettingsUser::index`                 | `settings_user.manage`   | Data user (JSON)                           |
| POST   | `/settings/user/create`                  | `SettingsUser::create`                | `settings_user.manage`   | Tambah user baru                           |
| PUT    | `/settings/user/update/(:segment)`       | `SettingsUser::update/$1`             | `settings_user.manage`   | Update user                                |
| POST   | `/settings/user/reset/(:segment)`        | `SettingsUser::reset/$1`              | `settings_user.manage`   | Reset password user (ke NIP/NISN/username) |
| DELETE | `/settings/user/delete/(:segment)`       | `SettingsUser::delete/$1`             | `settings_user.manage`   | Hapus user                                 |
| GET    | `/settings/menu`                         | `SettingsMenu::index`                 | `settings_menu.manage`   | Halaman menu &amp; role                    |
| GET    | `/settings/menu/json`                    | `SettingsMenu::index`                 | `settings_menu.manage`   | Data menu (JSON)                           |
| PUT    | `/settings/menu/update`                  | `SettingsMenu::update`                | `settings_menu.manage`   | Update visibility menu per role            |
| GET    | `/settings/sistem`                       | `SettingsSistem::index`               | `settings_sistem.manage` | Halaman setting sistem                     |
| GET    | `/settings/sistem/json`                  | `SettingsSistem::index`               | `settings_sistem.manage` | Data setting sistem (JSON)                 |
| PUT    | `/settings/sistem/update`                | `SettingsSistem::update`              | `settings_sistem.manage` | Update setting sistem                      |
| POST   | `/settings/sistem/maintenance`           | `SettingsSistem::maintenance`         | `settings_sistem.manage` | Toggle maintenance mode                    |
| POST   | `/settings/sistem/upload-branding`       | `SettingsSistem::uploadBranding`      | `settings_sistem.manage` | Upload logo/icon sekolah                   |
| POST   | `/settings/sistem/upload-background-kta` | `SettingsSistem::uploadBackgroundKta` | `settings_sistem.manage` | Upload background kartu pelajar            |

### 5.9 Backup &amp; Log Activity

Hanya Admin

| Method | Route                         | Controller::Method    | Permission          | Deskripsi                     |
|--------|-------------------------------|-----------------------|---------------------|-------------------------------|
| GET    | `/backup`                     | `Backup::index`       | `backup.manage`     | Halaman backup                |
| POST   | `/backup/generate`            | `Backup::generate`    | `backup.manage`     | Generate SQL dump (PHP murni) |
| GET    | `/backup/download/(:segment)` | `Backup::download/$1` | `backup.manage`     | Download file backup (.sql)   |
| DELETE | `/backup/delete/(:segment)`   | `Backup::delete/$1`   | `backup.manage`     | Hapus file backup             |
| GET    | `/log/activity`               | `LogActivity::index`  | `log_activity.view` | Halaman log activity          |
| GET    | `/log/activity/json`          | `LogActivity::index`  | `log_activity.view` | Data log activity (JSON)      |
| GET    | `/log/activity/export`        | `LogActivity::export` | `log_activity.view` | Export Excel log activity     |

* * *

## 6. Route Group: API Mobile

auth:api — Semua route di bawah ini membutuhkan JWT valid.

**Prefix:** `/api`

| Method | Route                                     | Controller::Method           | Permission                | Deskripsi                           |
|--------|-------------------------------------------|------------------------------|---------------------------|-------------------------------------|
| GET    | `/api/dashboard/data`                     | `Dashboard::data`            | `dashboard.view`          | Data widget dashboard               |
| GET    | `/api/presensi/siswa/input/(:segment)`    | `PresensiSiswa::input/$1`    | `presensi_siswa.input`    | Form input presensi siswa           |
| POST   | `/api/presensi/siswa/save`                | `PresensiSiswa::save`        | `presensi_siswa.input`    | Simpan presensi siswa               |
| GET    | `/api/presensi/siswa/ews`                 | `PresensiSiswa::ews`         | `ews_radar.view`          | Data EWS Radar                      |
| GET    | `/api/presensi/siswa/rekap/(:segment)`    | `PresensiSiswa::rekap/$1`    | `presensi_siswa.view`     | Rekap presensi siswa                |
| GET    | `/api/presensi/mengajar/input/(:segment)` | `PresensiMengajar::input/$1` | `presensi_mengajar.input` | Form input jurnal                   |
| POST   | `/api/presensi/mengajar/save`             | `PresensiMengajar::save`     | `presensi_mengajar.input` | Simpan jurnal (validasi id\_guru)   |
| GET    | `/api/presensi/mengajar/laporan`          | `PresensiMengajar::laporan`  | `presensi_mengajar.view`  | Laporan jurnal                      |
| GET    | `/api/laporan/presensi/matrix`            | `LaporanPresensi::matrix`    | `laporan_matrix.view`     | Matrix presensi (Total H\|S\|I\|A)  |
| GET    | `/api/laporan/jurnal`                     | `LaporanJurnal::index`       | `laporan_jurnal.view`     | Laporan jurnal                      |
| GET    | `/api/bk/kasus`                           | `BKKasus::index`             | `bk_kasus.view`           | Daftar catatan kasus                |
| POST   | `/api/bk/kasus/create`                    | `BKKasus::create`            | `bk_kasus.manage`         | Tambah catatan kasus                |
| GET    | `/api/bk/kasus/top`                       | `BKKasus::top`               | `bk_kasus.view`           | Top 20 poin pelanggaran             |
| GET    | `/api/bk/prestasi`                        | `BKPrestasi::index`          | `prestasi.view`           | Daftar prestasi                     |
| POST   | `/api/bk/prestasi/create`                 | `BKPrestasi::create`         | `prestasi.manage`         | Tambah prestasi                     |
| GET    | `/api/kartu/preview/(:segment)`           | `KartuPelajar::preview/$1`   | `kartu_pelajar.view`      | Preview kartu                       |
| GET    | `/api/kartu/download/(:segment)`          | `KartuPelajar::download/$1`  | `kartu_pelajar.view`      | Download PDF kartu                  |
| GET    | `/api/profile/guru`                       | `ProfileGuru::index`         | `profile_guru.view`       | Ambil data profile guru             |
| PUT    | `/api/profile/guru`                       | `ProfileGuru::update`        | `profile_guru.edit`       | Update profile guru (NIP readonly)  |
| POST   | `/api/profile/guru/foto`                  | `ProfileGuru::uploadFoto`    | `profile_guru.edit`       | Upload foto profile guru            |
| GET    | `/api/profile/siswa`                      | `ProfileSiswa::index`        | `profile_siswa.view`      | Ambil data profile siswa (readonly) |
| GET    | `/api/version`                            | `Api::version`               | None                      | Cek versi APK (untuk update)        |

**Catatan:** Endpoint `/api/version` tidak memerlukan autentikasi agar APK dapat mengecek update sebelum login.

* * *

## 7. Route 404 — Catch-All

| Method                    | Route | Handler                         | Deskripsi             |
|---------------------------|-------|---------------------------------|-----------------------|
| GET / POST / PUT / DELETE | `(*)` | `view('errors/html/error_404')` | Halaman 404 Not Found |

* * *

## 8. Catatan Penting untuk Developer

- **PermissionFilter:** Setiap route di grup `master/`, `presensi/`, `laporan/`, `bk/`, `kartu/`, `settings/`, `backup/`, dan `log/` akan dicek oleh `PermissionFilter`.
- **API Profile GAP FIX:** Route `PUT /api/profile/guru`, `POST /api/profile/guru/foto`, dan `/api/auth/refresh` telah ditambahkan.
- **API Version:** Route `GET /api/version` tersedia tanpa auth untuk cek update APK.
- **Dual-Output:** Endpoint dengan parameter `?format=json` atau suffix `/json` mengembalikan data JSON.
- **Single Active Session:** Web dan API menggunakan mekanisme `auth_version` yang sama.
- **Permission Mapping:** Pastikan `role_permissions` dan `role_menus` sudah terisi di database (lihat 02\_DATABASE).

* * *

© 2026 SisisFour · MTsN 4 Jombang · Routes Final
