# Global Standard SisisFour

**Status:** Canonical / Global SSOT  
**Tanggal Acuan:** 17 September 2026  
**Kedudukan:** companion wajib `00_POLA_PENGERJAAN___SisisFour.md`; dibaca sebelum dokumen domain/fitur.

> Dokumen ini menetapkan **cara berpikir dan mapping global** untuk semua fitur SisisFour. Ia tidak menggantikan business rule domain. Dokumen domain menjelaskan *apa* aturannya; dokumen ini memastikan aturan tersebut diterapkan konsisten dari authorization sampai UI, persistence, export, audit, testing, dan deployment.

## 1. Prinsip Utama

Urutan keputusan fitur **tidak boleh dimulai dari UI, tombol, tabel, query, atau database**.

Canonical order:

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
```

Global security stance:

```text
DEFAULT DENY
```

Role/context yang tidak dinyatakan berada di Access Boundary suatu domain **tidak mendapat akses** sampai SSOT memutuskan sebaliknya.

## 2. Master Mapping Global

```mermaid
flowchart TD
    A["MENU / FITUR"] --> B["USE CASE / TUJUAN BISNIS"]
    B --> C["SSOT / BUSINESS CONTRACT"]

    C --> C1["Tujuan fitur"]
    C --> C2["Aktor yang terlibat"]
    C --> C3["Data yang dikelola"]
    C --> C4["Kondisi yang dilarang"]

    C1 --> D["DOMAIN & ENTITAS"]
    C2 --> D
    C3 --> D
    C4 --> D

    D --> D1["Parent / Main Record"]
    D --> D2["Child / History / Detail"]
    D --> D3["Master / Reference"]
    D --> D4["Ownership & Relasi"]

    D1 --> E["ACCESS BOUNDARY"]
    D2 --> E
    D3 --> E
    D4 --> E

    E --> E1{"Actor boleh masuk domain fitur?"}
    E1 -- "TIDAK" --> DENY["DENY TOTAL ACCESS"]
    DENY --> DENY1["Tidak ada menu"]
    DENY --> DENY2["Direct URL ditolak"]
    DENY --> DENY3["Service ditolak"]
    DENY --> DENY4["Data tidak dibentuk / dikirim ke client"]

    E1 -- "YA" --> F["CAPABILITY"]
    F --> F1["View"]
    F --> F2["Create"]
    F --> F3["Update"]
    F --> F4["Delete / Cancel / Void"]
    F --> F5["Export"]
    F --> F6["Settings"]
    F --> F7["Approve / Review / Verify"]
    F --> F8["Capability domain lain"]

    F1 --> G["SCOPE DATA"]
    F2 --> G
    F3 --> G
    F4 --> G
    F5 --> G
    F6 --> G
    F7 --> G
    F8 --> G

    G --> G1["SEMUA"]
    G --> G2["KELAS_DIAMPU"]
    G --> G3["KELAS_TERJADWAL"]
    G --> G4["DIRI_SENDIRI"]
    G --> G5["UNIT / DOMAIN KHUSUS"]
    G --> G6["TIDAK_ADA"]
    G --> G7["BELUM DIKUNCI"]

    G1 --> H["PERIOD CONTEXT"]
    G2 --> H
    G3 --> H
    G4 --> H
    G5 --> H
    G6 --> H
    G7 --> H

    H --> H1["Read / History → periode filter"]
    H --> H2["Create Parent → periode aktif"]
    H --> H3["Update Existing → periode record"]
    H --> H4["Create Child / Follow-up → periode parent"]
    H --> H5["Export → periode filter / record"]
    H --> H6["Dashboard → sesuai kontrak domain"]
    H --> H7["Non-periodik → jangan paksa Tahun Ajaran"]

    H1 --> I["TARGET VALIDATION"]
    H2 --> I
    H3 --> I
    H4 --> I
    H5 --> I
    H6 --> I
    H7 --> I

    I --> I1["Target record ada?"]
    I --> I2["Actor berada dalam scope?"]
    I --> I3["Siswa / Guru / Kelas valid?"]
    I --> I4["Parent-child benar?"]
    I --> I5["Target sesuai periode?"]
    I --> I6["Aktif / historis sesuai workflow?"]

    I1 --> J["BUSINESS INVARIANT"]
    I2 --> J
    I3 --> J
    I4 --> J
    I5 --> J
    I6 --> J

    J --> J1["Status transition valid"]
    J --> J2["Tanggal & urutan waktu valid"]
    J --> J3["Field wajib sesuai kondisi"]
    J --> J4["Histori tidak tertimpa"]
    J --> J5["Data historis tetap terbaca"]
    J --> J6["No hidden side-effects"]
    J --> J7["Privasi domain terjaga"]

    J1 --> K["PERSISTENCE"]
    J2 --> K
    J3 --> K
    J4 --> K
    J5 --> K
    J6 --> K
    J7 --> K

    K --> K1["Transaction"]
    K --> K2["Foreign Key"]
    K --> K3["Index"]
    K --> K4["created_by / updated_by"]
    K --> K5["created_at / updated_at"]
    K --> K6["Snapshot period / ownership bila perlu"]
    K --> K7["Soft Delete / Void bila domain perlu"]
    K --> K8["Rollback / compatibility bila perlu"]

    K1 --> L["SERVICE / APPLICATION BOUNDARY"]
    K2 --> L
    K3 --> L
    K4 --> L
    K5 --> L
    K6 --> L
    K7 --> L
    K8 --> L

    L --> L1["Service = authoritative business + security boundary"]
    L --> L2["Controller = HTTP orchestration tipis"]
    L --> L3["Route / Filter = entry permission gate"]
    L --> L4["Model = persistence / query"]
    L --> L5["View / JS bukan security boundary"]

    L1 --> M["PRESENTATION UI"]
    L2 --> M
    L3 --> M
    L4 --> M
    L5 --> M

    M --> M1["Name-first identity"]
    M --> M2["Filter konsisten"]
    M --> M3["Form konsisten"]
    M --> M4["SearchableSelect untuk entity besar"]
    M --> M5["Loading / Empty / Error"]
    M --> M6["Busy guard / anti double submit"]
    M --> M7["Responsive desktop + mobile"]
    M --> M8["No unwanted horizontal overflow"]
    M --> M9["Action hanya tampil jika capability tersedia"]

    M1 --> N["OUTPUT CHANNEL"]
    M2 --> N
    M3 --> N
    M4 --> N
    M5 --> N
    M6 --> N
    M7 --> N
    M8 --> N
    M9 --> N

    N --> N1["Listing"]
    N --> N2["Detail"]
    N --> N3["Dashboard / Widget"]
    N --> N4["Export"]
    N --> N5["API / JSON"]
    N --> N6["Notification / Badge"]

    N1 --> O["AUDIT & OBSERVABILITY"]
    N2 --> O
    N3 --> O
    N4 --> O
    N5 --> O
    N6 --> O

    O --> O1["Log Activity"]
    O --> O2["Actor Tracking"]
    O --> O3["Error Trace"]
    O --> O4["Change History"]
    O --> O5["Sensitive data tidak masuk log"]

    O1 --> P["TESTING & REGRESSION GATE"]
    O2 --> P
    O3 --> P
    O4 --> P
    O5 --> P

    P --> P1["Static / Syntax"]
    P --> P2["Route / Permission"]
    P --> P3["Role Matrix"]
    P --> P4["Scope Matrix"]
    P --> P5["Period / Historical"]
    P --> P6["Mutation Workflow"]
    P --> P7["Export / Dashboard"]
    P --> P8["Responsive / Mobile"]
    P --> P9["Local Runtime UAT"]

    P1 --> Q{"Semua gate PASS?"}
    P2 --> Q
    P3 --> Q
    P4 --> Q
    P5 --> Q
    P6 --> Q
    P7 --> Q
    P8 --> Q
    P9 --> Q

    Q -- "TIDAK" --> R["KEMBALI KE LAYER YANG SALAH"]
    R --> C

    Q -- "YA" --> S["DOCS / SSOT FINAL SYNC"]
    S --> T["HOSTING / PRODUCTION GATE"]
    T --> T1["Audit schema production"]
    T --> T2["SQL delta bila ada"]
    T --> T3["Deploy hanya dengan approval"]
    T --> T4["Production smoke test"]

    T1 --> U{"Production PASS?"}
    T2 --> U
    T3 --> U
    T4 --> U

    U -- "TIDAK" --> R
    U -- "YA" --> V["FEATURE CLOSED / NEXT PHASE"]
