# Global Standard — CodeIgniter 4 + Sneat Bootstrap 5

**Status:** Canonical Global Reference  
**Baseline vendor:** Sneat Bootstrap HTML Admin Template Free v3.0.0  
**Bootstrap:** 5.3.3  
**Scope:** reusable untuk proyek CodeIgniter 4 + Sneat, tidak khusus SisisFour

> Dokumen ini memisahkan **Vendor Baseline** dan **Application Standard**. Nilai vendor berasal dari Sneat v3 yang dipelajari; pola partial CI4 adalah konvensi aplikasi agar implementasi lintas proyek konsisten.

## 1. Prinsip

```text
Sneat / Bootstrap vendor
        ↓
Global application foundation
        ↓
CI4 layout shell
        ↓
Reusable components
        ↓
Feature View
        ↓
Feature JavaScript
```

Aturan:

1. Jangan edit `core.css`, Bootstrap, atau vendor Sneat langsung.
2. Custom CSS dimuat setelah vendor.
3. Shell dipisah dari feature.
4. View feature hanya berisi konten feature.
5. JavaScript tidak membuat struktur statis yang sudah diketahui server.
6. Desktop dan mobile memakai satu sistem responsive.
7. Gunakan komponen Bootstrap/Sneat native sebelum custom.
8. Hindari inline CSS rutin dan angka spacing acak.
9. Business JavaScript memakai Vanilla JS; jQuery hanya untuk dependency vendor/plugin.

## 2. Vendor Baseline

```text
Sneat Free       3.0.0
Bootstrap        5.3.3
Public Sans
Sidebar desktop  260px
Sidebar collapsed 84px
Navbar           64px
Body font        15px
Desktop menu     xl / 1200px
```

Root shell canonical Sneat v3:

```html
<html lang="id" class="layout-menu-fixed layout-compact">
```

`light-style` bukan bagian canonical baseline v3 ini.

## 3. Breakpoints

```text
xs  < 576
sm >= 576
md >= 768
lg >= 992
xl >= 1200
xxl>= 1400
```

`<1200px` sidebar berperilaku mobile/offcanvas. `>=1200px` sidebar desktop aktif.

## 4. Typography

```text
Font family       Public Sans
Root              16px
Body              15px
Small/helper      13px
Card title        18px
Page title        24px
Major detail      28px bila diperlukan
```

Font weight:

```text
400 normal
500 medium
600 semibold
700 bold
```

CRUD rutin tidak perlu `h1/h2`.

## 5. Spacing

Gunakan vocabulary konsisten:

```text
4px   micro
8px   compact gap
12px  dense grouping
16px  normal
24px  section/card
32px  major gap
48px  landing/major separator
```

Hindari `13px`, `17px`, `23px` tanpa alasan visual khusus.

## 6. Grid & Container

Vendor baseline:

```text
Grid gutter          26px
Desktop content X    26px
Mobile content X     16px
Content Y            24px
xxl max-width      1440px
```

Canonical:

```html
<div class="container-xxl flex-grow-1 container-p-y">
```

`container-fluid` hanya bila feature memang memerlukan kanvas sangat lebar.

## 7. Radius & Card

```text
Card radius      6px
Modal radius     8px
Card padding    24px
Modal padding   24px
```

Gunakan native Sneat. Jangan menambah radius/shadow berbeda-beda per halaman.

## 8. Sidebar / Navbar / Footer

Sidebar:

```text
expanded  260px
collapsed  84px
```

- data menu diselesaikan sebelum View;
- active/open state spesifik;
- permission tidak ditentukan hanya dengan CSS hide.

Navbar:

```text
height 64px
```

Berisi toggle mobile, context/title, user/profile dan optional notification. Filter halaman tidak masuk navbar.

Footer ringan dan tidak fixed kecuali feature membutuhkan.

## 9. Struktur Layout CI4

Recommended:

```text
app/Views/
├── layouts/
│   ├── main.php
│   └── partials/
│       ├── head.php
│       ├── sidebar.php
│       ├── navbar.php
│       ├── footer.php
│       ├── flash.php
│       └── scripts.php
└── components/
```

Project existing boleh memakai nama/path berbeda selama responsibility sama.

## 10. `main.php`

Canonical responsibility:

```php
<!doctype html>
<html lang="id" class="layout-menu-fixed layout-compact">
<head>
    <?= $this->include('layouts/partials/head') ?>
    <?= $this->renderSection('styles') ?>
</head>
<body>
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        <?= $this->include('layouts/partials/sidebar') ?>
        <div class="layout-page">
            <?= $this->include('layouts/partials/navbar') ?>
            <div class="content-wrapper">
                <main class="container-xxl flex-grow-1 container-p-y">
                    <?= $this->include('layouts/partials/flash') ?>
                    <?= $this->renderSection('content') ?>
                </main>
                <?= $this->include('layouts/partials/footer') ?>
                <div class="content-backdrop fade"></div>
            </div>
        </div>
    </div>
    <div class="layout-overlay layout-menu-toggle"></div>
</div>
<?= $this->include('layouts/partials/scripts') ?>
<?= $this->renderSection('scripts') ?>
</body>
</html>
```

`main.php` tidak melakukan query, business authorization, atau memuat modal feature.

## 11. `head.php`

Tanggung jawab:

```text
meta
viewport
browser title
favicon
font
vendor CSS
global app CSS
helpers/config Sneat bila diperlukan
```

Viewport minimum:

```html
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
```

Asset order:

```text
vendor CSS
→ application CSS
→ module CSS
```

## 12. Scripts

Dependency order umum:

```text
jQuery bila vendor membutuhkan
Popper
Bootstrap
Perfect Scrollbar
menu.js
main.js / global app.js
page-specific JS
```

Jangan load semua page JS pada semua route.

## 13. Page Header

Canonical:

```html
<div class="app-page-header">
    <div class="app-page-header__copy">
        <h4 class="fw-bold mb-1">Judul</h4>
        <p class="text-muted mb-0">Deskripsi singkat.</p>
    </div>
    <div class="app-page-actions">...</div>
</div>
```

Desktop horizontal, `<1200px` dapat stack bila action padat.

## 14. Filter

Gunakan card + `row g-3 align-items-end`.

```text
label wajib
field responsive
Reset + Terapkan konsisten
entity besar searchable
```

Jangan memakai placeholder sebagai satu-satunya label.

## 15. Form Control

Baseline:

```text
body/control font 15px
label            13px
control normal  ~40px
button normal   ~38px
```

Gunakan `row g-3` atau `mb-3`. Required dan validation dekat field.

## 16. Button

```text
Primary          btn-primary
Secondary        btn-outline-secondary
Positive         success
Danger           danger
Row action       btn-sm / icon action
```

Icon-only wajib `title` dan `aria-label`.

## 17. Table

Canonical desktop:

```html
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
```

Rules:

- status menggunakan badge;
- action di kanan;
- empty/loading/error visible;
- jangan `text-nowrap` untuk seluruh tabel tanpa alasan;
- horizontal strategy mengikuti project-specific standard.

Project dapat menetapkan aturan mobile yang lebih ketat daripada baseline ini.

## 18. Pagination

Satu aplikasi menggunakan satu paginator reusable.

Default administrasi umum:

```text
25 / 50 / 100
```

Footer berisi summary + navigation tanpa double border/padding.

## 19. Modal

Vendor baseline:

```text
sm       360px
default  560px
lg       800px
```

Mutation form memiliki:

```text
header
body
footer: Batal + primary action
```

Gunakan `modal-dialog-scrollable` untuk content panjang. Project mobile boleh meng-override menjadi fullscreen pada viewport kecil.

## 20. Alerts / Confirmation

Inline alert untuk page/field state. SweetAlert2/toast dapat digunakan untuk feedback/confirmation.

Hindari `alert()`, `confirm()`, `prompt()` sebagai final business UX.

## 21. Loading / Empty / Error

Area async memiliki:

```text
initial
loading
ready
empty
error
```

Mutation memiliki busy guard dan tidak double-submit.

## 22. Responsive

Mobile-first grid:

```html
<div class="col-12 col-md-6 col-xl-4">
```

Jangan memakai fixed width yang lebih lebar dari viewport. Body horizontal overflow adalah bug.

## 23. Accessibility

Minimum:

- input punya label;
- icon-only action punya aria-label;
- focus keyboard tidak dihapus;
- error tidak hanya warna;
- custom select/dropdown keyboard-friendly;
- modal close punya `aria-label`;
- gambar penting punya `alt`.

## 24. Branding & Favicon

Jika branding dapat di-upload, login dan authenticated shell memakai source setting yang sama.

Resolver wajib:

```text
DB path
→ verifikasi physical file
→ URL asset
→ cache-busting revision/filemtime
→ fallback favicon
```

Uploaded favicon idealnya juga menjadi `apple-touch-icon`.

## 25. Login Password Toggle

Toggle login tidak bergantung pada dashboard/sidebar script.

Gunakan semantic button:

```html
<button type="button" aria-controls="password" aria-pressed="false" aria-label="Tampilkan password">
```

Behavior mengubah `type=password/text`, icon dan ARIA.

## 26. JavaScript Pattern

Feature JS dibatasi root element:

```js
(() => {
  'use strict';
  const app = document.getElementById('featureApp');
  if (!app) return;
  // refs → state → helpers → fetch/render → events
})();
```

JS boleh render row/state dinamis, tetapi tidak seharusnya membuat page header, filter utama, tab utama atau tombol Export statis setelah load.

## 27. CSS Architecture

```text
vendor core.css
→ application foundation CSS
→ module CSS bila benar-benar unik
```

Reusable primitive masuk global app CSS. Jangan membuat rangkaian file `fix`, `fix2`, `final-fix`.

## 28. Security View

- escape output;
- CSRF mengikuti policy project;
- jangan query DB dari View;
- jangan menaruh permission business kompleks di View;
- client bukan security boundary.

## 29. QA Viewport

Minimum:

```text
360×800
390×844
768×1024
1024×768
1280×720
1366×768
1440×900
1920×1080
```

Periksa navbar/sidebar, action, filter, table, modal, keyboard, loading/error, dan body overflow.

## 30. Definition of Done Layout

- shell dan partial responsibility jelas;
- vendor tidak dipatch langsung;
- title/browser/navbar sinkron;
- component reusable tersedia;
- mobile/desktop shell normal;
- no body horizontal overflow;
- modal/table responsive sesuai policy project;
- lint PHP/JS PASS;
- browser regression PASS.
