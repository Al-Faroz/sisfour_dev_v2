<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php
$task = $widgets['task_summary'] ?? [];
$wali = $widgets['wali'] ?? [];
$rekap = $wali['presensi_hari_ini'] ?? [];
?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
  <div><h4 class="mb-1">Dashboard Guru & Wali Kelas</h4><p class="text-muted mb-0">Status Wali ditentukan otomatis dari mapping aktif.</p></div>
</div>

<div class="row g-3 mb-4">
  <?php foreach([['Jadwal Hari Ini',$task['jadwal']??0,'primary'],['Presensi Perlu Diisi',$task['presensi_perlu']??0,'warning'],['Jurnal Perlu Diisi',$task['jurnal_perlu']??0,'danger'],['Jurnal Selesai',$task['jurnal_selesai']??0,'success']] as $item): ?>
    <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body"><small class="text-muted"><?= esc($item[0]) ?></small><h4 class="text-<?= esc($item[2]) ?> mb-0"><?= (int)$item[1] ?></h4></div></div></div>
  <?php endforeach; ?>
</div>

<div class="card mb-4">
  <div class="card-header"><h5 class="mb-0">Jadwal Mengajar Hari Ini</h5></div>
  <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Jam</th><th>Kelas</th><th>Mapel</th><th>Sesi</th><th>Presensi</th><th>Jurnal</th></tr></thead><tbody>
    <?php if(empty($widgets['jadwal_hari_ini'])): ?><tr><td colspan="6" class="text-center text-muted py-4">Tidak ada jadwal hari ini.</td></tr>
    <?php else: foreach($widgets['jadwal_hari_ini'] as $j): ?>
    <tr><td><?= esc($j['jam_mulai']) ?> - <?= esc($j['jam_selesai']) ?></td><td><?= esc($j['nama_kelas']) ?></td><td><?= esc($j['nama_mapel']) ?></td><td><span class="badge bg-label-secondary"><?= esc($j['sesi']) ?></span></td><td>
      <?php $ps=$j['presensi_state']??''; $m=['not_applicable'=>['—','secondary'],'submitted'=>['Sudah Diinput','success'],'not_started'=>['Belum Waktunya','secondary'],'available'=>['Isi Presensi','primary'],'wali_available'=>['Isi sebagai Wali','info'],'ended'=>['Waktu Habis','danger']]; [$l,$c]=$m[$ps]??['Tidak Tersedia','secondary']; ?>
      <?php if(!empty($j['presensi_url'])):?><a href="<?= base_url($j['presensi_url']) ?>" class="btn btn-sm btn-outline-<?= esc($c) ?>"><?= esc($l) ?></a><?php else:?><span class="badge bg-label-<?= esc($c) ?>"><?= esc($l) ?></span><?php endif;?>
    </td><td>
      <?php $js=$j['jurnal_state']??''; $m2=['submitted'=>['Sudah','success'],'not_started'=>['Belum Waktunya','secondary'],'available'=>['Isi Jurnal','primary'],'ended'=>['Terlewat','danger']]; [$l2,$c2]=$m2[$js]??['Tidak Tersedia','secondary']; ?>
      <?php if(!empty($j['jurnal_url'])):?><a href="<?= base_url($j['jurnal_url']) ?>" class="btn btn-sm btn-outline-<?= esc($c2) ?>"><?= esc($l2) ?></a><?php else:?><span class="badge bg-label-<?= esc($c2) ?>"><?= esc($l2) ?></span><?php endif;?>
    </td></tr>
    <?php endforeach; endif; ?>
  </tbody></table></div>
</div>

<?php if(!empty($wali)): ?>
<div class="card mb-4 border border-primary">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2"><div><h5 class="mb-1">Kelas Wali: <?= esc($wali['nama_kelas']??'-') ?></h5><small class="text-muted"><?= (int)($wali['jumlah_siswa']??0) ?> siswa aktif</small></div><span class="badge bg-label-danger">EWS <?= (int)($wali['ews_count']??0) ?> siswa</span></div>
  <div class="card-body">
    <div class="row g-3 text-center mb-4">
      <?php foreach([['Hadir',$rekap['hadir']??0,'success'],['Sakit',$rekap['sakit']??0,'warning'],['Izin',$rekap['izin']??0,'info'],['Alpha',$rekap['alpha']??0,'danger']] as $item): ?><div class="col-6 col-md-3"><div class="border rounded p-3"><small class="text-muted d-block"><?= esc($item[0]) ?></small><h4 class="text-<?= esc($item[2]) ?> mb-0"><?= (int)$item[1] ?></h4></div></div><?php endforeach; ?>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <?php foreach(($wali['quick_links']??[]) as $link): ?><a href="<?= base_url($link['url']) ?>" class="btn btn-sm btn-outline-primary"><?= esc($link['label']) ?></a><?php endforeach; ?>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-5"><div class="card h-100"><div class="card-header"><h5 class="mb-0">EWS Kelas</h5></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Nama</th><th>Alpha</th></tr></thead><tbody><?php if(empty($wali['ews_top'])):?><tr><td colspan="2" class="text-center text-muted py-4">Tidak ada siswa EWS.</td></tr><?php else: foreach($wali['ews_top'] as $row):?><tr><td><?= esc($row['nama']) ?></td><td><span class="badge bg-label-danger"><?= (int)$row['total_alpha'] ?></span></td></tr><?php endforeach; endif;?></tbody></table></div></div></div>
  <div class="col-lg-7"><div class="card h-100"><div class="card-header"><h5 class="mb-0">Sakit / Izin / Alpha Terbaru</h5></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Tanggal</th><th>Nama</th><th>Status</th></tr></thead><tbody><?php if(empty($wali['recent_absence'])):?><tr><td colspan="3" class="text-center text-muted py-4">Belum ada catatan.</td></tr><?php else: foreach($wali['recent_absence'] as $row):?><tr><td><?= esc($row['tanggal']) ?></td><td><?= esc($row['nama']) ?></td><td><span class="badge bg-label-<?= $row['status']==='Alpha'?'danger':($row['status']==='Sakit'?'warning':'info') ?>"><?= esc($row['status']) ?></span></td></tr><?php endforeach; endif;?></tbody></table></div></div></div>
</div>
<?php endif; ?>

<div class="card"><div class="card-header"><h5 class="mb-0">Riwayat Jurnal Terakhir</h5></div><ul class="list-group list-group-flush"><?php if(empty($widgets['riwayat_jurnal_terakhir'])):?><li class="list-group-item text-center text-muted py-4">Belum ada jurnal.</li><?php else: foreach($widgets['riwayat_jurnal_terakhir'] as $row):?><li class="list-group-item"><strong><?= esc($row['tanggal']) ?> · <?= esc($row['nama_kelas']??'-') ?></strong><div class="small text-muted"><?= esc($row['nama_mapel']??'-') ?> · <?= esc($row['status']) ?> · <?= esc(mb_strimwidth((string)$row['materi'],0,70,'...')) ?></div></li><?php endforeach; endif;?></ul></div>

<?= $this->endSection() ?>