```

## 3. Authorization Standard

Access Boundary, Capability, Scope, Period, Target, dan Business Rule adalah layer berbeda dan tidak boleh dicampur.

```mermaid
flowchart TD
    A["USER REQUEST"] --> B{"Authenticated?"}
    B -- "NO" --> X1["401 / LOGIN"]
    B -- "YES" --> C["EFFECTIVE ROLE"]

    C --> D{"Role/context termasuk ACCESS BOUNDARY fitur?"}
    D -- "NO" --> X2["403 DENY TOTAL ACCESS"]
    X2 --> X21["Tidak ada menu"]
    X2 --> X22["Direct URL ditolak"]
    X2 --> X23["Service tidak membentuk data"]

    D -- "YES" --> E["PERMISSION / CAPABILITY"]
    E --> F{"Capability action tersedia?"}
    F -- "NO" --> X3["403 ACTION DENIED"]
    F -- "YES" --> G["RESOLVE DATA SCOPE"]

    G --> H{"Target berada dalam scope?"}
    H -- "NO" --> X4["403 TARGET DENIED"]
    H -- "YES" --> I["RESOLVE PERIOD CONTEXT"]

    I --> J{"Target valid pada period?"}
    J -- "NO" --> X5["INVALID PERIOD / TARGET"]
    J -- "YES" --> K["BUSINESS VALIDATION"]

    K --> L{"Business invariant valid?"}
    L -- "NO" --> X6["422 BUSINESS VALIDATION ERROR"]
    L -- "YES" --> M["EXECUTE ACTION"]

    M --> N["PERSIST"]
    N --> O["AUDIT LOG"]
    O --> P["RESPONSE"]
