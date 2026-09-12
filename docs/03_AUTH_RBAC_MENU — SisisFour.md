# Authentication, RBAC & Menu — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 12 September 2026
**Baseline Aplikasi:** `main` @ `39da4651acd29adcd575677d7a37c058bf32269d`
**Baseline Database:** `sisfour_dev_v2 (33).sql`


> Dokumen ini menyatakan kontrak yang berlaku pada baseline di atas. Dokumen ini **bukan changelog** dan tidak menyimpan narasi fase lama.

## 1. Web Authentication

Web menggunakan database session.

Session actor minimum:

```text
user_id
role
username
id_guru
id_pegawai
id_siswa
auth_version
logged_in
```

`session.role` hanya primary role dan boleh NULL untuk account tertentu, khususnya Pegawai sebelum role operasional diberikan.

## 2. Login dan Lockout

Syarat login:

- username dan password terisi;
- user aktif;
- password hash valid.

Lima kegagalan berturut-turut menghasilkan lock sementara 5 menit.

Login berhasil:

- mencatat attempt berhasil;
- membentuk session actor;
- mencatat LOGIN;
- **tidak menaikkan `auth_version`**.

`auth_version` hanya berubah ketika credential/state keamanan diubah oleh Service berwenang.

## 3. Credential Managed

### Guru/Pegawai

```text
identifier = NIP bila tersedia
             selain itu NIK
username   = identifier
default/reset password managed = identifier
```

Jika NIP baru ditambahkan pada account yang sebelumnya memakai NIK:

```text
username -> NIP
password -> reset ke NIP
auth_version -> increment
```

Perubahan dilakukan atomically.

### Siswa

```text
username = NISN
default password = NISN
```

## 4. API Authentication

Public:

```text
POST /api/auth/login
POST /api/auth/refresh
GET  /api/version
```

Protected:

```text
POST /api/auth/logout
GET  /api/auth/me
```

Bearer access token dan refresh token disimpan di `api_tokens`.

Canonical RequestContext:

```text
api_user
api_access_token
api_token_row
api_claims
```

Kontrak:

- access token sekitar 1 jam;
- refresh token sekitar 30 hari;
- token dapat direvoke;
- `auth_version` token harus sama dengan user current.

## 5. Effective Role

```text
effective_roles =
users.role
UNION
user_roles.role
```

Role resmi:

```text
admin
operator
pimpinan
bk
guru
siswa
```

Tidak ada role `pegawai`.

## 6. Wali Kelas

Wali bukan role.

```text
users.id_guru
-> mapping_wali_kelas
-> tahun aktif
-> mapping aktif
```

Hak Wali **menambah** hak Guru, bukan menggantikan.

Dalam desain UI, istilah **Walas** berarti experience Guru yang memiliki mapping Wali aktif. Jangan membuat role `walas` baru hanya untuk kebutuhan tampilan.

## 7. Scope

```text
SEMUA
KELAS_DIAMPU
KELAS_TERJADWAL
DIRI_SENDIRI
TIDAK_ADA
```

Service dapat menggunakan multi-scope/dual-context bila workflow membutuhkannya.

## 8. Karakter Role

| Role/Context | Karakter Utama |
|---|---|
| Admin | Full system, settings, backup, seluruh master/operasional |
| Operator | Administrasi operasional luas; tidak otomatis Settings/Backup |
| Pimpinan | Supervisi/read-only sesuai permission |
| BK | Monitoring BK, kasus, prestasi, EWS |
| Guru | Jadwal, Presensi, Jurnal, Profile |
| Guru + Wali | Guru + hak contextual kelas wali |
| Siswa | Read-only data diri, Profile, Kartu diri |

## 9. Permission Boundary

- PermissionFilter = route gate.
- Service = authoritative scope/target.
- Menu = navigation only.
- View/JS = presentation only.
- Tidak ada hardcoded Admin bypass pada business rule.

## 10. Menu

Menu dibentuk dari:

```text
menus
role_menus
effective role
permission
contextual Wali
MenuService
```

Parent `#` tanpa child visible tidak ditampilkan. Untuk URL bertumpuk, item paling spesifik menjadi active item.

### Prinsip UI Guru/Wali/Siswa

- menu yang terlihat bukan sumber authorization;
- menyembunyikan menu yang tidak relevan boleh dilakukan untuk UX, tetapi direct URL tetap harus aman di Filter/Service;
- Guru non-Wali dan Guru+Wali boleh memiliki sidebar berbeda secara contextual tanpa menambah role baru;
- Siswa hanya mendapat navigasi yang mengarah ke data diri sendiri;
- keputusan final tentang penyederhanaan sidebar tiga experience tersebut harus dicatat di dokumen UI/UX dan diuji sebelum source dianggap final.

## 11. Experience Role UI

Priority dashboard:

```text
admin > operator > pimpinan > bk > guru > siswa
```

BK berada di atas Guru agar akun BK dengan identity/secondary role Guru tetap memperoleh Dashboard BK.

Wali tidak masuk priority role karena merupakan context di dalam Dashboard Guru.

## 12. Maintenance

Saat ON:

```text
effective Admin -> tetap dapat akses/login
non-Admin Web   -> 503 HTML
non-Admin API   -> 503 JSON
```

AJAX Admin yang session-authenticated tetap dapat beroperasi agar maintenance dapat dimatikan.

## 13. CSRF dan Session Recovery

Web mutation `POST/PUT/PATCH/DELETE` diproteksi CSRF.

`assets/js/csrf-fetch.js` memasang token pada same-origin mutation dan mendeteksi session-expired redirect ke `/auth/login`.

## 14. Security Rules

- Browser tidak menentukan role/scope.
- Menu bukan security boundary.
- Semua target ID dari client harus divalidasi ulang server-side.
- UI redesign tidak boleh memperluas akses data hanya karena link/tombol ditambahkan.
