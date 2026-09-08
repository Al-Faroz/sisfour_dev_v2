# Pola Pengerjaan — SisisFour

**Versi Acuan Utama:** v0.5 FINAL BASELINE  
**Tanggal Acuan:** 08 September 2026  
**Baseline Aplikasi:** `main` @ `b85b857e1a38b6eb1fd26ba2d9aa61ae5e679f55`  
**Baseline Database:** `sisfour_dev_v2 (15).sql`

Dokumen ini adalah **acuan utama** SisisFour. Isinya menyatakan kontrak dan kondisi baseline yang berlaku, bukan riwayat perubahan.

---

# 1. Fungsi Dokumen Acuan

Folder `docs/` adalah kontrak utama pengembangan, audit, testing, dan pemeliharaan SisisFour.

Urutan dokumen:

```text
00_POLA_PENGERJAAN
01_MASTERPLAN
02_DATABASE
03_AUTH_RBAC_MENU
04_MASTER_DATA
05_PRESENSI
06_LAPORAN
07_BK_PRESTASI_KARTU
08_DASHBOARD_SETTINGS_BACKUP
09_PROFILE
15_TESTING_POLISH
```

Jika dokumen, database, route, dan implementasi tidak selaras, konflik harus ditemukan dan diputuskan secara eksplisit. Tidak boleh diselesaikan dengan asumsi diam-diam.

# 2. Sumber Kebenaran

Baseline teknis harus diverifikasi terhadap:

```text
Database   → dump database resmi terbaru
Route      → app/Config/Routes.php
Auth/RBAC  → users, user_roles, permissions, role_permissions
Menu       → menus, role_menus + MenuService
Business   → Service modul
UI         → View + JavaScript
```

Database dan kode tidak boleh mengandung kontrak tersembunyi yang bertentangan dengan dokumen.

# 3. Aturan Full File

Setiap file baru atau revisi diserahkan sebagai **file utuh**, bukan snippet/diff.

Berlaku untuk:

```text
docs/*.md
app/Config/*.php
app/Models/*.php
app/Services/*.php
app/Filters/*.php
app/Controllers/*.php
app/Views/*.php
assets/js/*.js
SQL
script utilitas
```

Jika beberapa file berubah, paket ZIP boleh digunakan.

# 4. Urutan Pengerjaan Modul

```text
Model
→ Service
→ Filter bila diperlukan
→ Controller
→ View
→ JavaScript
→ Routes
→ Static Check
→ Runtime Checkpoint
```

Service adalah pusat:

- authorization data-level;
- transaction;
- business rule;
- validasi relasi;
- lifecycle;
- side effect antar tabel;
- histori;
- duplicate prevention.

Controller fokus request/response. View dan JavaScript bukan security boundary.

# 5. Stack

```text
Framework        CodeIgniter 4
PHP              8.2+
Database         MariaDB/MySQL
Development      XAMPP
UI               Sneat Free + Bootstrap 5
Business JS      Vanilla JavaScript
HTTP Frontend    Fetch API
Chart            ApexCharts
Excel            PhpSpreadsheet
PDF              Dompdf
QR               endroid/qr-code
Session Web      Database
Auth API         JWT / api_tokens
Timezone         Asia/Jakarta
```

jQuery boleh dimuat sebagai dependency template/vendor, tetapi business JavaScript tidak bergantung pada jQuery.

# 6. Document Root

Project root adalah web root.

```text
sisfour_dev_v2/
├── index.php
├── .htaccess
├── app/
├── assets/
├── uploads/
├── writable/
├── vendor/
└── docs/
```

Runtime tidak menggunakan `public/` sebagai document root.

# 7. Path File Runtime

```text
uploads/foto_guru/
uploads/foto_siswa/
uploads/settings/branding/
uploads/settings/kartu/
assets/kartu/default/
writable/backups/
```

File upload harus:

- tipe eksplisit;
- benar-benar image bila image;
- re-encode;
- nama aman/random;
- tidak executable;
- tidak memakai nama mentah dari client.

# 8. Database-First

Untuk dataset besar:

```text
presensi
presensi_mengajar
log_activity
laporan
dashboard
histori
```

operasi berikut dikerjakan database:

```text
WHERE
JOIN
GROUP BY
COUNT
SUM
MIN / MAX
HAVING
ORDER BY
LIMIT / OFFSET
```

Dilarang mengambil seluruh dataset besar lalu melakukan agregasi utama di PHP.

Pivot ringan diperbolehkan setelah dataset dibatasi, misalnya satu kelas × satu bulan.

# 9. Auth dan Scope

Effective role:

```text
users.role
UNION
user_roles.role
```

Role resmi:

```text
admin
operator
pimpinan
bk
guru
siswa
```

Wali Kelas bukan role.

Scope:

```text
SEMUA
KELAS_DIAMPU
KELAS_TERJADWAL
DIRI_SENDIRI
TIDAK_ADA
```

# 10. Web Security

- session Web disimpan di database;
- CSRF aktif;
- mutation Fetch memakai `assets/js/csrf-fetch.js`;
- actor berasal dari session;
- target/scope divalidasi ulang server;
- output teks menggunakan escaping;
- credential/token/hash tidak masuk log.

# 11. API Security

- API berada di prefix `/api`;
- actor berasal dari token valid;
- token dapat direvoke;
- API tidak memakai CSRF Web;
- API tetap tunduk permission/scope yang sama.

# 12. Static Check

PHP:

```powershell
php -l path\file.php
```

JavaScript:

```powershell
node --check path\file.js
```

# 13. Runtime Checkpoint

Setiap modul minimal harus bebas dari:

- 404 route tidak sengaja;
- 500;
- 403 palsu;
- CSRF gagal pada mutation sah;
- privilege escalation;
- duplicate akibat race;
- partial transaction;
- histori putus;
- uncaught browser error.

# 14. Status Fase Baseline

Pengerjaan fitur utama telah mencapai finalisasi dan selanjutnya masuk **Testing & Polish**.

Baseline mempunyai satu blocker implementasi yang harus diselesaikan sebelum rilis:

```text
Route + permission Profile tersedia,
tetapi Controller ProfileGuru/ProfileSiswa
tidak ditemukan pada repo baseline.
```

Rinciannya berada di `09_PROFILE` dan `15_TESTING_POLISH`.