```

### Canonical wording

Jika actor tidak berada dalam Access Boundary, tulis:

```text
Siswa tidak memiliki akses ke fitur X.
```

Bukan:

```text
Siswa tidak boleh mengedit fitur X.
```

Capability matrix hanya memuat actor yang **sudah lolos Access Boundary**.

### Makna `Full Access`

```text
Full Access = seluruh capability operasional yang memang didefinisikan oleh domain.
```

`Full Access` **tidak otomatis** berarti:

```text
hard delete
cancel / void
settings
approval khusus
credential/security mutation
bypass scope
bypass business invariant
```

Capability dengan risiko tinggi harus ditetapkan eksplisit oleh kontrak domain.

### Makna `ReadOnly`

```text
ReadOnly = view/list/detail/search/filter pada scope yang sah.
```

ReadOnly tidak memberi mutation. `Export` juga **tidak otomatis** dianggap ReadOnly; export harus diputuskan sebagai capability tersendiri bila dibutuhkan.

## 4. Canonical Role Registry

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

`Wali Kelas` adalah **context Guru**, bukan role tersendiri.

Effective role tetap mengikuti pola:

```text
primary role + secondary roles
→ permission
→ domain access boundary
→ capability
→ scope
```

Role baru tidak otomatis mewarisi domain role lain.

## 5. Domain Access Baseline — UKS / Kesehatan

Menu `UKS` mempunyai target fitur:

```text
Data CKG  -> Data Kesehatan Siswa
Data UKS  -> Catatan Harian UKS
```

Mapping yang sudah diputuskan:

```mermaid
flowchart TD
    A["MENU UKS"] --> B{"Actor / Context"}

    B -- "Kesehatan" --> C["FULL ACCESS"]
    B -- "Admin" --> C
    B -- "Operator" --> C

    B -- "Pimpinan" --> D["READONLY"]
    D --> D1["Scope: BELUM DIKUNCI"]

    B -- "Guru + Wali Kelas" --> E["READONLY"]
    E --> E1["Scope: KELAS_DIAMPU / kelas wali"]

    B -- "Siswa" --> F["READONLY"]
    F --> F1["Scope: DIRI_SENDIRI"]

    B -- "Guru non-Wali / BK / PTSP / role lain" --> X["DEFAULT DENY"]

    C --> G["Capability operasional domain UKS"]
    D1 --> G
    E1 --> G
    F1 --> G

    G --> H["Target Validation"]
    H --> I["Business Invariant"]
    I --> J["Persistence"]
    J --> K["Presentation UI"]
