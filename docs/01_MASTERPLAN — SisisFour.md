# Masterplan — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 18 September 2026
**Development aktif:** G3.6B — PTSP
**Target:** Web + Android Cordova

## 1. Sistem

SisisFour adalah Sistem Informasi Manajemen Madrasah MTsN 4 Jombang untuk akademik, Presensi, monitoring, BK, UKS/Kesehatan, PTSP, Kartu Pelajar, personalia, pelaporan, dan self-service pengguna.

Stack utama:

```text
CodeIgniter 4
PHP 8.2+
MariaDB/MySQL
Sneat Free v3 + Bootstrap 5.3.x
Vanilla JavaScript + Fetch
```

## 2. Surface Client

```text
Desktop/laptop browser
Mobile browser
Android Cordova WebView
Authenticated Web
Public PTSP Landing
API /api/*
Public aggregate statistics API PTSP
```

UI authenticated utama tetap View CI4/Sneat yang sama; APK tidak menjadi SPA kedua. PTSP mempunyai public landing terpisah untuk public form.

## 3. Role

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

Wali Kelas adalah context Guru, bukan role baru. Kesehatan dan PTSP memakai identity Pegawai. Multi-role diperbolehkan.

## 4. Identity UX

```text
Nama lengkap = identitas visual utama
NISN/NIP/NIK = identifier sekunder
```

Search tetap mendukung nama + identifier untuk verifikasi/disambiguasi.

## 5. Modul

- Auth Web + API.
- RBAC + menu dinamis.
- Dashboard per experience.
- Master Guru/Pegawai/Siswa/Kelas/Tahun/Mapel.
- Mapping Wali + Jadwal Guru.
- Manajemen Siswa.
- Presensi Siswa + Jurnal Mengajar.
- Laporan/Matrix/EWS/Signage.
- BK: Catatan Pelanggaran, Tindak Lanjut Pelanggaran, Konseling, Tindak Lanjut Konseling, Prestasi.
- UKS/Kesehatan: Data CKG + Catatan Harian UKS.
- PTSP: Layanan, Polling Kepuasan, Pengaduan, public landing, public statistics API.
- Kartu Pelajar.
- Profile/Personalia/Portofolio.
- Settings/Maintenance/Backup/Log.
- Android Cordova pada G4.

## 6. Arsitektur

```text
Client
→ Route/Filter
→ Controller
→ Service
→ Model/Query
→ Database
```

Service adalah business/security boundary. View/JS tidak menentukan authorization final.

Public PTSP tetap melewati Service validation; public form tidak memberikan akses internal administration.

## 7. Semester & Lifecycle

```text
Ganjil → Genap tahun sama = Siapkan Genap
Genap → Ganjil tahun berikutnya = Kenaikan/Kelulusan
7 → 8
8 → 9
kelas 9 → Kelulusan
```

Lifecycle menjaga membership/history/status/Kartu secara transactional. Histori terminal tidak dihapus.

## 8. Global Standard

SSOT global:

```text
docs/00_POLA_PENGERJAAN___SisisFour.md
docs/00A_GLOBAL_STANDARD_SISFOUR.md
```

Canonical mapping:

```text
Menu/Fitur
→ Use Case
→ Domain
→ Access Boundary
→ Capability
→ Scope
→ Period Context
→ Target Validation
→ Business Invariant
→ Persistence
→ Service Boundary
→ UI
→ Output/API
→ Audit
→ Testing
→ Docs Sync
→ Deployment Gate
```

Global UI/UX:

```text
filter desktop padat tidak dipaksa satu baris
periodic/history table -> filter Tahun Ajaran
Tahun Ajaran default/reset -> periode aktif
export periodik -> mengikuti periode terpilih
create parent periodik -> Service snapshot periode aktif
no body horizontal overflow
no horizontal table scroll role operasional
name-first identity
```

## 9. Phase Closed

```text
G2     CLOSED / MERGED — PR #5
G3.1   CLOSED / MERGED — PR #6
G3.2   CLOSED / MERGED — PR #7
G3.3   CLOSED / MERGED — PR #8
G3.3.1 CLOSED / MERGED — PR #9
G3.4   CLOSED / MERGED — PR #10
G3.5   CLOSED / MERGED — PR #11
G3.6   CLOSED / MERGED — PR #12
```

Merge baseline:

