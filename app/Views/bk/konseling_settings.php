<?= $this->extend('main') ?>
<?= $this->section('content') ?>

<?php
$options = $initial['options'] ?? [];
$topik = $options['topik'] ?? [];
$toLines = static fn (array $items): string => implode("\n", $items);
?>

<div id="bkKonselingSettingsApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Pengaturan Form Konseling</h4>
            <p class="text-muted mb-0">
                Atur pilihan form Konseling BK tanpa mengubah struktur database.
            </p>
        </div>
        <div class="sisfour-page-actions">
            <a href="<?= esc(base_url('bk/konseling')) ?>" class="btn btn-outline-secondary">
                <i class="bx bx-arrow-back me-1"></i> Kembali ke Konseling
            </a>
        </div>
    </div>

    <?php if (empty($initial['success'])): ?>
        <div class="alert alert-danger">
            <?= esc($initial['message'] ?? 'Pengaturan Form Konseling tidak dapat dibuka.') ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <strong>Format pengisian:</strong> satu pilihan per baris. Baris kosong dan pilihan duplikat akan diabaikan.
            <div class="mt-1">
                <strong>Tetap:</strong> Bidang = Pribadi, Sosial, Belajar, Karier; Status = Proses, Selesai.
            </div>
        </div>

        <div id="konselingSettingsAlert" class="alert d-none" role="alert"></div>

        <form id="formKonselingSettings" class="card">
            <div class="card-header">
                <h5 class="mb-0">Pilihan Form Konseling BK</h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-12 col-lg-6">
                        <label for="settingBentukLayanan" class="form-label fw-semibold">Bentuk Layanan</label>
                        <textarea id="settingBentukLayanan" name="bentuk_layanan" class="form-control" rows="7" required><?= esc($toLines($options['bentuk_layanan'] ?? [])) ?></textarea>
                    </div>

                    <div class="col-12 col-lg-6">
                        <label for="settingCaraHadir" class="form-label fw-semibold">Cara Siswa Hadir</label>
                        <textarea id="settingCaraHadir" name="cara_hadir" class="form-control" rows="7" required><?= esc($toLines($options['cara_hadir'] ?? [])) ?></textarea>
                    </div>

                    <div class="col-12">
                        <hr class="my-0">
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="settingTopikPribadi" class="form-label fw-semibold">Topik — Pribadi</label>
                        <textarea id="settingTopikPribadi" name="topik_pribadi" class="form-control" rows="9" required><?= esc($toLines($topik['Pribadi'] ?? [])) ?></textarea>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="settingTopikSosial" class="form-label fw-semibold">Topik — Sosial</label>
                        <textarea id="settingTopikSosial" name="topik_sosial" class="form-control" rows="9" required><?= esc($toLines($topik['Sosial'] ?? [])) ?></textarea>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="settingTopikBelajar" class="form-label fw-semibold">Topik — Belajar</label>
                        <textarea id="settingTopikBelajar" name="topik_belajar" class="form-control" rows="9" required><?= esc($toLines($topik['Belajar'] ?? [])) ?></textarea>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="settingTopikKarier" class="form-label fw-semibold">Topik — Karier</label>
                        <textarea id="settingTopikKarier" name="topik_karier" class="form-control" rows="9" required><?= esc($toLines($topik['Karier'] ?? [])) ?></textarea>
                    </div>

                    <div class="col-12">
                        <label for="settingRencana" class="form-label fw-semibold">Rencana Berikutnya</label>
                        <textarea id="settingRencana" name="rencana" class="form-control" rows="8" required><?= esc($toLines($options['rencana'] ?? [])) ?></textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex flex-column flex-sm-row justify-content-end gap-2">
                <button type="button" id="btnResetKonselingSettings" class="btn btn-outline-danger">
                    <i class="bx bx-reset me-1"></i> Kembalikan Default
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="bx bx-save me-1"></i> Simpan Pengaturan
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
