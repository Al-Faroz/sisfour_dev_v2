<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php
$access = is_array($widgets['access'] ?? null) ? $widgets['access'] : [];
$quickActions = is_array($widgets['quick_actions'] ?? null) ? $widgets['quick_actions'] : [];
$tahun = is_array($widgets['tahun_aktif'] ?? null) ? $widgets['tahun_aktif'] : null;

$kpi = [
    [
        'label' => 'Konseling Proses',
        'value' => $widgets['konseling_proses'] ?? null,
        'tone' => 'indigo',
        'icon' => 'bx-message-rounded-dots',
        'meta' => 'Tahun Ajaran aktif',
    ],
    [
        'label' => 'Pelanggaran Bulan Ini',
        'value' => $widgets['kasus_bulan_ini'] ?? null,
        'tone' => 'rose',
        'icon' => 'bx-error-circle',
        'meta' => isset($widgets['pelanggaran_berat_bulan_ini'])
            ? ((int) $widgets['pelanggaran_berat_bulan_ini']) . ' kategori Berat'
            : null,
    ],
    [
        'label' => 'EWS Alpha 14 Hari',
        'value' => $widgets['ews_count'] ?? null,
        'tone' => 'amber',
        'icon' => 'bx-radar',
        'meta' => 'Minimal 3 Alpha Sesi Awal',
    ],
    [
        'label' => 'Prestasi Bulan Ini',
        'value' => $widgets['prestasi_bulan_ini'] ?? null,
        'tone' => 'green',
        'icon' => 'bx-trophy',
        'meta' => 'Tahun Ajaran aktif',
    ],
];

$actionTone = static function (string $label): string {
    $label = strtolower(trim($label));

    return match (true) {
        str_contains($label, 'konseling') => 'sisfour-action--violet',
        str_contains($label, 'pelanggaran') => 'sisfour-action--rose',
        str_contains($label, 'ews') => 'sisfour-action--amber',
        str_contains($label, 'prestasi') => 'sisfour-action--green',
        default => 'sisfour-action--indigo',
    };
};
?>

<div class="sisfour-page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
  <div class="sisfour-page-header__copy">
    <h4 class="mb-1">Dashboard BK</h4>
    <p class="text-muted mb-0">Prioritas konseling, tindak lanjut, pelanggaran, EWS, dan prestasi siswa.</p>
  </div>
  <?php if ($tahun !== null): ?>
    <span class="badge bg-label-primary">
      <?= esc((string) ($tahun['nama_tahun'] ?? '-')) ?> · <?= esc((string) ($tahun['semester'] ?? '-')) ?>
    </span>
  <?php else: ?>
    <span class="badge bg-label-warning">Tahun Ajaran aktif belum tersedia</span>
  <?php endif; ?>
</div>

<?php if ($quickActions !== []): ?>
<div class="sisfour-dashboard-heading">
  <h5 class="mb-0">Prioritas BK</h5>
  <small class="text-muted">Pekerjaan dan akses utama</small>
</div>
<div class="row g-2 mb-4">
  <?php foreach ($quickActions as $action): ?>
    <div class="col-6 col-md-3">
      <a href="<?= base_url((string) ($action['url'] ?? '')) ?>"
         class="btn sisfour-action sisfour-action--tile <?= esc($actionTone((string) ($action['label'] ?? '')), 'attr') ?> w-100 h-100 sisfour-touch-target">
        <span class="d-flex align-items-center gap-2 min-w-0">
          <i class="bx <?= esc((string) ($action['icon'] ?? 'bx-link')) ?> fs-4 flex-shrink-0"></i>
          <span class="min-w-0">
            <strong class="d-block text-wrap"><?= esc((string) ($action['label'] ?? '-')) ?></strong>
            <small class="d-block opacity-75 text-wrap"><?= esc((string) ($action['description'] ?? '')) ?></small>
          </span>
        </span>
      </a>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?= $this->include('_dashboard_secondary_actions') ?>

<div class="sisfour-dashboard-heading">
  <h5 class="mb-0">Ringkasan BK</h5>
  <small class="text-muted">Tahun Ajaran aktif</small>
</div>
<div class="sisfour-metric-grid mb-4">
  <?php foreach ($kpi as $item): ?>
    <?php if ($item['value'] === null): continue; endif; ?>
    <div class="sisfour-metric-tile sisfour-metric-tile--compact sisfour-metric-tile--<?= esc($item['tone']) ?>">
      <span class="sisfour-metric-tile__icon"><i class="bx <?= esc($item['icon']) ?>"></i></span>
      <strong class="sisfour-metric-tile__value"><?= (int) $item['value'] ?></strong>
      <span class="sisfour-metric-tile__label"><?= esc($item['label']) ?></span>
      <?php if (! empty($item['meta'])): ?>
        <span class="sisfour-metric-tile__meta"><?= esc((string) $item['meta']) ?></span>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<?php if (($access['konseling'] ?? false) === true): ?>
