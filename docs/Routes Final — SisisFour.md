# Routes Final — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 18 September 2026
**Application baseline:** `main` @ `90acc7f94fee391a5a7fbad2395e3f16571fe921` + G3.6B feature branch

> Runtime source of truth adalah seluruh route file yang terdaftar pada `Config\Routing::$routeFiles`, bukan hanya `Routes.php`.

## 1. Route Source Runtime

Runtime route source:

```text
app/Config/Routing.php
  -> app/Config/Routes.php
  -> app/Config/RoutesBKFoundation.php
```

`autoRoute` tetap `false`.

Prinsip:

- Web protected berada di group `auth`;
- API protected berada di group `auth:api`;
- `permission:*` adalah route gate;
- Service tetap memvalidasi effective role, target, scope, period, dan business rule;
- Wali Kelas bukan role;
- menu bukan authorization boundary.

## 2. Public / Authentication

```text
GET  /
GET  /auth/login
POST /auth/login
POST /auth/logout
POST /api/auth/login
POST /api/auth/logout
GET  /api/auth/me
POST /api/auth/refresh
GET  /api/version
GET  /kartu/verify/{kode}
GET  /signage
GET  /signage/data
```

## 3. Dashboard / Remote UI

```text
GET /dashboard
GET /dashboard/data
GET /ui/search/siswa
```

Permission/scope final tetap diputuskan Route/Filter + Service.