```

Canonical notes:

```text
Kesehatan/Admin/Operator = Full Access domain UKS.
Pimpinan                 = ReadOnly; scope belum dinyatakan eksplisit.
Guru + Wali              = ReadOnly hanya kelas wali/yang diampu sesuai kontrak domain.
Siswa                     = ReadOnly data dirinya sendiri.
Guru tanpa context Wali   = tidak otomatis punya akses.
BK/PTSP/role lain         = default deny sampai SSOT mengubahnya.
```

Sebelum implementasi UKS, domain document harus mengunci minimal:

```text
scope Pimpinan
period context Data CKG
period context Catatan Harian UKS
capability export
capability destructive/correction
privacy/medical-data exposure
actor identity untuk Role Kesehatan
```

## 6. Domain Access Baseline — PTSP

Menu `PTSP` mempunyai target fitur:

```text
Layanan PTSP      -> Form Pendaftaran Layanan PTSP
Polling Kepuasan  -> Form Polling Kepuasan Layanan Madrasah/PTSP
Pengaduan         -> Form Pengaduan intern maupun ekstern
```

Mapping yang sudah diputuskan:

```mermaid
flowchart TD
    A["MENU PTSP"] --> B{"Actor / Context"}

    B -- "PTSP" --> C["FULL ACCESS"]
    B -- "Admin" --> C
    B -- "Operator" --> C

    B -- "Pimpinan" --> D["READONLY"]
    D --> D1["Scope: BELUM DIKUNCI"]

    B -- "Kesehatan / BK / Guru / Wali / Siswa / role lain" --> X["DEFAULT DENY"]

    C --> E["Capability operasional domain PTSP"]
    D1 --> E

    E --> F["Target Validation"]
    F --> G["Business Invariant"]
    G --> H["Persistence"]
    H --> I["Presentation UI"]

    J["Pengaduan EKSTERN"] --> K{"Public / anonymous submission sudah diputuskan?"}
    K -- "BELUM" --> L["JANGAN BUAT PUBLIC ROUTE"]
    K -- "SUDAH" --> M["Definisikan auth/rate-limit/privacy/moderation contract"]
```

Canonical notes:

```text
PTSP/Admin/Operator = Full Access domain PTSP.
Pimpinan            = ReadOnly; scope belum dinyatakan eksplisit.
Role lain           = default deny sampai SSOT mengubahnya.
```

Istilah `Pengaduan ekstern` **belum sama dengan keputusan public/anonymous access**. Sebelum implementasi jalur eksternal, wajib diputuskan:

```text
siapa yang boleh submit
apakah login wajib
anonymous vs identified submitter
rate limit / anti-spam
lampiran bila ada
privacy data pelapor
status/tindak lanjut pengaduan
siapa yang boleh melihat identitas pelapor
retention/audit
```

Hal yang sama berlaku bila `Polling Kepuasan` kelak ingin dibuka untuk publik: public access harus menjadi keputusan eksplisit, bukan inferensi dari nama fitur.

## 7. Period Context Standard

Canonical untuk domain periodik/Tahun Ajaran:

```mermaid
flowchart TD
    A["ACTION PERIODIK"] --> B{"Jenis action?"}

    B -- "Read / History" --> C["Gunakan Tahun Ajaran dari filter"]
    B -- "Export" --> C
    B -- "Dashboard Historis" --> C

    B -- "Create Parent Baru" --> D["Gunakan Tahun Ajaran Aktif"]
    B -- "Update Existing Record" --> E["Pertahankan Tahun Ajaran milik record"]
    B -- "Create Child / Follow-up" --> F["Ikuti Tahun Ajaran parent"]

    C --> G["Resolve scope pada periode tersebut"]
    D --> H["Validasi target pada periode aktif"]
    E --> I["Validasi target terhadap record"]
    F --> J["Validasi child terhadap parent"]

    G --> K["QUERY / ACTION"]
    H --> K
    I --> K
    J --> K
