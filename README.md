# SisisFour

Aplikasi manajemen madrasah terpadu untuk **MTsN 4 Jombang** — mencakup presensi siswa & guru, jurnal mengajar, bimbingan konseling (BK), pencatatan prestasi, dan kartu pelajar digital dengan verifikasi QR.

Dibangun dengan **CodeIgniter 4** (PHP 8.2), mendukung dual-output (Web + JSON API untuk mobile Cordova).

* * *

## Stack Teknis

| Komponen | Teknologi |
| --- | --- |
| Framework | CodeIgniter 4 |
| PHP | 8.2.12 |
| Database | MySQL / MariaDB |
| UI | Sneat Free (Bootstrap 5) + jQuery |
| Library | PhpSpreadsheet (Excel), Dompdf (PDF), endroid/qr-code |
| Auth Mobile | JWT (Access Token 1 jam + Refresh Token 30 hari) |

* * *

## Instalasi di Lokal

1. **Clone repository**

   ```bash
   git clone https://github.com/Al-Faroz/sisfour_dev_v2.git
   cd sisfour_dev_v2
   ```

2. **Install dependency**

   ```bash
   composer install
   ```

3. **Setup environment**

   Copy `env` menjadi `.env`, lalu sesuaikan:

   ```
   CI_ENVIRONMENT = development
   app.baseURL = 'http://localhost/sisfour_dev_v2'

   database.default.hostname = localhost
   database.default.database = sisfour_dev_v2
   database.default.username = root
   database.default.password =
   ```

4. **Import database**

   Import file SQL (lihat `docs/02_DATABASE.md` atau file dump `.sql` di root) ke MySQL/MariaDB via phpMyAdmin atau command line. Pastikan seluruh tabel, foreign key, dan seeder awal (`permissions`, `role_permissions`, `role_menus`, `mata_pelajaran`) terisi dengan benar.

5. **Jalankan server**

   ```bash
   php spark serve
   ```

6. **Akses aplikasi**

   `http://localhost:8080`

7. **Login default**

   ```
   Username: admin
   Password: admin
   ```

   *(Wajib diubah setelah login pertama.)*

* * *

## Deploy ke Hosting Produksi

1. Upload seluruh file ke hosting (root domain / `public_html`).
2. Sesuaikan `.env` untuk production:

   ```
   CI_ENVIRONMENT = production
   app.baseURL = 'https://domain-produksi.com'
   database.default.hostname = [host]
   database.default.database = [db]
   database.default.username = [user]
   database.default.password = [pass]
   ```

3. Import schema database (sama seperti langkah lokal).
4. Set permission folder writable:

   ```bash
   chmod 755 writable/
   ```

5. Pastikan `.htaccess` proteksi folder sensitif (`app/`, `writable/`, `vendor/`, `.env`) ikut ter-upload dan aktif.
6. Pastikan ekstensi PHP aktif: `intl`, `mbstring`, `zip`, `gd`, `fileinfo`, `mysqlnd`.

* * *

## Catatan Penting

- **Struktur folder:** `public/` sudah dipindahkan ke root — `index.php` dan `.htaccess` ada langsung di root project.
- **HTTPS/SSL wajib** di produksi — dibutuhkan untuk Geolocation API (geofencing) dan aplikasi mobile.
- **NIK:** 16 digit, divalidasi di form dan saat import Excel.
- **Backup database:** menggunakan SQL dump murni PHP (tanpa `exec()`/`shell_exec()`), karena hosting mematikan fungsi tersebut.
- **Testing:** `tests/` (PHPUnit, area kritis: validasi jadwal, geofencing, resolusi scope RBAC) hanya dijalankan di lokal via `php spark test`. Folder ini tidak diupload ke hosting produksi.
- **Timezone:** `Asia/Jakarta`, wajib diset di `app/Config/App.php`.

* * *

## Dokumentasi Acuan

Seluruh dokumen spesifikasi teknis (masterplan, skema database, RBAC, tiap modul, routes, dsb.) tersedia di folder `docs/`.

* * *

© 2026 SisisFour · MTsN 4 Jombang
