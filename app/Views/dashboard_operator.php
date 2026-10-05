<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php
$master = $widgets['master'] ?? [];
$presensi = $widgets['presensi_hari_ini'] ?? [];
$jurnal = $widgets['jurnal_hari_ini'] ?? [];
$kartu = $widgets['kartu'] ?? [];
$tahun = $widgets['tahun_aktif'] ?? [];
?>

<div class="sisfour-page-header">
  <div class="sisfour-page-header__copy"><h4 class="mb-1">Dashboard Operator</h4><p class="text-muted mb-0">Prioritas operasional harian madrasah.</p></div>
  <div class="sisfour-page-actions">
    <a href="<?= base_url('signage') ?>" target="_blank" rel="noopener" class="btn sisfour-action sisfour-action--amber sisfour-action--compact sisfour-touch-target">
      <i class="bx bx-tv me-1"></i>EWS Signage
    </a>
    <span class="badge bg-label-primary fs-6"><?= esc(($tahun['nama_tahun'] ?? 'Tahun belum aktif') . (!empty($tahun['semester']) ? ' · ' . $tahun['semester'] : '')) ?></span>
  </div>
</div>

<div class="sisfour-dashboard-heading">
  <h5 class="mb-0">Kondisi Operasional</h5>
  <small class="text-muted">Pengecualian hari ini</small>
</div>
<div class="sisfour-metric-grid sisfour-metric-grid--3 mb-4">
  <div class="sisfour-metric-tile sisfour-metric-tile--prominent sisfour-metric-tile--amber">
    <span class="sisfour-metric-tile__icon"><i class="bx bx-list-check"></i></span>
    <strong class="sisfour-metric-tile__value"><?= (int) ($presensi['belum_kelas'] ?? 0) ?></strong>
    <span class="sisfour-metric-tile__label">Kelas Belum Presensi</span>
    <span class="sisfour-metric-tile__meta">dari <?= (int) ($presensi['wajib_kelas'] ?? 0) ?> kelas wajib</span>
  </div>
  <div class="sisfour-metric-tile sisfour-metric-tile--prominent sisfour-metric-tile--violet">
    <span class="sisfour-metric-tile__icon"><i class="bx bx-book-content"></i></span>
    <strong class="sisfour-metric-tile__value"><?= (int) ($jurnal['belum'] ?? 0) ?></strong>
    <span class="sisfour-metric-tile__label">Jadwal Belum Jurnal</span>
    <span class="sisfour-metric-tile__meta">dari <?= (int) ($jurnal['wajib'] ?? 0) ?> jadwal aktif</span>
  </div>
  <div class="sisfour-metric-tile sisfour-metric-tile--prominent sisfour-metric-tile--amber">
    <span class="sisfour-metric-tile__icon"><i class="bx bx-radar"></i></span>
    <strong class="sisfour-metric-tile__value"><?= (int) ($widgets['ews_count'] ?? 0) ?></strong>
    <span class="sisfour-metric-tile__label">EWS Alpha 14 Hari</span>
    <span class="sisfour-metric-tile__meta">siswa perlu perhatian</span>
  </div>
</div>

<div class="sisfour-dashboard-heading">
  <h5 class="mb-0">Ringkasan Master</h5>
  <small class="text-muted">Data aktif</small>
</div>
<div class="sisfour-metric-grid mb-4">
  <?php foreach ([
    ['Siswa', $master['siswa'] ?? 0, 'indigo', 'bx-group'],
    ['Guru', $master['guru'] ?? 0, 'blue', 'bx-chalkboard'],
    ['Pegawai', $master['pegawai'] ?? 0, 'cyan', 'bx-id-card'],
    ['Kelas', $master['kelas'] ?? 0, 'violet', 'bx-door-open'],
  ] as $item): ?>
    <div class="sisfour-metric-tile sisfour-metric-tile--compact sisfour-metric-tile--<?= esc($item[2]) ?>">
      <span class="sisfour-metric-tile__icon"><i class="bx <?= esc($item[3]) ?>"></i></span>
      <strong class="sisfour-metric-tile__value"><?= (int) $item[1] ?></strong>
      <span class="sisfour-metric-tile__label"><?= esc($item[0]) ?></span>
    </div>
  <?php endforeach; ?>
</div>

