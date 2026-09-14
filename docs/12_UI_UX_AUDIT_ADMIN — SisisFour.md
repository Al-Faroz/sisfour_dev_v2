# Audit UI/UX Admin — SisisFour

**Status:** Working Audit / berlaku terhadap branch G2  
**Tanggal Audit:** 14 September 2026  
**Branch:** `fix/g2-master-data-20260913`  
**Standar Acuan:** `docs/11_UI_UX — SisisFour.md`

> Audit ini adalah pemeriksaan source UI seluruh halaman Admin terhadap standar canonical. Status visual final tetap membutuhkan browser regression pada desktop/laptop/tablet/mobile setelah refactor selesai.

## 1. Skala Status

```text
BASE OK   = struktur utama sudah dekat standar; perlu migrasi class/foundation kecil
MINOR     = ada deviasi visual/komponen tetapi tidak perlu redesign flow
REFACTOR  = melanggar pola canonical atau berpotensi layout shift/inconsistency nyata
N/A       = layout khusus/print, tidak dibandingkan langsung dengan Admin shell
```

## 2. Temuan Global P0

### P0-01 — Page title tidak satu kontrak

Controller umumnya mengirim `title`, sedangkan `_navbar.php` dan `_header.php` membaca `pageTitle`.

Dampak:

- navbar dapat tetap menampilkan `Dashboard` pada halaman lain;
- browser `<title>` dapat tidak sesuai judul halaman;
- screenshot user telah menunjukkan kasus ini pada Master Tahun Ajaran.

Target:

```text
title dari controller
-> dinormalisasi sebagai pageTitle di BaseController/layout
-> navbar + <title> menggunakan nilai sama
```

### P0-02 — Belum ada primitive page layout bersama

Saat audit, header/filter/table/modal ditulis ulang di banyak View memakai kombinasi Bootstrap yang mirip tetapi tidak identik.

Target primitive:

```text
sisfour-page-header
sisfour-page-actions
sisfour-filter-card
sisfour-filter-actions
sisfour-table-card
sisfour-table-actions
sisfour-pager
sisfour-empty-state
```

### P0-03 — Layout utama masih dibentuk lewat JavaScript pada beberapa halaman

Ditemukan pola:

- Export Master Kelas ditambahkan JS;
- Export Master Mapel ditambahkan JS;
- Export Mapping Wali ditambahkan JS;
- Export Master Pelanggaran ditambahkan JS;
- filter Tingkat Kenaikan Kelas ditambahkan JS;
- navigation Profile Guru ditambahkan JS;
- search Nama Guru Presensi Mengajar dibuat ad-hoc lewat JS.

Target: seluruh struktur ini pindah ke View. JS hanya behavior.

### P0-04 — Pagination belum satu komponen

Ada tiga pola bersamaan:

1. `SisfourPagination` canonical;
2. manual Prev/Next dalam card footer;
3. client-side slice yang memasang `SisfourPagination` setelah data dimuat.

Target: semua list pageable memakai `SisfourPagination` dengan summary + nomor halaman + limit.

## 3. Temuan Foundation

### UI-F01 — Paginator footer berpotensi double spacing

`SisfourPagination.mount()` membuat/menempatkan container di `.card-footer`, sedangkan `.sisfour-pager` sendiri memiliki padding dan border-top.

Target: satu layer yang mengontrol padding/border.

### UI-F02 — SearchableSelect accessibility

Native `<select>` disembunyikan dan input visual baru dibuat, tetapi label `for` masih menunjuk ke select tersembunyi.

Target: label/aria diarahkan juga ke visible combobox.

### UI-F03 — CSS legacy

`assets/css/custom.css` masih berisi Select2/DataTables override, sedangkan business UI saat ini memakai komponen internal dan file tersebut tidak menjadi foundation aktif.

Target: pola reusable hanya di `sisfour-ui.css`; legacy override tidak menjadi referensi implementation baru.

### UI-F04 — Confirmation tidak seragam

Sebagian modul memakai SweetAlert2, tetapi Master Pelanggaran masih memakai `confirm()` / `alert()`.

Target: SweetAlert2 untuk flow business interaktif.

### UI-F05 — Table bottom spacing

Sebagian table memakai `mb-0`, sebagian tidak. Table tanpa `mb-0` dapat menyisakan area kosong di card.

Target: seluruh data table dalam card menggunakan `mb-0`.

## 4. Audit Semua Halaman Admin

