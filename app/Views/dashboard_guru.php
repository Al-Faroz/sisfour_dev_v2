<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php
$task = $widgets['task_summary'] ?? [];
$tahun = $widgets['tahun_aktif'] ?? [];
$jadwalHariIni = $widgets['jadwal_hari_ini'] ?? [];
$riwayatJurnal = $widgets['riwayat_jurnal_terakhir'] ?? [];

$presensiLabels = [
    'not_applicable' => ['—', 'secondary', false],
    'submitted' => ['✓ Presensi', 'success', false],
    'not_started' => ['Belum mulai', 'secondary', false],
    'available' => ['Isi Presensi', 'primary', true],
    'wali_available' => ['Isi Presensi', 'info', true],
    'ended' => ['Selesai', 'secondary', false],
];

$jurnalLabels = [
    'submitted' => ['✓ Jurnal', 'success', false],
    'not_started' => ['Belum mulai', 'secondary', false],
    'available' => ['Isi Jurnal', 'primary', true],
    'ended' => ['Terlewat', 'danger', false],
];
?>

<div class="sisfour-dashboard">
  <div class="sf-dashboard-header">
    <div>
      <h4>Dashboard Guru</h4>
      <p class="text-muted">Jadwal dan pekerjaan mengajar hari ini.</p>
    </div>
    <span class="badge bg-label-primary sf-year-badge">
      <?= esc(($tahun['nama_tahun'] ?? 'Tahun belum aktif') . (!empty($tahun['semester']) ? ' · ' . $tahun['semester'] : '')) ?>
    </span>
  </div>

  <div class="sf-stat-grid">
    <?php foreach ([
        ['Jadwal', $task['jadwal'] ?? 0, 'primary'],
        ['Presensi Perlu', $task['presensi_perlu'] ?? 0, 'warning'],
        ['Jurnal Perlu', $task['jurnal_perlu'] ?? 0, 'danger'],
        ['Jurnal Selesai', $task['jurnal_selesai'] ?? 0, 'success'],
    ] as $item): ?>
      <div class="sf-stat-card">
        <span class="sf-stat-card__label"><?= esc($item[0]) ?></span>
        <span class="sf-stat-card__value text-<?= esc($item[2]) ?>"><?= (int) $item[1] ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card sf-section-card">
    <div class="card-header d-flex justify-content-between align-items-center gap-2">
      <h5 class="mb-0">Jadwal & Tugas Hari Ini</h5>
      <small class="text-muted d-none d-sm-inline">Sesi disederhanakan dari tampilan</small>
    </div>

    <?php if (empty($jadwalHariIni)): ?>
      <div class="card-body text-center text-muted py-4">
        Tidak ada jadwal mengajar hari ini.
      </div>
    <?php else: ?>
      <div class="sf-agenda">
        <?php foreach ($jadwalHariIni as $j): ?>
          <?php
          $ps = $j['presensi_state'] ?? '';
          [$presensiLabel, $presensiColor, $presensiAction] = $presensiLabels[$ps] ?? ['Tidak tersedia', 'secondary', false];

          $js = $j['jurnal_state'] ?? '';
          [$jurnalLabel, $jurnalColor, $jurnalAction] = $jurnalLabels[$js] ?? ['Tidak tersedia', 'secondary', false];
          ?>
          <div class="sf-agenda-row">
            <div class="sf-agenda-time">
              <?= esc(substr((string) ($j['jam_mulai'] ?? ''), 0, 5)) ?>–<?= esc(substr((string) ($j['jam_selesai'] ?? ''), 0, 5)) ?>
            </div>

            <div>
              <div class="sf-agenda-class"><?= esc($j['nama_kelas'] ?? '-') ?></div>
              <div class="sf-agenda-mapel"><?= esc($j['nama_mapel'] ?? '-') ?></div>
            </div>

            <div class="sf-agenda-actions">
              <?php if ($presensiAction && !empty($j['presensi_url'])): ?>
                <a class="btn btn-sm btn-outline-<?= esc($presensiColor) ?>" href="<?= base_url($j['presensi_url']) ?>">
                  <?= esc($presensiLabel) ?>
                </a>
              <?php else: ?>
                <span class="badge bg-label-<?= esc($presensiColor) ?>"><?= esc($presensiLabel) ?></span>
              <?php endif; ?>

              <?php if ($jurnalAction && !empty($j['jurnal_url'])): ?>
                <a class="btn btn-sm btn-outline-<?= esc($jurnalColor) ?>" href="<?= base_url($j['jurnal_url']) ?>">
                  <?= esc($jurnalLabel) ?>
                </a>
              <?php else: ?>
                <span class="badge bg-label-<?= esc($jurnalColor) ?>"><?= esc($jurnalLabel) ?></span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="card sf-section-card mb-0">
    <div class="card-header d-flex justify-content-between align-items-center gap-2">
      <h5 class="mb-0">Riwayat Jurnal Terakhir</h5>
      <?php if (!empty($widgets['profile_available'])): ?>
        <a href="<?= base_url('profile/guru') ?>" class="btn btn-sm btn-outline-secondary">
          <i class="bx bx-user me-1"></i>Profil Saya
        </a>
      <?php endif; ?>
    </div>

    <ul class="list-group list-group-flush sf-compact-list">
      <?php if (empty($riwayatJurnal)): ?>
        <li class="list-group-item text-center text-muted py-4">Belum ada jurnal.</li>
      <?php else: ?>
        <?php foreach ($riwayatJurnal as $row): ?>
          <li class="list-group-item">
            <div class="d-flex justify-content-between gap-3 flex-wrap">
              <div>
                <strong><?= esc($row['tanggal']) ?> · <?= esc($row['nama_kelas'] ?? '-') ?></strong>
                <div class="small text-muted"><?= esc($row['nama_mapel'] ?? '-') ?> · <?= esc($row['status']) ?></div>
              </div>
              <div class="text-muted small text-md-end">
                <?= esc(mb_strimwidth((string) ($row['materi'] ?? ''), 0, 70, '...')) ?>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
      <?php endif; ?>
    </ul>
  </div>
</div>

<?= $this->endSection() ?>
