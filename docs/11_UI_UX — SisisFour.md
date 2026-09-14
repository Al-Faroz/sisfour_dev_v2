# UI/UX Standard — SisisFour

**Status:** Canonical / Fresh SSOT  
**Tanggal Acuan:** 14 September 2026  
**Baseline Branch:** `fix/g2-master-data-20260913`  
**Stack UI:** Sneat + Bootstrap 5 + Vanilla JavaScript

> Dokumen ini adalah kontrak tata letak, ukuran, pola visual, responsive behavior, accessibility, dan pola coding UI SisisFour. Semua halaman Web yang memakai layout utama wajib mengikuti dokumen ini kecuali halaman cetak/PDF/public verification yang memiliki kebutuhan khusus.

## 1. Tujuan

UI SisisFour harus:

- konsisten antar modul dan role;
- mudah dipindai oleh Admin/Operator/Guru;
- tidak bergeser setelah JavaScript selesai dimuat;
- responsif pada desktop, laptop, tablet, mobile/WebView;
- tidak menggantungkan business UI pada jQuery/DataTables/Select2;
- accessible secara dasar: label, keyboard, focus, aria, state;
- memisahkan struktur HTML, behavior JavaScript, dan styling CSS;
- menggunakan komponen bersama sebelum membuat pola baru per halaman.

## 2. Sumber Kebenaran UI

```text
Layout shell      -> app/Views/main.php + partial _header/_navbar/_sidebar/_footer
UI foundation     -> assets/css/sisfour-ui.css
Searchable select -> assets/js/components/searchable-select.js
Pagination        -> assets/js/components/pagination.js
Business view     -> app/Views/<module>/*.php
Business behavior -> assets/js/<module>/*.js
Theme/vendor      -> Sneat + Bootstrap 5
```

`assets/css/custom.css` bukan tempat menambah pola UI baru. Pola reusable masuk ke `sisfour-ui.css`; CSS page-specific hanya bila benar-benar tidak reusable.

## 3. Prinsip Markup

### 3.1 Struktur visual harus berasal dari View

Wajib:

- page header;
- action bar;
- tombol Export/Import/Template;
- filter panel;
- tabs/navigation;
- card/table shell;
- modal shell;
- empty-state placeholder awal.

semuanya ditulis di View/PHP.

JavaScript hanya mengatur:

- fetch/reload;
- render row dinamis;
- pagination state;
- modal data binding;
- busy/loading state;
- validasi UX;
- event handler.

**Dilarang** membuat layout utama melalui `createElement()` hanya untuk menambah tombol/filter/tab yang seharusnya sudah diketahui saat render server.

### 3.2 Tidak boleh ada layout shift yang disengaja

Setelah `DOMContentLoaded`, struktur utama halaman tidak boleh tiba-tiba:

- menambah tombol di header;
- membungkus ulang tombol;
- menambahkan filter card;
- menambahkan tab utama;
- memindahkan card/table.

Komponen progressive enhancement seperti searchable-select dan paginator boleh meningkatkan control yang sudah memiliki anchor/markup yang jelas.

## 4. Grid dan Container

Layout utama tetap:

```html
<div class="container-xxl flex-grow-1 container-p-y">
```

Jangan menambah fixed width page wrapper pada halaman Admin.

Bootstrap breakpoints canonical:

```text
xs  < 576 px
sm >= 576 px
md >= 768 px
lg >= 992 px
xl >= 1200 px
xxl >= 1400 px
```

Gunakan grid Bootstrap sebelum menulis CSS grid baru.

## 5. Spacing Scale

Gunakan spacing Bootstrap sebagai vocabulary tunggal:

```text
1 = 0.25rem = 4 px
2 = 0.50rem = 8 px
3 = 1.00rem = 16 px
4 = 1.50rem = 24 px
5 = 3.00rem = 48 px
```

Kontrak halaman:

```text
Page header -> konten berikutnya : mb-4 (24 px)
Antar card/section utama          : mb-4 (24 px)
Gap field form/filter             : g-3 (16 px)
Gap tombol                        : gap-2 (8 px)
Judul -> deskripsi                : mb-1 (4 px)
Card header -> body               : mengikuti Bootstrap/Sneat
Modal field vertical              : mb-3 / row g-3
```

Jangan memakai margin random `5px`, `10px`, `18px`, dll bila padanan Bootstrap tersedia.

## 6. Typography

Gunakan Public Sans dari theme.

### 6.1 Page title

```html
<h4 class="fw-bold mb-1">Judul Halaman</h4>
<p class="text-muted mb-0">Deskripsi satu atau dua kalimat.</p>
```

Aturan:

- satu `h4` utama per halaman;
- jangan hardcode `font-size` untuk page title;
- deskripsi ideal maksimal sekitar 80 karakter visual / `80ch`;
- card title memakai `h5 mb-0`;
- subsection form/modal memakai `h6`;
- metadata memakai `small text-muted`.

### 6.2 Identifier

NIK, NIP, NISN, kode, nomor kartu, dan identifier teknis memakai `font-monospace` bila membantu pemindaian.

## 7. Page Header Canonical

Markup:

```html
<div class="sisfour-page-header">
  <div class="sisfour-page-header__copy">
    <h4 class="fw-bold mb-1">...</h4>
    <p class="text-muted mb-0">...</p>
  </div>
  <div class="sisfour-page-actions">
    ... tombol ...
  </div>
</div>
```

Desktop:

- copy di kiri;
- actions di kanan;
- vertical center;
- gap 16 px;
- action boleh wrap.

Mobile:

- copy di atas;
- action di bawah;
- tombol utama full-width bila action sedikit;
- kelompok >2 tombol boleh tetap wrap dengan lebar natural, tetapi tidak overflow viewport.

## 8. Hierarki Tombol

### 8.1 Primary

Satu aksi utama per konteks:

```text
Tambah
Simpan
Proses
Muat
Generate
```

Gunakan `btn btn-primary` kecuali semantics kuat (mis. Simpan Presensi dapat success).

### 8.2 Secondary

```text
Template      -> outline-primary
Import        -> outline-primary
Export        -> outline-success
Recycle/Histori -> outline-secondary
Reset/Batal   -> outline-secondary
Danger delete -> outline-danger
```

### 8.3 Ukuran

```text
Button normal : min-height sekitar 38 px (native Bootstrap/Sneat)
Button small  : sekitar 31–32 px (`btn-sm`)
Icon          : 16–18 px visual
Gap icon/text : `me-1`
```

Jangan override tinggi tombol per halaman kecuali komponen khusus.

### 8.4 Row action

CRUD sederhana menggunakan tombol icon-only `btn-sm` dengan:

- `title`;
- `aria-label`;
- ukuran visual konsisten;
- urutan Detail -> Edit -> Delete/Nonaktifkan.

Workflow bisnis yang membutuhkan kejelasan boleh memakai teks: `Proses`, `Aktifkan`, `Cetak`, `Restore`.

## 9. Alert dan Help Text

Alert hanya untuk informasi yang memang perlu menonjol.

Gunakan:

- `alert-danger`: blocker/error;
- `alert-warning`: konsekuensi penting;
- `alert-info`: aturan penting yang jarang;
- `form-text`: petunjuk field;
- `small text-muted`: metadata.

Jangan memakai alert besar permanen untuk penjelasan biasa yang dapat menjadi description/help text.

## 10. Filter Panel

Canonical:

```html
<div class="card sisfour-filter-card mb-4">
  <div class="card-body">
    <form class="row g-3 align-items-end">
      ... fields ...
      <div class="col-12 sisfour-filter-actions">
        <button class="btn btn-outline-secondary">Reset</button>
        <button class="btn btn-primary">Terapkan</button>
      </div>
    </form>
  </div>
</div>
```

Aturan:

- label selalu ada;
- filter tidak boleh mengandalkan placeholder sebagai label;
- tombol berada pada baseline bawah field;
- urutan visual: Reset lalu Terapkan pada kelompok kanan, atau Terapkan lalu Reset bila halaman legacy dipertahankan; satu pilihan harus dipakai konsisten pada seluruh Admin setelah migrasi;
- filter entity besar memakai SearchableSelect;
- filter enum kecil <=7 option tetap native select dengan `data-searchable-off="1"`;
- submit filter mengembalikan pagination ke offset 0.

## 11. Form Control

Target control normal mengikuti Bootstrap/Sneat dengan tinggi efektif sekitar `38 px`.

Wajib:

- `label.form-label` terhubung `for` ke control visual;
- required terlihat melalui `required` dan bila perlu tanda `*`;
- validation message dekat field;
- textarea hanya set `rows`, bukan fixed pixel height;
- readonly menggunakan `readonly`, bukan disabled jika value perlu disubmit;
- identifier numeric disimpan/ditampilkan sebagai string, bukan number JS/Excel bila dapat kehilangan digit.

## 12. Searchable Select

Gunakan komponen internal `SisfourSearchableSelect`.

Entity yang biasanya searchable:

```text
Siswa
Guru
Pegawai
Kelas
Mapel
Pelanggaran
Wali
```

Aturan:

- jangan membuat input search tambahan sendiri di atas `<select>`;
- label harus secara accessibility mengacu ke input visual hasil enhancement;
- dropdown harus tetap berada di atas modal/card (`z-index` benar);
- remote search untuk dataset besar;
- local search untuk option kecil-menengah yang sudah tersedia;
- saat reset, visible input harus sinkron dengan native select.

## 13. Card

### 13.1 Table card

```html
<div class="card sisfour-table-card">
  <div class="card-header ...">
    <h5 class="mb-0">Daftar ...</h5>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">...</table>
  </div>
  <div class="card-footer ...">pagination</div>
</div>
```

Tidak boleh ada margin bawah default dari `<table>` yang menambah strip kosong dalam card.

### 13.2 Form card

Card form memakai:

- `card-header` untuk section title bila section berdiri sendiri;
- `card-body` untuk field;
- `card-footer` untuk action bila action terpisah dari field.

### 13.3 Ukuran padding

Gunakan native Sneat/Bootstrap. Untuk mobile, foundation CSS boleh mengurangi padding card ke sekitar 16 px bila theme desktop terlalu lebar.

## 14. Table

Canonical:

```html
<table class="table table-hover align-middle mb-0">
```

Aturan:

- table selalu di `.table-responsive` untuk tabel data;
- numeric count rata kanan atau center sesuai konteks;
- status menggunakan badge;
- kolom identifier jangan wrap bila pendek;
- kolom deskripsi boleh wrap;
- kolom `Aksi` berada paling kanan;
- action group tidak pecah antarbaris;
- empty state memakai satu row dengan `text-center text-muted py-4`;
- loading state memakai spinner kecil + teks;
- error state tidak hanya `console.error`; harus terlihat user.

Target visual row reguler sekitar 44–52 px, tanpa memaksa `height` statis.

## 15. Pagination

Semua tabel pageable memakai `SisfourPagination`.

Default:

```text
limit     : 25
pilihan   : 25 / 50 / 100
summary   : Menampilkan X–Y dari N data
navigasi  : sebelumnya + nomor halaman + berikutnya
```

Tidak boleh membuat paginator baru hanya berupa Prev/Next bila komponen canonical dapat dipakai.

`SisfourPagination.mount()` harus menghasilkan satu footer visual; padding/border tidak boleh dobel antara `.card-footer` dan `.sisfour-pager`.

## 16. Modal

Ukuran:

```text
modal-dialog        -> form sederhana
modal-lg            -> detail / form medium
modal-xl            -> bulk/process/table besar
modal-dialog-scrollable -> modal yang dapat melewati tinggi viewport
```

Struktur wajib:

```text
modal-header
modal-body
modal-footer
```

