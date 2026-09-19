# Cordova Packaging & Integration — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 19 September 2026
**Current Boundary:** G3.8 WebView readiness aktif; Cordova implementation tetap G4

> SisisFour akan dibungkus menjadi Android APK dengan Apache Cordova. Dokumen ini mengatur integrasi teknis APK. UI/UX mobile ada di `14_SISFOUR_MOBILE_CORDOVA_UI_UX_STANDARD.md`. Business rule tetap di server.

## 1. Target Architecture

```text
CI4/Sneat Web Application
        ↓
responsive mobile UI
        ↓
Cordova Android WebView wrapper
```

Server tetap source of truth untuk auth, RBAC, scope, period context, business rule, validation, transaction, dan persistence.

## 2. Separation of Phases

```text
G2      Master Data/lifecycle fixing + stabilization
G3.1–G3.7 Mobile role UI + responsive foundation
G3.8    Web-side Viewport/WebView Readiness
G4      Cordova integration + APK packaging
```

Cordova project/plugin tidak ditambahkan ke G3 hanya untuk persiapan dini.

### G3.8 Locked Boundary

```text
SSOT lock = PASS / user approval
```

Baseline:

```text
main   = 7a595f21b70d9bfc28272b7f8ba19a2dfd3e60f9
branch = feat/g3-8-webview-readiness-20260919
G3.7  = CLOSED / MERGED — PR #16
```

G3.8 hanya menguji dan, bila ada gap nyata, memperbaiki source Web agar wrapper G4 tidak perlu mengoreksi ulang behavior browser.

```text
DB/schema/SQL       = NONE
RBAC/permission     = unchanged
scope/period        = unchanged
business rule       = unchanged
route/menu          = unchanged
server auth/session = tetap authoritative
Cordova project     = OUT OF SCOPE
plugin/native API   = OUT OF SCOPE
APK/signing         = OUT OF SCOPE
```

Web-side readiness G3.8 mencakup viewport-fit, safe-area, short-height/landscape, visualViewport/soft keyboard, modal/SearchableSelect, Fetch session expiry, network failure UX, browser upload/download/export, dan navigation inventory.

G3.8 **tidak** membuktikan `deviceready`, Android Back, permission geolocation native, download/share bridge, external intent, status bar/edge-to-edge native config, signed build, atau real-device APK matrix. Bukti tersebut tetap G4.

## 3. G4 Architecture Spike

Sebelum implementasi APK penuh, spike minimal membuktikan:

```text
production/staging URL aman dibuka dalam WebView
session/login/logout stabil
redirect normal
deviceready tersedia
device Back dapat dikontrol
geolocation permission bekerja
download/open/share dapat ditangani
external link diarahkan keluar WebView bila perlu
```

Plugin/version final ditentukan saat G4.

## 4. Web Auth vs API Auth

Web UI tetap memakai Web session + CSRF. API `/api/*` tetap memakai JWT/token. Cordova wrapper tidak otomatis mengganti Web auth dengan JWT.

## 5. Session Expiry UX

Jika session Web berakhir saat AJAX/Fetch:

```text
jangan render HTML login di tabel/modal
→ tampilkan sesi berakhir
→ arahkan ke login
```

Restore workflow hanya jika aman dan tidak menyebabkan mutation ulang.

## 6. Cordova Mode

Setelah `deviceready`, wrapper dapat menambah class `sisfour-cordova` hanya untuk perbedaan WebView nyata; jangan membuat UI kedua.

## 7. Android Back

```text
Modal terbuka       → tutup modal
Sidebar terbuka     → tutup sidebar
Offcanvas/dropdown  → tutup layer
Detail page         → history back
Dirty form          → project confirmation
Dashboard/root      → exit/double-back sesuai keputusan final
```

Input Presensi/Jurnal/Catatan Pelanggaran/Tindak Lanjut Pelanggaran/Konseling/Tindak Lanjut Konseling/Prestasi yang belum tersimpan tidak boleh hilang hanya karena Back.

## 8. Safe Area & Status Bar

G3 memakai safe-area token. G4 memverifikasi real device, status bar, dan edge-to-edge configuration.

