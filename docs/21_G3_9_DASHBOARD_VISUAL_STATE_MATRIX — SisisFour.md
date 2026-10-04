# G3.9 — Dashboard Visual State Matrix — SisisFour

**Status:** Draft SSOT / Visual contract
**Tanggal Acuan:** 3 Oktober 2026
**Depends On:** `20_G3_9_DASHBOARD_EXPERIENCE_V2 — SisisFour.md`
**Baseline Source:** `main @ 95bedb09200ed954c60ea34fa0d8665d471528da`

> Dokumen ini menerjemahkan contract Dashboard Experience V2 menjadi matrix
> visual per role. Ia tidak menambah permission, route, scope, atau business rule.

---

## 1. Prinsip Visual

Dashboard G3.9 harus membuat perbedaan yang jelas antara:

```text
ACTION
→ sesuatu yang dapat ditekan / dikerjakan

METRIC
→ informasi / kondisi

STATUS
→ state / hasil

DATA
→ daftar / tabel / aktivitas
```

Keempat jenis elemen tidak boleh memiliki affordance yang sama.

### 1.1 Hard Affordance Contract

**Background bukan penanda tombol.**

Canonical grammar Dashboard G3.9:

```text
BACKGROUND
→ menjelaskan function family / state / grouping

ACTION SHADOW
→ menandakan enabled interactive surface

BORDER / STATE
→ menandakan current / priority / disabled

SHAPE
→ membantu membedakan button, status chip, metric, dan container
```

Hard rules:

1. Setiap **enabled clickable Dashboard action** wajib memakai shared subtle
   action shadow.
2. Primary/current action memakai action shadow level yang lebih kuat, tetap
   subtle.
3. Disabled/non-actionable control tidak memakai action shadow.
4. Metric Tile tidak memakai action shadow.
5. Status Chip/Badge tidak memakai action shadow.
6. Context stat/information surface tidak memakai action shadow.
7. Work Surface, termasuk state `active/current`, **bukan tombol** dan tidak
   memakai action shadow. Current work surface cukup memakai tonal background,
   emphasized border, dan explicit status.
8. Outer Sneat card boleh mempertahankan ambient/theme elevation. Ambient card
   elevation bukan action affordance.
9. Jika completed control masih clickable untuk detail/review, ia tetap memakai
   action shadow. Jika completed control non-clickable, gunakan disabled/non-
   interactive treatment.

Ringkasnya:

> **Background = function/state. Shadow = interactivity.**

---

## 2. Canonical Dashboard Layers

### 2.1 Non-Action-Surface Role

Admin / Operator / Pimpinan:

```text
HEADER
↓
METRIC SUMMARY
↓
EWS / ACCESS / MONITORING
↓
DATA / ACTIVITY
```

### 2.2 Action-Surface Role

BK / Kesehatan / PTSP / Guru:

```text
HEADER
↓
PRIMARY ACTION SURFACE
↓
METRIC SUMMARY
↓
EWS / ACCESS
↓
DATA / ACTIVITY
```

### 2.3 Guru + Wali

```text
HEADER
↓
PRIMARY ACTION GURU
↓
CONTEXT WALI
↓
METRIC SUMMARY GURU
↓
ACCESS KELAS
↓
DATA GURU + WALI
```

### 2.4 Siswa

```text
HEADER / STATUS
↓
METRIC SUMMARY PERSONAL
↓
SELF-SERVICE ACCESS
↓
DATA PERSONAL
```

---

## 3. Action Component Hierarchy

### 3.1 Primary Action

Digunakan untuk pekerjaan paling relevan pada role action-first.

Visual:

```text
filled / stronger tonal
atau soft gradient
icon + label
subtle shadow
44–48px minimum touch target
strong focus-visible
pressed state
```

Primary action boleh menggunakan satu dominant CTA pada satu context.

### 3.2 Access Action

Digunakan untuk navigation/action sekunder.

Visual:

```text
tonal filled background
soft border
subtle shadow
icon + label
44px minimum pada mobile
```

Bukan outline-only.

### 3.3 Compact Action

Untuk:

```text
Lihat Semua
Detail
Buka Kartu
Lihat Rekap
secondary navigation kecil
```

Visual tetap mempunyai background, tetapi strength lebih ringan daripada Access
Action.

### 3.4 Status Chip

Status chip bukan tombol.

```text
pill / badge
no action shadow
no pressed state
semantic state color
```

---

## 4. Functional Color Families

Warna berikut adalah **design token family**, bukan business state.

