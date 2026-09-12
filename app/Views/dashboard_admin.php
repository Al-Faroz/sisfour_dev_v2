<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php
$master = $widgets['master'] ?? [];
$presensi = $widgets['presensi_hari_ini'] ?? [];
$jurnal = $widgets['jurnal_hari_ini'] ?? [];
$kartu = $widgets['kartu'] ?? [];
$statusSistem = $widgets['status_sistem'] ?? [];
$tahun = $widgets['tahun_aktif'] ?? [];
?>

<div class="sisfour-dashboard">
  <div class="sf-dashboard-header">
    <div>
      <h4>Dashboard Admin</h4>
      <p class="text-muted">Ringkasan sistem dan operasional SisisFour.</p>
    </div>
    <span class="badge bg-label-primary sf-year-badge">
      <?= esc(($tahun['nama_tahun'] ?? 'Tahun belum aktif') . (!empty($tahun['semester']) ? ' · ' . $tahun['semester'] : '')) ?>
    </span>
  </div>

  <div class="sf-stat-grid">
    <?php foreach ([
        ['Siswa Aktif', $master['siswa'] ?? 0, 'primary'],
        ['Guru', $master['guru'] ?? 0, 'success'],
        ['Pegawai', $master['pegawai'] ?? 0, 'info'],
        ['Kelas Aktif', $master['kelas'] ?? 0, 'warning'],
    ] as $item): ?>
      <div class="sf-stat-card">
        <span class="sf-stat-card__label"><?= esc($item[0]) ?></span>
        <span class="sf-stat-card__value text-<?= esc($item[2]) ?>"><?= (int) $item[1] ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="sf-stat-grid">
    <?php foreach ([
        ['Kelas Belum Presensi', $presensi['belum_kelas'] ?? 0, 'danger'],
        ['Jadwal Belum Jurnal', $jurnal['belum'] ?? 0, 'warning'],
        ['EWS Alpha 14 Hari', $widgets['ews_count'] ?? 0, 'danger'],
        ['Kartu Aktif', $kartu['aktif'] ?? 0, 'success'],
    ] as $item): ?>
      <div class="sf-stat-card">
        <span class="sf-stat-card__label"><?= esc($item[0]) ?></span>
        <span class="sf-stat-card__value text-<?= esc($item[2]) ?>"><?= (int) $item[1] ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card sf-section-card">
    <div class="card-header d-flex justify-content-between align-items-center gap-2">
      <h5 class="mb-0">Presensi Siswa Hari Ini</h5>
      <small class="text-muted">Sesi Awal</small>
    </div>
    <div class="card-body">
      <div class="sf-attendance-strip">
        <?php foreach ([
            ['Hadir', $presensi['hadir'] ?? 0, 'success'],
            ['Sakit', $presensi['sakit'] ?? 0, 'warning'],
            ['Izin', $presensi['izin'] ?? 0, 'info'],
            ['Alpha', $presensi['alpha'] ?? 0, 'danger'],
        ] as $item): ?>
          <div class="sf-attendance-item">
            <small><?= esc($item[0]) ?></small>
            <strong class="text-<?= esc($item[2]) ?>"><?= (int) $item[1] ?></strong>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="sf-stat-grid">
    <div class="sf-stat-card">
      <span class="sf-stat-card__label">Kasus BK Bulan Ini</span>
      <span class="sf-stat-card__value"><?= (int) ($widgets['bk_bulan_ini'] ?? 0) ?></span>
    </div>
    <div class="sf-stat-card">
      <span class="sf-stat-card__label">Prestasi Bulan Ini</span>
      <span class="sf-stat-card__value text-success"><?= (int) ($widgets['prestasi_bulan_ini'] ?? 0) ?></span>
    </div>
    <div class="sf-stat-card">
      <span class="sf-stat-card__label">Maintenance</span>
      <span class="badge bg-label-<?= !empty($statusSistem['maintenance_mode']) ? 'danger' : 'success' ?> mt-1">
        <?= !empty($statusSistem['maintenance_mode']) ? 'ON' : 'OFF' ?>
      </span>
    </div>
    <div class="sf-stat-card">
      <span class="sf-stat-card__label">Geofencing</span>
      <span class="badge bg-label-<?= !empty($statusSistem['geofence_aktif']) ? 'primary' : 'secondary' ?> mt-1">
        <?= !empty($statusSistem['geofence_aktif']) ? 'ON' : 'OFF' ?>
      </span>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-5">
      <div class="card h-100 sf-section-card mb-0">
        <div class="card-header"><h5 class="mb-0">Tren Presensi 7 Hari</h5></div>
        <div class="table-responsive">
          <table class="table table-sm sf-compact-table mb-0">
            <thead><tr><th>Tanggal</th><th>% Hadir</th></tr></thead>
            <tbody>
              <?php foreach (($widgets['tren_presensi'] ?? []) as $row): ?>
                <tr><td><?= esc($row['tanggal']) ?></td><td><?= esc((string) $row['persen_hadir']) ?>%</td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card h-100 sf-section-card mb-0">
        <div class="card-header"><h5 class="mb-0">Aktivitas Terakhir</h5></div>
        <div class="table-responsive">
          <table class="table table-sm sf-compact-table mb-0">
            <thead><tr><th>Waktu</th><th>Modul</th><th>Aksi</th><th>Keterangan</th></tr></thead>
            <tbody>
              <?php if (empty($widgets['aktivitas_terakhir'])): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada aktivitas.</td></tr>
              <?php else: ?>
                <?php foreach ($widgets['aktivitas_terakhir'] as $log): ?>
                  <tr>
                    <td><?= esc($log['waktu']) ?></td>
                    <td><?= esc($log['modul']) ?></td>
                    <td><?= esc($log['aksi']) ?></td>
                    <td><?= esc($log['keterangan'] ?? '-') ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
