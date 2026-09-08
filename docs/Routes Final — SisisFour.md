# Routes Final — SisisFour

**Versi Acuan Utama:** v0.5 FINAL BASELINE  
**Tanggal Acuan:** 08 September 2026  
**Baseline Aplikasi:** `main` @ `b85b857e1a38b6eb1fd26ba2d9aa61ae5e679f55`  
**Baseline Database:** `sisfour_dev_v2 (15).sql`

Dokumen ini adalah **acuan utama** dan menggambarkan baseline aplikasi yang berlaku. Bagian yang belum tersedia di repo dinyatakan sebagai gap/blocker, bukan diasumsikan sudah selesai.

---

# 1. Prinsip

Source of truth route:

```text
app/Config/Routes.php
```

AutoRoute tidak digunakan sebagai kontrak aplikasi. Route bisnis ditulis eksplisit.

Wali Kelas bukan role.

Filter:

```text
auth
auth:api
permission:...
```

Scope tetap divalidasi Service.

# 2. Public & Auth Web

| Method | Route | Target | Filter |
|---|---|---|---|
| GET | `/` | `Auth::login` | Public |
| GET | `/auth/login` | `Auth::login` | Public |
| POST | `/auth/login` | `Auth::login` | Public |
| POST | `/auth/logout` | `Auth::logout` | `auth` |
| GET | `/kartu/verify/(:segment)` | `KartuPelajar::verify/$1` | Public |

# 3. Auth/API Public

| Method | Route | Target | Filter |
|---|---|---|---|
| POST | `/api/auth/login` | `Auth::apiLogin` | Public |
| POST | `/api/auth/refresh` | `Auth::apiRefresh` | Public |
| POST | `/api/auth/logout` | `Auth::apiLogout` | `auth:api` |
| GET | `/api/auth/me` | `Auth::apiMe` | `auth:api` |
| GET | `/api/version` | `Api::version` | Public |

**Baseline blocker:** `app/Controllers/Api.php` tidak ditemukan.

# 4. Dashboard Web

| Method | Route | Permission |
|---|---|---|
| GET | `/dashboard` | `dashboard.view` |
| GET | `/dashboard/data` | `dashboard.view` |

# 5. Master Guru

| Method | Route | Permission |
|---|---|---|
| GET | `/master/guru` | `master_guru.manage OR master_guru.view` |
| GET | `/master/guru/json` | sama |
| GET | `/master/guru/template` | `master_guru.manage` |
| POST | `/master/guru/create` | `master_guru.manage` |
| POST | `/master/guru/import` | `master_guru.manage` |
| GET | `/master/guru/export` | `master_guru.manage` |
| PUT | `/master/guru/update/(:segment)` | `master_guru.manage` |
| DELETE | `/master/guru/delete/(:segment)` | `master_guru.manage` |
| POST | `/master/guru/upload-foto/(:segment)` | `master_guru.manage` |
| GET | `/master/guru/recycle` | `master_guru.manage` |
| GET | `/master/guru/recycle/json` | `master_guru.manage` |
| POST | `/master/guru/restore/(:segment)` | `master_guru.manage` |
| DELETE | `/master/guru/force-delete/(:segment)` | `master_guru.manage` |

# 6. Master Pegawai

| Method | Route | Permission |
|---|---|---|
| GET | `/master/pegawai` | `master_pegawai.manage OR master_pegawai.view` |
| GET | `/master/pegawai/json` | sama |
| GET | `/master/pegawai/template` | `master_pegawai.manage` |
| POST | `/master/pegawai/create` | `master_pegawai.manage` |
| POST | `/master/pegawai/import` | `master_pegawai.manage` |
| GET | `/master/pegawai/export` | `master_pegawai.manage` |
| PUT | `/master/pegawai/update/(:segment)` | `master_pegawai.manage` |
| DELETE | `/master/pegawai/delete/(:segment)` | `master_pegawai.manage` |
| GET | `/master/pegawai/recycle` | `master_pegawai.manage` |
| GET | `/master/pegawai/recycle/json` | `master_pegawai.manage` |
| POST | `/master/pegawai/restore/(:segment)` | `master_pegawai.manage` |
| DELETE | `/master/pegawai/force-delete/(:segment)` | `master_pegawai.manage` |

# 7. Master Siswa

