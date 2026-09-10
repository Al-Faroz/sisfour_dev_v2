# Testing & Polish — SisisFour

**Versi Acuan:** v0.9 PHASE 3.2 FINAL POLISH  
**Tanggal Acuan:** 09 September 2026  
**Baseline Aplikasi:** `main` @ `c05466738012ea2da852fa3e878b6bbb897d6607` + paket Phase 3.2  
**Baseline Database:** `sisfour_dev_v2 (29).sql`

Checklist ini adalah quality gate SisisFour setelah Phase 3.1 hardening dan Phase 3.2 final polish.

---

# 1. Static Gate

Untuk setiap file PHP yang berubah:

```powershell
php -l path\file.php
```

Untuk setiap file JS yang berubah:

```powershell
node --check path\file.js
```

Test unit Phase 3.1:

```powershell
vendor\bin\phpunit tests\unit\PersonaliaHardeningTest.php
```

# 2. Auth & Session

Uji:

- login valid;
- password salah;
- username tidak ada;
- user nonaktif;
- role NULL yang memang mempunyai identity Pegawai;
- multi-role;
- lockout;
- auth_version;
- logout;
- LOGIN/LOGOUT tercatat.

## Session database Phase 3.1

`ci_sessions.timestamp` wajib bertipe:

```text
DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
```

Setelah menjalankan SQL hardening, `ci_sessions` sengaja kosong dan semua account Web harus login ulang.

Setelah satu login baru:

```sql
SELECT id, ip_address, timestamp
FROM ci_sessions
ORDER BY timestamp DESC
LIMIT 5;
```

`timestamp` harus berisi waktu valid, bukan `4294967295`.

# 3. RBAC

Akun minimum:

```text
Admin
Operator
Pimpinan
BK
Guru
Guru Wali
Siswa
Guru+Operator
Guru+Pimpinan
Pegawai dengan role operasional
```

Uji menu, direct URL, mutation, scope, dan union effective role.

Wali Kelas tetap context dari `mapping_wali_kelas`, bukan role terpisah.

# 4. Master Guru/Pegawai

- CRUD;
- NIK wajib untuk create/edit/import baru;
- NIP nullable;
- login identifier NIP jika ada, selain itu NIK;
- perubahan NIK→NIP melakukan account sync sesuai Phase 2;
- duplicate identity lintas Guru/Pegawai ditolak;
- import atomic;
- export;
- foto;
- recycle/restore;
- force delete dependency;
- user lifecycle;
- Profile identity tetap konsisten.

# 5. Personalia Guru/Pegawai

Tabel:

```text
riwayat_pendidikan
riwayat_penugasan
riwayat_pangkat
dokumen_personalia
```

Uji:

- owner Guru valid;
- owner Pegawai valid;
- dua owner sekaligus ditolak DB;
- tanpa owner ditolak DB;
- tanggal selesai penugasan < tanggal mulai ditolak DB/Service;
- self Guru CRUD hanya diri sendiri;
- self Pegawai CRUD hanya diri sendiri;
- Admin/Operator manage dapat CRUD target;
- Pimpinan readonly hanya melihat;
- self-delete record sendiri berjalan;
- delete record identity lain ditolak;
- mutation tercatat pada log activity.

# 6. Dokumen Personalia

- PDF valid diterima;
- PNG valid diterima;
- JPG/JPEG valid diterima;
- >5 MB ditolak;
- executable/format lain ditolak;
- PDF tanpa signature `%PDF-` ditolak;
- image rusak ditolak;
- kategori dokumen di luar whitelist ditolak;
- edit tanpa file mempertahankan file lama;
- penggantian file menghapus file lama setelah commit;
- raw document hanya self/Admin/Operator manage;
- readonly actor tidak dapat raw document;
- path `../` ditolak;
- absolute Windows/Unix path ditolak;
- path di luar `uploads/personalia/` ditolak;
- path owner berbeda ditolak;
- extension storage selain PDF/PNG/JPG/JPEG ditolak.

# 7. Portofolio

- PDF A4 dapat dibuka;
- Guru dan Pegawai dapat generate sesuai scope;
- NIP hanya tampil bila ada;
- NIK tetap tersedia pada identitas;
- pendidikan terbaru di atas;
- penugasan terbaru di atas;
- pangkat terbaru di atas;
- Pegawai dapat memakai jabatan legacy sebagai fallback display;
- dokumen mentah tidak disisipkan;
- remote resource Dompdf tidak diperlukan.

# 8. Master Siswa / Manajemen Siswa

- NIK 16 digit;
- NISN unique;
- auto user;
- update Admin;
- Wali tidak dapat mengubah NISN;
- upload foto;
- import/export;
- penempatan/pindah kelas;
- kenaikan;
- mutasi;
- kelulusan;
- kartu Nonaktif sesuai lifecycle;
- histori kelas;
- recycle.

# 9. Kelas/Tahun/Mapel

- unique;
- membership one-per-year;
- active Tahun tunggal;
- Mapel dependency;
- recycle/restore sesuai dependency.

# 10. Mapping Wali/Jadwal

- one Guru active mapping;
- one Kelas active mapping;
- Wali sebagai context, bukan role;
- soft delete/reassign/restore;
- import Jadwal;
- overlap Guru ditolak;
- overlap Kelas ditolak;
- histori Jadwal Nonaktif tetap terbaca sesuai kebutuhan laporan.

