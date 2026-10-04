<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php
$task = $widgets['task_summary'] ?? [];
$jadwal = $widgets['jadwal_hari_ini'] ?? [];
$quickActions = $widgets['quick_actions'] ?? [];
$nextSchedule = $widgets['next_schedule'] ?? null;

$focusState = is_array($nextSchedule)
    ? (string) ($nextSchedule['dashboard_state'] ?? '')
    : '';
$focusSurfaceClass = match ($focusState) {
    'berlangsung' => 'sisfour-work-surface--active',
    'berikutnya' => 'sisfour-work-surface--upcoming',
    default => 'sisfour-work-surface--inactive',
};
$focusBadgeColor = match ($focusState) {
    'berlangsung' => 'primary',
    'berikutnya' => 'info',
    default => 'secondary',
};

$presensiLabels = [
    'not_applicable' => ['Tidak berlaku', 'secondary', 'is-disabled'],
    'submitted' => ['Presensi selesai', 'success', 'is-completed'],
    'not_started' => ['Belum waktunya', 'secondary', 'is-disabled'],
    'available' => ['Isi Presensi', 'primary', 'sisfour-action--blue'],
    'wali_available' => ['Isi sebagai Wali', 'warning', 'sisfour-action--blue is-late'],
    'ended' => ['Waktu habis', 'secondary', 'is-disabled'],
];
$jurnalLabels = [
    'submitted' => ['Jurnal selesai', 'success', 'is-completed'],
    'not_started' => ['Belum waktunya', 'secondary', 'is-disabled'],
    'available' => ['Isi Jurnal', 'primary', 'sisfour-action--violet'],
    'ended' => ['Terlewat', 'secondary', 'is-disabled'],
];

$secondaryGuruActions = array_values(array_filter(
    $quickActions,
    static fn (array $action): bool => ! in_array(
        (string) ($action['label'] ?? ''),
        ['Presensi', 'Jurnal'],
        true
    )
));

$actionTone = static function (string $label): string {
    return match (strtolower(trim($label))) {
        'jadwal' => 'sisfour-action--indigo',
        'profil' => 'sisfour-action--slate',
        default => 'sisfour-action--indigo',
    };
};
?>

<div class="sisfour-page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
  <div class="sisfour-page-header__copy">
    <h4 class="mb-1">Dasbor Guru</h4>
    <p class="text-muted mb-0">Tugas mengajar dan tindakan yang perlu diperhatikan hari ini.</p>
  </div>
</div>

<?php if (is_array($nextSchedule)): ?>
<div class="sisfour-dashboard-heading">
  <h5 class="mb-0">Hari Ini</h5>
  <?php if ((int) ($task['actionable_now'] ?? 0) > 0): ?>
    <span class="badge bg-label-primary"><?= (int) $task['actionable_now'] ?> bisa dikerjakan sekarang</span>
  <?php endif; ?>
</div>
<div class="card sisfour-work-surface <?= esc($focusSurfaceClass, 'attr') ?> mb-4">
  <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
    <div class="min-w-0">
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="badge bg-label-<?= esc($focusBadgeColor, 'attr') ?>">
          <?= ($nextSchedule['dashboard_state'] ?? '') === 'berlangsung' ? 'Sedang Berlangsung' : 'Jadwal Berikutnya' ?>
        </span>
        <small class="text-muted"><?= esc((string) ($nextSchedule['jam_mulai'] ?? '')) ?> - <?= esc((string) ($nextSchedule['jam_selesai'] ?? '')) ?></small>
      </div>
      <h5 class="mb-1"><?= esc((string) ($nextSchedule['nama_kelas'] ?? '-')) ?> · <?= esc((string) ($nextSchedule['nama_mapel'] ?? '-')) ?></h5>
      <div class="small text-muted"><?= esc((string) ($nextSchedule['sesi'] ?? '-')) ?></div>
    </div>
    <div class="sisfour-mobile-actions flex-shrink-0">
      <?php if (! empty($nextSchedule['presensi_url'])): ?>
        <a class="btn sisfour-action sisfour-action--blue sisfour-action--strong is-current sisfour-touch-target" href="<?= base_url((string) $nextSchedule['presensi_url']) ?>"><i class="bx bx-list-check"></i>Isi Presensi</a>
      <?php endif; ?>
      <?php if (! empty($nextSchedule['jurnal_url'])): ?>
        <a class="btn sisfour-action sisfour-action--violet sisfour-action--strong is-current sisfour-touch-target" href="<?= base_url((string) $nextSchedule['jurnal_url']) ?>"><i class="bx bx-book-content"></i>Isi Jurnal</a>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<?= $this->include('_dashboard_secondary_actions') ?>

<div class="sisfour-dashboard-heading">
  <h5 class="mb-0">Ringkasan Hari Ini</h5>
  <small class="text-muted">Status tugas mengajar</small>
