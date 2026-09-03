# 🧪 Testing &amp; Polish — SisisFour

**Versi:** 3.1 Final · **Tanggal:** 28 Agustus 2026

Dokumen ini mengatur strategi pengujian, checkpoint per tahap pengembangan, serta dokumentasi akhir (**README.md**) untuk instalasi dan deployment. Fokus utama adalah memastikan seluruh fitur berjalan sesuai spesifikasi sebelum rilis, dengan pendekatan pragmatis (manual testing dominan, automated test terbatas pada 3 area kritis).

* * *

## 1. Pendekatan Testing

### 1.1 Manual Testing (Checkpoint)

- **Metode:** Dilakukan oleh user/tester setelah setiap tahap selesai (checkpoint).
- **Tujuan:** Memastikan setiap modul berfungsi sesuai spesifikasi sebelum melanjutkan ke tahap berikutnya.
- **Data Uji:** Data diisi secara manual sebagai bahan uji di setiap checkpoint (minimal 1 guru, 1 siswa, 1 kelas, 1 jadwal).
- **Prinsip:** Jangan lanjut ke tahap berikutnya sebelum checkpoint saat ini disetujui.

### 1.2 Automated Test (PHPUnit)

- **Lingkup:** Hanya untuk **3 area kritis** yang rawan kesalahan logika:
  
  1. **Validasi bentrok jadwal** (`JadwalGuruService`) — overlap jam untuk guru &amp; kelas.
  2. **Perhitungan geofencing** (`GeofencingService`) — Haversine + radius 500m.
  3. **Resolusi scope RBAC** (`AuthService::resolveScope()` &amp; `PermissionFilter`) — SEMUA, KELAS\_DIAMPU, KELAS\_TERJADWAL, DIRI\_SENDIRI.
- **Eksekusi:** Hanya berjalan di **lingkungan lokal** (`php spark test`).
- **Deployment:** Folder `tests/` TIDAK diupload ke hosting produksi.
- **Tidak perlu automated test** di luar 3 area di atas — cukup manual testing.

* * *

## 2. Checkpoint per Tahap

Setiap tahap (1–14) dihentikan setelah selesai, menunggu konfirmasi manual dari user sebelum lanjut.

| Tahap | Modul                    | Checkpoint Testing                                                                                                                                                                                                                                |
|-------|--------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| 1     | **Setup Project**        | Akses CI4 tanpa /public/, .htaccess proteksi berfungsi                                                                                                                                                                                            |
| 2     | **Database Schema**      | SQL berhasil dijalankan, FK constraint aktif, 43 permission + seeder role\_permissions/role\_menus terisi **dengan benar** (tidak ada permission .manage untuk Pimpinan, BK mendapat prestasi.view, Guru mendapat jadwal\_guru.view bukan manage) |
| 3     | **Auth + RBAC**          | Login, rate limiting, PermissionFilter, scope resolver, single active session, Pimpinan hanya mendapat akses view (bukan manage)                                                                                                                  |
| 4     | **Master Data**          | CRUD Guru/Pegawai/Siswa/Kelas/Tahun/Mapel/Wali/Jadwal, import/export, kenaikan kelas, mutasi, histori siswa (A4), NIK 16 digit valid                                                                                                              |
| 5     | **Presensi Siswa**       | Input AW/AK (tombol solid default Hadir), revisi oleh Wali/Admin/Operator, EWS Radar, geofencing (Haversine)                                                                                                                                      |
| 6     | **Presensi Mengajar**    | Input jurnal semua sesi (termasuk Non Sesi), validasi id\_guru sendiri, revisi Admin/Operator, materi wajib                                                                                                                                       |
| 7     | **Laporan &amp; Export** | Matrix (Total H\|S\|I\|A), Export Bulanan/Semester (AW/AK), Laporan Jurnal (filter dinamis Guru→Kelas)                                                                                                                                            |
| 8     | **BK &amp; Prestasi**    | Catatan kasus (dengan audit updated\_at/updated\_by), master pelanggaran, prestasi, Top 20 poin. **BK dapat mengakses halaman Prestasi (prestasi.view)**                                                                                          |
| 9     | **Dashboard**            | Semua widget per role (Admin, Operator, Pimpinan, BK, Guru, Wali, Siswa). **Pimpinan melihat EWS Radar**                                                                                                                                          |
| 10    | **Kartu Pelajar**        | Bulk generate, cetak massal per kelas, QR verifikasi publik (NIK masking: 351012xxxxxx1234), preview/download                                                                                                                                     |
| 11    | **DIHAPUS**              | Cetak Form &amp; Surat — tidak dikerjakan                                                                                                                                                                                                         |
| 12    | **Settings**             | Manajemen User (reset password ke NIP/NISN), Menu &amp; Role, Setting Sistem (dengan validasi type), Maintenance Mode                                                                                                                             |
| 13    | **Geofencing**           | Haversine, integrasi Presensi Siswa &amp; Jurnal (hanya untuk status Hadir), toggle OFF                                                                                                                                                           |
| 14    | **Backup &amp; Log**     | SQL dump PHP murni (tanpa exec/shell), log activity (retensi 3 tahun), download backup                                                                                                                                                            |
| 15    | **Testing &amp; Polish** | Semua fitur berjalan, README lengkap, checklist final terpenuhi                                                                                                                                                                                   |