| Method | Route | Permission |
|---|---|---|
| GET | `/master/siswa` | `master_siswa.view` |
| GET | `/master/siswa/json` | `master_siswa.view` |
| GET | `/master/siswa/template` | `master_siswa.import_export` |
| POST | `/master/siswa/create` | `master_siswa.manage` |
| POST | `/master/siswa/import` | `master_siswa.import_export` |
| GET | `/master/siswa/export` | `master_siswa.import_export` |
| PUT | `/master/siswa/update/(:segment)` | `master_siswa.edit_biodata` |
| DELETE | `/master/siswa/delete/(:segment)` | `master_siswa.manage` |
| GET | `/master/siswa/recycle` | `master_siswa.manage` |
| GET | `/master/siswa/recycle/json` | `master_siswa.manage` |
| POST | `/master/siswa/restore/(:segment)` | `master_siswa.manage` |
| DELETE | `/master/siswa/force-delete/(:segment)` | `master_siswa.manage` |
| POST | `/master/siswa/mutasi/(:segment)` | `master_siswa.manage` |
| POST | `/master/siswa/upload-foto/(:segment)` | `master_siswa.edit_biodata` |

# 8. Master Kelas

| Method | Route | Permission |
|---|---|---|
| GET | `/master/kelas` | `master_kelas.manage` |
| GET | `/master/kelas/json` | `master_kelas.manage` |
| POST | `/master/kelas/create` | `master_kelas.manage` |
| PUT | `/master/kelas/update/(:segment)` | `master_kelas.manage` |
| DELETE | `/master/kelas/delete/(:segment)` | `master_kelas.manage` |
| GET | `/master/kelas/recycle` | `master_kelas.manage` |
| GET | `/master/kelas/recycle/json` | `master_kelas.manage` |
| POST | `/master/kelas/restore/(:segment)` | `master_kelas.manage` |
| DELETE | `/master/kelas/force-delete/(:segment)` | `master_kelas.manage` |
| GET | `/master/kelas/anggota/(:segment)` | `master_kelas.manage` |
| POST | `/master/kelas/anggota/add/(:segment)` | `master_kelas.manage` |
| DELETE | `/master/kelas/anggota/remove/(:segment)/(:segment)` | `master_kelas.manage` |
| GET | `/master/kelas/process-data/(:segment)` | `master_kelas.manage` |
| POST | `/master/kelas/naik/(:segment)` | `master_kelas.manage` |
| POST | `/master/kelas/lulus/(:segment)` | `master_kelas.manage` |

# 9. Tahun Ajaran

| Method | Route | Permission |
|---|---|---|
| GET | `/master/tahun` | `master_tahun_ajaran.manage` |
| GET | `/master/tahun/json` | sama |
| POST | `/master/tahun/create` | sama |
| PUT | `/master/tahun/update/(:segment)` | sama |
| POST | `/master/tahun/aktifkan/(:segment)` | sama |
| DELETE | `/master/tahun/delete/(:segment)` | sama |
| GET | `/master/tahun/recycle` | sama |
| GET | `/master/tahun/recycle/json` | sama |
| POST | `/master/tahun/restore/(:segment)` | sama |
| DELETE | `/master/tahun/force-delete/(:segment)` | sama |

# 10. Mata Pelajaran

| Method | Route | Permission |
|---|---|---|
| GET | `/master/mapel` | `master_mapel.manage` |
| GET | `/master/mapel/json` | sama |
| POST | `/master/mapel/create` | sama |
| PUT | `/master/mapel/update/(:segment)` | sama |
| DELETE | `/master/mapel/delete/(:segment)` | sama |

# 11. Mapping Wali

| Method | Route | Permission |
|---|---|---|
| GET | `/master/wali-kelas` | `mapping_wali.view OR mapping_wali.manage OR mapping_wali.view_all` |
| GET | `/master/wali-kelas/json` | sama |
| POST | `/master/wali-kelas/assign` | `mapping_wali.manage` |
| DELETE | `/master/wali-kelas/delete/(:segment)` | `mapping_wali.manage` |
| GET | `/master/wali-kelas/options` | `mapping_wali.manage` |
| GET | `/master/wali-kelas/recycle` | `mapping_wali.manage` |
| GET | `/master/wali-kelas/recycle/json` | `mapping_wali.manage` |
| POST | `/master/wali-kelas/restore/(:segment)` | `mapping_wali.manage` |
| DELETE | `/master/wali-kelas/force-delete/(:segment)` | `mapping_wali.manage` |

# 12. Jadwal Guru

| Method | Route | Permission |
|---|---|---|
| GET | `/master/jadwal` | `jadwal_guru.view OR jadwal_guru.view_all OR jadwal_guru.manage` |
| GET | `/master/jadwal/json` | sama |
| GET | `/master/jadwal/options` | sama |
| GET | `/master/jadwal/template` | `jadwal_guru.manage` |
| POST | `/master/jadwal/import` | `jadwal_guru.manage` |
| GET | `/master/jadwal/export` | `jadwal_guru.manage` |
| DELETE | `/master/jadwal/delete/(:segment)` | `jadwal_guru.manage` |

