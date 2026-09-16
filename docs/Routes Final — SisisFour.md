# Routes Final — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 16 September 2026  
**Application baseline:** `main` @ `06e4e559c045763096058fc889342da78d973314` + G3.3.1 pending merge

> Runtime source of truth adalah seluruh route file yang terdaftar pada `Config\Routing::$routeFiles`, bukan hanya `Routes.php`.

## 1. Route Source Runtime

G3.3.1 menggunakan:

```text
app/Config/Routing.php
  -> app/Config/Routes.php
  -> app/Config/RoutesBKFoundation.php
```

`RoutesBKFoundation.php` menambah Web route Konseling G3.3.1. `autoRoute` tetap `false`.

Prinsip:

- Web protected berada di group `auth`;
- API protected berada di group `auth:api`;
- `permission:*` adalah route gate;
- Service tetap memvalidasi effective role, target, scope, dan business rule;
- Wali Kelas bukan role;
- Profile Pegawai berbasis self identity;
- menu tidak menjadi authorization boundary.

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
| GET | `/api/version` | Version public |
| GET | `/kartu/verify/{kode}` | Public verify Kartu |
| GET | `/signage` | Signage public/read-only |
| GET | `/signage/data` | JSON Signage public |

## 3. Dashboard / Remote UI

Protected Web:

```text
GET /dashboard                  dashboard.view
GET /dashboard/data             dashboard.view
GET /ui/search/siswa            auth; scope/target diputuskan Service
```

## 4. Master Data

Route families existing tetap:

```text
/master/guru/*
/master/pegawai/*
/master/siswa/*
/master/kelas/*
/master/tahun/*
/master/mapel/*
/master/wali-kelas/*
/master/jadwal/*
```

Permission family:

```text
master_guru.view/manage
master_pegawai.view/manage
master_siswa.view/edit_biodata/manage/import_export
master_kelas.manage
master_tahun_ajaran.manage
master_mapel.manage
mapping_wali.view/view_all/manage
jadwal_guru.view/view_all/manage
```

Personalia/portofolio Master tetap berada di family Guru/Pegawai dan Service memvalidasi target.

## 5. Manajemen Siswa

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

Permission: `master_siswa.manage`.

## 6. Presensi Web

### Presensi Siswa

```text
GET  /presensi/siswa
GET  /presensi/siswa/input/{kelas}
GET  /presensi/siswa/input/{kelas}/json
POST /presensi/siswa/save
GET  /presensi/siswa/revisi/{kelas}
POST /presensi/siswa/revisi/save
GET  /presensi/siswa/rekap
GET  /presensi/siswa/rekap/json
GET  /presensi/siswa/ews
GET  /presensi/siswa/ews/json
```

Permission sesuai `presensi_siswa.input/revisi/view` dan `ews_radar.view`.

### Presensi Mengajar / Jurnal

```text
GET  /presensi/mengajar
GET  /presensi/mengajar/input/{jadwal}
GET  /presensi/mengajar/input/{jadwal}/json
POST /presensi/mengajar/save
GET  /presensi/mengajar/laporan
GET  /presensi/mengajar/laporan/json
```

G3.2 child exception siswa tidak menambah route baru; detail laporan menggunakan endpoint laporan existing sesuai controller contract.

## 7. Laporan Web

```text
GET /laporan/presensi/matrix
GET /laporan/presensi/matrix/json
GET /laporan/presensi/export
GET /laporan/presensi/export/bulan
GET /laporan/presensi/export/semester
GET /laporan/jurnal
GET /laporan/jurnal/json
GET /laporan/jurnal/export
```

Permission:

```text
laporan_matrix.view
laporan_export.generate
laporan_jurnal.view
laporan_jurnal.export
```

## 8. BK — Catatan Pelanggaran / Master / Prestasi

Physical legacy path `bk/kasus` dipertahankan; nama experience canonical adalah **Catatan Pelanggaran**.

```text
GET    /bk/kasus                              bk_kasus.view
GET    /bk/kasus/json                         bk_kasus.view
GET    /bk/kasus/detail/{id}                  bk_kasus.view
POST   /bk/kasus/create                       bk_kasus.manage
PUT    /bk/kasus/update/{id}                  bk_kasus.manage
DELETE /bk/kasus/delete/{id}                  bk_kasus.manage
POST   /bk/kasus/{id}/tindak-lanjut           bk_kasus.manage
PUT    /bk/kasus/tindak-lanjut/{id}           bk_kasus.manage
GET    /bk/kasus/export                       bk_kasus.manage
```

Legacy route yang tetap terdaftar untuk bookmark compatibility:

```text
GET /bk/kasus/top
GET /bk/kasus/top/json
```

Namun **fitur Top Poin retired** pada G3.3.1:

```text
HTML -> redirect ke /bk/kasus + info retired
JSON -> HTTP 410 / FEATURE_RETIRED
```

