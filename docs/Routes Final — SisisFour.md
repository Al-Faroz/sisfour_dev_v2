# Routes Final — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 12 September 2026
**Baseline Aplikasi:** `main` @ `39da4651acd29adcd575677d7a37c058bf32269d`
**Source of Truth Runtime:** `app/Config/Routes.php`

> Dokumen ini adalah inventaris kontrak route pada baseline. Bila ada perbedaan, `app/Config/Routes.php` adalah referensi runtime yang harus diaudit dan dokumen ini wajib disinkronkan.

## 1. Prinsip

- Route bisnis eksplisit.
- Web protected berada di group `auth`.
- API protected berada di group `auth:api`.
- `permission:...` adalah route gate; Service tetap memvalidasi target/scope.
- Wali Kelas bukan role.
- Route Profile Pegawai berbasis self identity dan tidak membutuhkan role `pegawai`.

## 2. Public / Authentication

| Method | Route | Target / Catatan |
|---|---|---|
| GET | `/` | Login Web |
| GET | `/auth/login` | Login Web |
| POST | `/auth/login` | Proses login Web |
| POST | `/auth/logout` | Logout Web, `auth` |
| POST | `/api/auth/login` | Login API |
| POST | `/api/auth/logout` | Logout API, `auth:api` |
| GET | `/api/auth/me` | Actor API, `auth:api` |
| POST | `/api/auth/refresh` | Refresh API |
| GET | `/api/version` | `Api::version` |
| GET | `/kartu/verify/(:segment)` | Public verify Kartu |
| GET | `/signage` | Digital Signage public |
| GET | `/signage/data` | JSON Signage public |

## 3. Dashboard & UI Remote

Protected `auth`:

```text
GET /dashboard                  dashboard.view
GET /dashboard/data             dashboard.view
GET /ui/search/siswa            auth + Service scope
```

## 4. Master Guru

```text
GET    /master/guru
GET    /master/guru/json
GET    /master/guru/template
POST   /master/guru/create
POST   /master/guru/import
GET    /master/guru/export
PUT    /master/guru/update/{id}
DELETE /master/guru/delete/{id}
POST   /master/guru/upload-foto/{id}
GET    /master/guru/recycle
GET    /master/guru/recycle/json
POST   /master/guru/restore/{id}
DELETE /master/guru/force-delete/{id}
GET    /master/guru/personalia/{id}
POST   /master/guru/personalia/{id}/save/{category}
DELETE /master/guru/personalia/{id}/delete/{category}/{record_id}
GET    /master/guru/portofolio/{id}
```

Permission family:

```text
master_guru.view
master_guru.manage
```

## 5. Master Pegawai

```text
GET    /master/pegawai
GET    /master/pegawai/json
GET    /master/pegawai/template
POST   /master/pegawai/create
POST   /master/pegawai/import
GET    /master/pegawai/export
PUT    /master/pegawai/update/{id}
DELETE /master/pegawai/delete/{id}
POST   /master/pegawai/upload-foto/{id}
GET    /master/pegawai/recycle
GET    /master/pegawai/recycle/json
POST   /master/pegawai/restore/{id}
DELETE /master/pegawai/force-delete/{id}
GET    /master/pegawai/personalia/{id}
POST   /master/pegawai/personalia/{id}/save/{category}
DELETE /master/pegawai/personalia/{id}/delete/{category}/{record_id}
GET    /master/pegawai/portofolio/{id}
```

Permission family:

```text
master_pegawai.view
master_pegawai.manage
```

## 6. Master Siswa

```text
GET    /master/siswa
GET    /master/siswa/json
GET    /master/siswa/template
POST   /master/siswa/create
POST   /master/siswa/import
GET    /master/siswa/export
PUT    /master/siswa/update/{id}
DELETE /master/siswa/delete/{id}
GET    /master/siswa/recycle
GET    /master/siswa/recycle/json
POST   /master/siswa/restore/{id}
DELETE /master/siswa/force-delete/{id}
POST   /master/siswa/upload-foto/{id}
```

