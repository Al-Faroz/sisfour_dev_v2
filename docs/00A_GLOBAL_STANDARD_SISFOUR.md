# Global Standard SisisFour

**Status:** Canonical / Global SSOT  
**Tanggal Acuan:** 17 September 2026  
**Kedudukan:** companion wajib `00_POLA_PENGERJAAN___SisisFour.md`; dibaca sebelum dokumen domain/fitur.

> Dokumen ini menetapkan **cara berpikir dan mapping global** untuk semua fitur SisisFour. Ia tidak menggantikan business rule domain. Dokumen domain menjelaskan *apa* aturannya; dokumen ini memastikan aturan diterapkan konsisten dari authorization sampai UI, persistence, export, audit, API, testing, dan deployment.

## 1. Canonical Mapping

Urutan keputusan fitur:

```text
Menu / Fitur
→ Use Case
→ SSOT / Domain
→ Access Boundary
→ Capability
→ Scope
→ Period Context
→ Target Validation
→ Business Invariant
→ Persistence
→ Service Boundary
→ Presentation UI
→ Output Channel
→ Audit / Observability
→ Testing / Regression
→ Docs Sync
→ Deployment Gate
```

Perbedaan berikut wajib dijaga:

```text
Tidak lolos Access Boundary
!= punya akses tetapi capability tidak tersedia

Tidak punya capability
!= target berada di luar scope

Target berada dalam scope
!= otomatis lolos business invariant

UI menyembunyikan tombol
!= authorization

Role ada di sistem
!= otomatis punya akses semua domain

Full Access pada domain A
!= Full Access pada domain B
```

Global security stance:

```text
DEFAULT DENY
```

Role/context yang tidak dinyatakan berada di Access Boundary suatu domain tidak mendapat akses sampai SSOT memutuskan sebaliknya.

## 2. Master Mapping Global

```mermaid
flowchart TD
    A["MENU / FITUR"] --> B["USE CASE / TUJUAN BISNIS"]
    B --> C["SSOT / BUSINESS CONTRACT"]
    C --> D["DOMAIN & ENTITAS"]
    D --> E["ACCESS BOUNDARY"]

    E --> E1{"Actor boleh masuk domain?"}
    E1 -- "TIDAK" --> X["DENY TOTAL ACCESS"]
    X --> X1["Tidak ada menu/admin surface"]
    X --> X2["Direct URL ditolak"]
    X --> X3["Service tidak membentuk data"]

    E1 -- "YA" --> F["CAPABILITY"]
    F --> F1["View"]
    F --> F2["Create"]
    F --> F3["Update"]
    F --> F4["Delete / Cancel / Void"]
    F --> F5["Export"]
    F --> F6["Settings / Master"]
    F --> F7["Approve / Verify / Status"]

    F1 --> G["SCOPE DATA"]
    F2 --> G
    F3 --> G
    F4 --> G
    F5 --> G
    F6 --> G
    F7 --> G

    G --> G1["SEMUA"]
    G --> G2["KELAS WALI / KELAS_DIAMPU"]
    G --> G3["KELAS_TERJADWAL"]
    G --> G4["DIRI_SENDIRI"]
    G --> G5["UNIT / DOMAIN KHUSUS"]
    G --> G6["TIDAK_ADA"]

    G1 --> H["PERIOD CONTEXT"]
    G2 --> H
    G3 --> H
    G4 --> H
    G5 --> H
    G6 --> H

    H --> I["TARGET VALIDATION"]
    I --> J["BUSINESS INVARIANT"]
    J --> K["PERSISTENCE"]
    K --> L["SERVICE / APPLICATION BOUNDARY"]
    L --> M["PRESENTATION UI"]
    M --> N["OUTPUT CHANNEL"]
    N --> O["AUDIT / OBSERVABILITY"]
    O --> P["TESTING / REGRESSION"]
    P --> Q{"Semua gate PASS?"}

    Q -- "TIDAK" --> R["Kembali ke layer yang salah"]
    R --> C
    Q -- "YA" --> S["DOCS FINAL SYNC"]
    S --> T["DEPLOYMENT GATE"]
```

## 3. Authorization Standard

