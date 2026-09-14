# Masterplan — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 14 September 2026  
**Development aktif:** G2 Stabilization  
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

Status siswa:

```text
Aktif
Lulus
Pindah
Keluar
```

Lifecycle terminal menjaga history, current membership, status, dan kartu secara konsisten.

## 8. UI/UX Hierarchy

```text
13 CI4 + Sneat Global
→ 11 UI/UX SisisFour
→ 14 Mobile & Cordova UI/UX
→ 11 Role Experience
```

Pimpinan/BK/Guru/Wali/Siswa adalah mobile-first pada phase G3.

## 9. G2 — Current

G2 menyelesaikan:

```text
F06–F14 integrity/business fixes
Admin UI stabilization yang sudah masuk branch
login/branding regression
static + browser regression
document sync
```

G2 tidak menerima full mobile redesign atau Cordova implementation.

## 10. G3 — Mobile Role UI

Setelah G2 closed:

```text
mobile foundation
Guru/Wali Presensi & Jurnal
Dashboard Guru/Wali
BK
Pimpinan
Siswa
global mobile sweep
WebView-readiness regression
```

Target utama:

```text
no horizontal table scroll untuk role operasional
compact spacing
touch target 44–48px
name-first identity
safe-area/keyboard ready
```

## 11. G4 — Cordova APK

Setelah G3 stable:

```text
architecture spike
Android project/config
session/WebView verification
Back/keyboard/safe-area
geolocation
network state
file/download/share
external links
device regression
signed package/distribution
```

Detail ada di `16_MOBILE_CORDOVA — SisisFour.md`.

## 12. Release Rule

```text
G2 PASS → merge G2
G3 PASS → mobile/WebView UI ready
G4 PASS → APK distribution gate
```

Setiap merge/release membutuhkan approval eksplisit pengguna.
