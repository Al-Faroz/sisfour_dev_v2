# Testing, Regression & Release Gate — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 15 September 2026  
**Phase aktif:** G3.2 — Guru/Wali Presensi & Jurnal

> Quality gate dibagi per phase agar regression bisnis, mobile UI, schema delta, dan Cordova tidak bercampur.

## 1. Static Gate Umum

```powershell
php -l path\file.php
node --check path\file.js
php spark routes
git diff --check
git status --short
```

Tidak boleh ada syntax error, route target hilang, file tidak sengaja terhapus, atau whitespace conflict.

## 2. G2 Gate — CLOSED

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

F06–F14 tidak dibuka ulang pada G3 tanpa regression/blocker baru yang terverifikasi.

## 3. Contract G2 yang Tetap Berlaku

### Tahun Ajaran

```text
initial load = periode aktif
Reset        = periode aktif
manual pilih histori tetap berfungsi bila halaman mendukung histori
export/filter mengikuti periode yang sedang dipilih
```

### Semester & Lifecycle

```text
Ganjil → Genap tahun sama       = Siapkan Genap
Genap → Ganjil tahun berikutnya = Kenaikan/Kelulusan
7 → 8
8 → 9
kelas 9 → Kelulusan
```

### F11 Mapel

- edit nama dengan kode sendiri valid;
- duplicate code ditolak.

### F14

- penempatan/pindah;
- mutasi;
- kelulusan;
- restore terminal lifecycle;
- anti-double-process;
- partial promotion;
- progress per kelas;
- transactional history/status/membership/card.

Semua sudah PASS pada G2.

## 4. G3 Gate — Mobile Role UI

Role prioritas:

```text
Pimpinan
BK
Guru
Guru + Wali
Siswa
```

Viewport wajib:

```text
360×800
375×812
390×844
412×915
768×1024
1024×768
1366×768
```

Acceptance global:

- no body horizontal overflow;
- no horizontal table scroll pada role operasional;
- primary information berbasis Nama;
- NISN/NIP/NIK sekunder;
- KPI 2×2 mobile bila ada KPI;
- touch target utama 44–48px;
- compact control minimum sekitar 40px;
- modal/keyboard nyaman;
- filter compact;
- mutation busy guard;
- network failure tidak menghapus input penting;
- server-confirmed success untuk data akademik;
- no uncaught browser error.

## 5. G3.1 — Mobile Foundation — PASS / MERGED

G3.1 telah lulus static/browser smoke dan merged melalui PR #6.

```text
PR #6          MERGED
merge commit   d10ced5d70ffc68642067aac44feeb6a91cacd29
```

Foundation yang telah diterima:

```text
safe-area tokens
mobile page spacing/density
compact navbar/page header
44px primary / 40px compact touch target
adaptive operational table primitive
primary/meta cell primitive
compact row-action primitive
mobile form/filter primitive
sticky action primitive
fullscreen/scrollable modal compatibility
compact pagination
empty/loading/error compact state
WebView-friendly viewport/overflow baseline
```

G3.1 tidak menambahkan Cordova project/plugin dan tidak merusak Admin desktop.

## 6. G3.2 — Guru/Wali Presensi & Jurnal — ACTIVE

### 6.1 Presensi Siswa

Wajib diuji:

- Nama siswa menjadi identitas utama pada mobile.
- NISN tidak menjadi kolom rutin mobile.
- tabel muat portrait tanpa horizontal scroll.
- status H/S/I/A dapat disentuh nyaman pada 360–412px.
- setiap status memiliki `aria-label`/label yang jelas.
- default seluruh siswa tetap Hadir sesuai business rule.
- Guru Terjadwal hanya kelas/jadwal yang diizinkan Service.
- Wali hanya kelas wali sesuai mapping/scope Service.
- Sesi Awal/Akhir tetap benar.
- time-window dan geofence tetap server-authoritative.
- satu submit kelas tetap atomic.
- duplicate/revision guard tetap benar.
- busy guard mencegah double-submit.
- save gagal/network failure mempertahankan pilihan status di layar.
- deep-link dengan kelas terpilih dapat memuat workflow tanpa tap tambahan yang tidak perlu.
- success hanya muncul setelah server response sukses.

### 6.2 Schema Gate Jurnal G3.2

Migration branch:

```text
app/Database/Migrations/2026-09-15-090000_AddJurnalStudentExceptions.php
```

Sebelum UAT fitur Jurnal baru, pada **database local/staging** wajib:

```text
migration up PASS
presensi_mengajar.catatan tersedia
presensi_mengajar_siswa tersedia
UNIQUE(parent,siswa) tersedia
FK parent cascade tersedia
FK siswa restrict tersedia
index parent/siswa/status tersedia
```

Rollback migration diuji hanya pada database disposable/copy yang aman. Production/hosting tidak dimigrasikan pada fase regression development.

### 6.3 Presensi Mengajar / Jurnal — Base Flow

Wajib diuji:

- Guru diri sendiri tidak perlu memilih Nama Guru berulang bila hanya satu identitas valid.
- jika hanya satu Jadwal valid, workflow dapat langsung memuat Jurnal.
- Admin/Operator tetap dapat memilih Guru lain sesuai scope SEMUA.
- Wali tidak mendapat hak Jurnal karena status Wali; Jurnal tetap berdasarkan Jadwal Guru.
- jadwal Sesi Awal/Akhir/Non Sesi tetap dapat memiliki Jurnal sesuai business rule.
- status Guru Hadir/Izin/Sakit tetap valid.
- materi/keterangan wajib.
- catatan optional dapat disimpan/dikosongkan.
- textarea nyaman saat keyboard mobile terbuka.
- status button minimal 44px.
- save button busy state terlihat dan tidak dapat double-submit.
- revisi tetap hanya actor yang diizinkan Service.
- time-window/geofence tetap server-authoritative.
- success hanya setelah server response sukses.

### 6.4 Exception Siswa pada Jurnal

Kontrak yang wajib dibuktikan runtime:

```text
child status = Sakit / Izin / Alpha
child bukan Presensi Siswa resmi
```

Uji minimal:

- search Nama menemukan siswa roster kelas Jurnal;
- search NISN menemukan siswa yang sama;
- siswa di luar roster kelas/tanggal tidak dapat dipilih dari UI;
- forged request siswa di luar roster ditolak server;
- siswa yang sama dua kali dalam satu payload ditolak;
- status selain Sakit/Izin/Alpha ditolak;
- siswa baru yang ditambahkan wajib memilih S/I/A sebelum save;
- tidak ada default status child diam-diam;
- status Guru `Hadir` boleh memiliki 0..N child;
- status Guru `Izin/Sakit` dengan child ditolak server;
- mengubah UI Guru `Hadir` → `Izin/Sakit` dengan child meminta konfirmasi sebelum mengosongkan list;
- summary badge S/I/A sesuai selected state;
- snapshot Nama/NISN child tersimpan;
- `UNIQUE(id_presensi_mengajar,id_siswa)` terjaga.

### 6.5 Atomicity dan Official Presensi Invariant

Create dan revisi wajib membuktikan:

```text
parent Jurnal + exact child list = satu transaction
```

Uji:

- create parent + beberapa child sukses semua;
- revisi child mengganti exact state lama, tidak meninggalkan stale row;
- kegagalan child tidak meninggalkan parent/child parsial;
- activity log hanya mengikuti transaction sukses;
- tabel `presensi` tidak bertambah/berubah akibat save child Jurnal;
- Rekap/EWS/Signage Presensi resmi tidak berubah akibat child Jurnal.

### 6.6 Laporan Jurnal dan Performance

Listing wajib:

```text
1 row = 1 Jurnal
```

Uji:

- Materi dan Catatan tampil ringkas;
- jumlah S/I/A sesuai child database;
- satu Jurnal dengan banyak child tetap satu row parent;
- pagination tetap berdasarkan jumlah parent Jurnal;
- desktop table normal;
- mobile memakai card/list tanpa horizontal scroll;
- `Detail` lazy-load menampilkan exact child Name/NISN/status;
- Detail actor scope tetap server-side;
- tidak ada N+1 child query per row;
- aggregate child dilakukan batch untuk parent IDs page aktif;
- network failure pada listing/detail memberi state gagal yang jelas.

### 6.7 Network Failure / Mutation Safety

Jika network/server gagal saat save:

```text
Presensi Siswa → pilihan H/S/I/A tetap di layar
Jurnal         → status Guru + materi + catatan + daftar siswa S/I/A tetap di layar
```

Tidak ada offline queue atau sukses palsu.

### 6.8 Viewport Focused G3.2

Minimum runtime smoke:

```text
360×800
390×844
412×915
768×1024
1366×768
```

Pada 360/390/412 wajib cek:

```text
body overflow = none
Presensi table horizontal scroll = none
H/S/I/A Presensi fully reachable
sticky save tidak menutup row terakhir
Jurnal Materi/Catatan tetap usable dengan keyboard simulation
search siswa tidak keluar viewport
S/I/A child controls reachable
SearchableSelect Guru tidak keluar viewport
report card/list tidak horizontal-scroll
modal Detail Jurnal vertical-scroll dan action reachable
console clean
```

