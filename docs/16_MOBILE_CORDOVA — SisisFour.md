# Cordova Packaging & Integration — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 14 September 2026  
**Implementation Phase:** G4, setelah G3 Mobile UI selesai

> SisisFour akan dibungkus menjadi Android APK dengan Apache Cordova. Dokumen ini mengatur integrasi teknis APK. UI/UX mobile ada di `14_SISFOUR_MOBILE_CORDOVA_UI_UX_STANDARD.md`.

## 1. Target Architecture

SisisFour tidak membangun ulang seluruh aplikasi menjadi SPA mobile kedua.

Target:

```text
CI4/Sneat Web Application
        ↓
responsive mobile UI
        ↓
Cordova Android WebView wrapper
```

Server tetap menjadi source of truth untuk auth, RBAC, scope, business rule, validation, transaction dan persistence.

## 2. Separation of Phases

```text
G2  Master Data/lifecycle fixing + stabilization
G3  Mobile role UI + WebView readiness
G4  Cordova integration + APK packaging
```

Cordova project/plugin tidak ditambahkan ke G2 hanya untuk persiapan dini.

## 3. G4 Architecture Spike

Sebelum implementasi APK penuh, buat spike minimal untuk membuktikan:

```text
production/staging URL dapat dibuka aman dalam WebView
session/login stabil
redirect/login/logout normal
Cordova deviceready tersedia sesuai architecture wrapper
device back dapat dikontrol
geolocation permission bekerja
download/open/share dapat ditangani
external link dapat diarahkan keluar WebView
```

Hasil spike menentukan detail `config.xml`, allow-navigation, whitelist dan plugin final.

Jangan mengunci plugin/version berdasarkan dokumentasi lama sebelum spike.

## 4. Web Auth vs API Auth

Web UI tetap menggunakan Web session/CSRF sesuai aplikasi CI4.

API `/api/*` tetap memakai JWT/token sesuai kontrak API.

Cordova wrapper **tidak otomatis mengganti seluruh Web auth dengan JWT**.

Jika kemudian ada feature native/local yang memanggil `/api/*`, feature tersebut mengikuti API auth secara terpisah.

## 5. Session Expiry UX

Jika session Web berakhir saat AJAX/Fetch:

```text
jangan render HTML login di dalam tabel/modal
→ deteksi unauthenticated
→ tampilkan pesan sesi berakhir
→ arahkan ke login
```

Setelah login, restore workflow hanya jika aman dan tidak menyebabkan mutation ulang.

## 6. Cordova Mode

Setelah `deviceready`, wrapper dapat menambah class:

```html
<html class="sisfour-cordova">
```

Digunakan hanya untuk perbedaan nyata WebView seperti safe-area/status bar/keyboard-specific behavior.

Jangan membuat UI kedua di bawah `.sisfour-cordova`.

## 7. Android Back

Contract:

```text
Modal terbuka       → tutup modal
Sidebar terbuka     → tutup sidebar
Offcanvas/dropdown  → tutup layer
Detail page         → history back
Dirty form          → confirmation
Dashboard/root      → exit confirmation/double-back sesuai keputusan final
```

Presensi/Jurnal yang belum tersimpan tidak boleh hilang hanya karena tombol Back.

## 8. Safe Area & Status Bar

UI G3 sudah harus menggunakan safe-area token:

```text
env(safe-area-inset-top)
env(safe-area-inset-right)
env(safe-area-inset-bottom)
env(safe-area-inset-left)
```

G4 memverifikasi behavior real device, status bar dan edge-to-edge configuration.

## 9. Soft Keyboard

Uji device nyata:

- input aktif tetap terlihat;
- modal scroll normal;
- sticky action tidak menutup field;
- SearchableSelect/dropdown tidak tertutup keyboard;
- textarea Jurnal/Kasus nyaman;
- orientation change tidak merusak viewport.

## 10. Geolocation

Flow:

```text
user menjalankan action yang butuh lokasi
→ cek permission
→ minta permission jika perlu
→ ambil lokasi
→ kirim koordinat ke server
→ server menentukan validitas geofence
```