# 13. Presensi Siswa Web

| Method | Route | Permission |
|---|---|---|
| GET | `/presensi/siswa` | `presensi_siswa.input` |
| GET | `/presensi/siswa/input/(:segment)` | `presensi_siswa.input` |
| GET | `/presensi/siswa/input/(:segment)/json` | `presensi_siswa.input` |
| POST | `/presensi/siswa/save` | `presensi_siswa.input` |
| GET | `/presensi/siswa/revisi/(:segment)` | `presensi_siswa.revisi` |
| POST | `/presensi/siswa/revisi/save` | `presensi_siswa.revisi` |
| GET | `/presensi/siswa/rekap` | `presensi_siswa.view` |
| GET | `/presensi/siswa/rekap/json` | `presensi_siswa.view` |
| GET | `/presensi/siswa/ews` | `ews_radar.view` |
| GET | `/presensi/siswa/ews/json` | `ews_radar.view` |

# 14. Presensi Mengajar Web

| Method | Route | Permission |
|---|---|---|
| GET | `/presensi/mengajar` | `presensi_mengajar.input` |
| GET | `/presensi/mengajar/input/(:segment)` | `presensi_mengajar.input` |
| GET | `/presensi/mengajar/input/(:segment)/json` | `presensi_mengajar.input` |
| POST | `/presensi/mengajar/save` | `presensi_mengajar.input` |
| GET | `/presensi/mengajar/laporan` | `presensi_mengajar.view` |
| GET | `/presensi/mengajar/laporan/json` | `presensi_mengajar.view` |

# 15. Laporan Web

| Method | Route | Permission |
|---|---|---|
| GET | `/laporan/presensi/matrix` | `laporan_matrix.view` |
| GET | `/laporan/presensi/matrix/json` | `laporan_matrix.view` |
| GET | `/laporan/presensi/export` | `laporan_export.generate` |
| GET | `/laporan/presensi/export/bulan` | `laporan_export.generate` |
| GET | `/laporan/presensi/export/semester` | `laporan_export.generate` |
| GET | `/laporan/jurnal` | `laporan_jurnal.view` |
| GET | `/laporan/jurnal/json` | `laporan_jurnal.view` |
| GET | `/laporan/jurnal/export` | `laporan_jurnal.export` |

# 16. BK Web

| Method | Route | Permission |
|---|---|---|
| GET | `/bk/kasus` | `bk_kasus.view` |
| GET | `/bk/kasus/json` | `bk_kasus.view` |
| GET | `/bk/kasus/top` | `bk_kasus.view` |
| GET | `/bk/kasus/top/json` | `bk_kasus.view` |
| POST | `/bk/kasus/create` | `bk_kasus.manage` |
| PUT | `/bk/kasus/update/(:segment)` | `bk_kasus.manage` |
| DELETE | `/bk/kasus/delete/(:segment)` | `bk_kasus.manage` |
| GET | `/bk/kasus/export` | `bk_kasus.manage` |
| GET | `/bk/pelanggaran` | `bk_pelanggaran_master.manage` |
| GET | `/bk/pelanggaran/json` | sama |
| POST | `/bk/pelanggaran/create` | sama |
| PUT | `/bk/pelanggaran/update/(:segment)` | sama |
| DELETE | `/bk/pelanggaran/delete/(:segment)` | sama |
| GET | `/bk/prestasi` | `prestasi.view` |
| GET | `/bk/prestasi/json` | `prestasi.view` |
| POST | `/bk/prestasi/create` | `prestasi.manage` |
| PUT | `/bk/prestasi/update/(:segment)` | `prestasi.manage` |
| DELETE | `/bk/prestasi/delete/(:segment)` | `prestasi.manage` |
| GET | `/bk/prestasi/export` | `prestasi.view OR prestasi.manage` |

# 17. Kartu Pelajar Web

| Method | Route | Permission |
|---|---|---|
| GET | `/kartu/daftar` | `kartu_pelajar.view` |
| GET | `/kartu/daftar/json` | `kartu_pelajar.view` |
| POST | `/kartu/generate` | `kartu_pelajar.manage` |
| GET | `/kartu/cetak/(:segment)` | `kartu_pelajar.manage OR kartu_pelajar.view` |
| GET | `/kartu/preview/(:segment)` | `kartu_pelajar.view` |
| GET | `/kartu/preview/(:segment)/json` | `kartu_pelajar.view` |
| GET | `/kartu/download/(:segment)` | `kartu_pelajar.view` |
| POST | `/kartu/reissue/(:segment)` | `kartu_pelajar.manage` |

