# Tree Structure — SisisFour

**Versi Acuan Utama:** v0.5 FINAL BASELINE  
**Tanggal Acuan:** 08 September 2026  
**Baseline Aplikasi:** `main` @ `b85b857e1a38b6eb1fd26ba2d9aa61ae5e679f55`  
**Baseline Database:** `sisfour_dev_v2 (15).sql`

Dokumen ini adalah **acuan utama** dan menggambarkan baseline aplikasi yang berlaku. Bagian yang belum tersedia di repo dinyatakan sebagai gap/blocker, bukan diasumsikan sudah selesai.

---

# 1. Prinsip Struktur

Project menggunakan CodeIgniter 4 dengan document root langsung di root repository.

```text
sisfour_dev_v2/
├── index.php
├── .htaccess
├── composer.json
├── README.md
├── app/
├── assets/
├── database/
├── docs/
├── tests/
├── uploads/
├── vendor/
└── writable/
```

Folder `public/` bukan document root runtime.

# 2. App

```text
app/
├── Config/
├── Controllers/
├── Filters/
├── Models/
├── Services/
├── Views/
├── Database/
├── Language/
└── ...
```

# 3. Config Runtime Penting

```text
app/Config/
├── App.php
├── Database.php
├── Filters.php
├── Routes.php
├── Security.php
├── Session.php
└── ...
```

Kontrak route utama hanya dari `Routes.php`.

# 4. Controllers Aktual

Controller yang terkonfirmasi pada baseline meliputi:

```text
app/Controllers/
├── Auth.php
├── BaseController.php
├── Dashboard.php
├── Home.php
├── MasterGuru.php
├── MasterPegawai.php
├── MasterSiswa.php
├── MasterKelas.php
├── MasterTahunAjaran.php
├── MasterMapel.php
├── MappingWaliKelas.php
├── JadwalGuru.php
├── PresensiSiswa.php
├── PresensiMengajar.php
├── LaporanPresensi.php
├── LaporanJurnal.php
├── BKKasus.php
├── BKPelanggaran.php
├── BKPrestasi.php
├── KartuPelajar.php
├── SettingsUser.php
├── SettingsMenu.php
├── SettingsSistem.php
├── Backup.php
└── LogActivity.php
```

# 5. Controller yang Direferensikan Route tetapi Tidak Ada

Baseline memiliki route ke:

```text
Api
ProfileGuru
ProfileSiswa
```

namun file berikut tidak ditemukan:

```text
app/Controllers/Api.php
app/Controllers/ProfileGuru.php
app/Controllers/ProfileSiswa.php
```

Tree acuan tidak boleh mencantumkan ketiga file tersebut sebagai file aktual sampai benar-benar dibuat.

# 6. Models Aktual

Folder `app/Models/` berisi model operasional, antara lain:

```text
AnggotaKelasModel.php
ApiTokensModel.php
BKKasusModel.php
BKPelanggaranModel.php
BKPrestasiModel.php
GuruModel.php
JadwalGuruModel.php
KartuPelajarModel.php
KelasModel.php
LaporanJurnalModel.php
LaporanPresensiModel.php
LogActivityModel.php
MappingWaliKelasModel.php
MataPelajaranModel.php
PegawaiModel.php
PresensiMengajarModel.php
PresensiModel.php
RiwayatSiswaModel.php
SettingSistemModel.php
SettingsMenuModel.php
SettingsUserModel.php
SiswaModel.php
TahunAjaranModel.php
UserModel.php
UserRolesModel.php
```

Nama Model tidak harus satu-per-satu identik dengan nama tabel karena beberapa model berfungsi sebagai query/read model.

# 7. Services Aktual

Folder `app/Services/` memuat business logic. Service yang terkonfirmasi mencakup:

```text
ActivityLogService.php
AuthService.php
BackupService.php
BkExportService.php
BkScopeService.php
BkService.php
DashboardService.php
ExportService.php
GeofencingService.php
GuruService.php
JadwalGuruService.php
JwtService.php
KartuPelajarService.php
KartuRenderService.php
KelasService.php
LaporanJurnalService.php
LaporanPresensiService.php
LogActivityService.php
MappingWaliKelasService.php
MenuService.php
PegawaiService.php
PresensiMengajarService.php
PresensiService.php
SettingsMenuService.php
SettingsSistemService.php
SettingsUserService.php
SiswaService.php
TahunAjaranService.php
```

Business rule baru ditempatkan di Service, bukan View/JavaScript.

# 8. Filters