Footer form mutation minimal:

```text
[Batal] [Simpan/Proses]
```

Mutation button harus punya busy state dan tidak dapat double submit.

Modal mobile:

- tidak overflow horizontal;
- table besar tetap di `.table-responsive`;
- action footer tetap terlihat/wajar;
- modal-xl turun natural mengikuti Bootstrap viewport.

## 17. Confirmation dan Notification

Gunakan SweetAlert2 untuk confirmation/feedback interaktif.

Dilarang pada business UI baru:

```javascript
window.alert(...)
window.confirm(...)
```

Delete/destructive confirmation wajib menjelaskan objek dan dampaknya.

## 18. Loading, Empty, Error, Success

Setiap area async harus memiliki empat state:

```text
Loading -> spinner + teks
Empty   -> pesan netral dan tindakan bila ada
Error   -> pesan visible + opsi retry bila relevan
Ready   -> data
```

Jangan meninggalkan tabel kosong tanpa pesan.

## 19. Dashboard

KPI card canonical:

- satu label kecil;
- satu nilai utama;
- satu icon/status opsional;
- tinggi card seimbang dalam row;
- maksimal 4 KPI utama per row desktop;
- `col-6 col-xl-3` untuk KPI ringkas;
- hindari campuran `h3/h4` tanpa hierarki.

Section dashboard memakai heading/card yang sama dengan halaman lain.

## 20. Tabs dan Secondary Navigation

Tab/navigasi utama harus berada di View, bukan ditambahkan lewat JS.

Desktop:

- tab nowrap bila jumlah sedikit;
- active state jelas.

Mobile:

- horizontal scroll diperbolehkan;
- seluruh tab tetap dapat ditemukan;
- jangan menyembunyikan tab penting hanya karena viewport kecil.

## 21. Navbar dan Page Title

Controller mengirim satu title canonical.

Kontrak layout:

```text
$data['title'] -> title halaman
navbar         -> title halaman yang sama
<title>        -> title halaman yang sama
```

Jangan memiliki dua variabel tidak sinkron seperti `title` dan `pageTitle` tanpa normalisasi di layout/controller base.

## 22. Sidebar

Sidebar tetap data-driven dari `menus` + `role_menus`.

UI rule:

- satu item paling spesifik active;
- parent dibuka bila child active;
- label truncation hanya bila perlu;
- icon konsisten Boxicons;
- desktop collapse tidak menghilangkan toggle;
- mobile menggunakan overlay native layout.

## 23. Responsive Contract

### >= 992 px

- page header horizontal;
- filter grid multi-column;
- action bar kanan;
- table full card.

### 768–991 px

- page header boleh horizontal atau stack sesuai jumlah action;
- filter 2 kolom;
- table horizontal scroll bila perlu.

### < 768 px

- page header stack;
- filter 1 kolom bila field panjang;
- tombol action tidak keluar viewport;
- table memakai horizontal scroll;
- pager stack summary dan navigation;
- modal dan tabs tetap usable.

### < 576 px

- card/body padding boleh sekitar 16 px;
- action utama dapat full width;
- profile hero stack;
- info grid menjadi 1 kolom;
- tidak ada fixed width > viewport.

## 24. Accessibility Minimum

Wajib:

- label-control association valid;
- icon-only button punya `title` dan `aria-label`;
- modal close punya `aria-label`;
- status tidak hanya dibedakan warna;
- disabled state benar-benar disabled;
- keyboard dapat menggunakan searchable select, modal, tabs, pagination;
- focus ring tidak dihapus;
- heading hierarchy masuk akal;
- table header menggunakan `<th>`;
- loading/error text dapat dibaca tanpa icon.

## 25. Pola Coding View

View bertanggung jawab pada struktur dan escaping.

```php
<?= esc($value) ?>
```

Jangan memasukkan data user ke HTML tanpa escaping.

ID element hanya untuk behavior yang benar-benar memerlukan selector JS. Styling menggunakan class, bukan ID.

