# G3.9 — Dashboard Experience V2 — SisisFour

**Status:** Draft SSOT / Audit-backed specification
**Tanggal Acuan:** 3 Oktober 2026
**Baseline Source:** `main @ 95bedb09200ed954c60ea34fa0d8665d471528da`
**Phase:** G3.9 — Role-Aware Dashboard Composition & Visual Action System

> Dokumen ini mendefinisikan perubahan experience Dashboard SisisFour setelah G3.8.
> G3.9 tidak mengubah permission, scope, period, privacy, atau business invariant
> domain secara diam-diam. Perubahan authorization/identity yang diperlukan oleh
> multi-role wajib dinyatakan eksplisit dan diuji terpisah.

---

## 1. Tujuan

G3.9 merapikan Dashboard agar:

1. Dashboard mempunyai hierarchy yang konsisten antara single, double, dan triple role.
2. Primary Role / Role 1 menjadi pemilik isi utama Dashboard.
3. Role tambahan tidak menggandakan seluruh Dashboard.
4. Actionable control terlihat jelas sebagai sesuatu yang dapat ditekan.
5. Metric/agregat tidak terlihat seperti tombol.
6. Guru/Wali mempunyai visual state yang mengikuti state waktu/backend aktual.
7. Mobile/WebView tetap compact, touch-safe, dan tidak menjadi salinan desktop.
8. Existing authorization tetap authoritative di server.

G3.9 adalah perubahan **experience/composition + visual system**, bukan rewrite domain.

---

## 2. Baseline Audit

### 2.1 Role resmi saat ini

Role formal:

```text
admin
operator
pimpinan
bk
kesehatan
ptsp
guru
siswa
```

Wali Kelas bukan role formal. Wali adalah context Guru yang dihitung dari:

```text
users.id_guru
→ mapping_wali_kelas
→ Tahun Ajaran
```

### 2.2 Effective Role saat ini

```text
effective_roles = users.role UNION user_roles.role
```

Permission tetap memakai union effective role.

### 2.3 Dashboard experience saat ini

Source saat baseline memilih satu experience dengan static priority:

```text
admin
> operator
> pimpinan
> bk
> kesehatan
> ptsp
> guru
> siswa
```

Priority tersebut berada pada Dashboard/RoleAwareDashboardService/BaseController
dan juga direfleksikan oleh unit test existing.

Konsekuensi baseline:

```text
['guru', 'bk']             → dashboard BK
['guru', 'bk', 'pimpinan'] → dashboard Pimpinan
['guru', 'bk', 'operator'] → dashboard Operator
```

G3.9 mengganti **experience resolution** ini tanpa mengubah union permission.

### 2.4 Inheritance dashboard saat ini

Dashboard service tumbuh secara bertahap:

```text
DashboardService
  ↓
RoleAwareDashboardService
  ↓
BkWorkflowDashboardService
  ↓
PimpinanDashboardService
  ↓
SiswaDashboardService
  ↓
KesehatanDashboardService
  ↓
PtspDashboardService
```

G3.9 tidak boleh menambah inheritance layer baru hanya untuk mendukung kombinasi role.

### 2.5 Temuan visual baseline

Banyak control Dashboard masih memakai pola:

```text
btn-outline-primary
shadow-none
border
```

Akibatnya action, navigation tile, dan informational container memiliki affordance
yang terlalu mirip.

KPI/card agregat juga terlalu dominan dan boros ruang pada role yang seharusnya
action-first.

---

## 3. Terminologi G3.9

### 3.1 Role 1

**Role 1 = Primary Role**, yaitu role utama yang menjadi pemilik Dashboard.

Target contract G3.9:

```text
users.role → Role 1 / Primary Role
```

Role 1 menentukan:

```text
Metric Summary
EWS / Access
Data / Activity
main dashboard identity
```

### 3.2 Role 2 dan Role 3

Role tambahan berasal dari `user_roles`.

Secara experience:

```text
Role 2 / Role 3
→ menambah capability melalui permission union
→ hanya role tertentu yang boleh menambah Primary Action Surface
→ tidak membawa seluruh dashboard role tersebut
```

Urutan formal Role 2/Role 3 masih **OPEN DECISION** karena schema/service baseline
belum menyimpan ordering tambahan secara eksplisit.

### 3.3 Wali Kelas

Wali tetap:

```text
context Guru
≠ role formal
≠ slot role tambahan
```

Experience Wali dapat menambah contextual action dan contextual data pada Dashboard
Guru sesuai permission dan mapping period-aware.

---

## 4. Empat Building Block Dashboard

