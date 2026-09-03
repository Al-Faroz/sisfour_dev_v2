<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<h4 class="mb-4">Dashboard Admin</h4>

<div class="row g-4 mb-4">
  <div class="col-md-4">
    <div class="card">
      <div class="card-body d-flex align-items-center">
        <div class="avatar avatar-lg me-3"><span class="avatar-initial rounded bg-label-primary"><i class="icon-base bx bx-group icon-lg"></i></span></div>
        <div>
          <span class="text-muted small">Total Siswa Aktif</span>
          <h4 class="mb-0"><?= (int) ($widgets['total_siswa_aktif'] ?? 0) ?></h4>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-body d-flex align-items-center">
        <div class="avatar avatar-lg me-3"><span class="avatar-initial rounded bg-label-success"><i class="icon-base bx bx-chalkboard icon-lg"></i></span></div>
        <div>
          <span class="text-muted small">Total Guru</span>
          <h4 class="mb-0"><?= (int) ($widgets['total_guru'] ?? 0) ?></h4>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card">
      <div class="card-body d-flex align-items-center">
        <div class="avatar avatar-lg me-3"><span class="avatar-initial rounded bg-label-info"><i class="icon-base bx bx-door-open icon-lg"></i></span></div>
        <div>
          <span class="text-muted small">Total Kelas</span>
          <h4 class="mb-0"><?= (int) ($widgets['total_kelas'] ?? 0) ?></h4>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h5 class="mb-0">Aktivitas Terakhir</h5></div>
  <div class="table-responsive text-nowrap">
    <table class="table">
      <thead>
        <tr><th>Waktu</th><th>Modul</th><th>Aksi</th><th>Keterangan</th></tr>
      </thead>
      <tbody>
        <?php if (empty($widgets['aktivitas_terakhir'])): ?>
          <tr><td colspan="4" class="text-center text-muted py-4">Belum ada aktivitas tercatat.</td></tr>
        <?php else: foreach ($widgets['aktivitas_terakhir'] as $log): ?>
          <tr>
            <td><?= esc($log['waktu']) ?></td>
            <td><?= esc($log['modul']) ?></td>
            <td><?= esc($log['aksi']) ?></td>
            <td><?= esc($log['keterangan'] ?? '-') ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>