**Catatan:** Tahap 11 (Cetak Form &amp; Surat) DIHAPUS dari lingkup proyek.

* * *

## 3. Automated Test — Spesifikasi

### 3.1 Validasi Bentrok Jadwal (`JadwalGuruService`)

- **Test Case 1:** Guru yang sama, hari yang sama, jam overlap → harus ditolak.
- **Test Case 2:** Guru yang sama, hari yang sama, jam tidak overlap → harus diterima.
- **Test Case 3:** Kelas yang sama, hari yang sama, jam overlap → harus ditolak.
- **Test Case 4:** Kelas yang sama, hari yang sama, jam tidak overlap → harus diterima.
- **Test Case 5:** Guru berbeda, kelas berbeda, jam overlap → harus diterima.

### 3.2 Perhitungan Geofencing (`GeofencingService`)

- **Test Case 1:** Titik di dalam radius (jarak &lt; 500m) → return true.
- **Test Case 2:** Titik di luar radius (jarak &gt; 500m) → return false.
- **Test Case 3:** Titik tepat di batas radius (jarak = 500m) → return true.
- **Test Case 4:** Koordinat device null/format salah → return false + error.

### 3.3 Resolusi Scope RBAC

- **Test Case 1:** Admin dengan permission `master_guru.manage` → scope SEMUA.
- **Test Case 2:** Guru dengan permission `presensi_siswa.input` → scope KELAS\_TERJADWAL.
- **Test Case 3:** Guru non-wali dengan permission `presensi_siswa.revisi` → scope KELAS\_DIAMPU, namun daftar kelas yang di-resolve kosong (karena tidak ada mapping wali) sehingga tidak ada data yang bisa direvisi.
- **Test Case 4:** Wali Kelas (status dinamis) dengan permission `presensi_siswa.revisi` → scope KELAS\_DIAMPU, dengan daftar kelas dari mapping\_wali\_kelas.
- **Test Case 5:** Siswa dengan permission `profile_siswa.view` → scope DIRI\_SENDIRI.
- **Test Case 6:** User tanpa permission yang valid → tolak akses (HTTP 403).
- **Test Case 7:** Pimpinan dengan permission `master_guru.view` → scope SEMUA.
- **Test Case 8:** Pimpinan **tidak boleh memiliki** permission `bk_kasus.manage` atau `prestasi.manage` atau `kartu_pelajar.manage` (hanya view).
- **Test Case 9:** BK dengan permission `prestasi.view` → scope SEMUA (BK dapat mengakses halaman Prestasi).
- **Test Case 10:** Guru dengan permission `jadwal_guru.view` → scope DIRI\_SENDIRI (bukan jadwal\_guru.manage).

* * *

## 4. Dokumentasi Akhir — README.md

Buat `README.md` di root project (terpisah dari `docs/` masterplan) dengan konten berikut:

### 4.1 Langkah Instalasi Ulang di Lokal

```
1. Clone repository:
   git clone [url-repo]

2. Composer install:
   composer install

3. Copy .env.example ke .env dan sesuaikan:
   CI_ENVIRONMENT = development
   app.baseURL = 'http://localhost/sisfour_dev'
   database.default.database = sisfour_dev
   database.default.username = root
   database.default.password =

4. Jalankan SQL dari dokumen 02_DATABASE:
   - Import file SQL ke database MySQL (via phpMyAdmin atau command line)
   - Pastikan semua tabel, FK, dan data awal (role_permissions, role_menus) terisi dengan benar

5. Jalankan server:
   php spark serve

6. Akses aplikasi:
   http://localhost:8080

7. Login default:
   Username: admin
   Password: admin
   (Ubah password setelah login pertama)
    
```

### 4.2 Langkah Deploy ke Hosting Produksi

