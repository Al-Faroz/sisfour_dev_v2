# 🛠️ Pola Pengerjaan — SisisFour

**Versi:** 1.0 · **Tanggal:** 29 Agustus 2026

Dokumen ini adalah catatan kerja (working order) untuk pengembangan SisisFour. Prinsip utama: **fondasi dikerjakan horizontal (sekali jalan)**, **fitur dikerjakan vertikal per modul** dengan urutan internal `Model → Service → Filter → Controller → View`, dan **setiap modul wajib dites (checkpoint) sebelum lanjut ke modul berikutnya**.

> Jangan lanjut ke tahap berikutnya sebelum checkpoint tahap saat ini disetujui.

* * *

## Fase 0 — Fondasi (Horizontal, Sekali Jalan)

Dikerjakan berurutan, tidak perlu dites per fitur karena sifatnya struktural/statis.

| # | Langkah | Detail | Acuan Dokumen |
| --- | --------- | -------- | ---------------- |
| 1 | **Setup CI4** | Install via Composer, pasang plugin (PhpSpreadsheet, Dompdf, endroid/qr-code), pindah isi `public/` ke root, setting `.env` (timezone `Asia/Jakarta`), sampai halaman welcome CI4 muncul di localhost | `01_MASTERPLAN` §5, Tahap 1 |
| 2 | **Assets** | Buat `assets/css`, `assets/js`, `assets/vendor` (Bootstrap 5, jQuery, DataTables, Select2, SweetAlert2), `assets/fonts` (Poppins untuk Dompdf), `assets/img` | `Tree Structure` |
| 3 | **Uploads** | Buat `uploads/.htaccess` (blokir eksekusi script), `foto_siswa/`, `foto_guru/`, `branding/`, `kartu_pelajar/background_depan/`, `kartu_pelajar/background_belakang/` | `01_MASTERPLAN` §5 |
| 4 | **Database** | Jalankan seluruh SQL dari `02_DATABASE` (tabel, FK, index, seeder mapel 17, permission 43, role_permissions, role_menus, admin default) | Tahap 2 |
| 5 | **Config** | Isi `app/Config/App.php`, `Database.php`, `Routes.php` (162 route dari `Routes Final`), `Filters.php`, `Session.php` | `Routes Final` |

### ✅ Checkpoint Fase 0

- [ ] Bisa akses aplikasi tanpa `/public/` di URL, `.htaccess` proteksi aktif
- [ ] SQL berhasil dijalankan, FK constraint aktif
- [ ] 43 permission + seeder `role_permissions`/`role_menus` terisi **benar**:
  - [ ] Tidak ada permission `.manage` untuk Pimpinan
  - [ ] BK mendapat `prestasi.view`
  - [ ] Guru mendapat `jadwal_guru.view` (bukan `.manage`)

* * *

## Fase 1 — Per Modul (Vertikal, Ikuti Urutan Tahap)

**Urutan internal wajib per modul:**

```
Model(s) → Service(s) → Filter (jika baru) → Controller(s) → View(s) → TES MODUL INI
```

Alasan urutan ini (bukan Controller dulu): Controller memanggil method dari Service (`01_MASTERPLAN` §7), dan `PermissionFilter` memanggil `AuthService::resolveScope()` (`03_AUTH_RBAC_MENU` §3.1). Jika Controller/Filter ditulis sebelum Service ada, akan terjadi fatal error saat dites.

### Tahap 3 — Auth + RBAC

- **Model:** `UserModel`, `UserRolesModel`, `LoginAttemptsModel`, `ApiTokensModel`
- **Service:** `AuthService` (login, resolveScope, isWaliKelas), `PermissionService`
- **Filter:** `AuthFilter`, `PermissionFilter`
- **Controller:** `Auth`
- **View:** `auth_login.php`
- **Tes:** Login, rate limiting 5x gagal → lock 5 menit, `PermissionFilter` jalan, scope resolver benar, single active session (`auth_version`), Pimpinan hanya dapat akses view

### Tahap 4 — Master Data

