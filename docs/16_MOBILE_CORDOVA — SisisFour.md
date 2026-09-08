# 16 Mobile Cordova — SisisFour

**Versi Acuan Utama:** v0.5 FINAL BASELINE  
**Tanggal Acuan:** 08 September 2026  
**Baseline Aplikasi:** `main` @ `b85b857e1a38b6eb1fd26ba2d9aa61ae5e679f55`  
**Baseline Database:** `sisfour_dev_v2 (15).sql`

Dokumen ini adalah **acuan utama** dan menggambarkan baseline aplikasi yang berlaku. Bagian yang belum tersedia di repo dinyatakan sebagai gap/blocker, bukan diasumsikan sudah selesai.

---

# 1. Kedudukan Tahap Mobile

Aplikasi mobile SisisFour direncanakan menggunakan **Apache Cordova** dengan WebView Android dan bersifat **online-only**.

Tahap mobile dikerjakan setelah Web SisisFour melewati Testing & Polish.

Baseline repo ini belum memuat project Cordova (`config.xml`, `www/`, `platforms/`, atau struktur client Cordova). Karena itu dokumen ini adalah kontrak implementasi mobile yang harus memakai API server yang benar-benar tersedia.

# 2. Prinsip Client

Frontend APK:

```text
HTML
CSS
Vanilla JavaScript
Fetch API
Cordova WebView
```

Business JavaScript tidak menggunakan jQuery.

Client mobile tidak menjalankan business rule authorization. Permission dan scope tetap authoritative di server.

# 3. Role Mobile

Target utama:

| Role | Mobile |
|---|---|
| Admin | bukan target utama |
| Operator | bukan target utama |
| Pimpinan | ya |
| BK | ya |
| Guru | ya |
| Wali | ya sebagai konteks Guru |
| Siswa | ya |

Admin/Operator tetap menggunakan Web untuk pekerjaan administratif berat seperti Master Data, Settings, Backup, dan pengelolaan user.

# 4. Authentication API

Endpoint public authentication:

| Method | Endpoint | Controller |
|---|---|---|
| POST | `/api/auth/login` | `Auth::apiLogin` |
| POST | `/api/auth/refresh` | `Auth::apiRefresh` |
| GET | `/api/version` | `Api::version` |

Endpoint protected:

| Method | Endpoint | Filter |
|---|---|---|
| POST | `/api/auth/logout` | `auth:api` |
| GET | `/api/auth/me` | `auth:api` |

# 5. Token

Server memakai `api_tokens`.

Access dan refresh token diterbitkan oleh `JwtService`.

Mobile wajib:

1. menyimpan token secara aman;
2. mengirim `Authorization: Bearer ...`;
3. melakukan refresh saat access token expired;
4. logout bila refresh gagal/revoked;
5. tidak menyimpan password setelah login.

`auth_version` digunakan untuk invalidasi session/token lama.

# 6. API Bisnis yang Terdaftar

Semua endpoint berikut berada dalam group `auth:api`.

## Dashboard

```text
GET /api/dashboard
GET /api/dashboard/data
```

Permission:

```text
dashboard.view
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

Permission sesuai route:

```text
presensi_siswa.input
presensi_siswa.revisi
presensi_siswa.view
ews_radar.view
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