Secara internal G3.9 mengenal empat blok:

```text
1. Primary Action Surface
2. Metric Summary
3. EWS / Access / Monitoring
4. Data / Activity
```

Nama tersebut adalah istilah design/engineering.

UI tidak wajib menampilkan heading teknis tersebut.

Contoh heading user-facing:

```text
Hari Ini
Prioritas BK
Layanan UKS
Layanan PTSP
Ringkasan Hari Ini
Kondisi Madrasah
Kelas Wali · 7-A
```

Istilah UI **"Aksi Cepat" tidak menjadi heading generic canonical G3.9**.

---

## 5. Klasifikasi Role Dashboard

### 5.1 Non-Action-Surface Role

Role berikut tidak mempunyai Primary Action Surface pada Home:

| Role | Primary Action Surface | Karakter |
|---|---:|---|
| Admin | Tidak | system/admin overview |
| Operator | Tidak | administrasi operasional luas |
| Pimpinan | Tidak | monitoring/supervisi/decision |

Tombol yang relevan tetap tersedia melalui **EWS / Access / Monitoring** dan menu
permission-aware.

Jika Operator/Pimpinan muncul sebagai Role 2/Role 3, role tersebut juga **tidak**
menambah section Primary Action.

### 5.2 Action-Surface Role

| Role/Context | Primary Action Surface |
|---|---:|
| BK | Ya |
| Kesehatan | Ya |
| PTSP | Ya |
| Guru | Ya |
| Guru + Wali | Ya + contextual Wali action |
| Siswa | Self-service pattern, bukan operational action surface |

Siswa tetap aggregate/self-service-first dan tidak diperlakukan sebagai multi-role
operasional.

---

## 6. Formula Dashboard

### 6.1 Single Role — Admin / Operator / Pimpinan

```text
HEADER

METRIC SUMMARY — ROLE 1

EWS / ACCESS / MONITORING — ROLE 1

DATA / ACTIVITY — ROLE 1
```

### 6.2 Single Role — BK / Kesehatan / PTSP / Guru

```text
HEADER

PRIMARY ACTION — ROLE 1

METRIC SUMMARY — ROLE 1

EWS / ACCESS — ROLE 1

DATA / ACTIVITY — ROLE 1
```

### 6.3 Guru + Wali

```text
HEADER

PRIMARY ACTION GURU
+ contextual Wali action bila relevan

METRIC SUMMARY GURU

KELAS WALI / EWS / ACCESS

DATA / ACTIVITY GURU + CONTEXT WALI
```

Metric Summary tetap metric Guru. Wali tidak membuat blok agregat kedua.

### 6.4 Siswa

```text
HEADER / STATUS HARI INI

METRIC SUMMARY PERSONAL

SELF-SERVICE ACCESS

DATA PERSONAL
```

### 6.5 Double Role

```text
PRIMARY ACTION ROLE 1       jika eligible
PRIMARY ACTION ROLE 2       jika eligible

METRIC SUMMARY ROLE 1
EWS / ACCESS ROLE 1
DATA / ACTIVITY ROLE 1
```

Role 2 tidak pernah mengganti Metric Summary Role 1.

### 6.6 Triple Role

```text
PRIMARY ACTION ROLE 1       jika eligible
PRIMARY ACTION ROLE 2       jika eligible
PRIMARY ACTION ROLE 3       jika eligible

METRIC SUMMARY ROLE 1
EWS / ACCESS ROLE 1
DATA / ACTIVITY ROLE 1
```

Jumlah role tidak otomatis sama dengan jumlah action section.

Contoh:

```text
BK + Operator + Kesehatan
```

menjadi:

```text
Primary Action BK
Primary Action Kesehatan
# Operator tidak menghasilkan action section

Metric Summary BK
EWS / Access BK
Data BK
```

---

## 7. Metric Summary — Pengganti Card Agregat Lama

### 7.1 Prinsip

Metric Summary:

```text
information-first
bukan action
Role 1 only
compact
scannable
mobile-safe
```

Metric Tile tidak boleh tampak seperti tombol.

### 7.2 Hierarchy Metric Tile

Canonical:

```text
icon
angka / value utama
label
optional metadata kecil
```

Angka/value menjadi hierarchy utama, bukan label panjang.

### 7.3 Visual Metric

Metric Tile memakai:

```text
flat soft tonal background
soft border
icon kecil / icon bubble
consistent radius
```

Metric/non-interactive surface **tidak memakai gradient**. Gradient Dashboard G3.9
dicadangkan untuk enabled actionable control.