- **Model:** `GuruModel`, `PegawaiModel`, `SiswaModel`, `RiwayatSiswaModel`, `KelasModel`, `TahunAjaranModel`, `MataPelajaranModel`, `AnggotaKelasModel`, `MappingWaliKelasModel`, `JadwalGuruModel`
- **Service:** `KelasService`, `JadwalGuruService`, `MappingWaliService`, `UploadService`
- **Controller:** `MasterGuru`, `MasterPegawai`, `MasterSiswa`, `MasterKelas`, `MasterTahunAjaran`, `MasterMapel`, `MappingWaliKelas`, `JadwalGuru`
- **View:** `master_*_list.php`, `mapping_wali_list.php`, `jadwal_guru_list.php`
- **Tes:** CRUD semua entitas, import/export Excel, kenaikan kelas (checklist default semua), mutasi siswa, histori (A4), validasi NIK 16 digit, restore wali kelas (bukan insert baru), validasi bentrok jadwal

### Tahap 5 — Presensi Siswa

- **Model:** `PresensiModel`
- **Service:** `PresensiService`, `GeofencingService`
- **Controller:** `PresensiSiswa`
- **View:** `presensi_siswa_index.php`, `presensi_siswa_input.php`, `presensi_siswa_ews.php`, `presensi_siswa_rekap.php`
- **Tes:** Input AW/AK (tombol solid default Hadir), revisi oleh Wali/Admin/Operator (bebas time-window), EWS Radar (≥3 Alpha/14 hari), geofencing Haversine 500m

### Tahap 6 — Presensi Mengajar (Jurnal)

- **Model:** `PresensiMengajarModel`
- **Service:** `PresensiMengajarService`
- **Controller:** `PresensiMengajar`
- **View:** `presensi_mengajar_index.php`, `presensi_mengajar_input.php`, `presensi_mengajar_laporan.php`
- **Tes:** Input semua sesi (termasuk Non Sesi), validasi `id_guru` = session sendiri, revisi hanya Admin/Operator, materi wajib untuk semua status

### Tahap 7 — Laporan & Export

- **Model:** (pakai Model yang sudah ada)
- **Service:** `LaporanPresensiService`
- **Controller:** `LaporanPresensi`, `LaporanJurnal`
- **View:** `laporan_presensi_matrix.php`, `laporan_presensi_export.php`, `laporan_jurnal_index.php`
- **Tes:** Matrix (Total H\|S\|I\|A dari Sesi Awal saja), Export Bulanan/Semester, Laporan Jurnal filter dinamis Guru→Kelas

### Tahap 8 — BK & Prestasi

- **Model:** `RefPelanggaranModel`, `CatatanKasusModel`, `CatatanPrestasiModel`
- **Service:** logic ringan, boleh langsung di Model/Controller
- **Controller:** `BKKasus`, `BKPelanggaran`, `BKPrestasi`
- **View:** `bk_kasus_list.php`, `bk_kasus_form.php`, `bk_pelanggaran_list.php`, `bk_prestasi_list.php`
- **Tes:** Audit `updated_at`/`updated_by` pada catatan kasus, master pelanggaran CRUD, prestasi, Top 20 poin, BK dapat akses `/bk/prestasi`

### Tahap 9 — Dashboard

- **Service:** `MenuService` (jika belum ada)
- **Controller:** `Dashboard`
- **View:** `dashboard_admin.php`, `dashboard_operator.php`, `dashboard_pimpinan.php`, `dashboard_bk.php`, `dashboard_guru.php`, `dashboard_wali.php`, `dashboard_siswa.php`
- **Tes:** Semua widget tampil sesuai role & scope, Pimpinan melihat EWS Radar (widget-only, tanpa menu)

### Tahap 10 — Kartu Pelajar

- **Model:** `KartuPelajarModel`
- **Service:** `KartuPelajarService`
- **Controller:** `KartuPelajar`
- **View:** `kartu_pelajar_daftar.php`, `kartu_pelajar_generate.php`, `kartu_pelajar_preview.php`, `kartu_pelajar_verify.php` (publik)
- **Tes:** Bulk generate, cetak massal per kelas (2×5/A4), QR verifikasi publik, masking NIK (`351012xxxxxx1234`), preview/download, reissue tanpa ubah nomor

### (Sisipan) Profile — bisa setelah Tahap 4

- **Controller:** `ProfileGuru`, `ProfileSiswa`
- **View:** `profile_guru_view.php`, `profile_siswa_view.php`
- **Tes:** Guru edit data diri (NIP readonly), Siswa readonly penuh (termasuk NIK)

