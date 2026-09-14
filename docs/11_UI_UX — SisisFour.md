# UI/UX Standard — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 14 September 2026  
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

Layout:

```text
layout-wrapper
→ layout-container
  → sidebar
  → layout-page
    → navbar
    → content-wrapper
      → container-xxl flex-grow-1 container-p-y
      → footer
```

Viewport memakai `viewport-fit=cover`.

## 4. Title Contract

Controller mengirim satu title canonical.

```text
title/pageTitle normalized
→ browser <title>
→ navbar context
→ page heading
```

Tidak boleh ada navbar bertuliskan halaman lain.

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

## 8. Typography

```text
Font          Public Sans
Body desktop  15px
Page title    24px desktop
Card title    18px desktop
Metadata      13px
```

Mobile scale mengikuti `14`.

Nama manusia adalah identitas visual utama. NISN/NIP/NIK adalah identifier sekunder untuk search/verifikasi/disambiguasi.

## 9. Filter

Canonical:

```html
<div class="card sisfour-filter-card mb-4">
  <div class="card-body">
    <div class="row g-3 align-items-end">...</div>
  </div>
</div>
```

- label selalu ada;
- entity besar memakai SearchableSelect;
- enum kecil boleh native select;
- apply filter mengembalikan page ke awal;
- mobile filter dapat collapse/offcanvas sesuai `14`.

## 10. Forms

- label terhubung ke control;
- validation dekat field;
- textarea memakai rows, bukan tinggi fixed;
- mutation punya busy guard;
- data identifier panjang diperlakukan string;
- mobile complex form dapat fullscreen modal/sticky action sesuai `14`.

## 11. SearchableSelect

Gunakan komponen internal, bukan input pencarian ad-hoc.

Search entity manusia mendukung:

```text
nama
+ NISN/NIP/NIK bila relevan
```

Hasil menonjolkan nama, identifier menjadi metadata.

## 12. Button Hierarchy

```text
Primary   Tambah/Simpan/Proses/Muat
Secondary Reset/Batal/History
Export    outline-success
Danger    destructive
```

Satu konteks idealnya memiliki satu primary action.

Row action desktop dapat icon/button compact. Mobile mengikuti `14`: action utama + menu sekunder bila aksi banyak.

## 13. Card

List/table:

```text
card
→ card-header
→ table/list
→ card-footer pager/action bila perlu
```

Table di card selalu `mb-0`.

Jangan menambah card di dalam card tanpa kebutuhan hierarchy.

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

### Mobile operational roles

Untuk Pimpinan/BK/Guru/Wali/Siswa, **aturan `14` berlaku**:

```text
NO horizontal table scroll
adaptive columns
metadata merge
Detail untuk data sekunder
```

Admin/Operator matrix boleh exception terdokumentasi.

## 15. Pagination

Gunakan satu komponen `SisfourPagination`.

Admin desktop umum:

```text
25 / 50 / 100
```

Role operasional mobile dapat default 10–15 sesuai `14`.

Tidak boleh menyisakan manual Prev/Next lama bersamaan dengan paginator baru.

## 16. Modal

Mutation form:

```text
header
body
footer: Batal + primary action
```

Gunakan `modal-dialog-scrollable` untuk content panjang.

Mobile complex form/detail menggunakan `modal-fullscreen-sm-down` bila sesuai.

## 17. Confirmation & Feedback

Business confirmation memakai SweetAlert2 atau komponen project.

Dilarang untuk final UI baru:

```javascript
alert()
confirm()
prompt()
```

Inline alert tetap digunakan untuk state halaman yang perlu persistent.

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
restore pada failure
```

## 19. Dashboard

Dashboard role mengikuti experience, bukan meniru Admin.

General:

```text
KPI penting
quick action bila perlu
3–5 item ringkas
Lihat Semua untuk detail
```

Mobile compact rule ada di `14`.

## 20. Tabs / Secondary Navigation

Tab utama ditulis di View. Mobile boleh scroll horizontal untuk **tabs/navigation**, karena ini berbeda dari tabel data.

Tab penting tidak boleh hilang pada mobile.

## 21. Sidebar

Data-driven dari menu/role/context.

- satu active item paling spesifik;
- parent open bila child active;
- parent kosong tidak tampil;
- mobile menggunakan offcanvas/overlay Sneat;
- menu bukan security boundary.

## 22. Branding

`nama_sekolah`, `logo_sekolah`, `icon_sekolah` berasal dari setting sistem.

Favicon:

```text
setting path
→ physical file check
→ cache-busted URL
→ fallback bila tidak ada
```

Login dan authenticated shell memakai source branding yang sama.

## 23. Login

Show/hide password memakai semantic button dan tidak bergantung pada dashboard script.

Wajib update:

```text
input type
icon
aria-label
aria-pressed
```

## 24. CSS Architecture

Reusable pattern masuk:

```text
assets/css/sisfour-ui.css
```

CSS module hanya untuk layout yang benar-benar unik.

Dilarang membuat patch chain seperti `fix.css`, `fix2.css`, `final-fix.css`.

## 25. JavaScript Architecture

Feature JS dibatasi oleh root element.

JS boleh:

```text
fetch
render data dinamis
bind modal
pagination
busy/loading
validation UX
event handler
```

JS tidak membuat page header/filter/tab/action statis setelah load.

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

Desktop Sneat penuh.

### 768–1199px

Sidebar mobile/offcanvas; header/action tidak dipaksa terlalu padat.

### <768px

Mobile hierarchy berlaku.

### Role operasional portrait

`14` menjadi kontrak final, terutama no-horizontal-table-scroll dan density/touch rules.

## 28. Cordova Awareness

Web UI tidak memasukkan plugin Cordova ke business layer.

G3 membuat UI WebView-ready. G4 baru menangani Cordova bridge, Android Back, permission, download/share, safe-area, dan packaging.

## 29. Acceptance

UI dinyatakan konsisten bila:

- title sinkron;
- tidak ada layout shift statis akibat JS;
- header/filter/card/modal memakai pola canonical;
- paginator konsisten;
- search entity konsisten;
- no body overflow;
- mobile operational tables memenuhi `14` setelah G3;
- loading/empty/error jelas;
- browser console bersih;
- business rule/permission tidak berubah karena refactor visual.

## 30. Phase Rule

Pada G2, dokumen ini digunakan untuk **stabilization/regression**, bukan alasan melakukan redesign mobile besar.

Implementasi mobile menyeluruh dimulai pada G3 setelah G2 closed.
