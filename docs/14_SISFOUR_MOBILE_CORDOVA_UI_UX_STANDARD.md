# SisisFour Mobile & Cordova UI/UX Standard

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 14 September 2026
**Baseline UI:** Sneat Free v3 + Bootstrap 5.3.x + CodeIgniter 4
**Target utama:** Android portrait melalui browser mobile dan Cordova WebView
**Role prioritas:** Pimpinan, BK, Guru, Guru+Wali Kelas, Siswa

> Dokumen ini adalah turunan khusus dari `13_CI4_SNEAT_GLOBAL_LAYOUT_STANDARD.md` dan `11_UI_UX — SisisFour.md`. Bila aturan mobile di sini lebih ketat, dokumen ini yang berlaku untuk mobile/WebView.

## 1. Arsitektur UI Mobile

SisisFour mempertahankan **satu source UI CI4/Sneat** untuk browser dan Cordova WebView.

```text
CI4 View + Sneat/Bootstrap + Vanilla JS
        ↓
responsive Web UI
        ├─ desktop/laptop browser
        ├─ mobile browser
        └─ Cordova Android WebView
```

Tidak membuat ulang seluruh aplikasi menjadi SPA mobile kedua hanya untuk APK.

API/JWT tetap tersedia untuk endpoint API dan kebutuhan integrasi/native yang memang memerlukannya, tetapi bukan alasan menduplikasi UI Web.

## 2. Role Mobile-First

```text
Pimpinan
BK
Guru
Guru + Wali Kelas
Siswa
```

Admin/Operator tetap responsive, tetapi pekerjaan matrix/administrasi berat boleh memiliki exception yang terdokumentasi.

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

## 3. Core Rule — No Horizontal Table Scroll

Untuk role operasional prioritas:

```text
body horizontal scroll   = DILARANG
table horizontal scroll  = DILARANG
nested horizontal scroll = DILARANG
```

“Tabel muat satu layar” berarti muat **lebar viewport portrait**. Vertical scrolling normal.

Jangan menyelesaikan tabel lebar dengan font ekstrem kecil atau `min-width` besar.

Gunakan:

```text
prioritas informasi
→ gabungkan metadata
→ sembunyikan kolom sekunder
→ Detail/modal/offcanvas untuk data lengkap
```

## 4. Maximum Columns Mobile

```text
read-only sederhana           3–4 kolom
nama panjang                  2–3 kolom
dengan action                 2–3 + action compact
presensi interaktif           2–3 kolom
workflow status               3 kolom
dashboard summary             2–3 kolom
```

Setiap table mengklasifikasikan data:

```text
P1 wajib mobile
P2 digabung ke P1
P3 tablet/desktop
P4 detail-only
```

## 5. Identity Rule — Name First

Canonical:

> **Search with Name + Identifier, display primarily by Name.**

Nama lengkap adalah identitas visual utama. NISN/NIK/NIP adalah identifier sekunder untuk:

```text
search
pencocokan
verifikasi
disambiguasi nama sama
import/export
audit/integrasi
```

### Siswa

```text
P1 Nama lengkap
P2 Kelas / status / context
P3 NISN
P4 NIK
```

### Guru/Wali

```text
P1 Nama lengkap
P2 Mapel / kelas / peran
P3 NIP
P4 NIK
```

Search tetap mendukung nama + identifier.

Search result:

```text
Ahmad Fulan
7-A · NISN 0012345678

Siti Aminah, S.Pd.
Matematika · NIP 1980...
```

Jika nama sama, urutan disambiguasi:

```text
Nama → context manusia → identifier resmi
```

Pada tabel operasional mobile, NISN/NIP/NIK tidak menjadi kolom tersendiri kecuali halaman memang untuk audit/pencocokan identifier.

## 6. Mobile Density Tokens

Target mobile dibuat compact pada spacing, **bukan mengecilkan touch target**.

```text
page padding X/Y       12px
section gap             8–12px
card padding           10–12px dashboard
card padding umum      12px
page title             18px
section/card title     15–16px
body                   13–14px
table body             12.5–13px
table header           11.5–12px
helper/meta            11–12px
badge                  11–12px
KPI value              20–24px
```