Master Pelanggaran:

```text
GET    /bk/pelanggaran
GET    /bk/pelanggaran/json
POST   /bk/pelanggaran/create
PUT    /bk/pelanggaran/update/{id}
DELETE /bk/pelanggaran/delete/{id}
```

Permission: `bk_pelanggaran_master.manage`.

Prestasi:

```text
GET    /bk/prestasi                           prestasi.view
GET    /bk/prestasi/json                      prestasi.view
POST   /bk/prestasi/create                    prestasi.manage
PUT    /bk/prestasi/update/{id}               prestasi.manage
DELETE /bk/prestasi/delete/{id}               prestasi.manage
GET    /bk/prestasi/export                    prestasi.view OR prestasi.manage
```

## 9. Konseling BK — G3.3.1

Route source: `app/Config/RoutesBKFoundation.php`. Seluruh route berada di Web `auth` group.

```text
GET  /bk/konseling                            bk_konseling.view
GET  /bk/konseling/json                       bk_konseling.view
GET  /bk/konseling/detail/{id}                bk_konseling.view
GET  /bk/konseling/export                     bk_konseling.export
GET  /bk/konseling/siswa-kelas/{kelas}        bk_konseling.manage
POST /bk/konseling/create                     bk_konseling.manage
PUT  /bk/konseling/update/{id}                bk_konseling.manage
```

Pengaturan Form Konseling:

```text
GET  /bk/konseling/settings                   bk_konseling.settings
POST /bk/konseling/settings                   bk_konseling.settings
POST /bk/konseling/settings/reset             bk_konseling.settings
```

Route filter bukan satu-satunya boundary. Service Konseling mengharuskan effective role Admin/Operator/BK; Service Settings mengharuskan Admin/BK.

G3.3.1 **tidak menambah API Konseling**.

## 10. Kartu Pelajar Web

```text
GET  /kartu/daftar
GET  /kartu/daftar/json
POST /kartu/generate
POST /kartu/generate-bulk
POST /kartu/cetak-massal
GET  /kartu/cetak/{id}
GET  /kartu/preview/{id}
GET  /kartu/preview/{id}/json
GET  /kartu/download/{id}
POST /kartu/reissue/{id}
```

Permission family: `kartu_pelajar.view/manage`.

## 11. Profile / Personalia Web

Guru:

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

Pegawai self-identity:

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

Siswa:

```text
GET /profile/siswa
GET /profile/siswa/json
```

Secure personalia file:

```text
GET /personalia/file/{category}/{record_id}/{field}
```

Service memvalidasi owner/permission sebelum file dikirim.

## 12. Settings / Backup / Log

Settings User:

```text
GET    /settings/user
GET    /settings/user/json
POST   /settings/user/create
PUT    /settings/user/update/{id}
POST   /settings/user/reset/{id}
DELETE /settings/user/delete/{id}
```

Settings Menu:

```text
GET /settings/menu
GET /settings/menu/json
PUT /settings/menu/update/{id}
```

Settings Sistem:

```text
GET  /settings/sistem
GET  /settings/sistem/json
PUT  /settings/sistem/update
POST /settings/sistem/maintenance
POST /settings/sistem/upload-branding
POST /settings/sistem/upload-background-kta
```

Catatan: Pengaturan Form Konseling **bukan** route `/settings/sistem`; ia memiliki route/permission khusus pada `/bk/konseling/settings`.

Backup:

```text
GET    /backup
POST   /backup/create
GET    /backup/download/{filename}
DELETE /backup/delete/{filename}
```

Log:

```text
GET /log/activity
GET /log/activity/json
GET /log/activity/export
```

## 13. Protected API Routes

Semua berada pada `/api` group dengan `auth:api`.

Current families:

```text
/api/dashboard*
/api/presensi/siswa*
/api/presensi/mengajar*
/api/laporan/presensi/matrix
/api/laporan/jurnal
/api/bk/kasus*
/api/bk/prestasi*
/api/kartu/preview/*
/api/kartu/download/*
/api/profile/guru*
/api/profile/pegawai*
/api/profile/siswa
```

Legacy `/api/bk/kasus/top` mengikuti controller retired Top Poin; endpoint API Konseling belum ada pada G3.3.1.

Setiap endpoint bisnis API tetap memakai permission/scope Service yang sama dengan kontrak domain.

## 14. Route Validation

Checkpoint:

```powershell
php spark routes
```

Rilis tidak boleh memiliki:

- route menuju Controller/method hilang;
- mutation tanpa authorization;
- Web route protected keluar dari `auth`;
- API protected keluar dari `auth:api`;
- route public membocorkan data;
- route tambahan yang dibuat tetapi tidak didaftarkan pada `Routing::$routeFiles`.

G3.3.1 route + browser smoke telah PASS pada localhost dan hosting. PR #9 belum merge sampai approval eksplisit user.