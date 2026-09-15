# Audit UI/UX Admin — SisisFour

**Status:** Current G2 Source Audit / Remaining Browser Gate Pending
**Tanggal Acuan:** 15 September 2026
**Branch:** `fix/g2-master-data-20260913`
**Acuan:** `11_UI_UX — SisisFour.md` + `13_CI4_SNEAT_GLOBAL_LAYOUT_STANDARD.md`

> Dokumen ini menyatakan kondisi source Admin saat ini dan gate yang masih perlu dibuktikan di browser. Ia bukan daftar bug historis.

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

Status: **source ready, focused browser regression tetap bagian gate G2.4**.

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

## 5. Halaman Admin — Current Source Status

| Area | Source State G2 | Gate sebelum G2 closed |
|---|---|---|
| Dashboard Admin | foundation diterapkan | browser hierarchy/spacing |
| Presensi Siswa | canonical header/filter/card | fungsi + responsive smoke |
| Presensi Mengajar | SearchableSelect Guru | search + jurnal regression |
| Rekap Presensi | paginator canonical | filter/pager browser |
| EWS | paginator/layout normalized | browser pagination |
| Master Guru | responsive compatibility + existing master workflow | CRUD/import/export/browser |
| Master Pegawai | responsive compatibility + existing master workflow | CRUD/import/export/browser |
| Master Siswa | canonical master + default Tahun aktif | focused active-year smoke PASS; CRUD/import/export browser |
| Master Kelas | Export + pagination + default Tahun aktif | focused active-year smoke PASS; export/pager browser |
| Tahun Ajaran | layout normalized | Siapkan Genap UI smoke |
| Mapel | Export + pagination normalized | export/pager browser |
| Mapping Wali | Export + pagination + default Tahun aktif | focused active-year smoke PASS; export/pager browser |
| Jadwal Guru | layout/filter/import + default Tahun aktif | F13 core PASS; remaining browser/import smoke sesuai gate |
| Penempatan/Pindah | foundation applied | **F14 PASS** |
| Kenaikan | filter Tingkat + pagination + workflow guards | **F14 PASS** |
| Mutasi | lifecycle tabs/history/restore | **F14 PASS** |
| Kelulusan | alumni tab/restore | **F14 PASS** |
| Matrix Presensi | canonical structure + default Tahun aktif | focused active-year smoke PASS; report browser |
| Export Presensi | form/action + default Tahun aktif | focused active-year smoke PASS; export browser |
| Laporan Jurnal | canonical table/pager + default Tahun aktif | focused active-year smoke PASS; filter/export browser |
| Catatan Kasus | canonical pager/layout | BK regression |
| Master Pelanggaran | canonical layout/export/SweetAlert | CRUD/export browser |
| Prestasi | canonical table/pager | CRUD/export browser |
| Kartu Pelajar | hierarchy/pager normalized | preview/download browser |
| Manajemen User | canonical header/filter/modal/pager | CRUD/reset browser |
| Menu & Role | canonical shell | matrix browser |
| Setting Sistem | canonical shell + branding preview | upload/favicon/maintenance |
| Backup | canonical shell | create/download/delete |
| Log Activity | canonical filter/pager | filter/export browser |
| Profile Guru | mobile secondary nav statis | tabs/profile browser |

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

## 8. Browser Regression Wajib G2

Minimum viewport smoke:

```text
390×844
1024×768
1366×768
```

Untuk Admin, 360px juga dicek untuk body overflow dan critical action, tetapi matrix administratif boleh exception lokal.

Per halaman yang diubah, cek:

```text
page title/navbar
sidebar active/open
header/action alignment
filter
active-year default/reset bila ada
pagination
modal
loading/empty/error
console error
primary mutation
```

## 9. Critical G2 UI Regression

Wajib sebelum PR close:

1. Login show/hide password.
2. Favicon icon sekolah pada login dan authenticated page.
3. Setting Sistem upload branding.
4. Master Kelas/Mapel/Mapping Wali/Pelanggaran export.
5. Presensi Mengajar Guru search.
6. EWS/Rekap/Kenaikan pagination.
7. Profile Guru tabs pada mobile.
8. Page title/navbar tidak kembali ke Dashboard secara salah.
9. Selector Tahun Ajaran yang relevan default/reset ke periode aktif dan histori tetap selectable bila didukung.

Item 9 focused smoke: **PASS**.

## 10. G3 Mobile Re-audit

Setelah G2 closed, seluruh surface Pimpinan/BK/Guru/Wali/Siswa diaudit ulang terhadap:

```text
14_SISFOUR_MOBILE_CORDOVA_UI_UX_STANDARD.md
```

Audit G3 berbeda dengan audit Admin ini. Target G3 adalah no-horizontal-table-scroll, mobile density, touch target, adaptive information hierarchy, dan WebView readiness.

## 11. Acceptance G2 Admin

G2 Admin UI dianggap stabil bila:

- tidak ada syntax/runtime blocker;
- title dan navigation benar;
- static action tidak muncul terlambat akibat JS;
- paginator yang sudah dinormalisasi bekerja;
- mutation modal/confirmation bekerja;
- export yang ditambah menghasilkan file;
- selector Tahun Ajaran mengikuti default aktif tanpa UI noise;
- branding/login regression PASS;
- tidak ada body horizontal overflow yang tidak disengaja;
- business rule F06–F14 tetap benar.

F14 sudah **PASS**, tetapi G2 keseluruhan belum dinyatakan closed sampai remaining browser regression, final PR review, dan approval merge selesai.

Pixel-level redesign role mobile **bukan gate merge G2**; itu gate G3.
