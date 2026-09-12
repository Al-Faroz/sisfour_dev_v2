# Testing, Regression & Release Gate — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 12 September 2026
**Baseline Aplikasi:** `main` @ `39da4651acd29adcd575677d7a37c058bf32269d`
**Baseline Database:** `sisfour_dev_v2 (33).sql`

> Dokumen ini adalah quality gate release yang berlaku. Ia bukan catatan fase pengerjaan lama.

## 1. Static Gate

PHP yang berubah:

```powershell
php -l path\file.php
```

JavaScript yang berubah:

```powershell
node --check path\file.js
```

Release-wide:

```powershell
php spark routes
git diff --check
git status --short
```

Tidak boleh ada syntax error, route target hilang, atau source file tak sengaja dihapus.

## 2. Auth Web

Uji:

- login valid;
- password salah;
- username tidak ada;
- account nonaktif;
- lockout 5 kegagalan beruntun;
- logout POST;
- session database;
- multi-role;
- role NULL dengan identity Pegawai bila digunakan;
- login normal **tidak menaikkan `auth_version`**;
- credential/security change yang relevan menaikkan `auth_version`.

## 3. API Auth / RequestContext

Canonical request context:

```text
api_user
api_access_token
api_token_row
api_claims
```

Uji:

```text
/api/auth/login
/api/auth/me
/api/dashboard
satu endpoint role/profile relevan
request tanpa token -> 401
/api/auth/logout
token lama setelah logout -> ditolak
/api/auth/refresh
/api/version
```

Tidak boleh ada consumer runtime yang memakai literal key lama `apiUser`.

## 4. RBAC

Akun minimum:

```text
Admin
Operator
Pimpinan
BK
Guru
Guru + Wali
Siswa
Guru + Operator bila tersedia
Guru + Pimpinan bila tersedia
Pegawai dengan role operasional bila tersedia
```

Per role cek:

```text
sidebar/menu
direct URL
read
mutation
scope target
union primary+secondary role
contextual Wali
```

## 5. Master Guru/Pegawai

- CRUD.
- NIK business-required 16 digit untuk data baru.
- NIP optional/nullable legacy.
- identifier NIP bila ada, selain itu NIK.
- account sync NIK -> NIP bila NIP ditambahkan.
- managed password reset sesuai kontrak Service.
- duplicate lintas Guru/Pegawai ditolak.
- import atomic.
- export.
- foto.
- recycle/restore.
- user lifecycle.
- Personalia/Portofolio.

## 6. Master Siswa / Manajemen Siswa

- NISN unique.
- NIK valid sesuai Service.
- auto account.
- Wali hanya kelas sendiri.
- Wali tidak mengubah NISN.
- import/export.
- upload foto.
- penempatan/pindah kelas.
- kenaikan.
- mutasi.
- kelulusan.
- histori kelas.
- lifecycle Kartu.
- pagination server-side.

## 7. Kelas, Tahun, Mapel, Wali, Jadwal

- satu Tahun Ajaran operasional aktif.
- membership satu siswa/tahun.
- dependency delete.
- Mapping Wali 1 Guru/1 kelas aktif per tahun.
- Wali bukan role.
- Jadwal import.
- overlap Guru ditolak.
- overlap Kelas ditolak.
- jadwal Nonaktif tetap dapat mendukung histori/laporan yang relevan.

## 8. Presensi Siswa

Uji:

- Admin/Operator.
- Guru Terjadwal.
- forged kelas/Jadwal ditolak.
- Wali kelas sendiri.
- dual Guru+Wali.
- time-window.
- geofence bila aktif.
- Sesi Awal/Akhir.
- default UI Hadir dan perubahan S/I/A.
- bulk atomic/rollback.
- duplicate prevention.
- revisi hanya actor berwenang.
- snapshot histori.
- Dashboard/status Siswa Sesi Awal.

## 9. Presensi Mengajar/Jurnal

- jadwal aktif.
- actor Guru benar.
- duplicate ditolak.
- materi/status.
- semua sesi yang didukung termasuk Non Sesi untuk Jurnal.
- scope view/input.
- histori Jadwal Nonaktif.

## 10. Laporan

- Matrix Sesi Awal.
- tanggal di luar membership bukan Alpha otomatis.
- bulanan/semester.
- Jurnal/export Jurnal.
- Wali hanya kelas sendiri.
- Pimpinan/Admin/Operator sesuai permission.
- query bounded/no N+1.

