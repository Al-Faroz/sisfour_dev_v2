<?php
$settings = $systemSettings ?? [];

$namaSekolah = trim(
    (string) (
        $settings['nama_sekolah']
        ?? 'MTsN 4 Jombang'
    )
);

if ($namaSekolah === '') {
    $namaSekolah = 'MTsN 4 Jombang';
}
?>
<footer class="content-footer footer bg-footer-theme">
  <div class="container-xxl">
    <div
      class="footer-container d-flex align-items-center justify-content-between py-3 flex-md-row flex-column gap-2"
    >
      <div class="text-body text-center text-md-start">
        © <?= date('Y') ?> SisisFour · <?= esc($namaSekolah) ?>
      </div>

      <div class="text-body-secondary text-center text-md-end">
        By : <strong>LemahTeles</strong>
      </div>
    </div>
  </div>
</footer>
