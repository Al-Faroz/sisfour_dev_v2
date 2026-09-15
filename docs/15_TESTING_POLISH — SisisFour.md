# Testing, Regression & Release Gate — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 15 September 2026

> Quality gate dibagi per phase agar regression bisnis, mobile UI, dan Cordova tidak bercampur.

## 1. Static Gate Umum

```powershell
php -l path\file.php
node --check path\file.js
php spark routes
git diff --check
git status --short
```

Tidak boleh ada syntax error, route target hilang, file tidak sengaja terhapus, atau whitespace conflict.

## 2. G2 Gate — Fixing & Stabilization

G2 wajib lulus:

```text
repository hygiene
F06–F14 business regression
Admin UI smoke
Login password toggle
favicon/branding
page title/navbar
pagination/filter/export yang berubah
active-year default/reset yang berubah
Profile Guru tabs
browser console
```

Status:

```text
G2.3 Business Regression   PASS
G2.4 Browser Regression    PASS
G2.5 Closure               IN PROGRESS
```

### F06 Guru

- create/update/import/export;
- identifier validation/sync;
- authorization first;
- dependency-protected permanent delete;
- user lifecycle/personalia dependency.

### F07 Pegawai

- CRUD/import/export;
- identifier validation;
- dependency-protected permanent delete;
- account/personalia relation.

### F08 Siswa

- NISN/NIK integrity;
- account creation/sync;
- import atomic;
- membership/history/card/BK dependency protection.

### F09 Kelas

- CRUD/recycle/restore;
- dependency delete protection;
- pagination/export UI regression.

### F10 Tahun Ajaran

`Siapkan Genap` contract:

```text
create target Genap
copy Kelas
copy active membership
copy Wali
copy active Jadwal
close source histories
open target histories
deactivate Ganjil
activate Genap
verify before commit
```

Presensi/Jurnal tidak disalin.

Jika Service transition tidak berubah setelah regression yang sudah PASS, tidak perlu mengulang destructive local transition tanpa alasan; lakukan targeted verification.

### F11 Mapel

- kode unique;
- edit nama dengan kode sendiri tetap valid;
- edit ke kode lain yang sudah ada tetap ditolak;
- dependency delete;
- pagination/export.

Final runtime retest F11 pada 15 September 2026: **PASS**.

### F12 Mapping Wali

- class/year integrity;
- 1 Guru/1 kelas aktif per tahun;
- history protected;
- Nonaktifkan → Assign baru sebagai workflow pergantian wali;
- restore mapping historis sesuai guard;
- pagination/export.

### F13 Jadwal Guru

- import;
- overlap Guru/Kelas;
- topology Sesi Awal/Non Sesi/Sesi Akhir;
- linked journal/delete protection;
- source cleanup memastikan tidak ada eksperimen inactive-Genap yang tersisa tanpa kebutuhan.

Core integrity regression F13 telah PASS; positive import tetap diuji hanya dengan snapshot/isolated data bila diperlukan karena import mengganti baseline aktif periode target secara atomic.

### F14 Manajemen Siswa

Wajib mencakup:

- penempatan/pindah;
- kenaikan antar tahun;
- mutasi;
- kelulusan;
- restore terminal lifecycle;
- transactional history/status/membership/card;
- anti-double-process;
- partial promotion;
- progress per kelas.

Kenaikan contract:

```text
source aktif = Genap
target        = Ganjil tahun ajaran berikutnya
7 → 8
8 → 9
kelas 9 → Kelulusan, bukan kenaikan biasa
```

Restore contract:

```text
terminal latest event saja
exact source period terminal masih aktif
terminal history dipertahankan
membership dipulihkan
history Aktif baru dibuka
status kembali Aktif
```

Status F14 per 15 September 2026: **PASS**.

## 3. Default Tahun Ajaran — Regression Gate

Surface selector Tahun Ajaran yang relevan harus mengikuti:

```text
initial load = periode aktif
Reset        = periode aktif
manual pilih histori tetap berfungsi bila halaman mendukung histori
export/filter mengikuti periode yang sedang dipilih
```

Surface minimum:

```text
Master Siswa
Master Kelas
Mapping Wali
Assign Wali
Master Jadwal Guru
Import Jadwal Guru
Laporan Jurnal
Matrix Presensi
Export Presensi
```

Tidak perlu helper/alert seperti “Default menampilkan Tahun Ajaran yang sedang aktif.” Default harus terbaca dari control terpilih.

Workflow current-state berikut tidak membutuhkan selector periode tambahan:

```text
Penempatan/Pindah
Mutasi
Kelulusan
Kenaikan
Presensi operasional
Jurnal operasional
Kartu Pelajar operasional
```

