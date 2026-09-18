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

<div class="row g-3 mb-4">
    <?php
    $kpis = [
        ['label' => 'Pemeriksaan CKG Bulan Ini', 'value' => $widgets['ckg_bulan_ini'] ?? null, 'icon' => 'bx-pulse'],
        ['label' => 'Kunjungan UKS Hari Ini', 'value' => $widgets['kunjungan_hari_ini'] ?? null, 'icon' => 'bx-calendar-check'],
        ['label' => 'Kunjungan UKS Bulan Ini', 'value' => $widgets['kunjungan_bulan_ini'] ?? null, 'icon' => 'bx-calendar'],
        ['label' => 'Rujuk ke Klinik Bulan Ini', 'value' => $widgets['rujuk_klinik_bulan_ini'] ?? null, 'icon' => 'bx-clinic'],
    ];
    foreach ($kpis as $kpi):
    ?>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bx <?= esc($kpi['icon']) ?> fs-4"></i>
                        <span class="small text-muted"><?= esc($kpi['label']) ?></span>
                    </div>
                    <div class="fs-3 fw-bold"><?= $kpi['value'] === null ? '—' : number_format((int) $kpi['value']) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($actions !== []): ?>
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Akses Cepat</h5></div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($actions as $action): ?>
                <div class="col-6 col-md-3">
                    <a class="card h-100 text-decoration-none shadow-none border" href="<?= esc(base_url($action['url'])) ?>">
                        <div class="card-body">
                            <i class="bx <?= esc($action['icon']) ?> fs-3 mb-2"></i>
                            <div class="fw-semibold"><?= esc($action['label']) ?></div>
                            <div class="small text-muted mt-1"><?= esc($action['description']) ?></div>
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Kunjungan UKS Terbaru</h5>
                <?php if (!empty($widgets['access']['harian'])): ?><a href="<?= esc(base_url('uks/harian')) ?>" class="btn btn-sm btn-outline-primary">Lihat Semua</a><?php endif; ?>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach (($widgets['kunjungan_terbaru'] ?? []) as $row): ?>
                    <div class="list-group-item py-3">
                        <div class="d-flex justify-content-between gap-2">
                            <div>
                                <div class="fw-semibold"><?= esc($row['nama'] ?? '-') ?></div>
                                <div class="small text-muted"><?= esc(($row['nama_kelas'] ?? '-') . ' · ' . ($row['tanggal'] ?? '-') . ' ' . substr((string) ($row['jam_masuk'] ?? ''), 0, 5)) ?></div>
                            </div>
                            <span class="badge bg-label-primary"><?= esc($row['hasil'] ?? '-') ?></span>
                        </div>
                        <div class="small mt-2"><?= esc($row['keluhan'] ?? '-') ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($widgets['kunjungan_terbaru'])): ?><div class="list-group-item text-muted py-4 text-center">Belum ada data.</div><?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Pemeriksaan CKG Terbaru</h5>
                <?php if (!empty($widgets['access']['ckg'])): ?><a href="<?= esc(base_url('uks/ckg')) ?>" class="btn btn-sm btn-outline-primary">Lihat Semua</a><?php endif; ?>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach (($widgets['ckg_terbaru'] ?? []) as $row): ?>
                    <div class="list-group-item py-3">
                        <div class="fw-semibold"><?= esc($row['nama'] ?? '-') ?></div>
                        <div class="small text-muted"><?= esc(($row['nisn'] ?? '-') . ' · ' . ($row['nama_kelas'] ?? '-') . ' · ' . ($row['tanggal'] ?? '-')) ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($widgets['ckg_terbaru'])): ?><div class="list-group-item text-muted py-4 text-center">Belum ada data.</div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