| Family | Intent | Contoh |
|---|---|---|
| Indigo | schedule / monitoring / general primary | Jadwal, Rekap, Laporan |
| Blue | attendance / operational input | Presensi |
| Violet | journal / learning workflow | Jurnal |
| Cyan | data / master / information access | Data Siswa, Data CKG |
| Teal | health/card/service utility | UKS, Kartu |
| Green | achievement / positive domain | Prestasi |
| Amber | attention domain | EWS |
| Rose-soft | violation/case domain | Pelanggaran |
| Slate | profile/general neutral access | Profil, utility |

Rules:

1. Family color tidak boleh mengalahkan state.
2. Rose-soft untuk navigation Pelanggaran **bukan** danger/destructive red.
3. Destructive/error red disimpan khusus state destructive/error.
4. Completed selalu success/green walau fungsi asalnya Blue/Violet.
5. Disabled selalu neutral grey walau fungsi asalnya berwarna.
6. Warna melekat pada fungsi, bukan role atau posisi tombol.
7. Primary, tile, compact, dan secondary-role action untuk fungsi yang sama
   memakai family yang sama.

Canonical function mapping:

```text
Jadwal / Rekap / Laporan        → Indigo
Presensi                        → Blue
Jurnal                          → Violet
Data / Master                   → Cyan
UKS / Kartu                     → Teal
Prestasi / positive satisfaction→ Green
EWS                             → Amber
Pelanggaran / Pengaduan         → Rose
Profil / utility neutral        → Slate
```

Domain-specific exception hanya boleh ditambahkan ke SSOT ini terlebih dahulu.

---

## 5. Locked G3.9 Token Palette

Nilai berikut adalah canonical untuk Dashboard G3.9. Perubahan warna harus dilakukan
di token bersama, bukan dengan nilai baru pada view/role tertentu. Contrast tetap
wajib diverifikasi pada visual regression.

| Token | Background | Border | Foreground |
|---|---|---|---|
| Indigo Soft | `#EEF0FF` | `#D9DCFF` | `#5053C7` |
| Blue Soft | `#EAF4FF` | `#CFE6FF` | `#2866A5` |
| Violet Soft | `#F2EDFF` | `#DED4FF` | `#6849B8` |
| Cyan Soft | `#EAF8FB` | `#CDECF2` | `#19778A` |
| Teal Soft | `#EAF7F4` | `#CCEAE3` | `#217565` |
| Green Soft | `#EDF8F0` | `#D2EBD8` | `#347A49` |
| Amber Soft | `#FFF6DF` | `#F3DEAA` | `#926415` |
| Rose Soft | `#FFF0F3` | `#F2D1D8` | `#A34A5E` |
| Slate Soft | `#F2F4F7` | `#DFE3E8` | `#586579` |

Strong/current CTA tetap berada di family warna fungsinya. Penguatan dilakukan
dengan border/emphasis + shadow tipis; jangan mengganti menjadi solid color ad-hoc,
dan jangan membuat glossy/high-contrast gradient.

---

## 6. Action State Override

State selalu menang atas functional family.

| State | Background intent | Shadow | Interaktif |
|---|---|---:|---:|
| Available | functional family | tipis | Ya |
| Current / Priority | stronger tonal family / emphasized border | tipis+ | Ya |
| Completed | Green Soft / success | action shadow bila clickable; none bila non-interactive | sesuai business rule |
| Not Started | Slate Soft | tidak | Tidak |
| Late but Actionable | Amber Soft | tipis | Ya |
| Expired / Disabled | neutral grey | tidak | Tidak |
| Error | danger/red soft | tidak/low | tergantung recovery |
| Destructive | danger/red | tipis | Ya dengan confirmation |
| Not Applicable | muted neutral | tidak | Tidak |

Disabled tidak boleh hanya mengandalkan opacity sangat rendah; label harus tetap
terbaca.

---

## 7. Metric Tile Contract

Metric Summary bukan kumpulan tombol.

### 7.1 Structure

```text
icon / icon bubble
VALUE
label
optional meta
```

### 7.2 Prominent

Role:

```text
Admin
Operator
Pimpinan
Siswa
```

Candidate:

```text
value 28–32px desktop
value 24–28px mobile
padding sedikit lebih luas
```

### 7.3 Compact

Role:

```text
BK
Kesehatan
PTSP
Guru
Guru + Wali
```

Candidate:

```text
value 22–26px
compact padding
2×2 mobile
```

### 7.4 Metric State

