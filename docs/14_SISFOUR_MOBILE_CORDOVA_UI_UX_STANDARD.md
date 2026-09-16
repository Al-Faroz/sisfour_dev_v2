# SisisFour Mobile & Cordova UI/UX Standard

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 16 September 2026  
**Baseline UI:** Sneat Free v3 + Bootstrap 5.3.x + CodeIgniter 4  
**Target utama:** Android portrait melalui browser mobile dan Cordova WebView  
**Role prioritas:** Pimpinan, BK, Guru, Guru+Wali Kelas, Siswa

> Dokumen ini adalah turunan khusus dari `13_CI4_SNEAT_GLOBAL_LAYOUT_STANDARD.md` dan `11_UI_UX — SisisFour.md`. Bila aturan mobile di sini lebih ketat, dokumen ini yang berlaku untuk mobile/WebView.

## 1. Arsitektur UI Mobile

SisisFour mempertahankan satu source UI CI4/Sneat untuk browser dan Cordova WebView.

```text
CI4 View + Sneat/Bootstrap + Vanilla JS
        ↓
responsive Web UI
        ├─ desktop/laptop browser
        ├─ mobile browser
        └─ Cordova Android WebView
```

Tidak membuat ulang seluruh aplikasi menjadi SPA mobile kedua hanya untuk APK.

## 2. Role Mobile-First

```text
Pimpinan
BK
Guru
Guru + Wali Kelas
Siswa
```

Admin/Operator tetap responsive, tetapi matrix/administrasi berat boleh memiliki exception terdokumentasi.

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

Vertical scroll normal. Jangan menyelesaikan tabel lebar dengan font ekstrem kecil atau `min-width` besar.

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

Setiap table mengklasifikasikan P1/P2/P3/P4 dan tidak memaksakan semua kolom desktop ke portrait.

## 5. Identity Rule — Name First

> **Search with Name + Identifier, display primarily by Name.**

Nama lengkap adalah identitas visual utama. NISN/NIK/NIP sekunder untuk search, pencocokan, verifikasi, disambiguasi, import/export, audit, dan integrasi.

## 6. Mobile Density / Touch

Target mobile dibuat compact pada spacing, bukan mengecilkan touch target.

```text
page padding X/Y       12px
section gap             8–12px
card padding           10–12px dashboard
card padding umum      12px
page title             18px
section/card title     15–16px
body                   13–14px
table body             12.5–13px
helper/meta            11–12px
KPI value              20–24px
primary button          44–48px
quick action            44–48px
icon row action         ±40px minimum
attendance option       40–44px
```

## 7. Dashboard Compact Standard

```text
page title compact
4 KPI = grid 2×2
Quick Action = grid 2×2 bila relevan
3–5 item penting
Lihat Semua untuk detail
```

Dashboard adalah launcher pekerjaan/monitoring, bukan laporan penuh.

## 8. Role Priority

### Pimpinan

```text
kelas belum Presensi
jadwal belum Jurnal
EWS
Catatan Pelanggaran / exception
trend singkat
```

Konseling BK bukan sumber widget/detail Pimpinan.

### Guru

```text
tugas hari ini
Presensi belum diisi
Jurnal belum diisi
jadwal berikutnya
```

Jalur Presensi/Jurnal ideal maksimal 1–2 tap.

### Guru + Wali

```text
tugas mengajar
kondisi kelas wali
Presensi kelas
EWS kelas
quick action kelas
```

Wali context Guru dan tidak otomatis mendapat Konseling BK.

### BK

Setelah G3.3.1:

```text
Konseling Proses / follow-up terdekat
Catatan Pelanggaran terbaru/berat
Tindak Lanjut yang perlu perhatian
EWS
Prestasi
```

Poin Pelanggaran tidak ditampilkan, dihitung, diranking, atau dijadikan metadata aktif.

### Siswa

```text
status kehadiran hari ini
rekap bulan ini
quick action
Kartu Pelajar
Prestasi
Catatan Pelanggaran diri sesuai permission
```

Konseling tidak tampil pada experience Siswa.

## 9. Quick Action

Recommended baseline:

```text
Pimpinan: Rekap Presensi / Jurnal / EWS / Laporan
Guru:     Presensi / Jurnal / Jadwal / Profil
Wali:     Presensi / Rekap Kelas / EWS / Data Siswa
BK:       Konseling / Catatan Pelanggaran / EWS / Prestasi
Siswa:    Presensi Saya / Kartu / Prestasi / Profil
```

Quick Action tidak menambah permission.

## 10. Adaptive Catatan Pelanggaran

Desktop dapat menampilkan:

```text
Tanggal | Siswa | Pelanggaran | Kategori | Keterangan | Tindak Lanjut | Aksi
```

Mobile canonical:

```text
Catatan Pelanggaran                 Aksi
Ahmad Fulan                         ⋮
Terlambat
14 Sep · Sedang
```

Keterangan dan histori tindak lanjut masuk Detail. **Tidak ada poin** pada cell metadata maupun ranking.

