<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<h4 class="mb-1">Dashboard BK</h4>
<p class="text-muted mb-4">Prioritas kasus, EWS Presensi, pelanggaran, dan prestasi.</p>

<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><small class="text-muted">Kasus Bulan Ini</small><h3 class="mb-0"><?= (int)($widgets['kasus_bulan_ini']??0) ?></h3></div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><small class="text-muted">Pelanggaran Berat</small><h3 class="text-danger mb-0"><?= (int)($widgets['pelanggaran_berat_bulan_ini']??0) ?></h3></div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><small class="text-muted">EWS Alpha 14 Hari</small><h3 class="text-danger mb-0"><?= (int)($widgets['ews_count']??0) ?></h3></div></div></div>
  <div class="col-md-3"><div class="card h-100"><div class="card-body"><small class="text-muted">Prestasi Bulan Ini</small><h3 class="text-success mb-0"><?= (int)($widgets['prestasi_bulan_ini']??0) ?></h3></div></div></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-5"><div class="card h-100"><div class="card-header"><h5 class="mb-0">EWS Alpha Teratas</h5></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Nama</th><th>Alpha</th></tr></thead><tbody><?php if(empty($widgets['ews_top'])): ?><tr><td colspan="2" class="text-center text-muted py-4">Tidak ada siswa EWS.</td></tr><?php else: foreach($widgets['ews_top'] as $row): ?><tr><td><?= esc($row['nama']) ?></td><td><span class="badge bg-label-danger"><?= (int)$row['total_alpha'] ?></span></td></tr><?php endforeach; endif; ?></tbody></table></div></div></div>
  <div class="col-lg-7"><div class="card h-100"><div class="card-header"><h5 class="mb-0">Top 20 Poin Pelanggaran</h5></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>#</th><th>Nama</th><th>Poin</th></tr></thead><tbody><?php if(empty($widgets['top20_pelanggaran'])): ?><tr><td colspan="3" class="text-center text-muted py-4">Belum ada data.</td></tr><?php else: foreach($widgets['top20_pelanggaran'] as $i=>$row): ?><tr><td><?= $i+1 ?></td><td><?= esc($row['nama']) ?></td><td><?= (int)$row['total_poin'] ?></td></tr><?php endforeach; endif; ?></tbody></table></div></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h5 class="mb-0">Kasus Terbaru</h5></div><ul class="list-group list-group-flush"><?php if(empty($widgets['kasus_terbaru'])): ?><li class="list-group-item text-muted text-center py-4">Belum ada kasus.</li><?php else: foreach($widgets['kasus_terbaru'] as $row): ?><li class="list-group-item"><strong><?= esc($row['nama']) ?></strong> — <?= esc($row['nama_pelanggaran']) ?><div><span class="badge bg-label-<?= ($row['kategori']??'')==='Berat'?'danger':(($row['kategori']??'')==='Sedang'?'warning':'secondary') ?>"><?= esc($row['kategori']) ?></span> <small class="text-muted"><?= esc($row['tanggal']) ?></small></div></li><?php endforeach; endif; ?></ul></div></div>
  <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h5 class="mb-0">Prestasi Terbaru</h5></div><ul class="list-group list-group-flush"><?php if(empty($widgets['prestasi_terbaru'])): ?><li class="list-group-item text-muted text-center py-4">Belum ada prestasi.</li><?php else: foreach($widgets['prestasi_terbaru'] as $row): ?><li class="list-group-item"><strong><?= esc($row['nama']) ?></strong><div><?= esc($row['nama_prestasi']) ?></div><small class="text-muted"><?= esc($row['tanggal']) ?> · <?= esc($row['tingkat']??'-') ?></small></li><?php endforeach; endif; ?></ul></div></div>
</div>

<?= $this->endSection() ?>