Metric dapat memakai tonal color untuk scanability, tetapi:

```text
no button shadow
no press effect
no pointer cue
no chevron action
```

---

# 8. ROLE MATRIX

## 8.1 Admin

### Structure

```text
Dashboard Admin

Ringkasan Sistem
↓
Akses Sistem / Monitoring
↓
Operational Data / Activity
```

### Primary Action Surface

```text
NONE
```

### Metric Summary — Prominent

Candidate Role 1 metrics:

| Metric | Visual family |
|---|---|
| Siswa Aktif | Indigo Soft |
| Guru | Blue Soft |
| Pegawai | Cyan Soft |
| Kelas Aktif | Violet Soft |

Operational exception seperti Presensi/Jurnal/EWS tetap dapat muncul sebagai
monitoring summary/access area sesuai final layout, bukan harus menambah grid KPI
tanpa batas.

### Access / Monitoring

Existing source yang dapat dipertahankan/reorganisasi:

```text
EWS Signage
Presensi
Jurnal
EWS
Prestasi
Kartu
master/operational links sesuai permission
```

Access action memakai tonal filled button/tile.

### Data / Activity

```text
Tren Presensi 7 Hari
Aktivitas Terakhir
operational summary
```

---

## 8.2 Operator

### Structure

```text
Dashboard Operator

Kondisi Operasional
↓
Akses Operasional
↓
Data / Activity
```

### Primary Action Surface

```text
NONE
```

Operator sebagai secondary role juga tidak menambah Primary Action Surface.

### Metric Summary — Prominent

Candidate:

| Metric | Family |
|---|---|
| Kelas Belum Presensi | Blue/Amber tonal berdasarkan metric meaning |
| Jadwal Belum Jurnal | Violet/Amber tonal |
| EWS Alpha 14 Hari | Amber Soft |
| Ringkasan Master utama | Indigo/Cyan Soft |

Metric adalah informasi; warna metric tidak mengubah business state.

### Access

Akses operasional diambil dari capability/menu existing, tidak dibuat menjadi
duplicate dashboard untuk setiap module.

### Data

```text
Presensi Hari Ini
Tren Presensi
Aktivitas Terakhir
summary BK/Prestasi/Kartu bila tetap relevan
```

---

## 8.3 Pimpinan

### Structure

```text
Dashboard Pimpinan

Kondisi Hari Ini
↓
Monitoring
↓
Trend / EWS / Prestasi / Ringkasan
```

### Primary Action Surface

```text
NONE
```

Pimpinan sebagai secondary role juga tidak menambah action section.

### Metric Summary — Prominent

Canonical:

| Metric | Family |
|---|---|
| Kelas Belum Presensi | Blue/Amber Soft |
| Jadwal Belum Jurnal | Violet/Amber Soft |
| EWS Alpha 14 Hari | Amber Soft |
| Pelanggaran Bulan Ini | Rose Soft |

### Monitoring Access

Current quick action dipindahkan secara konseptual dari "Aksi Cepat" ke
Monitoring:

| Action | Family |
|---|---|
| Rekap Presensi | Indigo Soft |
| Laporan Jurnal | Violet Soft |
| EWS | Amber Soft |
| Matrix/Laporan | Indigo/Cyan Soft |

### Data

```text
Tren Presensi 7 Hari
EWS max 5
Prestasi Terbaru max 5
Ringkasan Master
```

Tidak ada Konseling payload/detail.

---

## 8.4 BK

### Structure

```text
Dashboard BK

Prioritas BK
↓
Ringkasan BK
↓
Akses BK / EWS
↓
Data BK
```

### Primary Action Surface

Canonical catalog:

| Action | Family |
|---|---|
| Konseling BK | Indigo/Violet Soft |
| Catatan Pelanggaran | Rose Soft |
| EWS | Amber Soft |
| Prestasi | Green Soft |

Semua permission-aware.

Prioritas content tetap:

```text
Konseling proses / follow-up
Pelanggaran terbaru
Tindak Lanjut
EWS
Prestasi
```

Tidak menciptakan SLA/overdue baru.

### Metric Summary — Compact

| Metric | Family |
|---|---|
| Konseling Proses | Indigo Soft |
| Pelanggaran Bulan Ini | Rose Soft |
| EWS Alpha 14 Hari | Amber Soft |
| Prestasi Bulan Ini | Green Soft |

### Data

```text
Follow-up Terdekat
Pelanggaran Terbaru
EWS
Prestasi Terbaru
```

