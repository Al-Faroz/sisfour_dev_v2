<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
<title><?= isset($pageTitle) ? esc($pageTitle) . ' | ' : '' ?>SisisFour - MTsN 4 Jombang</title>
<meta name="description" content="SisisFour - Sistem Informasi Manajemen Madrasah MTsN 4 Jombang" />

<!--
  CSRF untuk seluruh halaman yang memakai layout utama.
  csrf-fetch.js akan memasukkan hash ini ke header X-CSRF-TOKEN pada
  request POST/PUT/PATCH/DELETE same-origin.
-->
<meta name="csrf-token-name" content="<?= esc(csrf_token()) ?>" />
<meta name="csrf-token" content="<?= esc(csrf_hash()) ?>" />
<meta name="csrf-header-name" content="X-CSRF-TOKEN" />

<link rel="icon" type="image/x-icon" href="<?= base_url('assets/img/favicon/favicon.ico') ?>" />

<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />

<link rel="stylesheet" href="<?= base_url('assets/vendor/fonts/iconify-icons.css') ?>" />

<link rel="stylesheet" href="<?= base_url('assets/vendor/css/core.css') ?>" />
<link rel="stylesheet" href="<?= base_url('assets/css/demo.css') ?>" />

<link rel="stylesheet" href="<?= base_url('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') ?>" />
<link rel="stylesheet" href="<?= base_url('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css') ?>" />
<link rel="stylesheet" href="<?= base_url('assets/vendor/libs/select2/select2.css') ?>" />
<link rel="stylesheet" href="<?= base_url('assets/vendor/libs/sweetalert2/sweetalert2.css') ?>" />
<link rel="stylesheet" href="<?= base_url('assets/vendor/libs/apex-charts/apex-charts.css') ?>" />

<?php if (isset($extraCss)): foreach ((array) $extraCss as $css): ?>
<link rel="stylesheet" href="<?= base_url($css) ?>" />
<?php endforeach; endif; ?>

<script src="<?= base_url('assets/vendor/js/helpers.js') ?>"></script>
<script src="<?= base_url('assets/js/config.js') ?>"></script>