```text
G2     375766c07f3856515a71ffdb07f3681c3047ca31
G3.1   d10ced5d70ffc68642067aac44feeb6a91cacd29
G3.2   176e5f764850d030968524af47117f259449064c
G3.3   06e4e559c045763096058fc889342da78d973314
G3.3.1 27d0f867d1c0ca7636a4a48f6c0b3251538ee7f6
G3.4   6f809913eab1032691f130c9df00e95da74b9a17
G3.5   6bdfc276ae07b6e70065ee7fae9e6ef51c3299ce
G3.6   59b22b651ad0d508ea3a29261ef590d4c9506da4
G3.6A  90acc7f94fee391a5a7fbad2395e3f16571fe921
```

## 10. G3.3.1 — Fondasi BK + Konseling — Closed

Branch merged:

```text
feat/g3-bk-foundation-konseling-20260916
```

Keputusan utama:

```text
Catatan Kasus -> Catatan Pelanggaran Siswa
poin pelanggaran retired
Master Pelanggaran nama + kategori
Top Poin retired
Konseling BK terpisah dan rahasia
Settings Form Konseling memakai setting_sistem
created_by -> users.id
akun BK aktual -> users.id_pegawai -> pegawai.id
Catatan Pelanggaran/Konseling/Prestasi = periodik Tahun Ajaran
Konseling parent + Tindak Lanjut 1:N
no delete parent/follow-up Konseling pada contract sekarang
```

Permission Konseling:

```text
bk_konseling.view       -> Admin, Operator, BK
bk_konseling.manage     -> Admin, Operator, BK
bk_konseling.export     -> Admin, Operator, BK
bk_konseling.settings   -> Admin, BK
```

Pimpinan/Guru/Wali/Siswa/Kesehatan/PTSP tidak menerima surface/detail Konseling.

### Period Context BK

```text
listing default = Tahun Ajaran aktif
Reset           = Tahun Ajaran aktif
history         = selectable
export          = mengikuti period filter
create parent   = snapshot Tahun Ajaran aktif server-side
update existing = tetap period record
follow-up       = mengikuti parent
```

### Konseling 1:N

```text
konseling_bk 1:N tindak_lanjut_konseling_bk
```

Canonical detail:

```text
Identitas
→ Hasil Pertemuan Awal
→ Riwayat Tindak Lanjut
→ Form Tambah/Edit Tindak Lanjut
```

### Closure

```text
local schema/runtime                     PASS
hosting schema/source/re-smoke           PASS
focused UAT Siswa                        PASS
final static exact head                  PASS
PR #9                                    MERGED
merge commit                             27d0f867d1c0ca7636a4a48f6c0b3251538ee7f6
```

## 11. G3 Roadmap Aktif

```text
G3.4  BK Workflow + Dashboard BK       CLOSED / MERGED — PR #10
G3.5  Pimpinan                         CLOSED / MERGED — PR #11
G3.6  Siswa                            CLOSED / MERGED — PR #12
G3.6A UKS / Kesehatan                  CLOSED / MERGED — PR #13
G3.6B PTSP                             ACTIVE / IMPLEMENTATION
G3.7  Global Mobile Sweep
G3.8  Viewport/WebView Readiness
G4    Cordova APK
```

### G3.6A — UKS / Kesehatan

SSOT: `17_UKS_KESEHATAN — SisisFour.md`.

Target:

```text
Role Kesehatan = Pegawai
Data CKG + import Excel
Catatan Harian UKS
periodik Tahun Ajaran
Admin/Operator/Kesehatan Full Access domain UKS
Pimpinan ReadOnly SEMUA + Export
Wali ReadOnly kelas wali
Siswa ReadOnly data dirinya sendiri
soft delete UKS/CKG
```

UKS ditempatkan setelah Pimpinan dan Siswa agar scope lintas-role telah mempunyai foundation stabil.

G3.6A CLOSED / MERGED pada PR #13, merge commit `90acc7f94fee391a5a7fbad2395e3f16571fe921`.

Contract locked:

```text
Import CKG stable key = NISN wajib
duplicate aktif siswa+tanggal = UPDATE
CKG tombstone lama = INSERT record aktif baru, tanpa auto-restore
Master configurable = Keluhan / Tindakan / Hasil Kunjungan
Master normal action = deactivate/reactivate
Dashboard Kesehatan = current-state Tahun Ajaran aktif
priority role = admin > operator > pimpinan > bk > kesehatan > guru > siswa
G3.6B priority role = admin > operator > pimpinan > bk > kesehatan > ptsp > guru > siswa
```

