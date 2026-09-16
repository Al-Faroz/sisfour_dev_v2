# Deployment Production — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 16 September 2026  
**Source baseline:** `main` @ `06e4e559c045763096058fc889342da78d973314` + PR #9 closure patch pending focused re-smoke  
**Production DB:** delta G3.2 + G3.3.1 applied; SQL/schema broad smoke PASS  
**Target Domain:** `https://sisfour.mtsn4jombang.sch.id/`

> Deployment production menggunakan manual ZIP upload Hostinger hPanel. Secret production tidak disimpan di repository/docs.

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

Project root adalah Web root. Isi project harus langsung di `public_html/`.

## 2. Paket Upload

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

Jangan menyertakan `.git/`, `.env` lokal, backup dev, runtime log/cache/debugbar, atau secret.

## 3. PHP / Extension

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

Rekomendasi resource bila paket hosting mendukung:

```text
memory_limit        512M
max_execution_time  300
max_input_time      300
upload_max_filesize 64M
post_max_size       64M
max_input_vars      5000
date.timezone       Asia/Jakarta
```

## 4. Composer

Dependency runtime mengikuti `composer.lock`. Jika `vendor/` tidak dibawa:

```bash
composer install --no-dev --optimize-autoloader
composer check-platform-reqs
```

## 5. `.env` Production

`.env` dibuat langsung di production dan tidak di-commit.

Minimum:

```ini
CI_ENVIRONMENT = production
app.baseURL = 'https://sisfour.mtsn4jombang.sch.id/'
app.forceGlobalSecureRequests = true

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

## 6. Database Deployment Rule

Jangan menimpa production dengan dump development tanpa keputusan eksplisit.

Untuk schema delta:

```text
1. backup database production
2. ambil/audit dump hosting aktual
3. bandingkan schema/data/FK/index/permissions/menu
4. susun SQL hosting spesifik environment
5. static/review gate
6. approval eksplisit
7. execute
8. verification query
9. logout/login bila permission/menu session/cache terlibat
10. smoke UAT production
```

SQL G3.2:

```text
database/20260915_G3_2_JURNAL_STUDENT_EXCEPTIONS_HOSTING.sql
```

SQL G3.3.1:

```text
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_HOSTING.sql
```

SQL G3.3.1 dibuat setelah dump aktual `u473908839_sisfour2026` diaudit. Eksekusi, verification, dan broad hosting smoke telah PASS.

## 7. Source Change Setelah Smoke

Production smoke hanya membuktikan source yang benar-benar terpasang saat pengujian.

Jika branch berubah sesudah smoke, lakukan focused redeploy/re-smoke sesuai area perubahan sebelum menganggap head terbaru production-verified.

Closure audit PR #9 menemukan patch source tanpa schema change:

```text
app/Services/KonselingBkService.php
assets/js/bk/konseling.js
```

Tujuan patch: menjaga `Rencana Berikutnya` historis yang sudah tersimpan bila opsi tersebut kemudian dihapus dari Pengaturan Form Konseling.

Tidak ada SQL tambahan. Setelah static/local focused UAT PASS, deploy dua file source tersebut ke hosting lalu ulang focused smoke:

```text
record lama menyimpan Rencana X
→ X dihapus dari Settings
→ buka record lama
→ X tetap terlihat sebagai "tersimpan"
→ save tanpa mengganti X tetap sukses
→ ganti ke opsi aktif Y sukses
→ record lain tidak dapat memakai X sebagai opsi baru
```

Sampai focused hosting smoke ini PASS, PR #9 **belum** masuk Ready/Merge gate.

## 8. Runtime/Auth State pada Fresh Import

```sql
TRUNCATE TABLE ci_sessions;
TRUNCATE TABLE api_tokens;
TRUNCATE TABLE login_attempts;
```

Jangan truncate business data lain.

## 9. Upload Runtime/Data File

Pastikan file referensi database tersedia:

```text
uploads/foto_siswa/
uploads/foto_guru/
uploads/foto_pegawai/
uploads/settings/branding/
uploads/settings/kartu/
writable/uploads/personalia/
```

Default permission aman folder 755/file 644; jangan 777 sebagai default.

## 10. Web Security

Production test:

```text
/.env               -> 403/404
/composer.json      -> 403/404
/app/Config/App.php -> 403/404
/docs/              -> 403/404
/signage            -> 200
```

HTTPS + secure cookie wajib setelah SSL aktif.

## 11. Production Smoke Umum

Role minimum:

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

Core workflow:

```text
Dashboard
Master Data
Manajemen Siswa
Presensi Siswa
Presensi Mengajar/Jurnal
Laporan
BK/Prestasi/Kartu
Profile/Personalia
Settings
Backup
Maintenance
Log Activity
```

## 12. G3.3.1 Broad Smoke — PASS

Telah diuji:

```text
Catatan Pelanggaran tanpa poin
Tindak Lanjut Pelanggaran
Export Pelanggaran 2 sheet + Kelas
Prestasi create/edit + export Kelas
Konseling Tahap 1/Tahap 2
Admin/BK Settings
Operator Konseling tanpa Settings
Pimpinan/Guru/Wali/Siswa tanpa Konseling
Dashboard lintas-role tidak bocor Konseling
responsive smoke role prioritas
```

Closure focused smoke pada preservasi Rencana historis adalah gate tambahan setelah broad smoke tersebut.

## 13. Rollback

Sebelum overwrite production:

1. backup database;
2. backup `.env` production;
3. backup runtime uploads;
4. simpan ZIP release sebelumnya bila perlu;
5. catat SQL delta yang sudah diaplikasikan;
6. pertimbangkan compatibility schema/data ketika rollback source.

## 14. Secret Handling

DB password, JWT secret, encryption key, password account, token tidak boleh masuk Git/docs/screenshot publik/log.

## 15. UI/UX Deployment Rule

```text
source implemented
→ static lint
→ role/browser regression
→ canonical docs updated
→ build ZIP production
→ manual upload/extract hPanel
→ smoke test production
```

Mockup/image/HTML presentasi bukan payload aplikasi kecuali secara eksplisit dijadikan asset final.

## 16. Current Release Boundary

`main` tetap baseline PR #8 sampai PR #9 mendapat Ready + Merge approval eksplisit. Database hosting sudah menerima schema G3.3.1 yang backward-compatible dengan branch, tetapi latest closure source patch masih menunggu focused local + hosting re-smoke.