Jangan meminta lokasi saat dashboard load.

UX harus membedakan:

```text
permission denied
GPS/service off
position timeout
akurasi buruk
lokasi didapat
di luar radius berdasarkan server response
```

## 11. Network State

APK adalah online-first.

Saat offline:

```text
jelaskan tidak ada koneksi
mutation tidak dinyatakan sukses
pertahankan input bila aman
sediakan retry
```

Tidak ada background/offline replay otomatis untuk Presensi/Jurnal/Kasus/Prestasi tanpa desain transaksi baru.

## 12. Mutation Safety

Cordova tidak mengubah contract server:

```text
busy guard
anti double-submit
server-confirmed success
transaction backend
idempotency/duplicate guard sesuai domain
```

## 13. File / Download / Share

Feature yang perlu diuji khusus APK:

```text
Kartu PDF
file export yang memang tersedia untuk role
preview document
open external app
share file bila diputuskan
```

Browser behavior tidak boleh diasumsikan otomatis identik dengan Android WebView.

## 14. Navigation External

Canonical intent:

```text
internal SisisFour URL → tetap di WebView
external website       → controlled external browser
mailto/tel/maps/chat   → application intent bila didukung
```

Whitelist/navigation policy hanya mengizinkan domain yang dibutuhkan.

## 15. Security

APK release:

- HTTPS only;
- tidak hardcode password/token/secret;
- tidak log session/token/credential;
- debug WebView dimatikan pada release;
- domain/navigation dibatasi;
- permission Android seminimal mungkin;
- server tetap melakukan authorization;
- file upload/download mengikuti validation server.

## 16. API

API core tetap tersedia untuk integration/native feature:

```text
/api/auth/*
/api/version
/api/dashboard
/api/presensi/*
/api/profile/*
... sesuai Routes Final
```

Jangan mengasumsikan endpoint API ada hanya karena Web route ada; lihat `Routes Final — SisisFour.md` dan source route aktual.

## 17. Branding APK

Icon/branding APK dan runtime branding sekolah adalah dua layer:

```text
APK launcher icon/splash = asset build Cordova
runtime favicon/logo      = setting_sistem
```

Launcher icon dapat menggunakan identitas sekolah yang disepakati, tetapi perubahan runtime `icon_sekolah` tidak otomatis mengganti icon launcher APK yang sudah terinstall.

## 18. Versioning

APK memiliki app/build version sendiri. Server/API memiliki version sendiri.

Client dapat mengecek endpoint version untuk compatibility/update notice, tetapi server tidak melakukan silent APK replacement.

## 19. Distribution

Tahap awal:

```text
APK debug/release untuk sideload internal device test
```

Produksi:

```text
signed APK/AAB dan kanal distribusi yang diputuskan madrasah
```

Keystore/signing material tidak masuk repository publik/source biasa.

## 20. Device Test Matrix

Minimum:

```text
Android kecil 360px
Android umum 390/412px
minimal 2 versi Android yang masih menjadi target deployment
Wi-Fi stabil
mobile data/lambat
offline → online
permission location allow/deny
keyboard open/close
back button
file download/open
```

Versi Android/Cordova/plugin final ditentukan pada saat G4 karena tooling dapat berubah.

## 21. G4 Gate

Cordova/APK dinyatakan siap bila:

```text
G3 mobile UI PASS
architecture spike PASS
login/session PASS
Back PASS
keyboard PASS
safe-area PASS
geolocation PASS
network/offline state PASS
file/download/share PASS
external link PASS
maintenance behavior PASS
security config PASS
signed build PASS
multi-device regression PASS
```

## 22. Non-Goal

G4 tidak digunakan untuk:

- memperbaiki business rule Master Data yang seharusnya selesai di G2;
- redesign besar dashboard/table yang seharusnya selesai di G3;
- memindahkan authorization ke JavaScript/Cordova;
- membuat offline academic mutation tanpa design khusus.
