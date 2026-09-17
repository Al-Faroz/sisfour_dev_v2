# Routes Final — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 17 September 2026
**Application baseline:** `main` @ `06e4e559c045763096058fc889342da78d973314` + G3.3.1 rework branch

> Runtime source of truth adalah seluruh route file yang terdaftar pada `Config\Routing::$routeFiles`, bukan hanya `Routes.php`.

## 1. Route Source Runtime

G3.3.1 menggunakan:

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

## 9. Kartu Pelajar

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

## 10. Profile / Personalia

Guru/Pegawai/Siswa route family existing tetap. Secure personalia file tetap melalui route yang memvalidasi owner/permission sebelum file dikirim.

## 11. Settings / Backup / Log

Route family existing tetap:

```text
/settings/user/*
/settings/menu/*
/settings/sistem/*
/backup/*
/log/activity*
```

Pengaturan Form Konseling bukan `/settings/sistem`; ia memiliki route dan permission khusus `/bk/konseling/settings`.

## 12. Protected API Routes

Current API families tetap berada pada `/api` + `auth:api`.

```text
/api/dashboard*
/api/presensi/siswa*
/api/presensi/mengajar*
/api/laporan/presensi/matrix
/api/laporan/jurnal
/api/bk/kasus*
/api/bk/prestasi*
/api/kartu/*
/api/profile/*
```

Endpoint API Konseling belum ada pada G3.3.1.

## 13. Route Validation

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

Baseline route smoke lama PASS, tetapi route follow-up rework 17 September masih memerlukan static + runtime evidence sebelum PR #9 Ready/Merge.