GET  /api/bk/prestasi
POST /api/bk/prestasi/create
```

## Kartu Pelajar

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

# 7. Gap API Baseline

## BLOCKER-MOBILE-API-01 — Version

Route:

```text
GET /api/version → Api::version
```

terdaftar di `Routes.php`, tetapi file:

```text
app/Controllers/Api.php
```

tidak ditemukan pada baseline repo.

Endpoint version belum boleh dianggap runtime-ready sampai Controller tersebut tersedia atau route disesuaikan.

## BLOCKER-MOBILE-PROFILE-01 — Profile

Route API Profile terdaftar, tetapi:

```text
app/Controllers/ProfileGuru.php
app/Controllers/ProfileSiswa.php
```

tidak ditemukan.

Fitur Profile mobile belum runtime-ready.

# 8. JSON Contract

Endpoint API mobile harus menghasilkan JSON konsisten.

Success minimum:

```json
{
  "success": true,
  "message": "Berhasil.",
  "data": {}
}
```

Error minimum:

```json
{
  "success": false,
  "message": "..."
}
```

Untuk endpoint Controller yang juga melayani Web, request API tidak boleh menghasilkan halaman HTML sebagai response normal.

# 9. Maintenance

Saat Maintenance ON:

- effective Admin dapat tetap akses;
- user mobile non-Admin mendapat HTTP 503 JSON;
- login non-Admin diblokir;
- API response memakai code `MAINTENANCE`.

Client harus menampilkan pesan maintenance dan tidak melakukan retry tanpa batas.

# 10. Geolocation

Presensi mobile yang memerlukan geofence harus:

1. meminta izin lokasi perangkat;
2. membaca latitude/longitude terkini;
3. mengirim koordinat ke server;
4. server menghitung validitas radius.

Client tidak boleh menentukan sendiri hasil `inside/outside`.

Pesan login Web:

```text
Aktifkan lokasi di perangkat saat menggunakan aplikasi
```

juga relevan sebagai edukasi user mobile/Web.

# 11. HTTPS

Produksi wajib HTTPS.

Tujuan:

- melindungi token;
- mendukung geolocation;
- mencegah mixed content;
- menjaga request API.

# 12. Online Only

Tidak ada queue offline untuk:

```text
Presensi
Jurnal
Kasus
Prestasi
```

Jika jaringan gagal, client harus memberi error jelas dan tidak mengklaim data tersimpan.

# 13. Kartu Pelajar

APK dapat:

```text
preview kartu
download PDF kartu
```

sesuai scope actor.

QR/public verification tetap mengarah pada endpoint public Web:

```text
/kartu/verify/{kode_verifikasi}
```

# 14. Profile Mobile

Setelah blocker Profile selesai:

Guru:

```text
view diri
edit field yang diizinkan
upload foto
NIP readonly
```

Siswa:

```text
view diri
readonly
```

# 15. Security Client

Dilarang menyimpan atau menampilkan:

- password;
- password hash;
- refresh token di log console;
- access token di UI;
- cookie Web;
- data role/scope buatan client.

Token storage harus memakai storage yang sesuai untuk aplikasi Android, bukan hardcoded source.

# 16. Cordova Configuration

Project Cordova ketika dibuat minimal mempunyai:

```text
config.xml
www/
platforms/android/
plugins/
```

Whitelist/network access hanya untuk domain API resmi.

External navigation tidak boleh dibuka bebas.

# 17. Plugin/Capability Minimum

Capability yang dibutuhkan:

```text
Geolocation
File/Download
Network status
Browser/external link terkontrol bila diperlukan
```

Pemilihan plugin Cordova harus mengikuti versi Cordova/Android target pada saat build, bukan dikunci pada package yang sudah obsolete.

# 18. Distribusi

Fase pengujian:

```text
APK sideload pada device Android nyata
```

Fase produksi:

```text
Google Play / kanal distribusi resmi yang diputuskan sekolah
```

# 19. Mekanisme Versi

Setelah `Api::version` tersedia, client dapat membandingkan:

```text
versi aplikasi lokal
vs
versi server
```

Jika update tersedia, user diarahkan ke kanal update resmi.

Tidak ada silent APK update dari server aplikasi.

# 20. Checklist Tahap 16

- [ ] Testing & Polish Web selesai.
- [ ] `Api::version` tersedia dan diuji.
- [ ] Profile API sinkron atau dikeluarkan dari scope mobile.
- [ ] Auth JWT login/me/refresh/logout lulus.
- [ ] auth_version invalidation lulus.
- [ ] seluruh API mobile mengembalikan JSON.
- [ ] RBAC mobile seluruh role lulus.
- [ ] geolocation Android nyata lulus.
- [ ] Maintenance JSON lulus.
- [ ] project Cordova dibuat.
- [ ] responsive mobile UI selesai.
- [ ] token storage aman.
- [ ] build APK berhasil.
- [ ] sideload test beberapa device.
- [ ] production HTTPS.
- [ ] distribusi/update diputuskan.

# 21. Status Baseline

```text
Server API core      → tersedia
JWT infrastructure   → tersedia
Cordova client       → belum ada di repo
/api/version         → route ada, Controller Api tidak ditemukan
Profile API          → route ada, Controller Profile tidak ditemukan
```

Tahap Mobile belum boleh dinyatakan FINAL sebelum blocker di atas selesai.