<div class="card mb-4">
  <div class="card-header"><h5 class="mb-0">Presensi Hari Ini</h5></div>
  <div class="card-body"><div class="sisfour-context-stat-grid">
    <?php foreach ([['Hadir',$presensi['hadir']??0,'success'],['Sakit',$presensi['sakit']??0,'warning'],['Izin',$presensi['izin']??0,'info'],['Alpha',$presensi['alpha']??0,'danger']] as $item): ?>
      <div class="sisfour-context-stat"><small class="text-muted d-block"><?= esc($item[0]) ?></small><strong class="text-<?= esc($item[2]) ?>"><?= (int) $item[1] ?></strong></div>
    <?php endforeach; ?>
  </div></div>
</div>

<div class="sisfour-dashboard-heading">
  <h5 class="mb-0">Ringkasan Pendukung</h5>
</div>
<div class="sisfour-metric-grid sisfour-metric-grid--3 mb-4">
  <div class="sisfour-metric-tile sisfour-metric-tile--compact sisfour-metric-tile--rose">
    <span class="sisfour-metric-tile__icon"><i class="bx bx-note"></i></span>
    <strong class="sisfour-metric-tile__value"><?= (int) ($widgets['bk_bulan_ini'] ?? 0) ?></strong>
    <span class="sisfour-metric-tile__label">Kasus BK Bulan Ini</span>
  </div>
  <div class="sisfour-metric-tile sisfour-metric-tile--compact sisfour-metric-tile--green">
    <span class="sisfour-metric-tile__icon"><i class="bx bx-trophy"></i></span>
    <strong class="sisfour-metric-tile__value"><?= (int) ($widgets['prestasi_bulan_ini'] ?? 0) ?></strong>
    <span class="sisfour-metric-tile__label">Prestasi Bulan Ini</span>
  </div>
  <div class="sisfour-metric-tile sisfour-metric-tile--compact sisfour-metric-tile--teal">
    <span class="sisfour-metric-tile__icon"><i class="bx bx-id-card"></i></span>
    <strong class="sisfour-metric-tile__value"><?= (int) ($kartu['aktif'] ?? 0) ?></strong>
    <span class="sisfour-metric-tile__label">Kartu Aktif</span>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card sisfour-table-card h-100">
      <div class="card-header"><h5 class="mb-0">Tren Presensi 7 Hari</h5></div>
      <div class="d-md-none list-group list-group-flush">
        <?php if (empty($widgets['tren_presensi'])): ?>
          <div class="list-group-item sisfour-mobile-state text-muted">Belum ada data tren.</div>
        <?php else: foreach (($widgets['tren_presensi'] ?? []) as $row): ?>
          <div class="list-group-item d-flex justify-content-between align-items-center gap-3 py-3">
            <span class="min-w-0 text-wrap"><?= esc($row['tanggal']) ?></span>
            <strong class="flex-shrink-0"><?= esc((string) $row['persen_hadir']) ?>%</strong>
          </div>
        <?php endforeach; endif; ?>
      </div>
      <div class="d-none d-md-block table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>Tanggal</th><th>% Hadir</th></tr></thead>
          <tbody>
            <?php if (empty($widgets['tren_presensi'])): ?>
              <tr class="sisfour-empty-row"><td colspan="2" class="text-muted">Belum ada data tren.</td></tr>
            <?php else: foreach (($widgets['tren_presensi'] ?? []) as $row): ?>
              <tr><td><?= esc($row['tanggal']) ?></td><td><?= esc((string) $row['persen_hadir']) ?>%</td></tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card sisfour-table-card h-100">
      <div class="card-header"><h5 class="mb-0">Aktivitas Terakhir</h5></div>
      <div class="d-md-none list-group list-group-flush">
        <?php if (empty($widgets['aktivitas_terakhir'])): ?>
          <div class="list-group-item sisfour-mobile-state text-muted">Belum ada aktivitas.</div>
        <?php else: foreach ($widgets['aktivitas_terakhir'] as $log): ?>
          <div class="list-group-item py-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-1">
              <strong class="min-w-0 text-wrap"><?= esc($log['modul']) ?></strong>
              <small class="text-muted flex-shrink-0"><?= esc($log['waktu']) ?></small>
            </div>
            <div class="small fw-semibold text-wrap"><?= esc($log['aksi']) ?></div>
          </div>
        <?php endforeach; endif; ?>
      </div>
      <div class="d-none d-md-block table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>Waktu</th><th>Modul</th><th>Aksi</th></tr></thead>
          <tbody>
            <?php if (empty($widgets['aktivitas_terakhir'])): ?>
              <tr><td colspan="3" class="text-center text-muted py-4">Belum ada aktivitas.</td></tr>
            <?php else: foreach ($widgets['aktivitas_terakhir'] as $log): ?>
              <tr><td><?= esc($log['waktu']) ?></td><td><?= esc($log['modul']) ?></td><td><?= esc($log['aksi']) ?></td></tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
