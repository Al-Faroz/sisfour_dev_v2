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

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-4">
  <div>
    <h4 class="mb-1">Dashboard Admin</h4>
    <p class="text-muted mb-0">Ringkasan sistem dan operasional SisisFour.</p>
  </div>
  <span class="badge bg-label-primary fs-6">
    <?= esc(($tahun['nama_tahun'] ?? 'Tahun belum aktif') . (!empty($tahun['semester']) ? ' · ' . $tahun['semester'] : '')) ?>
  </span>
</div>

<div class="row g-3 mb-4">
  <?php foreach ([
    ['Siswa Aktif', $master['siswa'] ?? 0, 'bx-group', 'primary'],
    ['Guru', $master['guru'] ?? 0, 'bx-chalkboard', 'success'],
    ['Pegawai', $master['pegawai'] ?? 0, 'bx-id-card', 'info'],
    ['Kelas Aktif', $master['kelas'] ?? 0, 'bx-door-open', 'warning'],
  ] as $card): ?>
    <div class="col-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <span class="avatar-initial rounded bg-label-<?= esc($card[3]) ?> p-2"><i class="bx <?= esc($card[2]) ?>"></i></span>
          <div><small class="text-muted d-block"><?= esc($card[0]) ?></small><h4 class="mb-0"><?= (int) $card[1] ?></h4></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card h-100"><div class="card-body">
      <small class="text-muted">Kelas Wajib Presensi</small>
      <h3 class="mb-1"><?= (int) ($presensi['wajib_kelas'] ?? 0) ?></h3>
      <div class="small"><span class="text-success">Sudah <?= (int) ($presensi['sudah_kelas'] ?? 0) ?></span> · <span class="text-danger">Belum <?= (int) ($presensi['belum_kelas'] ?? 0) ?></span></div>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card h-100"><div class="card-body">
      <small class="text-muted">Jadwal Wajib Jurnal</small>
      <h3 class="mb-1"><?= (int) ($jurnal['wajib'] ?? 0) ?></h3>
      <div class="small"><span class="text-success">Sudah <?= (int) ($jurnal['sudah'] ?? 0) ?></span> · <span class="text-danger">Belum <?= (int) ($jurnal['belum'] ?? 0) ?></span></div>
    </div></div>
  </div>
  <div class="col-md-4">
    <div class="card h-100"><div class="card-body">
      <small class="text-muted">EWS Alpha 14 Hari</small>
      <h3 class="mb-1 text-danger"><?= (int) ($widgets['ews_count'] ?? 0) ?></h3>
      <small class="text-muted">Siswa dengan ≥3 Alpha, Sesi Awal.</small>
    </div></div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header"><h5 class="mb-0">Presensi Siswa Hari Ini — Sesi Awal</h5></div>
  <div class="card-body">
    <div class="row g-3 text-center">
      <?php foreach ([
        ['Hadir', $presensi['hadir'] ?? 0, 'success'],
        ['Sakit', $presensi['sakit'] ?? 0, 'warning'],
        ['Izin', $presensi['izin'] ?? 0, 'info'],
        ['Alpha', $presensi['alpha'] ?? 0, 'danger'],
      ] as $item): ?>
        <div class="col-6 col-md-3"><div class="border rounded p-3"><small class="text-muted d-block"><?= esc($item[0]) ?></small><h4 class="text-<?= esc($item[2]) ?> mb-0"><?= (int) $item[1] ?></h4></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><small class="text-muted">Kasus BK Bulan Ini</small><h4 class="mb-0"><?= (int) ($widgets['bk_bulan_ini'] ?? 0) ?></h4></div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><small class="text-muted">Prestasi Bulan Ini</small><h4 class="mb-0"><?= (int) ($widgets['prestasi_bulan_ini'] ?? 0) ?></h4></div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><small class="text-muted">Kartu Aktif</small><h4 class="mb-0"><?= (int) ($kartu['aktif'] ?? 0) ?></h4></div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><small class="text-muted">Status Sistem</small><div class="mt-2"><span class="badge bg-label-<?= !empty($statusSistem['maintenance_mode']) ? 'danger' : 'success' ?>">Maintenance <?= !empty($statusSistem['maintenance_mode']) ? 'ON' : 'OFF' ?></span> <span class="badge bg-label-<?= !empty($statusSistem['geofence_aktif']) ? 'primary' : 'secondary' ?>">Geo <?= !empty($statusSistem['geofence_aktif']) ? 'ON' : 'OFF' ?></span></div></div></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0">Tren Presensi 7 Hari</h5></div>
      <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Tanggal</th><th>% Hadir</th></tr></thead><tbody>
        <?php foreach (($widgets['tren_presensi'] ?? []) as $row): ?><tr><td><?= esc($row['tanggal']) ?></td><td><?= esc((string) $row['persen_hadir']) ?>%</td></tr><?php endforeach; ?>
      </tbody></table></div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header"><h5 class="mb-0">Aktivitas Terakhir</h5></div>
      <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Waktu</th><th>Modul</th><th>Aksi</th><th>Keterangan</th></tr></thead><tbody>
      <?php if (empty($widgets['aktivitas_terakhir'])): ?><tr><td colspan="4" class="text-center text-muted py-4">Belum ada aktivitas.</td></tr>
      <?php else: foreach ($widgets['aktivitas_terakhir'] as $log): ?><tr><td><?= esc($log['waktu']) ?></td><td><?= esc($log['modul']) ?></td><td><?= esc($log['aksi']) ?></td><td><?= esc($log['keterangan'] ?? '-') ?></td></tr><?php endforeach; endif; ?>
      </tbody></table></div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
