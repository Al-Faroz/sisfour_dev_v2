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
G4.1C Auth download bridge      IMPLEMENTED / UAT PENDING
G4.1D Dashboard + geolocation   IMPLEMENTED / UAT PENDING
G4.1E Upload/import chooser     SOURCE READY / UAT PENDING
G4.1F POST output + Back        IMPLEMENTED / BUILD + UAT PENDING
G4.1G APK branding              IMPLEMENTED CANDIDATE / UAT PENDING
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
CookieManager session cookie + user-agent
        ↓
Android DownloadManager
        ↓
public Downloads + Android completion notification
```

Rules:

- hanya download HTTPS dari host `sisfour.mtsn4jombang.sch.id`;
- server route/permission/session tetap memutuskan boleh/tidak;
- cookie tidak dikirim ke host eksternal;
- tidak menyimpan password/token baru;
- nama file memakai `Content-Disposition`/MIME dari response lalu disanitasi;
- Android <= 9 meminta storage permission hanya saat diperlukan;
- Android 10+ tidak meminta legacy storage permission;
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
file tersimpan di Android Downloads
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
Dashboard helper muncul pada halaman internal
Dashboard helper tidak muncul di login/root/dashboard
Dashboard helper tidak menutup sticky action penting
Presensi geofence -> permission allow -> koordinat diterima server
Presensi geofence -> permission deny -> error jelas / no false success
Jurnal Guru Hadir -> location bekerja
jalur yang tidak membutuhkan geofence tidak memunculkan permission location
permission tidak muncul saat app startup/dashboard
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

## 28. G4.1F — POST Output + Android Back Adapter

Beberapa output Web tidak menghasilkan navigasi GET attachment:

- Statistik PDF mengirim `POST /statistik/export/pdf` dengan filter, CSRF, dan optional PNG chart payload;
- Kartu Pelajar massal mengirim `POST /kartu/cetak-massal`;
- Kartu JPG ZIP mengirim `POST /kartu/export-jpg-zip`.

G4.1F tidak mengirim blob besar melalui base64 bridge. Local shell mengirim metadata request allowlisted ke native plugin, lalu Android melakukan POST same-origin dengan cookie session + CSRF dan menulis response attachment langsung ke Downloads.

Allowlist native POST hanya:

```text
/statistik/export/pdf
/kartu/cetak-massal
/kartu/export-jpg-zip
```

Security gate:

- HTTPS + exact host `sisfour.mtsn4jombang.sch.id`;
- port default/443 only;
- no URL user-info;
- method fixed POST;
- redirect response ditolak agar HTML login tidak tersimpan sebagai file;
- response wajib 2xx + `Content-Disposition: attachment`;
- request header yang diterima native hanya CSRF/Accept/X-Requested-With;
- payload form dibatasi jumlah field dan ukuran;
- server tetap memutuskan session/RBAC/CSRF/business validation.

Android Back memakai injected history sentinel karena InAppBrowser default langsung `goBack()` atau close dialog.

Order adapter:

```text
modal.show      -> hide
offcanvas.show  -> hide
dropdown.show   -> hide
mobile sidebar  -> close
dirty page      -> confirmation
detail/history  -> real history back
/dashboard or / -> double-back within 1.8s -> app exit
```

Dirty state diprobe melalui existing `beforeunload` contract; adapter tidak membuat business dirty-state baru.

Source/static audit pada implementation head:

```text
shell.js JavaScript parse               PASS
plugin JS parse                         PASS
package.json/package-lock JSON          PASS
POST endpoint native allowlist present  PASS
redirect disabled                       PASS
attachment guard                        PASS
MediaStore Downloads path               PASS
Java brace/static structure             PASS
Gradle/Cordova compile                   PENDING user terminal
real-device UAT                          PENDING
```

## 29. G4.1G — APK Branding / Visual Identity

G4.1G adalah acceptance item sebelum release final, tetapi tidak boleh mengorbankan feature parity G4.1.

Current candidate:

```text
official source = uploads/settings/branding/logo_20260908_181247_ce029c58.png
source blob     = bcecd043b6230f6d8350b7641cb5439422e2a34b
mobile master   = mobile/cordova/resources/branding/mtsn4jombang-logo-master.png
launcher icon   = official logo, legacy + adaptive candidate
adaptive bg     = white
native splash   = official logo on white
local shell     = official logo + lightweight reduced-motion-safe breathe
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