# 18. Profile Web

| Method | Route | Permission |
|---|---|---|
| GET | `/profile/guru` | `profile_guru.view` |
| GET | `/profile/guru/json` | `profile_guru.view` |
| PUT | `/profile/guru/update` | `profile_guru.edit` |
| POST | `/profile/guru/upload-foto` | `profile_guru.edit` |
| GET | `/profile/siswa` | `profile_siswa.view` |
| GET | `/profile/siswa/json` | `profile_siswa.view` |

**Baseline blocker:** `ProfileGuru.php` dan `ProfileSiswa.php` tidak ditemukan.

# 19. Settings

| Method | Route | Permission |
|---|---|---|
| GET | `/settings/user` | `settings_user.manage` |
| GET | `/settings/user/json` | sama |
| POST | `/settings/user/create` | sama |
| PUT | `/settings/user/update/(:segment)` | sama |
| POST | `/settings/user/reset/(:segment)` | sama |
| DELETE | `/settings/user/delete/(:segment)` | sama |
| GET | `/settings/menu` | `settings_menu.manage` |
| GET | `/settings/menu/json` | sama |
| PUT | `/settings/menu/update/(:segment)` | sama |
| GET | `/settings/sistem` | `settings_sistem.manage` |
| GET | `/settings/sistem/json` | sama |
| PUT | `/settings/sistem/update` | sama |
| POST | `/settings/sistem/maintenance` | sama |
| POST | `/settings/sistem/upload-branding` | sama |
| POST | `/settings/sistem/upload-background-kta` | sama |

# 20. Backup & Log

| Method | Route | Permission |
|---|---|---|
| GET | `/backup` | `backup.manage` |
| POST | `/backup/create` | `backup.manage` |
| GET | `/backup/download/(:segment)` | `backup.manage` |
| DELETE | `/backup/delete/(:segment)` | `backup.manage` |
| GET | `/log/activity` | `log_activity.view` |
| GET | `/log/activity/json` | `log_activity.view` |
| GET | `/log/activity/export` | `log_activity.view` |

# 21. API Protected

Semua route berikut memakai group:

```text
auth:api
```

## Dashboard

```text
GET /api/dashboard
GET /api/dashboard/data
```

## Presensi Siswa

```text
GET  /api/presensi/siswa
GET  /api/presensi/siswa/input/(:segment)
POST /api/presensi/siswa/save
GET  /api/presensi/siswa/revisi/(:segment)
POST /api/presensi/siswa/revisi/save
GET  /api/presensi/siswa/rekap
GET  /api/presensi/siswa/ews
```

## Presensi Mengajar

```text
GET  /api/presensi/mengajar
GET  /api/presensi/mengajar/input/(:segment)
POST /api/presensi/mengajar/save
GET  /api/presensi/mengajar/laporan
```

## Laporan

```text
GET /api/laporan/presensi/matrix
GET /api/laporan/jurnal
```

## BK

```text
GET    /api/bk/kasus
GET    /api/bk/kasus/top
POST   /api/bk/kasus/create
PUT    /api/bk/kasus/update/(:segment)
DELETE /api/bk/kasus/delete/(:segment)
GET    /api/bk/prestasi
POST   /api/bk/prestasi/create
```

## Kartu

```text
GET /api/kartu/preview/(:segment)
GET /api/kartu/download/(:segment)
```

## Profile

```text
GET  /api/profile/guru
PUT  /api/profile/guru
POST /api/profile/guru/foto
GET  /api/profile/siswa
```

# 22. Route Gap Baseline

Route terdaftar tetapi target Controller tidak ditemukan:

```text
GET /api/version
→ Api::version
→ app/Controllers/Api.php tidak ada

/profile/guru...
→ ProfileGuru
→ app/Controllers/ProfileGuru.php tidak ada

/profile/siswa...
→ ProfileSiswa
→ app/Controllers/ProfileSiswa.php tidak ada
```

Ini adalah release blocker.

# 23. Maintenance Interaction

`MaintenanceFilter` adalah filter global.

Saat ON:

- Admin effective boleh;
- non-Admin 503;
- API 503 JSON;
- exact login/logout exception sesuai dokumen 08.

# 24. Route Final Rule

Dokumen ini harus selalu identik dengan `Routes.php`.

Perubahan route pada masa depan harus:

1. update full `Routes.php`;
2. update dokumen ini;
3. verify Controller method;
4. verify permission exists;
5. verify menu bila route navigasional;
6. verify API JSON bila prefix `/api`.
