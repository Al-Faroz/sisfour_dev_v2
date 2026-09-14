# Audit UI/UX Admin — SisisFour

**Status:** Current G2 Source Audit / Browser ACC Pending
**Tanggal Acuan:** 14 September 2026
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

Status: **source ready, browser regression pending**.

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

## 4. Halaman Admin — Current Source Status

| Area | Source State G2 | Gate sebelum G2 closed |
|---|---|---|
| Dashboard Admin | foundation diterapkan | browser hierarchy/spacing |
| Presensi Siswa | canonical header/filter/card | fungsi + responsive smoke |
| Presensi Mengajar | SearchableSelect Guru | search + jurnal regression |
| Rekap Presensi | paginator canonical | filter/pager browser |
| EWS | paginator/layout normalized | browser pagination |
| Master Guru | responsive compatibility + existing master workflow | CRUD/import/export/browser |
| Master Pegawai | responsive compatibility + existing master workflow | CRUD/import/export/browser |
| Master Siswa | existing canonical master pattern | CRUD/import/export/browser |
| Master Kelas | Export + pagination normalized | export/pager browser |
| Tahun Ajaran | layout normalized | Siapkan Genap UI smoke |
| Mapel | Export + pagination normalized | export/pager browser |
| Mapping Wali | Export + pagination normalized | export/pager/browser |
| Jadwal Guru | layout/filter/import normalized | F13 regression |
| Penempatan/Pindah | foundation applied | F14 regression |
| Kenaikan | filter Tingkat + pagination | F14 regression |
| Mutasi | foundation applied | F14 regression |
| Kelulusan | foundation applied | F14 regression |
| Matrix Presensi | canonical structure | report browser |
| Export Presensi | form/action normalized | export browser |
| Laporan Jurnal | canonical table/pager | filter/export browser |
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

## 5. G2 UI Scope Boundary

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

## 6. Browser Regression Wajib G2

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
pagination
modal
loading/empty/error
console error
primary mutation
```

## 7. Critical G2 UI Regression

Wajib sebelum PR close:

1. Login show/hide password.
2. Favicon icon sekolah pada login dan authenticated page.
3. Setting Sistem upload branding.
4. Master Kelas/Mapel/Mapping Wali/Pelanggaran export.
5. Presensi Mengajar Guru search.
6. EWS/Rekap/Kenaikan pagination.
7. Profile Guru tabs pada mobile.
8. Page title/navbar tidak kembali ke Dashboard secara salah.

## 8. G3 Mobile Re-audit

Setelah G2 closed, seluruh surface Pimpinan/BK/Guru/Wali/Siswa diaudit ulang terhadap:

```text
14_SISFOUR_MOBILE_CORDOVA_UI_UX_STANDARD.md
```

Audit G3 berbeda dengan audit Admin ini. Target G3 adalah no-horizontal-table-scroll, mobile density, touch target, adaptive information hierarchy, dan WebView readiness.

## 9. Acceptance G2 Admin

G2 Admin UI dianggap stabil bila:

- tidak ada syntax/runtime blocker;
- title dan navigation benar;
- static action tidak muncul terlambat akibat JS;
- paginator yang sudah dinormalisasi bekerja;
- mutation modal/confirmation bekerja;
- export yang ditambah menghasilkan file;
- branding/login regression PASS;
- tidak ada body horizontal overflow yang tidak disengaja;
- business rule F06–F14 tetap benar.

Pixel-level redesign role mobile **bukan gate merge G2**; itu gate G3.
