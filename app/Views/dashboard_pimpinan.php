<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php
$master = $widgets['master'] ?? [];
$presensi = $widgets['presensi_hari_ini'] ?? [];
$jurnal = $widgets['jurnal_hari_ini'] ?? [];
$kartu = $widgets['kartu'] ?? [];
$access = $widgets['access'] ?? [];
$tahun = is_array($widgets['tahun_aktif'] ?? null) ? $widgets['tahun_aktif'] : null;
$periodAvailable = ! empty($widgets['period_available']);
$quickActions = is_array($widgets['quick_actions'] ?? null) ? $widgets['quick_actions'] : [];
$canEws = ! empty($access['ews']);
$canPelanggaran = ! empty($access['pelanggaran']);
$canPrestasi = ! empty($access['prestasi']);
$canKartu = ! empty($access['kartu']);
$tren = $widgets['tren_presensi'] ?? [];
$ewsTop = $widgets['ews_top'] ?? [];
$prestasiTerbaru = $widgets['prestasi_terbaru'] ?? [];

$kpi = [
    [
        'label' => 'Kelas Belum Presensi',
        'value' => $periodAvailable ? (int) ($presensi['belum_kelas'] ?? 0) : null,
        'color' => 'warning',
        'icon' => 'bx-list-check',
        'meta' => $periodAvailable
            ? 'dari ' . (int) ($presensi['wajib_kelas'] ?? 0) . ' kelas wajib'
            : 'Tahun Ajaran aktif belum tersedia',
    ],
    [
        'label' => 'Jadwal Belum Jurnal',
        'value' => $periodAvailable ? (int) ($jurnal['belum'] ?? 0) : null,
        'color' => 'warning',
        'icon' => 'bx-book-content',
        'meta' => $periodAvailable
            ? 'dari ' . (int) ($jurnal['wajib'] ?? 0) . ' jadwal'
            : 'Tahun Ajaran aktif belum tersedia',
    ],
    [
        'label' => 'EWS 14 Hari',
        'value' => $canEws && $periodAvailable
            ? (int) ($widgets['ews_count'] ?? 0)
            : null,
        'color' => 'danger',
        'icon' => 'bx-radar',
        'meta' => ! $canEws
            ? 'tidak tersedia'
            : ($periodAvailable ? 'siswa' : 'Tahun Ajaran aktif belum tersedia'),
    ],
    [
        'label' => 'Catatan Pelanggaran Bulan Ini',
        'value' => $canPelanggaran && $periodAvailable
            ? (int) ($widgets['kasus_bulan_ini'] ?? 0)
            : null,
        'color' => 'primary',
        'icon' => 'bx-note',
        'meta' => ! $canPelanggaran
            ? 'tidak tersedia'
            : ($periodAvailable ? 'catatan · Tahun Ajaran aktif' : 'Tahun Ajaran aktif belum tersedia'),
    ],
];
?>

<div class="sisfour-page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
  <div class="sisfour-page-header__copy">
    <h4 class="mb-1">Dashboard Pimpinan</h4>
    <p class="text-muted mb-0">Ringkasan supervisi readonly berdasarkan data operasional yang diizinkan.</p>
  </div>
  <div class="d-flex flex-wrap gap-2 justify-content-end">
    <?php if ($tahun !== null): ?>
      <span class="badge bg-label-primary">
        <?= esc((string) ($tahun['nama_tahun'] ?? '-')) ?> · <?= esc((string) ($tahun['semester'] ?? '-')) ?>
      </span>
    <?php else: ?>
      <span class="badge bg-label-warning">Tahun Ajaran aktif belum tersedia</span>
    <?php endif; ?>
    <span class="badge bg-label-secondary">Readonly</span>
  </div>
</div>