| # | Halaman | Status | Temuan Utama | Target Normalisasi |
|---|---|---|---|---|
| 1 | Dashboard | MINOR | Header mendekati standar, tetapi KPI mencampur `h3/h4`, bobot card tidak seragam | `sisfour-page-header`, KPI card canonical, typography konsisten |
| 2 | Presensi Siswa | BASE OK | Header/filter/card cukup konsisten; table status memang lebar | Migrasi class foundation; pastikan mobile table scroll lokal |
| 3 | Presensi Mengajar | REFACTOR | Search Nama Guru dibuat input tambahan lewat JS | Guru select memakai SearchableSelect dari markup View |
| 4 | Rekap Presensi | REFACTOR | Footer masih manual Prev/Next + page info | Ganti dengan `SisfourPagination` |
| 5 | EWS Radar | MINOR | Pager sudah canonical; table card tidak memiliki heading konsisten | Tambah table-card header/standard state |
| 6 | Master Guru | MINOR | Struktur kuat; masih memakai kombinasi class manual dan table tanpa kontrak foundation penuh | Migrasi page/header/filter/table/action classes; `mb-0` |
| 7 | Master Pegawai | MINOR | Pola sama dengan Guru | Migrasi ke foundation yang sama |
| 8 | Master Siswa | MINOR | Pola dekat Guru/Pegawai | Migrasi ke foundation yang sama |
| 9 | Master Kelas | REFACTOR | Tombol Export ditambahkan JS setelah render; table belum `mb-0` | Export statis di View + foundation |
| 10 | Master Tahun Ajaran | MINOR | Struktur cukup rapi; info alert cukup dominan; legacy `card-datatable` | Foundation + compact rule notice; non-pageable tetap tanpa pager |
| 11 | Master Mata Pelajaran | REFACTOR | Export/wrapper action dibuat JS | Export statis di View + foundation |
| 12 | Mapping Wali Kelas | REFACTOR | Export dibuat JS; pagination baru dipasang client-side | Export statis + foundation + paginator canonical |
| 13 | Master Jadwal Guru | MINOR | Header/action/filter baik; info alert panjang dan filter row belum `align-items-end` | Foundation, compact callout, standard filter alignment |
| 14 | Penempatan / Pindah Kelas | MINOR | Header/filter/table cukup jelas tetapi belum foundation; workflow modal perlu responsive regression | Foundation + paginator/empty state konsisten |
| 15 | Kenaikan Kelas | REFACTOR | Filter Tingkat dibuat dari JS dan disisipkan sebelum card | Filter statis dalam View + paginator canonical |
| 16 | Mutasi Siswa | MINOR | Layout workflow sederhana tetapi belum foundation | Header/filter/table/modal standard |
| 17 | Kelulusan | MINOR | Layout workflow sederhana tetapi belum foundation | Header/table/modal/confirmation standard |
| 18 | Matrix Presensi | BASE OK | Filter grid dan table baik; perlu foundation dan empty state visual seragam | Foundation + table card canonical |
| 19 | Export Presensi | MINOR | Halaman action/form khusus; hierarchy action perlu disamakan | Page header + form card standard |
| 20 | Laporan Jurnal | MINOR | Filter/report mengikuti pola sendiri | Standard header/filter/table/export action |
| 21 | Catatan Kasus | REFACTOR | Pagination manual Prev/Next; table/action/footer berbeda dari master | `SisfourPagination` + standard table card |
| 22 | Master Pelanggaran | REFACTOR | Header sangat minimal, Export dibuat JS, confirm/alert native, table tanpa card header | Full list template canonical + SweetAlert2 |
| 23 | Prestasi Siswa | MINOR | Struktur business cukup baik tetapi perlu penyamaan filter/table/pager | Foundation + paginator canonical bila pageable |
| 24 | Kartu Pelajar | REFACTOR | Banyak kelompok action dalam satu card; manual Prev/Next; berisiko padat di mobile | Pisahkan filter/action secara visual + canonical pager/mobile actions |
| 25 | Manajemen User | REFACTOR | Header tidak stack secara canonical; manual Prev/Next; modal form footer tidak punya Batal | Foundation + canonical pager + modal footer |
| 26 | Menu & Role | REFACTOR | Tabel sangat lebar dan action per row; mobile hierarchy belum jelas | Standard header/table; responsive horizontal strategy; action sticky/nowrap bila perlu |
| 27 | Setting Sistem | MINOR | Beberapa form card memakai tombol di body, spacing/action berbeda antar section | Standard form-card footer dan section heading |
| 28 | Backup | MINOR | Header cukup baik; table `text-nowrap` dapat terlalu lebar di mobile; warning besar | Foundation + responsive table + compact warning |
| 29 | Log Activity | REFACTOR | Manual Prev/Next; filter action terpisah dari row field | Canonical filter actions + `SisfourPagination` |
| 30 | Profile Guru | REFACTOR | Navigation Biodata/Personalia/Portofolio ditambahkan JS setelah render | Tabs/navigation ditulis statis di View + CSS responsive canonical |

