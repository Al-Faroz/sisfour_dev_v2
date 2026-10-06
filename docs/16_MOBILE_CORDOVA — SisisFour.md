# Cordova Packaging & Integration — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 5 Oktober 2026
**Current Boundary:** G3.9 + G3.10 CLOSED / MERGED; G4.0A Environment PASS; G4.0B Architecture Lock APPROVED

> SisisFour akan dibungkus menjadi Android APK dengan Apache Cordova. Dokumen ini mengatur integrasi teknis APK. UI/UX mobile ada di `14_SISFOUR_MOBILE_CORDOVA_UI_UX_STANDARD.md`. Business rule tetap di server.

## 1. Target Architecture

```text
Cordova Android APK
        ↓
local Cordova shell (privileged)
        ↓
controlled fullscreen InAppBrowser
        ↓
https://sisfour.mtsn4jombang.sch.id/
        ↓
CI4/Sneat responsive Web UI
```

Primary Cordova WebView tidak memuat production URL langsung sebagai privileged remote content. Remote SisFour tidak diberi arbitrary Cordova API. Native capability hanya melalui bridge sempit yang divalidasi oleh local shell.

Server tetap source of truth untuk auth, session, CSRF, RBAC, scope, period context, business rule, validation, transaction, persistence, document authorization, privacy Konseling, dan geofence decision.

## 2. Separation of Phases

```text
G2      Master Data/lifecycle fixing + stabilization
G3.1–G3.7 Mobile role UI + responsive foundation
G3.8    Web-side Viewport/WebView Readiness
G4      Cordova integration + APK packaging
```

Cordova project/plugin tidak ditambahkan ke G3 hanya untuk persiapan dini.

### G3.8 Closure Boundary

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

Closure:

```text
local WebView readiness = PASS
feature head            = 52143aad25b2d273ee585bcc318ebdedcb3ccfa4
PR #17                  = CLOSED / MERGED
PR Ready                = PASS / user approval
Merge                   = PASS / user approval
merge commit            = 2a22d4d8a4d8fce9ec1dd27504b9ef77c357dec9
main after merge        = 2a22d4d8a4d8fce9ec1dd27504b9ef77c357dec9
hosting deployment      = NOT AUTHORIZED / NOT EXECUTED
G4/Cordova              = NOT STARTED
```

## 3. G4 Architecture Spike

G4.0 baseline:

```text
main                         = de3efc5119f811d30f4c1759e20d244106ebd899
branch                       = feat/g4-cordova-android-20261005
G4.0A Environment Preflight = PASS
G4.0B Architecture Lock     = PASS / user approval
Cordova CLI                 = 13.0.0
cordova-android target      = 15.1.0
Android SDK                 = API 36
Build Tools                 = 36.0.0
JDK                         = 17
```

Minimal spike G4.1 wajib membuktikan:

```text
local shell deviceready tersedia
production URL dibuka melalui controlled InAppBrowser
session/login/logout/redirect stabil
CSRF same-origin Web tetap bekerja
device Back memenuhi project contract
geolocation permission allow/deny bekerja
authenticated download/open/share dapat ditangani
external URL/intents diarahkan sesuai policy
offline startup tidak menjadi blank WebView
tidak ada client-side authorization widening
```

Plugin baseline locked:

```text
cordova-plugin-inappbrowser  = 7.0.0
cordova-plugin-geolocation   = 4.1.0
local SisFour Android plugin = download/open/share/controlled intents bila spike membuktikan diperlukan
```

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

Setelah `deviceready`, local shell membuka satu instance controlled InAppBrowser untuk production SisFour. Jangan membuat UI aplikasi kedua. Perbedaan native hanya boleh ditambahkan bila benar-benar diperlukan oleh runtime APK.

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

## 24. G4.0 Architecture Lock

### 24.1 Trust boundary

```text
Local shell:
- owns cordova.js / deviceready
- owns native plugins
- validates native bridge messages
- owns startup offline/fatal state

Remote production UI:
- owns CI4/Sneat application UI
- owns Web login/session
- owns CSRF
- never receives arbitrary native execution capability

Server:
- remains authentication/authorization/business boundary
```

Dilarang:

```text
<content src="https://sisfour.mtsn4jombang.sch.id/">
arbitrary native.exec / evalNative
hardcoded password/token/API secret
client-side role/permission decision
offline academic mutation replay
background location
```

### 24.2 Navigation policy

```text
https://sisfour.mtsn4jombang.sch.id/*  -> tetap di app
external HTTPS                          -> controlled system browser
HTTP                                    -> deny
mailto/tel/maps/chat                    -> controlled allowlisted intent bila didukung
```

Redirect dari route internal tetap boleh menjalankan server authorization terlebih dahulu. Contoh Dokumen Saya: `/dokumen-saya/buka/{id}` tetap internal sampai server selesai memvalidasi akses; redirect Google Drive kemudian keluar ke browser eksternal.

### 24.3 Bridge allowlist

Remote Web hanya boleh meminta native action yang terdokumentasi:

```text
location.request
external.open
file.download
file.open
file.share
app.exit
```

Semua message wajib JSON valid, type allowlisted, payload tervalidasi, dan hanya diterima saat browser berada pada origin SisFour production. Tidak ada arbitrary command atau arbitrary file read.

### 24.4 Android Back

Back adalah high-risk G4.1 gate:

```text
modal/sidebar/offcanvas/dropdown -> close layer
detail/history                    -> history back
dirty form                        -> confirmation
root/dashboard                    -> double-back / exit
```

History sentinel/adapter Web boleh dipakai hanya untuk mempertahankan contract ini. Bila InAppBrowser default history tidak cukup reliable, spike berhenti dan native Back integration diperluas sebelum feature lain dilanjutkan.

### 24.5 File / download / share

Server tetap melakukan authorization terlebih dahulu. Authenticated download handler harus mempertahankan session/cookie dan user-agent yang relevan, lalu menyerahkan hasil ke Android download/open/share flow. File Konseling/export rahasia tidak boleh bocor ke actor yang tidak berhak.

### 24.6 Repository layout

```text
mobile/cordova/
  config.xml
  package.json
  package-lock.json
  www/
    index.html
    css/shell.css
    js/shell.js
  local-plugins/
    sisfour-native-android/
  resources/
```

Generated `platforms/` dan `plugins/` bukan source of truth dan harus dapat direcreate dari manifest/lock.

### 24.7 Phase status

```text
G4.0A Environment Preflight     PASS
G4.0B Architecture Lock        PASS / user approval
G4.1A Cordova shell scaffold    PASS
G4.1B Controlled IAB runtime    PASS / user UAT evidence
G4.1C Auth GET download bridge  IMPLEMENTED / REBUILD + DEVICE UAT PENDING
G4.1D Dashboard + geolocation   IMPLEMENTED / REBUILD + DEVICE UAT PENDING
G4.1E Upload/import chooser     PASS / USER DEVICE UAT
G4.1F Android Back              IMPLEMENTED / REBUILD + DEVICE UAT PENDING
G4.1F POST output               IMPLEMENTED GENERIC / REBUILD + DEVICE UAT PENDING
G4.1G APK branding              IMPLEMENTED / REBUILD + DEVICE UAT PENDING
G4.2 Release engineering        VERSION/SIGNING PROCEDURE PREPARED / KEY + SIGNED BUILD PENDING
signed APK / device matrix      PENDING
```


## 25. G4.1C — Authenticated Download Bridge

Current runtime evidence after G4.1B:

```text
controlled InAppBrowser startup/navigation = PASS / user UAT evidence
Web feature surface                         = reachable
browser-style export/download in APK        = FAIL / user UAT evidence
```

G4.1C memperbaiki boundary file tanpa memindahkan authorization dari server:

```text
InAppBrowser Android download event
        ↓
local shell validates SisFour HTTPS origin
        ↓
SisFourNative.download
        ↓
native authenticated HTTPS GET
(cookie session + user-agent)
        ↓
read response Content-Disposition + MIME
        ↓
app-private temporary cache
        ↓
Android ACTION_CREATE_DOCUMENT / Save As
        ↓
user-selected destination
```

Rules:

- hanya download HTTPS dari host `sisfour.mtsn4jombang.sch.id`;
- server route/permission/session tetap memutuskan boleh/tidak;
- cookie tidak dikirim ke host eksternal;
- tidak menyimpan password/token baru;
- nama file memakai `Content-Disposition`/MIME dari response lalu disanitasi;
- Android Save As memakai `ACTION_CREATE_DOCUMENT`;
- tidak memakai legacy storage permission pada versi Android mana pun;
- source of truth plugin berada di `mobile/cordova/local-plugins/sisfour-native-android/`;
- generated `platforms/` dan `plugins/` tetap bukan source of truth.

Initial gate G4.1C berfokus pada response download GET. Surface POST-download wajib regression terpisah karena event `download` InAppBrowser tidak membawa request body. Contoh yang harus diuji khusus: Statistik PDF POST/client chart payload dan Kartu Pelajar ZIP POST.

G4.1C belum PASS sampai APK hasil rebuild diuji minimal untuk:

```text
Catatan Pelanggaran XLSX
Konseling BK XLSX
Prestasi XLSX
UKS XLSX/template
PTSP XLSX
Dokumen Siswa XLSX/template
Laporan Presensi/Jurnal
Backup download
Kartu Pelajar single-file download
Android Save As membuka pemilih lokasi dan file tersimpan di tujuan pilihan user
session/RBAC tetap enforced
logout lalu direct download tidak lolos
```

## 26. G4.1D — Dashboard Helper + Native Geolocation Bridge

Local shell menyuntikkan helper UI minimal hanya pada origin SisFour production:

- tombol floating **Dashboard** pada halaman internal selain login/root/dashboard;
- tombol tidak membuka capability baru dan hanya menuju route `/dashboard`;
- remote page tetap tidak menerima arbitrary Cordova API;
- helper dipasang ulang setelah setiap `loadstop`.

Geolocation memakai bridge sempit:

```text
Web page navigator.geolocation.getCurrentPosition()
        ↓
injected compatibility shim
        ↓
cordova_iab.postMessage({ type: location.request })
        ↓
local shell validates current SisFour origin + request id + options
        ↓
cordova-plugin-geolocation 4.1.0
        ↓
Android location permission / native location
        ↓
result dikembalikan hanya ke callback request tersebut
```

Location request hanya diteruskan bila ada user gesture dalam 15 detik terakhir. Tujuannya menjaga contract bahwa permission/lokasi diminta saat user menjalankan aksi seperti Simpan Presensi/Jurnal, bukan saat dashboard/startup load.

UAT wajib:

```text
Dashboard helper muncul pada halaman internal selain login/root/dashboard
Dashboard helper tidak muncul di login/root/dashboard
Dashboard helper tidak menutup sticky action penting

geofencing_aktif = OFF:
- Presensi Siswa tidak meminta lokasi
- Presensi Guru/Jurnal tidak meminta lokasi

geofencing_aktif = ON:
- Presensi Siswa Guru Terjadwal meminta lokasi saat save
- Jurnal Guru status Hadir non-SEMUA meminta lokasi saat save
- permission allow -> koordinat diterima server
- permission deny -> no false success
- outside radius -> server menolak sesuai rule domain
- Jurnal Izin/Sakit tidak meminta lokasi
- actor capability SEMUA tidak meminta lokasi

startup/login/dashboard tidak meminta lokasi
Cordova tidak membaca role/status/setting geofence; Web/server yang menentukan kapan navigator.geolocation dipanggil
```

## 27. G4.1E — Upload / Import File Chooser

`cordova-plugin-inappbrowser 7.0.0` pada Android sudah menyediakan `onShowFileChooser` dan membuka Android `ACTION_GET_CONTENT`. Karena itu G4.1E tidak menambah native bridge baru sebelum ada bukti gap runtime.

Device regression minimum:

```text
upload foto/profile
import Excel master yang relevan
import CKG
import Dokumen Siswa
lampiran PTSP bila actor/surface memang mendukung
cancel file chooser kembali ke form tanpa crash
file invalid tetap ditolak oleh validation server
file valid tetap mengikuti permission/RBAC server
```

Jika UAT membuktikan kebutuhan camera capture, multiple-select, atau MIME-specific picker yang tidak terpenuhi oleh InAppBrowser default, barulah native chooser diperluas.

## 28. G4.1F — Android Back + Generic POST Output

Android Back tetap bagian thin wrapper karena WebView/InAppBrowser tidak otomatis memenuhi contract aplikasi:

```text
modal/sidebar/offcanvas/dropdown -> close layer
detail/history                    -> history back
dirty form                        -> confirmation
root/dashboard                    -> double-back / exit
```

Adapter Back bersifat generik dan tidak mengenal role/domain.

### Generic POST attachment transport

POST file output aktual yang membutuhkan compatibility path:

```text
Statistik PDF          -> form POST + chart_images/filter/CSRF
Kartu massal PDF       -> FormData POST
Kartu JPG ZIP          -> FormData POST
```

Tidak ada endpoint bisnis yang di-hardcode di local shell/native plugin.

Progressive enhancement contract:

```text
Chrome/browser:
Web workflow -> existing fetch/form submit -> browser download

Cordova APK:
Web workflow
→ optional window.SisFourFileDownload.post(...)
→ cordova_iab postMessage(type=file.download)
→ local shell validates:
   - current origin SisFour production
   - HTTPS same-origin target
   - method POST only
   - request id
   - safe headers only: Accept / X-Requested-With
   - text-only fields
   - max 1200 fields
   - max encoded payload 24 MiB
→ SisFourNative.downloadRequest
→ CookieManager session + User-Agent
→ application/x-www-form-urlencoded UTF-8
→ server CSRF/RBAC/business validation
→ redirect disabled
→ only 2xx attachment response accepted
→ server filename/MIME preserved
→ Android Save As
```

Repeated field names such as `id_kartu[]` are preserved in the URL-encoded POST body and remain arrays server-side.

Remote Web receives no arbitrary Cordova API. It only sees an optional semantic file-download adapter injected by the trusted local shell. If the adapter is absent, existing browser behavior remains unchanged.

Security boundary:

- no Cookie/Authorization header accepted from remote payload;
- native obtains cookie directly from Android WebView CookieManager;
- no arbitrary HTTP method;
- no external host;
- no redirect follow;
- no arbitrary native command;
- no File/Blob upload through this bridge;
- response must be an attachment;
- server remains authoritative for session, CSRF, RBAC, scope, filters, card selection, chart payload validation, and filename.

Status:

```text
GET attachment transport  = IMPLEMENTED / REBUILD + DEVICE UAT PENDING
POST attachment transport = IMPLEMENTED GENERIC / REBUILD + DEVICE UAT PENDING
Android Save As           = IMPLEMENTED / REBUILD + DEVICE UAT PENDING
Android Back adapter      = IMPLEMENTED / REBUILD + DEVICE UAT PENDING
```

## 29. G4.1G — APK Branding / Visual Identity

G4.1G adalah acceptance item sebelum release final, tetapi tidak boleh mengorbankan feature parity G4.1.

Current candidate:

```text
color vector master = mobile/cordova/resources/branding/LogoFlat.svg
white vector master = mobile/cordova/resources/branding/LogoFlat_White.svg
Android raster master = mobile/cordova/resources/branding/launcher-master-1024.png (1024×1024)
legacy launcher density = 36 / 48 / 72 / 96 / 144 / 192 px
adaptive foreground = launcher-master-1024 through safe-zone XML
adaptive bg = white
native splash = color launcher-master-1024 on white
local shell = exact white SVG on #119450; lightweight fade-in handoff
old 2319×2299 mobile master = RETIRED / REMOVED
Cordova branding/default placeholder = NONE
```

```text
final launcher icon
Android adaptive icon bila layak
final splash / launch screen
source asset master yang jelas
tanpa placeholder/default Apache Cordova
identitas SisFour / MTsN 4 Jombang
real-device visual validation
```

Prioritas asset:

1. logo existing SisFour bila resolusi/source cukup;
2. asset mobile khusus bila logo existing tidak memenuhi kebutuhan icon/splash.

Icon dan splash boleh memakai master berbeda. Animasi pembuka hanya boleh ringan dan tidak menambah startup delay yang terasa.

## 30. G4.2 — Release Engineering

```text
versionCode
versionName
release keystore
signed APK
signed AAB bila diperlukan kanal distribusi
final icon
final splash
signature verification
clean install
signed update test
multi-device test
distribution artifact
```

Keystore/signing secret tidak disimpan di repository biasa. Source repo hanya boleh menyimpan prosedur, metadata non-secret yang diperlukan, dan referensi backup policy.

