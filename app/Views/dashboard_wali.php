<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php
$task = $widgets['task_summary'] ?? [];
$jadwal = $widgets['jadwal_hari_ini'] ?? [];
$quickActions = $widgets['quick_actions'] ?? [];
$nextSchedule = $widgets['next_schedule'] ?? null;
$wali = $widgets['wali'] ?? [];
$rekap = $wali['presensi_hari_ini'] ?? [];
$rekapTotal = (int) ($rekap['hadir'] ?? 0)
    + (int) ($rekap['sakit'] ?? 0)
    + (int) ($rekap['izin'] ?? 0)
    + (int) ($rekap['alpha'] ?? 0);

$presensiLabels = [
    'not_applicable' => ['Tidak berlaku', 'secondary'],
    'submitted' => ['Presensi selesai', 'success'],
    'not_started' => ['Belum waktunya', 'secondary'],
    'available' => ['Isi Presensi', 'primary'],
    'wali_available' => ['Isi sebagai Wali', 'info'],
    'ended' => ['Waktu habis', 'danger'],
];
$jurnalLabels = [
    'submitted' => ['Jurnal selesai', 'success'],
    'not_started' => ['Belum waktunya', 'secondary'],
    'available' => ['Isi Jurnal', 'primary'],
    'ended' => ['Terlewat', 'danger'],
];

$secondaryGuruActions = array_values(array_filter(
    $quickActions,
    static fn (array $action): bool => ! in_array(
        (string) ($action['label'] ?? ''),
        ['Presensi', 'Jurnal'],
        true
    )
));

$focusSchedule = is_array($nextSchedule) ? $nextSchedule : null;
if ($focusSchedule === null && $jadwal !== []) {
    $focusSchedule = $jadwal[array_key_last($jadwal)];
    $focusSchedule['dashboard_state'] = 'selesai';
}

$focusState = is_array($focusSchedule)
    ? (string) ($focusSchedule['dashboard_state'] ?? '')
    : '';
$focusStateLabel = match ($focusState) {
    'berlangsung' => 'Sedang Berlangsung',
    'berikutnya' => 'Jadwal Berikutnya',
    'selesai' => 'Jadwal Terakhir Hari Ini',
    default => 'Jadwal Mengajar',
};
?>

<div class="sisfour-page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
  <div class="sisfour-page-header__copy">
    <h4 class="mb-1">Dashboard Guru & Wali Kelas</h4>
    <p class="text-muted mb-0">Tugas mengajar hari ini dan kondisi kelas wali dalam satu layar.</p>
  </div>
  <?php if (! empty($wali['nama_kelas'])): ?>
    <span class="badge bg-label-primary">Wali <?= esc((string) $wali['nama_kelas']) ?></span>
  <?php endif; ?>
</div>

