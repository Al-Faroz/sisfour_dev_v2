# SisisFour Mobile & Cordova UI/UX Standard

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 17 September 2026
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

Admin/Operator tetap responsive; matrix administratif berat boleh exception terdokumentasi.

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

Gunakan prioritas informasi → metadata merge → hide secondary columns → Detail/modal/offcanvas.

## 4. Maximum Columns Mobile

```text
read-only sederhana           3–4 kolom
nama panjang                  2–3 kolom
dengan action                 2–3 + action compact
presensi interaktif           2–3 kolom
workflow status               3 kolom
dashboard summary             2–3 kolom
```

## 5. Identity Rule — Name First

> **Search with Name + Identifier, display primarily by Name.**

Nama lengkap adalah identitas visual utama. NISN/NIK/NIP sekunder untuk search, verifikasi, import/export, audit, dan integrasi.

## 6. Mobile Density / Touch

```text
page padding X/Y       12px
section gap             8–12px
card padding umum      12px
page title             18px
section/card title     15–16px
body                   13–14px
helper/meta            11–12px
KPI value              20–24px
primary button          44–48px
quick action            44–48px
icon row action         ±40px minimum
```

Compact dibuat lewat spacing, bukan mengecilkan touch target.

## 7. Dashboard Compact Standard

```text
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

```text
Konseling Proses / follow-up terdekat
Catatan Pelanggaran terbaru/berat
Tindak Lanjut yang perlu perhatian
EWS
Prestasi
```

Poin Pelanggaran tidak ditampilkan/dihitung/diranking.

### Siswa

```text
status kehadiran
rekap
Kartu Pelajar
Prestasi
Catatan Pelanggaran diri sesuai permission
```

Konseling tidak tampil pada experience Siswa.

## 9. Filter Periodik Mobile

Keputusan global 17 September 2026 berlaku juga di mobile:

```text
periodic/history table -> Tahun Ajaran tersedia
initial                 -> Tahun Ajaran aktif
Reset                   -> Tahun Ajaran aktif
history                 -> selectable bila didukung
```

Maksimal 1–2 filter utama langsung terlihat. Jika filter banyak, Tahun Ajaran termasuk filter utama; filter tambahan dapat stack/collapse/offcanvas.

Desktop boleh memakai 2 baris filter; mobile **tidak** meniru dua baris desktop secara paksa, melainkan stack/collapse sesuai ruang.

## 10. Adaptive Catatan Pelanggaran

Desktop dapat menampilkan kolom lebih lengkap. Mobile canonical menonjolkan:

```text
Nama siswa
Pelanggaran
Tanggal · Kategori
Aksi compact
```

Keterangan dan histori tindak lanjut masuk Detail. Tidak ada poin.

## 11. Konseling BK Mobile

Konseling adalah workflow rahasia untuk Admin/Operator/BK sesuai permission.

### Tahap 1

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

### Tahap 2 — Pertemuan Awal

```text
Identitas
→ Uraian Masalah
→ Hasil Pembahasan & Kesepakatan
→ Rencana Berikutnya
→ Tanggal Berikutnya
→ Status
→ Simpan
```

### Tindak Lanjut 1:N

Keputusan 17 September 2026:

```text
Riwayat Tindak Lanjut
→ Tambah/Edit Tindak Lanjut
→ tanggal
→ perkembangan
→ hasil/kesepakatan
→ rencana berikutnya
→ tanggal berikutnya
→ status
```

Riwayat tampil sebelum form Tambah/Edit agar context sebelumnya terlihat. Multiple follow-up harus tampil sebagai vertical timeline/card, bukan tabel lebar.

Tidak ada action Delete parent Konseling maupun Delete follow-up.

Form panjang satu kolom pada portrait; textarea usable saat keyboard terbuka; tidak boleh overflow horizontal.

## 12. Historical Rencana Mobile

Jika nilai Rencana tersimpan sudah dihapus dari Settings, record lama tetap menampilkan `X (tersimpan)` dan dapat dipertahankan. Opsi legacy tidak boleh muncul sebagai pilihan global pada record lain.

Rule ini berlaku pada parent maupun Tindak Lanjut Konseling 1:N.

## 13. Row Actions

Mobile tidak menampilkan deretan action panjang. Gunakan satu action utama + menu `⋮` untuk secondary action bila perlu.

Untuk Konseling rework, follow-up hanya mempunyai Tambah/Edit; tidak ada Delete.

## 14. Presensi Siswa

```text
Siswa              Status
Ahmad Fulan        [H] [S] [I] [A]
7-A
```

NISN tidak tampil rutin; tetap tersedia untuk search/disambiguasi.

## 15. Jadwal Guru/Wali

Mobile canonical:

```text
Jam | Kelas / Mapel | Aksi
```

Sesi menjadi metadata/badge.

## 16. Filter / Form Mobile

- 1–2 filter utama langsung terlihat;
- filter tambahan stack/collapse/offcanvas;
- SearchableSelect untuk entity besar;
- control rutin ±40px;
- primary action 44–48px;
- sticky action boleh dipakai bila tidak menutup content/keyboard.

## 17. Sticky Action / Safe Area

Gunakan safe-area token:

```css
--sisfour-safe-top: env(safe-area-inset-top, 0px);
--sisfour-safe-right: env(safe-area-inset-right, 0px);
--sisfour-safe-bottom: env(safe-area-inset-bottom, 0px);
--sisfour-safe-left: env(safe-area-inset-left, 0px);
```

## 18. Modal / Keyboard Android

Form/detail kompleks memakai `modal-fullscreen-sm-down modal-dialog-scrollable` bila sesuai.

Wajib diuji:

- input tidak tertutup keyboard;
- modal/form vertical-scroll;
- sticky footer tidak menutup field;
- searchable select terlihat;
- textarea Konseling nyaman;
- timeline follow-up tetap readable;
- tidak ada horizontal overflow.

## 19. Android Back Contract

```text
modal terbuka      → Back menutup modal
sidebar terbuka    → Back menutup sidebar
offcanvas/dropdown → Back menutup layer
halaman detail     → Back kembali
form dirty         → confirm project sebelum meninggalkan
root/dashboard     → double-back / exit sesuai keputusan APK
```

## 20. Network & Offline State

Cordova target online-first. Tidak ada silent offline queue untuk Presensi, Jurnal, Catatan Pelanggaran/Tindak Lanjut, Konseling/Tindak Lanjut, atau Prestasi.

Network failure harus menjelaskan gagal, mempertahankan input bila aman, memberi retry, dan tidak menampilkan sukses palsu.

## 21. Busy Guard

Mutation wajib:

```text
button disabled
spinner / Menyimpan...
server response
success/error
restore state pada gagal
```

## 22. Session/Auth WebView

G3 menjaga Web login/session. G4 melakukan spike strategy wrapper. Session expiry Fetch harus tampil sebagai sesi berakhir lalu arahkan login.

## 23. Download / External Link

Sebelum APK final, uji PDF Kartu, XLSX export yang dipakai role, open/share file, external browser, dan intent relevan.

## 24. Pagination / Performance

Role operasional default 10–15 row per page bila paginated. Dashboard 3–5 item per list. Hindari ratusan DOM row.

## 25. Admin/Operator Exception

Horizontal-scroll hanya exception untuk matrix administratif dua dimensi yang tidak dapat direduksi tanpa kehilangan fungsi. Data role operasional harus adaptif.

## 26. QA Acceptance

Halaman role operasional ACC bila:

```text
no body horizontal overflow
no table horizontal scroll
no action clipped
primary action mudah ditemukan
touch target nyaman
font tidak ekstrem kecil
filter periodik default aktif
filter tidak menghabiskan layar
modal kompleks nyaman dengan keyboard
safe area aman
loading/error/success jelas
mutation anti double-submit
360px usable
```

Untuk G3.3.1 rework, baseline hosting smoke lama tidak menutup perubahan 17 September; periodic filter + follow-up 1:N harus UAT ulang.

## 27. Phase

```text
G2 = stabilization
G3 = Mobile Role UI + WebView readiness
G4 = Cordova APK packaging/integration
```
