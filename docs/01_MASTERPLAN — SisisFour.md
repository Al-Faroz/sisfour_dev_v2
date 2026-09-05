# Masterplan SisisFour — MTsN 4 Jombang

**Versi:** 4.0 Final · **Tanggal:** 05 September 2026

* * *

## 1. Pendahuluan

SisisFour adalah aplikasi manajemen madrasah terpadu yang mencakup presensi siswa, jurnal mengajar guru, bimbingan konseling (BK), pencatatan prestasi, dan kartu pelajar digital untuk MTsN 4 Jombang. Sistem dibangun dari nol menggunakan CodeIgniter 4 dengan pendekatan arsitektur yang bersih dan aman.

### 1.1 Tujuan

- Menyediakan sistem informasi terpadu untuk seluruh warga madrasah.
- Menggantikan sistem lama yang sudah tidak memadai dengan arsitektur yang modern dan terukur.
- Mendukung dual-output (HTML + JSON) untuk kebutuhan web dan mobile (Cordova).
- Menjamin integritas data dan keamanan akses melalui sistem RBAC yang ketat.

### 1.2 Lingkup Proyek

| Dalam Lingkup                                                           | Di Luar Lingkup        |
|-------------------------------------------------------------------------|------------------------|
| Aplikasi web full-featured (Admin, Operator, Pimpinan, BK, Guru, Siswa) | Push notification      |
| Aplikasi mobile (Cordova) untuk Guru, Pimpinan, BK, Siswa               | Mode offline           |
| Manajemen data master (guru, pegawai, siswa, kelas, tahun ajaran)       | Integrasi Dapodik/EMIS |
| Presensi siswa &amp; guru, jurnal mengajar, BK, prestasi                |                        |
| Kartu pelajar digital dengan verifikasi QR                              |                        |
| Laporan &amp; ekspor data (Excel/PDF)                                   |                        |
| Dashboard per role &amp; sistem pendukung keputusan (EWS Radar)         |                        |

* * *

## 2. Stack Teknis

| Komponen              | Teknologi                                                         |
|-----------------------|-------------------------------------------------------------------|
| Framework             | CodeIgniter 4 (Composer, versi stabil terbaru)                    |
| PHP                   | 8.2.12                                                            |
| Database              | MySQL (XAMPP lokal / Produksi)                                    |
| UI                    | Sneat Free (Bootstrap 5) + jQuery                                 |
| Library Frontend      | DataTables (Server-side/Client-side hybrid), Select2, SweetAlert2 |
| Composer              | PhpSpreadsheet (Excel), Dompdf (PDF), endroid/qr-code             |
| Session               | Database session (`ci_sessions`)                                  |
| Backup                | SQL dump murni PHP (tanpa exec/shell)                             |
| Authentication Mobile | JWT (1 jam expiry + Refresh Token 30 hari)                        |

* * *

## 3. Spesifikasi Hosting Produksi

- CPU: 1 core, RAM: 2GB
- `max_execution_time`: 360 detik
- `memory_limit`: 1536MB
- `exec()` / `shell_exec()`: **disabled** (backup pakai SQL dump PHP murni)
- **Timezone:** `Asia/Jakarta` (wajib diset di `app/Config/App.php`)

**Catatan:** Cetak massal Kartu Pelajar dibatasi per kelas, import Excel bertahap per kelas untuk menghindari memory limit.

* * *

## 4. Standar UI/UX

- **Tabel data:** Menggunakan DataTables. Untuk data master kecil (&lt; 500 row) menggunakan client-side. Untuk data transaksi besar (presensi, log, riwayat) menggunakan server-side processing.
- **Responsive:** Plugin **Responsive** aktif (collapse ke tombol `+` di layar kecil).
- **Filter + Export selaras:** Export WAJIB mengikuti filter aktif di layar.
- **Frontend:** Menggunakan jQuery karena terintegrasi penuh dengan template Sneat.

* * *

## 5. Struktur Folder Final

```
sisfour_dev/                          (root project)
├── index.php                         (front controller CI4)
├── .htaccess                         (proteksi app/, writable/, vendor/, .env)
├── assets/                           (aset statis tema Sneat)
│   ├── css/
│   ├── js/
│   ├── vendor/
│   └── img/
├── uploads/                          (upload dinamis — TIDAK ikut Git)
│   ├── .htaccess                     (blokir eksekusi script)
│   ├── foto_siswa/
│   ├── foto_guru/
│   ├── branding/
│   └── kartu_pelajar/
│       ├── background_depan/
│       └── background_belakang/
├── app/                              (dilindungi .htaccess root)
│   ├── Controllers/
│   ├── Models/
│   ├── Views/
│   ├── Filters/
│   │   ├── AuthFilter.php
│   │   ├── PermissionFilter.php     (Resolve scope & cek akses otomatis)
│   │   └── MaintenanceFilter.php
│   └── Services/
│       ├── AuthService.php
│       ├── PermissionService.php    (resolveScope)
│       ├── KelasService.php         (kenaikan kelas, mutasi, lulus)
│       ├── JadwalGuruService.php    (validasi bentrok, import)
│       ├── GeofencingService.php    (Haversine)
│       ├── PresensiService.php
│       ├── KartuPelajarService.php
│       └── ...
├── writable/
│   └── backups/                      (hasil backup SQL)
├── vendor/
└── docs/                             (dokumen acuan final)
    
```

