# Testing, Regression & Release Gate — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 15 September 2026  
**Phase aktif:** G3.2 — Guru/Wali Presensi & Jurnal

> Quality gate dibagi per phase agar regression bisnis, mobile UI, dan Cordova tidak bercampur.

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

### Presensi Siswa

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

### Presensi Mengajar / Jurnal

Wajib diuji:

- Guru diri sendiri tidak perlu memilih Nama Guru berulang bila hanya satu identitas valid.
- jika hanya satu Jadwal valid, workflow dapat langsung memuat Jurnal.
- Admin/Operator tetap dapat memilih Guru lain sesuai scope SEMUA.
- Wali tidak mendapat hak Jurnal karena status Wali; Jurnal tetap berdasarkan Jadwal Guru.
- jadwal Sesi Awal/Akhir/Non Sesi tetap dapat memiliki Jurnal sesuai business rule.
- status Hadir/Izin/Sakit tetap valid.
- materi/keterangan wajib.
- textarea nyaman saat keyboard mobile terbuka.
- status button minimal 44px.
- save button busy state terlihat dan tidak dapat double-submit.
- network/server failure mempertahankan status + materi.
- revisi tetap hanya actor yang diizinkan Service.
- time-window/geofence tetap server-authoritative.
- success hanya setelah server response sukses.

### Viewport focused G3.2

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
H/S/I/A fully reachable
sticky save tidak menutup row terakhir
Jurnal textarea tetap terlihat/scrollable dengan keyboard simulation
SearchableSelect Guru tidak keluar viewport
console clean
```

### Actor minimum G3.2

```text
Guru terjadwal
Guru + Wali
Admin atau Operator untuk smoke compatibility
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
G3.2 PASS      → Guru/Wali Presensi & Jurnal mobile-ready
G3 PASS        → mobile/WebView UI dianggap siap
G4 PASS        → APK dapat masuk distribution gate
```

Setiap merge/release tetap membutuhkan approval eksplisit pengguna.
