# UI/UX Standard — SisisFour

**Status:** Canonical / Fresh SSOT
**Tanggal Acuan:** 17 September 2026
**Stack:** CodeIgniter 4 + Sneat Free v3 + Bootstrap 5.3.x + Vanilla JavaScript

> Dokumen ini menetapkan kontrak UI/UX SisisFour secara umum. Baseline vendor dan pola reusable CI4 ada di `13_CI4_SNEAT_GLOBAL_LAYOUT_STANDARD.md`. Aturan mobile/WebView yang lebih ketat ada di `14_SISFOUR_MOBILE_CORDOVA_UI_UX_STANDARD.md`.

## 1. Hirarki UI

```text
13 CI4 + Sneat Global
        ↓
11 UI/UX SisisFour
        ↓
14 Mobile & Cordova UI/UX
        ↓
11 Role Experience
```

Business rule tetap milik Service/dokumen domain. UI tidak boleh memperluas permission atau scope.

## 2. Source of Truth UI

```text
Shell             app/Views/main.php
Partials          _header / _navbar / _sidebar / _footer / _scripts
Foundation CSS    assets/css/sisfour-ui.css
Search select     assets/js/components/searchable-select.js
Pagination        assets/js/components/pagination.js
Active year       assets/js/components/active-year-default.js
Feature View      app/Views/<module>/...
Feature JS        assets/js/<module>/...
Vendor            assets/vendor/...
```

Vendor Sneat/Bootstrap tidak dipatch langsung.

## 3. Shell

Canonical root:

```html
<html lang="id" class="layout-menu-fixed layout-compact">
```

Viewport memakai `viewport-fit=cover`. Feature tidak boleh membuat width yang memicu body horizontal overflow.

## 4. Title Contract

Controller mengirim satu title canonical:

```text
title/pageTitle normalized
→ browser <title>
→ navbar context
→ page heading
```

## 5. Markup First

Elemen statis yang sudah diketahui saat server render wajib ditulis di View:

```text
page header
action utama
Export/Import/Template
filter utama
tabs/navigation
card/table shell
modal shell
empty anchor/pager anchor
```

JavaScript menangani behavior/data, bukan membangun shell halaman setelah load.

## 6. Page Header

```html
<div class="sisfour-page-header">
  <div class="sisfour-page-header__copy">
    <h4 class="fw-bold mb-1">Judul</h4>
    <p class="text-muted mb-0">Deskripsi singkat.</p>
  </div>
  <div class="sisfour-page-actions">...</div>
</div>
```

Desktop horizontal. Di bawah `xl/1200px`, header boleh stack bila action padat.

## 7. Spacing & Density

Desktop mengikuti Sneat native:

```text
section gap 16–24px
field gap   16px
action gap   8px
card pad    24px native
```

Mobile density mengikuti dokumen `14`, bukan sekadar mengecilkan semua font/control.

## 8. Typography & Identity

```text
Font          Public Sans
Body desktop  15px
Page title    24px desktop
Card title    18px desktop
Metadata      13px
```

Nama manusia adalah identitas visual utama. NISN/NIP/NIK adalah identifier sekunder untuk search/verifikasi/disambiguasi.

## 9. Filter — Global Contract

Canonical:

```html
<div class="card sisfour-filter-card mb-4">
  <div class="card-body">
    <div class="row g-3 align-items-end">...</div>
  </div>
</div>
```

Rules:

- label selalu ada;
- entity besar memakai SearchableSelect;
- enum kecil boleh native select;
- Apply/Tampilkan mengembalikan pagination ke awal;
- Reset mengembalikan semua filter ke canonical default;
- mobile filter dapat stack/collapse/offcanvas sesuai `14`;
- field tidak dipersempit secara berlebihan hanya agar muat satu baris.

### 9.1 Filter Banyak di Desktop

Jika filter mulai padat atau field berjumlah banyak, **jangan dipaksa satu baris**. Gunakan dua baris `row g-3 align-items-end` yang seimbang.

