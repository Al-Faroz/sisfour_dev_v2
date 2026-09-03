<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<h4 class="mb-4">Dashboard BK</h4>

<div class="row g-4 mb-4">
  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <span class="text-muted small">Kasus Bulan Ini</span>
        <h3 class="mb-0"><?= (int) ($widgets['kasus_bulan_ini'] ?? 0) ?></h3>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <span class="text-muted small">EWS Radar (≥3 Alpha / 14 hari)</span>
        <h3 class="mb-0 text-danger"><?= (int) ($widgets['ews_radar_count'] ?? 0) ?> siswa</h3>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <span class="text-muted small">Top 20 Poin Pelanggaran</span>
        <h3 class="mb-0"><?= count($widgets['top20_pelanggaran'] ?? []) ?> siswa terdaftar</h3>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0">Top 20 Poin Pelanggaran</h5></div>
      <div class="table-responsive text-nowrap">
        <table class="table table-sm">
          <thead><tr><th>#</th><th>Nama</th><th>Poin</th></tr></thead>
          <tbody>
            <?php foreach (($widgets['top20_pelanggaran'] ?? []) as $i => $row): ?>
              <tr><td><?= $i + 1 ?></td><td><?= esc($row['nama']) ?></td><td><?= (int) $row['total_poin'] ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0">Prestasi Terbaru</h5></div>
      <ul class="list-group list-group-flush">
        <?php if (empty($widgets['prestasi_terbaru'])): ?>
          <li class="list-group-item text-muted text-center py-4">Belum ada data.</li>
        <?php else: foreach ($widgets['prestasi_terbaru'] as $p): ?>
          <li class="list-group-item">
            <strong><?= esc($p['nama_prestasi']) ?></strong>
            <div class="small text-muted"><?= esc($p['tingkat'] ?? '-') ?> · <?= esc($p['tanggal']) ?></div>
          </li>
        <?php endforeach; endif; ?>
      </ul>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