## 4. Master / Student / Presensi / Laporan

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
/manajemen-siswa/*
/presensi/siswa/*
/presensi/mengajar/*
/laporan/presensi/*
/laporan/jurnal*
```

Period filter pada route GET memakai query `id_tahun` bila surface memang periodik; Service tetap memvalidasi period yang dipilih.

## 5. BK — Catatan Pelanggaran

Physical legacy path `bk/kasus` dipertahankan; nama experience canonical adalah **Catatan Pelanggaran Siswa**.

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

Listing/export periodik menerima query `id_tahun`; default diputuskan Service sebagai Tahun Ajaran aktif.

Legacy Top Poin route tetap hanya compatibility dan feature retired.

## 6. Master Pelanggaran

```text
GET    /bk/pelanggaran
GET    /bk/pelanggaran/json
POST   /bk/pelanggaran/create
PUT    /bk/pelanggaran/update/{id}
DELETE /bk/pelanggaran/delete/{id}
```

Permission: `bk_pelanggaran_master.manage`.

Master Pelanggaran non-periodik sehingga tidak memiliki `id_tahun` filter.

## 7. Prestasi

```text
GET    /bk/prestasi                           prestasi.view
GET    /bk/prestasi/json                      prestasi.view
POST   /bk/prestasi/create                    prestasi.manage
PUT    /bk/prestasi/update/{id}               prestasi.manage
DELETE /bk/prestasi/delete/{id}               prestasi.manage
GET    /bk/prestasi/export                    prestasi.view/manage sesuai Service
```

Listing/export periodik menerima `id_tahun`; create baru selalu snapshot Tahun Ajaran aktif dari Service.

## 8. Konseling BK — G3.3.1 Rework

Route source: `app/Config/RoutesBKFoundation.php`. Seluruh route berada di Web `auth` group.

Parent/list:

```text
GET  /bk/konseling                            bk_konseling.view
GET  /bk/konseling/json                       bk_konseling.view
GET  /bk/konseling/detail/{id}                bk_konseling.view
GET  /bk/konseling/export                     bk_konseling.export
GET  /bk/konseling/siswa-kelas/{kelas}        bk_konseling.manage
POST /bk/konseling/create                     bk_konseling.manage
PUT  /bk/konseling/update/{id}                bk_konseling.manage
```

Tindak Lanjut Konseling 1:N:

```text
POST /bk/konseling/{id_konseling}/tindak-lanjut   bk_konseling.manage
PUT  /bk/konseling/tindak-lanjut/{id}              bk_konseling.manage
```

**Tidak ada route DELETE** untuk parent Konseling maupun Tindak Lanjut Konseling.

Listing/export menerima query `id_tahun`. Create parent tetap selalu memakai Tahun Ajaran aktif dan kelas/siswa aktif dari server.

Pengaturan Form Konseling:

```text
GET  /bk/konseling/settings                   bk_konseling.settings
POST /bk/konseling/settings                   bk_konseling.settings
POST /bk/konseling/settings/reset             bk_konseling.settings
```

Service Konseling mengharuskan effective role Admin/Operator/BK; Service Settings mengharuskan Admin/BK.

G3.3.1 tidak menambah API Konseling.

## 9. UKS / Kesehatan — G3.6A

Web routes berada pada authenticated `/uks` group.

Data CKG:

```text
GET    /uks/ckg                      uks_ckg.view
GET    /uks/ckg/json                 uks_ckg.view
POST   /uks/ckg/create               uks_ckg.manage
PUT    /uks/ckg/update/{id}          uks_ckg.manage
DELETE /uks/ckg/delete/{id}          uks_ckg.manage
GET    /uks/ckg/template             uks_ckg.import
POST   /uks/ckg/import               uks_ckg.import
GET    /uks/ckg/export               uks_ckg.export
```

Catatan Harian UKS:

```text
GET    /uks/harian                   uks_harian.view
GET    /uks/harian/json              uks_harian.view
POST   /uks/harian/create            uks_harian.manage
PUT    /uks/harian/update/{id}       uks_harian.manage
DELETE /uks/harian/delete/{id}       uks_harian.manage
GET    /uks/harian/export            uks_harian.export
```

Master UKS:

```text
GET    /uks/master                           uks_master.manage
GET    /uks/master/json                      uks_master.manage
POST   /uks/master/{type}/create             uks_master.manage
PUT    /uks/master/{type}/update/{id}        uks_master.manage
DELETE /uks/master/{type}/delete/{id}        uks_master.manage
```

DELETE pada Master UKS adalah route mutation untuk **deactivate reference**, bukan hard delete business record. CKG dan kunjungan menggunakan soft delete.

Period/scope final tetap diputuskan `UksService` + `UksScopeService`, termasuk historical Wali pada Tahun Ajaran terpilih.

## 10. Kartu Pelajar

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

Kartu operasional adalah current-state workflow; tidak diberi filter Tahun Ajaran palsu.

## 11. Profile / Personalia

Guru/Pegawai/Siswa route family existing tetap. Secure personalia file tetap melalui route yang memvalidasi owner/permission sebelum file dikirim.

## 12. Settings / Backup / Log

Route family existing tetap:

```text
/settings/user/*
/settings/menu/*
/settings/sistem/*
/backup/*
/log/activity*
```

Pengaturan Form Konseling bukan `/settings/sistem`; ia memiliki route dan permission khusus `/bk/konseling/settings`.

## 13. Protected API Routes

Current API families tetap berada pada `/api` + `auth:api`.

```text
/api/dashboard*
/api/presensi/siswa*
/api/presensi/mengajar*
/api/laporan/presensi/matrix
/api/laporan/jurnal
/api/bk/kasus*
/api/bk/prestasi*
/api/uks/ckg*
/api/uks/harian*
/api/kartu/*
/api/profile/*
```

Endpoint API Konseling tetap tidak ada. G3.6A menambah protected API CRUD core untuk CKG dan Catatan Harian UKS; import/export/template tetap Web routes.

## 14. Route Validation

Checkpoint wajib pada head final:

```powershell
php spark routes
```

Rilis tidak boleh memiliki:

- route menuju Controller/method hilang;
- mutation tanpa authorization;
- Web route protected keluar dari `auth`;
- API protected keluar dari `auth:api`;
- route public membocorkan data;
- route DELETE Konseling/follow-up yang bertentangan dengan business contract;
- route tambahan yang tidak didaftarkan pada `Routing::$routeFiles`.

G3.6A route source sudah diimplementasikan pada feature branch, tetapi `php spark routes` dan runtime route UAT masih **PENDING** sampai user menjalankan static/local gate pada exact head.

## 10. PTSP — G3.6B

Public web:

```text
GET  /ptsp
GET  /ptsp/form/layanan
GET  /ptsp/form/pengaduan
GET  /ptsp/form/polling

POST /ptsp/layanan
POST /ptsp/polling
POST /ptsp/pengaduan
```

`/ptsp` adalah landing pola kios; GET form routes dipisah agar tidak berbenturan dengan authenticated internal routes.

Public aggregate API:

```text
GET /api/public/ptsp/statistik/layanan
GET /api/public/ptsp/statistik/polling
GET /api/public/ptsp/statistik/pengaduan
OPTIONS /api/public/ptsp/statistik/{type}
```

Authenticated internal:

```text
GET    /ptsp/layanan
GET    /ptsp/layanan/json
POST   /ptsp/layanan/create
PUT    /ptsp/layanan/status/{id}
DELETE /ptsp/layanan/delete/{id}
GET    /ptsp/layanan/export

GET    /ptsp/polling
GET    /ptsp/polling/json
DELETE /ptsp/polling/delete/{id}
GET    /ptsp/polling/export

GET    /ptsp/pengaduan
GET    /ptsp/pengaduan/json
PUT    /ptsp/pengaduan/status/{id}
DELETE /ptsp/pengaduan/delete/{id}
GET    /ptsp/pengaduan/lampiran/{id}
GET    /ptsp/pengaduan/export
```

Public POST memakai CSRF global web. Aggregate API memakai CORS filter dan tidak memakai auth/raw-record endpoint.

## G3.6C Routes

Public unchanged:

```text
GET /signage
GET /signage/data
```

Authenticated Statistik:

```text
GET /statistik             -> statistik.view
GET /statistik/data        -> statistik.view
GET /statistik/export/pdf  -> statistik.export_pdf
```
