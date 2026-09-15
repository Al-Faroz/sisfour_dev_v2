<?php
$settings = $systemSettings ?? [];
$namaSekolah = trim((string) ($settings['nama_sekolah'] ?? 'MTsN 4 Jombang'));
$iconSekolah = trim((string) ($settings['icon_sekolah'] ?? ''));

if ($namaSekolah === '') {
    $namaSekolah = 'MTsN 4 Jombang';
}

$iconUrl = sisfour_asset_url('assets/img/favicon/favicon.ico');
$iconType = 'image/x-icon';
$hasUploadedIcon = false;

if ($iconSekolah !== '') {
    $iconRelative = ltrim($iconSekolah, '/\\');
    $iconPath = FCPATH . $iconRelative;

    if (is_file($iconPath)) {
        $iconVersion = @filemtime($iconPath);
        $iconUrl = base_url($iconRelative)
            . ($iconVersion ? '?v=' . $iconVersion : '');
        $iconType = 'image/png';
        $hasUploadedIcon = true;
    }
}
?>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
<title><?= isset($pageTitle) ? esc($pageTitle) . ' | ' : '' ?>SisisFour - <?= esc($namaSekolah) ?></title>
<meta name="description" content="SisisFour - Sistem Informasi Manajemen Madrasah <?= esc($namaSekolah) ?>" />

<!--
  CSRF untuk seluruh halaman yang memakai layout utama.
  csrf-fetch.js akan memasukkan hash ini ke header X-CSRF-TOKEN pada
  request POST/PUT/PATCH/DELETE same-origin.
-->
<meta name="csrf-token-name" content="<?= esc(csrf_token()) ?>" />
<meta name="csrf-token" content="<?= esc(csrf_hash()) ?>" />
<meta name="csrf-header-name" content="X-CSRF-TOKEN" />

<link rel="icon" type="<?= esc($iconType, 'attr') ?>" href="<?= esc($iconUrl, 'attr') ?>" />
<?php if ($hasUploadedIcon): ?>
<link rel="apple-touch-icon" href="<?= esc($iconUrl, 'attr') ?>" />
<?php endif; ?>

<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />

<link rel="stylesheet" href="<?= sisfour_asset_url('assets/vendor/fonts/iconify-icons.css') ?>" />

<link rel="stylesheet" href="<?= sisfour_asset_url('assets/vendor/css/core.css') ?>" />
<link rel="stylesheet" href="<?= sisfour_asset_url('assets/css/demo.css') ?>" />
<link rel="stylesheet" href="<?= sisfour_asset_url('assets/css/searchable-select.css') ?>" />
<link rel="stylesheet" href="<?= sisfour_asset_url('assets/css/sisfour-ui.css') ?>" />

<link rel="stylesheet" href="<?= sisfour_asset_url('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') ?>" />
<link rel="stylesheet" href="<?= sisfour_asset_url('assets/vendor/libs/apex-charts/apex-charts.css') ?>" />

<?php if (isset($extraCss)): foreach ((array) $extraCss as $css): ?>
<link rel="stylesheet" href="<?= sisfour_asset_url((string) $css) ?>" />
<?php endforeach; endif; ?>

<!-- Global modal safety loaded last so page-specific CSS cannot disable modal scrolling. -->
<link rel="stylesheet" href="<?= sisfour_asset_url('assets/css/sisfour-modal.css') ?>" />

<script src="<?= sisfour_asset_url('assets/vendor/js/helpers.js') ?>"></script>
<script src="<?= sisfour_asset_url('assets/js/config.js') ?>"></script>
