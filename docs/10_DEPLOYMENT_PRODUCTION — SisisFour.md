# Deployment Production — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 12 September 2026
**Baseline Aplikasi:** `main` @ `39da4651acd29adcd575677d7a37c058bf32269d`
**Baseline Database:** `sisfour_dev_v2 (33).sql`
**Target Domain:** `https://sisfour.mtsn4jombang.sch.id/`

> Deployment baseline dilakukan melalui **manual ZIP upload Hostinger hPanel**, bukan Git deployment. Secret production tidak pernah disimpan di repository atau dokumen ini.

## 1. Platform

```text
Hosting        Hostinger hPanel
Web root       public_html/
PHP            8.2+; target 8.3
Database       MariaDB/MySQL
Protocol       HTTPS wajib
Timezone       Asia/Jakarta
Deployment     manual ZIP upload/extract
```

Project root adalah Web root. Isi project harus berada langsung di `public_html/`, bukan `public_html/sisfour_dev_v2/` dan bukan hanya isi folder `public/`.

Struktur minimum:

```text
public_html/
├── index.php
├── .htaccess
├── app/
├── assets/
├── public/
├── uploads/
├── vendor/
├── writable/
├── composer.json
├── composer.lock
└── spark
```

## 2. Isi Paket Upload

Paket production boleh menyertakan `vendor/` bila Composer tidak dijalankan di hosting.

Jangan menyertakan:

```text
.git/
.env lokal
_step*_backup/
build/
writable/logs/* runtime lokal
writable/cache/* runtime lokal
writable/debugbar/* runtime lokal
writable/backups/* backup dev
```

`docs/` boleh disertakan karena `.htaccess` production memblokir direct Web access, tetapi tidak diperlukan oleh runtime.

## 3. PHP dan Extension

Minimum:

```text
PHP >= 8.2
mysqli
mbstring
intl
gd
fileinfo
zip
dom/xml
curl
openssl
```

Rekomendasi bila paket hosting mengizinkan:

```text
memory_limit        512M
max_execution_time  300
max_input_time      300
upload_max_filesize 64M
post_max_size       64M
max_input_vars      5000
date.timezone       Asia/Jakarta
```

Kartu/PDF dan spreadsheet lebih sensitif terhadap memory/time limit dibanding halaman biasa.

## 4. Composer

Dependency runtime:

```text
codeigniter4/framework ^4.7
dompdf/dompdf ^3.1
endroid/qr-code ^6.0
firebase/php-jwt ^7.1
phpoffice/phpspreadsheet ^5.9
```

Jika `vendor/` tidak dibawa dari build lokal, jalankan di hosting:

```bash
composer install --no-dev --optimize-autoloader
```

Setelah itu jalankan `composer check-platform-reqs` bila tersedia.

## 5. `.env` Production

`.env` dibuat langsung di production dan **tidak di-commit**.

Template aman:

```ini
CI_ENVIRONMENT = production

app.baseURL = 'https://sisfour.mtsn4jombang.sch.id/'
app.forceGlobalSecureRequests = true
app.CSPEnabled = false

database.default.hostname = localhost
database.default.database = '<DB_NAME>'
database.default.username = '<DB_USER>'
database.default.password = '<DB_PASSWORD>'
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = 3306
database.default.DBDebug = false

cookie.secure = true
cookie.httponly = true
cookie.samesite = Lax

JWT_SECRET = '<RANDOM_SECRET_MIN_32_CHAR>'
encryption.key = '<RANDOM_ENCRYPTION_KEY_BERBEDA>'
```

Gunakan hostname yang benar-benar ditampilkan hPanel. Jangan menyalin secret lokal ke repository.

`JWT_SECRET` harus minimal 32 karakter. Secret JWT dan encryption key harus berbeda.

## 6. Database Import

Import dump resmi ke database production yang sudah dibuat.

Dump tidak boleh memaksa membuat/memilih database lokal yang salah. Setelah import environment baru:

```sql
TRUNCATE TABLE ci_sessions;
TRUNCATE TABLE api_tokens;
TRUNCATE TABLE login_attempts;
```

Jangan truncate business data lain.

Session Web memakai `DatabaseHandler` dan tabel `ci_sessions`.

## 7. Upload Runtime/Data File

File dinamis perlu diverifikasi terhadap paket release sebelum upload. Pada baseline `39da465`, sebagian asset Settings sudah tracked, sedangkan foto identitas dan dokumen personalia tetap bersifat runtime. Pastikan production memiliki file yang benar-benar direferensikan database, terutama:

