# 📱 Mobile App (Cordova) — SisisFour

**Versi:** 3.0 Final · **Tanggal:** 27 Agustus 2026

Dokumen ini mengatur seluruh spesifikasi untuk aplikasi **mobile** berbasis **Cordova**. Aplikasi ini diperuntukkan bagi pengguna yang membutuhkan akses cepat dan praktis di lapangan (Pimpinan, Guru, BK, Siswa), sementara tugas administratif berat tetap dikerjakan melalui **web** oleh Admin dan Operator.

**Status Eksekusi:** Fase mobile (Cordova) dikerjakan SETELAH Tahap 1–15 (web) selesai dan stabil.

* * *

## 1. Pendahuluan

- **Teknologi:** Apache Cordova (WebView) — bukan PWA. Tujuannya untuk **mengunci** cara akses (tanpa address bar) dan memberikan kesan aplikasi resmi.
- **Arsitektur:** Aplikasi mobile **online-only** (tidak ada mode offline). Semua data diambil dan disimpan langsung dari server melalui API.
- **Dual-Output:** Mengandalkan endpoint API `/api/...` yang sudah dibangun di sisi server (lihat `Routes Final`).
- **Frontend APK:** Menggunakan HTML + CSS + JavaScript (jQuery) yang sama dengan web, tetapi dijalankan dalam WebView Cordova.

* * *

## 2. Siapa yang Pakai APK?

| Platform | Role                                      | Keterangan                                                          |
|----------|-------------------------------------------|---------------------------------------------------------------------|
| APK      | **Pimpinan, Guru, Wali Kelas, BK, Siswa** | Akses mobile untuk tugas lapangan (presensi, dashboard, BK, kartu)  |
| Web-Only | **Admin &amp; Operator**                  | Kerjaan berat (master data, import, settings, backup) tetap via web |

* * *

## 3. Cakupan Modul per Role di APK

### 3.1 Guru &amp; Wali Kelas

- **Dashboard:** Jadwal hari ini (dengan status presensi), riwayat jurnal terakhir, ringkasan kelas diampu (jika wali).
- **Presensi Siswa:** Input presensi (AW/AK) dengan tombol solid default Hadir.
- **Presensi Mengajar (Jurnal):** Input jurnal untuk semua sesi (termasuk Non Sesi), dengan status Hadir/Izin/Sakit dan materi wajib.
- **Laporan Jurnal Mengajar:** Melihat jurnal milik sendiri (filter dinamis Guru→Kelas).
- **Profile Guru:** Melihat dan mengedit data diri (NIP readonly, sisanya editable).

### 3.2 Pimpinan

- **Dashboard:** 6 widget supervisi (kelas belum presensi, EWS Radar, kasus BK, tren presensi, guru belum input, Top 20).
- **Laporan Matrix:** Melihat matrix presensi (Total H|S|I|A) untuk semua kelas.
- **Laporan Jurnal Mengajar:** Melihat jurnal semua guru (filter Guru→Kelas).
- **BK (View-Only):** Melihat catatan kasus dan prestasi siswa (readonly).
- **Profile Guru:** Melihat dan mengedit data diri sendiri (NIP readonly, sisanya editable).

### 3.3 BK

- **Dashboard:** Kasus bulan ini, Top 20 poin pelanggaran, EWS Radar, prestasi terbaru.
- **Catatan Kasus:** Input dan melihat catatan kasus (dengan audit updated\_at/updated\_by).
- **Prestasi:** Input dan melihat prestasi siswa.
- **Profile Guru:** Melihat dan mengedit data diri sendiri (NIP readonly, sisanya editable).

### 3.4 Siswa

- **Dashboard:** Rekap presensi diri sendiri (bulan berjalan) dan riwayat prestasi/pelanggaran diri sendiri.
- **Kartu Pelajar:** Preview dan download kartu sendiri (depan &amp; belakang).
- **Profile Siswa:** Melihat data diri sendiri (readonly, termasuk NIK penuh).

### 3.5 Modul yang TETAP Web-Only (Tidak di APK)

- **Master Data:** CRUD Guru/Pegawai/Siswa/Kelas, Import/Export Excel, Kenaikan Kelas, Mutasi.
- **Settings:** Manajemen User, Menu &amp; Role, Setting Sistem, Maintenance.
- **Backup &amp; Log:** Backup SQL, Log Activity.

* * *