<div class="sisfour-mobile-kpi-grid mb-4">
  <?php foreach ($kpi as $item): ?>
    <div class="card h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <span class="avatar flex-shrink-0 bg-label-<?= esc($item['color']) ?> rounded">
          <i class="bx <?= esc($item['icon']) ?>"></i>
        </span>
        <div class="min-w-0">
          <small class="text-muted d-block"><?= esc($item['label']) ?></small>
          <h4 class="text-<?= esc($item['color']) ?> mb-0"><?= $item['value'] === null ? '—' : (int) $item['value'] ?></h4>
          <small class="text-muted"><?= esc((string) $item['meta']) ?></small>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($quickActions !== []): ?>
<div class="card mb-4">
  <div class="card-header sisfour-section-heading">
    <h5 class="mb-0">Aksi Cepat</h5>
  </div>
  <div class="card-body">
    <div class="row g-2">
      <?php foreach ($quickActions as $action): ?>
        <div class="col-6 col-md-3">
          <a href="<?= base_url((string) ($action['url'] ?? '')) ?>"
             class="btn btn-outline-primary w-100 h-100 sisfour-touch-target justify-content-start text-start p-3">
            <span class="d-flex align-items-center gap-2 min-w-0">
              <i class="bx <?= esc((string) ($action['icon'] ?? 'bx-link')) ?> fs-4 flex-shrink-0"></i>
              <span class="min-w-0">
                <strong class="d-block text-wrap"><?= esc((string) ($action['label'] ?? '-')) ?></strong>
                <small class="d-block text-muted text-wrap"><?= esc((string) ($action['description'] ?? '')) ?></small>
              </span>
            </span>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header sisfour-section-heading d-flex justify-content-between align-items-center gap-2">
        <h5 class="mb-0">Tren Presensi 7 Hari</h5>
        <small class="text-muted">Sesi Awal</small>
      </div>

      <div class="d-md-none list-group list-group-flush">
        <?php if (! $periodAvailable): ?>
          <div class="list-group-item sisfour-mobile-state text-muted">Tahun Ajaran aktif belum tersedia.</div>
        <?php elseif ($tren === []): ?>
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
            <?php if (! $periodAvailable): ?>
              <tr><td colspan="2" class="text-center text-muted py-4">Tahun Ajaran aktif belum tersedia.</td></tr>
            <?php elseif ($tren === []): ?>
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
        <div class="d-flex align-items-center gap-2">
          <?php if ($canEws && $periodAvailable): ?>
            <span class="badge bg-label-danger"><?= (int) ($widgets['ews_count'] ?? 0) ?> siswa</span>
          <?php endif; ?>
          <?php if ($canEws): ?>
            <a href="<?= base_url('presensi/siswa/ews') ?>" class="btn btn-sm btn-outline-primary sisfour-touch-target">Lihat Semua</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="list-group list-group-flush">
        <?php if (! $canEws): ?>
          <div class="list-group-item sisfour-mobile-state text-muted">Data EWS tidak tersedia untuk akun ini.</div>
        <?php elseif (! $periodAvailable): ?>
          <div class="list-group-item sisfour-mobile-state text-muted">Tahun Ajaran aktif belum tersedia.</div>
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
    <?php if ($canPrestasi): ?>
      <a href="<?= base_url('bk/prestasi') ?>" class="btn btn-sm btn-outline-primary sisfour-touch-target">Lihat Semua</a>
    <?php else: ?>
      <span class="badge bg-label-secondary">Tidak tersedia</span>
    <?php endif; ?>
  </div>
  <div class="list-group list-group-flush">
    <?php if (! $canPrestasi): ?>
      <div class="list-group-item sisfour-mobile-state text-muted">Data Prestasi tidak tersedia untuk akun ini.</div>
    <?php elseif (! $periodAvailable): ?>
      <div class="list-group-item sisfour-mobile-state text-muted">Tahun Ajaran aktif belum tersedia.</div>
    <?php elseif ($prestasiTerbaru === []): ?>
      <div class="list-group-item sisfour-mobile-state text-muted">Belum ada data prestasi pada Tahun Ajaran aktif.</div>
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