Metric Tile tidak memakai:

```text
button pressed state
action hover
chevron action
button-like shadow
cursor/tap affordance palsu
```

### 7.4 Density

**Prominent Metric Summary**:

```text
Admin
Operator
Pimpinan
Siswa
```

Karena agregat merupakan informasi prioritas.

**Compact Metric Summary**:

```text
BK
Kesehatan
PTSP
Guru
Guru + Wali
```

Karena action/workflow harus lebih dominan.

### 7.5 Mobile Grid

Default mobile dapat tetap memakai 2×2 ketika ada empat metric.

Empat metric bukan kewajiban absolut. Jangan membuat metric lemah/palsu hanya demi
mengisi grid.

Desktop boleh 4-column bila space cukup.

---

## 8. Metric Canonical per Role

### 8.1 Admin

Sumber existing dapat mencakup:

```text
Siswa Aktif
Guru
Pegawai
Kelas Aktif
operational summary sesuai permission
```

Final grouping/selection ditentukan saat role migration G3.9 agar tidak membuat
dashboard Admin terlalu padat.

### 8.2 Operator

Sumber existing:

```text
Kelas Belum Presensi
Jadwal Belum Jurnal
EWS Alpha 14 Hari
master summary / operational counters
```

### 8.3 Pimpinan

Canonical:

```text
Kelas Belum Presensi
Jadwal Belum Jurnal
EWS Alpha 14 Hari
Catatan Pelanggaran Bulan Ini
```

### 8.4 BK

Canonical:

```text
Konseling Proses
Pelanggaran Bulan Ini
EWS Alpha 14 Hari
Prestasi Bulan Ini
```

### 8.5 Kesehatan

Canonical:

```text
Kunjungan UKS Hari Ini
Kunjungan UKS Bulan Ini
Rujuk ke Klinik Bulan Ini
Pemeriksaan CKG Bulan Ini
```

### 8.6 PTSP

Canonical:

```text
Layanan Baru
Layanan Diproses
Pengaduan Masuk
Rata-rata Kepuasan
```

### 8.7 Guru / Guru + Wali

Canonical:

```text
Jadwal Hari Ini
Belum Presensi
Belum Jurnal
Selesai
```

### 8.8 Siswa

Canonical:

```text
Hadir
Sakit
Izin
Alpha
```

Metric personal mengikuti data diri sendiri dan Tahun Ajaran aktif.

---

## 9. Primary Action Surface

### 9.1 Prinsip

Primary Action Surface adalah area kerja, bukan kumpulan shortcut dekoratif.

Ia menjawab:

```text
Apa yang perlu saya lakukan sekarang?
```

### 9.2 Heading

Jangan gunakan generic heading `Aksi Cepat` sebagai default.

Contoh:

```text
Guru       → Hari Ini
BK         → Prioritas BK
Kesehatan  → Layanan UKS
PTSP       → Layanan PTSP
Wali       → Kelas Wali · {kelas}
```

Heading boleh dihilangkan bila context komponen sudah cukup jelas.

### 9.3 Existing action catalog sebagai baseline

Guru:

```text
Presensi
Jurnal
Jadwal
Profil
```

BK:

```text
Konseling BK
Catatan Pelanggaran
EWS
Prestasi
```

Kesehatan:

```text
Tambah Data Kunjungan
Data UKS
Data CKG
Import CKG
Master UKS
```

PTSP:

```text
Layanan PTSP
Polling Kepuasan
Pengaduan
Buka Public PTSP
```

Wali contextual access existing dapat mencakup:

```text
Presensi Kelas
Rekap Presensi
Data Siswa
Matrix Presensi
EWS Kelas
Catatan Pelanggaran
Prestasi Siswa
Kartu Pelajar
```

Action tetap permission-aware dan tidak menambah capability.

---

## 10. Global Action Visual System

### 10.1 Affordance

Semua control Dashboard yang benar-benar enabled/clickable wajib mendapat:

```text
functional gradient background
soft functional border
subtle shared action shadow
visible focus state
pressed state
adequate touch target
```

Gradient adalah affordance utama enabled button dan harus terlihat berbeda dari
background aplikasi/card di belakangnya.

Ini berlaku tidak hanya pada Primary Action, tetapi juga:

```text
Access button
Monitoring button
Lihat Semua
Jadwal action
EWS action
Profile
Kartu
Detail
navigation tile
compact actionable control
```

Outline-only bukan pola utama Dashboard V2.

### 10.2 Shared token

Shadow/radius tidak dibuat berbeda per halaman.