Inline `style="..."` hanya diperbolehkan untuk kebutuhan sangat spesifik seperti lebar kolom sederhana; pola reusable pindah ke CSS class.

## 26. Pola Coding JavaScript

Business JavaScript:

- Vanilla JS;
- Fetch API;
- same-origin credentials bila perlu;
- mutation melalui `csrf-fetch.js`;
- `escapeHtml()` untuk string yang dirender ke template HTML;
- state pagination/filter eksplisit;
- button busy guard;
- error visible ke user;
- tidak membuat layout utama yang seharusnya ada di View;
- tidak menggandakan komponen global.

Urutan modul yang dianjurkan:

```text
resolve root/app
resolve elements
state
helpers
render
load/fetch
mutation handlers
event bindings
initial load
```

## 27. Pola Coding CSS

Reusable CSS memakai prefix:

```text
.sisfour-*
```

Contoh:

```text
.sisfour-page-header
.sisfour-page-actions
.sisfour-filter-card
.sisfour-table-card
.sisfour-table-actions
.sisfour-pager
.sisfour-empty-state
.sisfour-profile-*
```

Hindari selector global agresif seperti `.card { ... }` yang mengubah seluruh theme tanpa scope.

Gunakan CSS variables Bootstrap/Sneat bila tersedia.

## 28. Anti-pattern yang Wajib Dihindari

```text
JS membuat tombol Export setelah render
JS membuat filter card utama setelah render
JS membuat tab/navigation utama setelah render
manual Prev/Next baru ketika paginator canonical tersedia
window.alert/window.confirm untuk flow business
DataTables/Select2 baru untuk kebutuhan yang sudah ditangani komponen internal
fixed pixel width besar pada form/card
button tanpa responsive wrapping
alert permanen untuk sekadar deskripsi
modal mutation tanpa tombol Batal
page title navbar berbeda dengan judul halaman
```

## 29. Template Halaman List Canonical

```php
<div id="moduleApp" data-base-url="<?= esc(base_url()) ?>">
  <div class="sisfour-page-header">
    <div class="sisfour-page-header__copy">
      <h4 class="fw-bold mb-1">Master Contoh</h4>
      <p class="text-muted mb-0">Deskripsi singkat halaman.</p>
    </div>
    <div class="sisfour-page-actions">
      <button class="btn btn-outline-success">Export</button>
      <button class="btn btn-primary">Tambah</button>
    </div>
  </div>

  <div class="card sisfour-filter-card mb-4">
    <div class="card-body">
      <form class="row g-3 align-items-end">
        <!-- field -->
        <div class="col-12 sisfour-filter-actions">
          <button type="button" class="btn btn-outline-secondary">Reset</button>
          <button type="submit" class="btn btn-primary">Terapkan</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card sisfour-table-card">
    <div class="card-header d-flex justify-content-between align-items-center gap-2">
      <h5 class="mb-0">Daftar Data</h5>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>...</thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>
```

## 30. Quality Gate UI/UX

Setiap halaman yang diubah wajib dicek pada:

```text
Desktop >= 1200
Laptop 992–1199
Tablet 768–991
Mobile 360–575
```

Checklist:

- navbar title = page title;
- header/action alignment;
- tidak ada overflow horizontal halaman;
- table boleh scroll di wrapper, bukan seluruh page;
- filter label/control rapi;
- buttons konsisten;
- loading/empty/error state;
- pagination konsisten;
- modal footer dan busy guard;
- tab dapat diakses mobile;
- searchable select keyboard + label;
- tidak ada layout shift akibat JS;
- tidak ada browser console error;
- direct refresh/back tidak merusak state penting.

## 31. Pengecualian

Halaman berikut boleh menggunakan layout khusus:

- PDF/print Kartu Pelajar;
- signage;
- public QR verify bila memang standalone;
- auth/login;
- halaman error/maintenance.

Walau layout khusus, typography, accessibility, responsive safety, dan escaping tetap wajib.