```

Artinya:

```text
Read/History            -> period filter
Create Parent           -> period aktif
Update Existing         -> period record
Create Child/Follow-up  -> period parent
Export                  -> period yang sedang dibaca
```

Filter histori **tidak otomatis** menjadi period tempat record parent baru dibuat.

Tidak semua domain harus periodik. PTSP, misalnya, tidak boleh diberi Tahun Ajaran hanya karena fitur lain memilikinya. Period context harus berasal dari domain contract.

## 8. Parent / Child / History Standard

```mermaid
flowchart TD
    A["PARENT RECORD"] --> B["Identitas / konteks utama"]
    A --> C["Status ringkas / latest state"]
    A --> D["CHILD / HISTORY 1:N"]

    D --> D1["History #1"]
    D --> D2["History #2"]
    D --> D3["History #N"]

    D1 --> E["Child tidak menimpa child lain"]
    D2 --> E
    D3 --> E

    E --> F["Parent boleh menyimpan latest state jika domain perlu"]
    F --> G["Source of history tetap child records"]

    G --> H{"Parent dibatalkan / soft delete?"}
    H -- "NO" --> I["Normal workflow"]
    H -- "YES" --> J["Child history tetap utuh kecuali kontrak domain eksplisit berbeda"]
    J --> K["Audit actor + waktu + alasan"]
```

Hard delete, cascade delete, soft delete, cancel, dan void **bukan sinonim**. Pilih hanya setelah business contract eksplisit.

## 9. Application Responsibility Standard

```mermaid
flowchart TD
    A["REQUEST"] --> R["ROUTE / FILTER"]
    R --> S["SERVICE"]
    S --> M["MODEL / QUERY"]
    M --> DB["DATABASE"]

    DB --> M
    M --> S
    S --> C["CONTROLLER"]
    C --> V["VIEW / JSON"]
    V --> JS["JAVASCRIPT / UI"]

    R -.-> R1["Entry permission gate"]
    S -.-> S1["Authoritative security + business logic"]
    M -.-> M1["Persistence / query"]
    C -.-> C1["HTTP orchestration"]
    V -.-> V1["Presentation"]
    JS -.-> JS1["UX only — bukan security boundary"]
```

Responsibility:

```text
Route / Filter = pintu pertama
Service        = keputusan final authorization + business rule
Model          = persistence / query
Controller     = orchestration request / response
View / JS      = presentation / UX
```

Security/business validation tidak boleh hanya hidup di View atau JavaScript.

## 10. UI / UX Global Standard

```mermaid
flowchart TD
    A["DATA + CAPABILITY DARI SERVER"] --> B["RENDER UI"]

    B --> C["IDENTITY"]
    C --> C1["Nama = primary"]
    C --> C2["Identifier = secondary"]

    B --> D["FILTER"]
    D --> D1["Tahun Ajaran untuk tabel periodik"]
    D --> D2["Default = Tahun Ajaran aktif"]
    D --> D3["Reset = kembali default"]
    D --> D4["Filter padat desktop = 2+ baris bila perlu"]
    D --> D5["Jangan paksa field terlalu sempit"]

    B --> E["FORM"]
    E --> E1["Logical grouping"]
    E --> E2["Server validation tetap wajib"]
    E --> E3["SearchableSelect untuk entity besar"]
    E --> E4["Pertahankan input saat error bila aman"]

    B --> F["TABLE / LIST"]
    F --> F1["Desktop table bila sesuai"]
    F --> F2["Mobile card/list bila table tidak cocok"]
    F --> F3["No unwanted horizontal body overflow"]
    F --> F4["Pagination reusable"]

    B --> G["ACTION"]
    G --> G1["Tampil hanya jika capability tersedia"]
    G --> G2["Busy guard"]
    G --> G3["Destructive action = project confirmation"]
    G --> G4["Tidak memakai native confirm()"]

    B --> H["STATE"]
    H --> H1["Loading"]
    H --> H2["Empty"]
    H --> H3["Filtered Empty"]
    H --> H4["Error"]
    H --> H5["Forbidden / unavailable"]
    H --> H6["Session expired"]

    B --> I["RESPONSIVE"]
    I --> I1["Desktop nyaman"]
    I --> I2["Mobile portrait nyaman"]
    I --> I3["Touch target cukup"]
    I --> I4["Modal scroll vertikal"]
    I --> I5["Safe-area / WebView aware"]
