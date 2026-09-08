<script src="<?= base_url('assets/vendor/libs/jquery/jquery.js') ?>"></script>
<script src="<?= base_url('assets/vendor/libs/popper/popper.js') ?>"></script>
<script src="<?= base_url('assets/vendor/js/bootstrap.js') ?>"></script>
<script src="<?= base_url('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') ?>"></script>
<script src="<?= base_url('assets/vendor/js/menu.js') ?>"></script>

<script src="<?= base_url('assets/vendor/libs/datatables/datatables.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/libs/select2/select2.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/libs/sweetalert2/sweetalert2.all.min.js') ?>"></script>
<script src="<?= base_url('assets/vendor/libs/apex-charts/apexcharts.js') ?>"></script>

<!--
  Harus dimuat sebelum JS modul mana pun.
  Wrapper ini menyuntikkan token CSRF ke seluruh Fetch mutasi same-origin.
-->
<script src="<?= base_url('assets/js/csrf-fetch.js') ?>"></script>

<script src="<?= base_url('assets/js/main.js') ?>"></script>

<?php if (isset($extraJs)): foreach ((array) $extraJs as $js): ?>
<script src="<?= base_url($js) ?>"></script>
<?php endforeach; endif; ?>