```mermaid
flowchart TD
    A["REQUEST"] --> B{"Authenticated / public contract valid?"}
    B -- "NO" --> X1["DENY / LOGIN / INVALID PUBLIC REQUEST"]
    B -- "YES" --> C["Resolve actor / public context"]

    C --> D{"Access Boundary lolos?"}
    D -- "NO" --> X2["403 / DENY TOTAL ACCESS"]
    D -- "YES" --> E{"Capability tersedia?"}

    E -- "NO" --> X3["403 ACTION DENIED"]
    E -- "YES" --> F["Resolve Scope"]
    F --> G{"Target dalam scope?"}

    G -- "NO" --> X4["403 TARGET DENIED"]
    G -- "YES" --> H["Resolve Period Context"]
    H --> I{"Target/period valid?"}

    I -- "NO" --> X5["INVALID PERIOD / TARGET"]
    I -- "YES" --> J["Business Validation"]
    J --> K{"Invariant valid?"}

    K -- "NO" --> X6["422 BUSINESS ERROR"]
    K -- "YES" --> L["Execute"]
    L --> M["Persist"]
    M --> N["Audit"]
    N --> O["Response / Render / Export / API"]
```

Canonical wording:

```text
Actor di luar Access Boundary:
"tidak memiliki akses fitur X"

Actor di dalam Access Boundary tetapi capability tidak ada:
"tidak memiliki capability action X"
```

Jangan mencampur dua kondisi tersebut.

## 4. Full Access / ReadOnly

```text
Full Access = seluruh capability operasional yang DIDEFINISIKAN domain.
```

Full Access tidak otomatis berarti:

```text
akses semua domain
hard delete
cancel / void
settings
approval khusus
bypass scope
bypass business invariant
```

`ReadOnly` berarti kemampuan baca pada scope yang sah. Export tidak otomatis ikut ReadOnly; export diputuskan eksplisit per domain.

## 5. Canonical Role Registry

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

`Wali Kelas` = context Guru, bukan role baru.

Identity:

```text
Guru       -> users.id_guru
Siswa      -> users.id_siswa
BK         -> users.id_pegawai
Kesehatan  -> users.id_pegawai
PTSP       -> users.id_pegawai
```

Multi-role = diperbolehkan.

```text
users.role UNION user_roles.role
→ permission
→ Access Boundary
→ Capability
→ Scope
→ Target/Period Validation
```

## 6. Domain Access Baseline — UKS

Detail SSOT: `17_UKS_KESEHATAN — SisisFour.md`.

```mermaid
flowchart TD
    A["MENU UKS"] --> B{"Actor / Context"}

    B -- "Admin" --> F["FULL ACCESS UKS / SEMUA"]
    B -- "Operator" --> F
    B -- "Kesehatan" --> F

    B -- "Pimpinan" --> P["READONLY + EXPORT / SEMUA"]
    B -- "Guru + Wali" --> W["READONLY / KELAS WALI"]
    B -- "Siswa" --> S["READONLY / DIRI_SENDIRI"]

    B -- "Guru non-Wali / BK / PTSP / role lain" --> X["DEFAULT DENY"]

    F --> V["Period + Target Validation"]
    P --> V
    W --> V
    S --> V
    V --> I["Business Invariant"]
```

Canonical UKS baseline:

```text
Data CKG + Catatan Harian UKS = periodik Tahun Ajaran
Pimpinan = ReadOnly SEMUA + XLSX
Wali = hanya kelas wali, termasuk histori pada periode lama
Siswa = seluruh kesehatan dirinya sendiri, view only
Admin/Operator/Kesehatan = edit + import + export + soft delete + master sesuai domain
Guru non-Wali = tidak memiliki akses UKS
```

## 7. Domain Access Baseline — PTSP

Detail SSOT: `18_PTSP — SisisFour.md`.

Internal admin surface:

```mermaid
flowchart TD
    A["PTSP INTERNAL"] --> B{"Actor"}
    B -- "Admin" --> F["FULL ACCESS PTSP / SEMUA"]
    B -- "Operator" --> F
    B -- "PTSP" --> F
    B -- "Pimpinan" --> P["READONLY + EXPORT / SEMUA"]
    B -- "role lain" --> X["DEFAULT DENY"]
```

Public surface:

```mermaid
flowchart TD
    A["PTSP PUBLIC LANDING"] --> B["Layanan PTSP"]
    A --> C["Polling Kepuasan"]
    A --> D["Pengaduan Anonim"]

    B --> E["Create public submission"]
    C --> E
    D --> E
    E --> F["Snapshot Tahun Ajaran aktif"]
    F --> G["Server Validation"]
    G --> H["Persist"]
```

Canonical PTSP baseline:

```text
PTSP/Admin/Operator = Full Access hanya domain PTSP
Pimpinan = ReadOnly SEMUA + XLSX
Layanan/Polling/Pengaduan = public form tanpa login
Layanan status = Baru -> Diproses -> Selesai
Pengaduan status = Masuk -> Diverifikasi -> Diproses/Selesai
Pengaduan = satu form anonim, internal/eksternal tidak dibedakan
Hard delete PTSP = Admin/Operator/PTSP
Thermal print = bukti pengisian, tanpa nomor tiket/antrian/tracking
```

## 8. Public Statistics API Standard

Jika domain menyediakan API statistik untuk portal publik, gunakan pola:

```text
Public GET only
Aggregate only
No PII
No raw record
Period/filter explicit
CORS/public consumption sesuai contract
```

PTSP wajib menyediakan statistik public per form:

```text
Layanan
Polling
Pengaduan
```

```mermaid
flowchart LR
    A["Domain Data"] --> B["Aggregate Service"]
    B --> C["Public Statistics API"]
    C --> D["WordPress / Portal"]
    C --> E["Copy/Paste Widget / JS"]
    B --> X["NO PII / NO RAW RECORD"]
```

Public statistics API tidak boleh menjadi backdoor untuk melewati Access Boundary internal.

## 9. Period Context Standard

Canonical untuk domain periodik:

```mermaid
flowchart TD
    A["ACTION PERIODIK"] --> B{"Jenis action?"}

    B -- "Read / History" --> C["Tahun Ajaran filter"]
    B -- "Export" --> C
    B -- "Dashboard / Statistik Historis" --> C

    B -- "Create Parent Baru" --> D["Tahun Ajaran aktif"]
    B -- "Update Existing" --> E["Pertahankan period record"]
    B -- "Create Child / Follow-up" --> F["Ikuti period parent"]

    C --> G["Resolve scope pada period"]
    D --> H["Validate target pada period aktif"]
    E --> I["Validate target terhadap record"]
    F --> J["Validate child terhadap parent"]
```

Tidak semua tabel harus periodik. Tahun Ajaran hanya dipakai jika domain contract menetapkannya.

## 10. Parent / Child / Delete Standard

```mermaid
flowchart TD
    A["PARENT RECORD"] --> B["Main data"]
    A --> C["CHILD / HISTORY 1:N bila domain perlu"]
    C --> C1["History #1"]
    C --> C2["History #2"]
    C --> C3["History #N"]

    C1 --> D["Child tidak menimpa child lain"]
    C2 --> D
    C3 --> D

    D --> E{"Delete contract?"}
    E -- "Hard Delete" --> H["Hanya jika domain eksplisit mengizinkan"]
    E -- "Soft Delete" --> S["Tombstone + audit"]
    E -- "No Delete" --> N["Tidak ada route/tombol delete"]
```

Hard delete, soft delete, cancel, void, dan no-delete adalah kontrak berbeda dan tidak boleh dianggap sinonim.

## 11. Application Responsibility

```mermaid
flowchart TD
    A["REQUEST"] --> R["ROUTE / FILTER"]
    R --> S["SERVICE"]
    S --> M["MODEL / QUERY"]
    M --> DB["DATABASE"]

    DB --> M
    M --> S
    S --> C["CONTROLLER"]
    C --> V["VIEW / JSON / FILE"]
    V --> JS["JAVASCRIPT / UI"]

    R -.-> R1["Entry gate"]
    S -.-> S1["Authoritative authorization + business"]
    M -.-> M1["Persistence / query"]
    C -.-> C1["HTTP orchestration"]
    JS -.-> JS1["UX only"]
```

Security/business validation tidak boleh hanya hidup di View/JavaScript.

## 12. Presentation UI / Output Channel

Global UI:

```text
Name-first identity
SearchableSelect untuk entity besar
filter periodik default Tahun aktif
filter padat desktop boleh 2+ baris
loading / empty / filtered-empty / error
busy guard
project confirmation untuk destructive action
responsive desktop/mobile
no unwanted horizontal body overflow
safe-area / touch target
```

Satu business rule harus konsisten pada seluruh output:

```mermaid
flowchart LR
    A["BUSINESS CONTRACT"] --> B["Listing"]
    A --> C["Detail"]
    A --> D["Dashboard"]
    A --> E["Export"]
    A --> F["API / JSON"]
    A --> G["Public Stats / Widget"]
    B --> H["Consistency Check"]
    C --> H
    D --> H
    E --> H
    F --> H
    G --> H
```