```

## 11. Output Channel Consistency

Satu business rule harus konsisten pada seluruh output channel yang relevan.

```mermaid
flowchart LR
    A["BUSINESS CONTRACT"] --> B["Listing"]
    A --> C["Detail"]
    A --> D["Dashboard / Widget"]
    A --> E["Export"]
    A --> F["API / JSON"]
    A --> G["Notification / Badge"]

    B --> H["Consistency Check"]
    C --> H
    D --> H
    E --> H
    F --> H
    G --> H
```

Data yang dilarang untuk sebuah role tidak boleh disembunyikan hanya di UI sementara masih dikirim melalui JSON/export/dashboard.

## 12. Cross-role Regression Standard

Setiap fitur diuji terhadap **seluruh role resmi**, lalu context Wali diuji sebagai cabang Guru bila domain terkait kelas.

```mermaid
flowchart TD
    A["FITUR SIAP UAT"] --> B["ACCESS MATRIX"]

    B --> B1["Admin"]
    B --> B2["Operator"]
    B --> B3["Pimpinan"]
    B --> B4["BK"]
    B --> B5["Guru"]
    B --> B6["Guru + Wali"]
    B --> B7["Siswa"]
    B --> B8["Kesehatan"]
    B --> B9["PTSP"]

    B1 --> C["Cek Access Boundary"]
    B2 --> C
    B3 --> C
    B4 --> C
    B5 --> C
    B6 --> C
    B7 --> C
    B8 --> C
    B9 --> C

    C --> D["Cek Capability"]
    D --> E["Cek Scope"]
    E --> F["Cek Period"]
    F --> G["Cek Direct URL"]
    G --> H["Cek UI Visibility"]
    H --> I["Cek API / JSON / Export Exposure"]

    I --> J{"Role menerima data yang tidak berhak?"}
    J -- "YA" --> X["FAIL — SECURITY REGRESSION"]
    J -- "TIDAK" --> K["PASS"]
```

Wali Kelas adalah **context Guru**, bukan role baru. Role Kesehatan dan PTSP adalah role resmi dan harus selalu masuk regression matrix, termasuk saat expected result-nya `DENY`.

## 13. Feature Development Gate

```mermaid
flowchart TD
    A["KEPUTUSAN FITUR"] --> B["Update SSOT / Docs"]
    B --> C["Schema / SQL bila perlu"]
    C --> D["Backend"]
    D --> E["UI / UX"]
    E --> F["Source Review"]

    F --> G["Static Gate"]
    G --> H["Local Runtime UAT"]
    H --> I["Cross-role Regression"]
    I --> J["Period / Historical Regression"]
    J --> K["Responsive / Mobile UAT"]
    K --> L["Export / Dashboard Regression"]

    L --> M{"LOCAL PASS?"}
    M -- "NO" --> N["FIX"]
    N --> B

    M -- "YES" --> O["Final Docs Sync"]
    O --> P["Audit Production DB / Environment"]
    P --> Q["Prepare Hosting Delta"]
    Q --> R["EXPLICIT DEPLOY APPROVAL"]

    R --> S["Deploy"]
    S --> T["Hosting Smoke UAT"]
    T --> U{"HOSTING PASS?"}

    U -- "NO" --> N
    U -- "YES" --> V["EXPLICIT READY APPROVAL"]
    V --> W["PR READY"]
    W --> X["EXPLICIT MERGE APPROVAL"]
    X --> Y["MERGE"]
