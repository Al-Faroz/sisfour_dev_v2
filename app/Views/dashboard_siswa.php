<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<h4 class="mb-4">Dashboard Siswa</h4>

<?php $rekap = $widgets['rekap_presensi_bulan_ini'] ?? []; ?>
<div class="row g-4 mb-4">
  <div class="col-6 col-md-3">
    <div class="card"><div class="card-body text-center">
      <span class="text-muted small d-block">Hadir</span>
      <h3 class="text-success mb-0"><?= (int) ($rekap['hadir'] ?? 0) ?></h3>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card"><div class="card-body text-center">
      <span class="text-muted small d-block">Sakit</span>
      <h3 class="text-warning mb-0"><?= (int) ($rekap['sakit'] ?? 0) ?></h3>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card"><div class="card-body text-center">
      <span class="text-muted small d-block">Izin</span>
      <h3 class="text-info mb-0"><?= (int) ($rekap['izin'] ?? 0) ?></h3>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card"><div class="card-body text-center">
      <span class="text-muted small d-block">Alpha</span>
      <h3 class="text-danger mb-0"><?= (int) ($rekap['alpha'] ?? 0) ?></h3>
    </div></div>
  </div>
</div>

<div class="row g-4">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0">Riwayat Prestasi</h5></div>
      <ul class="list-group list-group-flush">
        <?php if (empty($widgets['riwayat_prestasi'])): ?>
          <li class="list-group-item text-muted text-center py-4">Belum ada prestasi tercatat.</li>
        <?php else: foreach ($widgets['riwayat_prestasi'] as $p): ?>
          <li class="list-group-item">
            <strong><?= esc($p['nama_prestasi']) ?></strong>
            <div class="small text-muted"><?= esc($p['tingkat'] ?? '-') ?> · <?= esc($p['tanggal']) ?></div>
          </li>
        <?php endforeach; endif; ?>
      </ul>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0">Riwayat Catatan Kasus</h5></div>
      <ul class="list-group list-group-flush">
        <?php if (empty($widgets['riwayat_pelanggaran'])): ?>
          <li class="list-group-item text-muted text-center py-4">Tidak ada catatan.</li>
        <?php else: foreach ($widgets['riwayat_pelanggaran'] as $p): ?>
          <li class="list-group-item">
            <strong><?= esc($p['nama_pelanggaran']) ?></strong>
            <span class="badge bg-label-<?= $p['kategori'] === 'Berat' ? 'danger' : ($p['kategori'] === 'Sedang' ? 'warning' : 'secondary') ?>"><?= esc($p['kategori']) ?></span>
            <div class="small text-muted"><?= esc($p['tanggal']) ?></div>
          </li>
        <?php endforeach; endif; ?>
      </ul>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
