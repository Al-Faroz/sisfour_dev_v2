<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php
$rekap = $widgets['rekap_presensi_bulan_ini'] ?? [];
$kartu = $widgets['kartu'] ?? null;
$presensiHariIni = $widgets['presensi_hari_ini'] ?? null;
$access = $widgets['access'] ?? [];
$canPresensi = ! empty($access['presensi']);
$canPrestasi = ! empty($access['prestasi']);
$canPelanggaran = ! empty($access['pelanggaran']);
$canKartu = ! empty($access['kartu']);
$statusHariIni = trim((string) ($presensiHariIni['status'] ?? ''));
$statusClass = [
    'Hadir' => 'success',
    'Sakit' => 'warning',
    'Izin' => 'info',
    'Alpha' => 'danger',
][$statusHariIni] ?? 'secondary';
$tanggalHariIni = trim((string) ($presensiHariIni['tanggal'] ?? ''));
$presensiTerbaru = $widgets['presensi_terbaru'] ?? [];
$riwayatPrestasi = $widgets['riwayat_prestasi'] ?? [];
$riwayatPelanggaran = $widgets['riwayat_pelanggaran'] ?? [];
?>

<div class="sisfour-page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
  <div class="sisfour-page-header__copy">
    <h4 class="mb-1">Dashboard Siswa</h4>
    <p class="text-muted mb-0">Ringkasan readonly untuk data milik sendiri.</p>
  </div>
  <span class="badge bg-label-secondary">Data Saya</span>
</div>

<?php if ($canPresensi): ?>
<div class="card mb-4">
  <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
    <div>
      <div class="text-muted small mb-1">Status Kehadiran Hari Ini</div>
      <h4 class="mb-1">Presensi Sesi Awal</h4>
      <?php if ($tanggalHariIni !== ''): ?>
        <div class="small text-muted"><?= esc(date('d-m-Y', strtotime($tanggalHariIni))) ?></div>
      <?php endif; ?>
    </div>

    <div class="text-md-end">
      <?php if (! empty($presensiHariIni['tersedia']) && $statusHariIni !== ''): ?>
        <span class="badge bg-label-<?= esc($statusClass) ?> fs-5 px-3 py-2">
          <?= esc($statusHariIni) ?>
        </span>
      <?php else: ?>
        <span class="badge bg-label-secondary fs-6 px-3 py-2">Belum tercatat</span>
        <div class="small text-muted mt-2">Data Sesi Awal hari ini belum tersedia.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
  <h5 class="mb-0">Rekap Presensi Bulan Ini</h5>
  <small class="text-muted">Sesi Awal</small>
</div>

