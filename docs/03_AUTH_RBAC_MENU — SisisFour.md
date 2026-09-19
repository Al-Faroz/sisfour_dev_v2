# Authentication, RBAC & Menu — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 18 September 2026
**Application baseline:** `main` @ `90acc7f94fee391a5a7fbad2395e3f16571fe921` + G3.6B feature branch

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
kesehatan
ptsp
```

Tidak ada role `pegawai` dan tidak ada role `wali`/`walas`. Wali Kelas adalah context Guru.

Multi-role = YA.

## 3. Identity

```text
Guru       -> users.id_guru
Siswa      -> users.id_siswa
BK         -> users.id_pegawai
Kesehatan  -> users.id_pegawai
PTSP       -> users.id_pegawai
```

Role berbasis Pegawai tidak boleh dipaksa memiliki `users.id_guru`.

## 4. Access Boundary vs Capability vs Scope

Canonical order:

```text
Effective Role
→ Access Boundary
→ Permission / Capability
→ Scope
→ Period
→ Target Validation
→ Business Invariant
```

Role di luar Access Boundary tidak ditulis sebagai sekadar `tidak boleh edit`; role tersebut **tidak memiliki akses domain**.

Global stance:

```text
DEFAULT DENY
```

Role/context yang tidak disebut pada domain tidak otomatis mendapat akses.

`Full Access` bersifat domain-specific dan tidak berarti akses seluruh aplikasi.

## 5. Login / Credential

User harus aktif dan credential valid. `auth_version` digunakan untuk invalidation security state. Credential/token/session secret tidak ditulis ke log.

## 6. API Authentication

Public baseline:

```text
POST /api/auth/login
POST /api/auth/refresh
GET  /api/version
```

Protected baseline:

```text
POST /api/auth/logout
GET  /api/auth/me
```

API protected memakai Bearer/token; Web authenticated memakai session + CSRF.

Domain boleh mempunyai public endpoint bila SSOT eksplisit menetapkannya. Public endpoint tidak otomatis memberi akses ke admin/list/detail internal.

PTSP target mempunyai public submission + public aggregate statistics API sesuai `18_PTSP — SisisFour.md`.

## 7. Wali Kelas

Wali adalah context Guru berdasarkan mapping, bukan role baru.

Workflow current-state:

```text
users.id_guru
→ mapping_wali_kelas
→ Tahun Ajaran aktif
```

Pembacaan histori periodik:

```text
scope wali / kelas relevan
→ dievaluasi pada Tahun Ajaran record/filter
→ bukan selalu Tahun aktif
```

Untuk UKS, Guru+Wali hanya ReadOnly **kelas wali**. Guru non-Wali tidak mendapat akses UKS.

## 8. Scope

Canonical scope:

```text
SEMUA
KELAS_DIAMPU
KELAS_TERJADWAL
KELAS_WALI
DIRI_SENDIRI
UNIT / DOMAIN KHUSUS
TIDAK_ADA
```

Period-aware scope wajib menggunakan periode yang sedang berlaku untuk action tersebut.

## 9. Karakter Role

| Role/Context | Karakter Utama |
|---|---|
| Admin | Full system sesuai permission; bukan bypass business rule |
| Operator | Administrasi operasional luas sesuai permission |
| Pimpinan | Supervisi/read-only sesuai permission/domain |
| BK | Domain BK: Pelanggaran, Konseling, EWS, Prestasi |
| Guru | Jadwal, Presensi, Jurnal, Profile sesuai permission |
| Guru + Wali | Guru + scope contextual kelas wali |
| Siswa | Self-service/read-only pada domain yang diberikan |
| Kesehatan | Full Access **domain UKS**; identity Pegawai |
| PTSP | Full Access **domain PTSP**; identity Pegawai |

## 10. Permission Boundary

```text
PermissionFilter = route gate
Service          = authoritative role/scope/target/period/business rule
Menu             = navigation only
View/JS          = presentation only
```

Semua target ID dan period ID client divalidasi ulang server-side.

## 11. Konseling G3.3.1

Permission:

```text
bk_konseling.view
bk_konseling.manage
bk_konseling.export
bk_konseling.settings
```

Mapping:

```text
view/manage/export -> Admin, Operator, BK / SEMUA
settings           -> Admin, BK / SEMUA
```

Access Boundary Konseling:

```text
Admin / Operator / BK = masuk domain sesuai permission
Pimpinan / Guru / Wali / Siswa / Kesehatan / PTSP = TIDAK memiliki akses domain/detail Konseling
```

Permission parent dan Tindak Lanjut Konseling 1:N mengikuti boundary yang sama.

G3.6C exception: halaman Statistik boleh menampilkan aggregate school-wide Konseling kepada Admin/Operator/Pimpinan (total/status/bidang/tren) tanpa record/detail individual. Exception ini tidak memberi `bk_konseling.*` kepada Pimpinan dan tidak membuka menu/listing/detail/export Konseling.

## 12. UKS / Kesehatan — G3.6A RBAC

SSOT domain: `17_UKS_KESEHATAN — SisisFour.md`.

```text
Admin       = Full Access UKS / SEMUA
Operator    = Full Access UKS / SEMUA
Kesehatan   = Full Access UKS / SEMUA
Pimpinan    = ReadOnly SEMUA + Export
Guru + Wali = ReadOnly KELAS_WALI
Siswa       = ReadOnly DIRI_SENDIRI
Guru non-Wali / BK / PTSP / role lain = DENY
```

Full Access UKS mencakup capability domain yang dikunci pada docs/17, termasuk create/update/import/export/soft-delete/master. Ia **tidak memberi akses domain PTSP/BK**.

Permission G3.6A:

```text
uks_ckg.view
uks_ckg.manage
uks_ckg.import
uks_ckg.export
uks_harian.view
uks_harian.manage
uks_harian.export
uks_master.manage
```

Role `kesehatan` hanya valid bila user memiliki `users.id_pegawai`.

## 13. PTSP — G3.6B RBAC

SSOT domain: `18_PTSP — SisisFour.md`.

Internal administration:

```text
Admin     = Full Access PTSP / SEMUA
Operator  = Full Access PTSP / SEMUA
PTSP      = Full Access PTSP / SEMUA
Pimpinan  = ReadOnly SEMUA + Export
role lain = DENY internal PTSP administration
```

Public submission:

```text
Layanan PTSP     = public tanpa login
Polling Kepuasan = public tanpa login
Pengaduan        = public anonim tanpa login
```

Public submission tidak memberi view/list/detail internal.

Hard delete PTSP adalah capability eksplisit Admin/Operator/PTSP dan tidak diwariskan ke domain lain.

Public statistics API PTSP hanya aggregate read-only; tidak boleh membocorkan PII/raw record.

## 14. Menu Visibility

Menu visibility mengikuti Access Boundary + permission, tetapi bukan authorization final.

G3.3.1:

```text
Konseling BK              -> admin, operator, bk
Pengaturan Form Konseling -> admin, bk
role lain                 -> tidak mendapat menu tersebut
```

G3.6A UKS:

```text
UKS -> Admin, Operator, Kesehatan, Pimpinan, Guru+Wali, Siswa
Guru non-Wali / BK / PTSP -> DENY
```

Untuk former Wali, entry gate UKS boleh tersedia agar period historis dapat dipilih; data final selalu dihitung dari mapping Wali + membership pada Tahun Ajaran terpilih.

Target PTSP internal:

```text
PTSP administration -> Admin, Operator, PTSP, Pimpinan
```

Public PTSP landing berada di luar menu authenticated.

## 15. Experience Priority

Priority G3.6B **LOCKED**:

```text
admin > operator > pimpinan > bk > kesehatan > ptsp > guru > siswa
```

Wali tetap context pada experience Guru.

## 16. Security Rules

- Browser tidak menentukan role/scope/period authorization.
- UI redesign tidak boleh memperluas akses data.
- Data tidak boleh dikirim ke role terlarang lalu hanya disembunyikan.
- Period filter tidak boleh melewati scope.
- Direct ID access selalu divalidasi terhadap target + period.
- Authenticated Web mutation memakai CSRF.
- Public form mengikuti public contract domain + server validation.
- Public statistics API hanya aggregate dan tidak menjadi backdoor raw data.
- Role multi-role tidak boleh mendapat cross-domain access hanya karena salah satu role memiliki Full Access pada domain lain.

## 17. Regression Matrix

Setiap domain baru wajib diuji terhadap:

```text
Admin
Operator
Pimpinan
BK
Guru
Guru + Wali
Siswa
Kesehatan
PTSP
```

Expected `DENY` tetap merupakan test case wajib.

Public PTSP surface diuji terpisah dari authenticated role matrix.

## 18. Status Implementasi

```text
Konseling G3.3.1     = CLOSED / MERGED — PR #9
Dashboard Siswa G3.6 = CLOSED / MERGED — PR #12
UKS/Kesehatan G3.6A  = CLOSED / MERGED — PR #13
PTSP G3.6B            = source/schema/permission/menu/public API IMPLEMENTED ON FEATURE BRANCH; localhost SQL PENDING
```

Tidak ada permission/menu/route UKS/PTSP yang dianggap tersedia hanya karena sudah tercatat pada dokumen target.

## G3.6C — Statistik Access Boundary

Capability baru:

```text
statistik.view
statistik.export_pdf
```

Matrix:

```text
Admin      SEMUA view + export
Operator   SEMUA view + export
Pimpinan   SEMUA view + export

BK         DENY
Kesehatan  DENY
PTSP       DENY
Guru/Wali  DENY
Siswa      DENY
```

Menu `Statistik` bukan security boundary. Route memakai PermissionFilter dan `StatistikService` melakukan authorization ulang.

Signage tetap OPEN/PUBLIC. Shortcut Signage hanya ditampilkan di dashboard Admin/Operator/Pimpinan sebagai experience shortcut, bukan sebagai pembatas route.