Pedoman:

```text
<= 4 field sederhana                  boleh 1 baris
5+ field / ada search + date range    prioritaskan 2 baris
field penting                         beri lebar lebih besar
Reset/Tampilkan                       dikelompokkan sebagai action
```

Contoh canonical Konseling:

```text
Baris 1: Tahun Ajaran | Pencarian | Kelas
Baris 2: Status | Bidang | Dari | Sampai | Reset/Tampilkan
```

Tujuannya readability dan konsistensi spacing, bukan mengejar jumlah baris minimum.

### 9.2 Tahun Ajaran untuk Tabel Periodik

Keputusan global 17 September 2026:

> **Setiap halaman tabel/list periodik atau historis yang datanya memiliki dimensi Tahun Ajaran wajib menyediakan filter Tahun Ajaran.**

Kontrak:

```text
initial value = Tahun Ajaran aktif
Reset         = Tahun Ajaran aktif
manual select = histori boleh dipilih bila domain mendukung
request       = id_tahun eksplisit pada list/export
create baru   = Service snapshot Tahun Ajaran aktif; jangan percaya period dari client
```

Default harus terlihat langsung dari option terpilih. Jangan menambah alert/helper hanya untuk menjelaskan bahwa default adalah periode aktif.

Contoh surface periodik:

```text
Master Siswa/Kelas bila membaca histori periode
Mapping/Assign Wali
Master/Import Jadwal Guru
Laporan Jurnal
Matrix/Export Presensi
Catatan Pelanggaran
Konseling BK
Prestasi Siswa
surface periodik lain yang mempunyai id_tahun / period context
```

Tidak diberi filter Tahun Ajaran palsu:

```text
User
Permission
Menu
Setting Sistem
Log Activity
Master Pelanggaran
Master Tahun Ajaran itu sendiri
workflow current-state seperti Kenaikan/Mutasi/Kartu operasional bila kontraknya selalu periode aktif
```

Jika tabel legacy periodik belum menyimpan snapshot periode, schema/business layer harus dibenahi lebih dulu. UI tidak boleh membuat filter Tahun Ajaran yang secara teknis memetakan data dengan tebakan.

## 10. Forms

- label terhubung ke control;
- validation dekat field;
- textarea memakai rows, bukan tinggi fixed;
- mutation punya busy guard;
- data identifier panjang diperlakukan string;
- mobile complex form dapat fullscreen modal/sticky action sesuai `14`.

Workflow histori 1:N harus menampilkan **riwayat sebelum form Tambah/Edit** bila keputusan baru membutuhkan konteks sebelumnya.

## 11. SearchableSelect

Gunakan komponen internal, bukan input pencarian ad-hoc.

Search entity manusia mendukung:

```text
nama
+ NISN/NIP/NIK bila relevan
```

Hasil menonjolkan nama; identifier menjadi metadata.

## 12. Button Hierarchy

```text
Primary   Tambah/Simpan/Proses/Muat
Secondary Reset/Batal/History
Export    outline-success
Danger    destructive
```

Satu konteks idealnya memiliki satu primary action. Icon-only wajib accessible label.

## 13. Card

List/table:

```text
card
→ card-header
→ table/list
→ card-footer pager/action bila perlu
```

Table di card selalu `mb-0`. Jangan menambah card di dalam card tanpa kebutuhan hierarchy.

## 14. Table — General

Desktop canonical:

```html
<table class="table table-hover align-middle mb-0">
```

- status badge;
- action paling kanan;
- empty/loading/error visible;
- description boleh wrap;
- jangan nowrap seluruh tabel.

Untuk Pimpinan/BK/Guru/Wali/Siswa, aturan `14` berlaku:

```text
NO horizontal table scroll
adaptive columns
metadata merge
Detail untuk data sekunder
```

Admin/Operator matrix boleh exception terdokumentasi.

## 15. Pagination

Gunakan satu komponen `SisfourPagination`.

```text
Admin desktop umum: 25 / 50 / 100
Role operasional mobile: 10–15 bila paginated
```

