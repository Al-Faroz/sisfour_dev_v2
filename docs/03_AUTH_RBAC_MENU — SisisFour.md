# Authentication, RBAC & Menu — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 17 September 2026  
**Application baseline:** `main` @ `06e4e559c045763096058fc889342da78d973314` + G3.3.1 rework  
**Database state:** baseline G3.3.1 local+hosting PASS; 17 Sep rework local gate pending

> Authorization final ditentukan Route/Filter + Service. Menu/JS/View hanya presentation/navigation dan tidak menjadi security boundary.

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

Akun role BK aktual memakai:

```text
users.role = bk
users.id_pegawai -> pegawai.id
users.id_guru = NULL
```

Karena itu fitur BK tidak boleh mensyaratkan identity Guru.

## 4. Login / Credential

User harus aktif dan credential valid. `auth_version` digunakan untuk invalidation security state. Credential/token/session secret tidak ditulis ke log.

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

API protected memakai Bearer/token; Web memakai session + CSRF.

## 6. Wali Kelas

Wali adalah context Guru berdasarkan mapping, bukan role baru.

Untuk workflow current-state:

```text
users.id_guru
→ mapping_wali_kelas
→ Tahun Ajaran aktif
```

Untuk **pembacaan histori periodik**, scope kelas harus dievaluasi pada Tahun Ajaran yang sedang dipilih, bukan selalu Tahun aktif.

## 7. Scope

Canonical scope:

```text
SEMUA
KELAS_DIAMPU
KELAS_TERJADWAL
DIRI_SENDIRI
TIDAK_ADA
```

### Period-aware scope

Keputusan 17 September 2026:

> Jika surface periodik memilih `id_tahun`, maka `KELAS_DIAMPU`/scope kelas yang relevan harus dihitung terhadap periode tersebut.

Contoh:

```text
user memilih Tahun Ajaran 2025/2026
→ mapping/jadwal/membership yang dipakai untuk authorization histori = 2025/2026
→ jangan memakai kelas Tahun aktif 2026/2027
```

Record legacy dengan period snapshot `NULL` tidak boleh diberikan ke scope kelas bila periodenya tidak dapat diverifikasi secara aman. Actor `SEMUA` tetap dapat menangani audit/maintenance sesuai permission.

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
Service          = authoritative role/scope/target/period/business rule
Menu             = navigation only
View/JS          = presentation only
```

Semua target ID dan period ID client divalidasi ulang server-side.

## 10. Permission Konseling G3.3.1

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

Boundary Service:

```text
Konseling operasional -> effective role admin/operator/bk + permission
Settings Konseling    -> effective role admin/bk + permission
```

Pimpinan, Guru/Wali, dan Siswa ditolak walaupun URL diketahui.

Permission yang sama mengikat parent Konseling dan Tindak Lanjut Konseling 1:N.

## 11. Menu

Menu G3.3.1:

```text
Catatan Pelanggaran       bk/kasus
Konseling BK              bk/konseling
Pengaturan Form Konseling bk/konseling/settings
```

Visibility:

```text
Konseling BK              -> admin, operator, bk
Pengaturan Form Konseling -> admin, bk
pimpinan/guru/siswa       -> tidak mendapat dua menu tersebut
Wali                       -> context Guru, tidak otomatis mendapat Konseling
```

Menu visibility bukan authorization boundary.

## 12. Experience Priority

```text
admin > operator > pimpinan > bk > guru > siswa
```

BK berada di atas Guru agar account multi-role BK+Guru tetap mendapat experience BK. Wali tetap context pada experience Guru.

## 13. Security Rules

- Browser tidak menentukan role/scope/period authorization.
- UI redesign tidak boleh memperluas akses data.
- Data Konseling/follow-up tidak boleh dikirim ke role terlarang lalu hanya disembunyikan.
- Period filter tidak boleh menjadi cara melewati class scope.
- Direct ID access selalu divalidasi terhadap target record dan period record.
- CSRF tetap aktif untuk Web mutation.
- API memakai Bearer/JWT.

## 14. G3.3.1 Gate

Gate baseline 16 September telah PASS untuk RBAC/privacy. Rework 17 September mengubah period handling dan follow-up history, sehingga regression berikut wajib diulang:

```text
Admin/Operator/BK Konseling sesuai permission
Pimpinan/Guru/Wali/Siswa tetap ditolak Konseling
KELAS_DIAMPU current year benar
KELAS_DIAMPU historical year memakai period terpilih
legacy id_tahun NULL tidak bocor ke scope kelas
follow-up Konseling memakai boundary parent yang sama
```

Status:

```text
baseline RBAC/privacy                PASS
17 Sep period-aware scope source     IMPLEMENTED / UAT PENDING
17 Sep follow-up RBAC                IMPLEMENTED / UAT PENDING
hosting rework                       NOT STARTED
PR #9                                DRAFT / BELUM MERGE
```