<div class="card mb-4">
  <div class="card-header sisfour-section-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h5 class="mb-1">Jadwal Follow-up Terdekat</h5>
      <small class="text-muted">Berdasarkan tanggal berikutnya yang tersimpan pada workflow Konseling.</small>
    </div>
    <a href="<?= base_url('bk/konseling') ?>" class="btn sisfour-action sisfour-action--indigo sisfour-action--compact sisfour-touch-target">Lihat Semua</a>
  </div>
  <div class="list-group list-group-flush">
    <?php if (empty($widgets['konseling_terdekat'])): ?>
      <div class="list-group-item text-muted text-center py-4">Belum ada jadwal follow-up Konseling yang tersimpan.</div>
    <?php else: ?>
      <?php foreach ($widgets['konseling_terdekat'] as $row): ?>
        <div class="list-group-item py-3">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div class="min-w-0">
              <strong class="d-block text-wrap"><?= esc((string) ($row['nama_siswa'] ?? '-')) ?></strong>
              <small class="text-muted d-block text-wrap">
                <?= esc((string) ($row['nama_kelas'] ?? '-')) ?>
                <?php if (! empty($row['nisn'])): ?> · <?= esc((string) $row['nisn']) ?><?php endif; ?>
              </small>
              <div class="mt-2 text-wrap">
                <?= esc((string) ($row['bidang'] ?? '-')) ?> · <?= esc((string) ($row['topik'] ?? '-')) ?>
              </div>
              <small class="text-muted d-block mt-1">Sumber jadwal: <?= esc((string) ($row['sumber_jadwal'] ?? '-')) ?></small>
            </div>
            <span class="badge bg-label-primary flex-shrink-0">
              <?= esc(date('d-m-Y', strtotime((string) ($row['tanggal_follow_up'] ?? '')))) ?>
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <?php if (($access['pelanggaran'] ?? false) === true): ?>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header sisfour-section-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Catatan Pelanggaran Terbaru</h5>
        <a href="<?= base_url('bk/kasus') ?>" class="btn sisfour-action sisfour-action--rose sisfour-action--compact sisfour-touch-target">Lihat Semua</a>
      </div>
      <div class="list-group list-group-flush">
        <?php if (empty($widgets['kasus_terbaru'])): ?>
          <div class="list-group-item text-muted text-center py-4">Belum ada catatan pelanggaran pada Tahun Ajaran aktif.</div>
        <?php else: ?>
          <?php foreach ($widgets['kasus_terbaru'] as $row): ?>
            <?php
            $kategori = (string) ($row['kategori'] ?? '');
            $kategoriColor = $kategori === 'Berat'
                ? 'danger'
                : ($kategori === 'Sedang' ? 'warning' : 'secondary');
            ?>
            <div class="list-group-item py-3">
              <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-1">
                <div class="min-w-0">
                  <strong class="d-block text-wrap"><?= esc((string) ($row['nama'] ?? '-')) ?></strong>
                  <?php if (! empty($row['nisn'])): ?>
                    <small class="text-muted d-block"><?= esc((string) $row['nisn']) ?></small>
                  <?php endif; ?>
                </div>
                <span class="badge bg-label-<?= esc($kategoriColor) ?> flex-shrink-0"><?= esc($kategori !== '' ? $kategori : '-') ?></span>
              </div>
              <div class="text-wrap"><?= esc((string) ($row['nama_pelanggaran'] ?? '-')) ?></div>
              <small class="text-muted"><?= esc((string) ($row['tanggal'] ?? '-')) ?></small>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (($access['ews'] ?? false) === true): ?>
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header sisfour-section-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">EWS Alpha 14 Hari</h5>
        <a href="<?= base_url('presensi/siswa/ews') ?>" class="btn sisfour-action sisfour-action--amber sisfour-action--compact sisfour-touch-target">Lihat Semua</a>
      </div>
      <div class="list-group list-group-flush">
        <?php if (empty($widgets['ews_top'])): ?>
          <div class="list-group-item text-muted text-center py-4">Tidak ada siswa EWS saat ini.</div>
        <?php else: ?>
          <?php foreach ($widgets['ews_top'] as $row): ?>
            <div class="list-group-item py-3 d-flex justify-content-between align-items-center gap-3">
              <strong class="text-wrap min-w-0 flex-grow-1"><?= esc((string) ($row['nama'] ?? '-')) ?></strong>
              <span class="badge bg-label-warning flex-shrink-0"><?= (int) ($row['total_alpha'] ?? 0) ?> Alpha</span>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php if (($access['prestasi'] ?? false) === true): ?>
<div class="card mb-4">
  <div class="card-header sisfour-section-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="mb-0">Prestasi Terbaru</h5>
    <a href="<?= base_url('bk/prestasi') ?>" class="btn sisfour-action sisfour-action--green sisfour-action--compact sisfour-touch-target">Lihat Semua</a>
  </div>
  <div class="list-group list-group-flush">
    <?php if (empty($widgets['prestasi_terbaru'])): ?>
      <div class="list-group-item text-muted text-center py-4">Belum ada prestasi pada Tahun Ajaran aktif.</div>
    <?php else: ?>
      <?php foreach ($widgets['prestasi_terbaru'] as $row): ?>
        <div class="list-group-item py-3">
          <div class="d-flex justify-content-between align-items-start gap-3">
            <div class="min-w-0">
              <strong class="d-block text-wrap"><?= esc((string) ($row['nama'] ?? '-')) ?></strong>
              <?php if (! empty($row['nisn'])): ?>
                <small class="text-muted d-block"><?= esc((string) $row['nisn']) ?></small>
              <?php endif; ?>
              <div class="mt-1 text-wrap"><?= esc((string) ($row['nama_prestasi'] ?? '-')) ?></div>
              <small class="text-muted"><?= esc((string) ($row['tanggal'] ?? '-')) ?></small>
            </div>
            <span class="badge bg-label-success flex-shrink-0"><?= esc((string) ($row['tingkat'] ?? '-')) ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
