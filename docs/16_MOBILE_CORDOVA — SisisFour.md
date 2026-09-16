# Cordova Packaging & Integration — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 16 September 2026  
**Implementation Phase:** G4, setelah G3 Mobile UI selesai

> SisisFour akan dibungkus menjadi Android APK dengan Apache Cordova. Dokumen ini mengatur integrasi teknis APK. UI/UX mobile ada di `14_SISFOUR_MOBILE_CORDOVA_UI_UX_STANDARD.md`. Business rule tetap di server.

## 1. Target Architecture

SisisFour tidak membangun ulang aplikasi menjadi SPA mobile kedua.

```text
CI4/Sneat Web Application
        ↓
responsive mobile UI
        ↓
Cordova Android WebView wrapper
```

Server tetap source of truth untuk auth, RBAC, scope, business rule, validation, transaction, dan persistence.

## 2. Separation of Phases

```text
G2      Master Data/lifecycle fixing + stabilization
G3      Mobile role UI + WebView readiness
G3.3.1  BK foundation/Konseling privacy contract
G4      Cordova integration + APK packaging
```

Cordova project/plugin tidak ditambahkan ke G3 hanya untuk persiapan dini.

## 3. G4 Architecture Spike

Sebelum implementasi APK penuh, spike minimal membuktikan:

```text
production/staging URL aman dibuka dalam WebView
session/login/logout stabil
redirect normal
deviceready tersedia sesuai wrapper
device Back dapat dikontrol
geolocation permission bekerja
download/open/share dapat ditangani
external link diarahkan keluar WebView bila perlu
```

Plugin/version final ditentukan saat G4, bukan dikunci dari dokumentasi lama.

## 4. Web Auth vs API Auth

Web UI tetap memakai Web session + CSRF. API `/api/*` tetap memakai JWT/token.

Cordova wrapper tidak otomatis mengganti seluruh Web auth dengan JWT.

## 5. Session Expiry UX

Jika session Web berakhir saat AJAX/Fetch:

```text
jangan render HTML login di tabel/modal
→ tampilkan sesi berakhir
→ arahkan ke login
```

Restore workflow hanya jika aman dan tidak menyebabkan mutation ulang.

## 6. Cordova Mode

Setelah `deviceready`, wrapper dapat menambah:

```html
<html class="sisfour-cordova">
```

Hanya untuk perbedaan WebView nyata; jangan membuat UI kedua.

## 7. Android Back

```text
Modal terbuka       → tutup modal
Sidebar terbuka     → tutup sidebar
Offcanvas/dropdown  → tutup layer
Detail page         → history back
Dirty form          → confirmation
Dashboard/root      → exit confirmation/double-back sesuai keputusan final
```

Input Presensi/Jurnal/Catatan Pelanggaran/Konseling/Prestasi yang belum tersimpan tidak boleh hilang hanya karena Back.

## 8. Safe Area & Status Bar

G3 harus menggunakan safe-area token. G4 memverifikasi real device, status bar, dan edge-to-edge configuration.

## 9. Soft Keyboard

Uji device nyata:

- input aktif terlihat;
- modal vertical-scroll normal;
- sticky action tidak menutup field;
- SearchableSelect/dropdown tidak tertutup keyboard;
- textarea Jurnal dan Konseling nyaman;
- orientation/viewport tidak merusak layout;
- tidak ada horizontal overflow.

## 10. Geolocation

Flow:

```text
user menjalankan action yang butuh lokasi
→ cek/minta permission
→ ambil lokasi
→ kirim koordinat ke server
→ server menentukan validitas geofence
```

Jangan meminta lokasi saat dashboard load.

## 11. Network State

APK online-first.

Saat offline/network gagal:

```text
jelaskan tidak ada koneksi/gagal
mutation tidak dinyatakan sukses
pertahankan input bila aman
sediakan retry
```

Tidak ada background/offline replay otomatis untuk:

```text
Presensi
Jurnal
Catatan Pelanggaran
Tindak Lanjut Pelanggaran
Konseling
Prestasi
```

tanpa desain transaksi baru.

## 12. Mutation Safety

Cordova tidak mengubah contract server:

```text
busy guard
anti double-submit
server-confirmed success
transaction backend
idempotency/duplicate guard sesuai domain
```

## 13. Privacy Konseling BK

G4 tidak boleh memperluas akses Konseling.

```text
Operasional Konseling = Admin / Operator / BK + permission
Settings Konseling    = Admin / BK + permission
Pimpinan/Guru/Wali/Siswa = tidak mendapat detail/widget/surface Konseling
```

WebView/Cordova tidak menjadi alasan menyimpan cache/export Konseling secara lokal tanpa policy baru.

## 14. File / Download / Share

Feature yang perlu diuji khusus APK:

```text
Kartu PDF
XLSX export yang memang tersedia untuk role
export Catatan Pelanggaran
export Prestasi
export Konseling hanya actor yang berhak
preview document
open external app
share file bila diputuskan
```

Browser behavior tidak diasumsikan identik dengan Android WebView.

## 15. Navigation External

```text
internal SisisFour URL → tetap di WebView
external website       → controlled external browser
mailto/tel/maps/chat   → application intent bila didukung
```

Whitelist/navigation policy hanya mengizinkan domain yang dibutuhkan.

## 16. Security

APK release:

- HTTPS only;
- tidak hardcode password/token/secret;
- tidak log session/token/credential;
- debug WebView off pada release;
- navigation/domain dibatasi;
- permission Android seminimal mungkin;
- server tetap authorization boundary;
- file upload/download mengikuti validation server;
- data Konseling tidak bocor ke role/client yang tidak berhak.

## 17. API

API core tetap tersedia sesuai route runtime. Jangan mengasumsikan endpoint API ada hanya karena Web route ada.

G3.3.1 menambah Web route Konseling melalui `RoutesBKFoundation.php`; **tidak menambah API Konseling**. Jika API Konseling dibutuhkan untuk feature native kelak, itu harus menjadi scope baru dengan permission/privacy gate yang sama atau lebih ketat.

## 18. Branding APK

```text
APK launcher icon/splash = asset build Cordova
runtime favicon/logo      = setting_sistem
```

Perubahan runtime `icon_sekolah` tidak otomatis mengganti launcher icon APK yang sudah terpasang.

## 19. Versioning

APK memiliki app/build version sendiri. Server/API memiliki version sendiri. Endpoint version dapat dipakai compatibility/update notice, bukan silent APK replacement.

## 20. Distribution

Tahap awal:

```text
APK debug/release untuk sideload internal device test
```

Produksi:

```text
signed APK/AAB dan kanal distribusi yang diputuskan madrasah
```

Keystore/signing material tidak masuk source repository biasa.

## 21. Device Test Matrix

Minimum:

```text
Android kecil 360px
Android umum 390/412px
minimal 2 versi Android target
Wi-Fi stabil
mobile data/lambat
offline → online
location allow/deny
keyboard open/close
Back button
file download/open
```

## 22. G4 Gate

Cordova/APK siap bila:

```text
G3 mobile UI PASS
architecture spike PASS
login/session PASS
Back PASS
keyboard PASS
safe-area PASS
geolocation PASS
network/offline PASS
file/download/share PASS
external link PASS
maintenance PASS
security config PASS
signed build PASS
multi-device regression PASS
```

## 23. Non-Goal

G4 tidak digunakan untuk:

- memperbaiki ulang business rule yang sudah ditutup di G2/G3;
- menghidupkan kembali poin Pelanggaran;
- membuka Konseling ke role lain;
- redesign besar dashboard/table yang seharusnya selesai di G3;
- memindahkan authorization ke JavaScript/Cordova;
- membuat offline academic mutation tanpa desain khusus.