## 11. Konseling BK Mobile

Konseling adalah workflow rahasia untuk Admin/Operator/BK sesuai permission.

Tahap 1 mobile:

```text
Kelas
→ Siswa
→ Tanggal
→ Pertemuan ke-
→ Bentuk Layanan
→ Cara Hadir
→ Bidang
→ Topik
→ Simpan
```

Tahap 2:

```text
Identitas
→ Perkembangan Tersimpan
→ Uraian Masalah
→ Hasil Pembahasan & Kesepakatan
→ Rencana Berikutnya
→ Tanggal Berikutnya
→ Status
→ Simpan
```

Form panjang harus satu kolom pada portrait; modal/detail tidak boleh overflow horizontal; textarea tetap usable saat keyboard terbuka. `Perkembangan Tersimpan` adalah snapshot record persisten, bukan histori 1:N.

## 12. Row Actions

Mobile tidak menampilkan deretan action panjang. Gunakan satu action utama + menu `⋮` untuk secondary action bila perlu.

Destructive action tetap memakai confirmation project, bukan native `confirm()`.

## 13. Presensi Siswa

```text
Siswa              Status
Ahmad Fulan        [H] [S] [I] [A]
7-A
```

NISN tidak tampil rutin. Setiap control punya label aksesibel penuh.

## 14. Jadwal Guru/Wali

Mobile canonical:

```text
Jam | Kelas / Mapel | Aksi
```

Sesi menjadi metadata/badge. Presensi/Jurnal action compact.

## 15. Filter / Form Mobile

Maksimal 1–2 filter utama langsung terlihat. Filter tambahan collapse/offcanvas.

Search entity tetap SearchableSelect.

Control rutin target ±40px; primary action 44–48px. Workflow panjang seperti Presensi, Jurnal, Catatan Pelanggaran, Tindak Lanjut, dan Konseling boleh memakai sticky save/action bar bila tidak menutup content/keyboard.

## 16. Sticky Action / Safe Area

Gunakan safe-area token:

```css
--sisfour-safe-top: env(safe-area-inset-top, 0px);
--sisfour-safe-right: env(safe-area-inset-right, 0px);
--sisfour-safe-bottom: env(safe-area-inset-bottom, 0px);
--sisfour-safe-left: env(safe-area-inset-left, 0px);
```

Sticky/fixed action dan fullscreen modal footer harus memperhitungkan safe-area.

## 17. Modal / Keyboard Android

Form/detail kompleks memakai `modal-fullscreen-sm-down modal-dialog-scrollable` bila sesuai.

Wajib diuji:

- input tidak tertutup keyboard;
- modal/form dapat vertical-scroll;
- sticky footer tidak menutup field;
- searchable select terlihat;
- textarea Jurnal/Konseling nyaman;
- tidak ada horizontal overflow.

## 18. Android Back Contract

```text
modal terbuka      → Back menutup modal
sidebar terbuka    → Back menutup sidebar
offcanvas/dropdown → Back menutup layer
halaman detail     → Back kembali
form dirty         → confirm sebelum meninggalkan
root/dashboard     → double-back / exit confirmation sesuai keputusan APK
```

## 19. Network & Offline State

Cordova target online-first.

Tidak ada silent offline queue untuk:

```text
Presensi
Jurnal
Catatan Pelanggaran / Tindak Lanjut
Konseling
Prestasi
```

Network failure:

```text
jelaskan gagal
pertahankan input user bila aman
berikan retry
jangan tampilkan sukses palsu
```

## 20. Busy Guard

Mutation wajib:

```text
button disabled
spinner / teks Menyimpan...
server response
success/error
restore state pada gagal
```

## 21. Geolocation

Jangan meminta lokasi saat dashboard/page load. Minta hanya ketika workflow memang membutuhkan dan server tetap menentukan validitas radius.

## 22. Session/Auth WebView

G3 menjaga Web login/session. G4 melakukan spike untuk strategy wrapper. Session expiry Fetch harus tampil sebagai sesi berakhir lalu arahkan login, bukan merender HTML login di area tabel/modal.

## 23. Download / External Link

Sebelum APK final, uji PDF Kartu, XLSX export yang dipakai role, open/share file, external browser, dan intent yang relevan.

## 24. Pagination / Performance

Role operasional default 10–15 row per page bila paginated. Dashboard hanya 3–5 item per list. Hindari ratusan DOM row.

## 25. Admin/Operator Exception

Horizontal-scroll hanya exception untuk matrix administratif dua dimensi yang tidak dapat direduksi tanpa kehilangan fungsi. Data matrix untuk role operasional harus punya presentation adaptif.

## 26. QA Acceptance

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

Per 16 September 2026, finalisasi cross-role G3.3.1 telah PASS pada localhost dan hosting smoke. G3.4 melanjutkan redesign Dashboard/Workflow BK tanpa mengubah contract privacy/poin yang sudah final.

## 27. Phase

```text
G2 = stabilization
G3 = Mobile Role UI + WebView readiness
G4 = Cordova APK packaging/integration
```