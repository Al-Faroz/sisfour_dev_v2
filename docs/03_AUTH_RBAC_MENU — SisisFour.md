# Authentication, RBAC & Menu — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 19 September 2026
**Application baseline:** `main` @ `f6f30ceaf070f342d609c322905ee77dc33f3e6f` + G3.6C feature branch

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

### Web session hardening

Session cookie web memakai nama aplikasi-spesifik:

```text
sisfour_v2_session
```

Jangan menggunakan nama generik `ci_session` pada deployment yang berbagi hostname
dengan aplikasi CodeIgniter lain karena cookie dengan path `/` dapat saling menimpa.

`auth_version` tetap menjadi invalidation token. Bila Admin memperbarui/reset akun
yang sedang digunakan sendiri dan akun tetap aktif, session actor saat ini harus
disinkronkan ke row `users` terbaru sehingga current session tetap valid sementara
session lain dengan auth_version lama tetap terinvalidasi.

`AuthFilter` mencatat alasan invalidasi tanpa menulis session ID/credential:
missing session, missing auth_version, invalid user/status, atau mismatch auth_version.

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
PTSP G3.6B            = CLOSED / MERGED — PR #14
Statistik G3.6C       = CLOSED / MERGED — PR #15; merge f82a0299c8989da6c1026d84861f3e95d786f7dd
```

Dokumentasi tidak membuat capability tersedia di suatu environment. Availability final tetap mengikuti source yang terpasang + state database environment tersebut.

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


## G3.7 — RBAC Invariant

Global Mobile Sweep tidak membuka access baru.

```text
new role            = NONE
new permission      = NONE
new role_permission = NONE
new menu            = NONE
scope change        = NONE
period rule change  = NONE
```

Semua responsive/mobile adaptation tetap presentation-only. Route/Filter + Service existing tetap authoritative. Expected DENY pada cross-role regression tetap wajib dipertahankan.


## 19. G3.9 — Primary Role, Multi-Role & Dashboard Composition

Section ini **supersede Experience Priority §15 untuk pemilihan Dashboard/Home**.
Effective role dan permission union tidak berubah.

### 19.1 Primary Role

```text
users.role = Role 1 / Primary Role
```

Role 1 menentukan identity Dashboard:

```text
Metric Summary
EWS / Access
Data / Activity
dashboard heading/context utama
```

`user_roles.role` menambah effective role/capability, tetapi tidak mengambil alih
Dashboard hanya karena berada lebih tinggi pada priority list lama.

### 19.2 Additional Role

Role tambahan adalah set permission/capability yang divalidasi whitelist G3.9.

```text
Admin      = exclusive
Siswa      = exclusive

Primary BK
→ additional: Operator / Pimpinan / Kesehatan / PTSP
→ max 2

Primary Guru
→ additional: Operator / Pimpinan / Kesehatan / PTSP
→ max 1

Guru + Wali context
→ additional: Operator / Kesehatan / PTSP
→ Pimpinan tetap tidak diizinkan
→ max 1
```

Primary Operator/Pimpinan/Kesehatan/PTSP tetap valid untuk single-role account,
tetapi bukan primary multi-role pada contract G3.9.

### 19.3 Wali

Wali tetap context Guru dan tidak menghabiskan slot `user_roles`.

### 19.4 Person Identity vs Operational Role

G3.9 menormalkan:

```text
person master identity:
Guru    → id_guru
Pegawai → id_pegawai
Siswa   → id_siswa
```

Satu account person tetap mempunyai satu master identity.

BK/Kesehatan/PTSP adalah operational role. Role tersebut dapat dijalankan oleh
valid staff identity:

```text
id_guru OR id_pegawai
```

selama effective role, permission, scope, period, dan business invariant sah.

Role tidak boleh dipakai untuk membuat duplicate Master Guru/Pegawai.

### 19.5 Dashboard Action Composition

```text
Admin / Operator / Pimpinan
→ tidak menghasilkan Primary Action Surface

BK / Kesehatan / PTSP / Guru
→ dapat menghasilkan Primary Action Surface

secondary Operator/Pimpinan
→ tetap menambah capability/menu
→ tidak menambah action section Home
```

UI composition bukan authorization boundary.

### 19.6 Multi-Role Sidebar Composition

G3.9 membedakan **authorization union** dari **navigation composition**.

Authorization tetap:

```text
effective_roles = Primary Role UNION Secondary Roles
permission      = union seluruh effective role yang valid
```

Sidebar tidak boleh lagi melebur seluruh menu effective role menjadi satu tree
tanpa provenance role.

Untuk account multi-role:

```text
Dashboard
↓
Primary Role section
↓
Secondary Role 1 section
↓
Secondary Role 2 section
↓
Account / Profile
```

Rules:

1. Primary/secondary ordering mengikuti `DashboardCompositionService`.
2. Wali tetap context Guru dan tidak membuat section role baru.
3. Secondary Operator/Pimpinan tetap dapat menghasilkan menu sesuai permission,
   walaupun tidak menghasilkan Primary Action Surface Dashboard.
4. Leaf menu yang sudah tampil pada Primary tidak diulang pada Secondary.
5. Parent/container boleh muncul pada lebih dari satu section untuk menjaga
   struktur/provenance role.
6. Dashboard hanya tampil satu kali sebagai global navigation.
7. Profile berasal dari person identity, bukan operational secondary role.
8. Permission/scope/context filtering tetap dijalankan setelah role ownership.
9. Single-role mempertahankan presentation existing; heading role tambahan hanya
   diperlukan pada multi-role.
10. `role_menus` tetap navigation assignment dan bukan security boundary.

Target source flow:

```text
Primary Role + Secondary Roles + Wali + Identity
→ role-owned menu candidates
→ permission/context filter
→ incremental leaf dedupe
→ role-aware Sidebar sections
```


## 20. G3.10 Access Boundary

### BK Group Recording

Pelanggaran Kelompok memakai permission existing:

```text
bk_kasus.view
bk_kasus.manage
```

Konseling Kelompok memakai permission existing:

```text
bk_konseling.view
bk_konseling.manage
bk_konseling.export
```

Privacy Konseling tidak berubah:

```text
Admin / Operator / BK = sesuai permission
Pimpinan / Guru / Wali / Siswa / Kesehatan / PTSP = DEFAULT DENY detail
```

### Student Document Center

Permission baru:

```text
dokumen_siswa.view_self
dokumen_siswa.view_all
dokumen_siswa.manage
dokumen_siswa.export
dokumen_siswa.hard_delete
```

Role default:

```text
Admin     = view_all + manage + export + hard_delete
Operator  = view_all + manage + export + hard_delete
Siswa     = view_self
role lain = DEFAULT DENY
```

Self authorization selalu resolve `users.id_siswa`; request parameter tidak boleh memilih siswa lain.

Google Drive link bukan authorization boundary. SisFour hanya mengontrol visibility/open route; actual Drive sharing tetap mengikuti policy file di Google Drive.


### G3.10B Hard Delete Boundary

`dokumen_siswa.hard_delete` adalah capability destructive terpisah dari
`dokumen_siswa.manage`.

```text
Admin/Operator + hard_delete
→ boleh hard delete selected document IDs

Siswa/role lain
→ DEFAULT DENY
```

Server wajib revalidate seluruh ID, snapshot ke `dokumen_siswa_delete_log`,
hapus dependent access log, lalu hard-delete metadata dalam satu transaction.
File Google Drive tidak pernah dihapus oleh SisFour.