Gunakan shared design token/class agar tetap sesuai Sneat global layout standard.

Hard affordance rule:

```text
enabled Dashboard action
→ functional GRADIENT background
→ subtle action shadow

current/primary enabled action
→ stronger functional gradient emphasis
→ stronger shared action shadow

disabled/expired action
→ flat neutral grey
→ no gradient
→ no action shadow

Metric / Status / Badge / Context / Work Surface
→ FLAT background
→ no gradient
→ no action shadow
```

Untuk Dashboard G3.9:

```text
GRADIENT = enabled interactive action
FLAT     = information / status / context
SHADOW   = confirms enabled interactivity
```

Outer Sneat card ambient elevation tetap diperbolehkan dan bukan action shadow.

### 10.3 Functional Color Family

Warna dasar dapat mengikuti fungsi:

```text
Jadwal       → indigo
Presensi     → blue
Jurnal       → violet
Data         → blue/cyan
EWS          → amber
Pelanggaran  → rose
Prestasi     → green
Kartu        → teal
Profil       → neutral/slate
```

Final token harus diuji terhadap contrast dan existing Sneat palette.

State selalu mempunyai prioritas dibanding warna domain.

---

## 11. Canonical Action State

| State | Visual intent |
|---|---|
| Available | functional gradient + subtle shadow |
| Current / Priority | stronger functional gradient + stronger shared shadow |
| Completed | green / success; action shadow hanya bila masih clickable |
| Not Started | neutral/light, non-actionable |
| Late but Actionable | amber |
| Expired / Disabled | grey, shadow removed |
| Error / Destructive | red |
| Not Applicable | muted informational |

**Expired bukan error.**

Merah tidak digunakan hanya karena time-window telah habis.

---

## 12. Guru / Wali State Contract

Backend baseline telah memiliki:

Presensi:

```text
not_applicable
submitted
not_started
available
wali_available
ended
```

Jurnal:

```text
submitted
not_started
available
ended
```

Time window baseline:

```text
now < jam_mulai
→ NOT_STARTED

jam_mulai <= now <= jam_selesai + 15 menit
→ VALID

now > jam_selesai + 15 menit
→ ENDED
```

Presentation G3.9:

```text
available
→ active functional color

submitted
→ completed / green

not_started
→ neutral/light

ended
→ grey / disabled

wali_available
→ contextual actionable state

not_applicable
→ muted informational
```

### 12.1 Wali exception

Guru yang juga Wali dapat mempunyai:

```text
Presensi kelas wali setelah window Guru
→ wali_available
```

Jurnal tidak memperoleh privilege Wali:

```text
Jurnal setelah window
→ ended
```

Business rule tetap berasal dari Service, bukan CSS/JavaScript.

---

## 13. EWS / Access / Monitoring

Bagian ini mengikuti **Role 1**.

Admin/Operator/Pimpinan menggunakan bagian ini sebagai surface untuk tombol yang
tidak memiliki Primary Action Surface.

Contoh Pimpinan:

```text
Rekap Presensi
Laporan Jurnal
EWS
Matrix / Statistik
```

Role tambahan Operator/Pimpinan tidak membuat duplicate Access block pada Home.

Capability role tambahan tetap tersedia melalui menu permission-aware.

---

## 14. Data / Activity

Data/tabel/list utama mengikuti Role 1.

Contoh:

Guru/Wali:

```text
Jadwal Hari Ini
EWS Kelas
Ketidakhadiran Terbaru
Jurnal Terakhir
```

BK:

```text
Follow-up terdekat
Pelanggaran terbaru
EWS
Prestasi terbaru
```

Kesehatan:

```text
Kunjungan terbaru
CKG terbaru
```

PTSP:

```text
Layanan terbaru
Pengaduan terbaru
Ringkasan Kepuasan
```

Pimpinan:

```text
Tren
EWS
Prestasi
Ringkasan Master
```

Dashboard tetap bukan laporan lengkap:

```text
3–5 item penting
+ Lihat Semua
```

---

## 15. Permission, Scope, Period, Privacy

G3.9 tidak mengubah prinsip:

```text
Effective Role
→ Access Boundary
→ Permission / Capability
→ Scope
→ Period
→ Target Validation
→ Business Invariant
```

Dashboard bukan security boundary.

Control tanpa capability:

```text
tidak dirender
```

Jangan mengirim data terlarang lalu sekadar menyembunyikannya di UI.

### 15.1 Konseling

Privacy Konseling existing tetap:

```text
Pimpinan → tidak menerima detail/widget/source
Guru/Wali → tidak menerima detail/widget/quick link
Siswa → tidak menerima detail/widget
```