## 4. Autentikasi API — JWT

### 4.1 Spesifikasi Teknis (B4)

- **Access Token:** Berlaku **1 jam** setelah login.
- **Refresh Token:** Berlaku **30 hari**.
- **Penyimpanan:** Token disimpan di tabel `api_tokens` (MySQL) dengan hashing untuk refresh token (SHA-256).
- **Header Request:** `Authorization: Bearer {access_token}`.
- **Refresh Mechanism:** Jika access token expired, APK wajib melakukan refresh otomatis menggunakan refresh token tanpa meminta user login ulang.

### 4.2 Single Active Session (B5)

- **Mekanisme:** Menggunakan kolom `auth_version` di tabel `users`.
- **Alur:** Setiap kali user login (web maupun APK), `auth_version` di-increment. Token/session lama otomatis menjadi **invalid** karena versinya lebih rendah.
- **Dampak:** Login di device baru akan mengeluarkan user dari device lama.
- **Server Check:** Di setiap request API, server mengecek `auth_version` di token vs di database. Jika tidak match, tolak request dengan status 401 dan pesan "Session expired, please login again".

### 4.3 Endpoint Autentikasi

| Method | Endpoint            | Deskripsi                                      | Auth                        |
|--------|---------------------|------------------------------------------------|-----------------------------|
| POST   | `/api/auth/login`   | Login (return access\_token + refresh\_token)  | None                        |
| POST   | `/api/auth/logout`  | Logout (revoke token, set revoked\_at)         | JWT                         |
| GET    | `/api/auth/me`      | Ambil data user dari token                     | JWT                         |
| POST   | `/api/auth/refresh` | Refresh access token menggunakan refresh token | None (dengan refresh token) |

* * *

## 5. Endpoint API untuk Mobile

Berikut adalah daftar endpoint yang digunakan oleh aplikasi mobile. Semua endpoint **wajib** mengembalikan format JSON.

| Modul                 | Method | Endpoint                                  | Deskripsi                                           |
|-----------------------|--------|-------------------------------------------|-----------------------------------------------------|
| **Dashboard**         | GET    | `/api/dashboard/data`                     | Data widget dashboard sesuai role                   |
| **Presensi Siswa**    | GET    | `/api/presensi/siswa/input/(:segment)`    | Form input presensi (data kelas + siswa)            |
|                       | POST   | `/api/presensi/siswa/save`                | Simpan presensi (dengan geofencing jika diperlukan) |
|                       | GET    | `/api/presensi/siswa/rekap/(:segment)`    | Rekap presensi siswa (diri sendiri)                 |
| **Presensi Mengajar** | GET    | `/api/presensi/mengajar/input/(:segment)` | Form input jurnal (data jadwal)                     |
|                       | POST   | `/api/presensi/mengajar/save`             | Simpan jurnal (validasi id\_guru sendiri)           |
| **Laporan**           | GET    | `/api/laporan/presensi/matrix`            | Data matrix presensi (Total H\|S\|I\|A)             |
|                       | GET    | `/api/laporan/jurnal`                     | Data laporan jurnal (filter Guru→Kelas)             |
| **BK**                | GET    | `/api/bk/kasus`                           | Daftar catatan kasus                                |
|                       | POST   | `/api/bk/kasus/create`                    | Tambah catatan kasus                                |
| **Prestasi**          | GET    | `/api/bk/prestasi`                        | Daftar prestasi                                     |
|                       | POST   | `/api/bk/prestasi/create`                 | Tambah prestasi                                     |
| **Kartu Pelajar**     | GET    | `/api/kartu/preview/(:segment)`           | Preview kartu (depan &amp; belakang)                |
|                       | GET    | `/api/kartu/download/(:segment)`          | Download PDF kartu                                  |
| **Profile**           | GET    | `/api/profile/guru`                       | Ambil data profile guru                             |
|                       | PUT    | `/api/profile/guru`                       | Update profile guru (field terbatas, NIP readonly)  |
|                       | POST   | `/api/profile/guru/foto`                  | Upload foto profile (PNG, re-encode)                |
| **Profile Siswa**     | GET    | `/api/profile/siswa`                      | Ambil data profile siswa (readonly)                 |
| **Utility**           | GET    | `/api/version`                            | Cek versi APK (untuk mekanisme update)              |

