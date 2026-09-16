# Authentication, RBAC & Menu — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 16 September 2026  
**Application baseline:** `main` @ `06e4e559c045763096058fc889342da78d973314` + G3.3.1 pending merge  
**Database state:** G3.3.1 local + hosting PASS

> Authorization final ditentukan Route/Filter + Service. Menu/JS/View hanya presentation/navigation dan tidak boleh dianggap security boundary.

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

`session.role` adalah primary role dan boleh NULL untuk account tertentu.

## 2. Effective Role

```text
effective_roles = users.role UNION user_roles.role
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

Tidak ada role `pegawai` dan tidak ada role `wali`/`walas`.

## 3. Identity

```text
Guru     -> users.id_guru
Pegawai  -> users.id_pegawai
Siswa    -> users.id_siswa
```

Akun role BK aktual pada localhost dan hosting G3.3.1 menggunakan:

```text
users.role = bk
users.id_pegawai -> pegawai.id
users.id_guru = NULL
```

Karena itu fitur BK tidak boleh memaksa actor mempunyai identity Guru. Nama actor harus dapat di-resolve dari Guru/Pegawai/Siswa/user sesuai relasi aktual.

## 4. Login / Credential

Syarat login:

- username/password terisi;
- user aktif;
- password hash valid.

Lima kegagalan berturut-turut menghasilkan lock sementara 5 menit.

Login berhasil tidak menaikkan `auth_version`; version berubah ketika credential/security state diubah oleh Service berwenang.

Guru/Pegawai managed credential:

```text
identifier = NIP bila tersedia, selain itu NIK
username   = identifier
default/reset password managed = identifier
```

Siswa:

```text
username = NISN
default password = NISN
```

## 5. API Authentication

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

Bearer access/refresh token berada di `api_tokens`. Token membawa/mengecek `auth_version` current.

## 6. Wali Kelas

Wali adalah context Guru berdasarkan mapping aktif:

```text
users.id_guru
-> mapping_wali_kelas
-> tahun aktif
-> mapping aktif
```

Hak Wali menambah hak Guru sesuai permission/scope; tidak membuat role baru.

## 7. Scope

Canonical scope:

```text
SEMUA
KELAS_DIAMPU
KELAS_TERJADWAL
DIRI_SENDIRI
TIDAK_ADA
```

Service boleh menggabungkan multi-scope/dual-context bila workflow membutuhkannya.

## 8. Karakter Role

| Role/Context | Karakter Utama |
|---|---|
| Admin | Full system, settings, backup, master/operasional sesuai permission |
| Operator | Administrasi operasional luas; Settings/Backup tidak otomatis |
| Pimpinan | Supervisi/read-only sesuai permission; tidak menerima detail Konseling |
| BK | Catatan Pelanggaran, Konseling, tindak lanjut, EWS, Prestasi |
| Guru | Jadwal, Presensi, Jurnal, Profile |
| Guru + Wali | Guru + hak contextual kelas wali; tidak otomatis mendapat Konseling |
| Siswa | Read-only/self-service data diri; tidak mendapat Konseling |

## 9. Permission Boundary

```text
PermissionFilter = route gate
Service          = authoritative role/scope/target/business rule
Menu             = navigation only
View/JS          = presentation only
```

Tidak ada hardcoded Admin bypass yang menggantikan business validation Service.

## 10. Permission Konseling G3.3.1

Permission final:

```text
bk_konseling.view
bk_konseling.manage
bk_konseling.export
bk_konseling.settings
```

Mapping:

```text
view/manage/export  -> Admin, Operator, BK / SEMUA
settings            -> Admin, BK / SEMUA
```

Boundary tambahan di Service:

```text
Konseling operasional -> effective role harus salah satu admin/operator/bk + permission
Settings Konseling    -> effective role harus admin/bk + permission
```

Pimpinan, Guru/Wali, dan Siswa harus tetap ditolak walaupun URL diketahui. SQL final juga membersihkan accidental mapping `bk_konseling.*` untuk role tersebut.

## 11. Menu

Menu dibentuk dari:

```text
menus
role_menus
effective role
permission
contextual Wali
MenuService
```

Parent `#` tanpa child visible tidak ditampilkan. Item paling spesifik menjadi active item untuk URL bertumpuk.

Menu G3.3.1:

```text
Catatan Pelanggaran      link bk/kasus
Konseling BK             link bk/konseling
Pengaturan Form Konseling link bk/konseling/settings
```

Visibility final:

```text
Konseling BK             -> admin, operator, bk
Pengaturan Form Konseling -> admin, bk
pimpinan/guru/siswa       -> tidak mendapat dua menu tersebut
Wali                      -> context Guru, jadi tidak otomatis mendapat Konseling
```

Menu visibility bukan authorization boundary.

## 12. Experience Priority

Priority dashboard tetap:

```text
admin > operator > pimpinan > bk > guru > siswa
```

BK berada di atas Guru agar account multi-role BK+Guru tetap mendapat experience BK. Wali tetap context pada experience Guru.

## 13. Security Rules

- Browser tidak menentukan role/scope.
- Semua target ID client divalidasi ulang server-side.
- UI redesign tidak boleh memperluas akses data karena tombol/link baru.
- Data Konseling tidak boleh dibentuk/dikirim ke Pimpinan/Guru/Wali/Siswa hanya untuk kemudian disembunyikan di UI.
- CSRF tetap aktif untuk Web mutation.
- API memakai Bearer/JWT dan bukan CSRF Web.
- Credential/token/session secret tidak ditulis ke log.

## 14. G3.3.1 RBAC Gate

Per 16 September 2026:

```text
Admin Konseling + Settings       PASS
Operator Konseling tanpa Settings PASS
BK Konseling + Settings          PASS
Pimpinan direct access           DENIED / PASS
Guru/Wali direct access          DENIED / PASS
Siswa direct access              DENIED / PASS
Hosting smoke UAT                PASS
```

PR #9 masih menunggu approval eksplisit untuk merge.