### 15.2 Period

Dashboard current-state tetap memakai Tahun Ajaran aktif sesuai domain existing.

Historical selector tetap berada pada listing/domain yang memang mendukung history.

---

## 16. Mobile / WebView Contract

Dashboard G3.9 harus tetap mematuhi:

```text
touch target primary 44–48px
compact spacing
no horizontal operational table scroll
adaptive card/list
name-first identity
3–5 recent items
no fake hover dependency
keyboard/focus safe
WebView safe
```

Metric grid mobile default:

```text
2 × 2 bila 4 metric
```

Primary action harus lebih mudah ditemukan daripada utility action pada role
action-first.

---

## 17. Accessibility

Semua interactive element wajib:

```text
visible focus
sufficient contrast
touch target adequate
icon-only memiliki accessible label
disabled state tidak hanya dibedakan dengan warna
```

State sebaiknya tetap mempunyai text/icon cue, misalnya:

```text
Selesai
Belum waktunya
Waktu habis
Isi sebagai Wali
```

---

## 18. Multi-Role Identity — RESOLVED CONTRACT

### 18.1 Audit finding

Master Guru dan Master Pegawai baseline memang sengaja **mutual-exclusive untuk
satu manusia** pada identifier login/personalia.

GuruService menolak NIK/NIP yang sudah dipakai Pegawai, dan PegawaiService menolak
NIK/NIP yang sudah dipakai Guru.

SettingsUserService juga menjaga satu account hanya terhubung ke satu identity:

```text
id_guru OR id_pegawai OR id_siswa
```

Karena itu G3.9 **tidak** memperkenalkan duplicate person record dan tidak
mengizinkan satu `users` row mempunyai `id_guru + id_pegawai`.

### 18.2 Canonical identity model G3.9

Identity master dan operational role dipisahkan:

```text
PERSON MASTER IDENTITY
Guru    → users.id_guru
Pegawai → users.id_pegawai
Siswa   → users.id_siswa

OPERATIONAL ROLE
admin / operator / pimpinan / bk / kesehatan / ptsp / guru / siswa
→ authorization melalui effective role + permission + scope
```

BK/Kesehatan/PTSP adalah operational role. Role tersebut **tidak otomatis berarti
actor harus berasal dari Master Pegawai**.

Untuk actor personel:

```text
staff identity available
= users.id_guru > 0
  OR users.id_pegawai > 0
```

Siswa tetap identity terpisah dan tidak boleh menerima role operasional personel.

### 18.3 Cross-role Guru

Contoh valid secara konsep G3.9:

```text
Primary Guru + Secondary Kesehatan
→ users.id_guru tetap menjadi person identity
→ tidak membuat record Pegawai kedua
→ permission UKS berasal dari effective role Kesehatan
→ audit mutation menggunakan users.id
```

Hal yang sama berlaku pada role PTSP/BK bila kombinasi tersebut diizinkan oleh
whitelist.

### 18.4 Domain ownership

Audit source menunjukkan mutation utama UKS/PTSP/BK menggunakan `users.id`
untuk created_by/updated_by/audit dan authorization menggunakan permission/scope.

Karena itu requirement `id_pegawai` pada dashboard Kesehatan/PTSP baseline
diklasifikasikan sebagai **experience gate yang perlu dinormalisasi**, bukan
ownership data domain.

G3.9 implementation harus:

```text
Kesehatan/PTSP/BK operational gate
→ effective role + permission + valid staff identity

valid staff identity
→ id_guru OR id_pegawai
```

Service tetap boleh membutuhkan identity Guru/Siswa pada fitur yang memang
scope-nya secara business bergantung pada identity tersebut.

### 18.5 Profile

Profile tetap mengikuti master identity yang benar-benar terhubung:

```text
id_guru    → Profile Guru
id_pegawai → Profile Pegawai
id_siswa   → Profile Siswa
```

Operational role tidak boleh mengubah person master secara implisit.

**OD-01 = RESOLVED.**

---

## 19. Role 2 / Role 3 Ordering — RESOLVED CONTRACT

### 19.1 Baseline finding

`user_roles` tidak mempunyai ordering field dan baseline membaca secondary role
secara alfabetis.

G3.9 tidak menjadikan urutan row database sebagai business meaning.

### 19.2 Canonical ordering

Role 2 / Role 3 adalah **composition position**, bukan priority yang perlu disimpan
ke schema.

Urutan role tambahan dihitung deterministik dari whitelist G3.9.

Canonical candidate order untuk role tambahan:

```text
operator
pimpinan
kesehatan
ptsp
```

Role yang tidak diizinkan oleh primary/context dibuang sebelum ordering.

Contoh:

```text
Primary BK
assigned = {ptsp, operator}

Role 2 = Operator
Role 3 = PTSP
```

Karena Operator/Pimpinan tidak menghasilkan Primary Action Surface, urutan visible
action tetap sederhana:

```text
Primary Action BK
Primary Action PTSP
```

Tidak diperlukan kolom `sort_order` pada `user_roles` untuk G3.9.

Jika suatu fase masa depan membutuhkan user-defined role priority, itu menjadi
schema/UX change tersendiri.

**OD-02 = RESOLVED.**

---

## 20. Role Combination Whitelist — RESOLVED CONTRACT

G3.9 tidak menganggap semua kombinasi role valid hanya karena `user_roles`
mendukung banyak row.

### 20.1 Exclusive

```text
Admin
Siswa
```

tidak mempunyai secondary role.

### 20.2 Primary BK

Primary BK boleh mempunyai role tambahan:

```text
Operator
Pimpinan
Kesehatan
PTSP
```

Maksimum **2 secondary role**.

Artinya formal triple-role yang paling realistis berada pada Primary BK:

```text
BK + Role 2 + Role 3
```

Role 2/3 harus berasal dari set yang diizinkan di atas.

### 20.3 Primary Guru

Primary Guru boleh mempunyai maksimum **1 secondary role** dari:

```text
Operator
Pimpinan
Kesehatan
PTSP
```

### 20.4 Guru + Wali context

Wali bukan role formal.

Jika Guru mempunyai context Wali aktif, Wali **tidak menghabiskan slot role**
dan tidak menonaktifkan operational role PTSP. Secondary yang diizinkan:

```text
Operator
Kesehatan
PTSP
```

Pimpinan tetap tidak diizinkan pada context Guru+Wali. Maksimum 1 secondary role.

Secara experience dapat terlihat seperti:

```text
Guru + Wali + Operator
Guru + Wali + Kesehatan
Guru + Wali + PTSP
```

tetapi secara formal tetap:

```text
Guru + 1 secondary role
+ context Wali
```

Ini menjelaskan mengapa "triple" Guru+Wali relatif jarang tanpa menjadikan Wali
sebagai role database.

### 20.5 Primary lain

Primary:

```text
Operator
Pimpinan
Kesehatan
PTSP
```

dapat berdiri sebagai single-role experience, tetapi tidak menjadi primary
multi-role pada contract G3.9.

Jika seseorang mempunyai fungsi gabungan dengan role tersebut, Role 1 harus
mengikuti whitelist Primary BK atau Primary Guru.

### 20.6 Invalid combinations

Termasuk invalid:

```text
Admin + X
Siswa + X
Guru + BK
BK + Guru
Guru+Wali + Pimpinan
secondary lebih dari batas primary
kombinasi lain yang tidak tercantum
```

Settings User pada implementation G3.9 harus menegakkan whitelist server-side.
UI checkbox/filter hanya presentation aid, bukan enforcement final.

**OD-03 = RESOLVED.**

---

## 21. Recommended Architecture

G3.9 sebaiknya menambahkan composition layer.

Target conceptual flow:

```text
Authenticated User
        ↓
Dashboard Context
├─ Primary Role
├─ Secondary Roles
├─ Wali Context
├─ Identity Context
└─ Effective Permissions
        ↓
Dashboard Composition
├─ Action Sections
├─ Metric Summary Role 1
├─ EWS / Access Role 1
└─ Data / Activity Role 1
        ↓
View
```

Secondary role tidak perlu membangun seluruh payload dashboardnya bila hanya
membutuhkan action catalog.

Hal ini mencegah:

```text
build dashboard A
+ build dashboard B
+ build dashboard C
→ lalu sebagian besar dibuang
```

---

## 22. Source Impact Map

Expected source areas yang perlu dianalisis/diubah saat implementation:

```text
app/Controllers/Dashboard.php
app/Controllers/BaseController.php

app/Services/DashboardService.php
app/Services/RoleAwareDashboardService.php
app/Services/BkWorkflowDashboardService.php
app/Services/PimpinanDashboardService.php
app/Services/SiswaDashboardService.php
app/Services/KesehatanDashboardService.php
app/Services/PtspDashboardService.php

app/Services/AuthService.php
app/Services/SettingsUserService.php   # hanya jika role/identity contract diubah
app/Models/SettingsUserModel.php       # hanya jika ordering/assignment diubah

app/Views/dashboard_admin.php
app/Views/dashboard_operator.php
app/Views/dashboard_pimpinan.php
app/Views/dashboard_bk.php
app/Views/dashboard_kesehatan.php
app/Views/dashboard_ptsp.php
app/Views/dashboard_guru.php
app/Views/dashboard_wali.php
app/Views/dashboard_siswa.php

assets/css/sisfour-ui.css
assets/css/sisfour-mobile.css
optional scoped dashboard stylesheet

tests/unit/*
```