Maximum recent/top tetap 3–5 + Lihat Semua.

---

## 8.5 Kesehatan

### Structure

```text
Dashboard Kesehatan

Layanan UKS
↓
Ringkasan UKS
↓
Akses UKS
↓
Data UKS
```

### Primary Action Surface

Primary current:

```text
Tambah Data Kunjungan
```

Candidate visual:

```text
Teal/Blue stronger tonal
icon bx-plus-medical
full-width atau prominent tile
```

### Access

| Action | Family |
|---|---|
| Data UKS | Teal Soft |
| Data CKG | Cyan Soft |
| Import CKG | Indigo Soft |
| Master UKS | Slate/Cyan Soft |

### Metric Summary — Compact

| Metric | Family |
|---|---|
| Kunjungan Hari Ini | Teal Soft |
| Kunjungan Bulan Ini | Blue Soft |
| Rujuk Klinik Bulan Ini | Amber Soft |
| Pemeriksaan CKG Bulan Ini | Cyan Soft |

Amber pada "Rujuk" hanya ringkasan attention, bukan medical risk classification.

### Data

```text
Kunjungan UKS Terbaru
Pemeriksaan CKG Terbaru
```

Tidak membuat risk score, SLA, atau diagnosis.

---

## 8.6 PTSP

### Structure

```text
Dashboard PTSP

Layanan PTSP
↓
Ringkasan Layanan
↓
Akses Layanan
↓
Data Layanan
```

### Primary Action Surface

PTSP dapat menggunakan action surface untuk pekerjaan layanan yang benar-benar
operasional.

Existing catalog:

| Action | Family |
|---|---|
| Layanan PTSP | Indigo/Blue Soft |
| Polling Kepuasan | Green/Teal Soft |
| Pengaduan | Rose Soft |
| Public PTSP | Cyan Soft |

Final primary emphasis dapat diberikan pada Layanan PTSP, sedangkan action lain
menjadi access tiles.

### Metric Summary — Compact

| Metric | Family |
|---|---|
| Layanan Baru | Blue Soft |
| Layanan Diproses | Indigo Soft |
| Pengaduan Masuk | Rose Soft |
| Rata-rata Kepuasan | Green Soft |

### Data

```text
Layanan Terbaru
Pengaduan Terbaru
Ringkasan Kepuasan
```

---

## 8.7 Guru

### Structure

```text
Dasbor Guru

Hari Ini
↓
Ringkasan Hari Ini
↓
Akses Guru
↓
Jadwal / Jurnal Activity
```

### Primary Action Surface

Hero/current schedule:

```text
Kelas · Mapel
Jam
Sesi
state

[ Presensi ]
[ Jurnal ]
```

Secondary access:

| Action | Family |
|---|---|
| Jadwal | Indigo Soft |
| Profil | Slate Soft |

Presensi/Jurnal bukan sekadar shortcut; CTA mengikuti schedule state dari server.

### Metric Summary — Compact

| Metric | Family |
|---|---|
| Jadwal Hari Ini | Indigo Soft |
| Belum Presensi | Blue/Amber Soft |
| Belum Jurnal | Violet/Amber Soft |
| Selesai | Green Soft |

### Data

```text
Jadwal Hari Ini
Jurnal Terakhir
```

Jadwal lengkap tetap tampil sebagai data/activity walaupun current schedule telah
diangkat ke Primary Action Surface.

---

## 8.8 Guru + Wali

### Structure

```text
Dasbor Guru & Wali Kelas

Hari Ini / Guru
↓
Ringkasan Hari Ini — Guru
↓
Kelas Wali · {kelas}
↓
Akses Kelas
↓
Jadwal / EWS / Ketidakhadiran / Jurnal
```

Wali tidak membuat Metric Summary kedua.

### Guru Primary Action

Sama dengan Guru.

### Wali Context

Context card:

```text
Kelas Wali · 7-A
jumlah siswa
H/S/I/A Sesi Awal
EWS count jika permission
```

Context card adalah informasi + contextual access, bukan KPI dashboard kedua.

### Access Kelas

| Action | Family |
|---|---|
| Presensi Kelas | Blue Soft |
| Rekap Presensi | Indigo Soft |
| Data Siswa | Cyan Soft |
| Matrix Presensi | Violet/Indigo Soft |
| EWS Kelas | Amber Soft |
| Catatan Pelanggaran | Rose Soft |
| Prestasi Siswa | Green Soft |
| Kartu Pelajar | Teal Soft |

Semua action mendapatkan background + subtle shadow.

