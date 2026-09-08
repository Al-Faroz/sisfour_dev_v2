<?= $this->extend('main') ?>
<?= $this->section('content') ?>
<div class="card">
    <div class="card-header"><h4 class="mb-0">Top 20 Poin Pelanggaran</h4></div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead><tr><th>#</th><th>NISN</th><th>Nama</th><th>Total Kasus</th><th>Total Poin</th></tr></thead>
            <tbody>
            <?php if (empty($result['success'])): ?>
                <tr><td colspan="5" class="text-center text-danger"><?= esc($result['message'] ?? 'Tidak dapat memuat data.') ?></td></tr>
            <?php else: ?>
                <?php foreach (($result['rows'] ?? []) as $i => $row): ?>
                    <tr><td><?= $i + 1 ?></td><td><?= esc($row['nisn']) ?></td><td><?= esc($row['nama']) ?></td><td><?= (int) $row['total_kasus'] ?></td><td><?= (int) $row['total_poin'] ?></td></tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
