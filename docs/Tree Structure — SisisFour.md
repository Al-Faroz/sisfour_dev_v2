# 🌳 Tree Structure — SisisFour

**Versi:** 3.1 Final · **Tanggal:** 28 Agustus 2026

Struktur folder proyek SisisFour berdasarkan CodeIgniter 4 dengan pendekatan **public/ dipindahkan ke root** (tanpa folder public terpisah). Seluruh file dan folder dijelaskan sesuai dengan keputusan arsitektur final, dengan 43 permission, 25 controller modul, dan 14 service.

25

Controllers

22

Models

45+

Views

3

Filters

14

Services

110+

Total File

sisfour\_dev/ ├── index.php (Front Controller CI4) ├── .htaccess (Proteksi folder sensitif) ├── .env (Konfigurasi environment) ├── composer.json (Dependency: CI4, PhpSpreadsheet, Dompdf, QR) ├── README.md (Instalasi &amp; deploy) │ ├── app/ (Aplikasi inti) │ ├── Config/ │ │ ├── App.php (Timezone: Asia/Jakarta, baseURL) │ │ ├── Database.php (Koneksi DB) │ │ ├── Routes.php (Semua route final) │ │ ├── Filters.php (Daftar filter: auth, permission, maintenance) │ │ ├── Session.php (Database session) │ │ └── (konfigurasi CI4 lainnya) │ │ │ ├── Controllers/ │ │ ├── BaseController.php │ │ ├── Auth.php │ │ ├── Dashboard.php │ │ │ │ │ ├── MasterGuru.php │ │ ├── MasterPegawai.php │ │ ├── MasterSiswa.php │ │ ├── MasterKelas.php │ │ ├── MasterTahunAjaran.php │ │ ├── MasterMapel.php │ │ ├── MappingWaliKelas.php │ │ ├── JadwalGuru.php │ │ │ │ │ ├── PresensiSiswa.php │ │ ├── PresensiMengajar.php │ │ │ │ │ ├── LaporanPresensi.php │ │ ├── LaporanJurnal.php │ │ │ │ │ ├── BKKasus.php │ │ ├── BKPelanggaran.php │ │ ├── BKPrestasi.php │ │ │ │ │ ├── KartuPelajar.php │ │ │ │ │ ├── ProfileGuru.php │ │ ├── ProfileSiswa.php │ │ │ │ │ ├── SettingsUser.php │ │ ├── SettingsMenu.php │ │ ├── SettingsSistem.php │ │ │ │ │ ├── Backup.php │ │ └── LogActivity.php │ │ │ ├── Models/ │ │ ├── UserModel.php │ │ ├── UserRolesModel.php (E6: Many-to-Many) │ │ ├── ApiTokensModel.php (B4: JWT) │ │ ├── LoginAttemptsModel.php (B3: Rate limiting) │ │ ├── GuruModel.php │ │ ├── PegawaiModel.php │ │ ├── SiswaModel.php │ │ ├── RiwayatSiswaModel.php (A4: Histori) │ │ ├── KelasModel.php │ │ ├── TahunAjaranModel.php (A7: semester) │ │ ├── MataPelajaranModel.php │ │ ├── AnggotaKelasModel.php │ │ ├── MappingWaliKelasModel.php │ │ ├── JadwalGuruModel.php │ │ ├── PresensiModel.php │ │ ├── PresensiMengajarModel.php │ │ ├── RefPelanggaranModel.php │ │ ├── CatatanKasusModel.php (C1: updated\_at/updated\_by) │ │ ├── CatatanPrestasiModel.php │ │ ├── KartuPelajarModel.php │ │ ├── SettingSistemModel.php (F1: type column) │ │ └── LogActivityModel.php (C4: retensi 3 tahun) │ │ │ ├── Views/ │ │ ├── (Layout) │ │ │ ├── main.php │ │ │ ├── \_header.php │ │ │ ├── \_sidebar.php (Dibangun dari menu + permission) │ │ │ ├── \_navbar.php │ │ │ ├── \_footer.php │ │ │ ├── \_scripts.php │ │ │ └── \_flash.php │ │ │ │ │ ├── auth\_login.php │ │ │ │ │ ├── (Dashboard) │ │ │ ├── dashboard\_admin.php │ │ │ ├── dashboard\_operator.php │ │ │ ├── dashboard\_pimpinan.php │ │ │ ├── dashboard\_bk.php │ │ │ ├── dashboard\_guru.php │ │ │ ├── dashboard\_wali.php │ │ │ └── dashboard\_siswa.php │ │ │ │ │ ├── (Master Data) │ │ │ ├── master\_guru\_list.php │ │ │ ├── master\_pegawai\_list.php │ │ │ ├── master\_siswa\_list.php │ │ │ ├── master\_kelas\_list.php │ │ │ ├── master\_tahun\_list.php │ │ │ ├── master\_mapel\_list.php │ │ │ ├── mapping\_wali\_list.php │ │ │ └── jadwal\_guru\_list.php │ │ │ │ │ ├── (Presensi) │ │ │ ├── presensi\_siswa\_index.php (Pilih kelas) │ │ │ ├── presensi\_siswa\_input.php (Tombol solid default Hadir) │ │ │ ├── presensi\_siswa\_ews.php │ │ │ ├── presensi\_siswa\_rekap.php │ │ │ ├── presensi\_mengajar\_index.php (Pilih jadwal) │ │ │ ├── presensi\_mengajar\_input.php (Status + Materi wajib) │ │ │ └── presensi\_mengajar\_laporan.php (Filter dinamis) │ │ │ │ │ ├── (Laporan) │ │ │ ├── laporan\_presensi\_matrix.php (Total H|S|I|A) │ │ │ ├── laporan\_presensi\_export.php (Per Bulan/Semester) │ │ │ └── laporan\_jurnal\_index.php (Filter Guru→Kelas) │ │ │ │ │ ├── (BK &amp; Prestasi) │ │ │ ├── bk\_kasus\_list.php │ │ │ ├── bk\_kasus\_form.php │ │ │ ├── bk\_pelanggaran\_list.php │ │ │ └── bk\_prestasi\_list.php │ │ │ │ │ ├── (Kartu Pelajar) │ │ │ ├── kartu\_pelajar\_daftar.php │ │ │ ├── kartu\_pelajar\_generate.php │ │ │ ├── kartu\_pelajar\_preview.php │ │ │ └── kartu\_pelajar\_verify.php (Publik) │ │ │ │ │ ├── (Profile) │ │ │ ├── profile\_guru\_view.php │ │ │ └── profile\_siswa\_view.php (Readonly) │ │ │ │ │ ├── (Settings) │ │ │ ├── settings\_user\_index.php │ │ │ ├── settings\_menu\_index.php │ │ │ └── settings\_sistem\_index.php │ │ │ │ │ ├── (Backup &amp; Log) │ │ │ ├── backup\_index.php │ │ │ └── log\_activity\_index.php │ │ │ │ │ └── coming\_soon.php │ │ │ ├── Filters/ │ │ ├── AuthFilter.php (Cek session) │ │ ├── PermissionFilter.php (Cek role\_permissions + scope) │ │ └── MaintenanceFilter.php (Maintenance mode, kecuali Admin) │ │ │ └── Services/ │ ├── AuthService.php (Login, JWT, resolveScope, isWali) │ ├── PermissionService.php (Resolve scope) │ ├── MenuService.php (Bangun menu tree) │ ├── KelasService.php (Kenaikan kelas, lulus, mutasi) │ ├── JadwalGuruService.php (Import, validasi bentrok) │ ├── MappingWaliService.php (Assign, restore, cek status) │ ├── GeofencingService.php (Haversine) │ ├── PresensiService.php (EWS, time-window, AW/AK) │ ├── PresensiMengajarService.php │ ├── KartuPelajarService.php (Generate, cetak, QR) │ ├── LaporanPresensiService.php (Matrix, Export H|S|I|A) │ ├── BackupService.php (SQL dump PHP murni) │ ├── LogActivityService.php (Catat log, retensi) │ └── UploadService.php (Re-encode PNG, crop 3:4) │ ├── writable/ │ ├── backups/ (File .sql) │ ├── logs/ │ ├── session/ │ └── cache/ │ ├── uploads/ (Dinamis, TIDAK ikut Git) │ ├── .htaccess (Blokir eksekusi script) │ ├── foto\_siswa/ │ ├── foto\_guru/ │ ├── branding/ (logo, icon) │ └── kartu\_pelajar/ │ ├── background\_depan/ │ └── background\_belakang/ │ ├── assets/ (Aset statis Sneat) │ ├── css/ │ ├── js/ │ ├── vendor/ (Bootstrap, jQuery, DataTables, Select2) │ ├── fonts/ (Poppins untuk Dompdf) │ └── img/ │ └── docs/ (Dokumentasi final) ├── 01\_MASTERPLAN.html ├── 02\_DATABASE.html ├── 03\_AUTH\_RBAC\_MENU.html ├── 04\_MASTER\_DATA.html ├── 05\_PRESENSI.html ├── 06\_LAPORAN.html ├── 07\_BK\_PRESTASI\_KARTU.html ├── 08\_DASHBOARD\_SETTINGS\_BACKUP.html ├── 09\_PROFILE.html ├── 15\_TESTING\_POLISH.html ├── 16\_MOBILE\_CORDOVA.html ├── Routes Final.html └── Tree Structure.html (Dokumen ini)

