# Deployment Production — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 17 September 2026  
**Source baseline:** `main` @ `06e4e559c045763096058fc889342da78d973314` + PR #9 rework  
**Production DB:** baseline G3.3.1 applied + broad smoke PASS; **17 Sep rework belum diterapkan**  
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

Project root adalah Web root. Isi project langsung di `public_html/`.

## 2. Paket Upload

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

## 4. Composer

Dependency runtime mengikuti `composer.lock`.

```bash
composer install --no-dev --optimize-autoloader
composer check-platform-reqs
```

## 5. `.env` Production

`.env` dibuat langsung di production dan tidak di-commit. HTTPS/secure cookie wajib. DB credential/JWT/encryption secret tidak boleh masuk docs/repo.

## 6. Database Deployment Rule

Jangan menimpa production dengan dump development tanpa keputusan eksplisit.

Schema delta:

```text
1. backup database production
2. ambil/audit dump hosting aktual
3. bandingkan schema/data/FK/index/permissions/menu
4. susun SQL hosting spesifik environment
5. review/static gate
6. approval eksplisit
7. execute
8. verification query
9. logout/login bila permission/menu state terlibat
10. smoke UAT production
```

Baseline yang sudah diterapkan:

```text
database/20260915_G3_2_JURNAL_STUDENT_EXCEPTIONS_HOSTING.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_HOSTING.sql
```

Baseline G3.3.1 execution + broad hosting smoke telah PASS.

## 7. Rework 17 September 2026 — BELUM HOSTING

Keputusan baru setelah baseline smoke:

```text
Catatan Pelanggaran + Prestasi snapshot id_tahun
periodic table filter Tahun Ajaran
Konseling follow-up 1:N
tidak ada delete Konseling/follow-up
filter Konseling desktop 2 baris
```

SQL localhost yang disiapkan:

```text
database/20260917_G3_3_1_BK_PERIOD_YEAR_COUNSELING_FOLLOWUP_LOCALHOST.sql
```

Delta target:

```text
+ catatan_kasus.id_tahun
+ catatan_prestasi.id_tahun
+ tindak_lanjut_konseling_bk
```

**Tidak ada SQL hosting untuk rework ini saat ini.**

Alasan: source/schema berubah setelah broad hosting smoke. Sesuai gate, localhost SQL + UAT + static harus PASS dulu, lalu dump hosting aktual diaudit ulang sebelum SQL hosting baru dibuat.

## 8. Source Rework yang Nanti Perlu Deploy

Area utama:

```text
app/Config/RoutesBKFoundation.php
app/Controllers/BKKonseling.php
app/Models/BKKasusModel.php
app/Models/BKPrestasiModel.php
app/Models/KonselingBkModel.php
app/Models/KonselingBkFollowUpModel.php
app/Services/BkService.php
app/Services/PrestasiService.php
app/Services/PeriodContextService.php
app/Services/KonselingBkService.php
app/Services/KonselingBkExportService.php
app/Views/bk/kasus.php
app/Views/bk/prestasi.php
app/Views/bk/konseling.php
assets/js/bk/kasus.js
assets/js/bk/prestasi.js
assets/js/bk/konseling.js
```

Jangan upload subset source yang bergantung pada schema baru sebelum database production siap sesuai urutan deploy yang disetujui.

## 9. Urutan Hosting Rework Nanti

Setelah localhost PASS dan user menyetujui hosting gate:

```text
A. ambil/audit dump hosting terbaru
B. buat delta SQL hosting spesifik
C. backup hosting
D. execute SQL hosting
E. verification schema/data
F. upload source head final
G. clear/cache/session bila memang diperlukan
H. smoke Tahun Ajaran + follow-up Konseling
I. final docs/PR sync
```

Tidak ada eksekusi production otomatis oleh ChatGPT.

## 10. Focused Hosting Smoke Rework

Minimum:

```text
Catatan Pelanggaran default Tahun aktif + history + export
Prestasi default Tahun aktif + history + export
Konseling filter Tahun aktif + Kelas periodik
Konseling desktop filter 2 baris
Konseling parent Tahap 1/Tahap 2
Tambah follow-up #1 dan #2 tanpa overwrite
Edit follow-up existing
status parent sinkron dengan follow-up terbaru
historical Rencana pada follow-up tetap terjaga
no Delete parent/follow-up
Admin/Operator/BK access tetap benar
Pimpinan/Guru/Wali/Siswa tetap tanpa Konseling
mobile no horizontal overflow
```

## 11. Runtime/Auth State pada Fresh Import

Hanya untuk fresh environment sesuai kebutuhan:

```sql
TRUNCATE TABLE ci_sessions;
TRUNCATE TABLE api_tokens;
TRUNCATE TABLE login_attempts;
```

Jangan truncate business data lain.

## 12. Upload Runtime/Data File

Pastikan file referensi database tersedia:

```text
uploads/foto_siswa/
uploads/foto_guru/
uploads/foto_pegawai/
uploads/settings/branding/
uploads/settings/kartu/
writable/uploads/personalia/
```

Default permission aman folder 755/file 644.

## 13. Web Security

Production test:

```text
/.env               -> 403/404
/composer.json      -> 403/404
/app/Config/App.php -> 403/404
/docs/              -> 403/404
/signage            -> 200
```

## 14. Rollback

Sebelum overwrite production:

1. backup database;
2. backup `.env`;
3. backup runtime uploads;
4. simpan ZIP release sebelumnya;
5. catat SQL delta yang diaplikasikan;
6. pastikan rollback source kompatibel dengan schema yang tersisa.

## 15. Secret Handling

DB password, JWT secret, encryption key, password account, token tidak boleh masuk Git/docs/screenshot publik/log.

## 16. Current Release Boundary

```text
main                                  = PR #8 baseline
hosting baseline G3.3.1               = PASS
17 Sep rework localhost SQL/UAT       = PENDING
17 Sep rework final static            = PENDING
17 Sep hosting dump audit/delta       = NOT STARTED
17 Sep hosting smoke                  = NOT STARTED
PR #9                                 = DRAFT / BELUM MERGE
```

Production baseline yang sudah PASS tidak boleh dipakai sebagai bukti untuk source/schema rework 17 September.