## 9. Soft Keyboard

Uji device nyata:

- input aktif terlihat;
- modal vertical-scroll normal;
- sticky action tidak menutup field;
- SearchableSelect/dropdown tidak tertutup keyboard;
- textarea Jurnal/Konseling/follow-up nyaman;
- timeline follow-up Konseling readable;
- tidak ada horizontal overflow.

## 10. Geolocation

```text
user menjalankan action yang butuh lokasi
→ cek/minta permission
→ ambil lokasi
→ kirim koordinat ke server
→ server menentukan validitas geofence
```

Jangan meminta lokasi saat dashboard load.

## 11. Network State

APK online-first. Tidak ada background/offline replay otomatis untuk:

```text
Presensi
Jurnal
Catatan Pelanggaran
Tindak Lanjut Pelanggaran
Konseling
Tindak Lanjut Konseling
Prestasi
```

Saat network gagal: jelaskan gagal, pertahankan input bila aman, berikan retry, jangan tampilkan sukses palsu.

## 12. Mutation Safety

Cordova tidak mengubah contract server:

```text
busy guard
anti double-submit
server-confirmed success
transaction backend
idempotency/duplicate guard sesuai domain
```

Tidak ada delete parent Konseling atau Tindak Lanjut Konseling hanya karena UI native/WebView menyediakan gesture/action tambahan.

## 13. Privacy Konseling BK

```text
Operasional Konseling = Admin / Operator / BK + permission
Settings Konseling    = Admin / BK + permission
Pimpinan/Guru/Wali/Siswa = tidak mendapat detail/widget/surface Konseling
```

Privacy mencakup parent Konseling dan seluruh histori `tindak_lanjut_konseling_bk`. WebView/Cordova tidak menjadi alasan menyimpan cache/export Konseling lokal tanpa policy baru.

## 14. Period Context

Surface periodik yang sudah mempunyai filter Tahun Ajaran di Web harus mempertahankan behavior yang sama di WebView:

```text
default = Tahun Ajaran aktif
Reset   = Tahun Ajaran aktif
history = selectable
export  = mengikuti Tahun terpilih
```

WebView tidak boleh mengganti period context hanya dari local state client.

## 15. File / Download / Share

Feature yang perlu diuji khusus APK:

```text
Kartu PDF
XLSX export sesuai role
export Catatan Pelanggaran
export Prestasi
export Konseling + Tindak Lanjut hanya actor berhak
preview document
open external app
share file bila diputuskan
```

## 16. Navigation External

```text
internal SisisFour URL → tetap di WebView
external website       → controlled external browser
mailto/tel/maps/chat   → application intent bila didukung
```

## 17. Security

APK release:

- HTTPS only;
- tidak hardcode password/token/secret;
- tidak log session/token/credential;
- debug WebView off pada release;
- navigation/domain dibatasi;
- permission Android seminimal mungkin;
- server tetap authorization boundary;
- file upload/download mengikuti validation server;
- data Konseling/follow-up tidak bocor ke role/client tidak berhak.

## 18. API

API core tetap mengikuti runtime route. Jangan mengasumsikan endpoint API ada hanya karena Web route ada.

G3.3.1 menambah Web route Konseling/follow-up melalui `RoutesBKFoundation.php`; **tidak menambah API Konseling**. API Konseling native kelak adalah scope baru dengan privacy gate yang sama atau lebih ketat.

## 19. Branding APK

```text
APK launcher icon/splash = asset build Cordova
runtime favicon/logo      = setting_sistem
```

## 20. Versioning & Distribution

APK memiliki app/build version sendiri; server/API mempunyai version sendiri. Keystore/signing material tidak masuk repository biasa.

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

- memperbaiki business rule yang seharusnya selesai di G2/G3;
- menghidupkan kembali poin Pelanggaran;
- membuka Konseling ke role lain;
- menambah delete Konseling/follow-up;
- redesign besar dashboard/table yang seharusnya selesai di G3;
- memindahkan authorization ke JavaScript/Cordova;
- membuat offline academic mutation tanpa desain khusus.
