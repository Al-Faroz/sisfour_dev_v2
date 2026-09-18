<?= $this->extend('main') ?>
<?= $this->section('content') ?>
<?php $refs = $initial['refs'] ?? ['keluhan' => [], 'tindakan' => [], 'hasil' => []]; ?>

<div id="uksMasterApp" data-base-url="<?= esc(base_url()) ?>">
    <div class="sisfour-page-header">
        <div class="sisfour-page-header__copy">
            <h4 class="fw-bold mb-1">Master UKS</h4>
            <p class="text-muted mb-0">Kelola Keluhan, Tindakan, dan Hasil Kunjungan.</p>
        </div>
    </div>

    <?php if (empty($initial['success'])): ?>
        <div class="alert alert-danger"><?= esc($initial['message'] ?? 'Master UKS tidak dapat dibuka.') ?></div>
    <?php else: ?>
        <div id="uksMasterAlert" class="alert d-none"></div>
        <div class="row g-4">
            <?php
            $groups = [
                'keluhan' => ['Keluhan', 'bx-message-square-detail'],
                'tindakan' => ['Tindakan', 'bx-plus-medical'],
                'hasil' => ['Hasil Kunjungan', 'bx-check-circle'],
            ];
            foreach ($groups as $type => [$label, $icon]):
            ?>
                <div class="col-12 col-lg-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center gap-2">
                            <h5 class="mb-0"><i class="bx <?= esc($icon) ?> me-1"></i><?= esc($label) ?></h5>
                            <button class="btn btn-sm btn-primary btn-master-new" type="button" data-type="<?= esc($type) ?>">Tambah</button>
                        </div>
                        <div class="list-group list-group-flush" data-list="<?= esc($type) ?>">
                            <?php foreach (($refs[$type] ?? []) as $row): ?>
                                <?php $isActive = (int) ($row['status_aktif'] ?? 0) === 1; ?>
                                <div class="list-group-item d-flex justify-content-between align-items-start gap-2<?= $isActive ? '' : ' bg-body-tertiary' ?>" data-json="<?= esc(rawurlencode(json_encode($row))) ?>">
                                    <div class="min-w-0">
                                        <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                            <div class="fw-semibold<?= $isActive ? '' : ' text-muted' ?>"><?= esc($row['nama']) ?></div>
                                            <span class="badge <?= $isActive ? 'bg-label-success' : 'bg-label-secondary' ?>">
                                                <i class="bx <?= $isActive ? 'bx-check-circle' : 'bx-block' ?> me-1"></i><?= $isActive ? 'Aktif' : 'Nonaktif' ?>
                                            </span>
                                        </div>
                                        <div class="small text-muted">Urutan <?= (int) $row['urutan'] ?></div>
                                    </div>
                                    <div class="text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary btn-master-edit" type="button" data-type="<?= esc($type) ?>">Edit</button>
                                        <?php if ($isActive): ?>
                                            <button class="btn btn-sm btn-outline-danger btn-master-delete" type="button" data-type="<?= esc($type) ?>">Nonaktifkan</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="modal fade" id="modalUksMaster" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" id="formUksMaster">
                    <div class="modal-header"><h5 class="modal-title">Master UKS</h5><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                    <div class="modal-body">
                        <input type="hidden" name="id"><input type="hidden" name="type">
                        <div class="mb-3"><label class="form-label">Nama</label><input name="nama" maxlength="120" class="form-control" required></div>
                        <div class="row g-3"><div class="col-6"><label class="form-label">Urutan</label><input name="urutan" type="number" min="0" class="form-control" value="0"></div><div class="col-6"><label class="form-label">Status</label><select name="status_aktif" class="form-select" data-searchable-off="1"><option value="1">Aktif</option><option value="0">Nonaktif</option></select></div></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan</button></div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