Tidak ada Konseling.

### Data

```text
Jadwal Hari Ini
EWS Kelas
Ketidakhadiran Terbaru
Jurnal Terakhir
```

---

## 8.9 Siswa

### Structure

```text
Dashboard Siswa

Status Sesi Awal Hari Ini
↓
Presensi Bulan Ini
↓
Akses Saya
↓
Data Saya
```

### Primary Action Surface

Tidak memakai operational Primary Action Surface seperti Guru/BK.

Status hari ini harus terlihat lebih dulu.

### Metric Summary — Prominent

| Metric | Family |
|---|---|
| Hadir | Green Soft |
| Sakit | Amber Soft |
| Izin | Blue/Cyan Soft |
| Alpha | Rose Soft |

Metric tetap informational.

### Self-Service Access

| Action | Family |
|---|---|
| Presensi Saya | Blue Soft |
| Kartu | Teal Soft |
| Prestasi | Green Soft |
| Profil | Slate Soft |

### Data

```text
Ketidakhadiran Terbaru
Kartu Pelajar
Prestasi Saya
Catatan Pelanggaran Saya
```

Semua self-scope. Konseling tidak pernah tampil.

---

# 9. GURU/WALI BUTTON STATE MATRIX

## 9.1 Presensi

| Backend state | Label intent | Visual | Clickable |
|---|---|---|---:|
| `not_applicable` | Tidak berlaku | Slate muted | Tidak |
| `submitted` | Presensi selesai | Green completed | Tidak / detail-only bila ada route sah |
| `not_started` | Belum waktunya | Slate Soft | Tidak |
| `available` | Isi Presensi | Blue active | Ya |
| `wali_available` | Isi sebagai Wali | Amber/Blue contextual | Ya |
| `ended` | Waktu habis | Grey disabled | Tidak |

### 9.2 Jurnal

| Backend state | Label intent | Visual | Clickable |
|---|---|---|---:|
| `submitted` | Jurnal selesai | Green completed | Tidak / detail-only bila sah |
| `not_started` | Belum waktunya | Slate Soft | Tidak |
| `available` | Isi Jurnal | Violet active | Ya |
| `ended` | Terlewat | Grey disabled | Tidak |

### 9.3 Current Schedule

Jika `dashboard_state = berlangsung`:

```text
current schedule card
→ stronger border/background
→ current indicator
→ available action memperoleh stronger tone
```

Tidak perlu flashing/pulsing animation.

### 9.4 Upcoming Schedule

```text
berikutnya
→ normal/soft card
→ action not_started tetap non-actionable
```

### 9.5 Finished Schedule

```text
selesai
→ card lebih muted
→ submitted = green
→ ended = grey
```

---

# 10. Interaction State Matrix

Semua button/tile actionable:

| Interaction | Behavior |
|---|---|
| Default | tonal/filled + shadow tipis |
| Hover desktop | background sedikit lebih kuat, shadow tetap subtle |
| Focus keyboard | visible focus ring |
| Active/Pressed | shadow mengecil/hilang + translation sangat kecil |
| Busy | disabled + spinner/progress cue bila mutation |
| Disabled | grey, no shadow, no pointer action |

Tidak memakai transform yang membuat layout bergeser signifikan.

---

# 11. Shadow Contract

Shadow level adalah **affordance contract**, bukan dekorasi per-page.

Canonical:

```text
LEVEL 0 — NON INTERACTIVE
Metric / Status / Badge / Context / Work Surface
→ no action shadow

LEVEL 1 — ENABLED ACTION
Access / compact / navigation action
→ 0 2px 6px rgba(67, 89, 113, 0.10)

LEVEL 2 — CURRENT / PRIMARY ACTION
Current operational action
→ 0 3px 8px rgba(67, 89, 113, 0.12)

PRESSED
→ 0 1px 2px rgba(67, 89, 113, 0.08)

DISABLED
→ none
```

Outer Sneat card boleh tetap memakai theme ambient elevation. Jangan menggunakan
ambient card shadow sebagai pengganti action affordance.

Nilai final harus diwujudkan sebagai shared CSS custom property/class.

---

# 12. Multi-Role Visual Composition

## 12.1 Secondary Operator/Pimpinan

Tidak menghasilkan section.

```text
Guru + Operator
→ Hari Ini Guru
→ Metric Guru
→ Access Guru
→ Data Guru
```

Menu/capability Operator tetap tersedia.

## 12.2 BK + Kesehatan