* * *

## 6. Konvensi Penamaan

| Jenis file   | Konvensi             | Contoh                                |
|--------------|----------------------|---------------------------------------|
| View         | `{modul}_{aksi}.php` | `presensi_siswa_input.php`            |
| Layout utama | `main.php`           | `main.php`                            |
| Partial      | `_{nama}.php`        | `_sidebar.php`, `_header.php`         |
| Controller   | PascalCase per modul | `MasterGuru.php`, `PresensiSiswa.php` |
| Model        | `{Nama}Model.php`    | `GuruModel.php`                       |
| Service      | `{Nama}Service.php`  | `GeofencingService.php`               |

* * *

## 7. Dual-Output (HTML + JSON)

- Controller untuk modul: Presensi Siswa, Presensi Mengajar, Laporan, BK, Dashboard, Kartu Pelajar, Profile harus mendukung `?format=json`.
- Business logic ada di **Service**, bukan langsung di Controller, untuk memudahkan pemeliharaan dan testing.

* * *

## 8. Role yang Didukung

| Role       | Basis Identitas             | Catatan                                             |
|------------|-----------------------------|-----------------------------------------------------|
| Admin      | Berdiri sendiri             | Full access seluruh sistem.                         |
| Operator   | `id_pegawai`/`id_guru`     | Wajib terhubung ke `data_pegawai`/`pegawai`, kecuali admin awal. Memiliki kewenangan administratif penuh. |
| Pimpinan   | `id_pegawai`/`id_guru`     | Read-only untuk data operasional; boleh manage Kartu Pelajar sesuai kewenangan yang ditetapkan. |
| BK         | `id_guru`                   | Mengelola BK & Prestasi; tidak memiliki kewajiban Presensi Mengajar. |
| Guru       | `id_guru`                   | Guru Biasa hanya input presensi sesuai jadwal dan jurnal diri sendiri. |
| Wali Kelas | `id_guru`                   | **BUKAN role.** Status dinamis dari `mapping_wali_kelas`; mendapat tambahan akses khusus hanya untuk kelas walinya. |
| Siswa      | `id_siswa`                  | Akses data diri, prestasi diri, kartu, dan rincian presensi S/I/A diri sendiri. |

**Multi-Role:** Satu user dapat memiliki beberapa role melalui tabel `user_roles` (misal Guru merangkap Operator).

* * *

## 9. Arsitektur Keamanan &amp; Autentikasi

- **Web:** Menggunakan `ci_sessions` (database) dengan timeout 2 jam.
- **Mobile (APK):** Menggunakan JWT dengan masa berlaku 1 jam dan refresh token 30 hari.
- **Single Active Session:** Didukung oleh kolom `auth_version` di tabel `users`. Setiap login baru akan meng-increment versi, sehingga semua token/session lama otomatis invalid.
- **Rate Limiting:** 5 kali percobaan login gagal berturut-turut akan mengunci akun selama 5 menit (berbasis username).
- **Password:** Default menggunakan NIP/NISN, di-hash dengan bcrypt.
- **RBAC:** Menggunakan `PermissionFilter` yang dipasang di setiap grup route untuk mengecek `permission_key` dan `scope` secara otomatis.

* * *

## 10. Dokumen Acuan Lainnya

| \# | Dokumen                        | Isi                                         |
|----|--------------------------------|---------------------------------------------|
| 2  | `02_DATABASE`                  | SQL Murni (CREATE TABLE, INSERT, FK, INDEX) |
| 3  | `03_AUTH_RBAC_MENU`            | Auth, RBAC, Menu, PermissionFilter          |
| 4  | `04_MASTER_DATA`               | Master Data, Wali Kelas, Jadwal, Service    |
| 5  | `05_PRESENSI`                  | Presensi Siswa + Jurnal + Geofencing        |
| 6  | `06_LAPORAN`                   | Laporan &amp; Export (Total H\|S\|I\|A)     |
| 7  | `07_BK_PRESTASI_KARTU`         | BK, Prestasi, Kartu Pelajar                 |
| 8  | `08_DASHBOARD_SETTINGS_BACKUP` | Dashboard, Settings, Backup                 |
| 9  | `09_PROFILE`                   | Profile Guru &amp; Siswa                    |
| 10 | `15_TESTING_POLISH`            | Testing &amp; Polish                        |
| 11 | `16_MOBILE_CORDOVA`            | Mobile App (Cordova)                        |
| 12 | `Routes Final`                 | Daftar route lengkap                        |
| 13 | `Tree Structure`               | Struktur folder final                       |

* * *

© 2026 SisisFour · MTsN 4 Jombang · Masterplan Final
