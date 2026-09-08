<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php
$master = $widgets['master'] ?? [];
$presensi = $widgets['presensi_hari_ini'] ?? [];
$jurnal = $widgets['jurnal_hari_ini'] ?? [];
$kartu = $widgets['kartu'] ?? [];
$tahun = $widgets['tahun_aktif'] ?? [];
?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
  <div><h4 class="mb-1">Dashboard Operator</h4><p class="text-muted mb-0">Prioritas operasional harian madrasah.</p></div>
  <span class="badge bg-label-primary fs-6"><?= esc(($tahun['nama_tahun'] ?? 'Tahun belum aktif') . (!empty($tahun['semester']) ? ' · ' . $tahun['semester'] : '')) ?></span>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="card border-start border-danger border-3"><div class="card-body"><small class="text-muted">Kelas Belum Presensi</small><h2 class="text-danger mb-0"><?= (int) ($presensi['belum_kelas'] ?? 0) ?></h2><small>dari <?= (int) ($presensi['wajib_kelas'] ?? 0) ?> kelas wajib</small></div></div></div>
  <div class="col-md-4"><div class="card border-start border-warning border-3"><div class="card-body"><small class="text-muted">Jadwal Belum Jurnal</small><h2 class="text-warning mb-0"><?= (int) ($jurnal['belum'] ?? 0) ?></h2><small>dari <?= (int) ($jurnal['wajib'] ?? 0) ?> jadwal aktif</small></div></div></div>
  <div class="col-md-4"><div class="card border-start border-danger border-3"><div class="card-body"><small class="text-muted">EWS Alpha 14 Hari</small><h2 class="text-danger mb-0"><?= (int) ($widgets['ews_count'] ?? 0) ?></h2><small>siswa perlu perhatian</small></div></div></div>
</div>

<div class="row g-3 mb-4">
  <?php foreach ([['Siswa', $master['siswa'] ?? 0], ['Guru', $master['guru'] ?? 0], ['Pegawai', $master['pegawai'] ?? 0], ['Kelas', $master['kelas'] ?? 0]] as $item): ?>
    <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body"><small class="text-muted"><?= esc($item[0]) ?></small><h4 class="mb-0"><?= (int) $item[1] ?></h4></div></div></div>
  <?php endforeach; ?>
</div>

<div class="card mb-4">
  <div class="card-header"><h5 class="mb-0">Presensi Hari Ini</h5></div>
  <div class="card-body"><div class="row g-3 text-center">
    <?php foreach ([['Hadir',$presensi['hadir']??0,'success'],['Sakit',$presensi['sakit']??0,'warning'],['Izin',$presensi['izin']??0,'info'],['Alpha',$presensi['alpha']??0,'danger']] as $item): ?>
      <div class="col-6 col-md-3"><div class="border rounded p-3"><small class="text-muted d-block"><?= esc($item[0]) ?></small><h4 class="text-<?= esc($item[2]) ?> mb-0"><?= (int) $item[1] ?></h4></div></div>
    <?php endforeach; ?>
  </div></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Kasus BK Bulan Ini</small><h4 class="mb-0"><?= (int) ($widgets['bk_bulan_ini'] ?? 0) ?></h4></div></div></div>
  <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Prestasi Bulan Ini</small><h4 class="mb-0"><?= (int) ($widgets['prestasi_bulan_ini'] ?? 0) ?></h4></div></div></div>
  <div class="col-md-4"><div class="card"><div class="card-body"><small class="text-muted">Kartu Aktif</small><h4 class="mb-0"><?= (int) ($kartu['aktif'] ?? 0) ?></h4></div></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-5"><div class="card h-100"><div class="card-header"><h5 class="mb-0">Tren Presensi 7 Hari</h5></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Tanggal</th><th>% Hadir</th></tr></thead><tbody><?php foreach (($widgets['tren_presensi'] ?? []) as $row): ?><tr><td><?= esc($row['tanggal']) ?></td><td><?= esc((string)$row['persen_hadir']) ?>%</td></tr><?php endforeach; ?></tbody></table></div></div></div>
  <div class="col-lg-7"><div class="card h-100"><div class="card-header"><h5 class="mb-0">Aktivitas Terakhir</h5></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Waktu</th><th>Modul</th><th>Aksi</th></tr></thead><tbody><?php if(empty($widgets['aktivitas_terakhir'])): ?><tr><td colspan="3" class="text-center text-muted py-4">Belum ada aktivitas.</td></tr><?php else: foreach($widgets['aktivitas_terakhir'] as $log): ?><tr><td><?= esc($log['waktu']) ?></td><td><?= esc($log['modul']) ?></td><td><?= esc($log['aksi']) ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div></div>
</div>

<?= $this->endSection() ?>