Localhost + hosting DB/source/runtime G3.6A telah PASS sebelum merge.

### G3.6B — PTSP

SSOT: `18_PTSP — SisisFour.md`.

Target:

```text
Role PTSP = Pegawai
public landing page
public Layanan PTSP
public Polling Kepuasan
public Pengaduan anonim
Admin/Operator/PTSP Full Access domain PTSP
Pimpinan ReadOnly SEMUA + Export
Hard delete PTSP = Admin/Operator/PTSP
Tahun Ajaran snapshot/filter
thermal print bukti pengisian TANPA nomor tiket
public aggregate statistics API per form untuk WordPress/portal
```

PTSP diletakkan setelah UKS karena menambah public surface + public API yang memerlukan regression khusus di luar authenticated role experience.

G3.6B implementation aktif pada branch `feat/g3-6b-ptsp-20260919` dari baseline main `90acc7f94fee391a5a7fbad2395e3f16571fe921`.

Production gate status:

```text
local SQL/runtime/dump                 PASS
hosting pre/post SQL dump audit        PASS
hosting SQL execution                  PASS / user evidence
hosting source deployment              PASS / user evidence
focused hosting runtime smoke          PASS / user runtime evidence
PR #14                                 READY FOR REVIEW
merge                                  NOT AUTHORIZED
```

Contract tambahan yang dikunci:

```text
priority = admin > operator > pimpinan > bk > kesehatan > ptsp > guru > siswa
persistence = 4 tabel PTSP
lampiran Pengaduan = private WRITEPATH, PDF/PNG/JPG/JPEG, max 5 MB
public statistics CORS = * / GET+OPTIONS / no credentials
dashboard PTSP = Layanan Baru / Diproses / Pengaduan Masuk / Rata-rata Kepuasan
```

## 12. G3.4 — Dashboard/Workflow BK

Branch merged:

```text
feat/g3-4-bk-dashboard-workflow-20260918
```

G3.4 memakai foundation final BK tanpa schema/permission/route baru.

Dashboard BK adalah current-state Tahun Ajaran aktif:

```text
KPI
- Konseling Proses
- Pelanggaran Bulan Ini
- EWS Alpha 14 Hari
- Prestasi Bulan Ini

Quick Action
- Konseling BK
- Catatan Pelanggaran
- EWS
- Prestasi

Priority list
- Jadwal Follow-up Terdekat
- Catatan Pelanggaran Terbaru
- EWS
- Prestasi Terbaru
```

Catatan Pelanggaran/Konseling/Prestasi pada dashboard harus dibatasi Tahun Ajaran aktif. Dashboard tidak menambah selector Tahun Ajaran karena historical period tetap menjadi concern listing.

Jadwal follow-up memakai tanggal pada entry tindak lanjut terbaru bila histori sudah ada; jika belum, memakai tanggal berikutnya parent. Tidak ada SLA/overdue baru.

Recent/top list maksimal 5 item + Lihat Semua. Quick action permission-aware dan tidak menambah authorization.

Closure:

```text
source implementation             PASS
SSOT sync                         PASS
local/runtime/privacy/mobile      PASS / user evidence
final static gate                 PASS / user terminal evidence
hosting deployment/re-smoke       PASS / user evidence
PR #10                            MERGED
merge commit                      6f809913eab1032691f130c9df00e95da74b9a17
```

## 13. G3.5 — Dashboard Pimpinan

Branch merged:

```text
feat/g3-5-pimpinan-dashboard-20260918
```

G3.5 memakai permission dan route existing. Tidak ada schema, permission, menu, route, atau mutation baru.

Dashboard Pimpinan adalah current-state Tahun Ajaran aktif:

```text
KPI
- Kelas Belum Presensi
- Jadwal Belum Jurnal
- EWS Alpha 14 Hari
- Catatan Pelanggaran Bulan Ini

Quick Action permission-aware
- Rekap
- Jurnal
- EWS
- Laporan

Monitoring
- Tren Presensi 7 Hari
- EWS maksimal 5 + Lihat Semua
- Prestasi Terbaru maksimal 5 + Lihat Semua
- Ringkasan Master
```

