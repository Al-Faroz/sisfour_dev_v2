<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php
$master = $widgets['master'] ?? [];
$presensi = $widgets['presensi_hari_ini'] ?? [];
$jurnal = $widgets['jurnal_hari_ini'] ?? [];
$kartu = $widgets['kartu'] ?? [];
?>

<h4 class="mb-1">Dashboard Pimpinan</h4>
<p class="text-muted mb-4">Ringkasan supervisi readonly. Tidak ada aksi perubahan data dari dashboard.</p>

<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><small class="text-muted">Kelas Belum Presensi</small><h3 class="text-warning mb-0"><?= (int) ($presensi['belum_kelas'] ?? 0) ?></h3><small>dari <?= (int) ($presensi['wajib_kelas'] ?? 0) ?> kelas wajib</small></div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><small class="text-muted">Jadwal Belum Jurnal</small><h3 class="text-warning mb-0"><?= (int) ($jurnal['belum'] ?? 0) ?></h3><small>dari <?= (int) ($jurnal['wajib'] ?? 0) ?> jadwal</small></div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><small class="text-muted">EWS 14 Hari</small><h3 class="text-danger mb-0"><?= (int) ($widgets['ews_count'] ?? 0) ?></h3><small>siswa</small></div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><small class="text-muted">Kasus BK Bulan Ini</small><h3 class="mb-0"><?= (int) ($widgets['kasus_bulan_ini'] ?? 0) ?></h3></div></div></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h5 class="mb-0">Tren Presensi 7 Hari</h5></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Tanggal</th><th>% Hadir</th></tr></thead><tbody><?php foreach(($widgets['tren_presensi']??[]) as $row): ?><tr><td><?= esc($row['tanggal']) ?></td><td><?= esc((string)$row['persen_hadir']) ?>%</td></tr><?php endforeach; ?></tbody></table></div></div></div>
  <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h5 class="mb-0">EWS Alpha Teratas</h5></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Nama</th><th>Alpha</th></tr></thead><tbody><?php if(empty($widgets['ews_top'])): ?><tr><td colspan="2" class="text-center text-muted py-4">Tidak ada siswa EWS.</td></tr><?php else: foreach($widgets['ews_top'] as $row): ?><tr><td><?= esc($row['nama']) ?></td><td><span class="badge bg-label-danger"><?= (int)$row['total_alpha'] ?></span></td></tr><?php endforeach; endif; ?></tbody></table></div></div></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-7"><div class="card h-100"><div class="card-header"><h5 class="mb-0">Top 20 Poin Pelanggaran</h5></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>#</th><th>Nama</th><th>Poin</th></tr></thead><tbody><?php if(empty($widgets['top20_pelanggaran'])): ?><tr><td colspan="3" class="text-center text-muted py-4">Belum ada data.</td></tr><?php else: foreach($widgets['top20_pelanggaran'] as $i=>$row): ?><tr><td><?= $i+1 ?></td><td><?= esc($row['nama']) ?></td><td><?= (int)$row['total_poin'] ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div></div>
  <div class="col-lg-5"><div class="card h-100"><div class="card-header"><h5 class="mb-0">Prestasi Terbaru</h5></div><ul class="list-group list-group-flush"><?php if(empty($widgets['prestasi_terbaru'])): ?><li class="list-group-item text-muted text-center py-4">Belum ada data.</li><?php else: foreach($widgets['prestasi_terbaru'] as $row): ?><li class="list-group-item"><strong><?= esc($row['nama']) ?></strong><div><?= esc($row['nama_prestasi']) ?></div><small class="text-muted"><?= esc($row['tanggal']) ?> · <?= esc($row['tingkat'] ?? '-') ?></small></li><?php endforeach; endif; ?></ul></div></div>
</div>

<div class="row g-3">
  <?php foreach([['Siswa Aktif',$master['siswa']??0],['Guru',$master['guru']??0],['Pegawai',$master['pegawai']??0],['Kelas',$master['kelas']??0],['Kartu Aktif',$kartu['aktif']??0]] as $item): ?>
    <div class="col-6 col-md"><div class="card"><div class="card-body"><small class="text-muted"><?= esc($item[0]) ?></small><h5 class="mb-0"><?= (int)$item[1] ?></h5></div></div></div>
  <?php endforeach; ?>
</div>

<?= $this->endSection() ?>