```text
app/Filters/
├── AuthFilter.php
├── PermissionFilter.php
└── MaintenanceFilter.php
```

Fungsi:

```text
AuthFilter         → authentication Web/API
PermissionFilter   → gate permission
MaintenanceFilter  → maintenance global
```

# 9. Layout Views

Layout canonical:

```text
app/Views/
├── main.php
├── _header.php
├── _sidebar.php
├── _navbar.php
├── _footer.php
├── _scripts.php
└── auth_login.php
```

View halaman modul memakai:

```php
<?= "$" ?>this->extend('main')
```

bukan `layout`.

# 10. View Modul

Struktur runtime mengikuti folder domain, misalnya:

```text
app/Views/
├── backup/
│   └── index.php
├── log/
│   └── activity.php
├── settings/
│   ├── user.php
│   ├── menu.php
│   └── sistem.php
├── errors/html/
│   └── maintenance.php
└── ... modul Master/Presensi/Laporan/BK/Kartu/Dashboard
```

# 11. JavaScript

Business JavaScript berada di:

```text
assets/js/
```

dan memakai Vanilla JS + Fetch.

File penting:

```text
assets/js/csrf-fetch.js
assets/js/backup/index.js
assets/js/log/activity.js
assets/js/settings/user.js
assets/js/settings/menu.js
assets/js/settings/sistem.js
```

jQuery dapat tersedia di vendor/template tetapi bukan fondasi business JS.

# 12. Kartu Default

```text
assets/kartu/default/
├── background_kta_depan.jpg
└── background_kta_belakang.jpg
```

Kedua file ini adalah fallback renderer Kartu.

# 13. Upload Runtime

```text
uploads/
├── foto_guru/
├── foto_siswa/
└── settings/
    ├── branding/
    │   ├── logo_*.png
    │   └── icon_*.png
    └── kartu/
        └── background upload hasil normalisasi
```

Path lama seperti:

```text
uploads/branding/
uploads/kartu_pelajar/background_depan/
```

bukan path canonical baseline.

# 14. Writable

```text
writable/
├── backups/
├── cache/
├── logs/
├── session/
└── uploads/ / runtime CI bila digunakan framework
```

Backup database canonical:

```text
writable/backups/backup_YYYYMMDD_HHMMSS.sql
```

# 15. Database

Database baseline memiliki 27 tabel. Daftar lengkap ada di `02_DATABASE — SisisFour.md`.

# 16. Docs Canonical

```text
docs/
├── 00_POLA_PENGERJAAN___SisisFour.md
├── 01_MASTERPLAN — SisisFour.md
├── 02_DATABASE — SisisFour.md
├── 03_AUTH_RBAC_MENU — SisisFour.md
├── 04_MASTER_DATA — SisisFour.md
├── 05_PRESENSI — SisisFour.md
├── 06_LAPORAN — SisisFour.md
├── 07_BK_PRESTASI_KARTU — SisisFour.md
├── 08_DASHBOARD_SETTINGS_BACKUP — SisisFour.md
├── 09_PROFILE — SisisFour.md
├── 15_TESTING_POLISH — SisisFour.md
├── 16_MOBILE_CORDOVA — SisisFour.md
├── Routes Final — SisisFour.md
└── Tree Structure — SisisFour.md
```

# 17. Mobile Project

Project Cordova belum berada di repo baseline.

Jika Tahap 16 dibuat, client Cordova sebaiknya dipisahkan jelas, misalnya:

```text
mobile/
├── config.xml
├── package.json
└── www/
```

atau repository mobile terpisah.

Server Web tidak boleh dicampur dengan generated `platforms/` Cordova.

# 18. Branding

Login:

```text
SisFour Dev
logo → setting_sistem.logo_sekolah
```

Footer:

```text
By : LemahTeles
```

# 19. Struktur yang Tidak Boleh Diasumsikan

Dokumen Tree hanya menyatakan file yang benar-benar tersedia.

Tidak boleh mencantumkan sebagai file aktual:

```text
Api.php
ProfileGuru.php
ProfileSiswa.php
Cordova config.xml
Cordova www/
```

sampai file tersebut benar-benar ada.

# 20. Release Structural Check

Sebelum release jalankan audit:

```text
Routes target Controller exists
Controller target Service exists
View yang dirender exists
JS asset exists
fallback Kartu exists
upload path writable
backup path writable
docs sesuai repo
```

Current structural blockers:

```text
Api::version target missing
ProfileGuru target missing
ProfileSiswa target missing
```