Tidak boleh menyisakan paginator lama paralel dengan komponen baru.

## 16. Modal

Mutation form:

```text
header
body
footer: Batal + primary action
```

Gunakan `modal-dialog-scrollable` untuk content panjang. Mobile complex form/detail menggunakan `modal-fullscreen-sm-down` bila sesuai.

## 17. Confirmation & Feedback

Business confirmation memakai SweetAlert2 atau komponen project.

Dilarang untuk final UI baru:

```javascript
alert()
confirm()
prompt()
```

Inline alert tetap untuk persistent page state.

## 18. Loading / Empty / Error

Setiap area async:

```text
loading
ready
empty
error
```

Mutation:

```text
disabled + busy
server response
success/error visible
restore input/state pada failure bila aman
```

## 19. Dashboard

Dashboard role mengikuti experience, bukan meniru Admin.

```text
KPI penting
quick action bila perlu
3–5 item ringkas
Lihat Semua untuk detail
```

## 20. Tabs / Secondary Navigation

Tab utama ditulis di View. Mobile boleh scroll horizontal untuk tabs/navigation; ini berbeda dari tabel data. Tab penting tidak boleh hilang.

## 21. Sidebar

Data-driven dari menu/role/context. Menu bukan security boundary.

## 22. Branding

`nama_sekolah`, `logo_sekolah`, `icon_sekolah` berasal dari setting sistem. Login dan authenticated shell memakai source branding yang sama.

## 23. Login

Show/hide password memakai semantic button dan update `input type`, icon, `aria-label`, dan `aria-pressed`.

## 24. CSS Architecture

Reusable pattern masuk `assets/css/sisfour-ui.css`. CSS module hanya untuk layout yang benar-benar unik. Dilarang membuat patch chain `fix.css`, `fix2.css`, `final-fix.css`.

## 25. JavaScript Architecture

Feature JS dibatasi root element. JS boleh fetch/render/bind/pagination/busy/validation UX, tetapi tidak membuat header/filter/tab/action statis setelah load.

`active-year-default.js` boleh menjadi helper generic, tetapi server tetap sumber kebenaran period context dan pilihan eksplisit server/user tidak boleh ditimpa.

## 26. Accessibility

Minimum:

- label valid;
- icon action punya aria-label;
- focus visible;
- error tidak hanya warna;
- modal close accessible;
- searchable component keyboard-friendly;
- touch target mobile mengikuti `14`.

## 27. Responsive Contract

### >=1200px
Desktop Sneat penuh; filter padat boleh 2 baris.

### 768–1199px
Sidebar mobile/offcanvas; header/action/filter tidak dipaksa terlalu padat.

### <768px
Mobile hierarchy berlaku.

### Role operasional portrait
`14` menjadi kontrak final, terutama no-horizontal-table-scroll dan density/touch rules.

## 28. Cordova Awareness

Web UI tidak memasukkan plugin Cordova ke business layer. G3 membuat UI WebView-ready; G4 menangani bridge/native integration.

## 29. Acceptance

UI dinyatakan konsisten bila:

- title sinkron;
- tidak ada layout shift statis akibat JS;
- header/filter/card/modal memakai pola canonical;
- filter banyak desktop tidak dipaksa menjadi satu baris sempit;
- tabel periodik memiliki filter Tahun Ajaran default-active;
- Reset kembali ke Tahun Ajaran aktif;
- pilihan histori bekerja sesuai domain;
- export mengikuti period filter;
- paginator/search entity konsisten;
- no body horizontal overflow;
- mobile operational tables memenuhi `14`;
- loading/empty/error jelas;
- browser console bersih;
- business rule/permission tetap ditentukan server.

## 30. Phase Rule

Aturan di dokumen ini bersifat global. Sub-phase baru wajib mengikutinya, dan ketika ditemukan feature existing yang sedang disentuh tetapi menyimpang, penyimpangan tersebut harus diperbaiki atau dicatat eksplisit sebagai exception.