■ Folder ■ File Penting / Highlight ■ File Biasa Total File: **110+** | Total Folder: **25+**

* * *

## 📋 Ringkasan per Kategori

| Kategori            | Jumlah | Keterangan                                                                                                                                     |
|---------------------|--------|------------------------------------------------------------------------------------------------------------------------------------------------|
| **Controllers**     | 25     | Semua controller modul (Auth, Dashboard, Master, Presensi, Laporan, BK, Kartu, Profile, Settings, Backup, Log). BaseController tidak dihitung. |
| **Models**          | 22     | Semua tabel di database + tabel relasi (user\_roles, api\_tokens, riwayat\_siswa)                                                              |
| **Views (Layout)**  | 7      | main.php + 6 partial (\_header, \_sidebar, \_navbar, \_footer, \_scripts, \_flash)                                                             |
| **Views (Halaman)** | 38+    | Semua halaman per modul (dashboard, master, presensi, laporan, bk, kartu, profile, settings, backup, log)                                      |
| **Filters**         | 3      | AuthFilter (session), PermissionFilter (RBAC), MaintenanceFilter                                                                               |
| **Services**        | 14     | Business logic utama (Auth, Permission, Menu, Kelas, Jadwal, Wali, Geofencing, Presensi, Jurnal, Kartu, Laporan, Backup, Log, Upload)          |
| **TOTAL ESTIMASI**  | 110+   | Termasuk semua file di atas + 13 dokumen acuan di folder docs/                                                                                 |

