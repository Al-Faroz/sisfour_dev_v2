<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<h4 class="mb-4">Dashboard Guru</h4>

<div class="card mb-4">
  <div class="card-header"><h5 class="mb-0">Jadwal Hari Ini</h5></div>
  <div class="table-responsive text-nowrap">
    <table class="table">
      <thead><tr><th>Jam</th><th>Kelas</th><th>Mapel</th><th>Sesi</th><th>Presensi Siswa</th><th>Jurnal</th></tr></thead>
      <tbody>
        <?php if (empty($widgets['jadwal_hari_ini'])): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada jadwal mengajar hari ini.</td></tr>
        <?php else: foreach ($widgets['jadwal_hari_ini'] as $j): ?>
          <tr>
            <td><?= esc($j['jam_mulai']) ?> - <?= esc($j['jam_selesai']) ?></td>
            <td><?= esc($j['nama_kelas']) ?></td>
            <td><?= esc($j['nama_mapel']) ?></td>
            <td><span class="badge bg-label-secondary"><?= esc($j['sesi']) ?></span></td>
            <td>
              <?php if ($j['sesi'] !== 'Non Sesi'): ?>
                <a href="<?= base_url('presensi/siswa/input/' . $j['id_kelas']) ?>" class="btn btn-sm btn-outline-primary">Isi</a>
              <?php else: ?>
                <span class="text-muted small">-</span>
              <?php endif; ?>
            </td>
            <td><a href="<?= base_url('presensi/mengajar/input/' . $j['id']) ?>" class="btn btn-sm btn-outline-success">Isi Jurnal</a></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-header"><h5 class="mb-0">Riwayat Jurnal Terakhir</h5></div>
  <ul class="list-group list-group-flush">
    <?php if (empty($widgets['riwayat_jurnal_terakhir'])): ?>
      <li class="list-group-item text-muted text-center py-4">Belum ada jurnal.</li>
    <?php else: foreach ($widgets['riwayat_jurnal_terakhir'] as $r): ?>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <span><?= esc($r['tanggal']) ?> — <?= esc($r['status']) ?></span>
        <span class="text-muted small"><?= esc(mb_strimwidth($r['materi'], 0, 60, '...')) ?></span>
      </li>
    <?php endforeach; endif; ?>
  </ul>
</div>

<?= $this->endSection() ?>
