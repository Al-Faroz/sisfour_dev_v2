<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php
$task = $widgets['task_summary'] ?? [];
$wali = $widgets['wali'] ?? [];
$rekap = $wali['presensi_hari_ini'] ?? [];
$tahun = $widgets['tahun_aktif'] ?? [];
$jadwalHariIni = $widgets['jadwal_hari_ini'] ?? [];
$riwayatJurnal = $widgets['riwayat_jurnal_terakhir'] ?? [];
$primaryAction = $wali['primary_action'] ?? null;
$jumlahSiswa = (int) ($wali['jumlah_siswa'] ?? 0);
$jumlahTercatat = (int) ($rekap['jumlah_tercatat'] ?? 0);
$presensiTersedia = !empty($rekap['tersedia']);
$presensiLengkap = !empty($rekap['lengkap']);

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
      <h4>Dashboard Guru & Wali Kelas</h4>
      <p class="text-muted">Pekerjaan mengajar dan kondisi kelas wali hari ini.</p>
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

  <?php if (!empty($wali)): ?>
    <div class="card sf-section-card sf-wali-card">
      <div class="card-header d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
          <h5 class="mb-1">Presensi Kelas Wali · <?= esc($wali['nama_kelas'] ?? '-') ?></h5>
          <div class="sf-wali-meta">
            <span><i class="bx bx-group me-1"></i><?= $jumlahSiswa ?> siswa aktif</span>
            <span><i class="bx bx-calendar me-1"></i>Sesi Awal hari ini</span>
          </div>
        </div>

        <?php if (($wali['ews_count'] ?? null) !== null): ?>
          <span class="badge bg-label-danger">EWS <?= (int) $wali['ews_count'] ?> siswa</span>
        <?php endif; ?>
      </div>

      <div class="card-body">
        <div class="sf-attendance-strip">
          <?php foreach ([
              ['Hadir', $rekap['hadir'] ?? 0, 'success'],
              ['Sakit', $rekap['sakit'] ?? 0, 'warning'],
              ['Izin', $rekap['izin'] ?? 0, 'info'],
              ['Alpha', $rekap['alpha'] ?? 0, 'danger'],
          ] as $item): ?>
            <div class="sf-attendance-item">
              <small><?= esc($item[0]) ?></small>
              <strong class="text-<?= esc($item[2]) ?>"><?= (int) $item[1] ?></strong>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="sf-status-line">
          <?php if (!$presensiTersedia): ?>
            <span class="badge bg-label-warning">Belum diinput</span>
            <span class="text-muted">Belum ada Presensi Sesi Awal kelas wali hari ini.</span>
          <?php elseif ($presensiLengkap): ?>
            <span class="badge bg-label-success">Lengkap</span>
            <span class="text-muted"><?= $jumlahTercatat ?>/<?= $jumlahSiswa ?> siswa tercatat.</span>
          <?php else: ?>
            <span class="badge bg-label-warning">Belum lengkap</span>
            <span class="text-muted"><?= $jumlahTercatat ?>/<?= $jumlahSiswa ?> siswa tercatat.</span>
          <?php endif; ?>
        </div>

        <?php if (!empty($primaryAction) || !empty($wali['quick_links'])): ?>
          <div class="sf-primary-action">
            <div class="flex-grow-1">
              <?php if (!empty($wali['quick_links'])): ?>
                <div class="sf-action-grid">
                  <?php foreach ($wali['quick_links'] as $link): ?>
                    <a href="<?= base_url($link['url']) ?>" class="sf-action-tile">
                      <i class="bx <?= esc($link['icon'] ?? 'bx-link-external') ?>"></i>
                      <span><?= esc($link['label']) ?></span>
                    </a>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <?php if (!empty($primaryAction)): ?>
              <a href="<?= base_url($primaryAction['url']) ?>" class="btn btn-primary btn-sm flex-shrink-0">
                <i class="bx <?= esc($primaryAction['icon'] ?? 'bx-check-square') ?> me-1"></i>
                <?= esc($primaryAction['label']) ?>
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="card sf-section-card">
    <div class="card-header d-flex justify-content-between align-items-center gap-2">
      <h5 class="mb-0">Jadwal & Tugas Mengajar Hari Ini</h5>
      <small class="text-muted d-none d-sm-inline">Context Wali dipisahkan dari tugas mengajar</small>
    </div>

    <?php if (empty($jadwalHariIni)): ?>
      <div class="card-body text-center text-muted py-4">Tidak ada jadwal mengajar hari ini.</div>
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

  <?php if (!empty($wali)): ?>
    <div class="row g-3 mb-3">
      <?php if (($wali['ews_count'] ?? null) !== null): ?>
        <div class="col-lg-5">
          <div class="card h-100 sf-section-card mb-0">
            <div class="card-header"><h5 class="mb-0">EWS Kelas</h5></div>
            <div class="table-responsive">
              <table class="table table-sm sf-compact-table mb-0">
                <thead><tr><th>Nama</th><th>Alpha</th></tr></thead>
                <tbody>
                  <?php if (empty($wali['ews_top'])): ?>
                    <tr><td colspan="2" class="text-center text-muted py-4">Tidak ada siswa EWS.</td></tr>
                  <?php else: ?>
                    <?php foreach ($wali['ews_top'] as $row): ?>
                      <tr>
                        <td><?= esc($row['nama']) ?></td>
                        <td><span class="badge bg-label-danger"><?= (int) $row['total_alpha'] ?></span></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="<?= ($wali['ews_count'] ?? null) !== null ? 'col-lg-7' : 'col-12' ?>">
        <div class="card h-100 sf-section-card mb-0">
          <div class="card-header"><h5 class="mb-0">Sakit / Izin / Alpha Terbaru</h5></div>
          <div class="table-responsive">
            <table class="table table-sm sf-compact-table mb-0">
              <thead><tr><th>Tanggal</th><th>Nama</th><th>Status</th></tr></thead>
              <tbody>
                <?php if (empty($wali['recent_absence'])): ?>
                  <tr><td colspan="3" class="text-center text-muted py-4">Belum ada catatan.</td></tr>
                <?php else: ?>
                  <?php foreach ($wali['recent_absence'] as $row): ?>
                    <?php $statusColor = $row['status'] === 'Alpha' ? 'danger' : ($row['status'] === 'Sakit' ? 'warning' : 'info'); ?>
                    <tr>
                      <td><?= esc($row['tanggal']) ?></td>
                      <td><?= esc($row['nama']) ?></td>
                      <td><span class="badge bg-label-<?= esc($statusColor) ?>"><?= esc($row['status']) ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

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
            <strong><?= esc($row['tanggal']) ?> · <?= esc($row['nama_kelas'] ?? '-') ?></strong>
            <div class="small text-muted">
              <?= esc($row['nama_mapel'] ?? '-') ?> · <?= esc($row['status']) ?> · <?= esc(mb_strimwidth((string) ($row['materi'] ?? ''), 0, 70, '...')) ?>
            </div>
          </li>
        <?php endforeach; ?>
      <?php endif; ?>
    </ul>
  </div>
</div>

<?= $this->endSection() ?>
