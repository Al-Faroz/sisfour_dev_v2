# Audit UI/UX Admin — SisisFour

**Status:** Current G2 Source Audit / Browser Gate PASS / Closure Pending
**Tanggal Acuan:** 15 September 2026
**Branch:** `fix/g2-master-data-20260913`
**Acuan:** `11_UI_UX — SisisFour.md` + `13_CI4_SNEAT_GLOBAL_LAYOUT_STANDARD.md`

> Dokumen ini menyatakan kondisi source Admin saat ini dan hasil gate browser G2. Ia bukan daftar bug historis.

## 1. Kondisi Foundation

Source G2 saat ini sudah memiliki foundation berikut:

```text
Sneat v3 layout-menu-fixed layout-compact
container-xxl shell
page title normalization
viewport-fit=cover
sisfour-page-header
sisfour-page-actions
sisfour-filter-card
sisfour-filter-actions
sisfour-table-card
sisfour-modal-actions
SisfourPagination
SisfourSearchableSelect
active-year-default initializer
SweetAlert2 confirmation pada flow yang sudah dinormalisasi
```

Static control utama pada halaman yang telah dinormalisasi berada di View, bukan disisipkan setelah load melalui JavaScript.

## 2. Login & Branding

Source G2 menetapkan:

- password visibility toggle login eksplisit dan tidak bergantung pada dashboard script;
- `icon_sekolah` menjadi favicon login dan authenticated shell bila physical file tersedia;
- favicon memakai cache-busting;
- uploaded icon dapat menjadi apple-touch-icon;
- Setting Sistem menampilkan preview/path branding;
- branding reload setelah upload sukses.

Status focused browser regression: **PASS**.

## 3. Admin Layout Contract

Admin/Operator tetap memakai pola desktop/laptop sebagai surface utama:

```text
Page Header
Filter/Form Card
Data/Table Card
Pagination
Modal/Detail
```

Admin table boleh menggunakan horizontal-scroll hanya bila data memang bersifat matrix/lebar dan tidak dapat direduksi tanpa kehilangan fungsi.

Aturan no-horizontal-table-scroll yang lebih ketat berlaku terutama pada role operasional mobile di G3, bukan alasan merombak seluruh Admin pada G2.

## 4. Default Tahun Ajaran — Current Source Contract

Selector Tahun Ajaran pada surface baca/histori default ke periode aktif, tanpa helper/alert yang hanya menjelaskan default tersebut.

```text
Master Siswa          → default aktif
Master Kelas          → default aktif
Mapping Wali          → default aktif
Assign Wali           → default aktif + options langsung dimuat
Master Jadwal Guru    → default aktif
Import Jadwal Guru    → default aktif
Laporan Jurnal        → default aktif dari service
Matrix Presensi       → default aktif dari service
Export Presensi       → default aktif dari service
```

Reset mengembalikan selector ke Tahun Ajaran aktif. Pemilihan histori tetap tersedia pada halaman yang memang mendukung histori.

Workflow berikut tidak diberi selector periode tambahan karena business context-nya memang current active period:

```text
Penempatan/Pindah
Mutasi
Kelulusan
Kenaikan
Presensi operasional
Jurnal operasional
Kartu Pelajar operasional
```

Master Tahun Ajaran tetap menampilkan semua periode karena fungsi halaman adalah pengelolaan lifecycle periode.

Status focused smoke default Tahun Ajaran: **PASS**.

## 5. Halaman Admin — Final G2 Browser Status

Focused browser regression G2 dilakukan pada 15 September 2026 menggunakan data aktual hosting.

| Area | Source State G2 | Final Gate |
|---|---|---|
| Login | password toggle + branding | **PASS** |
| Branding/Favicon | login + authenticated shell | **PASS** |
| Title/Navbar | normalized page context | **PASS** |
| Filter/Pagination/Export | normalized areas | **PASS** |
| Presensi Mengajar | SearchableSelect Guru | **PASS** |
| Profile Guru mobile | secondary navigation statis | **PASS** |
| Modal + Responsive | global modal safety + normalized markup | **PASS** |
| Browser Console | focused changed surfaces | **PASS** |
| Master Mapel | edit nama dengan kode sendiri + duplicate guard | **PASS** |
| Default Tahun Ajaran | active-year default/reset | **PASS** |
| Penempatan/Pindah | F14 | **PASS** |
| Kenaikan | F14 | **PASS** |
| Mutasi | F14 | **PASS** |
| Kelulusan | F14 | **PASS** |

