<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php $tahun = $widgets['tahun_aktif'] ?? []; ?>

<div class="sisfour-dashboard">
  <div class="sf-dashboard-header">
    <div>
      <h4>Dashboard BK</h4>
      <p class="text-muted">Prioritas kasus, EWS Presensi, pelanggaran, dan prestasi.</p>
    </div>
    <span class="badge bg-label-primary sf-year-badge">
      <?= esc(($tahun['nama_tahun'] ?? 'Tahun belum aktif') . (!empty($tahun['semester']) ? ' · ' . $tahun['semester'] : '')) ?>
    </span>
  </div>

  <div class="sf-stat-grid">
    <?php foreach ([
        ['Kasus Bulan Ini', $widgets['kasus_bulan_ini'] ?? 0, 'primary'],
        ['Pelanggaran Berat', $widgets['pelanggaran_berat_bulan_ini'] ?? 0, 'danger'],
        ['EWS Alpha 14 Hari', $widgets['ews_count'] ?? 0, 'danger'],
        ['Prestasi Bulan Ini', $widgets['prestasi_bulan_ini'] ?? 0, 'success'],
    ] as $item): ?>
      <div class="sf-stat-card">
        <span class="sf-stat-card__label"><?= esc($item[0]) ?></span>
        <span class="sf-stat-card__value text-<?= esc($item[2]) ?>"><?= (int) $item[1] ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-lg-5">
      <div class="card h-100 sf-section-card mb-0">
        <div class="card-header"><h5 class="mb-0">EWS Alpha Teratas</h5></div>
        <div class="table-responsive">
          <table class="table table-sm sf-compact-table mb-0">
            <thead><tr><th>Nama</th><th>Alpha</th></tr></thead>
            <tbody>
              <?php if (empty($widgets['ews_top'])): ?>
                <tr><td colspan="2" class="text-center text-muted py-4">Tidak ada siswa EWS.</td></tr>
              <?php else: ?>
                <?php foreach ($widgets['ews_top'] as $row): ?>
                  <tr><td><?= esc($row['nama']) ?></td><td><span class="badge bg-label-danger"><?= (int) $row['total_alpha'] ?></span></td></tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card h-100 sf-section-card mb-0">
        <div class="card-header"><h5 class="mb-0">Top 20 Poin Pelanggaran</h5></div>
        <div class="table-responsive">
          <table class="table table-sm sf-compact-table mb-0">
            <thead><tr><th>#</th><th>Nama</th><th>Poin</th></tr></thead>
            <tbody>
              <?php if (empty($widgets['top20_pelanggaran'])): ?>
                <tr><td colspan="3" class="text-center text-muted py-4">Belum ada data.</td></tr>
              <?php else: ?>
                <?php foreach ($widgets['top20_pelanggaran'] as $i => $row): ?>
                  <tr><td><?= $i + 1 ?></td><td><?= esc($row['nama']) ?></td><td><?= (int) $row['total_poin'] ?></td></tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card h-100 sf-section-card mb-0">
        <div class="card-header"><h5 class="mb-0">Kasus Terbaru</h5></div>
        <ul class="list-group list-group-flush sf-compact-list">
          <?php if (empty($widgets['kasus_terbaru'])): ?>
            <li class="list-group-item text-muted text-center py-4">Belum ada kasus.</li>
          <?php else: ?>
            <?php foreach ($widgets['kasus_terbaru'] as $row): ?>
              <?php $kategoriColor = ($row['kategori'] ?? '') === 'Berat' ? 'danger' : (($row['kategori'] ?? '') === 'Sedang' ? 'warning' : 'secondary'); ?>
              <li class="list-group-item">
                <strong><?= esc($row['nama']) ?></strong> — <?= esc($row['nama_pelanggaran']) ?>
                <div>
                  <span class="badge bg-label-<?= esc($kategoriColor) ?>"><?= esc($row['kategori']) ?></span>
                  <small class="text-muted"><?= esc($row['tanggal']) ?></small>
                </div>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card h-100 sf-section-card mb-0">
        <div class="card-header"><h5 class="mb-0">Prestasi Terbaru</h5></div>
        <ul class="list-group list-group-flush sf-compact-list">
          <?php if (empty($widgets['prestasi_terbaru'])): ?>
            <li class="list-group-item text-muted text-center py-4">Belum ada prestasi.</li>
          <?php else: ?>
            <?php foreach ($widgets['prestasi_terbaru'] as $row): ?>
              <li class="list-group-item">
                <strong><?= esc($row['nama']) ?></strong>
                <div><?= esc($row['nama_prestasi']) ?></div>
                <small class="text-muted"><?= esc($row['tanggal']) ?> · <?= esc($row['tingkat'] ?? '-') ?></small>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
