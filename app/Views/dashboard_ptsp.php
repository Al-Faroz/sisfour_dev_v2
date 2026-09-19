<?= $this->extend('main') ?>
<?= $this->section('content') ?>
<?php
$tahun = $widgets['tahun_aktif'] ?? null;
$periodAvailable = !empty($widgets['period_available']);
$identityAvailable = !array_key_exists('identity_available', $widgets) || !empty($widgets['identity_available']);
$actions = is_array($widgets['quick_actions'] ?? null) ? $widgets['quick_actions'] : [];
?>

<div class="sisfour-page-header">
    <div class="sisfour-page-header__copy">
        <h4 class="fw-bold mb-1">Dashboard PTSP</h4>
        <p class="text-muted mb-0">Ringkasan current-state layanan, pengaduan, dan kepuasan pada Tahun Ajaran aktif.</p>
    </div>
    <div class="sisfour-page-actions">
        <?php if ($periodAvailable): ?>
            <span class="badge bg-label-primary"><?= esc(($tahun['nama_tahun'] ?? '-') . ' · ' . ($tahun['semester'] ?? '-')) ?></span>
        <?php else: ?>
            <span class="badge bg-label-secondary">Tahun Ajaran tidak tersedia</span>
        <?php endif; ?>
    </div>
</div>

<?php if (!$identityAvailable): ?>
<div class="alert alert-danger">Role PTSP memerlukan identity Pegawai yang valid. Data PTSP tidak dibentuk.</div>
<?php endif; ?>
<?php if (!$periodAvailable): ?>
<div class="alert alert-warning">Tidak ada Tahun Ajaran aktif. Ringkasan PTSP tidak dibentuk sebagai angka nol palsu.</div>
<?php endif; ?>

<div class="sisfour-mobile-kpi-grid mb-4">
<?php
$kpis = [
    ['label' => 'Layanan Baru', 'value' => $widgets['layanan_baru'] ?? null, 'icon' => 'bx-file'],
    ['label' => 'Layanan Diproses', 'value' => $widgets['layanan_diproses'] ?? null, 'icon' => 'bx-loader-circle'],
    ['label' => 'Pengaduan Masuk', 'value' => $widgets['pengaduan_masuk'] ?? null, 'icon' => 'bx-message-square-error'],
    ['label' => 'Rata-rata Kepuasan', 'value' => $widgets['rata_kepuasan'] ?? null, 'icon' => 'bx-happy'],
];
foreach ($kpis as $kpi):
?>
    <div class="card h-100">
        <div class="card-body d-flex align-items-start gap-2">
            <i class="bx <?= esc($kpi['icon']) ?> fs-4 flex-shrink-0"></i>
            <div class="min-w-0">
                <span class="small text-muted d-block text-wrap"><?= esc($kpi['label']) ?></span>
                <div class="fs-3 fw-bold mt-1"><?= $kpi['value'] === null ? '—' : esc((string) $kpi['value']) ?></div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php if ($actions !== []): ?>
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Akses Cepat</h5></div>
    <div class="card-body"><div class="row g-3">
        <?php foreach ($actions as $action): ?>
        <div class="col-6 col-md-3">
            <a class="card h-100 text-decoration-none shadow-none border sisfour-touch-target justify-content-start text-start" href="<?= esc(base_url($action['url'])) ?>" <?= ($action['url'] ?? '') === 'ptsp' ? 'target="_blank" rel="noopener"' : '' ?>>
                <div class="card-body min-w-0"><i class="bx <?= esc($action['icon']) ?> fs-3 mb-2"></i><div class="fw-semibold text-wrap"><?= esc($action['label']) ?></div><div class="small text-muted mt-1 text-wrap"><?= esc($action['description']) ?></div></div>
            </a>
        </div>
        <?php endforeach; ?>
    </div></div>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header sisfour-section-heading d-flex justify-content-between align-items-center flex-wrap gap-2"><h5 class="mb-0">Layanan Terbaru</h5><?php if (!empty($widgets['access']['layanan'])): ?><a class="btn btn-sm btn-outline-primary sisfour-touch-target--compact" href="<?= esc(base_url('ptsp/layanan')) ?>">Lihat Semua</a><?php endif; ?></div>
            <div class="list-group list-group-flush">
                <?php foreach (($widgets['layanan_terbaru'] ?? []) as $row): ?>
                <div class="list-group-item py-3"><div class="d-flex justify-content-between align-items-start flex-wrap gap-2"><div class="min-w-0 flex-grow-1"><div class="fw-semibold text-wrap"><?= esc($row['nama_lengkap'] ?? '-') ?></div><div class="small text-muted text-wrap"><?= esc(($row['jenis_layanan'] ?? '-') . ' · ' . ($row['kategori_pemohon'] ?? '-')) ?></div></div><span class="badge bg-label-primary flex-shrink-0"><?= esc($row['status'] ?? '-') ?></span></div></div>
                <?php endforeach; ?>
                <?php if (empty($widgets['layanan_terbaru'])): ?><div class="list-group-item text-center text-muted py-4">Belum ada data.</div><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header sisfour-section-heading d-flex justify-content-between align-items-center flex-wrap gap-2"><h5 class="mb-0">Pengaduan Terbaru</h5><?php if (!empty($widgets['access']['pengaduan'])): ?><a class="btn btn-sm btn-outline-primary sisfour-touch-target--compact" href="<?= esc(base_url('ptsp/pengaduan')) ?>">Lihat Semua</a><?php endif; ?></div>
            <div class="list-group list-group-flush">
                <?php foreach (($widgets['pengaduan_terbaru'] ?? []) as $row): ?>
                <div class="list-group-item py-3"><div class="d-flex justify-content-between align-items-start flex-wrap gap-2"><div class="min-w-0 flex-grow-1"><div class="fw-semibold text-wrap"><?= esc($row['judul_laporan'] ?? '-') ?></div><div class="small text-muted text-wrap"><?= esc($row['tanggal_kejadian'] ?: ($row['created_at'] ?? '-')) ?></div></div><span class="badge bg-label-warning flex-shrink-0"><?= esc($row['status'] ?? '-') ?></span></div></div>
                <?php endforeach; ?>
                <?php if (empty($widgets['pengaduan_terbaru'])): ?><div class="list-group-item text-center text-muted py-4">Belum ada data.</div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header sisfour-section-heading d-flex justify-content-between align-items-center flex-wrap gap-2"><h5 class="mb-0">Ringkasan Kepuasan</h5><?php if (!empty($widgets['access']['polling'])): ?><a class="btn btn-sm btn-outline-primary sisfour-touch-target--compact" href="<?= esc(base_url('ptsp/polling')) ?>">Lihat Detail</a><?php endif; ?></div>
    <div class="card-body"><div class="row g-2">
        <?php foreach (($widgets['kepuasan_ringkas'] ?? []) as $row): ?>
        <div class="col-12 col-md"><div class="border rounded p-3 h-100"><div class="small text-muted"><?= esc($row['label'] ?? '-') ?></div><div class="fs-4 fw-bold"><?= number_format((int) ($row['total'] ?? 0)) ?></div></div></div>
        <?php endforeach; ?>
        <?php if (empty($widgets['kepuasan_ringkas'])): ?><div class="text-muted">Belum ada polling pada periode aktif.</div><?php endif; ?>
    </div></div>
</div>
<?= $this->endSection() ?>