### Tahap 12 — Settings

- **Model:** `SettingSistemModel`
- **Controller:** `SettingsUser`, `SettingsMenu`, `SettingsSistem`
- **View:** `settings_user_index.php`, `settings_menu_index.php`, `settings_sistem_index.php`
- **Tes:** Reset password (NIP/NISN/username), Menu & Role, Setting Sistem (validasi `type`), Maintenance Mode (kecuali Admin)

### Tahap 13 — Geofencing (Integrasi Ulang & Verifikasi)

- Cek ulang integrasi Haversine di Presensi Siswa & Jurnal
- **Tes:** Toggle OFF berfungsi, radius 500m akurat, hanya guru mapel dengan status Hadir yang terkunci geofencing

### Tahap 14 — Backup & Log

- **Model:** `LogActivityModel`
- **Service:** `BackupService`, `LogActivityService`
- **Controller:** `Backup`, `LogActivity`
- **View:** `backup_index.php`, `log_activity_index.php`
- **Tes:** SQL dump PHP murni (tanpa exec/shell), download backup, retensi log 3 tahun

> **Tahap 11 (Cetak Form & Surat) DIHAPUS dari lingkup — dilewati.**

* * *

## Fase 2 — Automated Test (PHPUnit, Paralel)

Ditulis begitu Service terkait selesai — **jangan tunggu sampai akhir proyek**.

| Area | Service | Kapan Ditulis |
| ------ | --------- | ---------------- |
| Validasi bentrok jadwal | `JadwalGuruService` | Setelah Tahap 4 |
| Perhitungan geofencing | `GeofencingService` | Setelah Tahap 5 |
| Resolusi scope RBAC | `AuthService::resolveScope()` | Setelah Tahap 3 |

Jalankan `php spark test` secara rutin di lokal. Folder `tests/` tidak diupload ke produksi.

* * *

## Fase 3 — Mobile (Cordova)

**Hanya dimulai setelah Tahap 1–15 (web) selesai dan stabil** (`16_MOBILE_CORDOVA` §1).

1. Setup project Cordova, konfigurasi WebView terkunci (no address bar)
2. Integrasi endpoint API (`/api/...`) yang sudah ada
3. Implementasi JWT (access 1 jam, refresh 30 hari) + auto-refresh
4. Plugin: `cordova-plugin-geolocation`, `cordova-plugin-file`, `cordova-plugin-file-transfer`
5. Build & sideload testing di device Android nyata
6. Mekanisme cek versi (`/api/version`)

* * *

## Fase 4 — Testing & Polish Akhir (Tahap 15)

- [ ] Semua modul (kecuali Cetak Form & Surat) lolos manual testing per tahap
- [ ] 3 area PHPUnit lolos secara lokal
- [ ] README.md instalasi & deploy lengkap, dicoba dari nol
- [ ] Tidak ada error di console browser / `writable/logs/`
- [ ] Performa terjaga (DataTables client-side, export selaras filter)
- [ ] Geofencing teruji indoor/outdoor + toggle OFF
- [ ] Backup SQL berhasil digenerate & didownload
- [ ] JWT teruji dengan Postman
- [ ] Validasi NIK 16 digit (form & import)
- [ ] Masking NIK di verifikasi publik berfungsi
- [ ] Wali Kelas bisa edit biodata siswa kelas diampu
- [ ] Menu Data Guru disembunyikan untuk guru & siswa
- [ ] PermissionFilter bekerja di semua grup route
- [ ] Pimpinan tidak punya akses manage/edit di semua modul

* * *

## Ringkasan Alur

```
Fase 0 (Fondasi)
   └─ Setup → Assets → Uploads → Database → Config
        ↓
Fase 1 (Per Modul — vertikal, checkpoint tiap tahap)
   └─ Tahap 3 → 4 → 5 → 6 → 7 → 8 → 9 → 10 → 12 → 13 → 14
        (Model → Service → Filter → Controller → View → TES)
        ↓
Fase 2 (PHPUnit — paralel, mengikuti Service terkait)
        ↓
Fase 3 (Mobile Cordova — setelah web stabil)
        ↓
Fase 4 (Testing & Polish akhir)
```

* * *

© 2026 SisisFour · MTsN 4 Jombang · Catatan Pola Pengerjaan