Permission family:

```text
master_siswa.view
master_siswa.edit_biodata
master_siswa.manage
master_siswa.import_export
```

## 7. Master Kelas / Tahun / Mapel

### Kelas

```text
GET    /master/kelas
GET    /master/kelas/json
POST   /master/kelas/create
PUT    /master/kelas/update/{id}
DELETE /master/kelas/delete/{id}
GET    /master/kelas/recycle
GET    /master/kelas/recycle/json
POST   /master/kelas/restore/{id}
DELETE /master/kelas/force-delete/{id}
```

Permission: `master_kelas.manage`.

### Tahun Ajaran

```text
GET    /master/tahun
GET    /master/tahun/json
POST   /master/tahun/create
PUT    /master/tahun/update/{id}
POST   /master/tahun/aktifkan/{id}
DELETE /master/tahun/delete/{id}
GET    /master/tahun/recycle
GET    /master/tahun/recycle/json
POST   /master/tahun/restore/{id}
DELETE /master/tahun/force-delete/{id}
```

Permission: `master_tahun_ajaran.manage`.

### Mata Pelajaran

```text
GET    /master/mapel
GET    /master/mapel/json
POST   /master/mapel/create
PUT    /master/mapel/update/{id}
DELETE /master/mapel/delete/{id}
```

Permission: `master_mapel.manage`.

## 8. Mapping Wali & Jadwal

### Mapping Wali

```text
GET    /master/wali-kelas
GET    /master/wali-kelas/json
POST   /master/wali-kelas/assign
DELETE /master/wali-kelas/delete/{id}
GET    /master/wali-kelas/options
GET    /master/wali-kelas/recycle
GET    /master/wali-kelas/recycle/json
POST   /master/wali-kelas/restore/{id}
DELETE /master/wali-kelas/force-delete/{id}
```

Permission:

```text
mapping_wali.view
mapping_wali.view_all
mapping_wali.manage
```

### Jadwal Guru

```text
GET    /master/jadwal
GET    /master/jadwal/json
GET    /master/jadwal/options
GET    /master/jadwal/template
POST   /master/jadwal/import
GET    /master/jadwal/export
DELETE /master/jadwal/delete/{id}
```

Permission:

```text
jadwal_guru.view
jadwal_guru.view_all
jadwal_guru.manage
```

## 9. Manajemen Siswa

Permission: `master_siswa.manage`.

```text
GET  /manajemen-siswa/kelas
GET  /manajemen-siswa/kelas/json
POST /manajemen-siswa/kelas/set/{id}
GET  /manajemen-siswa/kenaikan
GET  /manajemen-siswa/process-data/{id}
POST /manajemen-siswa/kenaikan/proses/{id}
GET  /manajemen-siswa/mutasi
GET  /manajemen-siswa/mutasi/json
POST /manajemen-siswa/mutasi/proses/{id}
GET  /manajemen-siswa/kelulusan
POST /manajemen-siswa/kelulusan/proses/{id}
```

## 10. Presensi Web

### Siswa

```text
GET  /presensi/siswa                         presensi_siswa.input
GET  /presensi/siswa/input/{kelas}           presensi_siswa.input
GET  /presensi/siswa/input/{kelas}/json      presensi_siswa.input
POST /presensi/siswa/save                    presensi_siswa.input
GET  /presensi/siswa/revisi/{kelas}          presensi_siswa.revisi
POST /presensi/siswa/revisi/save             presensi_siswa.revisi
GET  /presensi/siswa/rekap                   presensi_siswa.view
GET  /presensi/siswa/rekap/json              presensi_siswa.view
GET  /presensi/siswa/ews                     ews_radar.view
GET  /presensi/siswa/ews/json                ews_radar.view
```

### Mengajar/Jurnal