## 7. Touch Target

Karena target APK/WebView:

```text
primary button          44–48px
quick action            44–48px
icon row action         ±40px minimum
attendance option       40–44px
```

Jangan mengecilkan target sentuh hanya agar tabel muat.

## 8. Dashboard Compact Standard

Dashboard role adalah launcher pekerjaan/monitoring, bukan laporan penuh.

Canonical mobile:

```text
page title compact
4 KPI = grid 2×2
Quick Action = grid 2×2 bila relevan
3–5 item penting
Lihat Semua untuk detail
```

Gap KPI 8px, padding 10–12px.

Top 20 pada dashboard menjadi Top 5 + `Lihat Semua`.

## 9. Role Priority

### Pimpinan

Prioritas:

```text
kelas belum presensi
jadwal belum jurnal
EWS
kasus BK / exception
trend singkat
```

Data jumlah siswa/guru/pegawai/kelas ditempatkan sebagai statistik umum di bawah.

### Guru

Prioritas:

```text
tugas hari ini
presensi belum diisi
jurnal belum diisi
jadwal berikutnya
```

Jalur ke Presensi/Jurnal idealnya maksimal 1–2 tap.

### Guru + Wali

Urutan:

```text
tugas mengajar
kondisi kelas wali
presensi kelas
EWS kelas
quick action kelas
```

Wali tetap context Guru, bukan role baru.

### BK

Prioritas:

```text
kasus urgent
EWS
tindak lanjut
pelanggaran berat
prestasi
```

### Siswa

Prioritas:

```text
status kehadiran hari ini
rekap bulan ini
quick action
kartu pelajar
prestasi
riwayat/kasus
```

Siswa menggunakan pola self-service, bukan data-grid Admin.

## 10. Quick Action

Recommended 2×2:

```text
Pimpinan: Rekap Presensi / Jurnal / EWS / Laporan
Guru:     Presensi / Jurnal / Jadwal / Profil
Wali:     Presensi / Rekap Kelas / EWS / Data Siswa
BK:       Tambah Kasus / EWS / Pelanggaran / Prestasi
Siswa:    Presensi Saya / Kartu / Prestasi / Profil
```

Sidebar/offcanvas Sneat tetap navigation utama. Bottom navigation tidak menjadi default.

## 11. Table Adaptive Pattern

Contoh Catatan Kasus desktop:

```text
Tanggal | Siswa | Pelanggaran | Kategori | Poin | Keterangan | Aksi
```

Mobile:

```text
Kasus                         Aksi
Ahmad Fulan                   ⋮
Terlambat
14 Sep · Sedang · 10 poin
```

Keterangan/history masuk Detail.

Primary cell dapat memakai:

```html
<td class="sisfour-cell-primary">
  <div class="sisfour-cell-title">Ahmad Fulan</div>
  <small class="sisfour-cell-meta">Terlambat · 14 Sep · 10 poin</small>
</td>
```

## 12. Mobile Table CSS Primitive

```css
@media (max-width: 575.98px) {
  .sisfour-mobile-table {
    width: 100%;
    table-layout: fixed;
  }

  .sisfour-mobile-table th,
  .sisfour-mobile-table td {
    padding: .45rem .4rem;
    font-size: .78rem;
    line-height: 1.25;
    vertical-align: middle;
    overflow-wrap: anywhere;
  }

  .sisfour-mobile-table th {
    font-size: .72rem;
    font-weight: 600;
  }

  .sisfour-cell-title {
    font-weight: 600;
  }

  .sisfour-cell-meta {
    display: block;
    margin-top: .15rem;
    font-size: .7rem;
    color: var(--bs-secondary-color);
  }
}
```

Dilarang pada tabel operasional mobile:

```text
min-width: 700/900px
white-space: nowrap pada seluruh tabel
```

## 13. Row Actions

Mobile tidak menampilkan:

```text
[Detail] [Edit] [Hapus] [Riwayat]
```

Gunakan:

```text
1 action utama langsung
+ menu ⋮ untuk secondary actions
```

Action destructive tetap confirmation dan tidak hanya bergantung warna.