</div>
<div class="sisfour-metric-grid mb-4">
  <?php foreach ([
      ['Jadwal Hari Ini', $task['jadwal'] ?? 0, 'indigo', 'bx-calendar'],
      ['Belum Presensi', $task['belum_presensi'] ?? ($task['presensi_perlu'] ?? 0), 'amber', 'bx-list-check'],
      ['Belum Jurnal', $task['belum_jurnal'] ?? ($task['jurnal_perlu'] ?? 0), 'violet', 'bx-book-content'],
      ['Selesai', $task['selesai'] ?? ($task['jurnal_selesai'] ?? 0), 'green', 'bx-check-circle'],
  ] as $item): ?>
    <div class="sisfour-metric-tile sisfour-metric-tile--compact sisfour-metric-tile--<?= esc($item[2]) ?>">
      <span class="sisfour-metric-tile__icon"><i class="bx <?= esc($item[3]) ?>"></i></span>
      <strong class="sisfour-metric-tile__value"><?= (int) $item[1] ?></strong>
      <span class="sisfour-metric-tile__label"><?= esc($item[0]) ?></span>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($secondaryGuruActions !== []): ?>
<div class="sisfour-dashboard-heading">
  <h5 class="mb-0">Akses Guru</h5>
</div>
<div class="row g-2 mb-4">
  <?php foreach ($secondaryGuruActions as $action): ?>
    <div class="col-6 col-md-3">
      <a href="<?= base_url((string) ($action['url'] ?? '')) ?>"
         class="btn sisfour-action sisfour-action--tile <?= esc($actionTone((string) ($action['label'] ?? ''))) ?> w-100 h-100 sisfour-touch-target">
        <span class="d-flex align-items-center gap-2 min-w-0">
          <i class="bx <?= esc((string) ($action['icon'] ?? 'bx-link')) ?> fs-4 flex-shrink-0"></i>
          <span class="min-w-0">
            <strong class="d-block"><?= esc((string) ($action['label'] ?? '-')) ?></strong>
            <small class="d-block opacity-75 text-wrap"><?= esc((string) ($action['description'] ?? '')) ?></small>
          </span>
        </span>
      </a>
    </div>
  <?php endforeach; ?>
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
      <div class="list-group list-group-flush sisfour-schedule-list">
        <?php foreach ($jadwal as $j): ?>
          <?php
          [$presensiLabel, $presensiColor, $presensiActionClass] = $presensiLabels[$j['presensi_state'] ?? ''] ?? ['Tidak tersedia', 'secondary', 'is-disabled'];
          [$jurnalLabel, $jurnalColor, $jurnalActionClass] = $jurnalLabels[$j['jurnal_state'] ?? ''] ?? ['Tidak tersedia', 'secondary', 'is-disabled'];
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
                  <a class="btn sisfour-action sisfour-action--blue sisfour-action--compact sisfour-touch-target--compact" href="<?= base_url((string) $j['presensi_url']) ?>">Presensi</a>
                <?php endif; ?>
                <?php if (! empty($j['jurnal_url'])): ?>
                  <a class="btn sisfour-action sisfour-action--violet sisfour-action--compact sisfour-touch-target--compact" href="<?= base_url((string) $j['jurnal_url']) ?>">Jurnal</a>
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
      <thead>
        <tr><th>Jam</th><th>Kelas / Mapel</th><th>Sesi</th><th>Presensi</th><th>Jurnal</th></tr>
      </thead>
      <tbody>
      <?php if ($jadwal === []): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada jadwal mengajar hari ini.</td></tr>
      <?php else: foreach ($jadwal as $j): ?>
        <?php
        [$presensiLabel, $presensiColor, $presensiActionClass] = $presensiLabels[$j['presensi_state'] ?? ''] ?? ['Tidak tersedia', 'secondary', 'is-disabled'];
        [$jurnalLabel, $jurnalColor, $jurnalActionClass] = $jurnalLabels[$j['jurnal_state'] ?? ''] ?? ['Tidak tersedia', 'secondary', 'is-disabled'];
        ?>
        <tr>
          <td class="text-nowrap"><?= esc((string) ($j['jam_mulai'] ?? '')) ?> - <?= esc((string) ($j['jam_selesai'] ?? '')) ?></td>
          <td><strong><?= esc((string) ($j['nama_kelas'] ?? '-')) ?></strong><div class="small text-muted"><?= esc((string) ($j['nama_mapel'] ?? '-')) ?></div></td>
          <td><span class="badge bg-label-secondary"><?= esc((string) ($j['sesi'] ?? '-')) ?></span></td>
          <td>
            <?php if (! empty($j['presensi_url'])): ?>
              <a class="btn sisfour-action sisfour-action--compact <?= esc($presensiActionClass, 'attr') ?>" href="<?= base_url((string) $j['presensi_url']) ?>"><?= esc($presensiLabel) ?></a>
            <?php else: ?>
              <span class="badge bg-label-<?= esc($presensiColor) ?>"><?= esc($presensiLabel) ?></span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (! empty($j['jurnal_url'])): ?>
              <a class="btn sisfour-action sisfour-action--compact <?= esc($jurnalActionClass, 'attr') ?>" href="<?= base_url((string) $j['jurnal_url']) ?>"><?= esc($jurnalLabel) ?></a>
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