```

Tidak boleh:

```text
local PASS -> otomatis deploy
hosting PASS -> otomatis PR Ready
Ready -> otomatis merge
```

Setiap gate tetap memerlukan approval eksplisit sesuai `00_POLA_PENGERJAAN___SisisFour.md`.

## 14. Checklist Mapping Fitur Baru / Perubahan Fitur

Sebelum coding, jawaban berikut harus jelas:

```text
[ ] Nama fitur / domain
[ ] Tujuan bisnis
[ ] Parent / child / master / reference
[ ] Role/context yang masuk Access Boundary
[ ] Role/context yang default-deny
[ ] Capability matrix
[ ] Makna Full Access bila istilah itu dipakai
[ ] Makna ReadOnly bila istilah itu dipakai
[ ] Scope per capability
[ ] Scope Pimpinan bila diberi ReadOnly
[ ] Period Context / non-periodik
[ ] Target Validation
[ ] Business Invariant
[ ] Persistence / transaction / FK / audit actor
[ ] Service boundary
[ ] Route/permission
[ ] Presentation UI
[ ] Listing/detail/dashboard/export/API implications
[ ] Logging / observability
[ ] Public/external access bila ada
[ ] Privacy / sensitive-data policy bila ada
[ ] Local test matrix
[ ] Cross-role regression semua role resmi
[ ] Historical/period regression bila relevan
[ ] Mobile/responsive regression
[ ] Production/deployment impact
```

Jika satu item belum jelas dan berpengaruh pada data/security/business rule, **jangan menebak**. Kunci keputusan terlebih dahulu di SSOT.

## 15. Aturan Membaca Docs

Urutan kerja minimal setiap memulai/melanjutkan fitur:

```text
1. docs/00_POLA_PENGERJAAN___SisisFour.md
2. docs/00A_GLOBAL_STANDARD_SISFOUR.md
3. dokumen domain yang terkait
4. docs/03_AUTH_RBAC_MENU bila menyentuh akses/scope
5. docs/11/13/14 bila menyentuh UI/mobile
6. docs/15_TESTING_POLISH bila masuk gate
7. source + schema aktual
```

Jika ada konflik:

```text
Keputusan user terbaru yang eksplisit
→ sinkronkan ke SSOT
→ domain contract
→ Global Standard
→ implementation
```

Jangan membiarkan keputusan baru hanya berada di chat.

Role/domain baru wajib disinkronkan ke dokumen domain/RBAC sebelum implementasi source. `00/00A` menetapkan arah global; ia tidak menggantikan detail schema, permission key, menu row, route, ataupun workflow domain.

## 16. Quick Reference

Gunakan diagram ini untuk review cepat sebelum coding:

```mermaid
flowchart LR
    A["Menu / Fitur"]
    --> B["Use Case"]
    --> C["SSOT / Domain"]
    --> D["Access Boundary"]
    --> E["Capability"]
    --> F["Scope"]
    --> G["Period Context"]
    --> H["Target Validation"]
    --> I["Business Invariant"]
    --> J["Persistence"]
    --> K["Service Boundary"]
    --> L["Presentation UI"]
    --> M["Output Channel"]
    --> N["Audit"]
    --> O["Testing / Regression"]
    --> P["Docs Sync"]
    --> Q["Deployment Gate"]
```

Dokumen ini harus diperbarui hanya jika **pola global aplikasi, role registry, atau baseline Access Boundary lintas-domain** berubah. Business rule rinci tetap ditulis di dokumen domain masing-masing agar SSOT tidak duplikatif dan tidak mudah drift.
