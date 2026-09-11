<script src="<?= sisfour_asset_url('assets/vendor/libs/jquery/jquery.js') ?>"></script>
<script src="<?= sisfour_asset_url('assets/vendor/libs/popper/popper.js') ?>"></script>
<script src="<?= sisfour_asset_url('assets/vendor/js/bootstrap.js') ?>"></script>
<script src="<?= sisfour_asset_url('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') ?>"></script>
<script src="<?= sisfour_asset_url('assets/vendor/js/menu.js') ?>"></script>

<!--
  DataTables dan Select2 tidak lagi dimuat global.
  Business UI SisisFour menggunakan Vanilla JS + Fetch API dan komponen
  internal. Library vendor tetap boleh dipakai kembali secara page-specific
  hanya bila benar-benar dibutuhkan modul legacy.
-->
<script src="<?= sisfour_asset_url('assets/vendor/libs/sweetalert2/sweetalert2.all.min.js') ?>"></script>
<script src="<?= sisfour_asset_url('assets/vendor/libs/apex-charts/apexcharts.js') ?>"></script>

<!--
  Harus dimuat sebelum JS modul mana pun.
  Wrapper ini menyuntikkan token CSRF ke seluruh Fetch mutasi same-origin.
-->
<script src="<?= sisfour_asset_url('assets/js/csrf-fetch.js') ?>"></script>

<!-- Komponen UI internal SisisFour: Vanilla JS, tanpa jQuery/Select2/DataTables. -->
<script src="<?= sisfour_asset_url('assets/js/components/searchable-select.js') ?>"></script>
<script src="<?= sisfour_asset_url('assets/js/components/pagination.js') ?>"></script>

<script src="<?= sisfour_asset_url('assets/js/main.js') ?>"></script>

<?php if (isset($extraJs)): foreach ((array) $extraJs as $js): ?>
<script src="<?= sisfour_asset_url((string) $js) ?>"></script>
<?php endforeach; endif; ?>
