<!doctype html>
<html lang="id">
<head>
<?= $this->include('_header') ?>
<?php if (isset($extraCss)): foreach ((array) $extraCss as $css): ?>
<link rel="stylesheet" href="<?= sisfour_asset_url((string) $css) ?>" />
<?php endforeach; endif; ?>
</head>
<body class="ptsp-kiosk-body">
<main class="container py-4 py-md-5">
    <div class="ptsp-kiosk-shell mx-auto">
        <div class="text-center mb-4 mb-md-5">
            <span class="badge bg-label-primary mb-3">Pelayanan Terpadu Satu Pintu</span>
            <h1 class="display-6 fw-bold mb-2"><?= esc($systemSettings['nama_sekolah'] ?? 'MTsN 4 Jombang') ?></h1>
            <?php if (!empty($systemSettings['alamat_sekolah'])): ?>
                <p class="text-muted mb-0"><?= esc($systemSettings['alamat_sekolah']) ?></p>
            <?php endif; ?>
            <p class="lead text-muted mt-3 mb-0">Silakan pilih layanan yang ingin digunakan.</p>
        </div>

        <div class="ptsp-kiosk-grid">
            <a class="ptsp-kiosk-button" href="<?= esc(base_url('ptsp/form/layanan')) ?>">
                <span class="ptsp-kiosk-icon bg-label-primary"><i class="bx bx-file fs-1"></i></span>
                <span>
                    <strong>Layanan PTSP</strong>
                    <small>Ajukan legalisir, surat keterangan, mutasi, dan layanan administrasi lainnya.</small>
                </span>
                <i class="bx bx-chevron-right fs-2"></i>
            </a>

            <a class="ptsp-kiosk-button" href="<?= esc(base_url('ptsp/form/pengaduan')) ?>">
                <span class="ptsp-kiosk-icon bg-label-warning"><i class="bx bx-message-square-error fs-1"></i></span>
                <span>
                    <strong>Pengaduan</strong>
                    <small>Sampaikan pengaduan, aspirasi, atau permintaan informasi secara anonim.</small>
                </span>
                <i class="bx bx-chevron-right fs-2"></i>
            </a>

            <a class="ptsp-kiosk-button" href="<?= esc(base_url('ptsp/form/polling')) ?>">
                <span class="ptsp-kiosk-icon bg-label-success"><i class="bx bx-happy fs-1"></i></span>
                <span>
                    <strong>Polling Kepuasan</strong>
                    <small>Berikan penilaian dan masukan untuk peningkatan kualitas pelayanan.</small>
                </span>
                <i class="bx bx-chevron-right fs-2"></i>
            </a>
        </div>
    </div>
</main>
</body>
</html>