```text
Prioritas BK
Layanan UKS / action subset Kesehatan
Ringkasan BK
Akses BK
Data BK
```

Secondary Kesehatan hanya mengirim action catalog yang diperlukan, bukan seluruh
dashboard Kesehatan.

## 12.3 BK + Operator + Kesehatan

```text
Prioritas BK
Layanan UKS
Ringkasan BK
Akses BK
Data BK
```

Operator tidak membuat action block.

## 12.4 BK Triple dengan dua Action-Surface secondary

Jika kombinasi whitelist menghasilkan dua secondary yang sama-sama eligible,
urutan mengikuti canonical role ordering dari contract G3.9.

Tetap:

```text
1 Metric Summary
1 EWS/Access owner
1 Data/Activity owner
```

Tidak boleh ada tiga KPI grids.

---

# 13. Responsive Matrix

## Mobile <= 767px

```text
Metric: 2 columns
Access action: 2 columns bila label muat
Long primary action: full width
Long label boleh wrap
touch 44px+
data: card/list adaptive
no horizontal operational table scroll
```

## Very narrow 360px

```text
2-column metric tetap boleh jika label aman
access dapat turun ke 1 column bila label/context terlalu panjang
no clipped text
no 32px mini button untuk primary action
```

## Tablet

```text
Metric 2–4 columns
action 2–4 columns sesuai context
data dapat memakai adaptive table/list
```

## Desktop

```text
4 metric dalam satu row bila 4 metric
primary action tetap hierarchical
jangan membuat dashboard terlalu wide/dense hanya karena space tersedia
```

---

# 14. Empty / Unavailable / Permission State

### Permission absent

```text
control/section tidak dirender
```

Jangan render disabled button "Tidak punya akses".

### Period unavailable

Tampilkan explicit unavailable state, bukan angka 0 palsu.

### Identity unavailable

Tampilkan explicit identity error hanya pada experience yang memang membutuhkan
valid person identity setelah contract G3.9.

### Empty data

```text
Tidak ada data / belum ada aktivitas
```

tetap berbeda dari unauthorized dan unavailable.

---

# 15. Implementation CSS Direction

G3.9 sebaiknya menambah shared semantic class, misalnya secara konseptual:

```text
.sisfour-action
.sisfour-action--primary
.sisfour-action--compact

.sisfour-action--indigo
.sisfour-action--blue
.sisfour-action--violet
.sisfour-action--cyan
.sisfour-action--teal
.sisfour-action--green
.sisfour-action--amber
.sisfour-action--rose
.sisfour-action--slate

.sisfour-action.is-completed
.sisfour-action.is-disabled
.sisfour-action.is-current
.sisfour-action.is-late

.sisfour-metric-grid
.sisfour-metric-tile
.sisfour-metric-tile--prominent
.sisfour-metric-tile--compact
```

Nama final class boleh berubah, tetapi semantic contract harus dipertahankan.

Jangan memberi style global pada seluruh `.btn` sehingga form/modal/module di luar
Dashboard ikut berubah tanpa review.

---

# 16. Acceptance Visual

G3.9 visual dinyatakan pass bila:

```text
[ ] user dapat membedakan action vs metric tanpa menebak
[ ] semua dashboard action mempunyai background
[ ] shadow konsisten dan tipis
[ ] metric tidak tampak clickable
[ ] current Guru task lebih menonjol dari metric
[ ] disabled Guru task jelas tetapi tetap readable
[ ] ended tidak menggunakan danger hanya karena waktu habis
[ ] completed konsisten green
[ ] Wali available masih tampak actionable
[ ] Admin/Operator/Pimpinan tidak mempunyai fake Primary Action section
[ ] secondary Operator/Pimpinan tidak menggandakan dashboard
[ ] mobile 360px tidak overflow
[ ] touch target tetap aman
[ ] keyboard focus terlihat
[ ] no permission leak karena presentation
```

---

# 17. Gate

Status setelah Visual State Matrix:

```text
Dashboard composition = SPEC RESOLVED
Identity contract      = SPEC RESOLVED
Role ordering          = SPEC RESOLVED
Role whitelist         = SPEC RESOLVED
Visual hierarchy       = DRAFT READY
Action state matrix    = DRAFT READY
Metric design          = DRAFT READY

Application source     = G3.9A IN PROGRESS — composition + role policy implemented; local gate pending
```

Next:

```text
Review Visual State Matrix
→ sync SSOT terdampak
→ G3.9A implementation contract/composition
→ G3.9B shared visual system
```