Focused regression active-year default/reset: **PASS**.

## 4. G2 Browser Smoke

Minimum:

```text
390×844
1024×768
1366×768
```

Final focused G2.4 result pada 15 September 2026 menggunakan data aktual hosting:

```text
Login                              PASS
Branding/Favicon                   PASS
Title/Navbar                       PASS
Filter/Pagination/Export           PASS
Presensi Mengajar Guru Search      PASS
Profile Guru mobile                PASS
Modal + responsive                 PASS
Browser console                    PASS
F11 edit Mapel kode sendiri        PASS
Active-year default/reset          PASS
```

Status G2.4: **PASS**.

Pixel-level mobile role redesign bukan gate G2.

## 5. Auth Web/API Baseline

Web:

- valid/invalid login;
- inactive account;
- lockout policy;
- logout;
- session DB;
- multi-role;
- CSRF mutation.

API:

- login/me/refresh/logout;
- missing/invalid token;
- effective permission/scope;
- version/maintenance response.

## 6. RBAC

Minimum actor:

```text
Admin
Operator
Pimpinan
BK
Guru
Guru + Wali
Siswa
multi-role relevan
```

Cek menu, direct URL, read, mutation, target scope, contextual Wali.

## 7. Presensi & Jurnal

Presensi Siswa:

- Guru Terjadwal;
- Wali kelas sendiri;
- Admin/Operator;
- forged target ditolak;
- time window/geofence;
- Sesi Awal/Akhir;
- atomic save;
- duplicate prevention;
- revisi actor sah.

Jurnal:

- Jadwal actor benar;
- status/materi;
- duplicate prevention;
- Non Sesi bila didukung;
- history Jadwal.

## 8. Laporan / BK / Kartu / Profile

Tetap uji scope, filter, export, detail, lifecycle, ownership, file validation, dan busy guard sesuai dokumen domain.

## 9. G3 Gate — Mobile Role UI

G3 tidak boleh dinyatakan ACC sebelum role Pimpinan/BK/Guru/Wali/Siswa lulus:

```text
360×800
375×812
390×844
412×915
768×1024
1024×768
1366×768
```

Acceptance:

- no body horizontal overflow;
- no horizontal table scroll pada role operasional;
- primary information berbasis Nama;
- NISN/NIP/NIK sekunder;
- KPI 2×2 mobile;
- touch target sesuai standard;
- modal/keyboard nyaman;
- filter compact;
- mutation busy guard;
- network failure tidak menghapus input penting;
- server-confirmed success untuk data akademik.

## 10. G3 Critical Workflows

Urutan test:

```text
Guru/Wali Presensi
Guru/Wali Jurnal
Dashboard Guru/Wali
BK Kasus/Tindak Lanjut
Dashboard BK
Dashboard Pimpinan
Dashboard/flow Siswa
```

## 11. G4 Gate — Cordova APK

Sebelum build final:

- G3 Web/mobile PASS;
- Cordova architecture spike PASS;
- real Android WebView test;
- safe-area;
- soft keyboard;
- Android Back;
- session/login behavior;
- geolocation permission/device GPS;
- network/offline state;
- file preview/download/share;
- internal/external link routing;
- maintenance behavior;
- no sensitive debug logging.

## 12. Cordova Mutation Safety

Tidak ada silent offline queue canonical untuk:

```text
Presensi
Jurnal
Kasus
Prestasi
```

Network failure = gagal/tertunda, bukan sukses palsu.

## 13. Production Security

Expected sensitive path tidak public. Uji CSRF, XSS, injection, IDOR, upload invalid, traversal, secret/log exposure, dan unauthorized API sesuai deployment contract.

## 14. Performance

- bounded DB query;
- index digunakan pada query besar;
- no N+1;
- pagination;
- dashboard hanya data ringkas;
- mobile tidak merender ratusan row;
- Kartu/PDF tetap dalam memory limit.

## 15. Current G2 Closure Rule

G2.3 business regression dan G2.4 focused browser regression sudah **PASS**.

Sebelum merge tetap wajib:

```text
final repository hygiene recheck
final static gate pada head terakhir
final docs/checklist sanity
PR review + PR body sync
explicit user approval
```

Database hosting/dump yang dipakai sebagai referensi regression tidak boleh masuk repository atau commit.

## 16. Phase Release Rule

```text
G2 PASS → boleh merge G2 setelah approval eksplisit
G3 PASS → mobile/WebView UI dianggap siap
G4 PASS → APK dapat masuk distribution gate
```

Setiap merge/release tetap membutuhkan approval eksplisit pengguna.