### G4.2 preparation status

```text
applicationId             = id.sch.mtsn4jombang.sisfour
versionName               = 1.0.0
versionCode               = 10000
AndroidEdgeToEdge         = false
release signing template  = mobile/cordova/build-release.example.json
release procedure         = mobile/cordova/RELEASE.md
real signing config       = gitignored build-release.json
keystore/private key      = NOT CREATED / NOT STORED IN REPO
signed APK/AAB            = PENDING G4.1 device UAT
```

Repo meng-ignore `*.jks`, `*.keystore`, `*.p12`, `*.pfx`, populated signing config, dan generated release artifact directory.


## 31. Device UAT Evidence — 6 Oktober 2026

Real-device APK evidence:

```text
Upload file chooser                     PASS
GET export                              FAIL — produced export.bin
Splash visual                           FAIL — native/shell logo overlap and oversized transition
Launcher icon                           FAIL — SisFour icon not visible on launcher
Dashboard helper                        FAIL / not observed
Geolocation                             NOT VERIFIED by tester
Saved credential / autofill UX          DEGRADED vs Chrome Android
```

Remediation implemented after this UAT:

### GET export filename

GET attachments tidak lagi mengandalkan tebakan MIME/filename dari event WebView. Native Android melakukan authenticated same-origin GET dengan cookie sesi WebView, membaca `Content-Disposition` dan MIME langsung dari response, menolak redirect/login/error, menulis sementara ke app-private cache, lalu menyerahkan file ke Android Save As. Filename server seperti `.xlsx` dipertahankan dan tidak boleh fallback menjadi `export.bin`.

### Splash / icon

- native splash uses a safe-zone XML drawable around the final 1024×1024 color raster derived from the user-provided SVG;
- adaptive foreground uses the same final 1024×1024 raster through a separate safe-zone XML drawable;
- legacy launcher icons are explicit density resources: 36/48/72/96/144/192 px;
- the earlier 2319×2299 mobile master is retired and removed;
- the post-splash shell uses the exact user-provided white SVG on brand green, preventing the prior oversized/double-logo presentation.

Branding UAT should use a clean APK install to avoid launcher/icon cache ambiguity.

### Dashboard

Dashboard injection sekarang hanya satu helper kecil. Tombol muncul pada halaman internal selain login/root/dashboard dan hanya menavigasi ke authenticated `/dashboard`.

### Geolocation test contract

Geolocation mengikuti SSOT `docs/05_PRESENSI`:

- switch global = `geofencing_aktif`;
- Presensi Siswa Guru Terjadwal mengikuti geofence bila setting ON;
- Presensi Guru/Jurnal status Hadir non-SEMUA mengikuti geofence bila setting ON;
- setting OFF tidak meminta lokasi pada kedua workflow;
- Jurnal Izin/Sakit tidak meminta lokasi;
- radius dan hasil valid/tidak valid tetap diputuskan server.

Service Jurnal sekarang mengirim `geofence_required` dari konfigurasi server; Web hanya memanggil `navigator.geolocation` ketika flag tersebut true. Cordova hanya menjembatani API lokasi dan tidak mengetahui business condition tersebut.

### Saved credential / autofill

The Web login already declares standard `autocomplete=username` and `autocomplete=current-password`. APK remediation adds:

- standard Android WebView `setSaveFormData(true)`;
- `IMPORTANT_FOR_AUTOFILL_YES` for Android O+.
- tidak ada lagi injection yang mengubah field/focus halaman login remote.

SisFour does not store plaintext passwords or create an app-owned password vault. Credential persistence remains owned by Android's configured Autofill/Password Manager service.

Static source validation after remediation:

```text
shell.js outer parse                       PASS
Dashboard injected runtime script parse    PASS
autofill prepare hook parse                PASS
native GET download method present           PASS
native Content-Disposition read             PASS
ACTION_CREATE_DOCUMENT Save As source path   PASS / DEVICE UAT PENDING
safe-zone splash/adaptive resources         PASS
Gradle build                                PASS / USER TERMINAL EVIDENCE
new device UAT                              PENDING
```


### Thin-wrapper recovery 6 Oktober 2026