# 11. Presensi Siswa

- Admin/Operator;
- Guru Terjadwal;
- kelas bukan Jadwal ditolak;
- Wali kelas;
- dual Guru+Wali;
- window waktu;
- geofence bila aktif;
- Sesi Awal/Akhir;
- bulk rollback;
- duplicate;
- revisi sesuai actor;
- snapshot/histori;
- Siswa diri.

# 12. Presensi Mengajar & Jurnal

- jadwal aktif hari ini;
- Hadir/Izin/Sakit sesuai kontrak;
- forged Guru ditolak;
- duplicate ditolak;
- revisi sesuai permission;
- jurnal semua sesi termasuk Non Sesi;
- materi;
- histori Jadwal Nonaktif.

# 13. Laporan / Dashboard / EWS

- Matrix;
- membership historis;
- Sesi Akhir tidak masuk total resmi;
- export bulanan/semester;
- Jurnal dan export Jurnal;
- scope Wali/Pimpinan/Admin/Operator;
- Dashboard effective role;
- contextual Wali;
- EWS 14 hari;
- Siswa diri;
- query bounded.

# 14. BK / Prestasi

- Kasus scope;
- Tindak Lanjut 1:N;
- Prestasi;
- searchable student remote;
- readonly scope sesuai actor;
- mutation sesuai permission.

# 15. Kartu Pelajar

- satu kartu Aktif;
- generate tunggal;
- bulk generate;
- reissue;
- render depan/belakang;
- cetak massal A4;
- QR;
- public verify;
- lifecycle kartu.

# 16. Digital Signage

Public route:

```text
/signage
/signage/data
```

Uji tanpa login:

- EWS Alpha Sesi Awal >=3 / 14 hari;
- kelas belum Presensi Sesi Awal hari ini;
- Guru belum Presensi Mengajar setelah `jam_selesai + 15 menit`;
- refresh 20 menit;
- realtime clock;
- auto-scroll;
- tidak ada token/session requirement.

# 17. Settings / Backup / Log

Settings:

- User Management;
- secondary role;
- reset password;
- auth_version;
- last Admin protection;
- Menu & Role;
- geofence;
- branding;
- maintenance.

Backup:

- create;
- download;
- delete;
- traversal ditolak;
- permission.

Log:

- Login/Logout;
- Personalia mutation;
- Backup;
- Settings;
- search/filter/pagination/CSV;
- no credential leak.

# 18. CSRF / API / Security

Mutation Web:

```text
POST
PUT
PATCH
DELETE
```

Request tanpa CSRF ditolak.

API:

- login/me/refresh/logout;
- token expired/revoked;
- endpoint bisnis;
- scope;
- no data leak.

Security minimum:

- SQL injection;
- XSS;
- IDOR;
- forged actor;
- forged target;
- direct mutation;
- upload invalid;
- path traversal;
- token leak;
- sensitive log.

# 19. Performance

Untuk query besar gunakan:

```sql
EXPLAIN SELECT ...
```

Periksa index, bounded rows, no N+1, dan tidak ada unbounded load pada Presensi/Matrix/EWS/Dashboard/Log.

# 20. Phase 3.2 UI / Repository Hygiene

Uji tampilan dan repository:

- tab Personalia pada mobile dapat di-scroll horizontal dan tidak memaksa layout melebar;
- actor readonly tidak melihat kolom `Aksi`;
- pesan jabatan legacy Pegawai sudah mengarahkan ke Riwayat & Portofolio, bukan menyebut fase berikutnya;
- PDF Portofolio dengan banyak row tidak memotong row tabel di tengah halaman sejauh dukungan Dompdf;
- viewport tidak mengunci pinch-zoom;
- hanya ada satu file canonical `09_PROFILE — SisisFour.md`;
- hanya ada satu file canonical `15_TESTING_POLISH — SisisFour.md`;
- tidak ada nama dokumen rusak `ΓÇö`;
- `build/` tidak tracked Git;
- `writable/uploads/` tidak tracked Git;
- `uploads/foto_pegawai/` tidak tracked Git.

Command checkpoint:

```powershell
git status --short
git ls-files build
git ls-files writable/uploads
git ls-files uploads/foto_pegawai
```

Tiga command `git ls-files` terakhir tidak boleh menampilkan file runtime.

# 21. Fresh Database Expected

Setelah Phase 3.1/3.2:

```text
32 tabel
43 permission
users.role nullable
unique users.id_guru/id_pegawai/id_siswa
4 tabel Personalia
1 tabel tindak_lanjut_kasus
ci_sessions.timestamp = DATETIME
CHECK owner XOR Personalia aktif
CHECK periode Penugasan aktif
FK/index sesuai schema
```

# 22. Release Criteria

Release/checkpoint berikut hanya jika:

- PHP/JS static gate lulus;
- PersonaliaHardeningTest lulus;
- `SHOW CREATE TABLE ci_sessions` benar;
- `SHOW CREATE TABLE` 4 tabel Personalia menampilkan CHECK yang diharapkan;
- browser Personalia/Portfolio lulus;
- RBAC/IDOR lulus;
- raw document boundary lulus;
- tidak ada 404/500 blocker;
- dump database terbaru dibuat setelah hardening;
- dokumen database sudah sinkron ke dump `(29)`;
- duplicate docs encoding lama sudah dihapus;
- artifact PHPUnit/build tidak lagi tracked.