## 5. Audit Layout Shell

### Main

`main.php` sudah benar memakai:

```text
layout-wrapper
layout-container
layout-page
content-wrapper
container-xxl flex-grow-1 container-p-y
```

Tidak perlu mengganti shell Sneat.

### Navbar

Struktur user dropdown baik. Blocker utamanya adalah sinkronisasi title.

### Sidebar

Sidebar sudah data-driven dan bukan tempat hardcode role. Struktur dipertahankan.

Perlu browser check:

- collapsed desktop;
- long menu name;
- active/open child;
- mobile overlay.

## 6. Audit Komponen

### Pagination

Status: **perlu konsolidasi**.

Target satu API:

```javascript
window.SisfourPagination.mount(...)
```

Manual `Prev/Next` akan dimigrasikan bertahap.

### Searchable Select

Status: **fungsi dasar baik, accessibility perlu hardening**.

Perlu:

- id input visual;
- label binding/aria-labelledby;
- proper invalid/disabled mirroring;
- tidak membuat ad-hoc search input per halaman.

### Alert/Notification

Status: **campuran**.

Target:

- SweetAlert2 untuk confirm/result mutation;
- inline alert untuk error/loading area;
- `form-text` untuk petunjuk biasa.

### Modal

Status: **campuran**.

Target:

- seluruh mutation modal punya `Batal + aksi utama`;
- busy state;
- `modal-dialog-scrollable` untuk modal panjang;
- no horizontal overflow.

## 7. Prioritas Refactor

### Batch A — Foundation / P0

1. normalisasi `title/pageTitle`;
2. tambah primitive global CSS di `sisfour-ui.css`;
3. perbaiki paginator double spacing;
4. harden searchable-select label/accessibility;
5. tetapkan table/action/modal classes.

### Batch B — Hapus Layout Shift JS

1. Master Kelas Export -> View;
2. Master Mapel Export -> View;
3. Mapping Wali Export -> View;
4. Master Pelanggaran Export -> View;
5. Kenaikan filter Tingkat -> View;
6. Profile Guru tabs -> View;
7. Presensi Mengajar search -> SearchableSelect.

### Batch C — Pagination

Migrasikan manual pager:

- Rekap Presensi;
- Catatan Kasus;
- Kartu Pelajar;
- Manajemen User;
- Log Activity;
- halaman lain yang masih Prev/Next manual setelah runtime sweep.

### Batch D — Visual Sweep Semua Admin

Urutan:

```text
Dashboard
Presensi
Master Data
Manajemen Siswa
Laporan
BK/Prestasi
Kartu
Settings
Backup/Log
Profile
```

Setiap halaman harus lolos empat viewport canonical.

## 8. Acceptance Criteria

UI Admin dinyatakan konsisten bila:

- seluruh page title navbar/browser cocok;
- seluruh page header memakai pola yang sama;
- tidak ada tombol/filter/tab utama yang baru muncul setelah JS load;
- filter controls sejajar dan responsive;
- data table memakai card/table/pager pattern yang sama;
- manual Prev/Next tidak tersisa bila dataset pageable;
- modal mutation punya Cancel + primary action;
- SweetAlert2 menjadi confirmation standard;
- search entity memakai SearchableSelect;
- tidak ada overflow halaman pada 360 px;
- table overflow terjadi hanya di `.table-responsive`;
- loading/empty/error terlihat jelas;
- tidak ada browser console error;
- business rule, route, permission, dan database behavior tidak berubah oleh refactor visual.

## 9. Batas Audit

Audit ini memeriksa source aktual dan struktur seluruh halaman Admin pada branch G2. Pixel-level final tetap harus diuji di browser lokal karena CSS theme, panjang data aktual, viewport, browser, dan WebView dapat menghasilkan masalah yang tidak dapat dibuktikan hanya dari source.
