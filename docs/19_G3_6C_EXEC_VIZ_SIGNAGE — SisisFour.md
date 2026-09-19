# 19 — G3.6C Executive Visualization & EWS Signage

## 1. Baseline

```text
main = f6f30ceaf070f342d609c322905ee77dc33f3e6f
G3.6B PTSP = CLOSED / MERGED — PR #14
branch = feat/g3-6c-exec-viz-signage-20260919
```

G3.6C dikerjakan sebelum G3.7 Global Mobile Sweep agar seluruh surface UI baru ikut regression mobile global.

## 2. Scope

G3.6C terdiri dari tiga deliverable:

```text
A. Shortcut EWS Signage pada Dashboard Admin / Operator / Pimpinan
B. Refresh layout + rotasi EWS Signage berdasarkan TemplateSIGNAGE.pdf
C. Halaman Statistik cross-domain + Export PDF
```

Tidak ada perubahan domain Konseling, tidak ada pengaktifan kembali Poin Pelanggaran, dan tidak ada tabel snapshot Statistik/Signage.

## 3. Shortcut EWS Signage

Shortcut hanya muncul di dashboard experience:

```text
Admin
Operator
Pimpinan
```

Target:

```text
/signage
target=_blank
rel=noopener
```

Route Signage sendiri tetap OPEN / PUBLIC. Shortcut dashboard bukan security boundary.

## 4. EWS Signage

Route tetap:

```text
GET /signage
GET /signage/data
```

Tanpa login/session/role/permission/token.

Reference presentation = TemplateSIGNAGE.pdf:

```text
Header compact
Jumlah dan Persentase Kehadiran Siswa
3 panel:
- EWS Presensi Siswa
- Kelas Belum Presensi
- Jadwal Belum Jurnal
Footer compact
```

Layout utama stabil; rotasi terjadi di dalam panel agar data panjang tetap terbaca.

### 4.1 Ringkasan Kehadiran

Sumber = `presensi`, Tahun Ajaran aktif, tanggal hari ini, `sesi='Sesi Awal'`.

Tampilkan:

```text
Hadir  jumlah + %
Sakit  jumlah + %
Izin   jumlah + %
Alpha  jumlah + %
coverage kelas = sudah / wajib
```

Denominator persentase = jumlah record siswa Sesi Awal yang sudah tercatat hari itu. Coverage kelas wajib ditampilkan agar persentase tidak dibaca sebagai coverage seluruh madrasah ketika input kelas belum lengkap.

### 4.2 Panel EWS Presensi Siswa

Sumber = `presensi`, Sesi Awal, 14 hari terakhir.

Rotasi internal 15 detik:

```text
Sakit tertinggi
Izin tertinggi
Alpha tertinggi
```

Masing-masing ranking:

```text
total DESC
nama ASC
maksimal 20 siswa
field public = nama siswa, kelas, total status
```

Threshold EWS internal `>=3 Alpha / 14 hari` tetap berlaku untuk indikator EWS internal. Ranking Signage S/I/A adalah monitoring dan tidak dibatasi threshold Alpha.

### 4.3 Panel Kelas Belum Presensi

Definisi:

```text
jadwal_guru aktif hari ini
AND sesi = Sesi Awal
AND id_tahun = aktif
MINUS kelas yang sudah mempunyai record presensi Sesi Awal hari ini
```

Tampil:

```text
Kelas
Wali Kelas
```

Jika melebihi kapasitas panel -> auto paging/rotation, tanpa pagination manual.

### 4.4 Panel Jadwal Belum Jurnal

Definisi:

```text
jadwal aktif hari ini
AND now > jam_selesai + 15 menit
AND belum ada presensi_mengajar untuk id_jadwal + tanggal hari ini
```

Tampil:

```text
Guru
Kelas
Mata Pelajaran
Jam
```

Jika melebihi kapasitas panel -> auto paging/rotation.

### 4.5 Refresh

```text
data refresh = 5 menit
rotasi panel = 15 detik
jam layar = 1 detik
```

Data refresh memakai fetch, bukan location.reload().

## 5. Statistik

### 5.1 Access Boundary

Halaman Statistik hanya untuk:

```text
Admin
Operator
Pimpinan
```

Capability:

```text
statistik.view
statistik.export_pdf
```

Scope = SEMUA.

Role lain default DENY.

Route:

```text
GET /statistik
GET /statistik/data
GET /statistik/export/pdf
```

Semua route berada dalam authenticated group dan menggunakan permission filter + Service authorization.

### 5.2 Period Context

Global filters:

```text
Tahun Ajaran = wajib
Rentang = Seluruh Periode / Bulan Ini / 30 Hari / Custom
Tingkat = opsional
Kelas = opsional
```

Default:

```text
Tahun Ajaran aktif
Rentang = Seluruh Periode
```

`tahun_ajaran` tidak memiliki tanggal mulai/selesai, sehingga sistem tidak boleh mengarang batas semester. `Seluruh Periode` berarti filter `id_tahun` tanpa batas tanggal tambahan.

Filter Kelas/Tingkat hanya diterapkan pada domain yang memiliki relasi siswa/kelas yang sah. PTSP tetap aggregate global karena tidak memiliki identity kelas.

### 5.3 Dataset V1

```text
Executive
- siswa terdaftar pada Tahun terpilih
- guru
- pegawai
- kelas
- mata pelajaran
- kartu pelajar aktif

Komposisi Siswa
- per tingkat
- per jenis kelamin

Presensi Siswa
- H/S/I/A
- tren H/S/I/A
- persentase hadir per kelas

EWS
- jumlah Alpha >=3 / 14 hari
- top Sakit / Izin / Alpha 14 hari

Pembelajaran
- kewajiban/sudah/belum Presensi Mengajar/Jurnal hari ini
- distribusi status Presensi Mengajar
- tren record Jurnal/Presensi Mengajar

Pelanggaran
- jumlah catatan
- kategori
- tren
- TANPA poin

Prestasi
- jumlah
- tingkat prestasi
- tren

UKS
- jumlah kunjungan
- jumlah CKG
- jumlah rujukan klinik
- distribusi hasil kunjungan
- aggregate only; tidak ada detail kesehatan individual

PTSP
- Layanan per status
- Pengaduan per status
- Pengaduan per klasifikasi
- Polling rata-rata + distribusi score
- aggregate only; tidak ada PII/raw record

Mobilitas Siswa
- status latest per siswa pada Tahun terpilih
```

### 5.4 Privacy

Konseling BK tidak pernah masuk payload Statistik karena Pimpinan adalah actor Statistik tetapi Access Boundary Konseling melarang Pimpinan.

UKS dan PTSP hanya aggregate-safe.

## 6. Presentation

Halaman Statistik memakai ApexCharts lokal yang sudah ada di repo. Tidak memakai CDN.

Section:

```text
Filter Global
Executive Summary
Komposisi Siswa
Presensi & EWS
Pembelajaran
Pembinaan
UKS
PTSP
Mobilitas Siswa
```

## 7. Export PDF

Export mengikuti filter aktif.

Target:

```text
A4 Landscape
nama sekolah
Tahun Ajaran
filter/rentang
timestamp export
KPI
visualisasi
ringkasan tabel
multi-page bila perlu
```

Server membentuk visual PDF dari dataset authoritative yang sama. Tidak menerima screenshot/chart payload client sebagai source of truth.

Export dicatat ke log_activity.

## 8. Persistence Delta

Tidak ada tabel baru.

Tambah:

```text
permissions:
- statistik.view
- statistik.export_pdf

role_permissions:
Admin      view + export
Operator   view + export
Pimpinan   view + export

menu:
Statistik -> /statistik

role_menus:
Admin
Operator
Pimpinan
```

Expected post-local-SQL:

```text
tables             45
permissions        68
role_permissions   229
menus              51
role_menus         176
```

SQL localhost:

```text
database/20260919_G3_6C_EXEC_VIZ_LOCALHOST.sql
```

Hosting SQL belum disusun. Harus menunggu local SQL + UAT + post-SQL dump PASS dan fresh hosting dump read-only audit.

## 9. Gate

```text
Contract / SSOT                 LOCKED by user approval
Implementation                  IN PROGRESS
Local SQL                       PREPARED
Local SQL execution             PASS / user evidence
Static terminal gate            PENDING
Local runtime UAT               PENDING
Post-SQL local dump audit       PENDING
Fresh hosting dump audit        PENDING
Hosting mutation/deploy         NOT AUTHORIZED
PR Ready                        NOT AUTHORIZED
Merge                           NOT AUTHORIZED
```
