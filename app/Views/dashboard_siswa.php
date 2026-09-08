<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php $rekap = $widgets['rekap_presensi_bulan_ini'] ?? []; $kartu=$widgets['kartu']??null; ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
  <div><h4 class="mb-1">Dashboard Siswa</h4><p class="text-muted mb-0">Ringkasan data milik sendiri.</p></div>
</div>

<div class="row g-3 mb-4">
  <?php foreach([['Hadir',$rekap['hadir']??0,'success'],['Sakit',$rekap['sakit']??0,'warning'],['Izin',$rekap['izin']??0,'info'],['Alpha',$rekap['alpha']??0,'danger']] as $item): ?><div class="col-6 col-md-3"><div class="card h-100"><div class="card-body text-center"><small class="text-muted d-block"><?= esc($item[0]) ?></small><h3 class="text-<?= esc($item[2]) ?> mb-0"><?= (int)$item[1] ?></h3></div></div></div><?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-6"><div class="card h-100"><div class="card-header d-flex justify-content-between align-items-center"><h5 class="mb-0">Sakit / Izin / Alpha Terbaru</h5><a href="<?= base_url('presensi/siswa/rekap') ?>" class="btn btn-sm btn-outline-primary">Lihat Rekap</a></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Tanggal</th><th>Status</th></tr></thead><tbody><?php if(empty($widgets['presensi_terbaru'])):?><tr><td colspan="2" class="text-center text-muted py-4">Tidak ada catatan.</td></tr><?php else: foreach($widgets['presensi_terbaru'] as $row):?><tr><td><?= esc($row['tanggal']) ?></td><td><span class="badge bg-label-<?= $row['status']==='Alpha'?'danger':($row['status']==='Sakit'?'warning':'info') ?>"><?= esc($row['status']) ?></span></td></tr><?php endforeach; endif;?></tbody></table></div></div></div>
  <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h5 class="mb-0">Kartu Pelajar</h5></div><div class="card-body"><?php if(empty($kartu)):?><p class="text-muted mb-0">Kartu pelajar belum tersedia.</p><?php else:?><div class="mb-2"><span class="badge bg-label-<?= ($kartu['status_aktif']??'')==='Aktif'?'success':'secondary' ?>"><?= esc($kartu['status_aktif']) ?></span></div><strong><?= esc($kartu['nomor_kartu']) ?></strong><div class="small text-muted mb-3">Terbit <?= esc($kartu['tanggal_terbit']) ?></div><small class="text-muted">Detail kartu tersedia saat modul Kartu diaktifkan.</small><?php endif;?></div></div></div>
</div>

<div class="row g-3">
  <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h5 class="mb-0">Prestasi Terbaru</h5></div><ul class="list-group list-group-flush"><?php if(empty($widgets['riwayat_prestasi'])):?><li class="list-group-item text-muted text-center py-4">Belum ada prestasi tercatat.</li><?php else: foreach($widgets['riwayat_prestasi'] as $row):?><li class="list-group-item"><strong><?= esc($row['nama_prestasi']) ?></strong><div class="small text-muted"><?= esc($row['tingkat']??'-') ?> · <?= esc($row['tanggal']) ?></div></li><?php endforeach; endif;?></ul></div></div>
  <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h5 class="mb-0">Catatan Kasus Saya</h5></div><ul class="list-group list-group-flush"><?php if(empty($widgets['riwayat_pelanggaran'])):?><li class="list-group-item text-muted text-center py-4">Tidak ada catatan kasus.</li><?php else: foreach($widgets['riwayat_pelanggaran'] as $row):?><li class="list-group-item"><strong><?= esc($row['nama_pelanggaran']) ?></strong> <span class="badge bg-label-<?= ($row['kategori']??'')==='Berat'?'danger':(($row['kategori']??'')==='Sedang'?'warning':'secondary') ?>"><?= esc($row['kategori']) ?></span><div class="small text-muted"><?= esc($row['tanggal']) ?> · <?= (int)$row['poin'] ?> poin</div><?php if(!empty($row['keterangan'])):?><div class="small mt-1"><?= esc($row['keterangan']) ?></div><?php endif;?></li><?php endforeach; endif;?></ul></div></div>
</div>

<?= $this->endSection() ?>