<div class="sisfour-mobile-kpi-grid mb-4">
  <?php foreach ([
      ['Jadwal Hari Ini', $task['jadwal'] ?? 0, 'primary', 'bx-calendar'],
      ['Belum Presensi', $task['belum_presensi'] ?? ($task['presensi_perlu'] ?? 0), 'warning', 'bx-list-check'],
      ['Belum Jurnal', $task['belum_jurnal'] ?? ($task['jurnal_perlu'] ?? 0), 'danger', 'bx-book-content'],
      ['Selesai', $task['selesai'] ?? ($task['jurnal_selesai'] ?? 0), 'success', 'bx-check-circle'],
  ] as $item): ?>
    <div class="card h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <span class="avatar flex-shrink-0 bg-label-<?= esc($item[2]) ?> rounded">
          <i class="bx <?= esc($item[3]) ?>"></i>
        </span>
        <div class="min-w-0">
          <small class="text-muted d-block"><?= esc($item[0]) ?></small>
          <h4 class="text-<?= esc($item[2]) ?> mb-0"><?= (int) $item[1] ?></h4>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card mb-4">
  <div class="card-header sisfour-section-heading d-flex align-items-center justify-content-between gap-2">
    <h5 class="mb-0">Quick Action Guru</h5>
    <?php if ((int) ($task['actionable_now'] ?? 0) > 0): ?>
      <span class="badge bg-label-primary"><?= (int) $task['actionable_now'] ?> bisa dikerjakan sekarang</span>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <div class="border rounded p-3 mb-3">
      <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <div class="min-w-0">
          <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
            <span class="avatar avatar-sm bg-label-primary rounded flex-shrink-0">
              <i class="bx bx-chalkboard"></i>
            </span>
            <strong>Jadwal Mengajar</strong>
            <?php if (is_array($focusSchedule)): ?>
              <span class="badge bg-label-primary"><?= esc($focusStateLabel) ?></span>
            <?php endif; ?>
          </div>

          <?php if (! is_array($focusSchedule)): ?>
            <div class="text-muted small">Tidak ada jadwal mengajar hari ini.</div>
          <?php else: ?>
            <h5 class="mb-1">
              <?= esc((string) ($focusSchedule['nama_kelas'] ?? '-')) ?> ·
              <?= esc((string) ($focusSchedule['nama_mapel'] ?? '-')) ?>
            </h5>
            <div class="small text-muted">
              <?= esc((string) ($focusSchedule['jam_mulai'] ?? '')) ?> -
              <?= esc((string) ($focusSchedule['jam_selesai'] ?? '')) ?> ·
              <?= esc((string) ($focusSchedule['sesi'] ?? '-')) ?>
            </div>
          <?php endif; ?>
        </div>

        <?php if (is_array($focusSchedule)): ?>
          <?php
          [$focusPresensiLabel, $focusPresensiColor] = $presensiLabels[$focusSchedule['presensi_state'] ?? ''] ?? ['Tidak tersedia', 'secondary'];
          [$focusJurnalLabel, $focusJurnalColor] = $jurnalLabels[$focusSchedule['jurnal_state'] ?? ''] ?? ['Tidak tersedia', 'secondary'];
          ?>
          <div class="sisfour-mobile-actions flex-shrink-0">
            <?php if (! empty($focusSchedule['presensi_url'])): ?>
              <a class="btn btn-primary sisfour-touch-target"
                 href="<?= base_url((string) $focusSchedule['presensi_url']) ?>">
                <i class="bx bx-list-check me-1"></i>Isi Presensi Siswa
              </a>
            <?php else: ?>
              <span class="badge bg-label-<?= esc($focusPresensiColor) ?> py-2 px-3">
                Presensi: <?= esc($focusPresensiLabel) ?>
              </span>
            <?php endif; ?>

            <?php if (! empty($focusSchedule['jurnal_url'])): ?>
              <a class="btn btn-outline-primary sisfour-touch-target"
                 href="<?= base_url((string) $focusSchedule['jurnal_url']) ?>">
                <i class="bx bx-book-content me-1"></i>Isi Jurnal Mengajar
              </a>
            <?php else: ?>
              <span class="badge bg-label-<?= esc($focusJurnalColor) ?> py-2 px-3">
                Jurnal: <?= esc($focusJurnalLabel) ?>
              </span>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($secondaryGuruActions !== []): ?>
      <div class="row g-2">
        <?php foreach ($secondaryGuruActions as $action): ?>
          <div class="col-6 col-md-3">
            <a href="<?= base_url((string) ($action['url'] ?? '')) ?>"
               class="btn btn-outline-primary w-100 h-100 sisfour-touch-target justify-content-start text-start p-3">
              <span class="d-flex align-items-center gap-2 min-w-0">
                <i class="bx <?= esc((string) ($action['icon'] ?? 'bx-link')) ?> fs-4 flex-shrink-0"></i>
                <span class="min-w-0">
                  <strong class="d-block"><?= esc((string) ($action['label'] ?? '-')) ?></strong>
                  <small class="d-block text-muted text-wrap"><?= esc((string) ($action['description'] ?? '')) ?></small>
                </span>
              </span>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if (! empty($wali)): ?>