```
1. Upload semua file ke hosting (public_html atau root domain)

2. Sesuaikan .env untuk production:
   CI_ENVIRONMENT = production
   app.baseURL = 'https://domain.com'
   database.default.hostname = [host]
   database.default.database = [db]
   database.default.username = [user]
   database.default.password = [pass]

3. Import schema via SQL (dari dokumen 02_DATABASE)

4. Setting permission folder writable/:
   chmod 755 writable/

5. Pastikan .htaccess proteksi folder sensitif ikut terupload

6. Pastikan ekstensi PHP aktif:
   intl, mbstring, zip, gd, fileinfo, mysqlnd
    
```

### 4.3 Catatan Khusus

- **Struktur Folder:** Sudah dipindah (`public/` ke root) — pastikan .htaccess proteksi ikut ter-upload dan aktif di hosting.
- **Login Default:** admin / admin (ubah password setelah login pertama).
- **HTTPS/SSL Wajib:** Untuk geofencing dan mobile (Geolocation API tidak berfungsi di non-HTTPS).
- **Server-side Requirements:** PHP 8.2+, MySQL 5.7+.
- **NIK:** 16 digit, wajib divalidasi di form dan import.
- **Permission &amp; Menu:** Jangan lupa menjalankan seeder `role_permissions` dan `role_menus` (sudah disertakan di SQL final).
- **Pimpinan Access:** Pimpinan hanya memiliki akses view (readonly) untuk semua modul, tidak ada akses manage/edit.

* * *

## 5. Checklist Final

- \[ ] 3 automated test area (jadwal, geofencing, RBAC scope) ditulis dan lolos secara lokal.
- \[ ] Seluruh modul (kecuali Cetak Form &amp; Surat yang dihapus) sudah lolos manual testing per tahap.
- \[ ] README.md instalasi &amp; deploy lengkap dan sudah dicoba diikuti dari nol (fresh install).
- \[ ] Review akhir: semua checklist di tahap 1–14 sudah tercentang.
- \[ ] Tidak ada error di console browser dan log server (`writable/logs/`).
- \[ ] Performa halaman terjaga (DataTables client-side, export selaras filter).
- \[ ] Geofencing teruji dengan GPS indoor/outdoor dan toggle OFF.
- \[ ] Backup SQL berhasil digenerate dan didownload.
- \[ ] JWT authentication untuk mobile teruji dengan Postman (access token 1 jam, refresh token 30 hari).
- \[ ] NIK 16 digit validasi di form dan import berfungsi.
- \[ ] Masking NIK di verifikasi publik (format: 351012xxxxxx1234) berfungsi.
- \[ ] Wali Kelas dapat mengedit biodata siswa di kelas diampu (bukan hanya readonly).
- \[ ] Menu Data Guru (31) disembunyikan untuk role guru dan siswa.
- \[ ] PermissionFilter bekerja untuk semua grup route.
- \[ ] Pimpinan **tidak** memiliki akses manage/edit untuk semua modul (hanya view).
- \[ ] BK dapat mengakses halaman Prestasi (`/bk/prestasi`).
- \[ ] Guru dapat melihat jadwal sendiri (`jadwal_guru.view`, bukan `jadwal_guru.manage`).
- \[ ] Guru non-wali tidak memiliki data di kelas yang di-resolve (tidak ada mapping wali).

* * *

## 6. Catatan Penting untuk Developer

- **PHPUnit:** Jalankan `php spark test` secara rutin di lokal sebelum push ke repository.
- **Checkpoint Disiplin:** Jangan lanjut ke tahap berikutnya sebelum checkpoint saat ini disetujui oleh user/tester.
- **Data Uji:** Untuk manual testing, gunakan data realistik (minimal 1 guru, 1 siswa, 1 kelas, 1 jadwal, 1 tahun ajaran aktif).
- **Environment:** Testing dilakukan di lingkungan **development** (bukan production) untuk menghindari kerusakan data.
- **Log Error:** Pastikan `writable/logs/` tidak ada error fatal saat testing.
- **GIT:** Jangan commit folder `writable/backups/`, `uploads/`, dan `.env` ke repository.
- **SQL Fresh Install:** Selalu gunakan SQL dari `02_DATABASE` untuk instalasi baru, jangan menggunakan migration CI4.
- **Guard for empty array in whereIn():** Pada implementasi scope `KELAS_DIAMPU` dan `KELAS_TERJADWAL`, pastikan jika `$daftarKelas` kosong, Service langsung mengembalikan collection kosong tanpa menjalankan query builder (hindari SQL `IN ()` yang invalid).

* * *

© 2026 SisisFour · MTsN 4 Jombang · Testing &amp; Polish Final
