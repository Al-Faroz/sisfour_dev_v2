# Masterplan — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 17 September 2026
**Development aktif:** G3.3.1 rework — periodic Tahun Ajaran + Konseling follow-up 1:N
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
```

Merge baseline sebelum PR #9:

```text
G2   375766c07f3856515a71ffdb07f3681c3047ca31
G3.1 d10ced5d70ffc68642067aac44feeb6a91cacd29
G3.2 176e5f764850d030968524af47117f259449064c
G3.3 06e4e559c045763096058fc889342da78d973314
```

## 10. G3.3.1 — Fondasi BK + Konseling

Branch:

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

### Current Gate

```text
baseline G3.3.1 local/hosting                   PASS
parent historical-Rencana focused local UAT    PASS
17 Sep localhost SQL execution                  PASS (user evidence)
17 Sep local runtime UAT                        PASS (user evidence)
17 Sep final static gate                        PENDING
17 Sep hosting dump audit/delta/re-smoke        NOT STARTED
PR #9                                           DRAFT / BELUM MERGE
```

## 11. G3 Roadmap Setelah PR #9

```text
G3.4  BK Workflow + Dashboard BK
G3.5  Pimpinan
G3.6  Siswa
G3.6A UKS / Kesehatan
G3.6B PTSP
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

## 12. G3.4 Foundation Target

G3.4 memakai foundation final BK:

```text
Konseling Proses/follow-up terdekat
Catatan Pelanggaran terbaru/berat tanpa poin
Tindak Lanjut perlu perhatian
EWS
Prestasi
quick action permission-aware
mobile-first
privacy Konseling ketat
```

## 13. G4 — Cordova APK

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

## 14. Release Rule

Setiap phase harus melewati SSOT + local/static/runtime + regression + production gate sesuai `00/00A/15`.

Tidak ada auto-deploy, auto-Ready, atau auto-merge. Setiap deploy/Ready/Merge membutuhkan approval eksplisit pengguna.