## 14. Presensi Siswa

Mobile canonical:

```text
Siswa              Status
Ahmad Fulan        [H] [S] [I] [A]
7-A
```

NISN tidak tampil rutin.

Option:

```text
H Hadir
S Sakit
I Izin
A Alpha
```

Setiap control punya `aria-label` dan `title` lengkap.

Status control dapat memakai 4-column grid compact dengan touch target 40–44px.

## 15. Jadwal Guru/Wali

Desktop dapat menampilkan Jam/Kelas/Mapel/Sesi/Presensi/Jurnal.

Mobile canonical:

```text
Jam | Kelas / Mapel | Aksi
```

Sesi menjadi metadata/badge di primary cell. Presensi/Jurnal menjadi action compact.

## 16. Filter Mobile

Maksimal 1–2 filter utama langsung terlihat.

Filter tambahan:

```text
collapse
atau offcanvas mobile
```

Date pair boleh 2 kolom jika masih nyaman di 360px.

Search entity tetap menggunakan SearchableSelect.

## 17. Forms

Control rutin target ±40px; primary action 44–48px.

Mobile form umumnya satu kolom.

Untuk workflow panjang:

```text
Presensi
Jurnal
Kasus/Tindak Lanjut
```

boleh memakai sticky save/action bar.

## 18. Sticky Action & Safe Area

```css
:root {
  --sisfour-safe-top: env(safe-area-inset-top, 0px);
  --sisfour-safe-right: env(safe-area-inset-right, 0px);
  --sisfour-safe-bottom: env(safe-area-inset-bottom, 0px);
  --sisfour-safe-left: env(safe-area-inset-left, 0px);
}
```

Sticky footer:

```css
padding-bottom: calc(.75rem + var(--sisfour-safe-bottom));
```

Safe area wajib diperhitungkan pada sticky/fixed element dan fullscreen modal footer.

## 19. Modal Mobile

Form/detail kompleks:

```html
modal-fullscreen-sm-down modal-dialog-scrollable
```

Confirmation sederhana tetap SweetAlert2.

Modal panjang ideal:

```text
header tetap
body scroll
footer action mudah dijangkau
```

Tidak boleh horizontal overflow.

## 20. Keyboard Android

Wajib diuji di WebView:

- input tidak tertutup keyboard;
- modal/form bisa scroll;
- sticky footer tidak menutupi field;
- searchable select terlihat;
- textarea jurnal/BK usable;
- focus tidak menyebabkan layout jump berat.

Gunakan `type` dan `inputmode` sesuai data.

## 21. Android Back Contract

Target Cordova:

```text
modal terbuka      → Back menutup modal
sidebar terbuka    → Back menutup sidebar
offcanvas/dropdown → Back menutup layer
halaman detail     → Back kembali
form dirty         → confirm sebelum meninggalkan
root/dashboard     → double-back / confirmation exit sesuai keputusan APK
```

Back tidak boleh langsung menutup APK saat user sedang mengisi Presensi/Jurnal.

## 22. Network & Offline State

Cordova target tetap **online-first**.

Tidak ada silent offline queue untuk:

```text
Presensi
Jurnal
Kasus
Prestasi
```

Saat offline/network gagal:

```text
jelaskan gagal
pertahankan input user bila aman
berikan retry
jangan tampilkan sukses palsu
```

Data akademik hanya dinyatakan tersimpan setelah server mengonfirmasi sukses.

## 23. Busy Guard

Mutation wajib:

```text
button disabled
spinner / teks Menyimpan...
anti success/error
restore state pada gagal
```

Double-submit tidak boleh menghasilkan mutation ganda.

## 24. Geolocation

Jangan minta lokasi saat dashboard/page load.

Minta hanya saat workflow benar-benar membutuhkan.

State UX:

```text
Memeriksa izin lokasi...
Mengambil lokasi...
Lokasi ditemukan
Izin ditolak
GPS tidak aktif
Lokasi tidak akurat
Di luar radius
```

Server tetap menentukan validitas radius.

## 25. Session/Auth dalam WebView

G3 harus menjaga Web login/session tetap stabil.