* * *

## 📌 Catatan Penting tentang Struktur

- **public/ dipindahkan ke root:** `index.php` dan `.htaccess` berada di root project untuk menghilangkan `/public` dari URL.
- **Proteksi Folder:** `.htaccess` di root memblokir akses ke `app/`, `writable/`, `vendor/`, dan `.env`.
- **Uploads:** Folder `uploads/` tidak ikut Git (dinamis), dengan `.htaccess` internal untuk memblokir eksekusi script PHP.
- **Services:** Semua business logic ditempatkan di `app/Services/`, bukan di Controller, untuk memudahkan pemeliharaan dan testing.
- **Views:** Mengikuti konvensi `{modul}_{aksi}.php`. Layout menggunakan `main.php` dengan partials.
- **Assets:** Menggunakan Sneat Free + jQuery (sesuai keputusan E7).
- **Dokumentasi:** Seluruh dokumen acuan (01–16, Routes, Tree) disimpan di folder `docs/` sebagai referensi final.
- **NIK &amp; NISN:** NIK (16 digit) adalah identifier siswa, NISN digunakan sebagai basis login.
- **Permission &amp; Menu:** 43 permission final dengan mapping Wali Kelas yang jelas. Pimpinan hanya memiliki permission .view dan .view\_all (tidak ada .manage). Menu Data Guru disembunyikan untuk Guru/Wali.

* * *

© 2026 SisisFour · MTsN 4 Jombang · Tree Structure Final
