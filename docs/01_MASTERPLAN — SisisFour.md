# Masterplan — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 15 September 2026  
**Development aktif:** G3.2 — Guru/Wali Presensi & Jurnal  
**Target:** Web + Android Cordova

## 1. Sistem

SisisFour adalah Sistem Informasi Manajemen Madrasah MTsN 4 Jombang untuk akademik, presensi, monitoring, BK, kartu pelajar, personalia, pelaporan, dan self-service pengguna.

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

Sumber UI utama tetap View CI4/Sneat yang sama. APK tidak direncanakan sebagai duplikasi SPA penuh.

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

Role experience canonical:

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

Untuk penggunaan sehari-hari:

```text
Nama lengkap = identitas visual utama
NISN/NIP/NIK = identifier sekunder
```

Search tetap mendukung nama dan identifier untuk pencocokan/verifikasi.

## 5. Modul

- Auth Web + API.
- RBAC + menu dinamis.
- Dashboard per experience.
- Master Guru/Pegawai/Siswa/Kelas/Tahun/Mapel.
- Mapping Wali + Jadwal Guru.
- Manajemen Siswa.
- Presensi Siswa + Jurnal Mengajar.
- Laporan/Matrix/EWS/Signage.
- BK/Pelanggaran/Prestasi.
- Kartu Pelajar.
- Profile/Personalia/Portofolio.
- Settings/Maintenance/Backup/Log.
- Android Cordova pada phase G4.

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

Siapkan Genap menyalin struktur akademik yang diperbolehkan secara transactional dan tidak menyalin Presensi/Jurnal historis.

Kenaikan normal hanya memproses:

```text
7 → 8
8 → 9
```

Kelas 9 menggunakan workflow Kelulusan. Kenaikan menjaga membership/history target secara transactional dan memiliki guard anti-double-process.

Status siswa:

```text
Aktif
Lulus
Pindah
Keluar
```

Lifecycle terminal menjaga history, current membership, status, dan kartu secara konsisten. Restore terminal lifecycle hanya diperbolehkan untuk event terminal terbaru ketika exact periode sumber terminal masih aktif; histori terminal tidak dihapus.

Status F06–F14: **PASS / CLOSED pada G2**.

## 8. UI/UX Hierarchy

```text
13 CI4 + Sneat Global
→ 11 UI/UX SisisFour
→ 14 Mobile & Cordova UI/UX
→ 11 Role Experience
```

Pimpinan/BK/Guru/Wali/Siswa adalah mobile-first pada G3.

Untuk halaman yang memiliki selector Tahun Ajaran sebagai filter baca/histori:

```text
default = periode aktif
Reset   = periode aktif
histori = tetap selectable bila didukung
```

UI tidak perlu menampilkan helper/alert yang hanya menjelaskan default tersebut. Workflow current-state mengikuti periode aktif langsung dari business context.

## 9. G2 — CLOSED

G2 telah selesai dan merged ke `main` melalui PR #5 pada 15 September 2026.

```text
G2.1 Repository Hygiene     PASS
G2.2 Static Gate            PASS
G2.3 Business Regression    PASS
G2.4 Browser Regression     PASS
G2.5 Closure Review         PASS
PR #5                       MERGED
```

Merge commit:

```text
375766c07f3856515a71ffdb07f3681c3047ca31
```

Scope G2 tidak dibuka ulang pada G3 kecuali ditemukan regression/blocker nyata.

## 10. G3 — Mobile Role UI

Urutan implementasi:

```text
G3.1 Mobile foundation                  CLOSED / MERGED
G3.2 Guru/Wali Presensi & Jurnal        ACTIVE
G3.3 Dashboard Guru/Wali
G3.4 BK workflow + Dashboard BK
G3.5 Pimpinan monitoring
G3.6 Siswa self-service
G3.7 global mobile sweep
G3.8 viewport/WebView readiness regression
```

Target utama:

```text
no body horizontal overflow
no horizontal table scroll untuk role operasional
compact spacing
touch target 44–48px
name-first identity
safe-area/keyboard ready
mobile filter/action density yang konsisten
```

### G3.1 Mobile Foundation — CLOSED

G3.1 merged melalui PR #6 dengan merge commit:

```text
d10ced5d70ffc68642067aac44feeb6a91cacd29
```

Foundation menyediakan role-aware shell, safe-area, mobile density, touch targets, adaptive table, sticky actions, KPI 2×2, compact pagination, dan overflow baseline.

### G3.2 Guru/Wali Presensi & Jurnal — ACTIVE

Branch aktif:

```text
feat/g3-guru-wali-presensi-jurnal-20260915
```

Fokus Presensi Siswa:

```text
mobile name-first
H/S/I/A touch-friendly
no horizontal table scroll
sticky/busy-safe save
network failure mempertahankan input
Guru/Wali scope tetap Service-authoritative
```

Fokus Jurnal:

```text
mobile keyboard-friendly
self Guru tidak perlu memilih identitas berulang
Jadwal tunggal dapat dipercepat
Materi wajib
Catatan optional
siswa exception per pembelajaran: Sakit/Izin/Alpha
search siswa Name + NISN hanya roster kelas/tanggal Jurnal
parent + child save/revisi atomic
laporan tetap 1 row per Jurnal
aggregate child no N+1 + Detail lazy-load
server-confirmed mutation success
```

G3.2 mempertahankan RBAC, geofence, time-window, dan makna Presensi Siswa resmi. Perluasan domain yang disetujui terbatas pada Jurnal:

```text
presensi_mengajar.catatan
presensi_mengajar_siswa
```

Child Jurnal tidak mengubah tabel `presensi`, tidak masuk Rekap/EWS/Signage Presensi, dan hanya menyimpan exception pembelajaran.

Schema delta di branch dibawa melalui migration reversible dan hanya diuji pada local/staging sampai G3.2 PASS. Database hosting/production tidak diubah tanpa approval deploy eksplisit.

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

Cordova wrapper tidak otomatis mengganti Web session auth dengan JWT. Detail ada di `16_MOBILE_CORDOVA — SisisFour.md`.

## 12. Release Rule

```text
G2 CLOSED      → baseline business/admin stabil
G3.1 CLOSED    → mobile foundation tersedia
G3.2 PASS      → Presensi/Jurnal Guru/Wali mobile-ready + schema delta Jurnal tervalidasi
G3 PASS        → mobile/WebView UI ready
G4 PASS        → APK distribution gate
```

Setiap merge/release tetap membutuhkan approval eksplisit pengguna.
