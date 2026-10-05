<?= $this->extend('main') ?>
<?= $this->section('content') ?>
<?php
$activePeriod = $initial['active_period'] ?? null;
$currentLevel = $initial['current_level'] ?? null;
?>
<div id="dokumenSayaApp">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Dokumen Saya</h4>
            <p class="text-muted mb-0">
                Dokumen Individu Anda dan Dokumen Tingkat yang sesuai dengan tingkat saat ini.
            </p>
        </div>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger">
            <?= esc(session()->getFlashdata('error')) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($initial['success'])): ?>
        <div class="alert alert-danger">
            <?= esc(
                $initial['message']
                ?? 'Dokumen tidak dapat dibuka.'
            ) ?>
        </div>
    <?php else: ?>
        <?php if (! empty($activePeriod) && ! empty($currentLevel)): ?>
            <div class="alert alert-info">
                <i class="bx bx-info-circle me-1"></i>
                Dokumen Tingkat mengikuti tingkat Anda saat ini:
                <strong>Tingkat <?= esc($currentLevel) ?></strong>
                pada
                <?= esc(
                    ($activePeriod['nama_tahun'] ?? '-')
                    . ' - '
                    . ($activePeriod['semester'] ?? '-')
                ) ?>.
                Dokumen Individu tidak terikat periode.
            </div>
        <?php elseif (empty($activePeriod)): ?>
            <div class="alert alert-warning">
                <i class="bx bx-error-circle me-1"></i>
                Tidak ada Tahun Ajaran aktif. Dokumen Individu tetap tersedia;
                Dokumen Tingkat sementara tidak ditampilkan.
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <?php if (($initial['rows'] ?? []) === []): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center text-muted py-5">
                            <i class="bx bx-folder-open fs-1 d-block mb-2"></i>
                            Belum ada Dokumen yang tersedia untuk Anda.
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php foreach (($initial['rows'] ?? []) as $row): ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between gap-2 mb-3">
                                <span class="badge bg-label-<?= ($row['format_file'] ?? '') === 'PDF' ? 'danger' : 'info' ?>">
                                    <?= esc($row['format_file'] ?? '-') ?>
                                </span>

                                <span class="badge bg-label-secondary">
                                    <?= esc(
                                        ($row['target_type'] ?? '') === 'TINGKAT'
                                            ? 'Tingkat ' . ($row['tingkat'] ?? '-')
                                            : 'Individu'
                                    ) ?>
                                </span>
                            </div>

                            <h5 class="card-title">
                                <?= esc($row['judul'] ?? '-') ?>
                            </h5>

                            <div class="text-muted small mb-4">
                                <?= ($row['target_type'] ?? '') === 'INDIVIDU'
                                    ? 'Dokumen pribadi'
                                    : 'Dokumen untuk tingkat saat ini' ?>
                            </div>

                            <a
                                class="btn btn-primary mt-auto"
                                target="_blank"
                                rel="noopener noreferrer"
                                href="<?= esc(
                                    base_url(
                                        'dokumen-saya/buka/'
                                        . (int) $row['id']
                                    )
                                ) ?>"
                            >
                                <i class="bx bx-link-external me-1"></i>
                                Buka Dokumen
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
