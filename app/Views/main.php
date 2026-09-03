<!doctype html>
<html lang="id" class="layout-menu-fixed" data-assets-path="<?= base_url('assets/') ?>" data-template="vertical-menu-template-free">
<head>
  <?= $this->include('_header') ?>
</head>
<body>
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
