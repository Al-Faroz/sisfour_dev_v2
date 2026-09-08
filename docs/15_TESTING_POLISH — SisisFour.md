# Testing & Polish — SisisFour

**Versi Acuan Utama:** v0.5 FINAL BASELINE  
**Tanggal Acuan:** 08 September 2026  
**Baseline Aplikasi:** `main` @ `b85b857e1a38b6eb1fd26ba2d9aa61ae5e679f55`  
**Baseline Database:** `sisfour_dev_v2 (15).sql`

Dokumen ini adalah **acuan utama** SisisFour. Isinya menyatakan kontrak dan kondisi baseline yang berlaku, bukan riwayat perubahan.

---

# 1. Tujuan

Checklist ini adalah quality gate sebelum SisisFour dinyatakan release-ready.

# 2. Blocker Baseline

## BLOCKER-PROFILE-01

Route dan permission Profile tersedia, tetapi `ProfileGuru.php` dan `ProfileSiswa.php` tidak ditemukan pada Controller baseline.

Sebelum release:

```text
route + implementation harus konsisten
```

## POLISH-MENU-01

Operator mempunyai `log_activity.view`, tetapi tidak mempunyai menu Log Activity di `role_menus`.

Putuskan:

```text
tambahkan menu Operator
atau
pertahankan direct-url-only secara sadar
```

# 3. Static

```powershell
php -l app\...
node --check assets\js\...
```

# 4. Browser & Responsive

Uji desktop/laptop/tablet/mobile.

Login:

- logo dari Setting Sistem;
- favicon;
- SisFour Dev;
- nama sekolah;
- pesan lokasi;
- tidak overflow;
- form usable.

Footer:

```text
By : LemahTeles
```

# 5. Auth

- login valid;
- password salah;
- username tidak ada;
- user nonaktif;
- role NULL;
- multi-role;
- lock 5 menit;
- auth_version;
- logout;
- LOGIN/LOGOUT tercatat.

# 6. Maintenance

OFF normal.

ON:

- login page terbuka;
- Admin login;
- non-Admin 503;
- Admin akses;
- API JSON 503;
- logout;
- Admin dapat OFF-kan Maintenance.

# 7. RBAC

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
```

Uji menu, direct URL, mutation, scope.

# 8. Master Guru/Pegawai

- CRUD;
- duplicate NIP lintas tabel;
- import atomic;
- export;
- foto;
- recycle;
- restore;
- force delete dependency;
- user lifecycle.

# 9. Master Siswa

- NIK 16 digit;
- NISN unique;
- auto user;
- update Admin;
- Wali tidak dapat NISN;
- upload foto;
- import/export;
- mutasi;
- Lulus;
- kartu Nonaktif;
- histori;
- recycle.

# 10. Kelas/Tahun/Mapel

- unique;
- membership one-per-year;
- kenaikan;
- kelulusan;
- active Tahun tunggal;
- Mapel dependency.

# 11. Mapping Wali/Jadwal

- one Guru active mapping;
- one Kelas active mapping;
- soft delete;
- reassign/restore;
- import Jadwal;
- overlap Guru;
- overlap Kelas;
- histori Nonaktif.

# 12. Presensi Siswa

- Admin/Operator;
- Guru Terjadwal;
- kelas bukan Jadwal;
- Wali;
- dual Guru+Wali;
- before/inside/after window;
- geofence;
- Sesi Awal/Akhir;
- bulk rollback;
- duplicate;
- revisi;
- snapshot;
- Siswa diri.

# 13. Jurnal

- semua sesi;
- Non Sesi;
- materi;
- Hadir/Izin/Sakit;
- forged Guru ditolak;
- duplicate;
- revisi Admin/Operator;
- histori Jadwal Nonaktif.

# 14. Laporan

- Matrix;
- membership historis;
- Sesi Akhir tidak masuk total;
- export bulanan;
- export semester;
- Jurnal;
- export Jurnal;
- scope.

# 15. Dashboard

- effective role;
- contextual Wali;
- kelas wajib Presensi dari Jadwal;
- Jurnal belum per Jadwal;
- EWS 14 hari;
- Siswa diri;
- query bounded.

# 16. BK/Prestasi/Kartu

- Kasus scope;
- Top 20;
- Prestasi;
- satu kartu Aktif;
- generate batch;
- reissue;
- render depan/belakang;
- QR;
- public verify;
- lifecycle kartu.

# 17. Settings

- User Management;
- role NULL Pegawai;
- secondary role;
- reset password;
- auth_version;
- last Admin protection;
- Menu & Role;
- geofence;
- nama/alamat;
- logo/icon;
- KTA background;
- maintenance.

# 18. Backup

- create;
- filename;
- file > 0;
- CREATE + INSERT;
- download;
- delete;
- traversal ditolak;
- permission.

# 19. Log Activity

- Login;
- Logout;
- Backup;
- Settings/event lain yang memang dilog;
- search;
- filter;
- pagination;
- CSV;
- no credential;
- Admin;
- Operator direct URL;
- role lain ditolak.

# 20. API

- login;
- me;
- refresh;
- logout;
- token expired/revoked;
- endpoint bisnis;
- scope;
- no data leak.

# 21. CSRF

Mutation Web:

```text
POST
PUT
PATCH
DELETE
```

Request sah membawa token. Request tanpa token ditolak.

# 22. Security

- SQL injection;
- XSS;
- IDOR;
- forged actor;
- forged target;
- direct mutation;
- upload invalid;
- backup traversal;
- token leak;
- sensitive log.

# 23. Performance

Untuk query besar:

```sql
EXPLAIN SELECT ...
```

Periksa:

- index;
- bounded rows;
- no N+1;
- no unbounded `findAll()` Presensi;
- Matrix praktis;
- EWS praktis;
- Dashboard praktis;
- Log server-side pagination.

# 24. Fresh Install

Expected:

```text
27 tabel
43 permission
users.role nullable
generated unique Mapping Wali
generated id_siswa_aktif Kartu
FK/index sesuai baseline
```

# 25. Release Criteria

Release hanya jika:

- BLOCKER-PROFILE-01 selesai;
- POLISH-MENU-01 diputuskan;
- semua route target ada;
- tidak ada 404/500 blocker;
- RBAC lulus;
- CSRF lulus;
- Maintenance lulus;
- Backup lulus;
- security critical lulus;
- responsive login/footer lulus;
- fresh database lulus.
