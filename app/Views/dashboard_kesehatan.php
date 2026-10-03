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
    <div class="alert alert-warning">Role Kesehatan memerlukan staff identity Guru atau Pegawai yang valid. Data UKS tidak dibentuk.</div>
<?php endif; ?>

<?php if (!$periodAvailable): ?>
    <div class="alert alert-warning">Tidak ada Tahun Ajaran aktif. Ringkasan UKS tidak dibentuk sebagai angka nol palsu.</div>
<?php endif; ?>

<?php
$actionTone = static function (string $label): string {
    $label = strtolower(trim($label));

    return match (true) {
        str_contains($label, 'kunjungan') => 'sisfour-action--teal',
        str_contains($label, 'ckg') => 'sisfour-action--cyan',
        str_contains($label, 'import') => 'sisfour-action--indigo',
        str_contains($label, 'master') => 'sisfour-action--slate',
        str_contains($label, 'uks') => 'sisfour-action--teal',
        default => 'sisfour-action--cyan',
    };
};
?>

<div class="sisfour-dashboard-heading">
    <h5 class="mb-0">Layanan UKS</h5>
    <small class="text-muted">Pekerjaan utama dan akses layanan</small>
</div>
<?php if ($primaryAction !== null): ?>
<a class="btn sisfour-action sisfour-action--hero sisfour-action--teal is-current mb-2"
   href="<?= esc(base_url($primaryAction['url'])) ?>">
    <span class="d-flex align-items-center gap-3 min-w-0">
        <i class="bx <?= esc($primaryAction['icon']) ?> fs-2 flex-shrink-0"></i>
        <span class="min-w-0">
            <strong class="d-block fs-5"><?= esc($primaryAction['label']) ?></strong>
            <small class="d-block opacity-75 text-wrap"><?= esc($primaryAction['description']) ?></small>
        </span>
    </span>
    <i class="bx bx-chevron-right fs-3 flex-shrink-0"></i>
</a>
<?php endif; ?>

<?php if ($actions !== []): ?>
<div class="row g-2 mb-4">
    <?php foreach ($actions as $action): ?>
        <div class="col-6 col-md-3">
            <a class="btn sisfour-action sisfour-action--tile <?= esc($actionTone((string) ($action['label'] ?? '')), 'attr') ?> w-100 h-100 sisfour-touch-target"
               href="<?= esc(base_url($action['url'])) ?>">
                <span class="min-w-0">
                    <i class="bx <?= esc($action['icon']) ?> fs-4 d-block mb-1"></i>
                    <strong class="d-block text-wrap"><?= esc($action['label']) ?></strong>
                    <small class="d-block opacity-75 mt-1 text-wrap"><?= esc($action['description']) ?></small>
                </span>
            </a>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="sisfour-dashboard-heading">
    <h5 class="mb-0">Ringkasan UKS</h5>
    <small class="text-muted">Current-state periode aktif</small>
</div>
<div class="sisfour-metric-grid mb-4">
    <?php
    $kpis = [
        ['label' => 'Kunjungan UKS Hari Ini', 'value' => $widgets['kunjungan_hari_ini'] ?? null, 'icon' => 'bx-calendar-check', 'tone' => 'teal'],
        ['label' => 'Kunjungan UKS Bulan Ini', 'value' => $widgets['kunjungan_bulan_ini'] ?? null, 'icon' => 'bx-calendar', 'tone' => 'blue'],
        ['label' => 'Rujuk ke Klinik Bulan Ini', 'value' => $widgets['rujuk_klinik_bulan_ini'] ?? null, 'icon' => 'bx-clinic', 'tone' => 'amber'],
        ['label' => 'Pemeriksaan CKG Bulan Ini', 'value' => $widgets['ckg_bulan_ini'] ?? null, 'icon' => 'bx-pulse', 'tone' => 'cyan'],
    ];
    foreach ($kpis as $kpi):
    ?>
        <div class="sisfour-metric-tile sisfour-metric-tile--compact sisfour-metric-tile--<?= esc($kpi['tone']) ?>">
            <span class="sisfour-metric-tile__icon"><i class="bx <?= esc($kpi['icon']) ?>"></i></span>
            <strong class="sisfour-metric-tile__value"><?= $kpi['value'] === null ? '—' : number_format((int) $kpi['value']) ?></strong>
            <span class="sisfour-metric-tile__label"><?= esc($kpi['label']) ?></span>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header sisfour-section-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">Kunjungan UKS Terbaru</h5>
                <?php if (!empty($widgets['access']['harian'])): ?><a href="<?= esc(base_url('uks/harian')) ?>" class="btn sisfour-action sisfour-action--teal sisfour-action--compact sisfour-touch-target--compact">Lihat Semua</a><?php endif; ?>
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
                <?php if (empty($widgets['kunjungan_terbaru'])): ?><div class="list-group-item sisfour-mobile-state sisfour-dashboard-empty text-muted">Belum ada data.</div><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header sisfour-section-heading d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">Pemeriksaan CKG Terbaru</h5>
                <?php if (!empty($widgets['access']['ckg'])): ?><a href="<?= esc(base_url('uks/ckg')) ?>" class="btn sisfour-action sisfour-action--cyan sisfour-action--compact sisfour-touch-target--compact">Lihat Semua</a><?php endif; ?>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach (($widgets['ckg_terbaru'] ?? []) as $row): ?>
                    <div class="list-group-item py-3">
                        <div class="fw-semibold text-wrap"><?= esc($row['nama'] ?? '-') ?></div>
                        <div class="small text-muted text-wrap"><?= esc(($row['nisn'] ?? '-') . ' · ' . ($row['nama_kelas'] ?? '-') . ' · ' . ($row['tanggal'] ?? '-')) ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($widgets['ckg_terbaru'])): ?><div class="list-group-item sisfour-mobile-state sisfour-dashboard-empty text-muted">Belum ada data.</div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
