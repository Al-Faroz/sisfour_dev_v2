# Masterplan — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 17 September 2026  
**Development aktif:** G3.3.1 rework — periodic Tahun Ajaran + Konseling follow-up 1:N / local gate pending  
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

UI utama tetap View CI4/Sneat yang sama; APK tidak menjadi SPA kedua.

## 3. Role

```text
admin
operator
pimpinan
bk
guru
siswa
```

Wali Kelas adalah context Guru, bukan role baru.

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
7 → 8
8 → 9
kelas 9 → Kelulusan
```

Lifecycle menjaga membership/history/status/Kartu secara transactional. Histori terminal tidak dihapus.

## 8. UI/UX Global

Hirarki:

```text
13 CI4 + Sneat Global
→ 11 UI/UX SisisFour
→ 14 Mobile & Cordova UI/UX
→ 11 Role Experience
```

Kontrak global yang ditegaskan 17 September 2026:

```text
filter desktop padat tidak dipaksa 1 baris
periodic/history table -> filter Tahun Ajaran
Tahun Ajaran default/reset -> periode aktif
export periodik -> mengikuti periode terpilih
create operational -> Service snapshot periode aktif
no body horizontal overflow
no horizontal table scroll role operasional
name-first identity
```

Tabel global/non-periodik tidak diberi filter Tahun Ajaran palsu.

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

Baseline decisions yang tetap berlaku:

```text
Catatan Kasus -> Catatan Pelanggaran Siswa
poin pelanggaran retired
Master Pelanggaran nama + kategori
Top Poin retired
Konseling BK terpisah dan rahasia
Settings Form Konseling memakai setting_sistem
created_by -> users.id
akun BK aktual -> users.id_pegawai -> pegawai.id
```

Permission:

```text
bk_konseling.view       -> Admin, Operator, BK
bk_konseling.manage     -> Admin, Operator, BK
bk_konseling.export     -> Admin, Operator, BK
bk_konseling.settings   -> Admin, BK
```

Pimpinan/Guru/Wali/Siswa tidak menerima detail/surface Konseling.

### 10.1 Tahun Ajaran Periodik BK

Catatan Pelanggaran, Konseling, dan Prestasi adalah surface periodik.

```text
listing default = Tahun Ajaran aktif
Reset           = Tahun Ajaran aktif
history         = selectable
export          = mengikuti period filter
create baru     = selalu snapshot Tahun Ajaran aktif di server
```

Rework menambah `id_tahun` pada `catatan_kasus` dan `catatan_prestasi`; `konseling_bk` sudah memilikinya.

### 10.2 Konseling Parent

Tahap 1:

```text
Kelas → Siswa → Tanggal → Pertemuan ke-
Bentuk Layanan → Cara Hadir → Bidang → Topik
status awal Proses
```

Tahap 2 parent/pertemuan awal:

```text
Uraian Masalah
Hasil Pembahasan & Kesepakatan
Rencana Berikutnya
Tanggal Pertemuan Berikutnya
Status Proses/Selesai
```

### 10.3 Tindak Lanjut Konseling 1:N

Keputusan 17 September mengganti model satu-rencana-lanjutan menjadi histori:

```text
konseling_bk 1:N tindak_lanjut_konseling_bk
```

Setiap follow-up mempunyai tanggal, perkembangan, hasil/kesepakatan, rencana, tanggal berikutnya, status, dan actor audit.

Canonical detail:

```text
Identitas
→ Hasil Pertemuan Awal
→ Riwayat Tindak Lanjut
→ Form Tambah/Edit Tindak Lanjut
```

Tidak ada Delete parent Konseling dan tidak ada Delete Tindak Lanjut Konseling.

### 10.4 Historical Rencana

Nilai Rencana lama yang dihapus dari Settings tetap dapat dipertahankan pada record yang sudah menyimpannya sebagai `(tersimpan)`, tetapi tidak menjadi pilihan global lagi.

Parent historical-Rencana focused local UAT sebelumnya PASS. Follow-up 1:N wajib mengulang invariant yang sama.

### 10.5 SQL

Baseline yang sudah PASS local/hosting:

```text
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_FIX3_LOCALHOST.sql
database/20260916_G3_3_1_BK_FOUNDATION_KONSELING_HOSTING.sql
```

Rework local:

```text
database/20260917_G3_3_1_BK_PERIOD_YEAR_COUNSELING_FOLLOWUP_LOCALHOST.sql
```

Target delta:

```text
catatan_kasus.id_tahun
catatan_prestasi.id_tahun
tindak_lanjut_konseling_bk
```

Belum ada hosting SQL untuk rework ini.

### 10.6 Current Gate

```text
baseline G3.3.1 local/hosting                   PASS
parent historical-Rencana focused local UAT    PASS
17 Sep source implementation                    IMPLEMENTED
17 Sep canonical docs sync                      IN PROGRESS
17 Sep localhost delta SQL                      PREPARED
17 Sep localhost SQL execution                  PENDING
17 Sep local runtime UAT                        PENDING
17 Sep final static gate                        PENDING
17 Sep hosting dump audit/delta/re-smoke        NOT STARTED
PR #9                                           DRAFT / BELUM MERGE
```

## 11. G3 Roadmap Setelah PR #9

```text
G3.4 BK Workflow + Dashboard BK
G3.5 Pimpinan
G3.6 Siswa
G3.7 Global Mobile Sweep
G3.8 Viewport/WebView Readiness
G4   Cordova APK
```

G3.4 memakai foundation final:

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

## 12. G4 — Cordova APK

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

## 13. Release Rule

```text
G3.3.1 PASS hanya setelah:
SSOT sync
+ localhost SQL/UAT
+ final static gate
+ hosting dump audit/delta execution
+ focused hosting smoke
+ explicit Ready approval
+ explicit Merge approval
```

Setiap merge/release membutuhkan approval eksplisit pengguna.