Audit ulang terhadap 00/00A/05/14/15/16 menghasilkan cleanup berikut:

```text
KEEP    controlled InAppBrowser architecture
KEEP    upload/import default file chooser
KEEP    generic same-origin authenticated GET download
KEEP    generic navigator.geolocation -> Android bridge
KEEP    generic Android Back adapter
KEEP    release signing secret boundary

FIX     Dashboard duplicate -> satu helper saja
FIX     Dashboard rule -> tidak tampil di login/root/dashboard
FIX     Jurnal Web -> server geofence_required menentukan permintaan lokasi
FIX     local shell CSS syntax
FIX     splash config -> documented SplashScreenBackgroundColor

REVERT  endpoint-specific POST download logic dari Cordova runtime
REVERT  remote login-form autofill/focus injection

IMPLEMENTED generic GET/POST attachment transport + Save As; exact-head build/device UAT pending
IMPLEMENTED Android autofill hook candidate; exact-head build/device UAT pending
IMPLEMENTED final 1024 launcher/splash pipeline; exact-head build/clean-install UAT pending
```


### Debug build evidence — thin-wrapper recovery

Exact branch recovery build pada 6 Oktober 2026:

```text
cordova build android     PASS
CordovaLib                PASS
app compileDebugJava      PASS
debug APK                 GENERATED
device UAT                NEXT
```

APK lokal:

```text
mobile/cordova/platforms/android/app/build/outputs/apk/debug/app-debug.apk
```

Build warning SDK XML/deprecated Gradle API tidak mengubah status build menjadi FAIL; warning tersebut dipantau terpisah dari acceptance feature parity.


### XLSX `export.bin` root cause — confirmed 6 Oktober 2026

Audit lintas-domain terhadap Master Siswa, BK, Konseling, Prestasi, UKS, PTSP, Dokumen Siswa, dan Laporan menunjukkan pola server yang konsisten:

```text
PhpSpreadsheet/Xlsx
→ server menentukan filename *.xlsx
→ CodeIgniter response->download(...)->setFileName(filename)
```

Nama file pada aplikasi Web **bukan akar masalah**.

Repo memakai CodeIgniter 4.7.4. `response->download($path, null)` menggunakan `setMime=false` secara default sehingga response XLSX dapat membawa:

```text
Content-Type: application/octet-stream
Content-Disposition:
attachment; filename="nama.xlsx"; filename*=UTF-8''nama.xlsx
```

Android `URLUtil.guessFileName()` legacy tidak reliable untuk header dengan parameter `filename*` tambahan. Fallback terhadap URL `.../export` + MIME octet-stream menghasilkan `export.bin`.

Remediation native bersifat generic:

```text
1. baca Content-Disposition response
2. parse filename* RFC 5987/6266
3. fallback ke filename
4. pertahankan filename dari server
5. bila MIME response octet-stream, gunakan MIME event yang lebih spesifik
6. bila masih generik, infer MIME dari ekstensi filename
7. URLUtil hanya fallback terakhir
```

Cordova tidak mengenal nama modul/endpoint bisnis pada parser ini.

Dashboard helper setelah UAT dipindah ke kiri bawah dengan safe-area; rule visibility tetap tidak muncul pada login/root/dashboard.

Status:

```text
generic XLSX filename/MIME remediation = IMPLEMENTED / REBUILD + DEVICE UAT PENDING
Dashboard bottom-left                  = IMPLEMENTED / REBUILD + DEVICE UAT PENDING
```


### Canonical downloadable file types — G4 bridge scope

Audit repo 6 Oktober 2026 memperluas acceptance download dari sekadar XLSX/PDF menjadi tipe file aktual yang memang dilayani SisFour:

```text
XLSX       export/template banyak domain
PDF        Kartu, Portofolio, receipt/output PDF, lampiran
SQL        Backup Database
PNG        lampiran/dokumen personalia/PTSP
JPG/JPEG   lampiran/dokumen personalia/PTSP
ZIP        arsip JPG Kartu Pelajar
```

Catatan boundary:

- JPG di dalam ZIP Kartu bukan direct GET file per siswa.
- Dokumen Siswa `PDF/IMAGE` berada di Google Drive eksternal; SisFour hanya mengotorisasi metadata/link lalu browser/Google Drive menangani file remote.
- JSON adalah response API/AJAX, bukan file download user.
- DOC/DOCX, PPT/PPTX, XLS legacy, CSV tidak ditemukan sebagai output file canonical SisFour pada audit ini.
- APK/AAB adalah release artifact developer, bukan runtime user download.

Native bridge harus generic terhadap filename + MIME dan tidak boleh hardcode route/modul. Fallback MIME canonical hanya berbasis ekstensi file aktual:

```text
.xlsx -> application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
.pdf  -> application/pdf
.zip  -> application/zip
.png  -> image/png
.jpg/.jpeg -> image/jpeg
.sql  -> application/sql
```

GET same-origin attachment menggunakan generic native download bridge.
POST-generated attachment memakai generic `file.download` transport dan tetap mempunyai regression gate tersendiri pada device UAT.


### Android Save As picker — user-selected destination

Atas UAT/keputusan user 6 Oktober 2026, authenticated GET download tidak lagi langsung menulis file ke public Downloads.

Flow generic:

```text
InAppBrowser download event
→ authenticated same-origin native GET
→ server Content-Disposition + MIME
→ stream ke app-private temporary cache
→ Android ACTION_CREATE_DOCUMENT
→ user memilih lokasi + dapat mengubah nama file
→ stream temp file ke URI pilihan
→ temp file dibersihkan
```

Rules:

- filename server tetap menjadi suggested filename;
- user boleh memilih folder dan mengubah nama melalui Android document picker;
- tidak membutuhkan `WRITE_EXTERNAL_STORAGE`;
- tidak meminta storage permission saat startup;
- cancel picker tidak menghasilkan false-success;
- hanya satu pending Save As native pada satu waktu;
- session/RBAC server tetap authoritative;
- tipe file tetap generic: XLSX/PDF/SQL/PNG/JPG/JPEG/ZIP;
- Cordova tetap tidak mengenal route/modul bisnis.

Status:

```text
Save As picker generic = IMPLEMENTED / REBUILD + DEVICE UAT PENDING
legacy storage permission = REMOVED
```


### Branding source update — user-provided LogoFlat assets

User-provided source files received:

```text
LogoFlat.png
LogoFlat.svg
LogoFlat_White.png
LogoFlat_White.svg
```

Raw SVG masters stored in mobile source:

```text
mobile/cordova/resources/branding/LogoFlat.svg
mobile/cordova/resources/branding/LogoFlat_White.svg
```

Opening handoff uses the exact white SVG source at:

```text
mobile/cordova/www/img/LogoFlat_White.svg
```

Visual contract:

```text
Android native splash
= color logo + white background

then, without artificial delay

local Cordova loading shell
= white logo + official green (#119450)
= short 320 ms opacity/scale entrance
= disabled by prefers-reduced-motion

then

controlled InAppBrowser SisFour Web
```

This is a handoff/fade, not a video or heavy animation. Native launcher/splash raster resources remain subject to clean-install real-device acceptance.


### Architecture allowlist extension — file.download

User instruction to continue generic POST-output on 6 Oktober 2026 approves one narrow G4 bridge capability extension:

```text
file.download
```

This capability does not identify a module or endpoint. It means only: perform a validated same-origin authenticated attachment request using the request semantics already chosen by Web, then hand the server-approved file to Android Save As.

It does not permit arbitrary native execution, arbitrary file read, external network access, role decisions, or business-rule decisions.


### Final Android launcher asset pipeline — PRE-BUILD FREEZE

Final source pipeline:

```text
user LogoFlat.svg (square vector)
→ deterministic 1024×1024 transparent PNG
→ launcher-master-1024.png
├─ drawable-nodpi/sisfour_brand_logo.png
│  ├─ adaptive foreground
│  └─ native splash icon
└─ legacy launcher derivatives
   ├─ ldpi      36×36
   ├─ mdpi      48×48
   ├─ hdpi      72×72
   ├─ xhdpi     96×96
   ├─ xxhdpi   144×144
   └─ xxxhdpi  192×192
```

`config.xml` no longer references the retired 2319×2299 mobile master. The retired file is removed from `mobile/cordova/resources/branding/`.

This is the branding state to be used by the next and only PRE-BUILD-FREEZE clean debug build.
