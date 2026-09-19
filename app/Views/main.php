<?php
$roleSlug = strtolower(trim((string) ($authUser['role'] ?? 'guest')));
$roleSlug = preg_replace('/[^a-z0-9_-]/', '', $roleSlug) ?: 'guest';

$bodyClasses = [
    'sisfour-app',
    'sisfour-role-' . $roleSlug,
];

if (in_array($roleSlug, ['pimpinan', 'bk', 'kesehatan', 'ptsp', 'guru', 'siswa'], true)) {
    $bodyClasses[] = 'sisfour-role-operational';
}

if ($roleSlug === 'guru' && (bool) ($authUser['is_wali'] ?? false)) {
    $bodyClasses[] = 'sisfour-context-wali';
}
?>
<!doctype html>
<html lang="id" class="layout-menu-fixed layout-compact" data-assets-path="<?= base_url('assets/') ?>" data-template="vertical-menu-template-free">
<head>
  <?= $this->include('_header') ?>
  <style>
    .swal2-container {
      z-index: 20000 !important;
    }
  </style>
</head>
<body class="<?= esc(implode(' ', $bodyClasses), 'attr') ?>">
  <div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

      <?= $this->include('_sidebar') ?>

      <div class="layout-page">

        <?= $this->include('_navbar') ?>

        <div class="content-wrapper">
          <div class="container-xxl flex-grow-1 container-p-y">
            <?= $this->include('_flash') ?>
            <?= $this->renderSection('content') ?>
          </div>

          <?= $this->include('_footer') ?>
          <div class="content-backdrop fade"></div>
        </div>
      </div>
    </div>

    <div class="layout-overlay layout-menu-toggle"></div>
    <div class="drag-target"></div>
  </div>

  <?= $this->include('_scripts') ?>
  <?= $this->renderSection('scripts') ?>
</body>
</html>