G4 Cordova melakukan spike khusus untuk memutuskan dan membuktikan strategy session/auth wrapper.

Jika session expired saat Fetch:

```text
jangan render HTML login di area tabel/modal
→ tampilkan sesi berakhir
→ arahkan login
```

API/JWT tetap dipakai untuk `/api/*`, bukan otomatis mengganti seluruh Web auth.

## 26. Download & External Links

Sebelum APK final, uji:

```text
PDF Kartu
XLSX export bila role menggunakan
open/share file
external browser
mailto/tel/WhatsApp/Maps bila ada
```

Internal SisisFour tetap di WebView. External destination dibuka terkontrol di app/browser yang sesuai.

## 27. Cordova Mode Class

Saat `deviceready`, wrapper dapat menambah:

```html
<html class="sisfour-cordova">
```

untuk behavior/layout WebView yang memang berbeda.

Jangan gunakan class ini untuk menduplikasi seluruh UI.

## 28. Navbar Mobile

Shell tetap Sneat, tetapi mobile dapat dibuat lebih compact.

Target praktis:

```text
navbar sekitar 52–56px bila theme memungkinkan tanpa merusak shell
page title 18px
page header gap 8–12px
```

Description dapat disembunyikan pada mobile bila hanya mengulang context dan tidak penting.

## 29. Pagination Mobile

Role operasional default:

```text
10–15 row per page
```

Admin desktop dapat tetap 25/50/100.

Mobile paginator compact:

```text
summary pendek
‹ 1 2 3 ›
```

## 30. Performance

Dashboard hanya memuat 3–5 item per list. Tabel paginated. Hindari ratusan DOM row.

Chart mobile bila digunakan:

```text
height ±180–220px
series terbatas
legend sederhana
```

## 31. Empty / Alert / Loading

Mobile empty state compact, bukan ruang kosong besar.

Petunjuk rutin gunakan small/help text atau collapse, bukan alert satu layar penuh.

Loading sebaiknya per-card/per-table, bukan overlay seluruh page kecuali proses global.

## 32. Status Color

```text
success   selesai / hadir / aktif
warning   perhatian / sakit / pending
info      izin / contextual
 danger   alpha / gagal / critical
secondary netral / belum / N/A
primary   action utama
```

Makna warna tidak berubah antar role.

## 33. Admin/Operator Exception

No-horizontal-scroll diterapkan sebisa mungkin global.

Exception hanya untuk matrix administratif yang benar-benar membutuhkan dua dimensi penuh, misalnya permission/jadwal matrix tertentu.

Jika data matrix dikonsumsi role operasional, buat summary/adaptive presentation, bukan meminta user memutar/scroll horizontal.

## 34. Recommended Reusable Classes

```text
sisfour-mobile-table
sisfour-cell-primary
sisfour-cell-title
sisfour-cell-meta
sisfour-action-cell
sisfour-action-menu
sisfour-kpi-grid
sisfour-kpi-card
sisfour-quick-grid
sisfour-mobile-note
sisfour-mobile-sticky-action
sisfour-mobile-compact
sisfour-cordova
```

Semua reusable primitive masuk `assets/css/sisfour-ui.css`, bukan file patch berulang.

## 35. QA Acceptance

Halaman role operasional ACC bila:

```text
no body horizontal overflow
no table horizontal scroll
no important action clipped
primary action mudah ditemukan
touch target nyaman
font tidak ekstrem kecil
KPI mobile 2 kolom
filter tidak menghabiskan layar
modal kompleks nyaman dengan keyboard
safe area aman
loading/error/success jelas
mutation anti double-submit
360px usable
```

Secara browser, cek bahwa `scrollWidth <= clientWidth` untuk page dan tidak ada wrapper table operasional yang membutuhkan scroll horizontal.

## 36. Implementasi Berdasarkan Phase

Dokumen ini **tidak memerintahkan refactor besar di G2**.

```text
G2 = fixing/regression/stabilization
G3 = implementasi Mobile Role UI + WebView readiness
G4 = Cordova APK packaging/integration
```

Detail phase mengikuti `00_POLA_PENGERJAAN___SisisFour.md`.
