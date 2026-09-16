# Masterplan — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 16 September 2026  
**Development aktif:** G3.3.1 closure patch — focused re-smoke pending  
**Target:** Web + Android Cordova

## 1. Sistem

SisisFour adalah Sistem Informasi Manajemen Madrasah MTsN 4 Jombang untuk akademik, Presensi, monitoring, BK, Kartu Pelajar, personalia, pelaporan, dan self-service pengguna.

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
API /api/*
```

UI utama tetap View CI4/Sneat yang sama. APK tidak direncanakan sebagai SPA kedua.

## 3. Role

```text
admin
operator
pimpinan
bk
guru
siswa
```

Wali Kelas adalah context Guru berdasarkan mapping aktif, bukan role baru.

Role experience:

```text
Admin
Operator
Pimpinan
BK
Guru
Guru + Wali
Siswa
```

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
- BK: Catatan Pelanggaran, Tindak Lanjut, Konseling, Prestasi.
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

## 7. Semester & Lifecycle

```text
Ganjil → Genap tahun sama = Siapkan Genap
Genap → Ganjil tahun berikutnya = Kenaikan/Kelulusan
```

Kenaikan normal:

```text
7 → 8
8 → 9
```

Kelas 9 memakai Kelulusan. Lifecycle menjaga membership/history/status/Kartu secara transactional; restore terminal hanya untuk event terbaru ketika exact periode sumber masih aktif. Histori terminal tidak dihapus.

Status siswa:

```text
Aktif
Lulus
Pindah
Keluar
```

F06–F14: **PASS / CLOSED pada G2**.

## 8. UI/UX Hierarchy

```text
13 CI4 + Sneat Global
→ 11 UI/UX SisisFour
→ 14 Mobile & Cordova UI/UX
→ 11 Role Experience
```

Pimpinan/BK/Guru/Wali/Siswa mobile-first pada G3.

Selector Tahun Ajaran untuk baca/histori:

```text
default = periode aktif
Reset   = periode aktif
histori = selectable bila didukung
```

Workflow current-state memakai periode aktif langsung dari business context.

## 9. G2 — CLOSED / MERGED

PR #5 merged 15 September 2026.

```text
G2.1 Repository Hygiene     PASS
G2.2 Static Gate            PASS
G2.3 Business Regression    PASS
G2.4 Browser Regression     PASS
G2.5 Closure Review         PASS
```

Merge commit:

```text
375766c07f3856515a71ffdb07f3681c3047ca31
```

## 10. G3 — Mobile Role UI

Urutan:

```text
G3.1 Mobile foundation                  CLOSED / MERGED
G3.2 Guru/Wali Presensi & Jurnal        CLOSED / MERGED
G3.3 Dashboard Guru/Wali                CLOSED / MERGED
G3.3.1 Fondasi BK + Konseling           CLOSURE PATCH / RE-SMOKE PENDING
G3.4 BK workflow + Dashboard BK         NEXT setelah PR #9 merge
G3.5 Pimpinan monitoring
G3.6 Siswa self-service
G3.7 global mobile sweep
G3.8 viewport/WebView readiness regression
```

Target G3:

```text
no body horizontal overflow
no horizontal table scroll role operasional
compact spacing
touch target 44–48px
name-first identity
safe-area/keyboard ready
mobile filter/action density konsisten
```

### G3.1 — CLOSED

PR #6 merge commit:

```text
d10ced5d70ffc68642067aac44feeb6a91cacd29
```

Foundation: role-aware shell, safe-area, mobile density, touch targets, adaptive table/list, sticky action, KPI 2×2, compact pagination, overflow baseline.

### G3.2 — CLOSED

PR #7 merge commit:

```text
176e5f764850d030968524af47117f259449064c
```

Hasil:

```text
Presensi Guru/Wali name-first
H/S/I/A touch-friendly
Jurnal keyboard-friendly
Catatan optional
exception siswa S/I/A per pembelajaran
parent + child save/revisi atomic
laporan parent-level + aggregate no N+1 + Detail lazy-load
SQL local + hosting validated
```

Child Jurnal tidak mengubah Presensi resmi/EWS/Signage.

### G3.3 — CLOSED

PR #8 merge commit:

```text
06e4e559c045763096058fc889342da78d973314
```

Dashboard Guru:

```text
Jadwal Hari Ini
Belum Presensi
Belum Jurnal
Selesai
Aksi Cepat permission-aware
jadwal berikutnya / sedang berlangsung
riwayat Jurnal ringkas
```

Guru+Wali menambah kelas wali, jumlah siswa, H/S/I/A Sesi Awal, EWS, absence terbaru, quick link contextual.

Mobile:

```text
4 KPI = 2×2
jadwal/EWS/absence = card/list
Presensi/Jurnal maksimal 1–2 tap
business/time-window/authorization server-side
```

### G3.3.1 — Fondasi BK + Konseling

Branch:

```text
feat/g3-bk-foundation-konseling-20260916
```

Kontrak final:

```text
Catatan Kasus -> Catatan Pelanggaran Siswa
poin pelanggaran dihentikan
Master Pelanggaran hanya nama + kategori
Top Poin retired
Konseling BK terpisah dan rahasia
Konseling create Tahap 1
Konseling update Tahap 2
Kelas -> Siswa divalidasi Tahun Ajaran aktif
Settings Form Konseling memakai setting_sistem
```

Tahap 1:

```text
Kelas → Siswa → Tanggal → Pertemuan ke-
Bentuk Layanan → Cara Hadir → Bidang → Topik
status awal Proses
```

Tahap 2:

```text
Uraian Masalah
Hasil Pembahasan & Kesepakatan
Rencana Berikutnya
Tanggal Pertemuan Berikutnya
Status Proses/Selesai
```

Permission:

```text
bk_konseling.view       -> Admin, Operator, BK
bk_konseling.manage     -> Admin, Operator, BK
bk_konseling.export     -> Admin, Operator, BK
bk_konseling.settings   -> Admin, BK
```

Pimpinan/Guru/Wali/Siswa tidak menerima surface/detail/widget Konseling.

Identity:

```text
created_by -> users.id
akun BK aktual -> users.id_pegawai -> pegawai.id
id_guru_bk nullable legacy metadata
```

Final SQL:

```text
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_FIX3_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_HOSTING.sql
```

Broad gates yang telah PASS:

```text
application/local static + UAT
Catatan Pelanggaran + Tindak Lanjut
export Pelanggaran 2 sheet + Kelas
Prestasi create/edit + export Kelas
Konseling Tahap 1/Tahap 2
Settings persistence/backend validation
cross-role privacy/responsive
hosting dump audit
hosting SQL execution
broad hosting smoke UAT
```

#### Closure patch setelah full docs/source audit

Ditemukan edge-case preservasi data historis:

```text
record lama menyimpan Rencana X
→ X dihapus dari Pengaturan Form
→ record lama harus tetap menampilkan dan boleh mempertahankan X
```

Patch hanya menyentuh Service + JS Konseling dan **tidak mengubah schema/SQL**. Nilai lama ditampilkan sebagai `(tersimpan)` hanya pada record terkait; record lain tetap tidak boleh memakai opsi yang sudah dinonaktifkan.

Status patch:

```text
source patched
canonical docs sync in branch
local static/focused UAT pending
focused hosting re-smoke pending
```

PR #9 tidak boleh Ready/Merge sebelum focused re-smoke PASS dan user memberi approval eksplisit.

### G3.4 — NEXT setelah PR #9 merge

Dashboard/Workflow BK memakai foundation final:

```text
Konseling Proses / follow-up terdekat
Catatan Pelanggaran terbaru/berat tanpa poin
Tindak Lanjut perlu perhatian
EWS
Prestasi
quick action permission-aware
mobile-first / no wide operational table
privacy Konseling tetap ketat
```

## 11. G4 — Cordova APK

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

Cordova wrapper tidak otomatis mengganti Web session dengan JWT.

## 12. Release Rule

```text
G2 CLOSED        → baseline business/admin stabil
G3.1 CLOSED      → mobile foundation tersedia
G3.2 CLOSED      → Guru/Wali Presensi/Jurnal validated
G3.3 CLOSED      → Dashboard Guru/Wali validated
G3.3.1 PASS      → setelah closure focused re-smoke PASS
G3 PASS          → mobile/WebView UI ready
G4 PASS          → APK distribution gate
```

Setiap merge/release membutuhkan approval eksplisit pengguna.