Perubahan actual harus dipersempit berdasarkan subphase.

---

## 23. SSOT yang Terdampak

Setelah contract G3.9 final, sinkronisasi minimal:

```text
03_AUTH_RBAC_MENU — SisisFour.md
08_DASHBOARD_SETTINGS_BACKUP — SisisFour.md
11_UI_UX — SisisFour.md
11_UI_UX_ROLE_EXPERIENCE — SisisFour.md
14_SISFOUR_MOBILE_CORDOVA_UI_UX_STANDARD.md
15_TESTING_POLISH — SisisFour.md
```

Aturan lama tidak dihapus tanpa jejak. Tandai sebagai superseded by G3.9 bila
contract berubah.

---

## 24. Test Contract G3.9

Minimal test harus mencakup:

### 24.1 Composition

```text
single Admin
single Operator
single Pimpinan
single BK
single Kesehatan
single PTSP
single Guru
Guru + Wali
single Siswa
```

### 24.2 Double Role

Uji kombinasi yang final whitelist nyatakan valid.

Harus memastikan:

```text
Metric = Role 1
Access = Role 1
Data = Role 1
eligible Role 2 action muncul
Operator/Pimpinan secondary tidak membuat action section
permission union tetap bekerja
```

### 24.3 Triple Role

Uji minimal satu kombinasi triple valid setelah ordering contract final.

Pastikan:

```text
tidak ada duplicate metric
tidak ada duplicate role dashboard
action ordering deterministik
Role 1 tetap owner
```

### 24.4 Guru/Wali State

```text
not_started
available
submitted
ended
wali_available
not_applicable
```

### 24.5 Security Regression

```text
no new permission grant
no scope widening
Konseling privacy tetap
Wali tidak mendapat Jurnal privilege
Siswa self-scope tetap
UKS/PTSP identity tetap valid sesuai final contract
```

### 24.6 Responsive

Target minimal:

```text
360px
390/412px
tablet
desktop
WebView
```

---

## 25. Non-Goals

G3.9 tidak otomatis mencakup:

```text
mengubah formula EWS
mengubah business rule Presensi/Jurnal
membuat SLA/overdue baru
membuka Konseling ke role lain
mengubah privacy medical/BK
mengubah period history
membuat second SPA
migrasi Cordova native
merge PR #1
```

PR #1 hanya boleh dipakai sebagai archaeological reference. Source G3.9 berasal
dari current main.

---

## 26. Implementation Sequence

Recommended sequence setelah spec disahkan:

```text
G3.9A — Contract / Role Composition
G3.9B — Shared Dashboard Visual System
G3.9C — Single Role Migration
G3.9D — Multi-Role Composition
G3.9E — Regression / Mobile / WebView / Polish
G3.9F — SSOT Closure
```

Identity/role-order blocker harus ditutup sebelum G3.9D.

Single-role visual migration tidak boleh digunakan untuk menyamarkan blocker
multi-role.

---

## 27. Locked Decisions

Keputusan berikut dianggap **LOCKED candidate** sampai user mengubahnya secara
eksplisit:

1. Role 1 adalah pemilik Dashboard.
2. Metric Summary selalu milik Role 1.
3. EWS / Access utama selalu milik Role 1.
4. Data / Activity utama selalu milik Role 1.
5. Role tambahan tidak membawa seluruh Dashboard.
6. Admin/Operator/Pimpinan tidak mempunyai Primary Action Surface.
7. Operator/Pimpinan sebagai role tambahan tidak menambah action section.
8. BK/Kesehatan/PTSP/Guru dapat memiliki Primary Action Surface sesuai assignment.
9. Wali adalah context Guru.
10. Wali tidak membuat Metric Summary kedua.
11. Generic heading "Aksi Cepat" tidak menjadi canonical UI heading.
12. Semua actionable control Dashboard mempunyai background visual.
13. Action memakai subtle shared shadow; Metric tidak memakai button-like shadow.
14. Expired/disabled memakai neutral grey, bukan danger hanya karena waktu habis.
15. Completed memakai success/green.
16. Permission/scope/business invariant tetap authoritative server-side.
17. Dashboard tetap current-state, bukan laporan lengkap.
18. Sidebar multi-role mengikuti Primary/Secondary composition yang sama dengan Dashboard.
19. Permission tetap union; navigation tidak boleh melebur provenance role.
20. Single-role Sidebar tidak mendapat heading role tambahan.
21. Dashboard global hanya satu; Profile mengikuti person identity.
22. Leaf menu dideduplikasi incremental Primary → Secondary; parent/container boleh diulang.

