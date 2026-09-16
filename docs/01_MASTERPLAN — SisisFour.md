# Masterplan — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 16 September 2026  
**Development aktif:** G3.3.1 — Fondasi BK + Konseling  
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
- BK/Pelanggaran/Konseling/Prestasi.
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
G3.2 Guru/Wali Presensi & Jurnal        CLOSED / MERGED
G3.3 Dashboard Guru/Wali                CLOSED / MERGED
G3.3.1 Fondasi BK + Konseling           ACTIVE
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

### G3.2 Guru/Wali Presensi & Jurnal — CLOSED

G3.2 merged melalui PR #7 pada 16 September 2026 dengan merge commit:

```text
176e5f764850d030968524af47117f259449064c
```

Hasil G3.2:

```text
Presensi Siswa Guru/Wali mobile name-first
H/S/I/A touch-friendly
Jurnal mobile keyboard-friendly
Catatan optional
student exception Sakit/Izin/Alpha per pembelajaran
parent + child save/revisi atomic
laporan parent-level + aggregate no N+1 + Detail lazy-load
SQL schema explicit untuk localhost/hosting
```

G3.2 mempertahankan RBAC, geofence, time-window, dan makna Presensi Siswa resmi. Child Jurnal tidak mengubah tabel `presensi`, Rekap/EWS/Signage Presensi, atau sesi resmi.

Schema delta G3.2 dibawa melalui SQL eksplisit:

```text
database/20260915_G3_2_JURNAL_STUDENT_EXCEPTIONS_LOCALHOST.sql
database/20260915_G3_2_JURNAL_STUDENT_EXCEPTIONS_HOSTING.sql
```

### G3.3 Dashboard Guru/Wali — CLOSED

G3.3 merged melalui PR #8 pada 16 September 2026 dengan merge commit:

```text
06e4e559c045763096058fc889342da78d973314
```

Dashboard Guru memprioritaskan:

```text
Jadwal Hari Ini
Belum Presensi
Belum Jurnal
Selesai
Aksi Cepat permission-aware
jadwal berikutnya / yang sedang berlangsung
riwayat Jurnal singkat
```

Dashboard Guru + Wali mewarisi seluruh experience Guru lalu menambah:

```text
kelas wali + jumlah siswa aktif
rekap H/S/I/A Sesi Awal hari ini
status data belum tersedia bila belum ada row
EWS kelas
ketidakhadiran terbaru
quick link contextual sesuai permission
Aksi Cepat Guru berbasis jadwal
```

Mobile contract G3.3:

```text
4 KPI = 2×2
jadwal mobile = card/list, bukan horizontal table
EWS/ketidakhadiran mobile = list
action Presensi/Jurnal server-generated
Presensi = CTA solid primary bila actionable
Jurnal = CTA solid success bila actionable
jalur Presensi/Jurnal maksimal 1–2 tap
business rule/time-window/authorization tetap server-side
```

Tidak ada schema/database baru pada G3.3.

### G3.3.1 Fondasi BK + Konseling — ACTIVE

Branch aktif:

```text
feat/g3-bk-foundation-konseling-20260916
```

G3.3.1 dikerjakan sebelum G3.4 karena kontrak data BK berubah.

Scope canonical:

```text
Catatan Kasus -> experience Catatan Pelanggaran Siswa
sistem poin pelanggaran dihentikan
Master Pelanggaran hanya nama + kategori
Top Poin retired
Konseling BK menjadi fitur terpisah dan rahasia
akses operasional Konseling = Admin + Operator + BK sesuai permission
Pimpinan, Guru/Wali, Siswa tidak menerima surface/detail Konseling
Konseling create Tahap 1
Konseling update Tahap 2
Kelas searchable -> Siswa searchable dalam kelas
server memvalidasi membership kelas/tahun aktif
Setting Form Konseling memakai setting_sistem, tanpa tabel baru
```

Tahap 1 Konseling:

```text
Siswa & Waktu
Jenis Layanan
```

Tahap 2 Konseling:

```text
Uraian Masalah
Hasil Pembahasan dan Kesepakatan
Rencana Berikutnya
Tanggal Pertemuan Berikutnya
Status Proses/Selesai
```

Permission Konseling:

```text
bk_konseling.view       -> Admin, Operator, BK
bk_konseling.manage     -> Admin, Operator, BK
bk_konseling.export     -> Admin, Operator, BK
bk_konseling.settings   -> Admin, BK
```

Identity/audit Konseling:

```text
created_by -> users.id
akun BK lokal memakai users.id_pegawai -> pegawai.id
id_guru_bk hanya metadata legacy nullable
```

SQL localhost G3.3.1:

```text
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_FIX2_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_FIX3_LOCALHOST.sql
```

**Hosting dikerjakan paling akhir.** Syntax SQL hosting final hanya disusun setelah dump SQL hosting aktual diberikan dan diaudit. File SQL hosting yang sudah ada pada branch bersifat provisional dan **tidak boleh dijalankan** sebelum audit dump hosting.

Finalisasi lintas-role G3.3.1 juga memastikan:

```text
Pimpinan = agregat supervisi tanpa detail Konseling dan tanpa poin
Wali = context Guru, quick link permission-aware, tanpa Konseling
Siswa = self-service milik sendiri, tanpa Konseling dan tanpa poin
mobile Pimpinan/Siswa = list/card, bukan tabel horizontal operasional
```

Detail kontrak BK canonical ada di `07_BK_PRESTASI_KARTU — SisisFour.md`. Detail dashboard lintas-role ada di `08_DASHBOARD_SETTINGS_BACKUP — SisisFour.md`.

G3.4 baru dimulai setelah G3.3.1 lulus schema, static, browser, privacy, dan cross-role regression.

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
G2 CLOSED        → baseline business/admin stabil
G3.1 CLOSED      → mobile foundation tersedia
G3.2 CLOSED      → Presensi/Jurnal Guru/Wali mobile-ready + schema delta tervalidasi
G3.3 CLOSED      → Dashboard Guru/Wali mobile-ready
G3.3.1 PASS      → fondasi BK tanpa poin + Konseling dua tahap siap
G3 PASS          → mobile/WebView UI ready
G4 PASS          → APK distribution gate
```

Setiap merge/release tetap membutuhkan approval eksplisit pengguna.