### 6.9 Actor Minimum G3.2

```text
Guru terjadwal
Guru + Wali
Admin atau Operator untuk smoke compatibility + revisi
```

Jika data memungkinkan, uji satu Guru biasa dan satu Guru yang juga Wali agar kedua context terbukti tidak saling menimpa.

## 7. G3.3 — Dashboard Guru/Wali

Minimum:

```text
4 KPI = 2×2
Quick Action = 2×2 bila relevan
3–5 item penting
Presensi/Jurnal maksimal 1–2 tap
Wali context bukan role baru
```

## 8. G3.4 — BK

Cek:

- Kasus/Tindak Lanjut;
- EWS;
- Pelanggaran;
- Prestasi;
- dashboard BK;
- no wide operational table;
- primary identity by Name;
- busy guard + network failure state.

## 9. G3.5 — Pimpinan

Prioritas:

```text
kelas belum presensi
jadwal belum jurnal
EWS
kasus BK / exception
trend singkat
```

Dashboard adalah launcher/monitoring ringkas, bukan laporan penuh.

## 10. G3.6 — Siswa

Pola self-service:

```text
status kehadiran hari ini
rekap bulan ini
quick action
kartu pelajar
prestasi
riwayat/kasus sesuai scope
profil
```

Tidak memakai data-grid Admin sebagai UX utama.

## 11. G3.7 — Global Mobile Sweep

Audit ulang semua role prioritas terhadap:

```text
body overflow
operational table overflow
page padding
navbar/header density
filter density
modal
sticky action
safe-area
touch target
empty/loading/error
pagination
name-first identity
```

## 12. G3.8 — Viewport/WebView Readiness Regression

Uji seluruh viewport wajib dan browser mobile. Fokus WebView readiness:

- safe-area;
- keyboard/focus;
- modal/offcanvas/dropdown layering;
- session-expiry Fetch UX;
- network failure state;
- internal/external link behavior yang bisa diuji dari Web;
- no dependency pada Cordova plugin sebelum G4.

Real Android Cordova behavior tetap gate G4.

## 13. Auth Web/API Baseline

Web:

- valid/invalid login;
- inactive account;
- lockout policy;
- logout;
- session DB;
- multi-role;
- CSRF mutation.

API:

- login/me/refresh/logout;
- missing/invalid token;
- effective permission/scope;
- version/maintenance response.

Cordova wrapper tidak otomatis mengganti Web session dengan JWT.

## 14. RBAC

Minimum actor:

```text
Admin
Operator
Pimpinan
BK
Guru
Guru + Wali
Siswa
multi-role relevan
```

Cek menu, direct URL, read, mutation, target scope, contextual Wali.

## 15. Network / Mutation Safety

Tidak ada silent offline queue canonical untuk:

```text
Presensi
Jurnal
Kasus
Prestasi
```

Network failure = gagal/tertunda, bukan sukses palsu.

Mutation wajib:

```text
button disabled
spinner / Menyimpan...
server response
success/error
restore state pada gagal
```

## 16. Performance

- bounded DB query;
- index digunakan pada query besar;
- no N+1;
- pagination;
- dashboard hanya data ringkas;
- mobile tidak merender ratusan row tanpa kebutuhan;
- chart mobile ringkas;
- Kartu/PDF tetap dalam memory limit.

Khusus laporan Jurnal child G3.2:

```text
parent page query
+ 1 aggregate child query untuk seluruh parent pada page
+ detail child hanya saat user meminta
```

Dilarang menjalankan satu child query untuk setiap row parent.

## 17. G4 Gate — Cordova APK

Sebelum build final:

- G3 Web/mobile PASS;
- Cordova architecture spike PASS;
- real Android WebView test;
- safe-area/status bar;
- soft keyboard;
- Android Back;
- session/login behavior;
- geolocation permission/device GPS;
- network/offline state;
- file preview/download/share;
- internal/external link routing;
- maintenance behavior;
- no sensitive debug logging;
- signed build + multi-device regression.

## 18. Phase Release Rule

```text
G2 CLOSED      → business/admin baseline
G3.1 CLOSED    → mobile foundation baseline
G3.2 PASS      → Guru/Wali Presensi & Jurnal mobile-ready + schema delta Jurnal validated
G3 PASS        → mobile/WebView UI dianggap siap
G4 PASS        → APK dapat masuk distribution gate
```

Setiap merge/release tetap membutuhkan approval eksplisit pengguna.