Catatan Pelanggaran Bulan Ini dan Prestasi Terbaru wajib dibatasi Tahun Ajaran aktif. Pimpinan tetap readonly sesuai permission/domain. Konseling BK tidak menjadi payload/widget/detail/shortcut Pimpinan.

Closure:

```text
source implementation             PASS
SSOT sync                         PASS
local Pimpinan runtime/UAT        PASS / user runtime evidence
privacy / readonly regression     PASS / user runtime evidence
mobile focused UAT                PASS / user runtime evidence
cross-role dashboard regression   PASS / user runtime evidence
final static gate                 PASS / user terminal evidence
hosting source deployment         PASS / user evidence
hosting re-smoke                  PASS / user runtime evidence
PR #11                            MERGED
merge commit                      6bdfc276ae07b6e70065ee7fae9e6ef51c3299ce
```

## 14. G3.6 — Dashboard Siswa

Branch merged:

```text
feat/g3-6-siswa-dashboard-20260918
```

G3.6 memakai identity/scope/permission dan route existing. Tidak ada schema, permission, menu, route, model, atau mutation baru.

Dashboard Siswa adalah self-service readonly current-state Tahun Ajaran aktif:

```text
Header
- Tahun Ajaran aktif
- Data Saya

Status
- Presensi Sesi Awal hari ini

KPI 2×2
- Hadir
- Sakit
- Izin
- Alpha

Quick Action permission-aware
- Presensi Saya
- Kartu
- Prestasi
- Profil

Recent/self-service
- Ketidakhadiran max 5 + Lihat Rekap
- Kartu Pelajar diri sendiri
- Prestasi max 5 + Lihat Semua
- Catatan Pelanggaran max 5 + Lihat Semua
```

Presensi, Prestasi, dan Catatan Pelanggaran dashboard dibatasi identity login. Data periodik memakai Tahun Ajaran aktif. Tidak adanya Tahun Ajaran aktif dibedakan dari nilai nol. Historical Prestasi/Pelanggaran tetap dibaca melalui listing self-only dengan selector Tahun Ajaran.

Pelanggaran tidak memakai poin. Konseling BK tidak menjadi payload/widget/detail/shortcut Siswa. Quick Action hanya shortcut ke capability existing dan tidak menambah permission.

Closure:

```text
source implementation             PASS
SSOT sync                         PASS
static gate                       PASS / user terminal evidence
local runtime/UAT                 PASS / user runtime evidence
self-scope/privacy regression     PASS / user runtime evidence
cross-role regression             PASS / user runtime evidence
hosting deployment/re-smoke       PASS / user evidence
PR #12                            MERGED
merge commit                      59b22b651ad0d508ea3a29261ef590d4c9506da4
```

## 15. G4 — Cordova APK

Setelah G3 stable:

```text
architecture spike
Android project/config
session/WebView verification
Android Back
keyboard/safe-area/status bar
geolocation
network/offline state
file/download/share
external links
real-device regression
signed package/distribution
```

## 16. Release Rule

Setiap phase harus melewati SSOT + local/static/runtime + regression + production gate sesuai `00/00A/15`.

Tidak ada auto-deploy, auto-Ready, atau auto-merge. Setiap deploy/Ready/Merge membutuhkan approval eksplisit pengguna.

## 17. G3.6C — Executive Visualization & EWS Signage

Baseline:

```text
G3.6B PR #14 = CLOSED / MERGED
main = f6f30ceaf070f342d609c322905ee77dc33f3e6f
branch = feat/g3-6c-exec-viz-signage-20260919
```

SSOT detail: `19_G3_6C_EXEC_VIZ_SIGNAGE — SisisFour.md`.

Scope:

```text
Dashboard Admin/Operator/Pimpinan -> shortcut EWS Signage
Signage -> TemplateSIGNAGE three-panel layout + internal rotation
Statistik -> Admin/Operator/Pimpinan only + PDF
```

Statistik tidak pernah membawa Konseling BK. Pelanggaran tetap tanpa poin. UKS/PTSP hanya aggregate-safe.

Roadmap setelah phase ini:

```text
G3.6C Executive Visualization & EWS Signage
G3.7  Global Mobile Sweep
G3.8  WebView Readiness
G4    Cordova APK
```