```text
GET  /presensi/mengajar                      presensi_mengajar.input
GET  /presensi/mengajar/input/{jadwal}       presensi_mengajar.input
GET  /presensi/mengajar/input/{jadwal}/json  presensi_mengajar.input
POST /presensi/mengajar/save                 presensi_mengajar.input
GET  /presensi/mengajar/laporan              presensi_mengajar.view
GET  /presensi/mengajar/laporan/json         presensi_mengajar.view
```

## 11. Laporan Web

```text
GET /laporan/presensi/matrix           laporan_matrix.view
GET /laporan/presensi/matrix/json      laporan_matrix.view
GET /laporan/presensi/export           laporan_export.generate
GET /laporan/presensi/export/bulan     laporan_export.generate
GET /laporan/presensi/export/semester  laporan_export.generate
GET /laporan/jurnal                    laporan_jurnal.view
GET /laporan/jurnal/json               laporan_jurnal.view
GET /laporan/jurnal/export             laporan_jurnal.export
```

## 12. BK / Prestasi Web

```text
GET    /bk/kasus                              bk_kasus.view
GET    /bk/kasus/json                         bk_kasus.view
GET    /bk/kasus/detail/{id}                  bk_kasus.view
GET    /bk/kasus/top                          bk_kasus.view
GET    /bk/kasus/top/json                     bk_kasus.view
POST   /bk/kasus/create                       bk_kasus.manage
PUT    /bk/kasus/update/{id}                  bk_kasus.manage
DELETE /bk/kasus/delete/{id}                  bk_kasus.manage
POST   /bk/kasus/{id}/tindak-lanjut           bk_kasus.manage
PUT    /bk/kasus/tindak-lanjut/{id}           bk_kasus.manage
GET    /bk/kasus/export                       bk_kasus.manage

GET    /bk/pelanggaran                        bk_pelanggaran_master.manage
GET    /bk/pelanggaran/json                   bk_pelanggaran_master.manage
POST   /bk/pelanggaran/create                 bk_pelanggaran_master.manage
PUT    /bk/pelanggaran/update/{id}            bk_pelanggaran_master.manage
DELETE /bk/pelanggaran/delete/{id}            bk_pelanggaran_master.manage

GET    /bk/prestasi                           prestasi.view
GET    /bk/prestasi/json                      prestasi.view
POST   /bk/prestasi/create                    prestasi.manage
PUT    /bk/prestasi/update/{id}               prestasi.manage
DELETE /bk/prestasi/delete/{id}               prestasi.manage
GET    /bk/prestasi/export                    prestasi.view OR prestasi.manage
```

## 13. Kartu Pelajar Web

```text
GET  /kartu/daftar                 kartu_pelajar.view
GET  /kartu/daftar/json            kartu_pelajar.view
POST /kartu/generate               kartu_pelajar.manage
POST /kartu/generate-bulk          kartu_pelajar.manage
POST /kartu/cetak-massal           kartu_pelajar.manage
GET  /kartu/cetak/{id}             kartu_pelajar.manage OR kartu_pelajar.view
GET  /kartu/preview/{id}           kartu_pelajar.view
GET  /kartu/preview/{id}/json      kartu_pelajar.view
GET  /kartu/download/{id}          kartu_pelajar.view
POST /kartu/reissue/{id}           kartu_pelajar.manage
```

## 14. Profile & Personalia Web

### Guru

```text
GET    /profile/guru
GET    /profile/guru/json
PUT    /profile/guru/update
POST   /profile/guru/upload-foto
GET    /profile/guru/personalia
POST   /profile/guru/personalia/save/{category}
DELETE /profile/guru/personalia/delete/{category}/{record_id}
GET    /profile/guru/portofolio
```

Permission family: `profile_guru.view`, `profile_guru.edit`.

### Pegawai