## 11. BK/Prestasi

- Master Pelanggaran CRUD.
- Kasus scope.
- detail/tindak lanjut 1:N.
- Prestasi.
- export.
- searchable student.
- readonly actor tidak mutation.
- double-submit UI tidak menghasilkan mutation ganda.

## 12. Kartu Pelajar

- satu kartu Aktif.
- generate tunggal.
- generate bulk hingga 200 per batch.
- reissue.
- preview/download.
- QR/public verify.
- background override/fallback.
- cetak massal front/back.
- 10 kartu/A4.
- hingga 200 kartu/request sesuai limit.
- sisi belakang tidak membangkitkan QR/foto yang tidak digunakan.
- lifecycle kartu.

## 13. Profile/Personalia

- Guru self.
- Pegawai self tanpa role `pegawai`.
- Siswa readonly.
- owner check Personalia.
- Admin/Operator manage target.
- readonly actor tidak mutation/raw document.
- upload PDF/PNG/JPG valid.
- >5 MB ditolak.
- traversal/absolute/owner silang ditolak.
- Portofolio PDF.

## 14. Dashboard/UI

- role priority `admin > operator > pimpinan > bk > guru > siswa`.
- contextual Wali quick links permission-gated.
- sidebar satu active item paling spesifik.
- parent kosong tidak tampil.
- loading/error/empty state masuk akal.
- mobile responsive.
- WebView tidak pecah.
- session-expired Fetch kembali ke login, bukan JSON/PDF parse error.

## 15. Signage

Tanpa login:

```text
/signage      -> 200
/signage/data -> data JSON
```

Verifikasi:

- Sesi Awal only;
- Top 20 Alpha/Izin/Sakit;
- Tidak Masuk Hari Ini S/I/A;
- nama siswa tampil;
- ranking 14 hari;
- refresh 5 menit;
- cache 240 detik;
- tidak membutuhkan session/token.

## 16. Settings/Maintenance/Backup/Log

Settings:

- User create/update/reset/delete;
- managed credential;
- secondary role;
- Menu;
- geofence;
- branding/background;
- busy guard.

Maintenance:

- Admin recovery Web/AJAX;
- non-Admin 503;
- API JSON 503.

Backup:

- create/download/delete;
- traversal ditolak;
- permission.

Log:

- event penting tercatat;
- pagination/filter/export;
- tidak ada credential/token leak.

## 17. Security Production

Expected:

```text
/.env               -> 403/404
/composer.json      -> 403/404
/app/Config/App.php -> 403/404
/docs/              -> 403/404
/signage            -> 200
```

Uji juga:

- CSRF mutation Web;
- SQL injection;
- XSS;
- IDOR;
- forged actor/target;
- upload invalid;
- path traversal;
- sensitive log;
- unauthorized API.

## 18. Performance

Untuk query besar:

```sql
EXPLAIN SELECT ...
```

Periksa:

```text
index digunakan
bounded rows
LIMIT/OFFSET
no N+1
no unbounded aggregation di PHP
memory PDF Kartu
Signage cache
```

## 19. Release Criteria

Release dinyatakan layak bila:

- PHP/JS static gate PASS;
- `spark routes` normal;
- RBAC role utama PASS;
- Auth Web/API PASS;
- Presensi core PASS;
- Kartu front/back PASS;
- BK/Settings mutation PASS;
- Signage public PASS;
- sensitive-path hardening PASS;
- tidak ada blocker 404/500;
- `.env`/secret production tidak masuk Git;
- dump database dan upload runtime tersedia untuk deployment/rollback.


## 20. UI/UX Guru–Walas–Siswa

Setiap perubahan UI pada tiga experience wajib diuji pada:

```text
Guru non-Wali
Guru + Wali
Siswa
```

Cek:

- sidebar/menu sesuai context dan tetap aman pada direct URL;
- Dashboard tidak membocorkan data lintas scope;
- Wali tetap context Guru, bukan role baru;
- status Siswa hari ini tetap bersumber dari Sesi Awal;
- empty/loading/error state;
- desktop, laptop, mobile/WebView;
- active/open sidebar;
- teks panjang dan data kosong;
- mutation button busy guard/double-submit;
- session-expired recovery;
- browser back/refresh tidak merusak state penting.

Mockup/render visual hanya menjadi bahan review. Quality gate memakai source yang benar-benar diimplementasikan.