<div class="card mb-4 border border-primary">
  <div class="card-header d-flex justify-content-between align-items-start flex-wrap gap-2">
    <div>
      <h5 class="mb-1">Kelas Wali · <?= esc((string) ($wali['nama_kelas'] ?? '-')) ?></h5>
      <small class="text-muted"><?= (int) ($wali['jumlah_siswa'] ?? 0) ?> siswa aktif</small>
    </div>
    <?php if ($wali['ews_count'] !== null): ?>
      <span class="badge bg-label-danger">EWS <?= (int) $wali['ews_count'] ?> siswa</span>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <?php if ($rekapTotal === 0): ?>
      <div class="alert alert-secondary py-2 mb-3" role="status">Presensi Sesi Awal hari ini belum tersedia.</div>
    <?php endif; ?>

    <div class="sisfour-mobile-kpi-grid mb-3">
      <?php foreach ([
          ['Hadir', $rekap['hadir'] ?? 0, 'success'],
          ['Sakit', $rekap['sakit'] ?? 0, 'warning'],
          ['Izin', $rekap['izin'] ?? 0, 'info'],
          ['Alpha', $rekap['alpha'] ?? 0, 'danger'],
      ] as $item): ?>
        <div class="border rounded p-3 text-center min-w-0">
          <small class="text-muted d-block"><?= esc($item[0]) ?></small>
          <h4 class="text-<?= esc($item[2]) ?> mb-0"><?= (int) $item[1] ?></h4>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if (! empty($wali['quick_links'])): ?>
      <div class="mb-2 small fw-semibold">Akses Kelas</div>
      <div class="row g-2">
        <?php foreach ($wali['quick_links'] as $link): ?>
          <div class="col-6 col-md-auto">
            <a href="<?= base_url((string) ($link['url'] ?? '')) ?>"
               class="btn btn-sm btn-outline-primary w-100 sisfour-touch-target--compact text-wrap">
              <?= esc((string) ($link['label'] ?? '-')) ?>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-header sisfour-section-heading d-flex justify-content-between align-items-center gap-2">
    <h5 class="mb-0">Jadwal Hari Ini</h5>
    <span class="text-muted small"><?= count($jadwal) ?> jadwal</span>
  </div>

  <div class="d-md-none">
    <?php if ($jadwal === []): ?>
      <div class="sisfour-mobile-state text-muted">Tidak ada jadwal mengajar hari ini.</div>
    <?php else: ?>
      <div class="list-group list-group-flush">
        <?php foreach ($jadwal as $j): ?>
          <?php
          [$presensiLabel, $presensiColor] = $presensiLabels[$j['presensi_state'] ?? ''] ?? ['Tidak tersedia', 'secondary'];
          [$jurnalLabel, $jurnalColor] = $jurnalLabels[$j['jurnal_state'] ?? ''] ?? ['Tidak tersedia', 'secondary'];
          ?>
          <div class="list-group-item py-3">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
              <div class="sisfour-cell-primary">
                <span class="sisfour-cell-title"><?= esc((string) ($j['nama_kelas'] ?? '-')) ?> · <?= esc((string) ($j['nama_mapel'] ?? '-')) ?></span>
                <span class="sisfour-cell-meta"><?= esc((string) ($j['jam_mulai'] ?? '')) ?> - <?= esc((string) ($j['jam_selesai'] ?? '')) ?> · <?= esc((string) ($j['sesi'] ?? '-')) ?></span>
              </div>
              <span class="badge bg-label-secondary flex-shrink-0"><?= esc((string) ($j['kode_mapel'] ?? '-')) ?></span>
            </div>
            <div class="d-flex flex-wrap gap-2 mb-2">
              <span class="badge bg-label-<?= esc($presensiColor) ?>"><?= esc($presensiLabel) ?></span>
              <span class="badge bg-label-<?= esc($jurnalColor) ?>"><?= esc($jurnalLabel) ?></span>
            </div>
            <?php if (! empty($j['presensi_url']) || ! empty($j['jurnal_url'])): ?>
              <div class="sisfour-mobile-actions">
                <?php if (! empty($j['presensi_url'])): ?>
                  <a class="btn btn-sm btn-primary sisfour-touch-target--compact" href="<?= base_url((string) $j['presensi_url']) ?>">Presensi</a>
                <?php endif; ?>
                <?php if (! empty($j['jurnal_url'])): ?>
                  <a class="btn btn-sm btn-outline-primary sisfour-touch-target--compact" href="<?= base_url((string) $j['jurnal_url']) ?>">Jurnal</a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="d-none d-md-block table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr><th>Jam</th><th>Kelas / Mapel</th><th>Sesi</th><th>Presensi</th><th>Jurnal</th></tr></thead>
      <tbody>
      <?php if ($jadwal === []): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada jadwal mengajar hari ini.</td></tr>
      <?php else: foreach ($jadwal as $j): ?>
        <?php
        [$presensiLabel, $presensiColor] = $presensiLabels[$j['presensi_state'] ?? ''] ?? ['Tidak tersedia', 'secondary'];
        [$jurnalLabel, $jurnalColor] = $jurnalLabels[$j['jurnal_state'] ?? ''] ?? ['Tidak tersedia', 'secondary'];
        ?>
        <tr>
          <td class="text-nowrap"><?= esc((string) ($j['jam_mulai'] ?? '')) ?> - <?= esc((string) ($j['jam_selesai'] ?? '')) ?></td>
          <td><strong><?= esc((string) ($j['nama_kelas'] ?? '-')) ?></strong><div class="small text-muted"><?= esc((string) ($j['nama_mapel'] ?? '-')) ?></div></td>
          <td><span class="badge bg-label-secondary"><?= esc((string) ($j['sesi'] ?? '-')) ?></span></td>
          <td>
            <?php if (! empty($j['presensi_url'])): ?>
              <a class="btn btn-sm btn-outline-<?= esc($presensiColor) ?>" href="<?= base_url((string) $j['presensi_url']) ?>"><?= esc($presensiLabel) ?></a>
            <?php else: ?>
              <span class="badge bg-label-<?= esc($presensiColor) ?>"><?= esc($presensiLabel) ?></span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (! empty($j['jurnal_url'])): ?>
              <a class="btn btn-sm btn-outline-<?= esc($jurnalColor) ?>" href="<?= base_url((string) $j['jurnal_url']) ?>"><?= esc($jurnalLabel) ?></a>
            <?php else: ?>
              <span class="badge bg-label-<?= esc($jurnalColor) ?>"><?= esc($jurnalLabel) ?></span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (! empty($wali)): ?>