Modul lain yang tidak termasuk focused smoke final tetap mengikuti regression domain yang sudah diselesaikan sebelumnya dan tidak dibuka ulang tanpa perubahan source terkait.

## 6. F14 Manajemen Siswa — Final G2 Status

F14 telah dinyatakan **PASS** pada 15 September 2026.

Cakupan yang sudah diterima:

```text
Penempatan/Pindah           PASS
Mutasi                      PASS
Kelulusan                   PASS
Restore lifecycle           PASS
Kenaikan 7 → 8              PASS
Kenaikan 8 → 9              PASS
Partial promotion           PASS
Promotion guards            PASS
Anti-double-process         PASS
Membership integrity        PASS
History integrity           PASS
Progress per kelas          PASS
Year-aware Master Siswa     PASS
```

Kenaikan tetap dibedakan tegas dari `Siapkan Genap`: kenaikan adalah Genap → Ganjil tahun akademik berikutnya, sedangkan Siapkan Genap adalah Ganjil → Genap pada tahun yang sama.

## 7. G2 UI Scope Boundary

G2 hanya memperbaiki blocker/regression pada UI Admin yang sudah disentuh branch.

Dilarang memperluas G2 menjadi:

```text
redesign seluruh dashboard role
adaptive table role operasional
Cordova bridge
Android Back handler
APK packaging
```

Pekerjaan tersebut berada pada G3/G4.

## 8. Browser Regression G2 — Final Result

Minimum viewport smoke:

```text
390×844
1024×768
1366×768
```

Focused browser gate yang disepakati:

```text
Login                              PASS
Branding/Favicon                   PASS
Title/Navbar                       PASS
Filter/Pagination/Export           PASS
Presensi Mengajar Guru Search      PASS
Profile Guru mobile                PASS
Modal + responsive                 PASS
Console                            PASS
F11 final Mapel runtime retest     PASS
Active-year default/reset          PASS
```

Status G2.4: **PASS**.

## 9. Critical G2 UI Regression

Semua critical item yang ditetapkan untuk focused G2.4 telah **PASS**:

1. Login show/hide password.
2. Favicon icon sekolah pada login dan authenticated page.
3. Branding/preview pada Setting Sistem.
4. Filter/pagination/export pada area yang disentuh.
5. Presensi Mengajar Guru search.
6. Pagination/filter pada area normalized.
7. Profile Guru tabs pada mobile.
8. Page title/navbar tidak kembali ke Dashboard secara salah.
9. Selector Tahun Ajaran yang relevan default/reset ke periode aktif dan histori tetap selectable bila didukung.
10. Modal panjang/responsive dan browser console.
11. F11 edit Mapel dengan kode sendiri.

## 10. G3 Mobile Re-audit

Setelah G2 closed, seluruh surface Pimpinan/BK/Guru/Wali/Siswa diaudit ulang terhadap:

```text
14_SISFOUR_MOBILE_CORDOVA_UI_UX_STANDARD.md
```

Audit G3 berbeda dengan audit Admin ini. Target G3 adalah no-horizontal-table-scroll, mobile density, touch target, adaptive information hierarchy, dan WebView readiness.

## 11. Acceptance G2 Admin

G2 Admin UI focused gate dinyatakan **PASS** karena:

- tidak ada syntax/runtime blocker yang ditemukan pada focused regression;
- title dan navigation benar;
- static action tidak muncul terlambat akibat JS;
- paginator/filter/export yang diuji bekerja;
- modal/confirmation bekerja;
- selector Tahun Ajaran mengikuti default aktif tanpa UI noise;
- branding/login regression PASS;
- responsive smoke PASS;
- browser console bersih;
- business rule F06–F14 tetap benar.

Sisa G2 bukan lagi browser regression, melainkan **G2.5 closure**: repository hygiene recheck, final docs/PR sanity, static gate final, dan approval eksplisit sebelum merge.

Pixel-level redesign role mobile **bukan gate merge G2**; itu gate G3.
