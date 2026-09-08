<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php $task = $widgets['task_summary'] ?? []; ?>

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
  <div><h4 class="mb-1">Dashboard Guru</h4><p class="text-muted mb-0">Daftar pekerjaan mengajar hari ini.</p></div>
</div>

<div class="row g-3 mb-4">
  <?php foreach([
    ['Jadwal Hari Ini',$task['jadwal']??0,'primary'],
    ['Presensi Perlu Diisi',$task['presensi_perlu']??0,'warning'],
    ['Jurnal Perlu Diisi',$task['jurnal_perlu']??0,'danger'],
    ['Jurnal Selesai',$task['jurnal_selesai']??0,'success'],
  ] as $item): ?>
    <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body"><small class="text-muted"><?= esc($item[0]) ?></small><h4 class="text-<?= esc($item[2]) ?> mb-0"><?= (int)$item[1] ?></h4></div></div></div>
  <?php endforeach; ?>
</div>

<div class="card mb-4">
  <div class="card-header"><h5 class="mb-0">Jadwal & Status Tugas Hari Ini</h5></div>
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr><th>Jam</th><th>Kelas</th><th>Mapel</th><th>Sesi</th><th>Presensi Siswa</th><th>Jurnal</th></tr></thead>
      <tbody>
      <?php if(empty($widgets['jadwal_hari_ini'])): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada jadwal mengajar hari ini.</td></tr>
      <?php else: foreach($widgets['jadwal_hari_ini'] as $j): ?>
        <tr>
          <td><?= esc($j['jam_mulai']) ?> - <?= esc($j['jam_selesai']) ?></td>
          <td><?= esc($j['nama_kelas']) ?></td>
          <td><?= esc($j['nama_mapel']) ?></td>
          <td><span class="badge bg-label-secondary"><?= esc($j['sesi']) ?></span></td>
          <td>
            <?php
              $ps = $j['presensi_state'] ?? '';
              $labels = [
                'not_applicable'=>['—','secondary'],
                'submitted'=>['Sudah Diinput','success'],
                'not_started'=>['Belum Waktunya','secondary'],
                'available'=>['Isi Presensi','primary'],
                'wali_available'=>['Isi sebagai Wali','info'],
                'ended'=>['Waktu Habis','danger'],
              ];
              [$label,$color] = $labels[$ps] ?? ['Tidak Tersedia','secondary'];
            ?>
            <?php if(!empty($j['presensi_url'])): ?><a class="btn btn-sm btn-outline-<?= esc($color) ?>" href="<?= base_url($j['presensi_url']) ?>"><?= esc($label) ?></a><?php else: ?><span class="badge bg-label-<?= esc($color) ?>"><?= esc($label) ?></span><?php endif; ?>
          </td>
          <td>
            <?php
              $js = $j['jurnal_state'] ?? '';
              $jlabels = ['submitted'=>['Sudah','success'],'not_started'=>['Belum Waktunya','secondary'],'available'=>['Isi Jurnal','primary'],'ended'=>['Terlewat','danger']];
              [$jlabel,$jcolor] = $jlabels[$js] ?? ['Tidak Tersedia','secondary'];
            ?>
            <?php if(!empty($j['jurnal_url'])): ?><a class="btn btn-sm btn-outline-<?= esc($jcolor) ?>" href="<?= base_url($j['jurnal_url']) ?>"><?= esc($jlabel) ?></a><?php else: ?><span class="badge bg-label-<?= esc($jcolor) ?>"><?= esc($jlabel) ?></span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-header"><h5 class="mb-0">Riwayat Jurnal Terakhir</h5></div>
  <ul class="list-group list-group-flush">
    <?php if(empty($widgets['riwayat_jurnal_terakhir'])): ?><li class="list-group-item text-center text-muted py-4">Belum ada jurnal.</li>
    <?php else: foreach($widgets['riwayat_jurnal_terakhir'] as $row): ?><li class="list-group-item"><div class="d-flex justify-content-between gap-3"><div><strong><?= esc($row['tanggal']) ?> · <?= esc($row['nama_kelas']??'-') ?></strong><div class="small text-muted"><?= esc($row['nama_mapel']??'-') ?> · <?= esc($row['status']) ?></div></div><div class="text-muted small text-end"><?= esc(mb_strimwidth((string)$row['materi'],0,70,'...')) ?></div></div></li><?php endforeach; endif; ?>
  </ul>
</div>

<?= $this->endSection() ?>
