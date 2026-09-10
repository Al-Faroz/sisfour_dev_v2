# Tree Structure — SisisFour

**Versi Acuan:** v0.9 PHASE 3.2 FINAL POLISH  
**Tanggal Acuan:** 09 September 2026  
**Baseline Aplikasi:** `main` @ `c05466738012ea2da852fa3e878b6bbb897d6607` + paket Phase 3.2  
**Baseline Database:** `sisfour_dev_v2 (29).sql`

Dokumen ini adalah acuan struktur repository SisisFour setelah implementasi Personalia/Portofolio dan final polish Phase 3.2.

---

# 1. Prinsip Struktur

Project menggunakan CodeIgniter 4 dengan **document root langsung di root repository**.

```text
sisfour_dev_v2/
├── index.php
├── .htaccess
├── .gitignore
├── composer.json
├── composer.lock
├── phpunit.dist.xml
├── README.md
├── app/
├── assets/
├── database/
├── docs/
├── postman/
├── tests/
├── uploads/
├── vendor/                 # dependency lokal, tidak di-commit
└── writable/               # runtime CI4
```

Folder `public/` **bukan** document root runtime proyek ini.

# 2. `app/`

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
├── ThirdParty/
├── Views/
├── Common.php
└── index.html
```

Pola implementasi bisnis:

```text
Route
  -> Filter route gate
  -> Controller
  -> Service (authorization + business/data scope)
  -> Model / Query DB
  -> View / JSON
```

`PermissionFilter` bukan pengganti authorization pada Service.

# 3. Personalia / Portofolio Phase 3

File utama:

```text
app/
├── Controllers/
│   └── Personalia.php
├── Models/
│   ├── DokumenPersonaliaModel.php
│   ├── RiwayatPangkatModel.php
│   ├── RiwayatPendidikanModel.php
│   └── RiwayatPenugasanModel.php
├── Services/
│   ├── PersonaliaService.php
│   ├── PortfolioService.php
│   └── UploadService.php
└── Views/
    ├── personalia/
    │   ├── detail.php
    │   └── portfolio.php
    └── profile/
        ├── guru.php
        └── pegawai.php

assets/js/
└── personalia/
    └── detail.js
```

Dokumen mentah Personalia tidak ditempatkan pada `uploads/` public.

# 4. Master Guru/Pegawai

Core Guru dan Pegawai tetap terpisah:

```text
app/Controllers/MasterGuru.php
app/Controllers/MasterPegawai.php
app/Models/GuruModel.php
app/Models/PegawaiModel.php
app/Services/GuruService.php
app/Services/PegawaiService.php
app/Views/master/guru.php
app/Views/master/pegawai.php
assets/js/master/guru.js
assets/js/master/pegawai.js
```

Tidak ada modul gabungan `sdm` dan tidak ada role baru `pegawai`.

# 5. Frontend

```text
assets/
├── css/
├── img/
├── js/
│   ├── components/
│   ├── master/
│   ├── personalia/
│   ├── csrf-fetch.js
│   └── main.js
└── vendor/
```

Business UI utama menggunakan **Vanilla JS + Fetch API**. DataTables/Select2 tidak dimuat global; searchable select internal dipakai untuk kebutuhan pencarian.

# 6. Database Script

```text
database/
├── ... script checkpoint sebelumnya ...
└── 20260909_PHASE3_2_VERIFY_PERSONALIA_CHECKS.sql
```

Script Phase 3.2 hanya melakukan verifikasi schema live; tidak mengubah data.

Schema canonical didokumentasikan pada:

```text
docs/02_DATABASE — SisisFour.md
```

# 7. Dokumentasi Canonical

File acuan utama menggunakan satu nama yang konsisten:

```text
docs/
├── 00_POLA_PENGERJAAN___SisisFour.md
├── 01_MASTERPLAN — SisisFour.md
├── 02_DATABASE — SisisFour.md
├── 03_AUTH_RBAC_MENU — SisisFour.md
├── 04_MASTER_DATA — SisisFour.md
├── 05_PRESENSI — SisisFour.md
├── 06_LAPORAN — SisisFour.md
├── 07_BK_PRESTASI_KARTU — SisisFour.md
├── 08_DASHBOARD_SETTINGS_BACKUP — SisisFour.md
├── 09_PROFILE — SisisFour.md
├── 15_TESTING_POLISH — SisisFour.md
├── 16_MOBILE_CORDOVA — SisisFour.md
├── Routes Final — SisisFour.md
├── Tree Structure — SisisFour.md
├── PHASE3_1_INTEGRITY_HARDENING.md
├── PHASE3_2_FINAL_POLISH.md
└── _CATATAN_PERUBAHAN_20260909.md
```

Nama file encoding rusak seperti:

```text
09_PROFILE ΓÇö SisisFour.md
15_TESTING_POLISH ΓÇö SisisFour.md
```

bukan file canonical dan harus dihapus.

# 8. Upload Public

```text
uploads/
├── branding/
├── foto_siswa/
├── foto_guru/
├── foto_pegawai/
└── kartu_pelajar/
```

Semua file runtime di folder tersebut diabaikan Git. File pengaman seperti `.htaccess`/`index.html` boleh dipertahankan bila ada.

# 9. Upload Non-Public

Dokumen Personalia:

```text
writable/uploads/personalia/
├── guru/{id}/...
└── pegawai/{id}/...
```

Boundary:

- tidak dapat diakses langsung melalui URL public;
- file dikirim melalui endpoint `Personalia::file` setelah authorization Service;
- path database divalidasi agar tidak keluar dari prefix owner;
- seluruh file runtime `writable/uploads/` diabaikan Git.

# 10. Runtime / Build yang Tidak Di-commit

```text
vendor/
build/
writable/logs/*
writable/session/*
writable/cache/*
writable/backups/*
writable/debugbar/*
writable/uploads/*
uploads/foto_siswa/*
uploads/foto_guru/*
uploads/foto_pegawai/*
uploads/branding/*
uploads/kartu_pelajar/**/*
```

`build/` berisi output PHPUnit seperti cache, JUnit, TestDox, dan coverage. Source test tetap berada di `tests/` dan boleh di-commit.

# 11. Tests

```text
tests/
└── unit/
    └── PersonaliaHardeningTest.php
```

Gate Phase 3.1/3.2:

```powershell
vendor\bin\phpunit tests\unit\PersonaliaHardeningTest.php
```

Hasil build test disimpan lokal sesuai `phpunit.dist.xml`, tetapi tidak menjadi source repository.

# 12. Aturan Penambahan File

- Source PHP mengikuti layer yang sudah ada; jangan menaruh business rule langsung di View/JS bila dapat dipusatkan pada Service.
- JS modul ditempatkan page-specific dan memakai Vanilla JS + Fetch.
- Upload user tidak boleh masuk repository.
- File dokumen mentah Personalia harus tetap non-public.
- Dokumen acuan tidak boleh mempunyai duplikat nama akibat encoding.
- `Routes.php` hanya diubah bila route memang perlu berubah; Phase 3.2 tidak membutuhkan perubahan Routes.