<div class="row g-3 mb-4">
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header sisfour-section-heading d-flex justify-content-between align-items-center gap-2">
        <h5 class="mb-0">EWS Kelas</h5>
        <?php if ($wali['ews_count'] !== null): ?><span class="badge bg-label-danger"><?= (int) $wali['ews_count'] ?></span><?php endif; ?>
      </div>
      <div class="list-group list-group-flush">
        <?php if (empty($wali['ews_top'])): ?>
          <div class="list-group-item sisfour-mobile-state text-muted">Tidak ada siswa EWS.</div>
        <?php else: foreach ($wali['ews_top'] as $row): ?>
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

  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header sisfour-section-heading"><h5 class="mb-0">Absence Terbaru</h5></div>
      <div class="list-group list-group-flush">
        <?php if (empty($wali['recent_absence'])): ?>
          <div class="list-group-item sisfour-mobile-state text-muted">Belum ada catatan Sakit/Izin/Alpha.</div>
        <?php else: foreach ($wali['recent_absence'] as $row): ?>
          <?php $status = (string) ($row['status'] ?? ''); $statusColor = $status === 'Alpha' ? 'danger' : ($status === 'Sakit' ? 'warning' : 'info'); ?>
          <div class="list-group-item d-flex justify-content-between align-items-start gap-3 py-3">
            <div class="sisfour-cell-primary">
              <span class="sisfour-cell-title"><?= esc((string) ($row['nama'] ?? '-')) ?></span>
              <span class="sisfour-cell-meta"><?= esc((string) ($row['tanggal'] ?? '-')) ?> · NISN <?= esc((string) ($row['nisn'] ?? '-')) ?></span>
            </div>
            <span class="badge bg-label-<?= esc($statusColor) ?> flex-shrink-0"><?= esc($status) ?></span>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header sisfour-section-heading"><h5 class="mb-0">Jurnal Terakhir</h5></div>
  <div class="list-group list-group-flush">
    <?php if (empty($widgets['riwayat_jurnal_terakhir'])): ?>
      <div class="list-group-item sisfour-mobile-state text-muted">Belum ada jurnal.</div>
    <?php else: foreach ($widgets['riwayat_jurnal_terakhir'] as $row): ?>
      <div class="list-group-item py-3">
        <div class="d-flex justify-content-between align-items-start gap-3">
          <div class="sisfour-cell-primary">
            <span class="sisfour-cell-title"><?= esc((string) ($row['nama_kelas'] ?? '-')) ?> · <?= esc((string) ($row['nama_mapel'] ?? '-')) ?></span>
            <span class="sisfour-cell-meta"><?= esc((string) ($row['tanggal'] ?? '-')) ?> · <?= esc((string) ($row['status'] ?? '-')) ?></span>
          </div>
          <span class="badge bg-label-secondary flex-shrink-0"><?= esc((string) ($row['status'] ?? '-')) ?></span>
        </div>
        <div class="small text-muted mt-2"><?= esc(mb_strimwidth((string) ($row['materi'] ?? ''), 0, 110, '...')) ?></div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<?= $this->endSection() ?>