Data yang dilarang untuk actor tidak boleh disembunyikan hanya di UI sementara masih dikirim melalui JSON/export/API lain.

## 13. Cross-role Regression

Seluruh role resmi wajib masuk regression matrix, termasuk expected result `DENY`.

```mermaid
flowchart TD
    A["FITUR SIAP UAT"] --> B["Admin"]
    A --> C["Operator"]
    A --> D["Pimpinan"]
    A --> E["BK"]
    A --> F["Guru"]
    A --> G["Guru + Wali"]
    A --> H["Siswa"]
    A --> I["Kesehatan"]
    A --> J["PTSP"]

    B --> K["Access / Capability / Scope / Period / Direct URL / Output Exposure"]
    C --> K
    D --> K
    E --> K
    F --> K
    G --> K
    H --> K
    I --> K
    J --> K
```

Public surface ditest terpisah dari authenticated role matrix.

## 14. Feature Development Gate

```mermaid
flowchart TD
    A["KEPUTUSAN FITUR"] --> B["Update SSOT"]
    B --> C["Schema / SQL bila perlu"]
    C --> D["Backend"]
    D --> E["UI / UX"]
    E --> F["Source Review"]
    F --> G["Static Gate"]
    G --> H["Local Runtime UAT"]
    H --> I["Cross-role Regression"]
    I --> J["Period / Historical Regression"]
    J --> K["Output/API/Export Regression"]
    K --> L["Responsive / Mobile"]
    L --> M{"LOCAL PASS?"}

    M -- "NO" --> N["FIX"]
    N --> B

    M -- "YES" --> O["Final Docs Sync"]
    O --> P["Audit Production"]
    P --> Q["Prepare Hosting Delta"]
    Q --> R["EXPLICIT DEPLOY APPROVAL"]
    R --> S["Deploy"]
    S --> T["Hosting Smoke UAT"]
    T --> U["EXPLICIT READY APPROVAL"]
    U --> V["PR READY"]
    V --> W["EXPLICIT MERGE APPROVAL"]
    W --> X["MERGE"]
```

Tidak boleh:

```text
local PASS -> otomatis deploy
hosting PASS -> otomatis Ready
Ready -> otomatis merge
```

## 15. Checklist Fitur Baru

```text
[ ] Nama fitur / domain
[ ] Tujuan bisnis
[ ] Parent / child / master / reference
[ ] Access Boundary
[ ] Capability matrix
[ ] Scope per capability
[ ] Period Context
[ ] Target Validation
[ ] Business Invariant
[ ] Delete/cancel contract
[ ] Persistence / transaction / FK / actor audit
[ ] Service boundary
[ ] Route / permission
[ ] Public access bila ada
[ ] Export / API / statistics implications
[ ] Presentation UI
[ ] Logging / observability
[ ] Cross-role regression semua role resmi
[ ] Public-surface regression bila ada
[ ] Historical/period regression
[ ] Mobile/responsive regression
[ ] Production/deployment impact
```

Jika satu item belum jelas dan mempengaruhi data/security/business rule, jangan menebak. Kunci keputusan di SSOT atau tandai `OPEN`.

## 16. Aturan Membaca Docs

```text
1. docs/00_POLA_PENGERJAAN___SisisFour.md
2. docs/00A_GLOBAL_STANDARD_SISFOUR.md
3. dokumen domain terkait
4. docs/03_AUTH_RBAC_MENU bila menyentuh akses/scope
5. docs/11/13/14 bila menyentuh UI/mobile
6. docs/15_TESTING_POLISH bila masuk gate
7. source + schema aktual
```

Keputusan user terbaru yang eksplisit harus disinkronkan ke SSOT; jangan membiarkan keputusan penting hanya berada di chat.

## 17. Quick Reference

```mermaid
flowchart LR
    A["Menu/Fitur"]
    --> B["Use Case"]
    --> C["Domain"]
    --> D["Access Boundary"]
    --> E["Capability"]
    --> F["Scope"]
    --> G["Period"]
    --> H["Target Validation"]
    --> I["Business Invariant"]
    --> J["Persistence"]
    --> K["Service Boundary"]
    --> L["UI"]
    --> M["Output/API"]
    --> N["Audit"]
    --> O["Testing"]
    --> P["Docs Sync"]
    --> Q["Deployment Gate"]
```

Dokumen ini diperbarui jika pola global, role registry, public-surface standard, atau baseline Access Boundary lintas-domain berubah. Detail workflow tetap berada di dokumen domain agar SSOT tidak drift.