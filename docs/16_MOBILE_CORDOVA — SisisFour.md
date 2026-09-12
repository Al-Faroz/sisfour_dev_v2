# Mobile Cordova — SisisFour

**Status:** Canonical Contract / Client Belum Dibangun
**Tanggal Acuan:** 12 September 2026
**Baseline Server:** `main` @ `39da4651acd29adcd575677d7a37c058bf32269d`
**API Version:** `v1`

> Server API core tersedia pada baseline. Project client Cordova belum menjadi bagian repository ini.

## 1. Target

Client Android direncanakan menggunakan Apache Cordova/WebView dan bersifat **online-only**.

```text
HTML
CSS
Vanilla JavaScript
Fetch API
Cordova WebView
```

Authorization/business rule tetap server-side.

## 2. Role Target Mobile

Prioritas:

```text
Pimpinan
BK
Guru/Wali
Siswa
```

Admin/Operator tetap lebih cocok menggunakan Web untuk pekerjaan administrasi berat, walaupun API permission tetap menentukan akses aktual.

## 3. API Base

Production:

```text
https://sisfour.mtsn4jombang.sch.id/api/
```

HTTPS wajib.

## 4. Authentication

Public:

```text
POST /api/auth/login
POST /api/auth/refresh
GET  /api/version
```

Protected:

```text
GET  /api/auth/me
POST /api/auth/logout
```

Server memakai JWT access token + opaque refresh token pada `api_tokens`.

Client wajib:

1. menyimpan token pada secure storage yang sesuai Android;
2. mengirim `Authorization: Bearer ...`;
3. refresh saat access token expired;
4. logout bila refresh gagal/revoked;
5. tidak menyimpan password setelah login;
6. tidak menulis token ke console/log.

## 5. Server Version Endpoint

`GET /api/version` runtime-ready melalui `Api::version`.

Baseline response identity:

```text
product        SisisFour
server_version 0.5
api_version    v1
timezone       Asia/Jakarta
```

Client dapat menggunakan endpoint ini untuk compatibility/update notice. Tidak ada silent APK update dari server.

## 6. RequestContext Server

Authenticated API actor disimpan request-scoped dengan canonical keys:

```text
api_user
api_access_token
api_token_row
api_claims
```

Client tidak pernah mengirim atau menentukan effective role/scope sendiri.

## 7. API Bisnis Tersedia

Protected group `auth:api` meliputi kategori:

```text
Dashboard
Presensi Siswa
Presensi Mengajar/Jurnal
Laporan Matrix/Jurnal
BK Kasus + Tindak Lanjut
Prestasi
Kartu preview/download
Profile Guru
Profile Pegawai
Profile Siswa
```

Endpoint aktual mengikuti `Routes Final — SisisFour.md` dan `app/Config/Routes.php`.

## 8. Profile API

Runtime-ready:

```text
GET  /api/profile/guru
PUT  /api/profile/guru
POST /api/profile/guru/foto

GET  /api/profile/pegawai
PUT  /api/profile/pegawai
POST /api/profile/pegawai/foto

GET  /api/profile/siswa
```

Personalia/Portofolio self pada baseline masih terutama kontrak Web; jangan mengasumsikan route API Personalia sebelum ditambahkan eksplisit.

## 9. JSON dan HTTP

Client harus memeriksa **HTTP status dan body**, bukan hanya field pesan.

Expected umum:

```text
200 success
401 unauthenticated/token invalid
403 forbidden/scope
422 validation/business error
503 maintenance
```

Endpoint Web/API dual-surface harus menghasilkan JSON untuk path `/api`.

## 10. Maintenance

Saat maintenance ON:

- non-Admin API menerima 503 JSON;
- client menampilkan pesan maintenance;
- client tidak melakukan retry tanpa batas;
- effective Admin dapat tetap melakukan recovery sesuai server policy.

## 11. Geolocation

Jika Presensi memerlukan geofence:

1. client meminta permission lokasi;
2. ambil latitude/longitude aktual;
3. kirim koordinat ke server;
4. server menghitung validitas radius.

Client tidak boleh menentukan sendiri `inside/outside` sebagai keputusan final.

## 12. Online Only

Tidak ada offline queue canonical untuk:

```text
Presensi
Jurnal
Kasus
Prestasi
```

Network failure harus tampil sebagai gagal/tertunda, bukan sukses palsu.

## 13. Kartu Pelajar

Mobile dapat menggunakan:

```text
preview kartu
download PDF kartu
```

sesuai permission/scope actor.

QR verify tetap Web public:

```text
/kartu/verify/{kode_verifikasi}
```

## 14. Cordova Security

Ketika project client dibuat:

- whitelist network hanya domain resmi;
- external navigation dibatasi;
- HTTPS only;
- token tidak hardcoded;
- password tidak disimpan;
- cookie Web tidak dijadikan credential API;
- debug logging sensitif dimatikan pada release.

## 15. Capability Minimum

```text
Geolocation
Secure token storage
Network status
File/download
External browser/link terkontrol bila diperlukan
```

Pemilihan plugin mengikuti versi Cordova/Android saat implementasi, bukan daftar package lama yang mungkin obsolete.

## 16. Distribusi

Tahap uji:

```text
APK sideload pada device Android nyata
```

Produksi:

```text
kanal distribusi resmi yang diputuskan madrasah
```

## 17. Gate Sebelum Client Dinyatakan Final

- Web production stabil.
- HTTPS production valid.
- Auth login/me/refresh/logout API PASS.
- `auth_version` invalidation PASS.
- seluruh endpoint mobile menghasilkan JSON yang benar.
- RBAC mobile role target PASS.
- geolocation Android nyata PASS.
- maintenance 503 PASS.
- token storage aman.
- download Kartu PASS.
- build APK PASS.
- multi-device test PASS.
- kebijakan distribusi/update diputuskan.

## 18. Status Saat Ini

```text
Server API core     tersedia
/api/version        tersedia
Profile API         tersedia
JWT infrastructure  tersedia
Cordova client      belum ada di repo
APK production      belum menjadi baseline release Web ini
```