```text
uploads/foto_siswa/
uploads/foto_guru/
uploads/foto_pegawai/
uploads/settings/branding/
uploads/settings/kartu/
writable/uploads/personalia/
```

### Catatan Repo Hygiene Penting

Implementasi final Settings menulis ke:

```text
uploads/settings/branding/
uploads/settings/kartu/
```

Pada baseline `39da465`, beberapa file branding dan background Kartu di dua folder `uploads/settings/...` tersebut **sudah tracked di repository**. Sementara itu, `.gitignore` masih memuat pola path historis seperti `uploads/branding/` dan `uploads/kartu_pelajar/`, bukan path final Settings. Artinya upload Settings berikutnya dapat ikut ter-track jika dilakukan `git add -A`. Ini adalah hygiene follow-up source: tentukan secara eksplisit apakah asset Settings akan diperlakukan sebagai release asset atau runtime-only, lalu selaraskan `.gitignore`. Jangan memindahkan path runtime yang sudah dipakai aplikasi hanya untuk mengatasi masalah ignore.

## 8. Permission File

Default aman:

```text
folder 755
file   644
```

PHP harus dapat menulis ke:

```text
writable/cache/
writable/logs/
writable/backups/
writable/uploads/
uploads/settings/
uploads/foto_*/
```

Jangan memakai `777` sebagai default. Perbaiki ownership melalui fasilitas hosting bila diperlukan.

## 9. Web Security

`.htaccess` root release melindungi file/folder internal.

Production test:

```text
/.env               -> 403/404
/composer.json      -> 403/404
/app/Config/App.php -> 403/404
/docs/              -> 403/404
/signage            -> 200
```

`robots.txt` meminta crawler tidak mengindeks aplikasi. Ini bukan authorization boundary.

## 10. HTTPS dan Cookie

SSL harus aktif sebelum memaksa HTTPS/cookie secure.

Production:

```text
HTTPS                        ON
app.forceGlobalSecureRequests true
cookie.secure                 true
cookie.httponly               true
SameSite                      Lax
```

Jika hosting berada di balik proxy/CDN dan muncul redirect loop, konfigurasi proxy/HTTPS harus diperiksa sebelum menonaktifkan security secara permanen.

## 11. Cache Hosting

Jangan mengaktifkan full-page cache agresif untuk seluruh aplikasi authenticated.

Data Dashboard, permission, Presensi, Settings, dan session bersifat dinamis. Gunakan caching selektif di aplikasi; Signage sendiri mempunyai server cache 240 detik.

## 12. Production Smoke Test

### Public/security

```text
/                       login dapat dibuka
/signage                200
/signage/data           JSON valid
/kartu/verify/{valid}   readonly valid
sensitive path          403/404
```

### Role Web

```text
Admin
Operator
Pimpinan
BK
Guru
Guru + Wali
Siswa
Pegawai identity bila digunakan
```

### Core workflow

```text
Dashboard
Master Data
Manajemen Siswa
Presensi Siswa
Presensi Mengajar/Jurnal
Laporan
BK/Prestasi
Kartu preview/download/cetak
Profile/Personalia
Settings
Backup
Maintenance
Log Activity
```

### API

```text
POST /api/auth/login       -> 200
GET  /api/auth/me          -> 200 dengan Bearer
GET  /api/dashboard        -> 200 bila permission
GET  protected tanpa token -> 401
POST /api/auth/logout      -> 200
token revoked sesudah logout -> ditolak
```

## 13. Deployment Rollback

Sebelum overwrite production berikutnya:

1. backup database;
2. backup `.env` production secara aman;
3. backup upload runtime;
4. simpan ZIP release sebelumnya bila perlu rollback;
5. jangan menimpa database production dengan dump development tanpa keputusan eksplisit.

## 14. Secret Handling

Dilarang memasukkan ke:

```text
Git
README/docs
screenshot publik
log aplikasi
chat publik
```

untuk data berikut:

```text
DB password
JWT secret
encryption key
password account
access/refresh token
```

Jika secret pernah dibagikan ke tempat yang tidak semestinya, rotate secret tersebut dan perbarui `.env` production.


## 11. UI/UX Change Deployment Rule

Redesign Guru/Walas/Siswa tidak boleh langsung dipindahkan ke production dari mockup. Urutan production tetap:

```text
source implemented
-> static lint
-> role/browser regression
-> canonical docs updated
-> build ZIP production
-> manual upload/extract hPanel
-> smoke test production
```

Mockup/image hasil diskusi tidak termasuk payload aplikasi kecuali secara eksplisit dijadikan asset final.
