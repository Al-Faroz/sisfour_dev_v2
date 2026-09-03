<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<h4 class="mb-1">Dashboard Pimpinan</h4>
<p class="text-muted mb-4">Seluruh widget di bawah ini bersifat readonly (khusus supervisi).</p>

<div class="row g-4 mb-4">
  <div class="col-md-4">
    <div class="card">
      <div class="card-body">
        <span class="text-muted small">Kelas Belum Presensi Hari Ini</span>
        <h3 class="mb-0 text-warning"><?= (int) ($widgets['kelas_belum_presensi'] ?? 0) ?></h3>
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
        <span class="text-muted small">Kasus BK Bulan Ini</span>
        <h3 class="mb-0"><?= (int) ($widgets['kasus_bk_bulan_ini'] ?? 0) ?></h3>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0">Tren Presensi 7 Hari Terakhir</h5></div>
      <div class="card-body">
        <table class="table table-sm">
          <thead><tr><th>Tanggal</th><th>% Hadir</th></tr></thead>
          <tbody>
            <?php foreach (($widgets['tren_presensi'] ?? []) as $t): ?>
              <tr><td><?= esc($t['tanggal']) ?></td><td><?= esc($t['persen_hadir']) ?>%</td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-body d-flex flex-column justify-content-center">
        <span class="text-muted small">Guru Belum Input Jurnal (hari ini)</span>
        <h3 class="mb-0"><?= (int) ($widgets['guru_belum_jurnal'] ?? 0) ?> guru</h3>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h5 class="mb-0">Top 20 Poin Pelanggaran</h5></div>
  <div class="table-responsive text-nowrap">
    <table class="table">
      <thead><tr><th>#</th><th>Nama Siswa</th><th>Total Poin</th></tr></thead>
      <tbody>
        <?php if (empty($widgets['top20_pelanggaran'])): ?>
          <tr><td colspan="3" class="text-center text-muted py-4">Belum ada data.</td></tr>
        <?php else: foreach ($widgets['top20_pelanggaran'] as $i => $row): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= esc($row['nama']) ?></td>
            <td><span class="badge bg-label-danger"><?= (int) $row['total_poin'] ?></span></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>