---

## 28. Resolved Decisions Sebelum Implementation Multi-Role

```text
OD-01 RESOLVED
      Person master tetap exclusive.
      Staff operational role dapat memakai identity Guru atau Pegawai.

OD-02 RESOLVED
      Role 2/3 memakai canonical business ordering.
      Tidak menambah sort_order schema pada G3.9.

OD-03 RESOLVED
      Whitelist + batas secondary mengikuti §20.

OD-04 RESOLVED
      BK dinormalisasi sebagai operational role.
      BK tidak wajib id_pegawai bila actor mempunyai valid staff identity.
```

### 28.2 G3.9D — Multi-Role Sidebar Composition

Sidebar current baseline masih melakukan:

```text
effective roles
→ UNION role_menus
→ DISTINCT id_menu
→ one flat/global tree
```

Pola tersebut menghilangkan provenance Primary/Secondary role dan **disupersede**
untuk account multi-role G3.9.

Target:

```text
DashboardComposition
├─ primary_role
├─ secondary_roles
└─ wali_context
        ↓
Role-aware Menu Composition
├─ Global: Dashboard
├─ Primary section
├─ Secondary section(s), incremental
└─ Account/Profile by identity
```

Tidak menambah schema baru. `role_menus.role` sudah menjadi ownership source untuk
navigation. Existing `Menu & Role` settings tetap dipakai.

Regression rules:

```text
single role                  → existing tree presentation
Guru + Wali                  → no fake Wali role section
Guru + PTSP                  → Guru primary + PTSP secondary
Guru + Wali + PTSP           → Guru/Wali primary + PTSP secondary
BK + Kesehatan + PTSP        → three role sections
secondary Operator/Pimpinan  → menu section tetap boleh ada
duplicate leaf               → tampil pada earliest owner saja
empty parent                 → prune
Profile                      → identity based
Dashboard                    → one global item
```

### 28.1 BK identity normalization

BK dapat dijalankan oleh person identity:

```text
Guru
atau
Pegawai
```

selama effective role, permission, dan scope BK sah.

Field legacy/metadata seperti `id_guru_bk` tetap optional dan tidak boleh dipakai
sebagai authorization identity.

SSOT lama yang menyatakan BK selalu `users.id_pegawai` harus disinkronkan saat
G3.9 docs synchronization.

---

## 29. Exit Criteria G3.9

G3.9 dapat dinyatakan CLOSED hanya jika:

```text
[ ] contract Role 1/2/3 final
[ ] identity blocker final
[ ] role ordering final
[ ] whitelist final
[ ] shared visual token final
[ ] semua dashboard role migrated
[ ] Guru/Wali state regression PASS
[ ] single/double/triple regression PASS
[ ] permission/scope/privacy regression PASS
[ ] mobile 360px PASS
[ ] WebView readiness tidak regress
[ ] SSOT lama tersinkron
[ ] no direct dependency terhadap stale PR #1
```

---

## 30. Current Gate

Status pada pembuatan dokumen ini:

```text
Audit source/doc        = DONE
Dashboard formula       = DRAFT LOCKED CANDIDATE
Metric Summary concept  = DRAFT LOCKED CANDIDATE
Action visual system    = DRAFT LOCKED CANDIDATE
Guru/Wali state mapping = DRAFT LOCKED CANDIDATE

Cross-identity          = RESOLVED / SPEC
Role 2/3 ordering       = RESOLVED / SPEC
Exact role whitelist    = RESOLVED / SPEC
BK identity alignment   = RESOLVED / SPEC

Application mutation    = IN PROGRESS / G3.9A
Dashboard composition   = IMPLEMENTED / LOCAL GATE PENDING
Role assignment policy  = IMPLEMENTED / LOCAL GATE PENDING
Dashboard visual rewrite= NOT STARTED / G3.9B
PR G3.9                 = #18 / DRAFT
```

Next gate:

```text
Review resolved contract G3.9
→ susun Dashboard Visual State Matrix
→ sinkronkan SSOT terdampak
→ baru implementasi source
```
