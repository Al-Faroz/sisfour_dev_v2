<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php
$master = $widgets['master'] ?? [];
$presensi = $widgets['presensi_hari_ini'] ?? [];
$jurnal = $widgets['jurnal_hari_ini'] ?? [];
$kartu = $widgets['kartu'] ?? [];
$access = $widgets['access'] ?? [];
$canEws = ! empty($access['ews']);
$canPelanggaran = ! empty($access['pelanggaran']);
$canPrestasi = ! empty($access['prestasi']);
$canKartu = ! empty($access['kartu']);
$tren = $widgets['tren_presensi'] ?? [];
$ewsTop = $widgets['ews_top'] ?? [];
$prestasiTerbaru = $widgets['prestasi_terbaru'] ?? [];
?>

<div class="sisfour-page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
  <div class="sisfour-page-header__copy">
    <h4 class="mb-1">Dashboard Pimpinan</h4>
    <p class="text-muted mb-0">Ringkasan supervisi readonly berdasarkan data operasional yang diizinkan.</p>
  </div>
  <span class="badge bg-label-secondary">Readonly</span>
</div>

<div class="sisfour-mobile-kpi-grid mb-4">
  <?php foreach ([
      ['Kelas Belum Presensi', (int) ($presensi['belum_kelas'] ?? 0), 'warning', 'bx-list-check', 'dari ' . (int) ($presensi['wajib_kelas'] ?? 0) . ' kelas wajib'],
      ['Jadwal Belum Jurnal', (int) ($jurnal['belum'] ?? 0), 'warning', 'bx-book-content', 'dari ' . (int) ($jurnal['wajib'] ?? 0) . ' jadwal'],
      ['EWS 14 Hari', $canEws ? (int) ($widgets['ews_count'] ?? 0) : null, 'danger', 'bx-error-circle', $canEws ? 'siswa' : 'tidak tersedia'],
      ['Catatan Pelanggaran Bulan Ini', $canPelanggaran ? (int) ($widgets['kasus_bulan_ini'] ?? 0) : null, 'primary', 'bx-note', $canPelanggaran ? 'catatan' : 'tidak tersedia'],
  ] as $item): ?>
    <div class="card h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <span class="avatar flex-shrink-0 bg-label-<?= esc($item[2]) ?> rounded">
          <i class="bx <?= esc($item[3]) ?>"></i>
        </span>
        <div class="min-w-0">
          <small class="text-muted d-block"><?= esc($item[0]) ?></small>
          <h4 class="text-<?= esc($item[2]) ?> mb-0"><?= $item[1] === null ? '—' : (int) $item[1] ?></h4>
          <small class="text-muted"><?= esc($item[4]) ?></small>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header sisfour-section-heading d-flex justify-content-between align-items-center gap-2">
        <h5 class="mb-0">Tren Presensi 7 Hari</h5>
        <small class="text-muted">Sesi Awal</small>
      </div>

      <div class="d-md-none list-group list-group-flush">
        <?php if ($tren === []): ?>
          <div class="list-group-item sisfour-mobile-state text-muted">Belum ada data tren presensi.</div>
        <?php else: foreach ($tren as $row): ?>
          <div class="list-group-item d-flex justify-content-between align-items-center gap-3 py-3">
            <span><?= esc((string) ($row['tanggal'] ?? '-')) ?></span>
            <strong><?= esc((string) ($row['persen_hadir'] ?? 0)) ?>%</strong>
          </div>
        <?php endforeach; endif; ?>
      </div>

      <div class="d-none d-md-block table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>Tanggal</th><th class="text-end">% Hadir</th></tr></thead>
          <tbody>
            <?php if ($tren === []): ?>
              <tr><td colspan="2" class="text-center text-muted py-4">Belum ada data tren presensi.</td></tr>
            <?php else: foreach ($tren as $row): ?>
              <tr><td><?= esc((string) ($row['tanggal'] ?? '-')) ?></td><td class="text-end"><strong><?= esc((string) ($row['persen_hadir'] ?? 0)) ?>%</strong></td></tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header sisfour-section-heading d-flex justify-content-between align-items-center gap-2">
        <h5 class="mb-0">EWS Alpha 14 Hari</h5>
        <?php if ($canEws): ?><span class="badge bg-label-danger"><?= (int) ($widgets['ews_count'] ?? 0) ?> siswa</span><?php endif; ?>
      </div>
      <div class="list-group list-group-flush">
        <?php if (! $canEws): ?>
          <div class="list-group-item sisfour-mobile-state text-muted">Data EWS tidak tersedia untuk akun ini.</div>
        <?php elseif ($ewsTop === []): ?>
          <div class="list-group-item sisfour-mobile-state text-muted">Tidak ada siswa EWS.</div>
        <?php else: foreach ($ewsTop as $row): ?>
          <div class="list-group-item d-flex justify-content-between align-items-center gap-3 py-3">
            <div class="sisfour-cell-primary">
              <span class="sisfour-cell-title"><?= esc((string) ($row['nama'] ?? '-')) ?></span>
              <span class="sisfour-cell-meta">Alpha Sesi Awal 14 hari terakhir</span>
            </div>
            <span class="badge bg-label-danger flex-shrink-0"><?= (int) ($row['total_alpha'] ?? 0) ?> Alpha</span>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header sisfour-section-heading d-flex justify-content-between align-items-center gap-2">
    <h5 class="mb-0">Prestasi Terbaru</h5>
    <?php if (! $canPrestasi): ?><span class="badge bg-label-secondary">Tidak tersedia</span><?php endif; ?>
  </div>
  <div class="list-group list-group-flush">
    <?php if (! $canPrestasi): ?>
      <div class="list-group-item sisfour-mobile-state text-muted">Data Prestasi tidak tersedia untuk akun ini.</div>
    <?php elseif ($prestasiTerbaru === []): ?>
      <div class="list-group-item sisfour-mobile-state text-muted">Belum ada data prestasi.</div>
    <?php else: foreach ($prestasiTerbaru as $row): ?>
      <div class="list-group-item py-3">
        <div class="sisfour-cell-primary">
          <span class="sisfour-cell-title"><?= esc((string) ($row['nama'] ?? '-')) ?></span>
          <span class="d-block"><?= esc((string) ($row['nama_prestasi'] ?? '-')) ?></span>
          <span class="sisfour-cell-meta"><?= esc((string) ($row['tanggal'] ?? '-')) ?> · <?= esc((string) ($row['tingkat'] ?? '-')) ?></span>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<div class="mb-2 d-flex justify-content-between align-items-center gap-2">
  <h5 class="mb-0">Ringkasan Master</h5>
  <small class="text-muted">Informasi pendukung</small>
</div>
<div class="row g-3">
  <?php
  $masterItems = [
      ['Siswa Aktif', $master['siswa'] ?? 0],
      ['Guru', $master['guru'] ?? 0],
      ['Pegawai', $master['pegawai'] ?? 0],
      ['Kelas', $master['kelas'] ?? 0],
  ];
  if ($canKartu) {
      $masterItems[] = ['Kartu Aktif', $kartu['aktif'] ?? 0];
  }
  ?>
  <?php foreach ($masterItems as $item): ?>
    <div class="col-6 col-md">
      <div class="card h-100"><div class="card-body"><small class="text-muted"><?= esc($item[0]) ?></small><h5 class="mb-0"><?= (int) $item[1] ?></h5></div></div>
    </div>
  <?php endforeach; ?>
</div>

<?= $this->endSection() ?>
