# Testing, Regression & Release Gate — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 15 September 2026
**Phase aktif:** G3 — Mobile Role UI

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
- modal/keyboard nyaman;
- filter compact;
- mutation busy guard;
- network failure tidak menghapus input penting;
- server-confirmed success untuk data akademik;
- no uncaught browser error.

## 5. G3.1 — Mobile Foundation Gate

Foundation harus reusable dan tidak page-specific.

Minimum:

```text
safe-area tokens
mobile page spacing/density
compact navbar/page header
44–48px primary touch target
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

Acceptance G3.1:

- tidak merusak Admin desktop;
- tidak ada body horizontal overflow pada viewport wajib;
- tidak ada global `min-width` yang memaksa mobile scroll;
- safe-area token tersedia;
- fixed/sticky element mempertimbangkan safe-area;
- foundation modal tetap kompatibel dengan G2 modal safety;
- touch target global tidak dikecilkan untuk mengejar density;
- no Cordova plugin/project masuk G3.1;
- `Routes.php` tidak berubah kecuali ada kebutuhan endpoint nyata.

## 6. G3.2 — Guru/Wali Presensi & Jurnal

Presensi Siswa:

- Nama siswa menjadi identitas utama;
- NISN tidak menjadi kolom rutin mobile;
- status H/S/I/A punya target sentuh memadai;
- Guru Terjadwal dan Wali tetap mengikuti scope server;
- time window/geofence tetap server-authoritative;
- atomic save;
- duplicate prevention;
- revisi actor sah;
- dirty input tidak hilang pada network failure.

Jurnal:

- jadwal actor benar;
- status/materi;
- duplicate prevention;
- textarea nyaman dengan keyboard;
- mutation busy guard;
- server-confirmed success.

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
- mobile tidak merender ratusan row;
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
G2 CLOSED → baseline main untuk G3
G3 PASS   → mobile/WebView UI dianggap siap
G4 PASS   → APK dapat masuk distribution gate
```

Setiap merge/release tetap membutuhkan approval eksplisit pengguna.