**Catatan:** Semua endpoint di atas sudah tercantum di `Routes Final` dan dilindungi oleh filter `auth:api` (JWT).

* * *

## 6. Prasyarat Teknis Wajib

- **HTTPS/SSL di Hosting Produksi:** Geolocation API (untuk geofencing) tidak berfungsi di halaman non-HTTPS pada WebView modern.
- **Environment Build:** Untuk membangun APK, dibutuhkan **Android Studio** + Java SDK terpasang di laptop developer (environment terpisah dari XAMPP).
- **Konfigurasi Cordova:**
  
  - WebView dikonfigurasi **terkunci** (tanpa address bar, tanpa external navigation).
  - Izinkan akses ke domain server (whitelist).
  - Plugin yang diperlukan: `cordova-plugin-geolocation`, `cordova-plugin-file`, `cordova-plugin-file-transfer`.

* * *

## 7. Distribusi &amp; Update APK

- **Fase Testing (Development):** Install manual di beberapa device (sideload APK langsung, mode developer testing).
- **Fase Final (Rilis Resmi):** Publikasi melalui **Google Play Store** (memerlukan akun developer Google dan proses review).
- **Mekanisme Update:**
  
  1. APK melakukan pengecekan ke endpoint `/api/version` secara berkala (atau saat dibuka).
  2. Jika versi di server **lebih baru** dari versi APK, tampilkan notifikasi "Ada update tersedia".
  3. User diarahkan ke **Google Play Store** untuk melakukan update.

**Catatan:** Tidak ada mekanisme update otomatis dalam APK (forced update). Semua update melalui Play Store.

* * *

## 8. Push Notification &amp; Mode Offline — LOCKED

### 8.1 Push Notification

- TIDAK ADA push notification.
- Pengguna membuka aplikasi sendiri saat membutuhkan (cek jadwal, input presensi).

### 8.2 Mode Offline

- ONLINE-ONLY — tidak ada penyimpanan lokal/queue sync untuk input presensi saat offline.
- Jika tidak ada koneksi internet, input akan gagal dengan pesan error jelas.

* * *

## 9. Checklist Selesai Tahap 16

- \[ ] Tahap 1–15 (web) sudah selesai &amp; stabil.
- \[ ] Seluruh endpoint API JSON untuk modul APK telah dibangun dan teruji dengan Postman.
- \[ ] JWT auth + tabel `api_tokens` berfungsi, single-session ter-enforce via `auth_version`.
- \[ ] Endpoint `/api/auth/refresh` berfungsi untuk refresh token otomatis.
- \[ ] SSL aktif di hosting produksi, geofencing teruji jalan dari HTTPS.
- \[ ] Project Cordova diinisialisasi, WebView dikonfigurasi terkunci (no address bar, no external navigation).
- \[ ] APK berhasil di-build dan diuji sideload di beberapa device Android nyata (fase testing).
- \[ ] Mekanisme cek-versi (`/api/version`) + banner "Ada update tersedia" berfungsi.
- \[ ] Publikasi ke Google Play Store (fase final/rilis).

* * *

## 10. Catatan Penting untuk Developer

- **Dual-Output sudah siap:** Controller di sisi server sudah mendukung `?format=json` dan endpoint API dengan prefix `/api`.
- **JWT Lifecycle (B4):** Access token 1 jam, refresh token 30 hari. Jika access token expired, APK wajib melakukan refresh otomatis tanpa meminta user login ulang.
- **Single Active Session (B5):** Pastikan di setiap request API, server mengecek `auth_version` di token vs di database. Jika tidak match, tolak request dengan status 401 dan pesan "Session expired, please login again".
- **Geofencing:** Untuk fitur presensi, APK wajib meminta izin lokasi dan mengirimkan koordinat device ke server. Server akan memvalidasi menggunakan Haversine.
- **File Download:** Download PDF Kartu Pelajar di APK menggunakan `cordova-plugin-file` untuk menyimpan file ke storage device.
- **NIK Masking:** Di verifikasi publik (jika diakses dari APK), NIK tetap dimasking (format: 351012xxxxxx1234).
- **Refresh Token Security:** Refresh token disimpan di database dalam bentuk hash (SHA-256) untuk keamanan tambahan.
- **Error Handling:** Jika API response 401 (unauthorized), APK wajib redirect ke halaman login (token expired/invalid).

* * *

© 2026 SisisFour · MTsN 4 Jombang · Mobile App Final
