# Tree Structure — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 12 September 2026
**Baseline Aplikasi:** `main` @ `39da4651acd29adcd575677d7a37c058bf32269d`

> Struktur ini menjelaskan boundary repository dan lokasi file penting. Ia tidak dimaksudkan menjadi daftar setiap file vendor/template.

## 1. Root

SisisFour memakai **project root sebagai Web root**.

```text
sisfour_dev_v2/
├── .htaccess
├── .gitignore
├── index.php
├── spark
├── composer.json
├── composer.lock
├── phpunit.dist.xml
├── README.md
├── app/
├── assets/
├── database/
├── docs/
├── postman/
├── public/
├── tests/
├── uploads/
├── vendor/          # runtime dependency, tidak di-commit
└── writable/        # runtime CI4/non-public storage
```

Folder `public/` ada untuk aset/fallback tertentu, tetapi **bukan document root runtime** baseline.

## 2. Security Root

`.htaccess` root:

- mematikan directory browsing;
- meneruskan route ke `index.php`;
- meneruskan Authorization header;
- memblokir file sensitif;
- memblokir direct access ke `app/`, `vendor/`, `writable/`, `tests/`, `database/`, `docs/`, Postman/repository metadata dan backup STEP.

`robots.txt` production meminta `Disallow: /`.

## 3. `app/`

```text
app/
├── Config/
├── Controllers/
├── Database/
├── Filters/
├── Helpers/
├── Language/
├── Libraries/
├── Models/
├── Services/
├── Support/
├── ThirdParty/
└── Views/
```

Alur bisnis:

```text
Routes
 -> Filter
 -> Controller
 -> Service
 -> Model / Query Builder
 -> DB
 -> View / JSON
```

## 4. Config Penting

```text
app/Config/App.php
app/Config/Database.php
app/Config/Filters.php
app/Config/Routes.php
app/Config/Security.php
app/Config/Session.php
app/Config/Cookie.php
```

Environment-specific secret/config production ditempatkan di `.env`, bukan hardcode source.

## 5. Auth / Authorization

File utama mencakup:

```text
app/Controllers/Auth.php
app/Services/AuthService.php
app/Services/JwtService.php
app/Filters/AuthFilter.php
app/Filters/PermissionFilter.php
app/Filters/MaintenanceFilter.php
app/Support/RequestContext.php
```

RequestContext API memakai snake_case canonical keys.

## 6. Master & Operasional

Controller/Service dipisah per domain, termasuk:

```text
MasterGuru
MasterPegawai
MasterSiswa
MasterKelas
MasterTahunAjaran
MasterMapel
MappingWaliKelas
JadwalGuru
ManajemenSiswa
PresensiSiswa
PresensiMengajar
LaporanPresensi
LaporanJurnal
BKKasus
BKPelanggaran
BKPrestasi
KartuPelajar
Dashboard
Signage
Settings*
Backup
LogActivity
Profile*
Personalia
```

## 7. Personalia / Portofolio

```text
app/Controllers/Personalia.php
app/Models/DokumenPersonaliaModel.php
app/Models/RiwayatPangkatModel.php
app/Models/RiwayatPendidikanModel.php
app/Models/RiwayatPenugasanModel.php
app/Services/PersonaliaService.php
app/Services/PortfolioService.php
app/Services/UploadService.php
app/Views/personalia/
assets/js/personalia/
```

Raw document disimpan non-public pada `writable/uploads/personalia/`.

## 8. Frontend

```text
assets/
├── css/
├── img/
├── js/
│   ├── bk/
│   ├── components/
│   ├── kartu/
│   ├── master/
│   ├── personalia/
│   ├── presensi/
│   ├── settings/
│   ├── csrf-fetch.js
│   └── main.js
└── vendor/
```

Business JavaScript = Vanilla JS + Fetch API.

Komponen umum seperti pagination/searchable select digunakan tanpa menjadikan client sebagai authorization boundary.

## 9. Upload Public

Runtime public utama:

```text
uploads/foto_siswa/
uploads/foto_guru/
uploads/foto_pegawai/
uploads/settings/branding/
uploads/settings/kartu/
```

Foto user dan dokumen personalia adalah runtime data. Khusus asset Settings, baseline `39da465` saat ini memang membawa beberapa branding/background Kartu sebagai tracked release asset; kebijakan jangka panjangnya perlu diseragamkan dengan `.gitignore`.

### Catatan `.gitignore`

Baseline `.gitignore` masih mempunyai pola path historis (`uploads/branding/`, `uploads/kartu_pelajar/`) sementara implementasi Settings final menulis ke `uploads/settings/...`. Pada release `39da465`, beberapa file di path final Settings sudah tracked. Karena itu setiap `git add -A` harus diaudit agar upload baru tidak ikut commit tanpa keputusan. Penyelarasan kebijakan asset Settings dan `.gitignore` dilakukan sebagai hygiene follow-up terpisah.

## 10. Upload Non-Public

```text
writable/uploads/personalia/
```

File hanya dikirim melalui Controller setelah authorization Service.

## 11. Runtime Writable

```text
writable/cache/
writable/logs/
writable/backups/
writable/debugbar/
writable/uploads/
```

Database session menggunakan `ci_sessions`, bukan file session sebagai kontrak utama.

## 12. Kartu Default Asset

Fallback release:

```text
public/assets/kartu/default/background_kta_depan.jpg
public/assets/kartu/default/background_kta_belakang.jpg
```

Runtime override berada di `uploads/settings/kartu/` dan direferensikan `setting_sistem`.

## 13. Database Scripts

`database/` berisi script utilitas/checkpoint schema yang pernah diperlukan. Schema live canonical tetap diverifikasi terhadap dump resmi terbaru dan `SHOW CREATE TABLE` bila perlu.

Migrations bukan syarat awal deployment baseline bila database dibuat dari dump resmi.

## 14. Dokumentasi Canonical

Set fresh `docs/`:

```text
00_POLA_PENGERJAAN___SisisFour.md
01_MASTERPLAN — SisisFour.md
02_DATABASE — SisisFour.md
03_AUTH_RBAC_MENU — SisisFour.md
04_MASTER_DATA — SisisFour.md
05_PRESENSI — SisisFour.md
06_LAPORAN — SisisFour.md
07_BK_PRESTASI_KARTU — SisisFour.md
08_DASHBOARD_SETTINGS_BACKUP — SisisFour.md
09_PROFILE — SisisFour.md
10_DEPLOYMENT_PRODUCTION — SisisFour.md
15_TESTING_POLISH — SisisFour.md
16_MOBILE_CORDOVA — SisisFour.md
Routes Final — SisisFour.md
Tree Structure — SisisFour.md
```

Dokumen phase note/changelog/package note lama bukan bagian SSOT fresh.

## 15. Tidak Di-commit

Secara prinsip:

```text
.env
vendor/
build/
backup STEP
runtime logs/cache/backups/debugbar
raw Personalia uploads
foto/branding/background runtime
secret/token/password
```

## 16. Aturan Perubahan Struktur

- Business rule tetap di Service.
- Controller tidak menjadi tempat agregasi rule domain besar.
- JS page-specific ditempatkan per modul.
- Route baru hanya ditambah bila endpoint memang diperlukan.
- File upload publik/non-public tidak boleh tertukar boundary.
- Dokumen canonical diperbarui setelah release contract berubah.


## Canonical UI/UX Document

```text
docs/11_UI_UX_GURU_WALAS_SISWA — SisisFour.md
```

Dokumen ini menjadi SSOT khusus inventory dan guardrail redesign experience Guru, Guru+Wali, dan Siswa.