```text
GET    /profile/pegawai
GET    /profile/pegawai/json
PUT    /profile/pegawai/update
POST   /profile/pegawai/upload-foto
GET    /profile/pegawai/personalia
POST   /profile/pegawai/personalia/save/{category}
DELETE /profile/pegawai/personalia/delete/{category}/{record_id}
GET    /profile/pegawai/portofolio
```

Gate = authenticated user; target authorization = self identity `id_pegawai` di Service.

### Siswa

```text
GET /profile/siswa
GET /profile/siswa/json
```

Permission: `profile_siswa.view`.

### Secure Personalia File

```text
GET /personalia/file/{category}/{record_id}/{field}
```

Auth group + Service authorization.

## 15. Settings / Backup / Log

### Settings User

```text
GET    /settings/user
GET    /settings/user/json
POST   /settings/user/create
PUT    /settings/user/update/{id}
POST   /settings/user/reset/{id}
DELETE /settings/user/delete/{id}
```

Permission: `settings_user.manage`.

### Settings Menu

```text
GET /settings/menu
GET /settings/menu/json
PUT /settings/menu/update/{id}
```

Permission: `settings_menu.manage`.

### Settings Sistem

```text
GET  /settings/sistem
GET  /settings/sistem/json
PUT  /settings/sistem/update
POST /settings/sistem/maintenance
POST /settings/sistem/upload-branding
POST /settings/sistem/upload-background-kta
```

Permission: `settings_sistem.manage`.

### Backup

```text
GET    /backup
POST   /backup/create
GET    /backup/download/{filename}
DELETE /backup/delete/{filename}
```

Permission: `backup.manage`.

### Log Activity

```text
GET /log/activity
GET /log/activity/json
GET /log/activity/export
```

Permission: `log_activity.view`.

## 16. Protected API Routes

Seluruh route berikut berada di group `/api` dengan filter `auth:api`.

### Dashboard

```text
GET /api/dashboard
GET /api/dashboard/data
```

### Presensi Siswa

```text
GET  /api/presensi/siswa
GET  /api/presensi/siswa/input/{kelas}
POST /api/presensi/siswa/save
GET  /api/presensi/siswa/revisi/{kelas}
POST /api/presensi/siswa/revisi/save
GET  /api/presensi/siswa/rekap
GET  /api/presensi/siswa/ews
```

### Presensi Mengajar

```text
GET  /api/presensi/mengajar
GET  /api/presensi/mengajar/input/{jadwal}
POST /api/presensi/mengajar/save
GET  /api/presensi/mengajar/laporan
```

### Laporan

```text
GET /api/laporan/presensi/matrix
GET /api/laporan/jurnal
```

### BK

```text
GET    /api/bk/kasus
GET    /api/bk/kasus/detail/{id}
GET    /api/bk/kasus/top
POST   /api/bk/kasus/create
PUT    /api/bk/kasus/update/{id}
DELETE /api/bk/kasus/delete/{id}
POST   /api/bk/kasus/{id}/tindak-lanjut
PUT    /api/bk/kasus/tindak-lanjut/{id}
GET    /api/bk/prestasi
POST   /api/bk/prestasi/create
```

### Kartu

```text
GET /api/kartu/preview/{id}
GET /api/kartu/download/{id}
```

### Profile

```text
GET  /api/profile/guru
PUT  /api/profile/guru
POST /api/profile/guru/foto
GET  /api/profile/pegawai
PUT  /api/profile/pegawai
POST /api/profile/pegawai/foto
GET  /api/profile/siswa
```

Setiap API endpoint bisnis tetap memakai permission/scope yang sama dengan kontrak Service terkait.

## 17. Route Validation

Checkpoint:

```powershell
php spark routes
```

Rilis tidak boleh memiliki:

- route menuju Controller/method yang hilang;
- mutation tanpa authorization yang semestinya;
- Web route yang tanpa sengaja keluar dari group `auth`;
- protected API yang keluar dari `auth:api`;
- route public yang membocorkan data lebih dari kontrak.