<div class="sisfour-mobile-kpi-grid mb-4">
  <?php foreach ([
      ['Hadir', $rekap['hadir'] ?? 0, 'success'],
      ['Sakit', $rekap['sakit'] ?? 0, 'warning'],
      ['Izin', $rekap['izin'] ?? 0, 'info'],
      ['Alpha', $rekap['alpha'] ?? 0, 'danger'],
  ] as $item): ?>
    <div class="card h-100">
      <div class="card-body text-center">
        <small class="text-muted d-block"><?= esc($item[0]) ?></small>
        <h3 class="text-<?= esc($item[2]) ?> mb-0"><?= (int) $item[1] ?></h3>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center gap-2">
    <h5 class="mb-0">Sakit / Izin / Alpha Terbaru</h5>
    <a href="<?= base_url('presensi/siswa/rekap') ?>" class="btn btn-sm btn-outline-primary sisfour-touch-target--compact">Lihat Rekap</a>
  </div>

  <div class="d-md-none list-group list-group-flush">
    <?php if ($presensiTerbaru === []): ?>
      <div class="list-group-item sisfour-mobile-state text-muted">Tidak ada catatan ketidakhadiran.</div>
    <?php else: foreach ($presensiTerbaru as $row): ?>
      <?php $status = (string) ($row['status'] ?? ''); $color = $status === 'Alpha' ? 'danger' : ($status === 'Sakit' ? 'warning' : 'info'); ?>
      <div class="list-group-item d-flex justify-content-between align-items-center gap-3 py-3">
        <span><?= esc((string) ($row['tanggal'] ?? '-')) ?></span>
        <span class="badge bg-label-<?= esc($color) ?>"><?= esc($status) ?></span>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <div class="d-none d-md-block table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Tanggal</th><th>Status</th></tr></thead>
      <tbody>
        <?php if ($presensiTerbaru === []): ?>
          <tr><td colspan="2" class="text-center text-muted py-4">Tidak ada catatan ketidakhadiran.</td></tr>
        <?php else: foreach ($presensiTerbaru as $row): ?>
          <?php $status = (string) ($row['status'] ?? ''); $color = $status === 'Alpha' ? 'danger' : ($status === 'Sakit' ? 'warning' : 'info'); ?>
          <tr><td><?= esc((string) ($row['tanggal'] ?? '-')) ?></td><td><span class="badge bg-label-<?= esc($color) ?>"><?= esc($status) ?></span></td></tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($canKartu): ?>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center gap-2">
    <h5 class="mb-0">Kartu Pelajar</h5>
    <?php if (! empty($kartu)): ?>
      <a href="<?= base_url('kartu/daftar') ?>" class="btn btn-sm btn-outline-primary sisfour-touch-target--compact">Buka Kartu</a>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <?php if (empty($kartu)): ?>
      <p class="text-muted mb-0">Kartu pelajar belum tersedia.</p>
    <?php else: ?>
      <div class="mb-2">
        <span class="badge bg-label-<?= ($kartu['status_aktif'] ?? '') === 'Aktif' ? 'success' : 'secondary' ?>">
          <?= esc((string) ($kartu['status_aktif'] ?? '-')) ?>
        </span>
      </div>
      <strong class="d-block mb-1"><?= esc((string) ($kartu['nomor_kartu'] ?? '-')) ?></strong>
      <div class="small text-muted mb-3">Terbit <?= esc((string) ($kartu['tanggal_terbit'] ?? '-')) ?></div>
      <div class="sisfour-mobile-actions">
        <a href="<?= base_url('kartu/preview/' . (int) $kartu['id']) ?>" class="btn btn-sm btn-outline-primary sisfour-touch-target--compact">
          <i class="bx bx-show me-1"></i> Preview
        </a>
        <a href="<?= base_url('kartu/download/' . (int) $kartu['id']) ?>" class="btn btn-sm btn-primary sisfour-touch-target--compact">
          <i class="bx bx-download me-1"></i> Unduh PDF
        </a>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php if ($canPrestasi || $canPelanggaran): ?>
<div class="row g-3">
  <?php if ($canPrestasi): ?>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0">Prestasi Saya</h5></div>
      <div class="list-group list-group-flush">
        <?php if ($riwayatPrestasi === []): ?>
          <div class="list-group-item sisfour-mobile-state text-muted">Belum ada prestasi tercatat.</div>
        <?php else: foreach ($riwayatPrestasi as $row): ?>
          <div class="list-group-item py-3">
            <strong class="d-block"><?= esc((string) ($row['nama_prestasi'] ?? '-')) ?></strong>
            <span class="small text-muted"><?= esc((string) ($row['tingkat'] ?? '-')) ?> · <?= esc((string) ($row['tanggal'] ?? '-')) ?></span>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($canPelanggaran): ?>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0">Catatan Pelanggaran Saya</h5></div>
      <div class="list-group list-group-flush">
        <?php if ($riwayatPelanggaran === []): ?>
          <div class="list-group-item sisfour-mobile-state text-muted">Tidak ada catatan pelanggaran.</div>
        <?php else: foreach ($riwayatPelanggaran as $row): ?>
          <?php $kategori = (string) ($row['kategori'] ?? ''); $kategoriColor = $kategori === 'Berat' ? 'danger' : ($kategori === 'Sedang' ? 'warning' : 'secondary'); ?>
          <div class="list-group-item py-3">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <strong><?= esc((string) ($row['nama_pelanggaran'] ?? '-')) ?></strong>
              <span class="badge bg-label-<?= esc($kategoriColor) ?> flex-shrink-0"><?= esc($kategori !== '' ? $kategori : '-') ?></span>
            </div>
            <div class="small text-muted mt-1"><?= esc((string) ($row['tanggal'] ?? '-')) ?></div>
            <?php if (! empty($row['keterangan'])): ?><div class="small mt-2 text-break"><?= esc((string) $row['keterangan']) ?></div><?php endif; ?>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if (! $canPresensi && ! $canKartu && ! $canPrestasi && ! $canPelanggaran): ?>
  <div class="alert alert-secondary mb-0">Belum ada data self-service yang tersedia untuk akun ini.</div>
<?php endif; ?>

<?= $this->endSection() ?>
