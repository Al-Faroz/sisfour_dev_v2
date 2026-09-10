# Phase 3.2 — Final Polish & Documentation Sync

**Tanggal:** 09 September 2026  
**Baseline repo:** `main` @ `c05466738012ea2da852fa3e878b6bbb897d6607`  
**Baseline DB:** `sisfour_dev_v2 (29).sql`

Phase 3.2 bukan fase penambahan modul bisnis. Scope-nya menutup hasil audit setelah Phase 3.1: polish tampilan Personalia/Portofolio, repository hygiene, dan sinkronisasi dokumen acuan.

---

# 1. Perubahan Runtime

## 1.1 Profile Pegawai

Pesan `jabatan` legacy tidak lagi menyatakan riwayat penugasan sebagai modul fase berikutnya. Modul tersebut sudah tersedia. Pengguna diarahkan ke **Riwayat & Portofolio** untuk mencatat periode/SK yang benar.

## 1.2 Personalia Readonly

Actor readonly:

- tetap dapat melihat data riwayat sesuai scope;
- tidak melihat tombol mutation;
- tidak lagi melihat kolom `Aksi` yang hanya berisi label Readonly;
- tetap tidak dapat membuka raw document bila tidak mempunyai hak manage.

## 1.3 Responsive Tabs

Enam tab Personalia tetap satu baris dan dapat horizontal-scroll pada layar sempit.

## 1.4 Portofolio PDF

Ditambahkan page-break guard:

```text
section title -> page-break-after: avoid
row tabel     -> page-break-inside: avoid
```

Tujuannya mengurangi heading/row yang terpotong ketika riwayat panjang.

## 1.5 Viewport

Viewport utama menjadi:

```html
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
```

Zoom pengguna tidak lagi dinonaktifkan.

# 2. Repository Hygiene

`.gitignore` ditambah untuk:

```text
/build/
/writable/uploads/*
/uploads/foto_pegawai/*
```

Tujuan:

- PHPUnit output tidak ikut commit;
- raw document Personalia tidak ikut commit;
- foto Pegawai runtime tidak ikut commit.

Source test di `tests/` tetap boleh di-commit.

# 3. Dokumentasi

Canonical docs yang diperbarui:

```text
02_DATABASE — SisisFour.md
09_PROFILE — SisisFour.md
15_TESTING_POLISH — SisisFour.md
Tree Structure — SisisFour.md
_CATATAN_PERUBAHAN_20260909.md
PHASE3_2_FINAL_POLISH.md
```

Duplicate encoding berikut harus dihapus:

```text
09_PROFILE ΓÇö SisisFour.md
15_TESTING_POLISH ΓÇö SisisFour.md
```

# 4. Database

Phase 3.2 **tidak melakukan mutation database**.

File:

```text
database/20260909_PHASE3_2_VERIFY_PERSONALIA_CHECKS.sql
```

hanya membaca metadata/schema live dan menjalankan `SHOW CREATE TABLE` untuk:

```text
ci_sessions
riwayat_pendidikan
riwayat_penugasan
riwayat_pangkat
dokumen_personalia
```

Expected CHECK Personalia:

```text
chk_rp_owner
chk_rpen_owner
chk_rpen_periode
chk_rpk_owner
chk_dp_owner
```

# 5. Routes

Tidak ada perubahan `app/Config/Routes.php` pada Phase 3.2.

# 6. Static Gate

File PHP yang berubah wajib:

```powershell
php -l app\Views\_header.php
php -l app\Views\profile\pegawai.php
php -l app\Views\personalia\detail.php
php -l app\Views\personalia\portfolio.php
```

Tidak ada file JS yang berubah pada Phase 3.2.

# 7. Browser Checkpoint

Minimum:

1. login Admin dan buka Master Guru/Pegawai → Personalia;
2. pastikan CRUD actor manage tetap tampil;
3. login/akses actor readonly → kolom Aksi tidak tampil;
4. kecilkan viewport → tab dapat di-scroll horizontal;
5. buka Profile Pegawai → pesan legacy sudah benar;
6. preview/download Portofolio;
7. uji pinch-zoom pada mobile/WebView yang mendukung;
8. pastikan tidak ada 404/500/console error baru.

# 8. Git Checkpoint

Setelah menjalankan cleanup package:

```powershell
git status --short
git ls-files build
git ls-files writable/uploads
git ls-files uploads/foto_pegawai
```

Tiga `git ls-files` terakhir tidak boleh menampilkan file runtime.

# 9. Definition of Done

Phase 3.2 dapat dikunci bila:

- semua PHP lint lulus;
- UI Personalia manage/readonly lulus;
- Portofolio masih dapat dibuat;
- duplicate docs encoding sudah hilang;
- build/runtime upload tidak tracked;
- `02_DATABASE` konsisten dengan dump `(29)`;
- `SHOW CREATE TABLE` live DB dikonfirmasi untuk CHECK Personalia;
- tidak ada regression blocker Phase 1–3.1.
