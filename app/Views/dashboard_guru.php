<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php
$task = $widgets['task_summary'] ?? [];
$jadwal = $widgets['jadwal_hari_ini'] ?? [];
$quickActions = $widgets['quick_actions'] ?? [];
$nextSchedule = $widgets['next_schedule'] ?? null;

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
?>

<div class="sisfour-page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
  <div class="sisfour-page-header__copy">
    <h4 class="mb-1">Dashboard Guru</h4>
    <p class="text-muted mb-0">Tugas mengajar dan action yang perlu diperhatikan hari ini.</p>
  </div>
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

<?php if ($quickActions !== []): ?>
<div class="card mb-4">
  <div class="card-header sisfour-section-heading d-flex align-items-center justify-content-between gap-2">
    <h5 class="mb-0">Quick Action</h5>
    <?php if ((int) ($task['actionable_now'] ?? 0) > 0): ?>
      <span class="badge bg-label-primary"><?= (int) $task['actionable_now'] ?> bisa dikerjakan sekarang</span>
    <?php endif; ?>
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
                <strong class="d-block"><?= esc((string) ($action['label'] ?? '-')) ?></strong>
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

<?php if (is_array($nextSchedule)): ?>
<div class="card mb-4 border border-primary">
  <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
    <div class="min-w-0">
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="badge bg-label-primary">
          <?= ($nextSchedule['dashboard_state'] ?? '') === 'berlangsung' ? 'Sedang Berlangsung' : 'Jadwal Berikutnya' ?>
        </span>
        <small class="text-muted"><?= esc((string) ($nextSchedule['jam_mulai'] ?? '')) ?> - <?= esc((string) ($nextSchedule['jam_selesai'] ?? '')) ?></small>
      </div>
      <h5 class="mb-1"><?= esc((string) ($nextSchedule['nama_kelas'] ?? '-')) ?> · <?= esc((string) ($nextSchedule['nama_mapel'] ?? '-')) ?></h5>
      <div class="small text-muted"><?= esc((string) ($nextSchedule['sesi'] ?? '-')) ?></div>
    </div>
    <div class="sisfour-mobile-actions flex-shrink-0">
      <?php if (! empty($nextSchedule['presensi_url'])): ?>
        <a class="btn btn-primary sisfour-touch-target" href="<?= base_url((string) $nextSchedule['presensi_url']) ?>">Isi Presensi</a>
      <?php endif; ?>
      <?php if (! empty($nextSchedule['jurnal_url'])): ?>
        <a class="btn btn-outline-primary sisfour-touch-target" href="<?= base_url((string) $nextSchedule['jurnal_url']) ?>">Isi Jurnal</a>
      <?php endif; ?>
    </div>
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
      <thead>
        <tr><th>Jam</th><th>Kelas / Mapel</th><th>Sesi</th><th>Presensi</th><th>Jurnal</th></tr>
      </thead>
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
