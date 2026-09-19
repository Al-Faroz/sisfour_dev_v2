<?= $this->extend('main') ?>
<?= $this->section('content') ?>
<?php
$tahun = $widgets['tahun_aktif'] ?? null;
$periodAvailable = !empty($widgets['period_available']);
$identityAvailable = !array_key_exists('identity_available', $widgets) || !empty($widgets['identity_available']);
$primaryAction = is_array($widgets['primary_action'] ?? null) ? $widgets['primary_action'] : null;
$actions = is_array($widgets['quick_actions'] ?? null) ? $widgets['quick_actions'] : [];
?>

<div class="sisfour-page-header">
    <div class="sisfour-page-header__copy">
        <h4 class="fw-bold mb-1">Dashboard Kesehatan</h4>
        <p class="text-muted mb-0">Ringkasan current-state UKS pada Tahun Ajaran aktif.</p>
    </div>
    <div class="sisfour-page-actions">
        <?php if ($periodAvailable): ?>
            <span class="badge bg-label-primary">
                <?= esc(($tahun['nama_tahun'] ?? '-') . ' · ' . ($tahun['semester'] ?? '-')) ?>
            </span>
        <?php else: ?>
            <span class="badge bg-label-secondary">Tahun Ajaran tidak tersedia</span>
        <?php endif; ?>
    </div>
</div>

<?php if (!$identityAvailable): ?>
    <div class="alert alert-danger">Role Kesehatan memerlukan identity Pegawai yang valid. Data UKS tidak dibentuk.</div>
<?php endif; ?>

<?php if (!$periodAvailable): ?>
    <div class="alert alert-warning">Tidak ada Tahun Ajaran aktif. Ringkasan UKS tidak dibentuk sebagai angka nol palsu.</div>
<?php endif; ?>

<div class="sisfour-mobile-kpi-grid mb-4">
    <?php
    $kpis = [
        ['label' => 'Kunjungan UKS Hari Ini', 'value' => $widgets['kunjungan_hari_ini'] ?? null, 'icon' => 'bx-calendar-check'],
        ['label' => 'Kunjungan UKS Bulan Ini', 'value' => $widgets['kunjungan_bulan_ini'] ?? null, 'icon' => 'bx-calendar'],
        ['label' => 'Rujuk ke Klinik Bulan Ini', 'value' => $widgets['rujuk_klinik_bulan_ini'] ?? null, 'icon' => 'bx-clinic'],
        ['label' => 'Pemeriksaan CKG Bulan Ini', 'value' => $widgets['ckg_bulan_ini'] ?? null, 'icon' => 'bx-pulse'],
    ];
    foreach ($kpis as $kpi):
    ?>
        <div class="card h-100">
            <div class="card-body d-flex align-items-start gap-2">
                <i class="bx <?= esc($kpi['icon']) ?> fs-4 flex-shrink-0"></i>
                <div class="min-w-0">
                    <span class="small text-muted d-block text-wrap"><?= esc($kpi['label']) ?></span>
                    <div class="fs-3 fw-bold mt-1"><?= $kpi['value'] === null ? '—' : number_format((int) $kpi['value']) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($primaryAction !== null): ?>
<a class="card mb-4 text-decoration-none border-primary" href="<?= esc(base_url($primaryAction['url'])) ?>">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3 py-4">
        <div class="d-flex align-items-center gap-3 min-w-0 flex-grow-1">
            <span class="avatar-initial rounded bg-label-primary p-3">
                <i class="bx <?= esc($primaryAction['icon']) ?> fs-2"></i>
            </span>
            <div class="min-w-0">
                <div class="fs-5 fw-semibold text-body text-wrap"><?= esc($primaryAction['label']) ?></div>
                <div class="text-muted text-wrap"><?= esc($primaryAction['description']) ?></div>
            </div>
        </div>
        <i class="bx bx-chevron-right fs-2 text-primary"></i>
    </div>
</a>
<?php endif; ?>

<?php if ($actions !== []): ?>
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Akses Cepat</h5></div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($actions as $action): ?>
                <div class="col-6 col-md-3">
                    <a class="card h-100 text-decoration-none shadow-none border sisfour-touch-target justify-content-start text-start" href="<?= esc(base_url($action['url'])) ?>">
                        <div class="card-body min-w-0">
                            <i class="bx <?= esc($action['icon']) ?> fs-3 mb-2"></i>
                            <div class="fw-semibold text-wrap"><?= esc($action['label']) ?></div>
                            <div class="small text-muted mt-1 text-wrap"><?= esc($action['description']) ?></div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header sisfour-section-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">Kunjungan UKS Terbaru</h5>
                <?php if (!empty($widgets['access']['harian'])): ?><a href="<?= esc(base_url('uks/harian')) ?>" class="btn btn-sm btn-outline-primary sisfour-touch-target--compact">Lihat Semua</a><?php endif; ?>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach (($widgets['kunjungan_terbaru'] ?? []) as $row): ?>
                    <div class="list-group-item py-3">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold text-wrap"><?= esc($row['nama'] ?? '-') ?></div>
                                <div class="small text-muted text-wrap"><?= esc(($row['nama_kelas'] ?? '-') . ' · ' . ($row['tanggal'] ?? '-') . ' ' . substr((string) ($row['jam_masuk'] ?? ''), 0, 5)) ?></div>
                            </div>
                            <span class="badge bg-label-primary flex-shrink-0"><?= esc($row['hasil'] ?? '-') ?></span>
                        </div>
                        <div class="small mt-2 text-wrap"><?= esc($row['keluhan'] ?? '-') ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($widgets['kunjungan_terbaru'])): ?><div class="list-group-item text-muted py-4 text-center">Belum ada data.</div><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header sisfour-section-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">Pemeriksaan CKG Terbaru</h5>
                <?php if (!empty($widgets['access']['ckg'])): ?><a href="<?= esc(base_url('uks/ckg')) ?>" class="btn btn-sm btn-outline-primary sisfour-touch-target--compact">Lihat Semua</a><?php endif; ?>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach (($widgets['ckg_terbaru'] ?? []) as $row): ?>
                    <div class="list-group-item py-3">
                        <div class="fw-semibold text-wrap"><?= esc($row['nama'] ?? '-') ?></div>
                        <div class="small text-muted text-wrap"><?= esc(($row['nisn'] ?? '-') . ' · ' . ($row['nama_kelas'] ?? '-') . ' · ' . ($row['tanggal'] ?? '-')) ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($widgets['ckg_terbaru'])): ?><div class="list-group-item text-muted py-4 text-center">Belum ada data.</div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
